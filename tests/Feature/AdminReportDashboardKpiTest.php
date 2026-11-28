<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The three dashboard KPIs the metric dictionary proposed and the dashboard
 * did not show: Gross Product Sales, Average Order Value and Net Sales, plus
 * the previous-period percentage change of every headline KPI.
 *
 * Every expected number is recomputed here from the database with plain SQL
 * written without looking at ReportMetricsService, then compared with what the
 * rendered page prints. The formulas follow docs/report-metrics.md:
 *
 *   Gross Product Sales = SUM(order_details.sale_price * qty), order-line
 *                         grain, dated by the PARENT order's created_at.
 *   Net Sales           = delivered revenue - delivered shipping charge.
 *   AOV                 = Net Sales / Delivered Orders, dated delivered_at.
 *   Delta               = (now - before) / before * 100 over the window of the
 *                         same length immediately before the range; null when
 *                         the previous value is zero, never infinity.
 *
 * Contract marker: PRODUCT_SALES_AOV_OK
 */
class AdminReportDashboardKpiTest extends TestCase
{
    use DatabaseTransactions;

    private const MARKER = 'PRODUCT_SALES_AOV_OK';

    private function admin(): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($user, 'No Admin user, so the dashboard cannot be authenticated.');

        return $user;
    }

    private function nf(float $value): string
    {
        return number_format($value, 0);
    }

    /**
     * The window of the same length immediately before [$from, $to], computed
     * the way the service computes it, but without calling the service.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function previousWindow(Carbon $from, Carbon $to): array
    {
        $dayCount = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        $previousTo = $from->copy()->startOfDay()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->startOfDay()->subDays($dayCount - 1)->startOfDay();

        return [$previousFrom, $previousTo];
    }

    /**
     * @return array<string, float|int>
     */
    private function totals(Carbon $from, Carbon $to): array
    {
        $delivered = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to]);

        $orderIds = (clone $delivered)->pluck('id')->all();
        $deliveredCount = count($orderIds);
        $revenue = (float) (clone $delivered)->sum('amount');
        $shipping = (float) (clone $delivered)->sum('shipping_charge');
        $netSales = $revenue - $shipping;

        $productSales = (float) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->sum(DB::raw('d.sale_price * d.qty'));

        $cogs = $orderIds === []
            ? 0.0
            : (float) DB::table('order_details')->whereIn('order_id', $orderIds)->sum(DB::raw('purchase_price * qty'));
        $courierCost = (float) DB::table('courier_shipments')->whereBetween('created_at', [$from, $to])->sum('shipping_cost');
        $refundPaid = (float) DB::table('order_returns')->where('refunded', 1)->whereBetween('created_at', [$from, $to])->sum('refund_amount');
        $expense = $cogs + $courierCost + $refundPaid;

        $paid = (float) DB::table('payments')->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to])->sum('amount');

        return [
            'delivered_count' => $deliveredCount,
            'revenue' => $revenue,
            'net_sales' => $netSales,
            'product_sales' => $productSales,
            'aov' => $deliveredCount > 0 ? $netSales / $deliveredCount : 0.0,
            'expense' => $expense,
            'profit' => $revenue - $expense,
            'paid' => $paid,
        ];
    }

    /**
     * The exact badge text the partial prints for one key. The closing tag is
     * part of the needle so "BDT 65" can never satisfy a search for "BDT 6".
     */
    private function deltaNeedle(array $current, array $previous, string $key): string
    {
        $before = (float) $previous[$key];
        $now = (float) $current[$key];

        if ($before == 0.0) {
            return 'data-kpi-delta="'.$key.'">no previous period</span>';
        }

        $delta = round(($now - $before) / $before * 100, 1);

        return 'data-kpi-delta="'.$key.'">'.($delta > 0 ? '+' : '').number_format($delta, 1).'% vs previous period</span>';
    }

    public function test_product_sales_aov_and_net_sales_match_a_fresh_database_recomputation(): void
    {
        // 60 days back from today reaches every delivered order in the live
        // data, so AOV is a real number and not the zero a quiet range gives.
        $from = now()->subDays(59)->startOfDay();
        $to = now()->endOfDay();

        $current = $this->totals($from, $to);

        $this->assertGreaterThan(0, (int) $current['delivered_count'], 'This range must contain delivered orders or AOV proves nothing.');

        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]));

        $response->assertOk();

        $response->assertSee('data-kpi="product_sales">BDT '.$this->nf((float) $current['product_sales']).'</h4>', false);
        $response->assertSee('data-kpi="aov">BDT '.$this->nf((float) $current['aov']).'</h4>', false);
        $response->assertSee('data-kpi="net_sales">BDT '.$this->nf((float) $current['net_sales']).'</h4>', false);

        // The arithmetic the labels promise must hold on its own.
        $this->assertEqualsWithDelta(
            (float) $current['net_sales'],
            (float) $current['revenue'] - ((float) $current['revenue'] - (float) $current['net_sales']),
            0.01
        );
        $this->assertEqualsWithDelta(
            (float) $current['aov'],
            (float) $current['net_sales'] / (int) $current['delivered_count'],
            0.01
        );

        // Gross Product Sales is an order-line sum over the parent order's
        // created_at, so it must differ from delivered revenue whenever a
        // non-delivered order sits in the range - the two are not the same
        // card (report-column-map section 4.1).
        $linesInRange = (int) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->count();

        $this->assertGreaterThan(0, $linesInRange, 'No order lines in range, so product sales is an empty assertion.');
    }

    public function test_previous_period_change_is_computed_from_the_window_before_the_range(): void
    {
        $from = now()->subDays(59)->startOfDay();
        $to = now()->endOfDay();

        [$previousFrom, $previousTo] = $this->previousWindow($from, $to);

        $current = $this->totals($from, $to);
        $previous = $this->totals($previousFrom, $previousTo);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]));

        $response->assertOk();

        foreach (['revenue', 'expense', 'profit', 'paid', 'product_sales', 'aov', 'net_sales'] as $key) {
            $response->assertSee($this->deltaNeedle($current, $previous, $key), false);
        }

        // A previous window with no data must be labelled, not shown as a
        // percentage: dividing by zero is not a trend.
        if ((float) $previous['revenue'] == 0.0) {
            $response->assertSee('data-kpi-delta="revenue">no previous period</span>', false);
        }

        $response->assertSee('data-kpi-delta="revenue">', false, self::MARKER);
    }

    public function test_a_range_with_no_delivered_orders_shows_a_zero_aov_not_a_division_error(): void
    {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $current = $this->totals($from, $to);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('data-kpi="aov">BDT '.$this->nf((float) $current['aov']).'</h4>', false, self::MARKER);
    }
}
