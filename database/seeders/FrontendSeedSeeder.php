<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * FrontendSeedSeeder
 * ------------------
 * Seeds the demo storefront from the manifest produced by
 * tools/frontend_seed/build_frontend_seed.py:
 *
 *   work/t/data/frontend_seed.json
 *
 * What it builds (all relations resolved by name, ids never hard-coded):
 *   categories (root)          <- product "cat"
 *   subcategories              <- product "sub"        (parent = category)
 *   childcategories            <- product "child"      (parent = subcategory)
 *   products                   <- 20 scraped Shinjuku products
 *   productimages              <- every gallery image, in source order
 *   product_variants + stocks  <- only when the source actually had variants
 *   banner_categories(id=1) + banners   <- hero slider (category_id=1)
 *   promo_banners              <- 1240x1000 promo images
 *
 * Idempotent: every write is firstOrCreate/updateOrCreate or delete+insert,
 * so `php artisan db:seed --class=FrontendSeedSeeder` can run repeatedly.
 *
 * Media paths are stored the way the rest of the app stores them:
 *   uploads/frontend-seed/...   (web-root relative, see App\Support\MediaPath)
 */
class FrontendSeedSeeder extends Seeder
{
    private const MANIFEST = 'work/t/data/frontend_seed.json';

    private const IMAGE_ROOT = 'uploads/frontend-seed';

    public function run(): void
    {
        $path = base_path(self::MANIFEST);
        if (! is_file($path)) {
            $this->command?->error("Missing manifest: {$path}");
            $this->command?->warn('Run: python3 tools/frontend_seed/build_frontend_seed.py');

            return;
        }

        $manifest = json_decode(file_get_contents($path), true);
        $products = $manifest['products'] ?? [];
        if (! is_array($products) || $products === []) {
            $this->command?->error('Manifest has no products.');

            return;
        }

        DB::transaction(function () use ($manifest, $products): void {
            $this->seedBannerCategory();
            $banners = $this->seedBanners($manifest['banners']['slider'] ?? []);
            $promo = $this->seedPromoBanners($manifest['banners']['promo'] ?? []);

            $this->command?->info(sprintf(
                'Banners: %d hero / %d promo',
                $banners,
                $promo
            ));

            $seeded = 0;
            $productsSkipped = 0;
            foreach ($products as $row) {
                if ($this->seedProduct($row)) {
                    $seeded++;
                } else {
                    $productsSkipped++;
                }
            }

            $this->command?->info("Products seeded: {$seeded} (skipped: {$productsSkipped})");
        });
    }

    // ------------------------------------------------------------- banners

