<?php

/**
 * The per-order courier chooser.
 *
 * Run from the project root:
 *
 *     php tools/verify/order-courier-chooser.php
 *
 * It prints COURIER_CHOOSER_OK only when:
 *
 *   1. Both SteadFast and Pathao are supported by the per-order screen - the
 *      old rule marked Pathao as bulk-only, which is no longer true.
 *   2. GET /admin/order/{id}/courier renders the chooser with a card and a link
 *      for BOTH couriers.
 *   3. The chooser and the send screen no longer print the bulk-only refusal.
 *   4. The courier-send view still carries the confirm form the chooser leads to.
 *   5. An order that already has a consignment shows it and offers no send form.
 *
 * No courier API call is made here: the dashboard check owns the live API
 * evidence, and the Pathao sandbox takes about a minute per preview.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CourierShipment;
use App\Models\User;
use App\Services\CourierOrderSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

const MARKER = 'COURIER_CHOOSER_OK';

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

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// ---------------------------------------------------------------- 1. the rule
$sender = app(CourierOrderSender::class);

foreach (['steadfast', 'pathao'] as $provider) {
    $support = $sender->perOrderSupport($provider);

    if (($support['supported'] ?? false) === true) {
        pass(ucfirst($provider) . ' is supported from the per-order screen.');
    } else {
        fail(ucfirst($provider) . ' is still refused by the per-order screen: ' . ($support['reason'] ?? 'no reason'));
    }
}

foreach (['admin.order.courier.choose', 'admin.order.courier.show', 'admin.order.courier.store'] as $route) {
    if (Route::has($route)) {
        pass('The route exists: ' . $route . '.');
    } else {
        fail('The route is missing: ' . $route . '.');
    }
}

// ---------------------------------------------------------------- 2. the chooser
$order = App\Models\Order::query()->find(511);

if (! $order) {
    fail('Order 511 does not exist.');

    exit(1);
}

$response = $kernel->handle(Illuminate\Http\Request::create('/admin/order/511/courier', 'GET'));
$html     = (string) $response->getContent();

if ($response->getStatusCode() !== 200) {
    fail('The courier chooser answered HTTP ' . $response->getStatusCode() . '.');
} else {
    pass('The courier chooser renders.');
}

foreach ([
    'SteadFast'         => 'courier/steadfast',
    'Pathao'            => 'courier/pathao',
] as $name => $href) {
    if (str_contains($html, $name) && str_contains($html, $href)) {
        pass('The chooser offers ' . $name . ' with its link.');
    } else {
        fail('The chooser does not offer ' . $name . ' (' . $href . ').');
    }
}

if (str_contains($html, 'Choose one courier')) {
    pass('The chooser states the one-order, one-courier rule.');
} else {
    fail('The chooser does not state the one-order rule.');
}

$response = $kernel->handle(Illuminate\Http\Request::create('/admin/order/511/courier/steadfast', 'GET'));
$sendHtml = (string) $response->getContent();

$bulkOnly = 'Bulk action' . ' only';

if (str_contains($html, $bulkOnly) || str_contains($sendHtml, $bulkOnly)) {
    fail('A courier is still described as bulk-only.');
} else {
    pass('No courier is described as bulk-only any more.');
}

if (str_contains($sendHtml, 'Send from here')) {
    pass('The send screen marks the couriers that can be used from here.');
} else {
    fail('The send screen does not mark the couriers it can use.');
}

// ---------------------------------------------------------------- 3. the send form
$sendView = (string) file_get_contents($root . '/resources/views/backEnd/order/courier_send.blade.php');

if (str_contains($sendView, 'id="fmCourierForm"')) {
    pass('The send screen contains the confirm form (#fmCourierForm).');
} else {
    fail('The send screen has no confirm form.');
}

$controller = (string) file_get_contents($root . '/app/Http/Controllers/Admin/OrderCourierController.php');

if (str_contains($controller, 'PathaoOrderSender') && str_contains($controller, '$this->pathao->send')) {
    pass('Pathao sends go through PathaoOrderSender.');
} else {
    fail('Pathao does not go through PathaoOrderSender.');
}

// ---------------------------------------------------------------- 4. an existing consignment
$existing = CourierShipment::query()
    ->where('order_id', 511)
    ->where('provider', 'pathao')
    ->first();

if (! $existing || ! filled($existing->consignment_id)) {
    fail('Order 511 has no Pathao consignment to test the duplicate guard.');
} else {
    $response = $kernel->handle(Illuminate\Http\Request::create('/admin/order/511/courier/pathao', 'GET'));
    $bookedHtml = (string) $response->getContent();

    if ($response->getStatusCode() !== 200) {
        fail('The booked Pathao screen answered HTTP ' . $response->getStatusCode() . '.');
    } else {
        pass('The booked Pathao screen renders.');
    }

    if (str_contains($bookedHtml, 'already has a consignment')
        && str_contains($bookedHtml, (string) $existing->consignment_id)
        && ! str_contains($bookedHtml, 'fmCourierForm')) {
        pass('The booked consignment is shown without a second send form.');
    } else {
        fail('The booked consignment screen is wrong (missing id, warning, or still has a form).');
    }
}

// ---------------------------------------------------------------- verdict
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
