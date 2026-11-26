<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\VariantStock;
use App\Support\SeedAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The canonical Z EVO development catalog (built from the Adora 2026 source).
 *
 * Reads the committed bundle at
 * `public/seed-assets/adora-2026/catalog.json` and creates, for every catalog
 * entry:
 *
 *   categories   12 age categories with their real source photos
 *   products     one row per catalog slug, upserted by slug
 *   images       four gallery images per product, exact order
 *   attributes   Color / Size attribute groups and their values
 *   variants     three variants per product, SKU `AD-<source_id>-DEFAULT|V2|V3`
 *   stocks       one variant_stocks row per variant, created by VariantStock
 *   ledger       one OPENING_BALANCE movement backing each stock row
 *
 * WHY THREE VARIANTS
 * ------------------
 * The storefront contract is that choosing an attribute must change both the
 * photo and the price. Each variant therefore has its own image file and its
 * own `price_override`; the JSON shape is the one VariantAttributes and
 * FrontendController read: `{"attrs":{...},"ids":{...}}`.
 *
 * Products whose garment is saturated enough get three colours (Original, Sky
 * Blue, Blush Pink). The rest get three age-appropriate sizes. The choice was
 * made once at asset-build time and is stored in `variant_mode`, so re-running
 * this seeder never changes a product's variant family.
 *
 * WHY THE PRODUCT CODE IS `AD-<source_id>`
 * ----------------------------------------
 * The source dump reuses shop-wide codes (`P0001`) that older demo seeders in
 * this repository also used. Using the source id keeps the seeded catalog
 * deterministic and collision-free without touching or deleting legacy rows
 * that demo orders may still reference.
 *
 * WHY STOCK IS WRITTEN TO variant_stocks
 * --------------------------------------
 * Stock lives in `variant_stocks` backed by the `inventory_movements` ledger.
 * Each variant gets its opening quantity through VariantStock::ensureRow(), so
 * the storefront and the admin read the same projection.
 *
 * IDEMPOTENT: products are matched by slug, variants by SKU, gallery rows are
 * replaced as an exact set, and VariantStock only creates a missing stock
 * row. `db:seed --class=DevCatalogSeeder` can run repeatedly.
 */
class DevCatalogSeeder extends Seeder
{
    /** Opening quantity for every variant. */
    private const OPENING_STOCK = 25;

    /** Bundle manifest relative to public/. */
    private const BUNDLE = 'seed-assets/adora-2026/catalog.json';

    public function run(): void
    {
        $path = public_path(self::BUNDLE);

        if (! is_file($path)) {
            throw new RuntimeException("Missing catalog bundle: {$path}");
        }

        $catalog = json_decode((string) file_get_contents($path), true);
        $products = is_array($catalog) ? ($catalog['products'] ?? []) : [];
        $categories = is_array($catalog) ? ($catalog['categories'] ?? []) : [];

        if (! is_array($products) || count($products) !== 30) {
            throw new RuntimeException(
                "Expected 30 products in {$path}, got " . (is_array($products) ? count($products) : 0) . '.'
            );
        }

        if (! is_array($categories) || count($categories) !== 12) {
            throw new RuntimeException(
                "Expected 12 categories in {$path}, got " . (is_array($categories) ? count($categories) : 0) . '.'
            );
        }

        $categoryImages = [];

        foreach ($categories as $category) {
            $name = trim((string) ($category['name'] ?? ''));
            $file = basename((string) ($category['file'] ?? ''));

            if ($name === '' || $file === '') {
                throw new RuntimeException('Catalog category without name or file.');
            }

            $relative = SeedAssets::categoryUploadPath($file);

            if (! is_file(public_path($relative))) {
                throw new RuntimeException(
                    "Category image missing for {$name}: {$relative}. Run SeedAssetSeeder first."
                );
            }

            $categoryImages[$name] = $relative;
        }

        $now = now();
        $created = 0;
        $updated = 0;
        $variantCount = 0;

        DB::transaction(function () use ($products, $categoryImages, $now, &$created, &$updated, &$variantCount): void {
            $categoryIds = [];

            foreach ($products as $row) {
                $category = trim((string) ($row['category'] ?? ''));

                if ($category === '' || ! isset($categoryImages[$category])) {
                    throw new RuntimeException('Catalog product without a known category: ' . ($row['slug'] ?? '?'));
                }

                $categoryIds[$category] ??= $this->ensureCategory($category, $categoryImages[$category], $now);
            }

            foreach (array_values($products) as $index => $row) {
                $existed = DB::table('products')
                    ->where('slug', (string) ($row['slug'] ?? ''))
                    ->exists();

                $variantCount += $this->seedProduct($row, $index, $categoryIds, $now);

                $existed ? $updated++ : $created++;
            }
        });

        $this->command?->info(sprintf(
            'Z EVO catalog: %d products (%d created, %d updated), %d categories, %d variants.',
            count($products),
            $created,
            $updated,
            count($categoryImages),
            $variantCount
        ));
    }

