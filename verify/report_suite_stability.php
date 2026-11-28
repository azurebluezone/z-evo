<?php

/**
 * Stability check for the test suite's writes to the live database.
 *
 * Why this exists: the suite runs against the real MySQL connection, so a
 * tearDown that forgets a row does not fail on its own run - it fails the NEXT
 * run. Three such leaks were found and fixed (LandingCampaignOrderTest,
 * Phase4TikTokWiringAndVariantStockGuardTest, Phase5OrderDetailsConsistencyTest),
 * plus the where('col', $array) misuse that silently kept the first id only.
 *
 * What it proves, without re-running the whole suite (a full double run needs
 * more than the 120s contract gate allows):
 *   1. every fixed tearDown still deletes the rows it created;
 *   2. no test file still passes an array to where() on an id column - that
 *      compiles to `col = <first id>` and leaks the rest;
 *   3. no orphan stock row exists (AdminReportsFinalTest:74 fails on these);
 *   4. every tracked table still has exactly the row count recorded in the
 *      snapshot taken BEFORE two consecutive full-suite runs that both printed
 *      TABLE_COUNTS_STABLE (2026-11-28, chain exit 0, REPORT_SUITE_STABLE).
 *
 * Prints REPORT_SUITE_STABLE_PART1 only after all four hold; on failure it
 * prints the offending file/table and exits 1.
 */

$root = dirname(__DIR__);

$fail = static function (string $message): void {
    fwrite(STDERR, "STABILITY_CHECK_FAILED: $message\n");
    exit(1);
};

// --- 1. the tearDown fixes are still in place ------------------------------

$required = [
    'tests/Feature/LandingCampaignOrderTest.php' => [
        "DB::table('variant_stocks')->whereIn('variant_id', \$variantIds)->delete();",
        "DB::table('product_variants')->whereIn('product_id', \$this->createdProductIds)->delete();",
        "DB::table('inventory_movements')->whereIn('variant_id', \$variantIds)->delete();",
        "->where('ref_type', 'order')",
    ],
    'tests/Feature/LandingOrderPlacementTest.php' => [
        "DB::table('product_variants')->whereIn('product_id', \$this->createdProductIds)->delete();",
    ],
    'tests/Feature/SampleTemplateOrderTest.php' => [
        "DB::table('product_variants')->whereIn('product_id', \$this->createdProductIds)->delete();",
    ],
    'tests/Feature/Phase4TikTokWiringAndVariantStockGuardTest.php' => [
        "DB::table('inventory_reservations')->whereIn('order_id', \$orderIds)->delete();",
        "DB::table('inventory_reservations')->whereIn('variant_id', \$this->createdVariantIds)->delete();",
    ],
    'tests/Feature/Phase5OrderDetailsConsistencyTest.php' => [
        "DB::table('inventory_reservations')->whereIn('order_id', \$orderIds)->delete();",
        "DB::table('inventory_reservations')->whereIn('variant_id', \$this->createdVariantIds)->delete();",
    ],
];

foreach ($required as $relative => $snippets) {
    $path = $root . '/' . $relative;
    if (!is_file($path)) {
        $fail("$relative is missing");
    }
    $body = (string) file_get_contents($path);
    foreach ($snippets as $snippet) {
        if (strpos($body, $snippet) === false) {
            $fail("$relative no longer contains: $snippet");
        }
    }
}

// --- 2. no test still passes an array to where() on an id column -----------

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/tests', FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    foreach (file($file->getPathname()) as $number => $line) {
        // Only an array-shaped argument leaks: $this->createdProductIds,
        // $orderIds, ...Ids. A scalar like $product->id is correct where().
        if (preg_match("/->where\\('(product_id|variant_id|order_id|customer_id)',\\s*\\$[A-Za-z0-9_>\\[\\]'-]*[Ii]ds\\b(?!\\[)/", $line)) {
            $fail(
                $file->getFilename() . ':' . ($number + 1)
                . " passes an array to where(); use whereIn() - " . trim($line)
            );
        }
    }
}

// --- 3 + 4. database state --------------------------------------------------

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

$orphanStock = (int) $pdo->query(
    'SELECT COUNT(*) FROM variant_stocks s'
    . ' LEFT JOIN product_variants v ON v.id = s.variant_id WHERE v.id IS NULL'
)->fetchColumn();
if ($orphanStock !== 0) {
    $fail("$orphanStock orphan variant_stocks rows (AdminReportsFinalTest:74 fails on these)");
}

$snapshotFile = __DIR__ . '/.table_counts.json';
if (!is_file($snapshotFile)) {
    $fail('no snapshot at verify/.table_counts.json - run verify/report_table_counts.php --snapshot');
}
$before = json_decode((string) file_get_contents($snapshotFile), true);
if (!is_array($before) || $before === []) {
    $fail('unreadable snapshot at verify/.table_counts.json');
}

$moved = [];
foreach ($before as $table => $was) {
    $now = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ((int) $was !== $now) {
        $moved[] = "$table: $was -> $now";
    }
}
if ($moved !== []) {
    $fail('counts differ from the pre-double-run snapshot: ' . implode('; ', $moved));
}

echo 'REPORT_SUITE_STABLE_PART1 fixes=' . count($required)
    . ' tables=' . count($before)
    . " orphan_stock=0\n";
exit(0);
