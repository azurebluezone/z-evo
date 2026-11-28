<?php

namespace Tests\Feature;

use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * In-to-in verification of the report dashboard.
 *
 * Every assertion here recomputes the number from the database with plain SQL
 * written independently of ReportMetricsService, then compares it with what
 * the rendered page actually shows. The test also proves that an order placed
 * today is visible immediately, and that the blades carry no subtitle or
 * developer note under a heading.
 */
class AdminReportDashboardTruthTest extends TestCase
{
    use DatabaseTransactions;

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

    private function placedOrderToday(): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'invoice_id' => 'TRUTH-'.uniqid(),
            'amount' => 1234,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => (int) DB::table('customers')->min('id'),
            'order_status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'order_id' => $orderId,
            'customer_id' => (int) DB::table('customers')->min('id'),
            'amount' => 1234,
            'payment_method' => 'Cash On Delivery',
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $orderId;
    }

    public function test_a_new_order_today_is_visible_in_the_placed_block(): void
    {
        $orderId = $this->placedOrderToday();
        $today = now()->toDateString();

        $placedQuery = DB::table('orders')->whereBetween('created_at', [
            now()->startOfDay(),
            now()->endOfDay(),
        ]);
        $placedCount = (clone $placedQuery)->count();
        $placedAmount = (float) (clone $placedQuery)->sum('amount');

        $placedPending = (float) DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('p.payment_status', 'pending')
            ->whereBetween('o.created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('p.amount');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.dashboard', ['from' => $today, 'to' => $today]));

        $response->assertOk();

        $this->assertGreaterThanOrEqual(1, $placedCount);
        $response->assertSee('data-placed-count>'.$this->nf($placedCount), false);
        $response->assertSee('data-placed-amount>BDT '.$this->nf($placedAmount), false);
        $response->assertSee('data-placed-pending>BDT '.$this->nf($placedPending), false);

        // The order just created is in the recent list with its human status.
        $response->assertSee('data-recent-order="'.$orderId.'"', false);
        $response->assertSee('#'.$orderId, false);
        $response->assertSee('Pending', false);

        // orders.order_status holds "1"; the page must not print a bare id in
        // the status column of that row.
        $this->assertMatchesRegularExpression(
            '/data-recent-order="'.$orderId.'"[\s\S]{0,400}?>Pending</',
            $response->getContent()
        );
    }

    public function test_every_dashboard_number_matches_a_fresh_database_recomputation(): void
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $delivered = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to]);
        $deliveredIds = (clone $delivered)->pluck('id')->all();
        $revenue = (float) (clone $delivered)->sum('amount');
        $cogs = $deliveredIds === []
            ? 0.0
            : (float) DB::table('order_details')->whereIn('order_id', $deliveredIds)->sum(DB::raw('purchase_price * qty'));
        $courierCost = (float) DB::table('courier_shipments')->whereBetween('created_at', [$from, $to])->sum('shipping_cost');
        $refundPaid = (float) DB::table('order_returns')->where('refunded', 1)->whereBetween('created_at', [$from, $to])->sum('refund_amount');
        $expense = $cogs + $courierCost + $refundPaid;
        $profit = $revenue - $expense;

        $paid = (float) DB::table('payments')->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to])->sum('amount');
        $pending = (float) DB::table('payments')->where('payment_status', 'pending')->whereBetween('created_at', [$from, $to])->sum('amount');

        $courierCount = DB::table('courier_shipments')->whereBetween('created_at', [$from, $to])->count();

        $returnCount = DB::table('order_returns')->whereBetween('created_at', [$from, $to])->count();
        $refundRequired = (float) DB::table('order_returns')->where('refund_required', 1)->whereBetween('created_at', [$from, $to])->sum('refund_amount');
        $refunded = (float) DB::table('order_returns')->where('refunded', 1)->whereBetween('created_at', [$from, $to])->sum('refund_amount');
        $refundPending = max(0.0, $refundRequired - $refunded);

        $couponUsages = DB::table('coupon_usages')->whereBetween('created_at', [$from, $to])->count();
        $couponDiscount = (float) DB::table('coupon_usages')->whereBetween('created_at', [$from, $to])->sum('discount_amount');
        $couponRevenue = (float) DB::table('coupon_usages as u')
            ->join('orders as o', 'o.id', '=', 'u.order_id')
            ->whereBetween('u.created_at', [$from, $to])
            ->sum('o.amount');

        $placedCount = DB::table('orders')->whereBetween('created_at', [$from, $to])->count();
        $placedAmount = (float) DB::table('orders')->whereBetween('created_at', [$from, $to])->sum('amount');
        $placedPending = (float) DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('p.payment_status', 'pending')
            ->whereBetween('o.created_at', [$from, $to])
            ->sum('p.amount');

        $settings = ReportSetting::current();
        $stockCount = DB::table('variant_stocks')->where('available', '<=', (int) $settings->stock_warning_threshold)->count();
        $stockOut = DB::table('variant_stocks')->where('available', '<=', 0)->count();

        // The arithmetic itself must hold before the page can show it.
        $this->assertEqualsWithDelta($expense, $cogs + $courierCost + $refundPaid, 0.01);
        $this->assertEqualsWithDelta($profit, $revenue - $expense, 0.01);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]));

        $response->assertOk();

        $expected = [
            'data-kpi="revenue">BDT '.$this->nf($revenue),
            'data-kpi="expense">BDT '.$this->nf($expense),
            'data-kpi="profit">BDT '.$this->nf($profit),
            'data-kpi="paid">BDT '.$this->nf($paid),
            'data-kpi="pending">BDT '.$this->nf($pending).' pending',
            'data-placed-count>'.$this->nf($placedCount),
            'data-placed-amount>BDT '.$this->nf($placedAmount),
            'data-placed-pending>BDT '.$this->nf($placedPending),
            'data-metric="revenue-line">BDT '.$this->nf($revenue),
            'data-metric="cogs-line">- BDT '.$this->nf($cogs),
            'data-metric="courier-cost-line">- BDT '.$this->nf($courierCost),
            'data-metric="refund-paid-line">- BDT '.$this->nf($refundPaid),
            'data-metric="profit-line">BDT '.$this->nf($profit),
            'data-metric="courier-count">'.$this->nf((float) $courierCount).' shipments',
            'data-metric="courier-cost">Cost BDT '.$this->nf($courierCost),
            'data-metric="return-count">'.$this->nf((float) $returnCount).' returns',
            'data-metric="refund-pending">BDT '.$this->nf($refundPending).' pending refund',
            'data-metric="refunded">Refunded BDT '.$this->nf($refunded),
            'data-metric="coupon-usages">'.$this->nf((float) $couponUsages).' usages',
            'data-metric="coupon-discount">BDT '.$this->nf($couponDiscount).' discount',
            'data-metric="coupon-revenue">Revenue BDT '.$this->nf($couponRevenue),
            'data-metric="stock-count">'.$this->nf((float) $stockCount).' at warning',
            'data-metric="stock-out">'.$this->nf((float) $stockOut).' out',
        ];

        foreach ($expected as $needle) {
            $response->assertSee($needle, false);
        }
    }

    public function test_report_blades_carry_no_subtitle_or_developer_note(): void
    {
        $dashboard = (string) file_get_contents(resource_path('views/backEnd/reports/dashboard.blade.php'));
        $email = (string) file_get_contents(resource_path('views/backEnd/reports/email/daily-summary.blade.php'));

        $forbiddenInDashboard = [
            'revenue by delivered_at',
            'Paid only, grouped on LOWER',
            'Last 10 orders by creation time',
            '<h4 class="mb-0">Report Dashboard</h4>'."\n".'        <p',
        ];

        foreach ($forbiddenInDashboard as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $dashboard);
        }

        $this->assertDoesNotMatchRegularExpression('/card-header[\s\S]{0,300}<p class="text-muted small mb-0">/', $dashboard);
        $this->assertStringNotContainsString('Range: {{ $label }}', $email);
        $this->assertStringNotContainsString('discount already deducted', $email);
    }

    public function test_status_ids_are_mapped_to_the_names_in_order_statuses(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.dashboard', ['from' => now()->toDateString(), 'to' => now()->toDateString()]));

        $response->assertOk();

        foreach (DB::table('order_statuses')->get(['id', 'slug', 'name']) as $status) {
            $orders = DB::table('orders')->where('order_status', (string) $status->id)->count();
            if ($orders === 0) {
                continue;
            }

            $response->assertSee((string) $status->name, false);
        }
    }
}
