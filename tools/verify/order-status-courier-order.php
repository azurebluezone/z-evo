<?php

/**
 * Contract gc-mwhawp70.
 *
 * Run from the project root:
 *
 *     php tools/verify/order-status-courier-order.php
 *
 * Prints ORDER_STATUS_COURIER_OK only when EVERY point holds:
 *
 *   1. A normal form POST to /admin/order-status redirects (no raw JSON body),
 *      while the same POST as AJAX still answers JSON success.
 *   2. The order row has a saving spinner + disabled select, the courier
 *      chooser shows a loading overlay before the slow preview, and the send
 *      form shows a sending state.
 *   3. The real storefront order for 01876543210 exists with its shipping row,
 *      customer row and a Cash On Delivery payment.
 *   4. That order has a Pathao consignment, and the live Pathao API returns the
 *      same consignment id / merchant order id.
 *   5. The fraud checker page shows the real provider answers: Pathao merchant
 *      login HTTP 400, SteadFast HTTP 403, and the saved check is labelled
 *      local history with no invented courier success rate.
 *   6. Every PHP file changed for this contract passes php -l.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CourierFraudCheck;
use App\Models\CourierShipment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipping;
use App\Models\User;
use App\Services\PathaoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

const MARKER = 'ORDER_STATUS_COURIER_OK';
const PHONE  = '01876543210';

$failures = [];

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

$admin = User::query()->where('email', 'admin@gmail.com')->first();

if (! $admin) {
    fail('The admin user admin@gmail.com is missing.');

    exit(1);
}

Auth::loginUsingId($admin->id);

$kernel  = $app->make(Illuminate\Contracts\Http\Kernel::class);
$session = $app['session']->driver();
$session->start();
$csrf = $session->token();

/** Run one request through the real HTTP kernel with the admin session. */
function send(string $method, string $uri, array $data = [], array $headers = []): Symfony\Component\HttpFoundation\Response
{
    global $kernel, $session, $csrf;

    $server = [];

    foreach ($headers as $name => $value) {
        $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
    }

    $request = Request::create($uri, $method, $data, [], [], $server);
    $request->setLaravelSession($session);
    $request->headers->set('X-CSRF-TOKEN', $csrf);

    return $kernel->handle($request);
}

// ------------------------------------------------------------------ 1. response shape
$order = Order::query()
    ->whereHas('shipping', fn ($q) => $q->where('phone', PHONE))
    ->orderByDesc('id')
    ->first();

if (! $order) {
    fail('No order exists for ' . PHONE . '. Place the customer order first.');

    exit(1);
}

$currentStatus = (int) $order->order_status;

$formResponse = send('POST', '/admin/order-status', [
    'order_ids'    => [(int) $order->id],
    'order_status' => $currentStatus,
], ['Referer' => 'http://localhost/admin/order/all']);

$formBody = (string) $formResponse->getContent();

if ($formResponse->getStatusCode() === 302) {
    pass('A normal order-status form POST redirects (HTTP 302).');
} else {
    fail('A normal order-status form POST answered HTTP ' . $formResponse->getStatusCode() . ', not a redirect.');
}

if (str_contains($formBody, '"status":"success"') || str_contains($formBody, 'Order status change successfully')) {
    fail('The normal form POST still returns the raw JSON status body.');
} else {
    pass('The normal form POST returns no raw JSON status body.');
}

$ajaxResponse = send('POST', '/admin/order-status', [
    'order_ids'    => [(int) $order->id],
    'order_status' => $currentStatus,
], [
    'X-Requested-With' => 'XMLHttpRequest',
    'Accept'           => 'application/json',
]);

$ajaxBody = (string) $ajaxResponse->getContent();

if ($ajaxResponse->getStatusCode() === 200
    && str_contains((string) $ajaxResponse->headers->get('content-type'), 'application/json')
    && str_contains($ajaxBody, '"status":"success"')
    && str_contains($ajaxBody, 'Order status change successfully')) {
    pass('The AJAX order-status POST still returns the JSON success shape.');
} else {
    fail('The AJAX order-status POST does not return the expected JSON success.');
}

// ------------------------------------------------------------------ 2. loaders
$rowView = (string) file_get_contents($root . '/resources/views/backEnd/order/_row.blade.php');
$indexJs = (string) file_get_contents($root . '/public/admin/assets/js/admin-page-order-index.js');
$chooseView = (string) file_get_contents($root . '/resources/views/backEnd/order/courier_choose.blade.php');
$sendJs  = (string) file_get_contents($root . '/public/admin/assets/js/admin-page-order-courier-send.js');

if (str_contains($rowView, 'row-status-spinner')
    && str_contains($indexJs, 'row-status-form')
    && str_contains($indexJs, "prop('disabled', true)")
    && ! str_contains($rowView, 'onchange="this.form.submit()"')) {
    pass('The row status dropdown saves in place with a spinner and a disabled select.');
} else {
    fail('The row status dropdown is missing its saving state or still auto-submits.');
}

if (str_contains($chooseView, 'courierPreviewOverlay')
    && str_contains($chooseView, 'data-courier-preview')
    && str_contains($chooseView, 'Opening the courier preview')) {
    pass('The courier chooser shows a loading overlay before the slow preview.');
} else {
    fail('The courier chooser has no preview loading overlay.');
}

if (str_contains($sendJs, 'dataset.sending')
    && str_contains($sendJs, 'Sending to the courier')
    && str_contains($sendJs, 'spinner-border-sm')) {
    pass('The courier send form shows a sending state and locks against a second submit.');
} else {
    fail('The courier send form has no submitting state.');
}

