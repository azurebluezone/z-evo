<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Profit definitions (docs/report-metrics.md 3.2, open items 2 and 12).
 *
 * Gross Profit must be exactly Net Sales - COGS. Estimated Net Profit must be
 * Delivered Revenue - COGS - Courier Cost - Refund Paid - Business Expenses.
 * Every expected number is recomputed with plain SQL, independent of
 * ReportMetricsService and ReportOverviewController.
 *
 * The business-expense fixture is rolled back by DatabaseTransactions.
 *
 * Marker: REPORT_TRUTH_OK
 */
class AdminReportProfitDefinitionTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so no profit report can be authenticated.');

        return $admin;
    }

    public function test_overview_gross_profit_is_net_sales_minus_cogs(): void
    {
        $delivered = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNotNull('delivered_at');

        $deliveredCount = (clone $delivered)->count();
        $this->assertGreaterThan(0, $deliveredCount, 'The audit needs at least one delivered order.');

        $collected = (float) (clone $delivered)->sum('amount');
        $shipping = (float) (clone $delivered)->sum('shipping_charge');
        $orderIds = (clone $delivered)->pluck('id')->all();

        $cogs = $orderIds === []
            ? 0.0
            : (float) DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->sum(DB::raw('purchase_price * qty'));

        $grossProfit = ($collected - $shipping) - $cogs;

        $html = $this->actingAs($this->admin())
            ->get(route('admin.reports.overview'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-metric="gross-profit">BDT '.number_format($grossProfit, 0).'<',
            $html,
            'REPORT_TRUTH_OK: Gross Profit is not Net Sales - COGS on the Sales & Profit page.'
        );
    }

    public function test_dashboard_expense_and_profit_include_business_expenses(): void
    {
        $categoryId = DB::table('expense_categories')->insertGetId([
            'name' => 'REPORT_TRUTH_OK category '.uniqid(),
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('business_expenses')->insert([
            'expense_category_id' => $categoryId,
            'amount' => 500,
            'spent_at' => now()->toDateString(),
            'note' => 'REPORT_TRUTH_OK fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $delivered = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to]);
        $orderIds = (clone $delivered)->pluck('id')->all();
        $revenue = (float) (clone $delivered)->sum('amount');

        $cogs = $orderIds === []
            ? 0.0
            : (float) DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->sum(DB::raw('purchase_price * qty'));

        $courierCost = (float) DB::table('courier_shipments')
            ->whereBetween('created_at', [$from, $to])
            ->sum('shipping_cost');
        $refundPaid = (float) DB::table('order_returns')
            ->where('refunded', 1)
            ->whereBetween('created_at', [$from, $to])
            ->sum('refund_amount');
        $businessExpenses = (float) DB::table('business_expenses')
            ->whereDate('spent_at', $from->toDateString())
            ->sum('amount');

        $this->assertGreaterThanOrEqual(500.0, $businessExpenses);

        $expense = $cogs + $courierCost + $refundPaid + $businessExpenses;
        $profit = $revenue - $expense;

        $html = $this->actingAs($this->admin())
            ->get(route('admin.reports.dashboard', ['from' => $from->toDateString(), 'to' => $to->toDateString()]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-kpi="expense">BDT '.number_format($expense, 0).'<',
            $html,
            'REPORT_TRUTH_OK: the dashboard expense does not include business_expenses.'
        );
        $this->assertStringContainsString(
            'data-kpi="profit">BDT '.number_format($profit, 0).'<',
            $html,
            'REPORT_TRUTH_OK: Estimated Net Profit does not subtract business_expenses.'
        );
    }
}
