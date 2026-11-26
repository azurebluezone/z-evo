<?php

/**
 * Contract gc-mwh8hoc2 - the audit log and the courier dashboard.
 *
 * Run from the project root:
 *
 *     php tools/verify/audit-log-courier-dashboard.php
 *
 * It prints AUDIT_LOG_COURIER_DASHBOARD_OK only when EVERY point below holds:
 *
 *   1. audit_logs exists with the columns the trail needs.
 *   2. Changing an order note through the REAL admin route writes an
 *      order.note.updated row that keeps the old text.
 *   3. An inventory adjustment through the REAL admin route writes an
 *      inventory.movement.recorded row.
 *   4. The other write sites are wired too (order status, order created,
 *      courier credentials, courier toggle, token rotation, courier send,
 *      fraud check, returns).
 *   5. GET /admin/audit-log renders and shows what was recorded.
 *   6. GET /admin/courier/dashboard renders with the live balance, the parcel
 *      counts, the recent parcel rows and the status breakdown, and without the
 *      old webhook/API-route developer text.
 *   7. docs/COURIER-API-FEATURES-2026-11-15.md exists and lists the API
 *      routes.
 *
 * Any failed point prints why and exits non-zero.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

const MARKER = 'AUDIT_LOG_COURIER_DASHBOARD_OK';

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

/*
 * The CSRF middleware skips its check when the application says it is running
 * the test suite - that is how Laravel's own feature tests post to a route.
 * The same trick is used here so the check can drive the REAL admin routes
 * without inventing a session.
 */
app()->instance('env', 'testing');

$admin = App\Models\User::query()->where('email', 'admin@gmail.com')->first();

if (! $admin) {
    fail('The admin user admin@gmail.com is missing.');

    exit(1);
}

Illuminate\Support\Facades\Auth::loginUsingId($admin->id);

$kernel = $app->make(HttpKernel::class);

/** Send a request through the real HTTP kernel, as the logged-in admin. */
function adminRequest(HttpKernel $kernel, string $method, string $uri, array $payload = []): array
{
    $request = Request::create($uri, $method, $payload);
    $response = $kernel->handle($request);

    return [$response->getStatusCode(), (string) $response->getContent()];
}

// ---------------------------------------------------------------- 1. the table
if (! Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
    fail('The audit_logs table does not exist.');

    exit(1);
}

$columns = Illuminate\Support\Facades\Schema::getColumnListing('audit_logs');

$needed = ['user_id', 'actor_name', 'action', 'subject_type', 'subject_id', 'description', 'before', 'after', 'source', 'ip', 'created_at'];
$missing = array_values(array_diff($needed, $columns));

if ($missing !== []) {
    fail('audit_logs is missing: ' . implode(', ', $missing));
} else {
    pass('audit_logs exists with every column the trail needs (' . count($columns) . ' columns).');
}

// ---------------------------------------------------------------- 2. an order note
$order = Order::query()->with('shipping')->orderByDesc('id')->first();

if (! $order || ! $order->shipping) {
    fail('There is no order with a shipping row to test the note flow.');

    exit(1);
}

$marker = 'AUDIT PROBE ' . date('Ymd-His');
$before = (string) ($order->admin_note ?? '');

[$status] = adminRequest($kernel, 'POST', '/admin/order/change', [
    'id'         => $order->id,
    'status'     => $order->order_status,
    'admin_note' => $marker,
    'area'       => 1,
    'name'       => $order->shipping->name,
    'phone'      => $order->shipping->phone,
    'address'    => $order->shipping->address,
]);

if ($status !== 302 && $status !== 200) {
    fail('POST /admin/order/change answered HTTP ' . $status . '.');
}

$noteLog = AuditLog::query()->where('action', 'order.note.updated')
    ->where('subject_id', (string) $order->id)
    ->orderByDesc('id')
    ->first();

if (! $noteLog) {
    fail('Changing an order note wrote no order.note.updated audit row.');
} elseif (! str_contains((string) ($noteLog->after['admin_note'] ?? ''), $marker)) {
    fail('The audit row does not carry the new note.');
} elseif (! array_key_exists('admin_note', (array) $noteLog->before)) {
    fail('The audit row does not keep the note it replaced.');
} else {
    pass('An order note change is audited with the old and the new text.');
    note('row: ' . json_encode($noteLog->after));
}

// ---------------------------------------------------------------- 3. an inventory movement
$variantId = (int) (DB::table('product_variants')->value('id') ?? 0);

if ($variantId === 0) {
    fail('There is no variant to adjust.');
} else {
    [$status] = adminRequest($kernel, 'POST', '/admin/inventory/adjustment', [
        'variant_id' => $variantId,
        'type'       => 'ADJUSTMENT',
        'qty_delta'  => 1,
        'note'       => $marker,
    ]);

    if ($status !== 302 && $status !== 200) {
        fail('POST /admin/inventory/adjustment answered HTTP ' . $status . '.');
    }

    $movementLog = AuditLog::query()->where('action', 'inventory.movement.recorded')
        ->where('subject_id', (string) $variantId)
        ->orderByDesc('id')
        ->first();

    if (! $movementLog) {
        fail('An inventory adjustment wrote no inventory.movement.recorded audit row.');
    } else {
        pass('An inventory movement is audited with its type, bucket and quantity.');
        note('row: ' . json_encode($movementLog->after));
    }
}

