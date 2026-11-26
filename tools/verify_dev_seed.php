<?php

/**
 * Verify the v3 dev seed bundle after migrate:fresh --seed.
 *
 * Exits 0 with DEV_SEED_BUNDLE_OK only when the database proves the whole
 * chain, not merely that rows exist:
 *
 *   orders      30 AD-ORD invoices, status mix 12/10/4/2/2, every line
 *               variant-aware, delivered_at / cancelled_at stamped
 *   money       amount = Σ sale_price×qty + shipping_charge − discount
 *   returns     2 headers + 3 line items, refund amounts reconcile,
 *               one refunded, one pending
 *   coupons     3 attribution rows with a real discount
 *   courier     Pathao + Steadfast shipments, every one with shipping_cost
 *               and an event timeline
 *   inventory   one OPENING_BALANCE per variant; RESERVE/SALE/RELEASE/RETURN
 *               movements reconcile exactly with variant_stocks and the
 *               legacy products.stock mirror; only the 10 pending orders keep
 *               an active reservation
 *   shipping    exactly the three fixed rates, four free-shipping orders
 *   audit       an order.created row for the set
 *   migrations  the six plan migrations are present
 *
 * Run twice (the bash bundle runner does) to prove idempotency: the second run
 * must produce identical counts.
 *
 * Usage:
 *   php tools/verify_dev_seed.php
 *
 * DB_* environment variables win over .env, so the bundle runner can point it
 * at the scratch database without editing the project .env.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

/** @return array<string, string> */
function env_values(string $path): array
{
    $values = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $values[trim($key)] = $value;
    }

    return $values;
}

function env_or(array $env, string $key, string $default = ''): string
{
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return $value;
    }

    return (string) ($env[$key] ?? $default);
}

$env = env_values($root . '/.env');
$host = env_or($env, 'DB_HOST', '127.0.0.1');
$port = env_or($env, 'DB_PORT', '3306');
$database = env_or($env, 'DB_DATABASE');
$username = env_or($env, 'DB_USERNAME');
$password = env_or($env, 'DB_PASSWORD');

if ($database === '' || $username === '') {
    fwrite(STDERR, "DB_DATABASE / DB_USERNAME are empty.\n");
    exit(11);
}

$failures = [];

/** @param mixed $actual @param mixed $expected */
function expect_same(&$failures, string $label, $actual, $expected): void
{
    if ((string) $actual !== (string) $expected) {
        $failures[] = "{$label}: expected {$expected}, got {$actual}";
    }
}

function expect_true(&$failures, string $label, bool $ok, string $detail = ''): void
{
    if (! $ok) {
        $failures[] = $label . ($detail !== '' ? ': ' . $detail : '');
    }
}

// ---------------------------------------------------------------- migrations

$migrationFiles = glob($root . '/database/migrations/2026_11_16_00000[1-6]_*.php') ?: [];
expect_same($failures, 'plan migration files', count($migrationFiles), 6);

// ------------------------------------------------------------------- connect

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Cannot connect to {$database}: {$e->getMessage()}\n");
    exit(12);
}

/** @return array<string,int> slug => id */
$statusIds = [];

foreach ($pdo->query('SELECT id, slug FROM order_statuses') as $row) {
    $statusIds[(string) $row['slug']] = (int) $row['id'];
}

foreach (['pending', 'shipped', 'delivered', 'cancelled', 'returned'] as $slug) {
    if (! isset($statusIds[$slug])) {
        $failures[] = "order_statuses is missing '{$slug}'";
    }
}

$id = static fn (string $slug): string => (string) ($statusIds[$slug] ?? 0);

// -------------------------------------------------------------------- orders

$orders = $pdo->query(
    "SELECT id, invoice_id, amount, discount, shipping_charge, shipping_is_free,
            shipping_list_price, order_status, delivered_at, cancelled_at, created_at
     FROM orders
     WHERE invoice_id LIKE 'AD-ORD-%'
     ORDER BY invoice_id"
)->fetchAll(PDO::FETCH_ASSOC);

expect_same($failures, 'AD-ORD orders', count($orders), 30);

$invoices = array_column($orders, 'invoice_id');
expect_same($failures, 'first invoice', $invoices[0] ?? '', 'AD-ORD-0001');
expect_same($failures, 'last invoice', $invoices[29] ?? '', 'AD-ORD-0030');

$mix = ['pending' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0, 'returned' => 0];
$freeOrders = 0;
$deliveredAtCount = 0;
$cancelledAtCount = 0;
$idToSlug = array_flip($statusIds);

