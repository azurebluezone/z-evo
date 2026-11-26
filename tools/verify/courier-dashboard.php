<?php

/**
 * Contract gc-mwh9mwmm - the courier dashboard must show real business data,
 * and one order must be sendable to either courier.
 *
 * Run from the project root:
 *
 *     php tools/verify/courier-dashboard.php
 *
 * It prints COURIER_VIEW_OK only when every point below holds:
 *
 *   1. GET /admin/courier/dashboard renders 200 with the KPI cards, the live
 *      SteadFast balance, the real courier_shipments rows, the status
 *      breakdown, and none of the old developer-panel text ("Webhook",
 *      "API route", "get_all_orders", "Courier dashboard features").
 *   2. The dashboard service calls only the documented endpoints
 *      (/get_balance, /parcel_states, /stats, Pathao stores) and none of the
 *      invented 404 probes.
 *   3. GET /admin/order/511/courier renders the chooser listing BOTH couriers
 *      with a link to each.
 *   4. The order-row truck icon links to the chooser route, never to a
 *      hardcoded SteadFast preview.
 *   5. GET /admin/order/511/courier/pathao shows the existing consignment
 *      without rebuilding or sending it, and a fresh Pathao preview renders
 *      the send form without creating a shipment row.
 *   6. The settings and courier-send screens contain no webhook token or
 *      endpoint UI.
 *   7. Every changed PHP file passes php -l, and the existing courier chooser
 *      check has been updated to the per-order rule.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierDashboardService;
use Illuminate\Support\Facades\Auth;

const MARKER = 'COURIER_VIEW_OK';

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

function note(string $message): void
{
    echo '..   ' . $message . PHP_EOL;
}

/**
 * One admin GET through the real HTTP kernel.
 *
 * @return array{status:int, html:string, seconds:float}
 */
function getPage($kernel, string $path): array
{
    $started = microtime(true);

    $response = $kernel->handle(Illuminate\Http\Request::create($path, 'GET'));

    return [
        'status'  => $response->getStatusCode(),
        'html'    => (string) $response->getContent(),
        'seconds' => round(microtime(true) - $started, 2),
    ];
}

/** PHP source with comments removed, so a docblock cannot fail a code check. */
function codeWithoutComments(string $file): string
{
    $source = (string) file_get_contents($file);
    $clean  = '';

    foreach (token_get_all($source) as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $clean .= $token[1];

            continue;
        }

        $clean .= $token;
    }

    return $clean;
}

// ============================================================== 1. the dashboard
$admin = User::query()->where('email', 'admin@gmail.com')->first();

if (! $admin) {
    fail('The admin user admin@gmail.com is missing.');

    exit(1);
}

Auth::loginUsingId($admin->id);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$page = getPage($kernel, '/admin/courier/dashboard');

note('GET /admin/courier/dashboard -> HTTP ' . $page['status'] . ' in ' . $page['seconds'] . 's');

if ($page['status'] !== 200) {
    fail('GET /admin/courier/dashboard answered HTTP ' . $page['status'] . '.');
} else {
    pass('The courier dashboard renders.');
}

$html = $page['html'];

$visible = [
    'the parcels-booked KPI'   => 'Parcels booked',
    'the awaiting-delivery KPI' => 'Awaiting delivery',
    'the cash-to-collect KPI'  => 'Cash to collect',
    'the live balance heading' => 'Balance at courier',
    'the live balance source'  => 'Live from SteadFast',
    'the recent parcel table'  => 'Recent parcels',
    'the status breakdown'     => 'Delivery status',
];

foreach ($visible as $label => $needle) {
    if (str_contains($html, $needle)) {
        pass('The dashboard shows ' . $label . '.');
    } else {
        fail('The dashboard does NOT show ' . $label . ' ("' . $needle . '").');
    }
}

