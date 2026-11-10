<?php

namespace Tests\Feature;

use App\Models\Courierapi;
use App\Models\CourierFraudCheck;
use App\Models\Order;
use App\Services\CourierFraudService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pathao's merchant fraud lookup and the provider chip on the order row.
 *
 * The upstream 200 payload is simulated with Http::fake: there are no live
 * Pathao merchant credentials in this workspace. What is real here is the
 * request shape, the response parsing, the token cache, the fallback, and the
 * value written to `orders.risk_provider`.
 */
class Chapter2CourierProviderTest extends TestCase
{
    private const PHONE = '01799990001';
    private const PHONE_FALLBACK = '01799990002';

    private ?int $pathaoId = null;
    private bool $pathaoExisted = false;
    private array $pathaoSnapshot = [];

    private ?int $orderId = null;
    private array $orderSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $pathao = Courierapi::query()->where('type', 'pathao')->first();

        if ($pathao) {
            $this->pathaoId = $pathao->id;
            $this->pathaoExisted = true;
            $this->pathaoSnapshot = $pathao->only([
                'type', 'title', 'url', 'api_key', 'secret_key',
                'client_id', 'client_secret', 'username', 'password',
                'token', 'status',
            ]);
        }

        $order = Order::query()->orderBy('id')->first();

        if ($order) {
            $this->orderId = $order->id;
            $this->orderSnapshot = $order->only([
                'risk_label', 'risk_score_percent', 'risk_last_checked_at',
                'risk_source', 'risk_provider',
            ]);
        }

        $this->forgetToken();
        $this->deleteChecks();
    }

    protected function tearDown(): void
    {
        $this->deleteChecks();

        if ($this->pathaoExisted && $this->pathaoId !== null) {
            Courierapi::query()->whereKey($this->pathaoId)->update($this->pathaoSnapshot);
        } elseif ($this->pathaoId !== null) {
            Courierapi::query()->whereKey($this->pathaoId)->delete();
        }

        if ($this->orderId !== null) {
            Order::query()->whereKey($this->orderId)->update($this->orderSnapshot);
        }

        $this->forgetToken();

        parent::tearDown();
    }

    public function test_pathao_merchant_api_returns_cross_merchant_delivery_record(): void
    {
        $this->configurePathao();
        $this->fakePathaoSuccess();

        $check = app(CourierFraudService::class)->checkPhone(self::PHONE);

        $this->assertSame('courier_apis', $check->provider);
        $this->assertSame(90, $check->success_rate_percent);
        $this->assertSame('normal', $check->risk_label);
        $this->assertSame(20, $check->raw_payload['api_data']['pathao']['total']);
        $this->assertSame(18, $check->raw_payload['api_data']['pathao']['delivered']);
        $this->assertSame(2, $check->raw_payload['api_data']['pathao']['cancelled']);
        $this->assertSame(['pathao'], $check->raw_payload['providers']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://merchant.pathao.com/api/v1/login'
                && ($request->data()['username'] ?? null) === 'merchant@example.com'
                && ($request->data()['password'] ?? null) === 'secret';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://merchant.pathao.com/api/v1/user/success'
                && ($request->data()['phone'] ?? null) === self::PHONE
                && $request->hasHeader('Authorization', 'Bearer tok-123');
        });
    }

    public function test_order_row_receives_the_provider_that_produced_the_score(): void
    {
        // The order is borrowed from the real database; a cleaned shop has
        // none. Skipping says the regression was not exercised, which is
        // exactly the truth.
        if ($this->orderId === null) {
            $this->markTestSkipped('No order in this database to attach the provider chip to.');
        }

        $this->configurePathao();
        $this->fakePathaoSuccess();

        $service = app(CourierFraudService::class);
        $check = $service->checkPhone(self::PHONE);
        $order = Order::query()->findOrFail($this->orderId);

        $service->attachToOrder($order, $check, 'checkout');
        $order->refresh();

        $this->assertSame('Pathao', $order->risk_provider);
        $this->assertSame('checkout', $order->risk_source);
        $this->assertSame(90, (int) $order->risk_score_percent);
    }

    public function test_pathao_login_failure_falls_back_to_local_history_honestly(): void
    {
        $this->configurePathao();

        Http::fake([
            'https://merchant.pathao.com/api/v1/login' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $check = app(CourierFraudService::class)->checkPhone(self::PHONE_FALLBACK);

        $this->assertSame('internal', $check->provider);
        $this->assertSame('unknown', $check->risk_label);
        $this->assertNull($check->success_rate_percent);
        $this->assertSame(0, $check->raw_payload['total_orders']);
        $this->assertSame('No delivery history found for this phone number', $check->raw_payload['message']);
    }

    public function test_merchant_token_is_cached_between_checks(): void
    {
        $this->configurePathao();
        $this->fakePathaoSuccess();

        $service = app(CourierFraudService::class);

        $service->checkPhone(self::PHONE);

        // Drop the result cache so the second call must ask Pathao again.
        CourierFraudCheck::query()->where('phone', self::PHONE)->delete();

        $service->checkPhone(self::PHONE);

        $loginRequests = collect(Http::recorded())
            ->filter(fn (array $pair) => str_contains($pair[0]->url(), '/api/v1/login'))
            ->count();

        $this->assertSame(1, $loginRequests, 'The merchant token should be reused for 50 minutes.');
    }

    private function configurePathao(): void
    {
        $pathao = Courierapi::query()->updateOrCreate(
            ['type' => 'pathao'],
            [
                'title' => 'Pathao',
                'url' => 'https://api-hermes.pathao.com',
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'username' => 'merchant@example.com',
                'password' => 'secret',
                'status' => 1,
            ]
        );

        $this->pathaoId = $pathao->id;
        $this->forgetToken();
    }

    private function fakePathaoSuccess(): void
    {
        Http::fake([
            'https://merchant.pathao.com/api/v1/login' => Http::response([
                'access_token' => 'tok-123',
            ], 200),
            'https://merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => [
                    'customer' => [
                        'successful_delivery' => 18,
                        'total_delivery' => 20,
                    ],
                ],
            ], 200),
        ]);
    }

    private function deleteChecks(): void
    {
        CourierFraudCheck::query()
            ->whereIn('phone', [self::PHONE, self::PHONE_FALLBACK])
            ->delete();
    }

    private function forgetToken(): void
    {
        if ($this->pathaoId !== null) {
            Cache::forget('courier.pathao.merchant_token.' . $this->pathaoId);
        }
    }
}
