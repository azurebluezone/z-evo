<?php

namespace Tests\Feature;

use App\Models\CourierShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Courier remittance and COD reconciliation
 * (docs/report-completion-plan.md Phase 1 item 4, docs/report-metrics.md 3.4).
 *
 * A remittance entry writes a courier_settlements row and the matching
 * shipment's remitted_amount is recomputed from all settlement rows for that
 * order and provider. The reconciliation block then shows expected COD,
 * remitted, return charge and shortfall = expected - remitted - return charge,
 * equal to an independent SQL sum.
 *
 * Fixtures are rolled back by DatabaseTransactions.
 *
 * Marker: COURIER_SETTLEMENT_OK
 */
class AdminCourierSettlementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the report cannot be authenticated.');

        return $admin;
    }

    private function shipment(string $provider, float $cod, float $returnCharge): CourierShipment
    {
        $orderId = (int) DB::table('orders')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $orderId, 'The audit needs at least one order.');

        return CourierShipment::create([
            'order_id' => $orderId,
            'provider' => $provider,
            'current_status' => 'Delivered',
            'cod_amount' => $cod,
            'return_charge' => $returnCharge,
            'remitted_amount' => 0,
        ]);
    }

    private function remit(int $shipmentId, float $amount, ?string $reference = null)
    {
        return $this->actingAs($this->admin())->post(route('admin.reports.couriers.settlement'), [
            'shipment_id' => $shipmentId,
            'amount' => $amount,
            'remitted_at' => now()->toDateString(),
            'reference' => $reference,
        ]);
    }

    public function test_remittance_entry_writes_row_and_recomputes_remitted(): void
    {
        $shipment = $this->shipment('settle-courier', 1500, 50);

        $this->remit($shipment->id, 400, 'COURIER-TRUTH-1')->assertRedirect()->assertSessionHasNoErrors();
        $this->remit($shipment->id, 1000, 'COURIER-TRUTH-2')->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('courier_settlements', [
            'order_id' => $shipment->order_id,
            'provider' => 'settle-courier',
            'amount' => 400,
            'status' => 'settled',
            'reference' => 'COURIER-TRUTH-1',
        ]);

        $this->assertSame(
            2,
            DB::table('courier_settlements')
                ->where('order_id', $shipment->order_id)
                ->where('provider', 'settle-courier')
                ->count()
        );

        // The shipment row is a projection of its settlement rows.
        $this->assertSame(1400.0, (float) $shipment->fresh()->remitted_amount);
    }

    public function test_page_shortfall_equals_independent_sql_sum(): void
    {
        $shipment = $this->shipment('settle-courier-gap', 1500, 50);

        $this->remit($shipment->id, 1400)->assertRedirect();

        $expected = (float) DB::table('courier_shipments')->sum('cod_amount');
        $remitted = (float) DB::table('courier_shipments')->sum('remitted_amount');
        $returnCharge = (float) DB::table('courier_shipments')->sum('return_charge');
        $shortfall = round($expected - $remitted - $returnCharge, 2);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.couriers'));

        $response->assertOk();
        $response->assertSee('data-metric="courier-shortfall"', false);

        $metrics = $response->viewData('metrics');
        $this->assertSame(
            $shortfall,
            round((float) $metrics['courier_shortfall'], 2),
            'The page shortfall must equal expected COD - remitted - return charge from SQL.'
        );

        $response->assertSee('data-courier-shortfall="settle-courier-gap"', false);
        $response->assertSee('BDT 50.00');
    }

    public function test_support_cannot_record_a_courier_remittance(): void
    {
        $support = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Support'))->first();
        $this->assertNotNull($support, 'No Support user, so the denial cannot be proven.');

        $shipment = $this->shipment('settle-courier-denied', 500, 0);

        $this->actingAs($support)->post(route('admin.reports.couriers.settlement'), [
            'shipment_id' => $shipment->id,
            'amount' => 500,
        ])->assertForbidden();

        $this->assertSame(
            0,
            DB::table('courier_settlements')
                ->where('order_id', $shipment->order_id)
                ->where('provider', 'settle-courier-denied')
                ->count()
        );
    }
}