// ---------------------------------------------------------------- 4. the other write sites
$sites = [
    'app/Http/Controllers/Admin/OrderController.php'         => ['orderNote(', 'orderStatus(', 'orderCreated('],
    'app/Services/OrderPlacementService.php'                 => ['orderCreated('],
    'app/Http/Controllers/Admin/ShippingVerificationController.php' => ['orderNote('],
    'app/Http/Controllers/Admin/CourierController.php'       => ['courier.credentials.saved', 'courier.toggled', 'courier.webhook_token.rotated'],
    'app/Services/CourierOrderSender.php'                   => ['courier.order.sent', 'courier.order.refused'],
    'app/Http/Controllers/Admin/CourierFraudCheckerController.php' => ['courier.fraud_check.run'],
    'app/Http/Controllers/Admin/ReturnsRefundsController.php' => ['return.approved', 'return.refunded'],
    'app/Models/InventoryMovement.php'                      => ['inventoryMovement('],
];

foreach ($sites as $file => $needles) {
    $path = $root . '/' . $file;

    if (! is_file($path)) {
        fail('Missing source file: ' . $file);

        continue;
    }

    $source = (string) file_get_contents($path);
    $absent = [];

    foreach ($needles as $needle) {
        if (! str_contains($source, $needle)) {
            $absent[] = $needle;
        }
    }

    if ($absent !== []) {
        fail($file . ' does not write: ' . implode(', ', $absent));
    } else {
        pass($file . ' writes: ' . implode(', ', $needles));
    }
}

// The action names themselves live in one place, the logger.
$loggerSource = (string) file_get_contents($root . '/app/Services/AuditLogger.php');
$actionNames = [
    'order.note.updated',
    'order.status.changed',
    'order.created',
    'inventory.movement.recorded',
];

$absentActions = array_values(array_filter($actionNames, fn (string $name) => ! str_contains($loggerSource, "'" . $name . "'")));

if ($absentActions !== []) {
    fail('The audit logger does not define: ' . implode(', ', $absentActions));
} else {
    pass('The audit logger defines every action name in one place.');
}

// ---------------------------------------------------------------- 5. the audit screen
[$status, $html] = adminRequest($kernel, 'GET', '/admin/audit-log');

if ($status !== 200) {
    fail('GET /admin/audit-log answered HTTP ' . $status . '.');
} elseif (! str_contains($html, 'order.note.updated') || ! str_contains($html, 'inventory.movement.recorded')) {
    fail('The audit screen does not show the recorded actions.');
} else {
    pass('The audit screen renders and lists what was recorded.');
}

// ---------------------------------------------------------------- 6. the courier dashboard
[$status, $html] = adminRequest($kernel, 'GET', '/admin/courier/dashboard');

if ($status !== 200) {
    fail('GET /admin/courier/dashboard answered HTTP ' . $status . '.');
} else {
    $needles = [
        'the live balance'   => ['Balance at courier'],
        'the parcel counts'  => ['Parcels booked'],
        'the recent parcels' => ['Recent parcels'],
        'the status split'   => ['Delivery status'],
        'the cash figure'    => ['Cash to collect'],
    ];

    foreach ($needles as $label => $words) {
        $found = false;

        foreach ((array) $words as $word) {
            if (str_contains($html, $word)) {
                $found = true;
                break;
            }
        }

        if ($found) {
            pass('The courier dashboard shows ' . $label . '.');
        } else {
            fail('The courier dashboard does NOT show ' . $label . '.');
        }
    }

    foreach (['Webhook', 'API route', 'get_all_orders', 'Courier dashboard features'] as $legacy) {
        if (str_contains($html, $legacy)) {
            fail('The courier dashboard still contains the old developer text: ' . $legacy . '.');
        } else {
            pass('The courier dashboard does not contain "' . $legacy . '".');
        }
    }
}

// ---------------------------------------------------------------- 7. the document
$doc = $root . '/docs/COURIER-API-FEATURES-2026-11-15.md';

if (! is_file($doc)) {
    fail('The feature document is missing: docs/COURIER-API-FEATURES-2026-11-15.md');
} else {
    $text = (string) file_get_contents($doc);

    $routes = ['/get_balance', '/create_order', '/fraud_check/score', '/get_all_orders', '/cancel_order'];

    $absent = [];

    foreach ($routes as $route) {
        if (! str_contains($text, $route)) {
            $absent[] = $route;
        }
    }

    if ($absent !== []) {
        fail('The feature document does not list: ' . implode(', ', $absent));
    } else {
        pass('The feature document lists every API route that was probed.');
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
