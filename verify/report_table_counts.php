<?php

/**
 * Table-count stability check for the live database this test suite runs on.
 *
 * The suite writes to the real MySQL connection, so a test that creates rows
 * and forgets to delete them does not fail on its own run: it poisons the NEXT
 * run (this is how the variant_stocks leak was found). This script closes that
 * gap by comparing row counts around a suite run.
 *
 *   php verify/report_table_counts.php --snapshot   # record counts before a run
 *   php verify/report_table_counts.php --compare    # fail if any count changed
 *
 * Snapshot file: verify/.table_counts.json (inside the project, regenerable).
 * Exit 1 with a per-table report when a count moved or no snapshot exists.
 */

$root = dirname(__DIR__);
$file = __DIR__ . '/.table_counts.json';

$tracked = [
    'orders',
    'order_details',
    'payments',
    'shippings',
    'products',
    'product_variants',
    'variant_stocks',
    'customers',
    'campaigns',
    'coupon_usages',
    'courier_shipments',
    'courier_events',
    'inventory_reservations',
    'inventory_movements',
    'order_returns',
    'order_return_items',
    'report_views',
    'expense_categories',
    'business_expenses',
    'marketing_spends',
    'order_attributions',
];

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

$counts = [];
foreach ($tracked as $table) {
    $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

$mode = $argv[1] ?? '';

if ($mode === '--snapshot') {
    file_put_contents($file, json_encode($counts, JSON_PRETTY_PRINT) . "\n");
    echo 'TABLE_COUNTS_SNAPSHOT_OK ' . count($counts) . " tables\n";
    exit(0);
}

if ($mode === '--compare') {
    if (!is_file($file)) {
        fwrite(STDERR, "no snapshot at $file - run --snapshot first\n");
        exit(1);
    }

    $before = json_decode((string) file_get_contents($file), true);
    if (!is_array($before)) {
        fwrite(STDERR, "unreadable snapshot at $file\n");
        exit(1);
    }

    $moved = [];
    foreach ($tracked as $table) {
        $was = isset($before[$table]) ? (int) $before[$table] : null;
        if ($was === null) {
            $moved[] = "$table: not in snapshot";
            continue;
        }
        if ($was !== $counts[$table]) {
            $moved[] = "$table: $was -> {$counts[$table]}";
        }
    }

    if ($moved !== []) {
        fwrite(STDERR, "TABLE COUNTS MOVED\n");
        foreach ($moved as $line) {
            fwrite(STDERR, "  - $line\n");
        }
        exit(1);
    }

    echo 'TABLE_COUNTS_STABLE ' . count($tracked) . " tables\n";
    exit(0);
}

fwrite(STDERR, "usage: php verify/report_table_counts.php --snapshot|--compare\n");
exit(1);