foreach ($orders as $order) {
    $slug = $idToSlug[(int) $order['order_status']] ?? 'unknown';
    $mix[$slug] = ($mix[$slug] ?? 0) + 1;

    if ((int) $order['shipping_is_free'] === 1) {
        $freeOrders++;

        if ((float) $order['shipping_charge'] !== 0.0) {
            $failures[] = "{$order['invoice_id']}: free shipping but shipping_charge={$order['shipping_charge']}";
        }

        if ((float) ($order['shipping_list_price'] ?? 0) <= 0) {
            $failures[] = "{$order['invoice_id']}: free shipping without a list price snapshot";
        }
    }

    if ($order['delivered_at'] !== null) {
        $deliveredAtCount++;
    }

    if ($order['cancelled_at'] !== null) {
        $cancelledAtCount++;
    }
}

expect_same($failures, 'delivered orders', $mix['delivered'], 12);
expect_same($failures, 'pending orders', $mix['pending'], 10);
expect_same($failures, 'shipped orders', $mix['shipped'], 4);
expect_same($failures, 'cancelled orders', $mix['cancelled'], 2);
expect_same($failures, 'returned orders', $mix['returned'], 2);
expect_same($failures, 'free shipping orders', $freeOrders, 4);
expect_same($failures, 'delivered_at rows', $deliveredAtCount, 14); // 12 delivered + 2 returned
expect_same($failures, 'cancelled_at rows', $cancelledAtCount, 2);

$orderIds = array_map(static fn ($row) => (int) $row['id'], $orders);
$inList = implode(',', array_map('intval', $orderIds));

// --------------------------------------------------------------------- lines

$lines = $pdo->query(
    "SELECT od.id, od.order_id, od.product_id, od.variant_id, od.qty, od.sale_price
     FROM order_details od
     WHERE od.order_id IN ({$inList})
     ORDER BY od.id"
)->fetchAll(PDO::FETCH_ASSOC);

expect_same($failures, 'order lines', count($lines), 35);

$orphanVariant = $pdo->query(
    "SELECT COUNT(*) FROM order_details od
     LEFT JOIN product_variants v ON v.id = od.variant_id
     WHERE od.order_id IN ({$inList})
       AND (od.variant_id IS NULL OR od.variant_id = 0 OR v.id IS NULL OR v.product_id <> od.product_id)"
)->fetchColumn();

expect_same($failures, 'lines without a matching variant', (int) $orphanVariant, 0);

// ------------------------------------------------------------- money snapshot

$lineTotals = [];

$stmt = $pdo->query(
    "SELECT order_id, SUM(sale_price * qty) AS subtotal
     FROM order_details
     WHERE order_id IN ({$inList})
     GROUP BY order_id"
);

foreach ($stmt as $row) {
    $lineTotals[(int) $row['order_id']] = (float) $row['subtotal'];
}

foreach ($orders as $order) {
    $expected = ($lineTotals[(int) $order['id']] ?? 0.0)
        + (float) $order['shipping_charge']
        - (float) $order['discount'];

    if (abs($expected - (float) $order['amount']) > 0.01) {
        $failures[] = sprintf(
            '%s: amount %0.2f <> lines+shipping-discount %0.2f',
            $order['invoice_id'],
            (float) $order['amount'],
            $expected
        );
    }
}

// ------------------------------------------------------------------- returns

$returns = $pdo->query(
    "SELECT r.id, r.order_id, r.refunded, r.refund_amount, o.invoice_id
     FROM order_returns r
     JOIN orders o ON o.id = r.order_id
     WHERE o.invoice_id LIKE 'AD-ORD-%'
     ORDER BY o.invoice_id"
)->fetchAll(PDO::FETCH_ASSOC);

expect_same($failures, 'order_returns', count($returns), 2);
expect_same($failures, 'return invoice 1', $returns[0]['invoice_id'] ?? '', 'AD-ORD-0029');
expect_same($failures, 'return invoice 2', $returns[1]['invoice_id'] ?? '', 'AD-ORD-0030');
expect_same($failures, 'refunded flag 1', (int) ($returns[0]['refunded'] ?? -1), 1);
expect_same($failures, 'refunded flag 2', (int) ($returns[1]['refunded'] ?? -1), 0);

$returnItems = (int) $pdo->query('SELECT COUNT(*) FROM order_return_items')->fetchColumn();
expect_same($failures, 'order_return_items', $returnItems, 3);

foreach ($returns as $return) {
    $itemSum = (float) $pdo->query(
        'SELECT COALESCE(SUM(refund_amount), 0) FROM order_return_items WHERE order_return_id = ' . (int) $return['id']
    )->fetchColumn();

    if (abs($itemSum - (float) $return['refund_amount']) > 0.01) {
        $failures[] = "{$return['invoice_id']}: return refund {$return['refund_amount']} <> items {$itemSum}";
    }
}

// ------------------------------------------------------------- coupon usages

expect_same(
    $failures,
    'coupon usages',
    (int) $pdo->query('SELECT COUNT(*) FROM coupon_usages')->fetchColumn(),
    3
);

