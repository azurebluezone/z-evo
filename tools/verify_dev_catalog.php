<?php

/**
 * Verify the canonical Adora dev catalog after DevCatalogSeeder has run.
 *
 * Exits 0 with DEV_CATALOG_SEED_OK only when every catalog product has:
 *   - exactly one products row for its slug
 *   - exactly the catalog gallery, in order, with every file on disk
 *   - exactly one default variant with SKU AD-<source_id>-DEFAULT
 *   - exactly one variant_stocks row
 *   - exactly one OPENING_BALANCE inventory movement
 *
 * Repeated-seed duplicates fail the check, which is the point: the seeder must
 * be idempotent, not merely "not crash on the second run".
 *
 * Usage:
 *   php tools/verify_dev_catalog.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$catalogPath = $root . '/public/seed-assets/adora-2026/catalog.json';

require $root . '/vendor/autoload.php';

if (! is_file($catalogPath)) {
    fwrite(STDERR, "Missing catalog: {$catalogPath}\n");
    exit(11);
}

$catalog = json_decode((string) file_get_contents($catalogPath), true);
$products = is_array($catalog) ? ($catalog['products'] ?? []) : [];

if (! is_array($products) || count($products) !== 30) {
    fwrite(STDERR, 'Expected 30 catalog products, got ' . (is_array($products) ? count($products) : 0) . ".\n");
    exit(11);
}

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

$env = env_values($root . '/.env');
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$database = $env['DB_DATABASE'] ?? '';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . "\n");
    exit(12);
}

$failures = [];
$imageCount = 0;
$variantCount = 0;

$productBySlug = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
$imagesForProduct = $pdo->prepare('SELECT image FROM productimages WHERE product_id = ? ORDER BY id');
$variantsForProduct = $pdo->prepare('SELECT id, sku FROM product_variants WHERE product_id = ? ORDER BY id');
$stockForVariant = $pdo->prepare('SELECT COUNT(*) FROM variant_stocks WHERE variant_id = ?');
$openingsForVariant = $pdo->prepare(
    "SELECT COUNT(*) FROM inventory_movements WHERE variant_id = ? AND type = 'OPENING_BALANCE'"
);
$categoryBySlug = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = ?');

$checkCategories = [];

foreach ($products as $row) {
    $slug = (string) ($row['slug'] ?? '');
    $sourceId = (int) ($row['source_id'] ?? 0);
    $category = (string) ($row['category'] ?? '');

    if ($slug === '' || $sourceId <= 0 || $category === '') {
        $failures[] = 'catalog row incomplete: ' . json_encode($row);

        continue;
    }

    $checkCategories[$category] = true;

    $productBySlug->execute([$slug]);
    $productIds = $productBySlug->fetchAll(PDO::FETCH_COLUMN);

    if (count($productIds) !== 1) {
        $failures[] = "{$slug}: product rows=" . count($productIds);

        continue;
    }

    $productId = (int) $productIds[0];

    $imagesForProduct->execute([$productId]);
    $actualImages = $imagesForProduct->fetchAll(PDO::FETCH_COLUMN);
    $expectedImages = [];

    foreach (($row['images'] ?? []) as $image) {
        $file = basename((string) ($image['file'] ?? ''));
        $relative = 'uploads/product/seed/' . $slug . '/' . $file;
        $expectedImages[] = $relative;
        $imageCount++;

        if (! is_file($root . '/public/' . $relative)) {
            $failures[] = "{$slug}: missing file {$relative}";
        }
    }

    if ($expectedImages === []) {
        $failures[] = "{$slug}: catalog has no images";
    } elseif ($actualImages !== $expectedImages) {
        $failures[] = "{$slug}: image rows do not match the catalog (got " . count($actualImages)
            . ', expected ' . count($expectedImages) . ')';
    }

    $variantsForProduct->execute([$productId]);
    $variants = $variantsForProduct->fetchAll(PDO::FETCH_ASSOC);
    $variantCount += count($variants);

    if (count($variants) !== 1) {
        $failures[] = "{$slug}: variant rows=" . count($variants);

        continue;
    }

    $variant = $variants[0];
    $expectedSku = 'AD-' . $sourceId . '-DEFAULT';

    if ((string) $variant['sku'] !== $expectedSku) {
        $failures[] = "{$slug}: sku={$variant['sku']}, expected {$expectedSku}";
    }

    $variantId = (int) $variant['id'];

    $stockForVariant->execute([$variantId]);
    $stockRows = (int) $stockForVariant->fetchColumn();

    if ($stockRows !== 1) {
        $failures[] = "{$slug}: variant_stocks rows={$stockRows}";
    }

    $openingsForVariant->execute([$variantId]);
    $openingRows = (int) $openingsForVariant->fetchColumn();

    if ($openingRows !== 1) {
        $failures[] = "{$slug}: OPENING_BALANCE movements={$openingRows}";
    }
}

foreach (array_keys($checkCategories) as $category) {
    $slug = Illuminate\Support\Str::slug($category);
    $categoryBySlug->execute([$slug]);
    $rows = (int) $categoryBySlug->fetchColumn();

    if ($rows !== 1) {
        $failures[] = "category {$category}: rows={$rows}";
    }
}

if ($failures !== []) {
    fwrite(STDERR, "DEV_CATALOG_SEED_FAILED\n");

    foreach (array_slice($failures, 0, 20) as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }

    if (count($failures) > 20) {
        fwrite(STDERR, ' - ... ' . (count($failures) - 20) . " more\n");
    }

    exit(13);
}

echo 'PRODUCTS ' . count($products) . "\n";
echo 'IMAGES ' . $imageCount . "\n";
echo 'VARIANTS ' . $variantCount . "\n";
echo "DEV_CATALOG_SEED_OK\n";