    private function seedBannerCategory(): void
    {
        // The storefront reads the hero slider from banner category 1.
        if (! Schema::hasTable('banner_categories')) {
            return;
        }

        DB::table('banner_categories')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Hero Slider', 'status' => 1, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function seedBanners(array $slides): int
    {
        if (! Schema::hasTable('banners') || ! Schema::hasTable('banner_categories')) {
            return 0;
        }

        $count = 0;
        foreach (array_values($slides) as $index => $slide) {
            $rel = $slide['rel'] ?? null;
            if (! $rel || ! is_file(public_path($rel))) {
                continue;
            }

            $link = $this->routeOr('/products');
            DB::table('banners')->updateOrInsert(
                ['image' => $rel, 'category_id' => 1],
                [
                    'title' => $slide['title'] ?? $this->slideTitle($rel, $index),
                    'media_type' => 'image',
                    'video' => null,
                    'link' => $link,
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function seedPromoBanners(array $promos): int
    {
        if (! Schema::hasTable('promo_banners')) {
            return 0;
        }

        $count = 0;
        foreach (array_values($promos) as $index => $promo) {
            $rel = $promo['rel'] ?? null;
            if (! $rel || ! is_file(public_path($rel))) {
                continue;
            }

            $title = $this->promoTitle($rel, $index);
            DB::table('promo_banners')->updateOrInsert(
                ['image' => $rel],
                [
                    'title' => $title,
                    'link' => $this->routeOr('/hot-deals'),
                    'sort_order' => $index,
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function slideTitle(string $rel, int $index): string
    {
        $titles = ['Fresh halal groceries, delivered', 'Market-fresh picks every week', 'Your halal kitchen, stocked'];
        return $titles[$index] ?? ('Halal picks #' . ($index + 1));
    }

    private function promoTitle(string $rel, int $index): string
    {
        $titles = ['Fresh ingredients, honest prices', 'Butcher-quality halal meat', 'Pantry staples for every home'];
        return $titles[$index] ?? ('Weekly offer #' . ($index + 1));
    }

    private function routeOr(string $fallback): string
    {
        try {
            return route('products.page');
        } catch (\Throwable $e) {
            return url($fallback);
        }
    }

    // ------------------------------------------------------------ products

    private function seedProduct(array $row): bool
    {
        $slug = trim((string) ($row['slug'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        if ($slug === '' || $name === '') {
            return false;
        }

        [$catId, $subId, $childId] = $this->resolveTaxonomy($row);

        $price = (int) ($row['cur'] ?? 0);
        if ($price <= 0) {
            $price = (int) ($row['reg'] ?? 0);
        }
        if ($price <= 0) {
            $price = 100;
        }
        $oldPrice = (int) ($row['reg'] ?? $price);
        if ($oldPrice < $price) {
            $oldPrice = $price;
        }

        $stock = ! empty($row['stock']) ? 25 : 0;

        $payload = $this->columns('products', [
            'name' => $name,
            'slug' => $slug,
            'category_id' => $catId ?? 0,
            'subcategory_id' => $subId,
            'childcategory_id' => $childId,
            'brand_id' => null,
            'product_code' => 'FS-' . (int) ($row['id'] ?? crc32($slug)),
            'purchase_price' => $price,
            'old_price' => $oldPrice,
            'new_price' => $price,
            'stock' => $stock,
            'pro_unit' => $this->unit((string) ($row['weight'] ?? '')),
            'meta_description' => Str::limit((string) ($row['short'] ?? $name), 250),
            'description' => (string) ($row['desc'] ?? $row['short'] ?? $name),
            'topsale' => 0,
            'feature_product' => 0,
            'campaign_id' => null,
            'status' => 1,
        ]);

        $product = DB::table('products')->where('slug', $slug)->first();
        if ($product) {
            DB::table('products')->where('id', $product->id)->update($payload + ['updated_at' => now()]);
            $productId = $product->id;
        } else {
            $productId = DB::table('products')->insertGetId($payload + ['created_at' => now(), 'updated_at' => now()]);
        }

        $this->seedImages($productId, $row);
        $this->seedVariants($productId, $row, $name);

        return true;
    }

    /**
     * @return array{0: int|null, 1: int|null, 2: int|null}
     */
    private function resolveTaxonomy(array $row): array
    {
        $catName = trim((string) ($row['cat'] ?? ''));
        $subName = trim((string) ($row['sub'] ?? ''));
        $childName = trim((string) ($row['child'] ?? ''));

        $catId = $subId = $childId = null;

        if ($catName !== '') {
            $catId = $this->firstOrCreateId('categories', ['name' => $catName], [
                'slug' => Str::slug($catName),
                'parent_id' => 0,
                'image' => 'uploads/category/default.png',
                'meta_title' => $catName,
                'meta_decription' => $catName,
                'status' => 1,
            ]);
        }

        if ($catId && $subName !== '') {
            $subId = $this->firstOrCreateId('subcategories', [
                'subcategoryName' => $subName,
                'category_id' => $catId,
            ], [
                'slug' => Str::slug($catName . '-' . $subName),
                'image' => null,
                'meta_title' => $subName,
                'meta_decription' => $subName,
                'status' => 1,
            ]);
        }

        if ($subId && $childName !== '') {
            $childId = $this->firstOrCreateId('childcategories', [
                'childcategoryName' => $childName,
                'subcategory_id' => $subId,
            ], [
                'slug' => Str::slug($catName . '-' . $subName . '-' . $childName),
                'meta_title' => $childName,
                'meta_decription' => $childName,
                'status' => 1,
            ]);
        }

        return [$catId, $subId, $childId];
    }

    private function seedImages(int $productId, array $row): void
    {
        if (! Schema::hasTable('productimages')) {
            return;
        }

        DB::table('productimages')->where('product_id', $productId)->delete();

        $title = (string) ($row['name'] ?? 'Product image');
        foreach ($row['images'] ?? [] as $image) {
            $rel = $image['rel'] ?? null;
            if (! $rel || ! is_file(public_path($rel))) {
                continue;
            }

            DB::table('productimages')->insert($this->columns('productimages', [
                'image' => $rel,
                'title' => $title,
                'product_id' => $productId,
            ]) + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function seedVariants(int $productId, array $row, string $name): void
    {
        if (! Schema::hasTable('product_variants') || empty($row['vars'])) {
            return;
        }

        DB::table('product_variants')->where('product_id', $productId)->delete();

        $price = (int) ($row['cur'] ?? 0);
        foreach (array_values($row['vars']) as $index => $variant) {
            $attrs = [];
            foreach ($variant['attr'] ?? [] as $attr) {
                $attrs[(string) ($attr['name'] ?? 'Option')] = (string) ($attr['value'] ?? '');
            }

            $label = implode(' / ', array_filter($attrs)) ?: ('Option ' . ($index + 1));
            $sku = 'FS-' . (int) ($row['id'] ?? 0) . '-' . ($index + 1);

            $variantId = DB::table('product_variants')->insertGetId($this->columns('product_variants', [
                'product_id' => $productId,
                'sku' => $sku,
                'title' => $name . ' - ' . $label,
                'price_override' => $price > 0 ? $price : null,
                'weight_gram' => null,
                'status' => 1,
                'attributes_json' => json_encode($attrs, JSON_UNESCAPED_UNICODE),
            ]) + ['created_at' => now(), 'updated_at' => now()]);

            if (Schema::hasTable('variant_stocks')) {
                DB::table('variant_stocks')->updateOrInsert(
                    ['variant_id' => $variantId],
                    ['on_hand' => 25, 'reserved' => 0, 'available' => 25, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    private function unit(string $weight): ?string
    {
        $weight = trim($weight);
        if ($weight === '') {
            return null;
        }

        return is_numeric($weight) ? $weight . ' kg' : $weight;
    }

    // -------------------------------------------------------------- helpers

    private function firstOrCreateId(string $table, array $where, array $values): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $existing = DB::table($table)->where($where)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $values = $this->columns($table, array_merge($where, $values));
        $id = DB::table($table)->insertGetId($values + ['created_at' => now(), 'updated_at' => now()]);

        return (int) $id;
    }

    /** Drop keys the live schema does not have (keeps the seeder portable). */
    private function columns(string $table, array $values): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $existing = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($values, $existing);
    }
}