// ------------------------------------------------------------------ 3. the customer order
$shipping = Shipping::query()->where('order_id', $order->id)->first();
$payment  = Payment::query()->where('order_id', $order->id)->first();
$customer = Customer::query()->where('phone', PHONE)->first();

if ($shipping && (string) $shipping->phone === PHONE) {
    pass('The order has its shipping row with phone ' . PHONE . '.');
} else {
    fail('The order is missing its shipping row for ' . PHONE . '.');
}

if ($customer) {
    pass('A customer row exists for ' . PHONE . ' (id ' . $customer->id . ').');
} else {
    fail('No customer row exists for ' . PHONE . '.');
}

if ($payment && stripos((string) $payment->payment_method, 'cash') !== false) {
    pass('The order has a Cash On Delivery payment.');
} else {
    fail('The order has no Cash On Delivery payment.');
}

// ------------------------------------------------------------------ 4. Pathao consignment, live
$shipment = CourierShipment::query()
    ->where('order_id', $order->id)
    ->where('provider', 'pathao')
    ->first();

if ($shipment && filled($shipment->consignment_id)) {
    pass('The order has a Pathao consignment: ' . $shipment->consignment_id . '.');
} else {
    fail('The order has no Pathao consignment.');
    $shipment = null;
}

if ($shipment) {
    $config = App\Models\Courierapi::query()->where('type', 'pathao')->first();

    if (! $config) {
        fail('The Pathao courier config is missing.');
    } else {
        $client = new PathaoClient($config);
        $live   = $client->get('aladdin/api/v1/orders/' . rawurlencode((string) $shipment->consignment_id) . '/info');

        $data = $live['body']['data'] ?? null;

        if (($live['ok'] ?? false) && is_array($data)
            && (string) ($data['consignment_id'] ?? '') === (string) $shipment->consignment_id
            && (string) ($data['merchant_order_id'] ?? '') === (string) $order->invoice_id) {
            pass('The live Pathao API confirms consignment ' . $shipment->consignment_id . ' for invoice ' . $order->invoice_id . '.');
        } else {
            fail('The live Pathao API did not confirm consignment ' . $shipment->consignment_id . ': ' . json_encode($live));
        }
    }
}

// ------------------------------------------------------------------ 5. honest fraud provider status
$fraudResponse = send('GET', '/admin/courier/fraud-checker?phone=' . PHONE);
$fraudHtml     = (string) $fraudResponse->getContent();

if ($fraudResponse->getStatusCode() === 200 && str_contains($fraudHtml, 'fraud-provider-status')) {
    pass('The fraud checker renders the provider status panel.');
} else {
    fail('The fraud checker does not render the provider status panel.');
}

if (str_contains($fraudHtml, 'Merchant login failed (HTTP 400)')
    && str_contains($fraudHtml, 'The user credentials were incorrect')) {
    pass('The page shows Pathao\'s real merchant-login failure (HTTP 400).');
} else {
    fail('The page does not show Pathao\'s real merchant-login failure.');
}

if (str_contains($fraudHtml, 'Fraud check failed (HTTP 403)')
    && str_contains($fraudHtml, 'Your account is inactive')) {
    pass('The page shows SteadFast\'s real fraud-check failure (HTTP 403).');
} else {
    fail('The page does not show SteadFast\'s real fraud-check failure.');
}

$latestCheck = CourierFraudCheck::query()
    ->where('phone', PHONE)
    ->orderByDesc('checked_at')
    ->first();

if ($latestCheck && in_array((string) $latestCheck->provider, ['internal', 'internal_history'], true)
    && $latestCheck->success_rate_percent === null) {
    pass('The saved check is labelled local history with no invented courier score.');
} else {
    fail('The saved check is not honest local history (provider=' . ($latestCheck->provider ?? 'none') . ', rate=' . var_export($latestCheck->success_rate_percent ?? null, true) . ').');
}

if (! str_contains($fraudHtml, 'Pathao</td>')
    || ! preg_match('/Pathao.*?<span class="badge bg-(danger|success)">(No|Yes)<\/span>/s', $fraudHtml)) {
    fail('The provider panel does not list Pathao with an availability badge.');
} else {
    pass('The provider panel lists Pathao with an availability badge.');
}

// ------------------------------------------------------------------ 6. PHP syntax of changed files
$changedPhp = [
    'app/Http/Controllers/Admin/OrderController.php',
    'app/Http/Controllers/Admin/CourierFraudCheckerController.php',
    'app/Services/CourierFraudService.php',
];

foreach ($changedPhp as $file) {
    $path = $root . '/' . $file;

    if (! is_file($path)) {
        fail('Changed PHP file is missing: ' . $file);

        continue;
    }

    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);

    if ($exitCode === 0 && str_contains(implode(' ', $output), 'No syntax errors detected')) {
        pass('php -l passes: ' . $file);
    } else {
        fail('php -l fails: ' . $file . ' — ' . implode(' ', $output));
    }

    $output = [];
}

// ------------------------------------------------------------------ verdict
echo str_repeat('-', 72) . PHP_EOL;

if ($failures !== []) {
    echo PHP_EOL . 'FAILED ' . count($failures) . ' check(s):' . PHP_EOL;

    foreach ($failures as $failure) {
        echo ' - ' . $failure . PHP_EOL;
    }

    exit(1);
}

echo MARKER . PHP_EOL;

exit(0);
