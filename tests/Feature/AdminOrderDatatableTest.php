<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

/**
 * Regression: the orders DataTables feed used to 500 on every tab that had a
 * risk-scored order, because Order had no cast on risk_last_checked_at and
 * backEnd/order/_row.blade.php called ->format() on the raw string. DataTables
 * surfaced that as "warning: table id=orderTable - Ajax error" (tn/7).
 */
class AdminOrderDatatableTest extends TestCase
{
    public function test_pending_datatable_returns_json_instead_of_ajax_error(): void
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so this regression test cannot authenticate.');

        $response = $this->actingAs($admin)
            ->get('/admin/order/pending/datatable?draw=1&start=0&length=20');

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $payload = $response->json();

        $this->assertGreaterThan(0, $payload['recordsFiltered'], 'The pending tab returned no rows.');

        // The legacy slug spelling must no longer hide rows from the tab.
        $legacy = Order::query()
            ->whereRaw("LOWER(TRIM(order_status)) = 'pending'")
            ->count();
        $this->assertSame(0, $legacy);
    }

    public function test_order_row_renders_when_risk_last_checked_at_is_set(): void
    {
        $order = Order::query()->whereNotNull('risk_last_checked_at')->first();

        if (!$order) {
            $this->markTestSkipped('No risk-scored order in this database.');
        }

        $order->load('shipping', 'status', 'courierShipments');

        $html = view('backEnd.order._row', [
            'value' => $order,
            'orderstatus' => \App\Models\OrderStatus::all(),
        ])->render();

        $this->assertStringContainsString('<tr', $html);
        $this->assertStringContainsString('Checked', $html);
    }
}
