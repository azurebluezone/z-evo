<?php

namespace Tests\Feature;

use App\Models\CourierShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COD Outstanding on the Courier report
 * (docs/report-completion-plan.md Phase 1 item 2, docs/report-metrics.md 3.4).
 *
 * The page total must equal an independent SQL sum of
 * (cod_amount - remitted_amount) over the same rows, and a settlement_status
 * outside the known set must be surfaced as unknown instead of being guessed.
 *
 * Fixtures are rolled back by DatabaseTransactions.
 *
 * Marker: COD_OUTSTANDING_OK
 */
class AdminCodOutstandingTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the Courier page cannot be authenticated.');

        return $admin;
    }

    private function shipment(array $attributes): CourierShipment
    {
        $orderId = (int) DB::table('orders')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $orderId, 'The audit needs at least one order.');

        return CourierShipment::create(array_merge([
            'order_id' => $orderId,
            'provider' => 'cod-truth',
            'current_status' => 'Delivered',
        ], $attributes));
    }

    public function test_page_total_equals_independent_sql_sum(): void
    {
        $this->shipment(['provider' => 'cod-truth-a', 'cod_amount' => 1500.00, 'remitted_amount' => 500.00]);
        $this->shipment(['provider' => 'cod-truth-b', 'cod_amount' => 800.25, 'remitted_amount' => 0]);

        $expected = (float) DB::table('courier_shipments')->sum(DB::raw('cod_amount - remitted_amount'));

        $response = $this->actingAs($this->admin())->get(route('admin.reports.couriers'));
        $response->assertOk();
        $response->assertSee('data-metric="cod-outstanding"', false);

        $metrics = $response->viewData('metrics');
        $this->assertSame(
            round($expected, 2),
            round((float) $metrics['cod_outstanding'], 2),
            'The page COD total must equal an independent SQL sum over courier_shipments.'
        );

        // The per-provider column shows the same arithmetic for its own rows.
        $response->assertSee('data-cod-provider="cod-truth-a"', false);
        $response->assertSee('BDT 1,000.00');
        $response->assertSee('BDT 800.25');
    }

    public function test_unknown_settlement_state_is_shown_not_guessed(): void
    {
        $this->shipment([
            'provider' => 'cod-truth-unknown',
            'cod_amount' => 250.00,
            'remitted_amount' => 0,
            'settlement_status' => 'mystery_state',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.couriers'));
        $response->assertOk();

        $metrics = $response->viewData('metrics');
        $this->assertGreaterThanOrEqual(
            1,
            (int) $metrics['settlement_unknown'],
            'A settlement_status outside pending/remitted/settled must be counted as unknown.'
        );

        $response->assertSee('unknown settlement state');
    }
}
