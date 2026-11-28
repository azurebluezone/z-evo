<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Return-rate truth (docs/report-metrics.md 3.5, open items 1 and 5).
 *
 * Every expected number is recomputed here with plain SQL that shares no code
 * with ReportAnalyticsController:
 *   - order rate: DISTINCT returned orders / orders, so two returns on one
 *     order count once;
 *   - item rate: returned units / units placed in the same range.
 *
 * The fixture adds two return rows to one existing order and rolls them back
 * through DatabaseTransactions, so the development database is untouched.
 *
 * Marker: REPORT_TRUTH_OK
 */
class AdminReportReturnRateTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the Returns page cannot be authenticated.');

        return $admin;
    }

    private function html(): string
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.returns'));
        $response->assertOk();

        return $response->getContent();
    }

    public function test_order_return_rate_counts_distinct_orders(): void
    {
        $orderId = (int) DB::table('orders')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $orderId, 'The audit needs at least one order.');

        // Two return rows on the SAME order. The old code counted both and
        // inflated the order-level rate.
        foreach ([1, 2] as $sequence) {
            DB::table('order_returns')->insert([
                'order_id' => $orderId,
                'status' => 'requested',
                'restocked' => 0,
                'refunded' => 0,
                'refund_required' => 0,
                'reason' => 'REPORT_TRUTH_OK fixture '.$sequence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $orderCount = DB::table('orders')->count();
        $returnRows = DB::table('order_returns')->count();
        $distinctOrders = DB::table('order_returns')->distinct()->count('order_id');
        $rate = $orderCount > 0 ? round($distinctOrders / $orderCount * 100, 1) : 0.0;

        $this->assertGreaterThan(
            $distinctOrders,
            $returnRows,
            'REPORT_TRUTH_OK: the fixture must produce more return rows than distinct returned orders.'
        );

        $html = $this->html();
        $this->assertStringContainsString(
            'data-metric="returned-orders">'.number_format($distinctOrders, 0).'<',
            $html,
            'REPORT_TRUTH_OK: the Returns page does not count distinct returned orders.'
        );
        $this->assertStringContainsString(
            'data-metric="return-rate">'.number_format($rate, 1).'%',
            $html,
            'REPORT_TRUTH_OK: the order return rate disagrees with DISTINCT order_id / orders.'
        );
    }

    public function test_item_return_rate_uses_returned_units_over_placed_units(): void
    {
        $detail = DB::table('order_details')->orderBy('id')->first(['id', 'order_id']);
        $this->assertNotNull($detail, 'The audit needs at least one order line.');

        $returnId = DB::table('order_returns')->insertGetId([
            'order_id' => (int) $detail->order_id,
            'status' => 'requested',
            'restocked' => 0,
            'refunded' => 0,
            'refund_required' => 0,
            'reason' => 'REPORT_TRUTH_OK item fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_return_items')->insert([
            'order_return_id' => $returnId,
            'order_detail_id' => (int) $detail->id,
            'qty' => 3,
            'refund_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $returnedUnits = (int) DB::table('order_return_items as i')
            ->join('order_returns as r', 'r.id', '=', 'i.order_return_id')
            ->sum('i.qty');
        $soldUnits = (int) DB::table('order_details')->sum('qty');
        $this->assertGreaterThan(0, $soldUnits, 'The audit needs at least one placed unit.');

        $rate = round($returnedUnits / $soldUnits * 100, 1);

        $html = $this->html();
        $this->assertStringContainsString(
            'data-metric="item-return-rate">'.number_format($rate, 1).'%',
            $html,
            'REPORT_TRUTH_OK: the item return rate disagrees with returned units / placed units.'
        );
    }
}
