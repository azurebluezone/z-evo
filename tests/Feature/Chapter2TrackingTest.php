<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use App\Support\Consent;
use App\Support\EventId;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Meta Conversions API and TikTok Events API 2.0 payloads.
 *
 * Upstream calls are faked: this workspace has no live ad-account tokens, so
 * the network response is simulated while the request the application builds,
 * its headers, hashing, consent gate and dedup id are real code paths.
 */
class Chapter2TrackingTest extends TestCase
{
    private array $settingsSnapshot = [];
    private array $cookieSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $settings = GeneralSetting::firstOrFail();
        $this->settingsSnapshot = $settings->only([
            'facebook_pixel_id',
            'facebook_conversion_api_token',
            'facebook_conversion_api_version',
            'facebook_server_side_tracking',
            'tiktok_pixel_id',
            'tiktok_access_token',
            'tiktok_server_side_tracking',
            'tiktok_browser_tracking',
            'tiktok_test_mode',
            'tiktok_test_event_code',
        ]);

        $this->cookieSnapshot = $_COOKIE;
    }

    protected function tearDown(): void
    {
        GeneralSetting::query()->update($this->settingsSnapshot);
        $_COOKIE = $this->cookieSnapshot;

        parent::tearDown();
    }

    public function test_tiktok_events_api_2_payload_uses_documented_shape_and_hashes(): void
    {
        $this->configureTikTok();
        $this->grantMarketingConsent();

        $_COOKIE['ttclid'] = 'E.C.P.abc-123';
        $_COOKIE['_ttp'] = '2ABC.def-456';

        Http::fake([
            'business-api.tiktok.com/*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => ['request_id' => 'req-1'],
            ], 200),
        ]);

        $sent = app(TikTokTrackingService::class)->sendEvent(
            'InitiateCheckout',
            [
                'currency' => 'BDT',
                'value' => 1250.50,
                'content_type' => 'product',
                'contents' => [
                    ['item_id' => 77, 'item_price' => 625.25, 'quantity' => 2],
                ],
            ],
            [
                'email' => '  Buyer@Example.com ',
                'phone' => '01799990001',
                'external_id' => 'customer-9',
            ],
            '01JTESTEVENTID'
        );

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $event = $body['data'][0] ?? [];

            return $request->url() === 'https://business-api.tiktok.com/open_api/v1.3/event/track/'
                && $request->hasHeader('Access-Token', 'tt-access-token')
                && ($body['event_source'] ?? null) === 'web'
                && ($body['event_source_id'] ?? null) === 'C123TIKTOK'
                && ! isset($body['test_event_code'])
                && ($event['event'] ?? null) === 'InitiateCheckout'
                && ($event['event_id'] ?? null) === '01JTESTEVENTID'
                && abs((int) ($event['event_time'] ?? 0) - time()) < 30
                && ($event['user']['email'] ?? null) === hash('sha256', 'buyer@example.com')
                && ($event['user']['phone'] ?? null) === hash('sha256', '8801799990001')
                && ($event['user']['external_id'] ?? null) === hash('sha256', 'customer-9')
                && ($event['user']['ttclid'] ?? null) === 'E.C.P.abc-123'
                && ($event['user']['ttp'] ?? null) === '2ABC.def-456'
                && ! empty($event['user']['ip'])
                && ! empty($event['user']['user_agent'])
                && ($event['properties']['contents'][0]['content_id'] ?? null) === '77'
                && ($event['properties']['contents'][0]['price'] ?? null) === 625.25
                && ($event['properties']['contents'][0]['quantity'] ?? null) === 2
                && ($event['properties']['currency'] ?? null) === 'BDT'
                && ($event['properties']['value'] ?? null) === 1250.50
                && str_starts_with((string) ($event['page']['url'] ?? ''), 'http');
        });
    }

    public function test_tiktok_test_mode_sends_the_code_and_refuses_without_one(): void
    {
        $this->configureTikTok([
            'tiktok_test_mode' => 1,
            'tiktok_test_event_code' => 'TEST12345',
        ]);
        $this->grantMarketingConsent();

        Http::fake([
            'business-api.tiktok.com/*' => Http::response(['code' => 0], 200),
        ]);

        $this->assertTrue(app(TikTokTrackingService::class)->sendEvent('ViewContent', [], [], 'evt-test-1'));

        Http::assertSent(function ($request) {
            return ($request->data()['test_event_code'] ?? null) === 'TEST12345';
        });

        GeneralSetting::query()->update(['tiktok_test_event_code' => null]);

        $this->assertFalse(app(TikTokTrackingService::class)->sendEvent('ViewContent', [], [], 'evt-test-2'));

        Http::assertSentCount(1);
    }

    public function test_tiktok_http_200_with_nonzero_business_code_is_a_failure(): void
    {
        $this->configureTikTok();
        $this->grantMarketingConsent();

        Http::fake([
            'business-api.tiktok.com/*' => Http::response([
                'code' => 40100,
                'message' => 'rate limited',
            ], 200),
        ]);

        $sent = app(TikTokTrackingService::class)->sendEvent('ViewContent', [], [], 'evt-rate-limited');

        $this->assertFalse($sent, 'A business error must not be reported as a delivered event.');
    }

    public function test_tiktok_is_not_called_without_marketing_consent(): void
    {
        $this->configureTikTok();

        Http::fake([
            'business-api.tiktok.com/*' => Http::response(['code' => 0], 200),
        ]);

        $sent = app(TikTokTrackingService::class)->sendEvent('AddToCart', [
            'currency' => 'BDT',
            'value' => 100,
        ]);

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_meta_capi_payload_uses_website_source_hashed_user_data_and_event_id(): void
    {
        $this->configureFacebook();
        $this->grantMarketingConsent();

        $_COOKIE['_fbp'] = 'fb.1.1760000000.1234567890';
        $_COOKIE['_fbc'] = 'fb.1.1760000000.abcdef';

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $sent = app(FacebookTrackingService::class)->sendEvent(
            'Purchase',
            [
                'currency' => 'BDT',
                'value' => 980.00,
                'content_ids' => [77],
                'content_type' => 'product',
                'order_id' => 501,
                'num_items' => 2,
            ],
            [
                'email' => 'Buyer@Example.com',
                'phone' => '01799990001',
                'first_name' => 'Rahim',
                'last_name' => 'Uddin',
                'city' => 'Barishal',
                'state' => 'Barishal',
                'country' => 'BD',
                'external_id' => 'customer-9',
            ],
            '01JTESTEVENTID'
        );

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $event = $payload['data'][0] ?? [];
            $user = $event['user_data'] ?? [];

            return $request->url() === 'https://graph.facebook.com/v21.0/1234567890/events'
                && ($payload['access_token'] ?? null) === 'meta-access-token'
                && ($event['event_name'] ?? null) === 'Purchase'
                && ($event['event_id'] ?? null) === '01JTESTEVENTID'
                && ($event['action_source'] ?? null) === 'website'
                && abs((int) ($event['event_time'] ?? 0) - time()) < 30
                && ! empty($event['event_source_url'])
                && ($user['em'] ?? null) === hash('sha256', 'buyer@example.com')
                && ($user['ph'] ?? null) === hash('sha256', '8801799990001')
                && ($user['fn'] ?? null) === hash('sha256', 'rahim')
                && ($user['ln'] ?? null) === hash('sha256', 'uddin')
                && ($user['ct'] ?? null) === hash('sha256', 'barishal')
                && ($user['st'] ?? null) === hash('sha256', 'barishal')
                && ($user['country'] ?? null) === hash('sha256', 'bd')
                && ($user['external_id'] ?? null) === hash('sha256', 'customer-9')
                && ($user['fbp'] ?? null) === 'fb.1.1760000000.1234567890'
                && ($user['fbc'] ?? null) === 'fb.1.1760000000.abcdef'
                && ! empty($user['client_ip_address'])
                && ! empty($user['client_user_agent'])
                && ($event['custom_data']['value'] ?? null) === 980.00;
        });
    }

    public function test_meta_capi_is_not_called_without_marketing_consent(): void
    {
        $this->configureFacebook();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $sent = app(FacebookTrackingService::class)->sendEvent('AddToCart', ['value' => 10]);

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_tiktok_track_endpoint_forwards_the_browser_event_id(): void
    {
        $this->configureTikTok();
        $this->seedSettings(['tiktok_server_side_tracking' => 1]);

        Http::fake([
            'business-api.tiktok.com/*' => Http::response(['code' => 0], 200),
        ]);

        $consent = json_encode([
            'analytics' => 'denied',
            'marketing' => 'granted',
            'v' => Consent::VERSION,
            'ts' => time(),
        ]);

        $response = $this->withCredentials()
            ->withUnencryptedCookie('consent', $consent)
            ->withUnencryptedCookie('ttclid', 'E.C.P.route-ttclid')
            ->postJson('/api/tiktok/track', [
                'event_name' => 'AddToCart',
                'event_id' => 'tt_browser_123',
                'event_data' => ['value' => 50, 'currency' => 'BDT'],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        Http::assertSent(function ($request) {
            $event = $request->data()['data'][0] ?? [];

            return ($event['event_id'] ?? null) === 'tt_browser_123'
                && ($event['user']['ttclid'] ?? null) === 'E.C.P.route-ttclid';
        });
    }

    public function test_analytics_trend_chart_receives_a_series_array(): void
    {
        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee("name: 'Events'", false)
            ->assertSee("name: 'Visitors'", false)
            ->assertSee("name: 'Server'", false);
    }

    public function test_admin_can_save_the_tiktok_test_event_code(): void
    {
        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.social-events.update'), [
                'tiktok_pixel_id' => 'C123TIKTOK',
                'tiktok_access_token' => 'tt-access-token',
                'tiktok_server_side_tracking' => '1',
                'tiktok_test_mode' => '1',
                'tiktok_test_event_code' => 'TEST99999',
            ])
            ->assertRedirect();

        $settings = GeneralSetting::firstOrFail();

        $this->assertSame('TEST99999', $settings->tiktok_test_event_code);
        $this->assertSame(1, (int) $settings->tiktok_test_mode);
    }

    public function test_phone_normalisation_is_e164_before_hashing(): void
    {
        $this->assertSame('8801799990001', PhoneNormalizer::e164('01799990001'));
        $this->assertSame('8801799990001', PhoneNormalizer::e164('+880 1799-990001'));
        $this->assertSame('8801799990001', PhoneNormalizer::e164('8801799990001'));
        $this->assertSame('8801799990001', PhoneNormalizer::e164('1799990001'));
        $this->assertSame('', PhoneNormalizer::e164(''));
        $this->assertSame('442079460958', PhoneNormalizer::e164('+44 20 7946 0958', '44'));
    }

    public function test_purchase_event_id_is_deterministic_for_dedup(): void
    {
        $first = EventId::purchaseFor(501, 'INV-501');
        $second = EventId::purchaseFor(501, 'INV-501');

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $first);
        $this->assertNotSame($first, EventId::purchaseFor(502, 'INV-502'));
    }

    private function configureTikTok(array $overrides = []): void
    {
        $this->seedSettings(array_merge([
            'tiktok_pixel_id' => 'C123TIKTOK',
            'tiktok_access_token' => 'tt-access-token',
            'tiktok_server_side_tracking' => 1,
            'tiktok_browser_tracking' => 1,
            'tiktok_test_mode' => 0,
            'tiktok_test_event_code' => null,
        ], $overrides));
    }

    private function configureFacebook(array $overrides = []): void
    {
        $this->seedSettings(array_merge([
            'facebook_pixel_id' => '1234567890',
            'facebook_conversion_api_token' => 'meta-access-token',
            'facebook_conversion_api_version' => 'v21.0',
            'facebook_server_side_tracking' => 1,
        ], $overrides));
    }

    private function seedSettings(array $values): void
    {
        GeneralSetting::query()->update($values);
    }

    private function grantMarketingConsent(): void
    {
        $this->app['request']->cookies->set('consent', json_encode([
            'analytics' => 'denied',
            'marketing' => 'granted',
            'v' => Consent::VERSION,
            'ts' => time(),
        ]));
    }
}
