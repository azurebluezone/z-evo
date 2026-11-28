<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Admin\ReportMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The dashboard has three charts: daily placed-order trend, order-status
 * donut and payment-status distribution. Every series must come from the same
 * selected range and must agree with a fresh database recomputation.
 */
class AdminReportDashboardChartsTest extends TestCase
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

    private function service(): ReportMetricsService
    {
        return app(ReportMetricsService::class);
    }

    public function test_dashboard_renders_three_chart_containers_and_payload(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => now()->subDays(6)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('data-chart="sales-trend"', false);
        $response->assertSee('data-chart="status-donut"', false);
        $response->assertSee('data-chart="payment-mix"', false);
        $response->assertSee('id="dashboard-chart-data"', false);
        $response->assertSee('data-sales-trend=', false);
        $response->assertSee('data-status-mix=', false);
        $response->assertSee('data-payment-mix=', false);
    }

    public function test_trend_status_and_payment_series_match_the_database(): void
    {
        $from = now()->subDays(6)->startOfDay();
        $to = now()->endOfDay();

        $summary = $this->service()->summary($from, $to);

        $this->assertArrayHasKey('sales_trend', $summary);
        $this->assertArrayHasKey('status_mix', $summary);
        $this->assertArrayHasKey('payment_mix', $summary);
        $this->assertCount(7, $summary['sales_trend']);

        foreach ($summary['sales_trend'] as $row) {
            $this->assertArrayHasKey('date', $row);
            $this->assertArrayHasKey('label', $row);
            $this->assertArrayHasKey('orders', $row);
            $this->assertArrayHasKey('amount', $row);
        }

        $placedCount = DB::table('orders')->whereBetween('created_at', [$from, $to])->count();
        $placedAmount = (float) DB::table('orders')->whereBetween('created_at', [$from, $to])->sum('amount');

        $this->assertSame($placedCount, array_sum(array_column($summary['sales_trend'], 'orders')));
        $this->assertEqualsWithDelta($placedAmount, array_sum(array_column($summary['sales_trend'], 'amount')), 0.01);

        $this->assertSame($placedCount, array_sum(array_column($summary['status_mix'], 'orders')));

        $paymentCount = DB::table('payments')->whereBetween('created_at', [$from, $to])->count();
        $this->assertSame($paymentCount, array_sum(array_column($summary['payment_mix'], 'count')));
    }

    public function test_a_quiet_range_is_zero_filled_and_has_no_mix_rows(): void
    {
        $summary = $this->service()->summary(
            Carbon::parse('2001-02-03')->startOfDay(),
            Carbon::parse('2001-02-05')->endOfDay()
        );

        $this->assertCount(3, $summary['sales_trend']);
        $this->assertSame(0, array_sum(array_column($summary['sales_trend'], 'orders')));
        $this->assertEqualsWithDelta(0.0, array_sum(array_column($summary['sales_trend'], 'amount')), 0.01);
        $this->assertSame([], $summary['status_mix']);
        $this->assertSame([], $summary['payment_mix']);
    }
}