foreach (['Webhook', 'API route', 'get_all_orders', 'Courier dashboard features'] as $forbidden) {
    if (stripos($html, $forbidden) !== false) {
        fail('The dashboard still contains developer-panel text: "' . $forbidden . '".');
    } else {
        pass('The dashboard contains no "' . $forbidden . '" text.');
    }
}

$shipments = CourierShipment::query()->orderByDesc('id')->limit(3)->get();

if ($shipments->isEmpty()) {
    fail('No courier_shipments rows exist, so there is no real parcel data to show.');
} else {
    foreach ($shipments as $shipment) {
        $consignment = trim((string) $shipment->consignment_id);

        if ($consignment !== '' && str_contains($html, $consignment)) {
            pass('A real shipment row is on screen: ' . $consignment . ' (' . $shipment->provider . ').');
        } else {
            fail('Shipment #' . $shipment->id . ' (' . $consignment . ') is missing from the dashboard.');
        }
    }
}

$statusRows = CourierShipment::query()
    ->selectRaw('current_status, COUNT(*) AS aggregate')
    ->groupBy('current_status')
    ->get();

if ($statusRows->isEmpty()) {
    fail('No shipment status exists, so the status breakdown cannot be real.');
} else {
    $shown = 0;

    foreach ($statusRows as $row) {
        $label = CourierDashboardService::statusMeta($row->current_status)['label'];

        if (str_contains($html, $label)) {
            $shown++;
        }
    }

    if ($shown > 0) {
        pass('The status breakdown shows the real courier status label(s).');
    } else {
        fail('The status breakdown does not show any status stored in courier_shipments.');
    }
}

// ============================================================== 2. the service endpoints
$serviceFile = $root . '/app/Services/CourierDashboardService.php';
$serviceCode = codeWithoutComments($serviceFile);

foreach (['/get_balance', '/parcel_states', '/stats', 'stores()'] as $endpoint) {
    if (str_contains($serviceCode, $endpoint)) {
        pass('The dashboard service uses the documented endpoint ' . $endpoint . '.');
    } else {
        fail('The dashboard service does not use ' . $endpoint . '.');
    }
}

foreach (['get_all_orders', 'get_all_pickup_stores', 'get_product_categories'] as $probe) {
    if (str_contains($serviceCode, $probe)) {
        fail('The dashboard service still calls the invented 404 route ' . $probe . '.');
    } else {
        pass('The dashboard service never calls ' . $probe . '.');
    }
}

// ============================================================== 3. the chooser
$orderId = 511;
$order   = Order::query()->find($orderId);

if (! $order) {
    fail('Order ' . $orderId . ' does not exist, so the chooser check cannot run.');
} else {
    $page = getPage($kernel, '/admin/order/' . $orderId . '/courier');

    note('GET /admin/order/' . $orderId . '/courier -> HTTP ' . $page['status'] . ' in ' . $page['seconds'] . 's');

    if ($page['status'] !== 200) {
        fail('The courier chooser answered HTTP ' . $page['status'] . '.');
    } else {
        pass('The courier chooser renders for order #' . $orderId . '.');

        foreach ([
            'the SteadFast card' => ['SteadFast', 'courier/steadfast'],
            'the Pathao card'    => ['Pathao', 'courier/pathao'],
        ] as $label => [$name, $href]) {
            if (str_contains($page['html'], $name) && str_contains($page['html'], $href)) {
                pass('The chooser lists ' . $label . ' with a working link.');
            } else {
                fail('The chooser does not list ' . $label . ' with its link (' . $href . ').');
            }
        }

        if (str_contains($page['html'], 'Choose one courier')) {
            pass('The chooser explains that one courier is chosen at a time.');
        } else {
            fail('The chooser does not explain the one-courier rule.');
        }
    }
}

// ============================================================== 4. the truck icon
$rowFile = $root . '/resources/views/backEnd/order/_row.blade.php';
$rowMarkup = (string) file_get_contents($rowFile);

