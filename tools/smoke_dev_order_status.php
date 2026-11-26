<?php

/**
 * Smoke-test the admin status path against the seeded scratch database.
 *
 * The seeder proves OrderLifecycle works; this proves the controller that the
 * order list dropdown and the Kanban board call is actually wired to it:
 *
 *   changeOrderStatus([id], processing)  -> order_status moves, audit written
 *   changeOrderStatus([id], pending)     -> moves back without touching stock
 *
 * Runs only inside tools/verify_dev_seed_bundle.sh, against the throwaway
 * database. It is not a report fixture and must never run on the dev database.
 *
 * Usage:
 *   DB_DATABASE=z_evo_seed_check php tools/smoke_dev_order_status.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\DB;

$fail = static function (string $message): void {
    fwrite(STDERR, "ORDER_STATUS_CONTROLLER_FAILED: {$message}\n");
    exit(13);
};

$order = Order::query()->where('invoice_id', 'AD-ORD-0013')->first();

if (! $order) {
    $fail('AD-ORD-0013 is missing');
}

$pendingId = (int) OrderStatus::query()->where('slug', 'pending')->value('id');
$processingId = (int) OrderStatus::query()->where('slug', 'processing')->value('id');

if ($pendingId <= 0 || $processingId <= 0) {
    $fail('pending/processing status rows are missing');
}

if ((int) $order->order_status !== $pendingId) {
    $fail('AD-ORD-0013 is not pending');
}

$reservationsBefore = (int) DB::table('inventory_reservations')
    ->where('order_id', (int) $order->id)
    ->count();

$auditBefore = (int) DB::table('audit_logs')
    ->where('action', 'order.status.changed')
    ->where('subject_type', 'order')
    ->where('subject_id', (string) $order->id)
    ->count();

$controller = new OrderController();

$failures = $controller->changeOrderStatus([(int) $order->id], $processingId);
if ($failures !== []) {
    $fail($failures[0]['message']);
}

$order->refresh();

if ((int) $order->order_status !== $processingId) {
    $fail('status did not move to processing');
}

$auditAfter = (int) DB::table('audit_logs')
    ->where('action', 'order.status.changed')
    ->where('subject_type', 'order')
    ->where('subject_id', (string) $order->id)
    ->count();

if ($auditAfter <= $auditBefore) {
    $fail('no order.status.changed audit row was written');
}

$failures = $controller->changeOrderStatus([(int) $order->id], $pendingId);
if ($failures !== []) {
    $fail($failures[0]['message']);
}

$order->refresh();

if ((int) $order->order_status !== $pendingId) {
    $fail('status did not move back to pending');
}

$reservationsAfter = (int) DB::table('inventory_reservations')
    ->where('order_id', (int) $order->id)
    ->count();

if ($reservationsAfter !== $reservationsBefore) {
    $fail("a pending/processing round-trip changed reservations {$reservationsBefore} -> {$reservationsAfter}");
}

echo "ORDER_STATUS_CONTROLLER_OK\n";
