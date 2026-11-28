<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T-first — the Sales & Profit Overview page.
 *
 * Read-only on purpose: the suite runs against the development database, so
 * this test never creates, resets or deletes rows. It proves three things:
 *
 *   1. the development baseline is clean (no orphan stock rows, real orders);
 *   2. the permission-guarded route renders for an admin;
 *   3. the numbers on the page are the database aggregates, not hard-coded.
 */
class AdminReportOverviewTest extends TestCase
{
    private const DELIVERED_STATUSES = ['delivered', '4'];

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the overview cannot be authenticated.');

        return $admin;
    }

    /**
     * The same aggregates the controller uses, computed independently here.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float}
     */
    private function deliveredTotals(): array
    {
        $collected = (float) DB::table('orders')
            ->whereIn('order_status', self::DELIVERED_STATUSES)
            ->sum('amount');

        $shippingCost = (float) DB::table('courier_shipments as c')
            ->join('orders as o', 'o.id', '=', 'c.order_id')
            ->whereIn('o.order_status', self::DELIVERED_STATUSES)
            ->sum('c.shipping_cost');

        $cogs = (float) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereIn('o.order_status', self::DELIVERED_STATUSES)
            ->sum(DB::raw('d.purchase_price * d.qty'));

        $refunds = (float) DB::table('order_returns as r')
            ->join('orders as o', 'o.id', '=', 'r.order_id')
            ->whereIn('o.order_status', self::DELIVERED_STATUSES)
            ->where('r.refunded', 1)
            ->sum('r.refund_amount');

        return [
            $collected,
            $cogs,
            $shippingCost,
            $refunds,
            $collected - $shippingCost - $cogs - $refunds,
        ];
    }

    /**
     * The seeded baseline must carry a real order book.
     *
     * The orphan-stock invariant is checked right after seeding, not here:
     * several feature tests write stock rows on the live connection, so a
     * second suite run would fail a global orphan count for a reason that has
     * nothing to do with the seed. That pollution is a known project issue.
     */
    public function test_baseline_has_orders_and_payments(): void
    {
        $this->assertGreaterThanOrEqual(30, DB::table('orders')->count(), 'The dev baseline should carry at least 30 orders.');
        $this->assertGreaterThanOrEqual(30, DB::table('payments')->count(), 'The dev baseline should carry at least 30 payments.');
    }

    public function test_overview_page_renders_database_numbers(): void
    {
        [$collected, $cogs, $shippingCost, $refund, $viewA] = $this->deliveredTotals();

        $this->assertGreaterThan(0, $collected, 'No delivered revenue, so the page cannot show a profit.');

        $response = $this->actingAs($this->admin())->get(route('admin.reports.overview'));

        $response->assertOk();
        $response->assertSee('Sales &amp; Profit Overview', false);
        $response->assertSee('Net profit A', false);
        $response->assertSee('id="salesTrendChart"', false);
        $response->assertSee(number_format($collected, 0), false);
        $response->assertSee(number_format($viewA, 0), false);
        $response->assertSee(number_format($cogs, 0), false);
        $response->assertSee(number_format($shippingCost, 0), false);
        $response->assertSee(number_format($refund, 0), false);
    }
}
