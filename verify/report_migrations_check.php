<?php

/**
 * Post-migration verification for report-column-map v1.1.
 *
 * Standalone (no framework boot): it reads the same .env the application
 * reads and asserts that every column, index and table the report matrix
 * requires now exists in the live database, plus the backfills the
 * migrations were supposed to write.
 *
 * Success marker printed on stdout: REPORT_MIGRATIONS_SCHEMA_OK
 * Any failure prints the item that is missing and exits 1.
 */

$root = dirname(__DIR__);

$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/', $line, $m)) {
        $env[$m[1]] = trim($m[2], " \t\"'");
    }
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? ''
    ),
    $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$failures = [];

$columns = [
    'orders' => ['order_status_id', 'cancel_reason', 'source', 'attributed_visitor_id'],
    'shippings' => ['district_id', 'division'],
    'payments' => ['gateway_fee', 'settled_amount', 'settlement_status', 'settled_at', 'settlement_reference', 'is_cod'],
    'courier_shipments' => ['status_normalized', 'delivered_at', 'cod_amount', 'return_charge', 'remitted_amount', 'settlement_status', 'status_updated_at'],
    'order_details' => ['cost_status'],
];

foreach ($columns as $table => $wanted) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $have = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $have[] = $row['Field'];
    }
    foreach ($wanted as $column) {
        if (!in_array($column, $have, true)) {
            $failures[] = "missing column $table.$column";
        }
    }
}

$tables = [
    'report_views',
    'expense_categories',
    'business_expenses',
    'courier_settlements',
    'payment_settlements',
    'marketing_spends',
    'order_attributions',
];

$stmt = $pdo->query('SHOW TABLES');
$liveTables = array_map(static fn (array $row) => array_values($row)[0], $stmt->fetchAll(PDO::FETCH_ASSOC));
foreach ($tables as $table) {
    if (!in_array($table, $liveTables, true)) {
        $failures[] = "missing table $table";
    }
}

$indexes = [
    'orders' => [
        'orders_order_status_index',
        'orders_created_at_index',
        'orders_order_status_id_index',
        'orders_cancel_reason_index',
        'orders_source_index',
        'orders_attributed_visitor_id_index',
        'orders_customer_id_created_at_index',
    ],
    'shippings' => ['shippings_district_id_index', 'shippings_division_index', 'shippings_order_id_index'],
    'payments' => [
        'payments_settlement_status_index',
        'payments_is_cod_index',
        'payments_order_id_index',
        'payments_payment_status_index',
        'payments_created_at_index',
    ],
    'courier_shipments' => [
        'courier_shipments_status_normalized_index',
        'courier_shipments_delivered_at_index',
        'courier_shipments_settlement_status_index',
        'courier_shipments_status_updated_at_index',
    ],
    'order_details' => ['order_details_cost_status_index', 'order_details_order_id_index', 'order_details_product_id_index'],
    'courier_events' => ['courier_events_created_at_index'],
    'inventory_movements' => ['inventory_movements_created_at_index'],
    'products' => ['products_category_id_index'],
];

foreach ($indexes as $table => $wanted) {
    $stmt = $pdo->query("SHOW INDEX FROM `$table`");
    $have = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Key_name');
    foreach ($wanted as $index) {
        if (!in_array($index, $have, true)) {
            $failures[] = "missing index $table.$index";
        }
    }
}

$migrations = [
    '2026_11_27_000002_add_report_columns_to_orders',
    '2026_11_27_000003_add_report_columns_to_shippings',
    '2026_11_27_000004_add_settlement_columns_to_payments',
    '2026_11_27_000005_add_report_columns_to_courier_shipments',
    '2026_11_27_000006_add_report_columns_to_order_details',
    '2026_11_27_000007_create_report_views_table',
    '2026_11_27_000008_create_expense_tables',
    '2026_11_27_000009_create_settlement_tables',
    '2026_11_27_000010_create_marketing_tables',
    '2026_11_27_000011_add_reporting_indexes',
];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
foreach ($migrations as $migration) {
    $stmt->execute([$migration]);
    if ((int) $stmt->fetchColumn() !== 1) {
        $failures[] = "migration not recorded in migrations table: $migration";
    }
}

$checks = [
    'orders without order_status_id backfill' => 'SELECT COUNT(*) FROM orders WHERE order_status_id IS NULL',
    'order_details without cost_status value' => 'SELECT COUNT(*) FROM order_details WHERE cost_status IS NULL',
    'courier_shipments still default pending while status differs' =>
        "SELECT COUNT(*) FROM courier_shipments WHERE status_normalized = 'pending' AND LOWER(current_status) NOT IN ('pending', 'created', 'booked')",
    'COD rows not flagged is_cod' =>
        "SELECT COUNT(*) FROM payments WHERE payment_method = 'Cash On Delivery' AND is_cod = 0",
];

foreach ($checks as $label => $sql) {
    $count = (int) $pdo->query($sql)->fetchColumn();
    if ($count > 0) {
        $failures[] = "$label: $count";
    }
}

if ($failures !== []) {
    fwrite(STDERR, "SCHEMA CHECK FAILED\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "  - $failure\n");
    }
    exit(1);
}

echo "REPORT_MIGRATIONS_SCHEMA_OK\n";
exit(0);