$couponOrders = $pdo->query(
    "SELECT DISTINCT o.invoice_id
     FROM coupon_usages cu
     JOIN orders o ON o.id = cu.order_id
     ORDER BY o.invoice_id"
)->fetchAll(PDO::FETCH_COLUMN);

expect_same($failures, 'coupon usage invoices', implode(',', $couponOrders), 'AD-ORD-0003,AD-ORD-0015,AD-ORD-0023');

$zeroUsages = (int) $pdo->query('SELECT COUNT(*) FROM coupon_usages WHERE discount_amount <= 0')->fetchColumn();
expect_same($failures, 'coupon usages without a discount', $zeroUsages, 0);

// ------------------------------------------------------------------- courier

$shipments = $pdo->query(
    'SELECT provider, COUNT(*) AS rows_count, MIN(shipping_cost) AS min_cost
     FROM courier_shipments GROUP BY provider'
)->fetchAll(PDO::FETCH_ASSOC);

$byProvider = [];
foreach ($shipments as $row) {
    $byProvider[(string) $row['provider']] = $row;
}

expect_same($failures, 'pathao shipments', (int) ($byProvider['pathao']['rows_count'] ?? 0), 2);
expect_same($failures, 'steadfast shipments', (int) ($byProvider['steadfast']['rows_count'] ?? 0), 4);

$missingCost = (int) $pdo->query(
    'SELECT COUNT(*) FROM courier_shipments WHERE shipping_cost IS NULL OR shipping_cost <= 0'
)->fetchColumn();
expect_same($failures, 'shipments without shipping_cost', $missingCost, 0);

$shipmentsWithoutEvents = (int) $pdo->query(
    'SELECT COUNT(*) FROM courier_shipments s
     LEFT JOIN courier_events e ON e.shipment_id = s.id
     WHERE e.id IS NULL'
)->fetchColumn();
expect_same($failures, 'shipments without an event', $shipmentsWithoutEvents, 0);

expect_same($failures, 'courier events', (int) $pdo->query('SELECT COUNT(*) FROM courier_events')->fetchColumn(), 14);

// ---------------------------------------------------------------- payment mix

$methods = (int) $pdo->query(
    "SELECT COUNT(DISTINCT p.payment_method)
     FROM payments p
     WHERE p.order_id IN ({$inList})"
)->fetchColumn();
expect_true($failures, 'payment methods', $methods >= 4, "found {$methods}");

// ---------------------------------------------------------- shipping charges

$charges = $pdo->query('SELECT name, amount, status FROM shipping_charges ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
expect_same($failures, 'shipping_charges rows', count($charges), 3);

$expectedCharges = [
    'Standard delivery' => 120,
    'ঢাকার ভিতরে' => 70,
    'ঢাকার বাহিরে' => 130,
];

foreach ($charges as $charge) {
    $name = (string) $charge['name'];

    if (! isset($expectedCharges[$name])) {
        $failures[] = "unexpected shipping charge '{$name}'";
        continue;
    }

    expect_same($failures, "shipping charge {$name}", (int) $charge['amount'], $expectedCharges[$name]);
    expect_same($failures, "shipping charge {$name} status", (int) $charge['status'], 1);
}

// ----------------------------------------------------------------- inventory

$openingDuplicates = (int) $pdo->query(
    "SELECT COUNT(*) FROM (
        SELECT variant_id FROM inventory_movements
        WHERE type = 'OPENING_BALANCE'
        GROUP BY variant_id HAVING COUNT(*) <> 1
    ) x"
)->fetchColumn();
expect_same($failures, 'variants without exactly one OPENING_BALANCE', $openingDuplicates, 0);

$ledger = [];
$stmt = $pdo->query(
    "SELECT variant_id,
        SUM(CASE WHEN type IN ('OPENING_BALANCE','ADJUSTMENT','RECEIVE','SALE','RETURN','RETURN_DAMAGED') THEN qty_delta ELSE 0 END) AS on_hand,
        SUM(CASE WHEN type IN ('OPENING_BALANCE','ADJUSTMENT','RECEIVE','SALE','RETURN') THEN qty_delta ELSE 0 END) AS available,
        SUM(CASE WHEN type = 'RETURN_DAMAGED' THEN qty_delta ELSE 0 END) AS damaged
     FROM inventory_movements
     GROUP BY variant_id"
);

foreach ($stmt as $row) {
    $ledger[(int) $row['variant_id']] = [
        'on_hand' => (int) $row['on_hand'],
        'available' => (int) $row['available'],
        'damaged' => (int) $row['damaged'],
    ];
}

$stocks = $pdo->query('SELECT variant_id, on_hand, available, damaged FROM variant_stocks')->fetchAll(PDO::FETCH_ASSOC);

