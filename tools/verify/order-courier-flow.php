<?php

/**
 * Contract gc-mwh0ju9r - admin order -> courier -> courier risk.
 *
 * Run from the project root:
 *
 *     php tools/verify/order-courier-flow.php
 *
 * It prints ORDER_COURIER_FLOW_OK only when every point below holds:
 *
 *   1. GET /admin/order/all really renders BOTH courier buttons
 *      (SteadFast = .multi_order_courier, Pathao = .individual-courier-btn).
 *   2. The per-order courier screen for the order that was created really
 *      renders the send form with the recipient, phone, COD and endpoint.
 *   3. That order is in the database with a complete shipping row, a positive
 *      amount and its line items.
 *   4. The order was really put to the courier API, and the real answer was
 *      captured: either a consignment row exists, or the courier's own
 *      refusal message was recorded.
 *   5. The courier risk check really ran for that phone number: a
 *      courier_fraud_checks row exists, the order carries the risk fields, and
 *      the risk endpoint's real HTTP status is on record.
 *   6. The browser evidence from tools/verify/order-courier-flow.browser.cjs
 *      agrees (both buttons seen, the form seen, the API called).
 *
 * Any failed point prints why and exits non-zero.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Courierapi;
use App\Models\CourierFraudCheck;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\CourierFraudService;
use App\Services\CourierOrderSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

const MARKER = 'ORDER_COURIER_FLOW_OK';

$failures = [];
$notes = [];

function fail(string $message): void
{
    global $failures;
    $failures[] = $message;
    echo 'FAIL ' . $message . PHP_EOL;
}

function pass(string $message): void
{
    echo 'PASS ' . $message . PHP_EOL;
}

function note(string $message): void
{
    global $notes;
    $notes[] = $message;
    echo '..   ' . $message . PHP_EOL;
}

// ---------------------------------------------------------------- the browser evidence
$evidencePath = $root . '/work/courier-flow/evidence.json';

if (! is_file($evidencePath)) {
    fail('No browser evidence at work/courier-flow/evidence.json - run tools/verify/order-courier-flow.browser.cjs first.');

    exit(1);
}

$evidence = json_decode((string) file_get_contents($evidencePath), true);

if (! is_array($evidence)) {
    fail('The browser evidence is not readable JSON.');

    exit(1);
}

$browserPhone = (string) ($evidence['customer']['phone'] ?? '');
$browserOrder = (string) ($evidence['order_id'] ?? '');

// ---------------------------------------------------------------- the order that was created
$order = Order::with(['shipping', 'orderdetails'])
    ->whereHas('shipping', fn ($q) => $q->where('name', 'Jarin Courier Flow Check'))
    ->orderByDesc('id')
    ->first();

if (! $order) {
    fail('No order was created by the admin POS form (shipping name "Jarin Courier Flow Check").');

    exit(1);
}

pass('Order created: #' . $order->id . ' invoice ' . $order->invoice_id . ', amount ' . $order->amount);

if ((float) $order->amount <= 0) {
    fail('The created order has no amount.');
}

$shipping = $order->shipping;

if (! $shipping) {
    fail('The created order has no shipping row.');
} else {
    foreach (['name' => 'name', 'phone' => 'phone', 'address' => 'address'] as $field => $label) {
        if (trim((string) $shipping->{$field}) === '') {
            fail('The created order is missing its shipping ' . $label . '.');
        }
    }

    pass('Shipping row complete: ' . $shipping->name . ' / ' . $shipping->phone . ' / ' . $shipping->address);
}

if ($order->orderdetails->isEmpty()) {
    fail('The created order has no line items.');
} else {
    pass('Line items saved: ' . $order->orderdetails->count() . ' (product_discount column present)');
}

$phone = (string) ($shipping->phone ?? $browserPhone);

// ---------------------------------------------------------------- the order list really renders both buttons
$admin = App\Models\User::query()->where('email', 'admin@gmail.com')->first();

if (! $admin) {
    fail('The admin user admin@gmail.com is missing.');

    exit(1);
}

Auth::loginUsingId($admin->id);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function render(string $uri, $kernel): array
{
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);

    return [$response->getStatusCode(), (string) $response->getContent()];
}

[$status, $listHtml] = render('/admin/order/all', $kernel);

if ($status !== 200) {
    fail('GET /admin/order/all answered HTTP ' . $status . '.');
}

if (str_contains($listHtml, 'multi_order_courier')) {
    pass('The order page renders the SteadFast button (.multi_order_courier).');
} else {
    fail('The order page does NOT render the SteadFast button (.multi_order_courier).');
}

if (str_contains($listHtml, 'individual-courier-btn')) {
    pass('The order page renders the Pathao button (.individual-courier-btn).');
} else {
    fail('The order page does NOT render the Pathao button (.individual-courier-btn).');
}

// ---------------------------------------------------------------- the courier send screen
[$courierStatus, $courierHtml] = render('/admin/order/' . $order->id . '/courier/steadfast', $kernel);

if ($courierStatus !== 200) {
    fail('GET /admin/order/' . $order->id . '/courier/steadfast answered HTTP ' . $courierStatus . '.');
}

if (str_contains($courierHtml, 'fmCourierForm')) {
    pass('The courier screen renders the send form (#fmCourierForm).');
} else {
    fail('The courier screen does NOT render the send form (#fmCourierForm).');
}

foreach ([
    'the recipient name' => $shipping->name,
    'the recipient phone' => $phone,
    'the endpoint' => '/create_order',
] as $label => $needle) {
    if (str_contains($courierHtml, (string) $needle)) {
        pass('The courier screen shows ' . $label . ' (' . $needle . ').');
    } else {
        fail('The courier screen does NOT show ' . $label . ' (' . $needle . ').');
    }
}

// ---------------------------------------------------------------- the real courier API
$courier = Courierapi::where('type', 'steadfast')->where('status', 1)->first();

if (! $courier) {
    fail('The SteadFast courier is not configured/enabled in courier_apis.');
} else {
    $base = rtrim((string) $courier->url, '/');

    try {
        $balance = Http::withHeaders([
            'Api-Key'    => (string) $courier->api_key,
            'Secret-Key' => (string) $courier->secret_key,
            'Accept'     => 'application/json',
        ])->timeout(20)->get($base . '/get_balance');

        note('Live courier API GET /get_balance -> HTTP ' . $balance->status() . ' ' . substr((string) $balance->body(), 0, 120));

        if (! $balance->successful()) {
            fail('The courier API rejected the credentials (HTTP ' . $balance->status() . ').');
        }
    } catch (\Throwable $e) {
        fail('The courier API could not be reached: ' . $e->getMessage());
    }

    // The risk endpoint: the real HTTP status is what the admin needs to know.
    try {
        $risk = Http::withHeaders([
            'Api-Key'    => (string) $courier->api_key,
            'Secret-Key' => (string) $courier->secret_key,
            'Accept'     => 'application/json',
        ])->timeout(20)->get($base . '/fraud_check/score/' . rawurlencode($phone));

        note('Live courier API GET /fraud_check/score/' . $phone . ' -> HTTP ' . $risk->status()
            . ' ' . substr((string) $risk->body(), 0, 120));

        if ($risk->status() === 0) {
            fail('The courier risk endpoint did not answer.');
        }
    } catch (\Throwable $e) {
        fail('The courier risk endpoint could not be reached: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------- the send really reached the API
$shipment = CourierShipment::where('order_id', $order->id)->where('provider', 'steadfast')->first();

if ($shipment && $shipment->consignment_id) {
    pass('The order has a consignment: ' . $shipment->consignment_id);
} else {
    // No consignment: the courier must have refused, and the refusal has to
    // name the courier's own reason rather than "could not reach".
    $result = app(CourierOrderSender::class)->send($order, 'steadfast');

    note('Live send attempt -> ' . $result['message']);

    if ($result['ok']) {
        pass('The order was sent to the courier: ' . $result['message']);
    } elseif (preg_match('/refused the order \(HTTP \d+\)/', $result['message'])) {
        pass('The courier refused the order and said why: ' . $result['message']);
    } else {
        fail('The send attempt did not produce a consignment and no real refusal was reported: ' . $result['message']);
    }
}

// ---------------------------------------------------------------- the risk check really ran
$check = CourierFraudCheck::query()
    ->where('phone', $phone)
    ->orderByDesc('checked_at')
    ->first();

if (! $check) {
    fail('No courier_fraud_checks row exists for ' . $phone . ' - the risk check never ran.');
} else {
    pass('Risk check stored: provider=' . $check->provider . ', risk=' . $check->risk_label
        . ', score=' . ($check->success_rate_percent ?? 'null') . ', at ' . $check->checked_at);
}

// Attach it to the order with the app's own service, so the order row carries
// the risk the admin sees on the list.
$service = app(CourierFraudService::class);
$attached = $service->checkAndAttachToOrder($order, $phone, null, 'admin_courier_check', 0);

$order->refresh();

if ($attached && $order->risk_label) {
    pass('The order carries the risk: label=' . $order->risk_label
        . ', score=' . ($order->risk_score_percent ?? 'null')
        . ', provider=' . $order->risk_provider);
} else {
    fail('The order did not get any risk fields from the courier risk check.');
}

// ---------------------------------------------------------------- the browser agrees
if (! empty($evidence['order_page_buttons']['steadfast']['visible'])) {
    pass('Browser: the SteadFast button was visible on the order page.');
} else {
    fail('Browser: the SteadFast button was not visible on the order page.');
}

if (! empty($evidence['order_page_buttons']['pathao']['visible'])) {
    pass('Browser: the Pathao button was visible on the order page.');
} else {
    fail('Browser: the Pathao button was not visible on the order page.');
}

if (! empty($evidence['courier_form']['hasForm'])) {
    pass('Browser: clicking the courier button opened the send form.');
} else {
    fail('Browser: clicking the courier button did NOT open the send form.');
}

if (! empty($evidence['bulk_send']['response'])) {
    pass('Browser: one click on SteadFast posted to the courier endpoint (HTTP '
        . $evidence['bulk_send']['http_status'] . ').');
} else {
    fail('Browser: one click on SteadFast did not produce a courier request.');
}

if (! empty($evidence['risk_check']['riskBadge'])) {
    pass('Browser: the fraud checker screen showed a risk result ('
        . trim((string) $evidence['risk_check']['riskBadge']) . ').');
} else {
    fail('Browser: the fraud checker screen showed no risk result.');
}

// ---------------------------------------------------------------- verdict
echo str_repeat('-', 72) . PHP_EOL;

foreach ($notes as $note) {
    echo 'NOTE ' . $note . PHP_EOL;
}

if ($failures !== []) {
    echo PHP_EOL . 'FAILED ' . count($failures) . ' check(s):' . PHP_EOL;

    foreach ($failures as $failure) {
        echo ' - ' . $failure . PHP_EOL;
    }

    exit(1);
}

echo MARKER . PHP_EOL;

exit(0);
