<?php

namespace Tests\Feature;

use App\Models\Courierapi;
use App\Models\CourierFraudCheck;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Shipping;
use App\Services\CourierConnectionTester;
use App\Services\CourierFraudService;
use App\Services\CourierOrderSender;
use App\Services\PathaoClient;
use App\Services\PathaoOrderSender;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The two courier send paths and the phone-check label.
 *
 * ---------------------------------------------------------------------------
 * WHAT IS SIMULATED AND WHAT IS REAL
 * ---------------------------------------------------------------------------
 * The courier HTTP endpoints are simulated with Http::fake (Pathao) and a
 * Guzzle MockHandler (SteadFast's sender, which uses Guzzle directly). No live
 * parcel is booked by this test - that was done once by hand against the
 * sandbox and returned consignment DS14112673MHHZ.
 *
 * What is REAL here is everything this project controls:
 *   - the URL rule (/aladdin comes from the documentation and was measured
 *     live: /aladdin/api/v1/issue-token = 200, /api/v1/issue-token = 404),
 *   - the request payload and headers,
 *   - the timeout constants (Pathao answers in ~20s; a 5s bound was the bug),
 *   - the distinction between "Pathao never answered" and "Pathao refused",
 *   - the preferred-store choice and the optional-location omission,
 *   - the rows written to courier_shipments and orders.
 *
 * The test creates its own order/shipping/detail rows and removes them again,
 * so it never mutates an order the shop is actually working on.
 */
class PathaoCourierFlowTest extends TestCase
{
    private const SANDBOX = 'https://courier-api-sandbox.pathao.com';
    private const STEADFAST = 'https://portal.packzy.com/api/v1';

    private const PHONE_PATH = '01890258555';
    private const PHONE_STEADFAST = '01711112233';
    private const PHONE_UNKNOWN = '01799990077';

    /** Order ids created by this test, deleted again in tearDown. */
    private array $orderIds = [];

    private ?int $pathaoId = null;
    private bool $pathaoExisted = false;
    private array $pathaoSnapshot = [];

    private ?int $steadfastId = null;
    private bool $steadfastExisted = false;
    private array $steadfastSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshot('pathao');
        $this->snapshot('steadfast');

        $this->forgetPathaoToken();
        $this->deleteFraudChecks();
    }

    protected function tearDown(): void
    {
        $this->cleanOrders();

        $this->deleteFraudChecks();

        $this->restore('pathao');
        $this->restore('steadfast');

        $this->forgetPathaoToken();

        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // 1. The URL rule
    // -----------------------------------------------------------------------

    public function test_pathao_urls_go_under_aladdin_for_every_base_shape(): void
    {
        $this->assertSame(
            self::SANDBOX . '/aladdin/api/v1/issue-token',
            PathaoClient::buildUrl(self::SANDBOX, 'api/v1/issue-token'),
            'The sandbox host serves the merchant API under /aladdin; '
                . 'measured live, /api/v1/issue-token answers 404.'
        );

        $this->assertSame(
            'https://api-hermes.pathao.com/aladdin/api/v1/orders',
            PathaoClient::buildUrl('https://api-hermes.pathao.com/', '/api/v1/orders'),
            'A trailing slash in the saved base must not produce a double slash.'
        );

        $this->assertSame(
            'https://api-hermes.pathao.com/aladdin/api/v1/stores',
            PathaoClient::buildUrl('https://api-hermes.pathao.com/aladdin/api/v1', 'api/v1/stores'),
            'A base that already carries /aladdin/api/v1 must not double the prefix.'
        );

        $this->assertSame(
            'https://api-hermes.pathao.com/aladdin/api/v1/stores',
            PathaoClient::buildUrl('https://api-hermes.pathao.com/api/v1', 'api/v1/stores'),
            'A base that already carries /api/v1 must not become /aladdin/api/v1... twice.'
        );

        $this->assertSame(
            'https://api-hermes.pathao.com/aladdin/api/v1/orders',
            PathaoClient::buildUrl('https://api-hermes.pathao.com', 'aladdin/api/v1/orders'),
            'A caller that passes the full documented path wins.'
        );
    }

    // -----------------------------------------------------------------------
    // 2. The token exchange
    // -----------------------------------------------------------------------

    public function test_pathao_token_exchange_posts_the_documented_payload_and_stores_the_token(): void
    {
        $this->configurePathao();

        Http::fake([
            self::SANDBOX . '/aladdin/api/v1/issue-token' => Http::response([
                'access_token' => 'tok-live-shape',
                'expires_in'   => 7776000,
                'token_type'   => 'Bearer',
            ], 200),
        ]);

        $config = $this->pathaoConfig();
        $config->token = null;
        $config->save();

        $client = new PathaoClient($config);

        $this->assertSame('tok-live-shape', $client->refreshToken());
        $this->assertNull($client->lastStatus, 'A successful token fetch has no HTTP failure status.');
        $this->assertSame('tok-live-shape', (string) $config->fresh()->token);
        $this->assertTrue(Cache::has('admin.courier.pathao.token.fresh'));

        Http::assertSent(function ($request) {
            return $request->url() === self::SANDBOX . '/aladdin/api/v1/issue-token'
                && $request->method() === 'POST'
                && $request->data() === [
                    'client_id'     => 'test-client-id',
                    'client_secret' => 'test-client-secret',
                    'grant_type'    => 'password',
                    'username'      => 'merchant@example.com',
                    'password'      => 'secret',
                ];
        });

        /*
         * The old timeout was 5 seconds against a host that answers in ~20.
         * The request was abandoned before Pathao had even finished saying
         * "not found", and the timeout was then reported as bad credentials.
         */
        $reflection = new \ReflectionClass(PathaoClient::class);

        $this->assertGreaterThanOrEqual(
            25,
            $reflection->getConstant('TIMEOUT_SECONDS'),
            'The total timeout must sit above the ~20s Pathao response time.'
        );

        $this->assertGreaterThanOrEqual(
            5,
            $reflection->getConstant('CONNECT_TIMEOUT_SECONDS'),
            'Connect must be bounded, but not so tight that a slow handshake fails.'
        );
    }

    // -----------------------------------------------------------------------
    // 3. Transport failure vs refused credentials
    // -----------------------------------------------------------------------

    public function test_a_transport_timeout_is_not_reported_as_bad_credentials(): void
    {
        $this->configurePathao();

        Http::fake([
            self::SANDBOX . '/aladdin/api/v1/issue-token' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30001 milliseconds');
            },
        ]);

        $client = new PathaoClient($this->pathaoConfig());

        $this->assertNull($client->refreshToken());
        $this->assertNull(
            $client->lastStatus,
            'No HTTP status means Pathao never answered - that is not an authentication verdict.'
        );
        $this->assertStringContainsString('Could not reach Pathao', (string) $client->lastError);

        $result = app(CourierConnectionTester::class)->test($this->pathaoConfig());

        $this->assertFalse($result['ok']);
        $this->assertCount(2, $result['steps'], 'Reachability and authentication are separate claims.');
        $this->assertFalse($result['steps'][0]['ok'], 'The reachability step must fail on a timeout.');
        $this->assertFalse($result['steps'][1]['ok'], 'No token can be issued when nothing answers.');
        $this->assertStringContainsString('network or timeout', $result['message']);
        $this->assertStringNotContainsString('refused the credentials', $result['message']);
    }

    public function test_a_refused_token_request_names_the_http_status_instead_of_a_timeout(): void
    {
        $this->configurePathao();

        Http::fake([
            self::SANDBOX . '/aladdin/api/v1/issue-token' => Http::response('', 401),
        ]);

        $client = new PathaoClient($this->pathaoConfig());

        $this->assertNull($client->refreshToken());
        $this->assertSame(401, $client->lastStatus);

        $result = app(CourierConnectionTester::class)->test($this->pathaoConfig());

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['steps'][0]['ok'], 'Pathao DID answer, so the service is reachable.');
        $this->assertFalse($result['steps'][1]['ok'], 'The credentials were refused, so no token exists.');
        $this->assertStringContainsString('refused the credentials (HTTP 401)', $result['message']);
    }

    // -----------------------------------------------------------------------
    // 4. Per-order Pathao send
    // -----------------------------------------------------------------------

    public function test_pathao_order_sender_books_with_the_preferred_store_and_records_the_consignment(): void
    {
        $this->configurePathao();

        // No city name in the address on purpose: Pathao resolves it, and the
        // documented rule is to OMIT city/zone/area rather than send null.
        $order = $this->makeOrder(self::PHONE_PATH, 'House 12, Road 4, Block C, Section 10, Mirpur');

        Http::fake([
            self::SANDBOX . '/aladdin/api/v1/issue-token' => Http::response([
                'access_token' => 'tok-test',
                'expires_in'   => 7776000,
            ], 200),
            self::SANDBOX . '/aladdin/api/v1/stores' => Http::response([
                'data' => [
                    'data' => [
                        ['store_id' => 11, 'store_name' => 'Some Other Store', 'is_active' => 1],
                        ['store_id' => 22, 'store_name' => 'Main Store', 'is_active' => 1, 'is_default_store' => 1],
                        ['store_id' => 33, 'store_name' => 'Returns Desk', 'is_active' => 1, 'is_default_return_store' => 1],
                        ['store_id' => 44, 'store_name' => 'Closed Store', 'is_active' => 0],
                    ],
                ],
            ], 200),
            self::SANDBOX . '/aladdin/api/v1/orders' => Http::response([
                'message' => 'Order has been placed successfully.',
                'type'    => 'success',
                'code'    => 200,
                'data'    => [
                    'consignment_id' => 'DS14112673TEST',
                    'order_status'   => 'Pending',
                    'delivery_fee'   => 60,
                ],
            ], 200),
        ]);

        $sender = app(PathaoOrderSender::class);
        $result = $sender->send($order);

        $this->assertTrue($result['ok'], (string) $result['message']);
        $this->assertSame('DS14112673TEST', $result['consignment_id']);
        $this->assertSame('Pending', $result['status']);

        $payload = $result['payload'];

        $this->assertSame(22, $payload['store_id'], 'The default store wins over whatever Pathao lists first.');
        $this->assertSame(48, $payload['delivery_type'], '48 is normal delivery.');
        $this->assertSame(2, $payload['item_type'], '2 is parcel.');
        $this->assertSame(2, $payload['item_quantity'], 'Quantity comes from the order lines.');
        $this->assertSame(450, $payload['amount_to_collect']);
        $this->assertSame(self::PHONE_PATH, $payload['recipient_phone']);
        $this->assertSame('Test Buyer', $payload['recipient_name']);
        $this->assertSame($order->invoice_id, $payload['merchant_order_id']);

        $this->assertArrayNotHasKey('recipient_city', $payload, 'Unknown location must be omitted, not null.');
        $this->assertArrayNotHasKey('recipient_zone', $payload);
        $this->assertArrayNotHasKey('recipient_area', $payload);

        Http::assertSent(function ($request) {
            return $request->url() === self::SANDBOX . '/aladdin/api/v1/orders'
                && $request->hasHeader('Authorization', 'Bearer tok-test')
                && ! array_key_exists('recipient_city', $request->data());
        });

        $shipment = CourierShipment::query()
            ->where('order_id', $order->id)
            ->where('provider', 'pathao')
            ->first();

        $this->assertNotNull($shipment, 'The consignment must be recorded so the order page can show it.');
        $this->assertSame('DS14112673TEST', $shipment->consignment_id);
        $this->assertSame('Pending', $shipment->current_status);
        $this->assertSame($order->invoice_id, $shipment->merchant_order_id);

        // A second click must not book (and bill) a second parcel.
        $again = $sender->send($order);

        $this->assertFalse($again['ok']);
        $this->assertStringContainsString('already has a Pathao consignment', $again['message']);
    }

    // -----------------------------------------------------------------------
    // 5. SteadFast send
    // -----------------------------------------------------------------------

    public function test_steadfast_sender_posts_create_order_with_its_own_headers_and_records_the_consignment(): void
    {
        $this->configureSteadfast();

        $order = $this->makeOrder(self::PHONE_STEADFAST, 'House 9, Road 2, Block B, Uttara');

        $mock = new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'status'      => 200,
                'message'     => 'Consignment has been created successfully.',
                'consignment' => [
                    'consignment_id' => 1424107,
                    'invoice'        => $order->invoice_id,
                    'tracking_code'  => '15BAEB8A',
                    'status'         => 'in_review',
                ],
            ])),
        ]);

        $sender = new CourierOrderSender(new Client(['handler' => HandlerStack::create($mock)]));
        $result = $sender->send($order, 'steadfast');

        $this->assertTrue($result['ok'], (string) $result['message']);
        $this->assertSame('1424107', $result['consignment_id']);
        $this->assertSame('15BAEB8A', $result['tracking_code']);

        $request = $mock->getLastRequest();

        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::STEADFAST . '/create_order', (string) $request->getUri());
        $this->assertSame('test-api-key', $request->getHeaderLine('Api-Key'));
        $this->assertSame('test-secret-key', $request->getHeaderLine('Secret-Key'));

        $sent = json_decode((string) $request->getBody(), true);

        $this->assertSame($order->invoice_id, $sent['invoice']);
        $this->assertSame('Test Buyer', $sent['recipient_name']);
        $this->assertSame(self::PHONE_STEADFAST, $sent['recipient_phone']);
        $this->assertSame('House 9, Road 2, Block B, Uttara', $sent['recipient_address']);
        $this->assertSame(450.0, (float) $sent['cod_amount']);
        $this->assertSame(2, $sent['total_lot']);

        $shipment = CourierShipment::query()
            ->where('order_id', $order->id)
            ->where('provider', 'steadfast')
            ->first();

        $this->assertNotNull($shipment, 'The SteadFast consignment must be recorded too.');
        $this->assertSame('1424107', $shipment->consignment_id);
        $this->assertSame('15BAEB8A', $shipment->tracking_code);
        $this->assertSame('in_review', $shipment->current_status);
    }

    // -----------------------------------------------------------------------
    // 6. The phone-check label on the order
    // -----------------------------------------------------------------------

    public function test_phone_check_label_is_written_onto_the_order_when_both_couriers_refuse(): void
    {
        $this->configurePathao();
        $this->configureSteadfast();

        $order = $this->makeOrder(self::PHONE_UNKNOWN, 'House 7, Road 1, Block A, Uttara');

        /*
         * Both answers below are the ones the LIVE endpoints actually return
         * today, not invented failures:
         *   SteadFast 403 "Your account is inactive. Please contact support."
         *   Pathao merchant login 400 "The user credentials were incorrect"
         *   (the courier sandbox client credentials are not merchant-portal
         *    credentials, so this endpoint cannot be used with them).
         *
         * The point of the test: the order still receives an honest label from
         * local history instead of silently keeping a blank or a fake score.
         */
        Http::fake([
            self::STEADFAST . '/fraud_check/score/*' => Http::response([
                'status'  => 403,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403),
            'https://merchant.pathao.com/api/v1/login' => Http::response([
                'message' => 'The user credentials were incorrect',
            ], 400),
        ]);

        $check = app(CourierFraudService::class)
            ->checkAndAttachToOrder($order, self::PHONE_UNKNOWN, null, 'checkout');

        $this->assertNotNull($check, 'A local-history check must still be recorded.');
        $this->assertSame('internal', $check->provider);
        $this->assertSame('unknown', $check->risk_label);
        $this->assertNull($check->success_rate_percent, 'No API data means no invented percentage.');

        $order->refresh();

        $this->assertSame('unknown', $order->risk_label);
        $this->assertNull($order->risk_score_percent);
        $this->assertSame('Local history', $order->risk_provider);
        $this->assertSame('checkout', $order->risk_source);
        $this->assertNotNull($order->risk_last_checked_at);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function configurePathao(): void
    {
        $row = Courierapi::query()->updateOrCreate(
            ['type' => 'pathao'],
            [
                'title'         => 'Pathao',
                'url'           => self::SANDBOX,
                'client_id'     => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'username'      => 'merchant@example.com',
                'password'      => 'secret',
                'status'        => 1,
            ]
        );

        $row->token = null;
        $row->save();

        $this->pathaoId = $row->id;
        $this->forgetPathaoToken();
    }

    private function configureSteadfast(): void
    {
        $row = Courierapi::query()->updateOrCreate(
            ['type' => 'steadfast'],
            [
                'title'      => 'SteadFast',
                'url'        => self::STEADFAST,
                'api_key'    => 'test-api-key',
                'secret_key' => 'test-secret-key',
                'status'     => 1,
            ]
        );

        $this->steadfastId = $row->id;
    }

    private function pathaoConfig(): Courierapi
    {
        return Courierapi::query()->where('type', 'pathao')->firstOrFail();
    }

    private function makeOrder(string $phone, string $address): Order
    {
        $order = new Order();
        $order->invoice_id      = 'PTEST-' . strtoupper(substr(uniqid(), -7));
        $order->amount          = 450;
        $order->discount        = 0;
        $order->shipping_charge = 60;
        $order->customer_id     = 1;
        $order->order_status    = 1;
        $order->save();

        $this->orderIds[] = (int) $order->id;

        $shipping = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = 1;
        $shipping->name        = 'Test Buyer';
        $shipping->phone       = $phone;
        $shipping->address     = $address;
        $shipping->area        = 'Mirpur';
        $shipping->save();

        $detail = new OrderDetails();
        $detail->order_id       = $order->id;
        $detail->product_id     = 1;
        $detail->product_name   = 'Test Product';
        $detail->purchase_price = 300;
        $detail->sale_price     = 450;
        $detail->qty            = 2;
        $detail->save();

        return $order->fresh(['shipping', 'orderdetails']);
    }

    private function cleanOrders(): void
    {
        foreach ($this->orderIds as $id) {
            CourierShipment::query()->where('order_id', $id)->delete();
            OrderDetails::query()->where('order_id', $id)->delete();
            Shipping::query()->where('order_id', $id)->delete();
            Order::query()->whereKey($id)->delete();
        }

        $this->orderIds = [];
    }

    private function snapshot(string $type): void
    {
        $row = Courierapi::query()->where('type', $type)->first();

        if (! $row) {
            return;
        }

        $data = $row->only([
            'type', 'title', 'url', 'api_key', 'secret_key',
            'client_id', 'client_secret', 'username', 'password',
            'token', 'status',
        ]);

        if ($type === 'pathao') {
            $this->pathaoId      = $row->id;
            $this->pathaoExisted = true;
            $this->pathaoSnapshot = $data;
        } else {
            $this->steadfastId      = $row->id;
            $this->steadfastExisted = true;
            $this->steadfastSnapshot = $data;
        }
    }

    private function restore(string $type): void
    {
        if ($type === 'pathao') {
            $id = $this->pathaoId;
            $existed = $this->pathaoExisted;
            $snapshot = $this->pathaoSnapshot;
        } else {
            $id = $this->steadfastId;
            $existed = $this->steadfastExisted;
            $snapshot = $this->steadfastSnapshot;
        }

        if ($id === null) {
            return;
        }

        if ($existed && $snapshot !== []) {
            Courierapi::query()->whereKey($id)->update($snapshot);
        } elseif (! $existed) {
            Courierapi::query()->whereKey($id)->delete();
        }
    }

    private function deleteFraudChecks(): void
    {
        CourierFraudCheck::query()
            ->whereIn('phone', [self::PHONE_PATH, self::PHONE_STEADFAST, self::PHONE_UNKNOWN])
            ->delete();
    }

    private function forgetPathaoToken(): void
    {
        Cache::forget('admin.courier.pathao.token.fresh');

        if ($this->pathaoId !== null) {
            Cache::forget('courier.pathao.merchant_token.' . $this->pathaoId);
        }
    }
}