foreach ($stocks as $stock) {
    $variantId = (int) $stock['variant_id'];
    $fromLedger = $ledger[$variantId] ?? ['on_hand' => 0, 'available' => 0, 'damaged' => 0];

    foreach (['on_hand', 'available', 'damaged'] as $bucket) {
        if ((int) $stock[$bucket] !== $fromLedger[$bucket]) {
            $failures[] = "variant {$variantId} {$bucket}: stock row {$stock[$bucket]} <> ledger {$fromLedger[$bucket]}";
        }
    }
}

$legacyMismatch = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM products p
     JOIN product_variants v ON v.product_id = p.id
     JOIN variant_stocks vs ON vs.variant_id = v.id
     WHERE p.stock <> vs.on_hand'
)->fetchColumn();
expect_same($failures, 'products.stock <> ledger on_hand', $legacyMismatch, 0);

$lineCount = count($lines);
expect_same(
    $failures,
    'RESERVE movements',
    (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements WHERE type = 'RESERVE'")->fetchColumn(),
    $lineCount
);

$soldStatuses = implode(',', array_map(static fn ($slug) => "'" . $id($slug) . "'", ['shipped', 'delivered', 'returned']));
$soldLines = (int) $pdo->query(
    "SELECT COUNT(*) FROM order_details od
     JOIN orders o ON o.id = od.order_id
     WHERE o.invoice_id LIKE 'AD-ORD-%' AND o.order_status IN ({$soldStatuses})"
)->fetchColumn();
expect_same(
    $failures,
    'SALE movements',
    (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements WHERE type = 'SALE'")->fetchColumn(),
    $soldLines
);

$cancelledLines = (int) $pdo->query(
    "SELECT COUNT(*) FROM order_details od
     JOIN orders o ON o.id = od.order_id
     WHERE o.invoice_id LIKE 'AD-ORD-%' AND o.order_status = '" . $id('cancelled') . "'"
)->fetchColumn();
expect_same(
    $failures,
    'RELEASE movements',
    (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements WHERE type = 'RELEASE'")->fetchColumn(),
    $cancelledLines
);

expect_same($failures, 'RETURN movements', (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements WHERE type = 'RETURN'")->fetchColumn(), 2);
expect_same($failures, 'RETURN_DAMAGED movements', (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements WHERE type = 'RETURN_DAMAGED'")->fetchColumn(), 1);

$activeReservations = (int) $pdo->query(
    'SELECT COUNT(*) FROM inventory_reservations WHERE expires_at > NOW()'
)->fetchColumn();
$pendingLines = (int) $pdo->query(
    "SELECT COUNT(*) FROM order_details od
     JOIN orders o ON o.id = od.order_id
     WHERE o.invoice_id LIKE 'AD-ORD-%' AND o.order_status = '" . $id('pending') . "'"
)->fetchColumn();

expect_same($failures, 'active reservations', $activeReservations, $pendingLines);

// ---------------------------------------------------------------- audit trail

$createdAudits = (int) $pdo->query(
    "SELECT COUNT(DISTINCT subject_id) FROM audit_logs
     WHERE action = 'order.created' AND subject_type = 'order'"
)->fetchColumn();
expect_same($failures, 'order.created audit subjects', $createdAudits, 30);

$statusAudits = (int) $pdo->query(
    "SELECT COUNT(*) FROM audit_logs WHERE action = 'order.status.changed'"
)->fetchColumn();
expect_true($failures, 'order.status.changed audit rows', $statusAudits >= 20, "found {$statusAudits}");

// --------------------------------------------------------------------- result

if ($failures !== []) {
    fwrite(STDERR, "DEV_SEED_BUNDLE_FAILED\n");

    foreach (array_slice($failures, 0, 25) as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }

    if (count($failures) > 25) {
        fwrite(STDERR, ' - ... ' . (count($failures) - 25) . " more\n");
    }

    exit(13);
}

echo 'ORDERS ' . count($orders) . "\n";
echo 'STATUS_MIX delivered=' . $mix['delivered'] . ' pending=' . $mix['pending']
    . ' shipped=' . $mix['shipped'] . ' cancelled=' . $mix['cancelled']
    . ' returned=' . $mix['returned'] . "\n";
echo 'LINES ' . count($lines) . " variant_aware=1\n";
echo 'RETURNS 2 ITEMS ' . $returnItems . "\n";
echo 'COUPON_USAGES 3' . "\n";
echo 'SHIPMENTS ' . ($byProvider['pathao']['rows_count'] + $byProvider['steadfast']['rows_count'])
    . ' EVENTS 14' . "\n";
echo 'RESERVATIONS ' . $activeReservations . "\n";
echo 'LEDGER_OPENING ' . count($ledger) . "\n";
echo "DEV_SEED_BUNDLE_OK\n";
