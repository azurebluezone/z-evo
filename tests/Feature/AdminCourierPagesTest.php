<?php

namespace Tests\Feature;

use App\Models\CourierShipment;
use App\Models\User;
use Tests\TestCase;

/**
 * Regression: three courier views kept their <script> AFTER @endsection, and
 * Blade only renders what sits inside a @section of an @extends child - so the
 * block was dropped, the layout never saw it, and the Test/Copy buttons on
 * /admin/courier, /admin/courier/{type}/edit and the shipment page were dead.
 * Each page is now asserted to be reachable AND to carry its script inside the
 * rendered document (before </html>).
 */
class AdminCourierPagesTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the courier pages cannot be authenticated.');

        return $admin;
    }

    public function test_courier_index_is_an_overview_without_a_second_credentials_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.index'));

        $response->assertOk();
        $response->assertSee('Steadfast', false);
        $response->assertSee('API settings', false);

        // The keys belong to the one settings screen now. Their field names
        // must not appear in an overview table.
        $response->assertDontSee('name="api_key"', false);
        $response->assertDontSee('name="client_secret"', false);
    }

    public function test_courier_edit_redirects_to_the_one_settings_screen(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.edit', 'steadfast'));

        $response->assertRedirect(route('admin.courier.settings') . '#courier-steadfast');
    }

    public function test_courier_settings_page_renders_both_forms_and_its_test_script(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.settings'));

        $response->assertOk();
        $response->assertSee('Steadfast', false);
        $response->assertSee('Pathao', false);
        $response->assertSee('name="api_key"', false);
        $response->assertSee('name="client_secret"', false);

        $this->assertScriptInsideDocument((string) $response->getContent(), '.js-courier-test');
    }

    public function test_courier_fraud_checker_page_renders(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.fraud-checker'));

        $response->assertOk();
    }

    public function test_courier_shipments_list_renders_with_dynamic_table(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.shipments'));

        $response->assertOk();
    }

    public function test_legacy_courier_api_page_redirects_to_api_settings(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/courierapi/manage');

        $response->assertRedirect(route('admin.courier.settings'));
    }

    public function test_single_shipment_page_renders_with_copy_script(): void
    {
        // courier_shipments is empty in this database, so the page was never
        // reachable and its script fix stayed unproven. A row is inserted for
        // the length of the request and removed again in finally, so the test
        // proves the real route binding + render path without leaving data.
        $order = \App\Models\Order::query()->latest('id')->first();

        if (!$order) {
            $this->markTestSkipped('No order to attach a shipment to.');
        }

        $shipment = CourierShipment::query()->create([
            'order_id'          => $order->id,
            'provider'          => 'steadfast',
            'merchant_order_id' => 'M-TEST-1',
            'consignment_id'    => 'C-TEST-1',
            'tracking_code'     => 'T-TEST-1',
            'current_status'    => 'pending',
            'raw_payload'       => ['consignment_id' => 'C-TEST-1'],
            'last_synced_at'    => now(),
        ]);

        try {
            $response = $this->actingAs($this->admin())
                ->get(route('admin.courier.shipment', $shipment));

            $response->assertOk();
            $response->assertSee('C-TEST-1', false);
            $this->assertScriptInsideDocument((string) $response->getContent(), 'copy-payload');
        } finally {
            $shipment->delete();
        }

        $this->assertDatabaseMissing('courier_shipments', ['id' => $shipment->id]);
    }

    /**
     * The script must land after the head and before </html>. Blade puts a
     * section into @yield('script') near the end of the layout; a block that
     * was discarded (or echoed before <!doctype>) fails one of these.
     */
    private function assertScriptInsideDocument(string $html, string $marker): void
    {
        $head = strpos($html, '</head>');
        $end  = strpos($html, '</html>');
        $at   = strpos($html, $marker);

        $this->assertNotFalse($head, 'No </head> in the response.');
        $this->assertNotFalse($end,  'No </html> in the response.');
        $this->assertNotFalse($at,   "Marker [{$marker}] never reached the document.");
        $this->assertGreaterThan($head, $at, "Marker [{$marker}] rendered before </head>.");
        $this->assertLessThan($end, $at, "Marker [{$marker}] rendered after </html>.");
    }
}