    private function ensureCategory(string $name, string $image, \DateTimeInterface $now): int
    {
        $slug = Str::slug($name);
        $existing = DB::table('categories')->where('slug', $slug)->first();

        if ($existing) {
            DB::table('categories')->where('id', $existing->id)->update([
                'name' => $name,
                'image' => $image,
                'meta_title' => $name,
                'status' => 1,
                'home_page' => 1,
                'updated_at' => $now,
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('categories')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => 0,
            'image' => $image,
            'meta_title' => $name,
            'status' => 1,
            'home_page' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, int>   $categoryIds
     *
     * @return int number of variants written for this product
     */
    private function seedProduct(
        array $row,
        int $index,
        array $categoryIds,
        \DateTimeInterface $now
    ): int {
        $slug = trim((string) ($row['slug'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $sourceId = (int) ($row['source_id'] ?? 0);
        $category = trim((string) ($row['category'] ?? ''));
        $images = $row['images'] ?? [];
        $variants = $row['variants'] ?? [];

        if ($slug === '' || $name === '' || $sourceId <= 0) {
            throw new RuntimeException('Catalog product without slug, name or source id.');
        }

        if (! is_array($images) || count($images) !== 4) {
            throw new RuntimeException("Catalog product {$slug} must have four gallery images.");
        }

        if (! is_array($variants) || count($variants) !== 3) {
            throw new RuntimeException("Catalog product {$slug} must have three variants.");
        }

        $imagePaths = [];

        foreach ($images as $image) {
            $file = basename((string) ($image['file'] ?? ''));

            if ($file === '' || $file === '.' || $file === '..') {
                throw new RuntimeException("Catalog product {$slug} has an image without a file name.");
            }

            $relative = SeedAssets::relativeUploadPath($slug, $file);

            if (! is_file(public_path($relative))) {
                throw new RuntimeException(
                    "Seed image missing for {$slug}: {$relative}. Run SeedAssetSeeder first."
                );
            }

            $imagePaths[] = $relative;
        }

        $code = 'AD-' . $sourceId;
        $codeOwner = DB::table('products')->where('product_code', $code)->value('slug');

        if ($codeOwner !== null && $codeOwner !== $slug) {
            throw new RuntimeException("Product code {$code} already belongs to {$codeOwner}.");
        }

        $description = trim((string) ($row['description'] ?? '')) ?: $name;

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'category_id' => $categoryIds[$category],
            'subcategory_id' => null,
            'childcategory_id' => null,
            'brand_id' => null,
            'product_code' => $code,
            'purchase_price' => (int) ($row['purchase_price'] ?? 0),
            'old_price' => (int) ($row['old_price'] ?? 0) ?: null,
            'new_price' => (int) ($row['new_price'] ?? 0),
            'pro_unit' => null,
            'pro_video' => null,
            'document' => null,
            'meta_description' => Str::limit($description, 160),
            'description' => $description,
            'topsale' => $index % 5 === 0 ? 1 : 0,
            'feature_product' => $index % 4 === 0 ? 1 : 0,
            'is_recommended' => $index % 3 === 0 ? 1 : 0,
            'recommended_sort_order' => $index % 3 === 0 ? $index : 0,
            'campaign_id' => null,
            'status' => 1,
            'updated_at' => $now,
        ];

        $existing = DB::table('products')->where('slug', $slug)->first();

        if ($existing) {
            DB::table('products')->where('id', $existing->id)->update($payload);
            $productId = (int) $existing->id;
        } else {
            $productId = (int) DB::table('products')->insertGetId($payload + [
                'created_at' => $now,
            ]);
        }

        // Product::image() is a hasOne relation, so the gallery is written as
        // an exact ordered set rather than appended to.
        DB::table('productimages')->where('product_id', $productId)->delete();

        foreach ($imagePaths as $imagePath) {
            DB::table('productimages')->insert([
                'product_id' => $productId,
                'image' => $imagePath,
                'title' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $seenSkus = [];

        foreach (array_values($variants) as $variantIndex => $variant) {
            $skuSuffix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($variant['sku_suffix'] ?? '')));

            if ($skuSuffix === '') {
                throw new RuntimeException("Variant {$variantIndex} of {$slug} has no SKU suffix.");
            }

            $sku = $code . '-' . $skuSuffix;
            $seenSkus[] = $sku;

            $imageFile = basename((string) ($variant['image'] ?? ''));
            $imagePath = SeedAssets::relativeUploadPath($slug, $imageFile);

            if ($imageFile === '' || ! is_file(public_path($imagePath))) {
                throw new RuntimeException("Variant image missing for {$sku}: {$imagePath}.");
            }

            $attribute = $variant['attributes'] ?? [];

            if (! is_array($attribute)) {
                throw new RuntimeException("Variant {$sku} has no attribute selection.");
            }

            $groupSlug = trim((string) ($attribute['group_slug'] ?? ''));
            $groupName = trim((string) ($attribute['group_name'] ?? ''));
            $value = trim((string) ($attribute['value'] ?? ''));
            $valueCode = trim((string) ($attribute['code'] ?? ''));

            if ($groupSlug === '' || $groupName === '' || $value === '') {
                throw new RuntimeException("Variant {$sku} has an incomplete attribute selection.");
            }

            $attributeId = $this->ensureAttribute($groupSlug, $groupName, $now);
            $valueId = $this->ensureAttributeValue($attributeId, $value, $valueCode, $variantIndex, $now);

            $attrs = [$groupSlug => $value];
            $ids = [$groupSlug => (string) $valueId];

            if ($valueCode !== '') {
                // Mirrors VariantAttributes::buildFromRequest: the swatch code
                // rides alongside the label, but the ids map stays one entry
                // per group so the frontend matching algorithm is untouched.
                $attrs[$groupSlug . '_code'] = $valueCode;
            }

            $skuOwner = DB::table('product_variants')->where('sku', $sku)->value('product_id');

            if ($skuOwner !== null && (int) $skuOwner !== $productId) {
                throw new RuntimeException("Variant SKU {$sku} already belongs to product {$skuOwner}.");
            }

            $variantPayload = [
                'product_id' => $productId,
                'sku' => $sku,
                'title' => (string) ($variant['title'] ?? ($name . ' - ' . $value)),
                'image' => $imagePath,
                'price_override' => (int) ($variant['price'] ?? $row['new_price'] ?? 0),
                'purchase_price' => (int) ($variant['purchase_price'] ?? $row['purchase_price'] ?? 0),
                'sale_price' => null,
                'discount_price' => null,
                'weight_gram' => null,
                'status' => 1,
                'attributes_json' => json_encode(['attrs' => $attrs, 'ids' => $ids], JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ];

            $variantRow = DB::table('product_variants')->where('sku', $sku)->first();

            if ($variantRow) {
                DB::table('product_variants')->where('id', $variantRow->id)->update($variantPayload);
                $variantId = (int) $variantRow->id;
            } else {
                $variantId = (int) DB::table('product_variants')->insertGetId($variantPayload + [
                    'created_at' => $now,
                ]);
            }

            // Creates the variant_stocks row AND its OPENING_BALANCE movement.
            VariantStock::ensureRow($variantId, self::OPENING_STOCK);
            $this->ensureOpeningBalance($variantId, $now);
        }

        $this->removeStaleVariants($productId, $seenSkus);

        return count($seenSkus);
    }

    private function ensureAttribute(string $slug, string $name, \DateTimeInterface $now): int
    {
        $existing = DB::table('attributes')->where('slug', $slug)->first();

        if ($existing) {
            DB::table('attributes')->where('id', $existing->id)->update([
                'name' => $existing->name ?: $name,
                'kind' => $existing->kind ?: 'selectable',
                'status' => 1,
                'updated_at' => $now,
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('attributes')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'kind' => 'selectable',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureAttributeValue(int $attributeId, string $value, string $code, int $sortOrder, \DateTimeInterface $now): int
    {
        $existing = DB::table('attribute_values')
            ->where('attribute_id', $attributeId)
            ->where('value', $value)
            ->first();

        $payload = [
            'code' => $code !== '' ? $code : null,
            'multiplier' => 1,
            'base_unit' => null,
            'kind' => 'selectable',
            'status' => 1,
            'sort_order' => $sortOrder,
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('attribute_values')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        return (int) DB::table('attribute_values')->insertGetId($payload + [
            'attribute_id' => $attributeId,
            'value' => $value,
            'created_at' => $now,
        ]);
    }

    /**
     * Self-heal a pre-existing stock row without a matching ledger entry: it
     * would otherwise be backed by nothing. An existing movement is never
     * duplicated.
     */
    private function ensureOpeningBalance(int $variantId, \DateTimeInterface $now): void
    {
        $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->first();

        if (! $stock) {
            return;
        }

        $hasOpening = DB::table('inventory_movements')
            ->where('variant_id', $variantId)
            ->where('type', 'OPENING_BALANCE')
            ->exists();

        if ($hasOpening) {
            return;
        }

        InventoryMovement::record([
            'variant_id' => $variantId,
            'type' => 'OPENING_BALANCE',
            'bucket' => 'sellable',
            'qty_delta' => (int) $stock->on_hand,
            'ref_type' => 'seed',
            'ref_id' => null,
            'created_by' => null,
            'note' => 'Opening balance recorded by DevCatalogSeeder.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Drop variants this seeder previously created but no longer owns.
     *
     * A variant referenced by an order or a live reservation is historical
     * evidence and is never deleted; only an unreferenced leftover from an
     * earlier seed run disappears, keeping "three variants per product" exact.
     *
     * @param array<int, string> $keepSkus
     */
    private function removeStaleVariants(int $productId, array $keepSkus): void
    {
        $stale = DB::table('product_variants')
            ->where('product_id', $productId)
            ->whereNotIn('sku', $keepSkus)
            ->pluck('id');

        foreach ($stale as $variantId) {
            $variantId = (int) $variantId;

            if (DB::table('order_details')->where('variant_id', $variantId)->exists()) {
                continue;
            }

            if (Schema::hasTable('inventory_reservations')
                && DB::table('inventory_reservations')->where('variant_id', $variantId)->exists()) {
                continue;
            }

            DB::table('variant_stocks')->where('variant_id', $variantId)->delete();
            DB::table('inventory_movements')->where('variant_id', $variantId)->delete();
            DB::table('product_variants')->where('id', $variantId)->delete();
        }
    }
}