if (str_contains($rowMarkup, "route('admin.order.courier.choose'")) {
    pass('The order-row truck icon links to the courier chooser.');
} else {
    fail('The order-row truck icon does not link to admin.order.courier.choose.');
}

if (str_contains($rowMarkup, 'courier.show')) {
    fail('The order-row truck icon still links straight to a courier preview.');
} else {
    pass('The order-row truck icon has no hardcoded courier preview link.');
}

// ============================================================== 5. Pathao preview
$existing = CourierShipment::query()
    ->where('order_id', $orderId)
    ->where('provider', 'pathao')
    ->first();

if (! $existing || ! filled($existing->consignment_id)) {
    fail('Order ' . $orderId . ' has no Pathao consignment to guard against a duplicate send.');
} else {
    $page = getPage($kernel, '/admin/order/' . $orderId . '/courier/pathao');

    note('GET /admin/order/' . $orderId . '/courier/pathao -> HTTP ' . $page['status'] . ' in ' . $page['seconds'] . 's');

    if ($page['status'] !== 200) {
        fail('The Pathao consignment screen answered HTTP ' . $page['status'] . '.');
    } else {
        pass('The Pathao consignment screen renders.');
    }

    if (str_contains($page['html'], 'already has a consignment')) {
        pass('The screen says the order is already booked.');
    } else {
        fail('The screen does not say the order already has a consignment.');
    }

    if (str_contains($page['html'], (string) $existing->consignment_id)) {
        pass('The existing Pathao consignment id is on screen: ' . $existing->consignment_id . '.');
    } else {
        fail('The existing Pathao consignment id is missing from the screen.');
    }

    if (! str_contains($page['html'], 'fmCourierForm')) {
        pass('No send form is offered for an already-booked consignment.');
    } else {
        fail('A send form is still offered for an already-booked consignment.');
    }
}

/*
 * A fresh order: complete shipping, no Pathao consignment. Its preview page is
 * the one that must render the send form. The sandbox takes ~60s for the token
 * and lookup calls, so there is a note with the measured time.
 */
$fresh = Order::query()
    ->whereHas('shipping', function ($query) {
        $query->whereNotNull('name')->where('name', '!=', '')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->whereNotNull('address')->where('address', '!=', '');
    })
    ->whereNotIn('id', CourierShipment::query()
        ->where('provider', 'pathao')
        ->whereNotNull('consignment_id')
        ->where('consignment_id', '!=', '')
        ->select('order_id'))
    ->orderByDesc('id')
    ->first();

if (! $fresh) {
    fail('No unbooked order with a complete address was found for the Pathao preview check.');
} else {
    $before = CourierShipment::query()->where('provider', 'pathao')->count();

    $page = getPage($kernel, '/admin/order/' . $fresh->id . '/courier/pathao');

    note('GET /admin/order/' . $fresh->id . '/courier/pathao -> HTTP ' . $page['status']
        . ' in ' . $page['seconds'] . 's (live Pathao sandbox)');

    if ($page['status'] !== 200) {
        fail('The Pathao preview answered HTTP ' . $page['status'] . '.');
    } else {
        pass('The Pathao preview renders for order #' . $fresh->id . '.');
    }

    if (str_contains($page['html'], 'fmCourierForm')) {
        pass('The Pathao preview renders the send form (#fmCourierForm).');
    } else {
        fail('The Pathao preview does not render the send form.');

        if (preg_match('#<div class="alert alert-warning[^"]*"[^>]*>(.*?)</div>\s*</div>#s', $page['html'], $m)) {
            note('Preview message: ' . trim(preg_replace('/\s+/', ' ', strip_tags($m[1]))));
        }
    }

    foreach (['Recipient', 'Cash to collect'] as $needle) {
        if (str_contains($page['html'], $needle)) {
            pass('The Pathao preview shows ' . $needle . '.');
        } else {
            fail('The Pathao preview does not show ' . $needle . '.');
        }
    }

    $after = CourierShipment::query()->where('provider', 'pathao')->count();

    if ($before === $after) {
        pass('Opening the preview created no Pathao shipment row.');
    } else {
        fail('Opening the preview changed the Pathao shipment count (' . $before . ' -> ' . $after . ').');
    }

    if (! CourierShipment::query()->where('order_id', $fresh->id)->where('provider', 'pathao')->exists()) {
        pass('The previewed order still has no Pathao consignment, so nothing was sent.');
    } else {
        fail('The previewed order gained a Pathao consignment, so the preview sent something.');
    }
}

