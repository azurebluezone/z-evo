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
        $response->assertSee('SteadFast Courier', false);
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
        $response->assertSee('SteadFast', false);
        $response->assertSee('Pathao', false);
        $response->assertSee('name="api_key"', false);
        $response->assertSee('name="client_secret"', false);

        // The marker must be the external script now: the dot in the selector
        // lives inside that file, not in the document.
        $this->assertScriptInsideDocument((string) $response->getContent(), 'admin-page-courier-settings.js');
    }

    /**
     * The settings form must look like the rest of the admin (appearance page):
     * a plain Bootstrap grid, and NO hint paragraphs under the fields. The
     * previous version carried a sentence about the base URL, a note about
     * enabling, a webhook explanation and a rotation warning, which is what the
     * screen was asked to lose.
     */
    public function test_courier_settings_form_uses_the_appearance_layout_without_hint_text(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.settings'));

        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('col-12 col-lg-6 mb-3', $html, 'Fields must use the appearance page grid.');
        $this->assertStringContainsString('class="form-label"', $html);
        $this->assertStringContainsString('class="form-control"', $html);

        // Nothing renders as a hint under an input any more.
        $this->assertStringNotContainsString('form-text', $html, 'Hint text under the inputs must be gone.');

        foreach ([
            'Paste the API key and secret key',
            'Paste the client credentials and merchant login',
            'The api/v1 root, e.g.',
            'Production: https://api-hermes.pathao.com',
            'Only a configured courier can be enabled',
            'Must be publicly reachable',
            'Rotating a token invalidates',
        ] as $removed) {
            $response->assertDontSee($removed, false);
        }
    }

    /**
     * Saving already runs the live test, so the page must print ONE line: the
     * result. Printing "Settings saved" beside a refused test said two things
     * at once, and the step list repeated the same failure in more detail than
     * the admin can act on.
     */
    public function test_connection_test_renders_one_line_and_no_step_list(): void
    {
        $message = 'Pathao could not be reached — this is a network or timeout problem, not the credentials.';

        $response = $this->actingAs($this->admin())
            ->withSession([
                'success' => 'Settings saved for Pathao.',
                'connection_test' => [
                    'ok' => false,
                    'provider' => 'pathao',
                    'message' => $message,
                    'steps' => [
                        ['label' => 'Service reachable (POST /aladdin/api/v1/issue-token)', 'ok' => false, 'detail' => 'cURL error 28: timed out'],
                        ['label' => 'Access token issued', 'ok' => false, 'detail' => 'No token returned.'],
                    ],
                ],
            ])
            ->get(route('admin.courier.settings'));

        $response->assertOk();
        $response->assertSee($message, false);

        $html = (string) $response->getContent();

        // One output: the step labels, their details and the save banner are not
        // repeated underneath it.
        $response->assertDontSee('Service reachable (POST', false);
        $response->assertDontSee('Access token issued', false);
        $response->assertDontSee('cURL error 28', false);

        // Check the save banner by its own markup: the debug toolbar echoes
        // session values into the document, so a plain "Settings saved" string
        // check would answer for the toolbar rather than for the page.
        $this->assertStringNotContainsString('<div class="alert alert-success py-2">', $html);
        $this->assertMatchesRegularExpression(
            '/alert alert-danger[^>]*>.*?network or timeout problem/s',
            $html,
            'The single result line must be the test message.'
        );

        // The button's own code must build a single line, not a <ul> of steps.
        $js = (string) file_get_contents(public_path('admin/assets/js/admin-page-courier-settings.js'));

        $this->assertStringContainsString('showLine', $js);
        $this->assertStringNotContainsString("createElement('ul')", $js);
        $this->assertStringNotContainsString('result.steps', $js);
    }

    public function test_courier_fraud_checker_page_renders(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.fraud-checker'));

        $response->assertOk();

        $html = (string) $response->getContent();

        // One phone field drives both actions: "Check now" points at the check
        // route with formaction, "Override" posts the form itself.
        $this->assertStringContainsString(
            'formaction="' . route('admin.courier.fraud-checker.check') . '"',
            $html
        );
        $this->assertStringContainsString(
            'action="' . route('admin.courier.fraud-checker.override') . '"',
            $html
        );

        // The sync card no longer explains itself in a paragraph.
        $response->assertDontSee('This does not send anything to a courier.', false);
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
