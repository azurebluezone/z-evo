<?php

/**
 * One-off repair, run inside contract gc-mwzaw92x.
 *
 * A diagnostic query used where('product_id', [1, 2, 3]) and Laravel compiled
 * it to `product_id = ?` with the binding [1] only, so the three seeded
 * product_variants rows of product 1 were deleted from the live database.
 * The tuples below are copied verbatim from the pre-migration dump
 * (backups/z_evo-pre-report-migrations-2026-11-27.sql, INSERT INTO
 * `product_variants`), and are re-inserted only when the id is still missing.
 *
 * Prints: PRODUCT1_VARIANTS_RESTORED <count>
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

$rows = [
    [
        'id' => 1,
        'product_id' => 1,
        'sku' => 'AD-4915-DEFAULT',
        'title' => 'Premature Full Romper PFR4401 - Preemie',
        'image' => 'uploads/product/seed/premature-full-romper-pfr4401-4915/variant-size-1.jpg',
        'price_override' => 350,
        'purchase_price' => 180,
        'sale_price' => null,
        'discount_price' => null,
        'weight_gram' => null,
        'status' => 1,
        'attributes_json' => '{"attrs":{"size":"Preemie"},"ids":{"size":"15"}}',
        'created_at' => '2026-11-27 14:20:02',
        'updated_at' => '2026-11-27 14:20:02',
    ],
    [
        'id' => 2,
        'product_id' => 1,
        'sku' => 'AD-4915-V2',
        'title' => 'Premature Full Romper PFR4401 - 0-1M',
        'image' => 'uploads/product/seed/premature-full-romper-pfr4401-4915/variant-size-2.jpg',
        'price_override' => 390,
        'purchase_price' => 180,
        'sale_price' => null,
        'discount_price' => null,
        'weight_gram' => null,
        'status' => 1,
        'attributes_json' => '{"attrs":{"size":"0-1M"},"ids":{"size":"16"}}',
        'created_at' => '2026-11-27 14:20:02',
        'updated_at' => '2026-11-27 14:20:02',
    ],
    [
        'id' => 3,
        'product_id' => 1,
        'sku' => 'AD-4915-V3',
        'title' => 'Premature Full Romper PFR4401 - 1-3M',
        'image' => 'uploads/product/seed/premature-full-romper-pfr4401-4915/variant-size-3.jpg',
        'price_override' => 430,
        'purchase_price' => 180,
        'sale_price' => null,
        'discount_price' => null,
        'weight_gram' => null,
        'status' => 1,
        'attributes_json' => '{"attrs":{"size":"1-3M"},"ids":{"size":"17"}}',
        'created_at' => '2026-11-27 14:20:02',
        'updated_at' => '2026-11-27 14:20:02',
    ],
];

$columns = [
    'id',
    'product_id',
    'sku',
    'title',
    'image',
    'price_override',
    'purchase_price',
    'sale_price',
    'discount_price',
    'weight_gram',
    'status',
    'attributes_json',
    'created_at',
    'updated_at',
];

$check = $pdo->prepare('SELECT COUNT(*) FROM product_variants WHERE id = ?');
$insert = $pdo->prepare(
    'INSERT INTO product_variants (' . implode(', ', $columns) . ') VALUES ('
    . implode(', ', array_fill(0, count($columns), '?')) . ')'
);

$restored = 0;
foreach ($rows as $row) {
    $check->execute([$row['id']]);
    if ((int) $check->fetchColumn() > 0) {
        continue;
    }
    $insert->execute(array_map(static fn (string $column) => $row[$column], $columns));
    $restored++;
}

$live = (int) $pdo->query('SELECT COUNT(*) FROM product_variants WHERE product_id = 1')->fetchColumn();
$stockOrphans = (int) $pdo->query(
    'SELECT COUNT(*) FROM variant_stocks s LEFT JOIN product_variants v ON v.id = s.variant_id WHERE v.id IS NULL'
)->fetchColumn();

if ($live !== 3) {
    fwrite(STDERR, "product 1 should have 3 variants, found $live\n");
    exit(1);
}

if ($stockOrphans !== 0) {
    fwrite(STDERR, "orphan variant_stocks rows: $stockOrphans\n");
    exit(1);
}

echo "PRODUCT1_VARIANTS_RESTORED $restored (live=$live, orphan_stock=$stockOrphans)\n";
exit(0);
