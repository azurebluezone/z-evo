<?php

namespace Tests\Feature;

use App\Models\CourierFraudCheck;
use App\Models\CourierShipment;
use App\Models\Courierapi;
use App\Models\User;
use Tests\TestCase;

/**
 * The courier screens had three usability gaps that unit tests never caught:
 *
 *   - courier_shipments was empty, so the status filter offered nothing and the
 *     Filter button stretched inside a two-column flex instead of sitting with
 *     the other filters in the card header;
 *   - the settings form printed stored api_key / secret_key / client_secret /
 *     password values back into the HTML value attribute;
 *   - the fraud checker showed matching rows 50 at a time but did not show the
 *     searched number's newest result or pre-fill the override fields, so an
 *     admin had nothing to correct a success rate from.
 *
 * These tests pin the fixed behaviour from the outside, with rows inserted for
 * the length of each test and removed again.
 */
class CourierAdminHardeningTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the courier pages cannot be authenticated.');

        return $admin;
    }

    public function test_shipments_page_renders_status_data_and_filters_by_it(): void
    {
        $pending = CourierShipment::query()->create([
            'order_id'          => 0,
            'provider'          => 'pathao',
            'merchant_order_id' => 'M-HARDEN-PENDING',
            'consignment_id'    => 'C-HARDEN-PENDING',
            'tracking_code'     => 'T-HARDEN-PENDING',
            'current_status'    => 'Pending',
            'raw_payload'       => ['consignment_id' => 'C-HARDEN-PENDING'],
            'last_synced_at'    => now(),
        ]);

        $delivered = CourierShipment::query()->create([
            'order_id'          => 0,
            'provider'          => 'pathao',
            'merchant_order_id' => 'M-HARDEN-DELIVERED',
            'consignment_id'    => 'C-HARDEN-DELIVERED',
            'tracking_code'     => 'T-HARDEN-DELIVERED',
            'current_status'    => 'Delivered',
            'raw_payload'       => ['consignment_id' => 'C-HARDEN-DELIVERED'],
            'last_synced_at'    => now(),
        ]);

        try {
            $response = $this->actingAs($this->admin())
                ->get(route('admin.courier.shipments', ['status' => 'Pending']));

            $response->assertOk();
            $response->assertSee('C-HARDEN-PENDING', false);
            $response->assertDontSee('C-HARDEN-DELIVERED', false);

            $html = (string) $response->getContent();

            // The status option is built from live rows and is selected.
            $response->assertSee('value="Pending"', false);
            $this->assertMatchesRegularExpression('/value="Pending"\s+selected/', $html);

            // The filter form belongs to the card header, next to the title -
            // the house layout used by Manage Orders and Inventory - instead of
            // a second card whose Filter button stretched.
            $this->assertMatchesRegularExpression(
                '/card-header[^>]*>.*?<form method="GET" action="[^"]*\/admin\/courier\/shipments/s',
                $html,
                'The shipments filter must sit in the card header.'
            );

            // No full-width wrapper on the Filter button.
            $this->assertStringNotContainsString('btn btn-primary w-100', $html);
        } finally {
            $pending->delete();
            $delivered->delete();
        }
    }

    public function test_settings_never_prints_stored_secrets_back_into_the_page(): void
    {
        config(['debugbar.enabled' => false]);

        // The mask only renders when a secret is actually stored. The live
        // courier rows arrive empty, so store one for the length of the test
        // instead of depending on whatever data happens to be seeded.
        $courier = Courierapi::query()->where('type', 'steadfast')->first()
            ?? Courierapi::query()->orderBy('id')->first();
        $this->assertNotNull($courier, 'No courier row, so the settings form has nothing to mask.');

        $original = (string) $courier->api_key;
        $courier->api_key = 'stored_secret_' . bin2hex(random_bytes(8));

        try {
            $courier->save();

            $response = $this->actingAs($this->admin())->get(route('admin.courier.settings'));

            $response->assertOk();
            $html = (string) $response->getContent();

            foreach (Courierapi::query()->get() as $row) {
                foreach (['api_key', 'secret_key', 'client_secret', 'password'] as $field) {
                    $value = (string) $row->{$field};

                    if (strlen($value) < 6) {
                        continue;
                    }

                    $this->assertStringNotContainsString(
                        'value="' . $value . '"',
                        $html,
                        "Stored {$row->type}.{$field} was printed back into the form."
                    );
                }
            }

            $this->assertStringNotContainsString('stored_secret_', $html);

            // A configured secret shows the fixed mask, not its own characters.
            $this->assertStringContainsString('••••••••••••', $html);

            // The secret inputs submit empty: blank means "keep the saved value".
            $this->assertMatchesRegularExpression('/name="client_secret"[^>]*value=""/', $html);
        } finally {
            $courier->api_key = $original;
            $courier->save();
        }
    }

    public function test_fraud_checker_shows_and_prefills_the_searched_numbers_latest_result(): void
    {
        $phone = '01700000001';

        CourierFraudCheck::query()->where('phone', $phone)->delete();

        $older = CourierFraudCheck::query()->create([
            'phone'                => $phone,
            'provider'             => 'manual',
            'success_rate_percent' => 40,
            'risk_label'           => 'high_risk',
            'raw_payload'          => ['source' => 'test'],
            'checked_at'           => now()->subDay(),
        ]);

        $latest = CourierFraudCheck::query()->create([
            'phone'                => $phone,
            'provider'             => 'manual',
            'success_rate_percent' => 92,
            'risk_label'           => 'normal',
            'raw_payload'          => ['source' => 'test'],
            'checked_at'           => now(),
        ]);

        try {
            $response = $this->actingAs($this->admin())
                ->get(route('admin.courier.fraud-checker', ['phone' => $phone]));

            $response->assertOk();
            $response->assertSee($phone, false);
            $response->assertSee('92%', false);
            $response->assertDontSee('No saved check for this number yet', false);

            $html = (string) $response->getContent();

            // The override form starts from the saved values, so the admin can
            // correct the success rate instead of typing everything again.
            $this->assertMatchesRegularExpression(
                '/name="success_rate_percent"[^>]*value="92"/',
                $html,
                'The success-rate input must be pre-filled from the latest check.'
            );
            $this->assertMatchesRegularExpression(
                '/option value="normal"[^>]*selected/',
                $html,
                'The risk label must be pre-selected from the latest check.'
            );

            // The search button reads saved rows only; the live check is a
            // separate button.
            $this->assertStringContainsString(
                'formaction="' . route('admin.courier.fraud-checker') . '"',
                $html
            );
        } finally {
            $older->delete();
            $latest->delete();
        }
    }

    public function test_sidebar_drops_the_duplicate_status_fast_courier_entry(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.index'));

        $response->assertOk();
        $response->assertDontSee('Status Fast Courier', false);
    }

    public function test_courier_index_places_its_actions_in_the_card_header(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.courier.index'));

        $response->assertOk();

        $html = (string) $response->getContent();

        $this->assertMatchesRegularExpression(
            '/card-header[^>]*>.*?API Settings/s',
            $html,
            'The Courier page actions must sit in the card header like the rest of the admin.'
        );
        $this->assertStringContainsString('btn-square', $html);
    }

    /**
     * Contract marker: CourierFraudUxTest.
     *
     * 25 saved checks must paginate 20 + 5 with the phone filter preserved -
     * not a limit(50) wall - and the newest-result summary must stay on page 2.
     */
    public function test_CourierFraudUxTest_paginates_saved_checks_and_keeps_the_phone_filter(): void
    {
        $phone = '01700000002';

        CourierFraudCheck::query()->where('phone', $phone)->delete();

        $rows = [];
        for ($i = 1; $i <= 25; $i++) {
            $rows[] = [
                'phone'                => $phone,
                'provider'             => 'manual',
                'success_rate_percent' => 26 - $i,
                'risk_label'           => 'normal',
                'raw_payload'          => json_encode(['source' => 'test']),
                'checked_at'           => now()->subMinutes($i),
                'created_at'           => now(),
                'updated_at'           => now(),
            ];
        }
        CourierFraudCheck::query()->insert($rows);

        try {
            $page1 = $this->actingAs($this->admin())
                ->get(route('admin.courier.fraud-checker', ['phone' => $phone]));

            $page1->assertOk();
            $page1->assertSee('Showing 1–20 of 25', false);

            $html1 = (string) $page1->getContent();
            $this->assertStringContainsString('page=2', $html1);
            $this->assertStringContainsString('phone=' . $phone, $html1);

            $page2 = $this->actingAs($this->admin())
                ->get(route('admin.courier.fraud-checker', ['phone' => $phone, 'page' => 2]));

            $page2->assertOk();
            $page2->assertSee('Showing 21–25 of 25', false);

            // The newest saved result is shown above the table on every page,
            // not only where its row happens to land.
            $page2->assertSee('25%', false);
        } finally {
            CourierFraudCheck::query()->where('phone', $phone)->delete();
        }
    }

    /**
     * A masked credential must say whether a value is stored. The star-only
     * placeholder left the admin guessing; "Saved" and "Not set" are explicit.
     */
    public function test_CourierFraudUxTest_marks_each_credential_saved_or_not_set(): void
    {
        config(['debugbar.enabled' => false]);

        $steadfast = Courierapi::query()->where('type', 'steadfast')->firstOrFail();
        $originalSecret = $steadfast->secret_key;

        // "Saved" needs a stored value; the live row may be empty, so own the
        // data for the length of the test.
        $pathao = Courierapi::query()->where('type', 'pathao')->first();
        $originalClientSecret = $pathao?->client_secret;

        $steadfast->secret_key = null;
        $steadfast->save();

        if ($pathao) {
            $pathao->client_secret = 'stored_client_secret_' . bin2hex(random_bytes(6));
            $pathao->save();
        }

        try {
            $response = $this->actingAs($this->admin())->get(route('admin.courier.settings'));

            $response->assertOk();
            $response->assertSee('Saved', false);
            $response->assertSee('Not set', false);

            $html = (string) $response->getContent();

            $this->assertMatchesRegularExpression(
                '/Secret key<\/label>.*?Not set/s',
                $html,
                'The empty secret must be labelled Not set.'
            );
            $this->assertMatchesRegularExpression(
                '/Client secret<\/label>.*?Saved/s',
                $html,
                'A stored secret must be labelled Saved.'
            );
        } finally {
            $steadfast->secret_key = $originalSecret;
            $steadfast->save();

            if ($pathao) {
                $pathao->client_secret = $originalClientSecret;
                $pathao->save();
            }
        }
    }

    /**
     * Contract marker: CourierFraudWriteTest.
     *
     * The screen pre-fills the form from the newest saved row; this test proves
     * the write path behind the user's question "how does an admin add a
     * success rate?" - POST the override, land back on the same number, and see
     * the saved values in the summary and the form.
     */
    public function test_CourierFraudWriteTest_saves_success_rate_and_returns_to_the_number(): void
    {
        config(['debugbar.enabled' => false]);

        $phone = '01700000003';
        $admin = $this->admin();

        CourierFraudCheck::query()->where('phone', $phone)->delete();

        try {
            $saved = $this->actingAs($admin)->post(
                route('admin.courier.fraud-checker.override'),
                [
                    'phone'                => $phone,
                    'risk_label'           => 'risky',
                    'success_rate_percent' => 88,
                ]
            );

            $saved->assertRedirect(
                route('admin.courier.fraud-checker', ['phone' => $phone])
            );

            $this->assertDatabaseHas('courier_fraud_checks', [
                'phone'                => $phone,
                'provider'             => 'manual',
                'risk_label'           => 'risky',
                'success_rate_percent' => 88,
                'checked_by'           => $admin->id,
            ]);

            // What the admin sees right after saving: the number stays
            // selected and both the summary and the form carry the new values.
            $follow = $this->actingAs($admin)
                ->get(route('admin.courier.fraud-checker', ['phone' => $phone]));

            $follow->assertOk();
            $follow->assertSee('88%', false);

            $html = (string) $follow->getContent();

            $this->assertMatchesRegularExpression(
                '/name="success_rate_percent"[^>]*value="88"/',
                $html,
                'The success-rate input must carry the saved value.'
            );
            $this->assertMatchesRegularExpression(
                '/option value="risky"[^>]*selected/',
                $html,
                'The risk select must carry the saved label.'
            );

            // The filtered list is still paginated after the write.
            $follow->assertSee('Showing 1–1 of 1', false);
        } finally {
            CourierFraudCheck::query()->where('phone', $phone)->delete();
        }
    }
}
