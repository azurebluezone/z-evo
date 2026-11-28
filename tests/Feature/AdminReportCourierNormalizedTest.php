<?php

namespace Tests\Feature;

use App\Models\CourierShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Courier status normalization (docs/report-metrics.md 3.4, open item 10).
 *
 * status_normalized is written by the CourierShipment model hook, so every
 * write path (Pathao sender, SteadFast sender, webhook, admin) shares one
 * mapping. The Courier page must count In-Transit from that column only; an
 * unknown provider word keeps its own slug and is never folded into
 * "in transit" by a subtraction.
 *
 * Fixtures are rolled back by DatabaseTransactions.
 *
 * Marker: REPORT_TRUTH_OK
 */
class AdminReportCourierNormalizedTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the Courier page cannot be authenticated.');

        return $admin;
    }

    public function test_model_writes_normalized_status_on_create_and_update(): void
    {
        $orderId = (int) DB::table('orders')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $orderId, 'The audit needs at least one order.');

        $shipment = CourierShipment::create([
            'order_id' => $orderId,
            'provider' => 'truth-normalizer',
            'current_status' => 'Delivered',
        ]);

        $this->assertSame('delivered', $shipment->fresh()->status_normalized);

        $shipment->current_status = 'in_transit';
        $shipment->save();

        $this->assertSame('in_transit', $shipment->fresh()->status_normalized);
    }

    public function test_in_transit_is_counted_from_normalized_status_only(): void
    {
        $orderId = (int) DB::table('orders')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $orderId, 'The audit needs at least one order.');

        // An unknown word must not be counted as in transit.
        $unknown = CourierShipment::create([
            'order_id' => $orderId,
            'provider' => 'truth-unknown',
            'current_status' => 'mystery_word_xyz',
        ]);

        $this->assertSame('mystery_word_xyz', $unknown->fresh()->status_normalized);

        // A real moving shipment, written through the same hook.
        CourierShipment::create([
            'order_id' => $orderId,
            'provider' => 'truth-moving',
            'current_status' => 'in_transit',
        ]);

        $shipments = DB::table('courier_shipments')->count();
        $delivered = DB::table('courier_shipments')->where('status_normalized', 'delivered')->count();
        $returned = DB::table('courier_shipments')
            ->whereIn('status_normalized', ['returned', 'cancelled'])
            ->count();
        $inTransit = DB::table('courier_shipments')->where('status_normalized', 'in_transit')->count();

        // The old derived formula would include the unknown row.
        $derived = $shipments - $delivered - $returned;
        $this->assertGreaterThan($inTransit, $derived, 'REPORT_TRUTH_OK: the fixture must expose the old subtraction bug.');

        $html = $this->actingAs($this->admin())
            ->get(route('admin.reports.couriers'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-metric="in-transit">'.number_format($inTransit, 0).'<',
            $html,
            'REPORT_TRUTH_OK: In-Transit is not counted from status_normalized.'
        );
        $this->assertStringContainsString(
            'data-metric="returned">'.number_format($returned, 0).' returned<',
            $html,
            'REPORT_TRUTH_OK: Returned shipments disagree with status_normalized.'
        );
    }
}