$controller = (string) file_get_contents($root . '/app/Http/Controllers/Admin/OrderCourierController.php');

if (str_contains($controller, 'PathaoOrderSender') && str_contains($controller, 'pathaoPreview')) {
    pass('The Pathao preview is built through PathaoOrderSender.');
} else {
    fail('The Pathao preview is not wired through PathaoOrderSender.');
}

// ============================================================== 6. no webhook UI
$legacyUi = ['Webhook endpoint', 'Webhook token', 'webhook_token', 'hook-url', 'hook-token', 'rotate-token'];

foreach ([
    'settings'      => $root . '/resources/views/backEnd/courier/settings.blade.php',
    'courier send'  => $root . '/resources/views/backEnd/order/courier_send.blade.php',
] as $label => $file) {
    $markup = (string) file_get_contents($file);
    $found  = false;

    foreach ($legacyUi as $needle) {
        if (str_contains($markup, $needle)) {
            fail('The ' . $label . ' view still contains "' . $needle . '".');
            $found = true;
        }
    }

    if (! $found) {
        pass('The ' . $label . ' view contains no webhook token/endpoint UI.');
    }
}

$page = getPage($kernel, '/admin/courier/settings');

if ($page['status'] === 200 && stripos($page['html'], 'webhook') === false) {
    pass('The rendered courier settings screen shows no webhook UI.');
} else {
    fail('The rendered courier settings screen still mentions a webhook or answered HTTP ' . $page['status'] . '.');
}

$page = getPage($kernel, '/admin/order/' . $orderId . '/courier/steadfast');

if ($page['status'] === 200 && stripos($page['html'], 'webhook') === false) {
    pass('The rendered courier-send screen shows no webhook UI.');
} else {
    fail('The rendered courier-send screen still mentions a webhook or answered HTTP ' . $page['status'] . '.');
}

// ============================================================== 7. lint + chooser check
$changed = [
    'app/Services/CourierDashboardService.php',
    'app/Services/CourierOrderSender.php',
    'app/Services/PathaoOrderSender.php',
    'app/Services/PathaoClient.php',
    'app/Http/Controllers/Admin/CourierDashboardController.php',
    'app/Http/Controllers/Admin/OrderCourierController.php',
    'routes/web.php',
    'tools/verify/courier-dashboard.php',
    'tools/verify/order-courier-chooser.php',
];

foreach ($changed as $relative) {
    $file = $root . '/' . $relative;

    if (! is_file($file)) {
        fail('Changed file is missing: ' . $relative);

        continue;
    }

    $output = [];
    $code   = 0;

    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

    if ($code === 0) {
        pass('php -l passes: ' . $relative);
    } else {
        fail('php -l failed: ' . $relative . ' - ' . implode(' ', $output));
    }
}

$chooserCheck = (string) file_get_contents($root . '/tools/verify/order-courier-chooser.php');

if (str_contains($chooserCheck, 'COURIER_CHOOSER_OK')
    && str_contains($chooserCheck, 'admin.order.courier.choose')
    && ! str_contains($chooserCheck, 'Bulk action only')) {
    pass('The existing chooser check is updated to the per-order rule.');
} else {
    fail('The existing chooser check still describes the old bulk-only rule.');
}

// ============================================================== verdict
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
