<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the demo grocery catalogue.
 *
 * The product images are produced by tools/make_demo_product_images.py, which
 * draws the same names and badges onto the frames. The text therefore lives
 * here as the single source of truth for the catalogue and the image script
 * mirrors it; nothing is invented at runtime.
 *
 * Every row it creates is stamped with DEMO_TAG inside meta_description so a
 * later `--wipe` removes exactly what this command added and leaves the shop
 * untouched.
 */
class SeedDemoProductsCommand extends Command
{
    protected $signature = 'demo:products
        {--wipe : Delete only the rows this command created, then exit}
        {--dry-run : Print the plan without writing anything}';

    protected $description = 'Seed or remove the demo grocery catalogue (20 products, images, variants).';

    /** Marker written into meta_description so a wipe can find our own rows. */
    private const DEMO_TAG = 'demo-catalogue-zevo';

    /** The single grocery attribute every product shares. */
    private const PACK_ATTRIBUTE_SLUG = 'pack-size';
    private const PACK_ATTRIBUTE_NAME = 'Pack Size';

    /**
     * Pack sizes sold for every product, in display order. The multiplier keeps
     * the price exactly proportional (2x and 3x of the base) so no discount is
     * invented; stock is split across the packs on the same ratio.
     *
     * @var array<int, array{label: string, multiplier: int, sku_suffix: string}>
     */
    private const PACK_SIZES = [
        ['label' => 'Single', 'multiplier' => 1, 'sku_suffix' => '-V1'],
        ['label' => 'Pack of 2', 'multiplier' => 2, 'sku_suffix' => '-V2'],
        ['label' => 'Pack of 3', 'multiplier' => 3, 'sku_suffix' => '-V3'],
    ];

    /**
     * The catalogue. 'motif' matches the drawing routine of the same name in
     * tools/make_demo_product_images.py.
     *
     * @var array<int, array<string, mixed>>
     */
    private const CATALOGUE = [
        ['slug' => 'organic-hass-avocado', 'name' => 'Organic Hass Avocado', 'badge' => '100% Organic', 'unit' => '500 g', 'motif' => 'avocado', 'category' => 'Fresh Vegetables', 'old' => 620, 'new' => 499, 'stock' => 40],
        ['slug' => 'crispy-fresh-broccoli', 'name' => 'Crisp Farm Broccoli', 'badge' => 'Farm Fresh', 'unit' => '1 pc', 'motif' => 'broccoli', 'category' => 'Fresh Vegetables', 'old' => 310, 'new' => 249, 'stock' => 35],
        ['slug' => 'pure-organic-milk', 'name' => 'Pure Organic Whole Milk', 'badge' => 'Daily Dairy', 'unit' => '1 L', 'motif' => 'bottle', 'category' => 'Dairy & Eggs', 'old' => 560, 'new' => 420, 'stock' => 60],
        ['slug' => 'artisan-raw-honey', 'name' => 'Artisan Wild Flower Honey', 'badge' => 'Raw & Unfiltered', 'unit' => '400 g', 'motif' => 'jar', 'category' => 'Pantry Staples', 'old' => 850, 'new' => 680, 'stock' => 25],
        ['slug' => 'farm-fresh-tomatoes', 'name' => 'Farm Fresh Tomatoes', 'badge' => 'Vine Ripened', 'unit' => '1 kg', 'motif' => 'tomato', 'category' => 'Fresh Vegetables', 'old' => 150, 'new' => 120, 'stock' => 80],
        ['slug' => 'organic-potatoes', 'name' => 'Organic Potatoes', 'badge' => 'Soil Grown', 'unit' => '2 kg', 'motif' => 'potato', 'category' => 'Fresh Vegetables', 'old' => 120, 'new' => 90, 'stock' => 90],
        ['slug' => 'fresh-spinach-bundle', 'name' => 'Fresh Spinach Bundle', 'badge' => 'Leafy Green', 'unit' => '300 g', 'motif' => 'leaf', 'category' => 'Fresh Vegetables', 'old' => 230, 'new' => 180, 'stock' => 45],
        ['slug' => 'free-range-eggs', 'name' => 'Free Range Eggs', 'badge' => 'Free Range', 'unit' => '12 pcs', 'motif' => 'eggs', 'category' => 'Dairy & Eggs', 'old' => 460, 'new' => 350, 'stock' => 70],
        ['slug' => 'fresh-carrots', 'name' => 'Fresh Carrots', 'badge' => 'Sweet Crunch', 'unit' => '500 g', 'motif' => 'carrot', 'category' => 'Fresh Vegetables', 'old' => 150, 'new' => 110, 'stock' => 85],
        ['slug' => 'red-apple', 'name' => 'Red Apple', 'badge' => 'Orchard Fresh', 'unit' => '1 kg', 'motif' => 'apple', 'category' => 'Fresh Fruits', 'old' => 280, 'new' => 220, 'stock' => 75],
        ['slug' => 'skinless-chicken', 'name' => 'Skinless Chicken Breast', 'badge' => 'Butcher Cut', 'unit' => '1 kg', 'motif' => 'meat', 'category' => 'Meat & Seafood', 'old' => 380, 'new' => 320, 'stock' => 30],
        ['slug' => 'artisan-sourdough-bread', 'name' => 'Artisan Sourdough Bread', 'badge' => 'Baked Fresh', 'unit' => '400 g', 'motif' => 'bread', 'category' => 'Bakery & Pantry', 'old' => 200, 'new' => 160, 'stock' => 28],
        ['slug' => 'red-onion', 'name' => 'Red Onions', 'badge' => 'Everyday Kitchen', 'unit' => '1 kg', 'motif' => 'onion', 'category' => 'Fresh Vegetables', 'old' => 95, 'new' => 75, 'stock' => 95],
        ['slug' => 'fresh-banana', 'name' => 'Fresh Bananas', 'badge' => 'Tropical Pick', 'unit' => '1 dozen', 'motif' => 'banana', 'category' => 'Fresh Fruits', 'old' => 130, 'new' => 105, 'stock' => 65],
        ['slug' => 'green-chili', 'name' => 'Green Chili', 'badge' => 'Farm Fresh', 'unit' => '250 g', 'motif' => 'chili', 'category' => 'Fresh Vegetables', 'old' => 70, 'new' => 55, 'stock' => 80],
        ['slug' => 'red-rice', 'name' => 'Red Rice', 'badge' => 'Staple Grain', 'unit' => '5 kg', 'motif' => 'rice', 'category' => 'Rice & Flour', 'old' => 420, 'new' => 350, 'stock' => 50],
        ['slug' => 'mustard-oil', 'name' => 'Cold Pressed Mustard Oil', 'badge' => 'Cold Pressed', 'unit' => '1 L', 'motif' => 'bottle', 'category' => 'Rice & Flour', 'old' => 780, 'new' => 695, 'stock' => 40],
        ['slug' => 'fresh-creamer-pack', 'name' => 'Fresh Creamer Pack', 'badge' => 'Daily Dairy', 'unit' => '500 ml', 'motif' => 'carton', 'category' => 'Dairy & Eggs', 'old' => 90, 'new' => 72, 'stock' => 90],
        ['slug' => 'desi-ghee', 'name' => 'Desi Ghee', 'badge' => 'Traditional Dairy', 'unit' => '500 g', 'motif' => 'jar', 'category' => 'Dairy & Eggs', 'old' => 1250, 'new' => 1090, 'stock' => 22],
        ['slug' => 'roasted-chickpeas', 'name' => 'Roasted Chickpeas', 'badge' => 'High Protein', 'unit' => '300 g', 'motif' => 'beans', 'category' => 'Pantry Staples', 'old' => 220, 'new' => 175, 'stock' => 48],
    ];

    /**
     * One brand for the demo range, created only when missing. The existing
     * brand table holds placeholder names from earlier seeding, so the demo
     * gets its own clearly named brand instead of borrowing one.
     */
    private const BRAND_SLUG = 'zevo-fresh';
    private const BRAND_NAME = 'Zevo Fresh';

    /**
     * Six products go into the flash sale. The section stays hidden until a
     * window is actually running AND it has products, so a demo catalogue with
     * neither would leave that home section permanently blank.
     *
     * @var array<int, string>
     */
    private const FLASH_SALE_SLUGS = [
        'organic-hass-avocado',
        'farm-fresh-tomatoes',
        'fresh-carrots',
        'free-range-eggs',
        'red-apple',
        'mustard-oil',
    ];

    /**
     * Promo banners reuse the catalogue frames. The promo_banners table was
     * empty, and the promo section hides itself on an empty collection.
     */
    private const PROMO_SLUGS = [
        'artisan-sourdough-bread',
        'desi-ghee',
        'red-rice',
    ];

    public function handle(): int
    {
        if ($this->option('wipe')) {
            return $this->wipe();
        }

        if ($this->option('dry-run')) {
            return $this->dryRun();
        }

        return $this->seed();
    }

    /** Print what would happen, write nothing. */
    private function dryRun(): int
    {
        $rows = $this->demoProductIds();
        $this->line('Dry run. No rows are written.');
        $this->line(sprintf('  products in catalogue     : %d', count(self::CATALOGUE)));
        $this->line(sprintf('  demo rows already present : %d', $rows->count()));
        $this->line(sprintf('  product images on disk    : %d', count($this->existingImages())));

        foreach (self::CATALOGUE as $item) {
            $image = 'uploads/product/' . $item['slug'] . '.jpg';
            $state = $rows->contains($item['slug']) ? 'update' : 'insert';
            $file = in_array($image, $this->existingImages(), true) ? 'image ok' : 'IMAGE MISSING';
            $this->line(sprintf(
                '  %-26s %-7s Tk %4d  %s',
                $item['slug'],
                $state,
                $item['new'],
                $file
            ));
        }

        return self::SUCCESS;
    }

    /** Remove only the rows this command created. */
    private function wipe(): int
    {
        $ids = $this->demoProductIds()->pluck('id');

        if ($ids->isEmpty()) {
            $this->line('No demo catalogue rows found. Nothing to remove.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            // Variant children first: the old wipe removed product_variants
            // without their variant_stocks / reservations / movements, which is
            // how thousands of orphan rows accumulated. Children must go before
            // the variants themselves.
            $variantIds = DB::table('product_variants')
                ->whereIn('product_id', $ids)
                ->pluck('id');

            if ($variantIds->isNotEmpty()) {
                DB::table('variant_stocks')->whereIn('variant_id', $variantIds)->delete();
                DB::table('inventory_reservations')->whereIn('variant_id', $variantIds)->delete();
                DB::table('inventory_movements')->whereIn('variant_id', $variantIds)->delete();
            }

            DB::table('flash_sale_products')->whereIn('product_id', $ids)->delete();
            DB::table('promo_banners')->whereIn('image', $this->catalogImages())->delete();
            DB::table('flash_sales')->where('title', 'Zevo Fresh Weekly Sale')->delete();
            DB::table('product_variants')->whereIn('product_id', $ids)->delete();
            DB::table('productimages')->whereIn('product_id', $ids)->delete();
            DB::table('products')->whereIn('id', $ids)->delete();
            DB::table('brands')->where('slug', self::BRAND_SLUG)->delete();
            DB::table('categories')->whereIn('slug', $this->categorySlugs())->delete();

            // The pack-size attribute and its values belong to this command,
            // so a wipe restores the attribute tables to their pre-seed state.
            $attr = DB::table('attributes')->where('slug', self::PACK_ATTRIBUTE_SLUG)->first();
            if ($attr) {
                DB::table('attribute_values')->where('attribute_id', $attr->id)->delete();
                DB::table('attributes')->where('id', $attr->id)->delete();
            }
        });

        $this->line(sprintf('Removed %d demo product(s) and the demo brand.', $ids->count()));

        return self::SUCCESS;
    }

    /** Insert or update the catalogue. */
    private function seed(): int
    {
        $now = now();
        $brandId = $this->ensureBrand($now);
        $categoryIds = $this->ensureCategories($now);
        $images = $this->existingImages();

        $missing = [];
        foreach (self::CATALOGUE as $item) {
            if (! in_array('uploads/product/' . $item['slug'] . '.jpg', $images, true)) {
                $missing[] = $item['slug'] . '.jpg';
            }
        }

        if ($missing) {
            $this->error('Image files are missing, run the generator first:');
            foreach ($missing as $file) {
                $this->line('  - ' . $file);
            }
            $this->error('  python3 tools/make_demo_product_images.py --out public/uploads/product');

            return self::FAILURE;
        }

        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use ($now, $brandId, $categoryIds, &$inserted, &$updated) {
            // One shared attribute for the whole grocery range: pack size.
            // Values are real attribute_values rows, so the product page picker
            // and the cart / checkStock matchers have ids to work with.
            $packValues = $this->ensurePackAttribute($now);

            foreach (self::CATALOGUE as $position => $item) {
                $slug = $item['slug'];
                $existing = DB::table('products')->where('slug', $slug)->first();

                $productCode = 'ZEVO-' . str_pad((string) ($position + 1), 4, '0', STR_PAD_LEFT);
                $description = $this->descriptionFor($item);

                $payload = [
                    'name' => $item['name'],
                    'slug' => $slug,
                    'category_id' => $categoryIds[$item['category']],
                    'brand_id' => $brandId,
                    'product_code' => $productCode,
                    'purchase_price' => (int) round($item['new'] * 0.72),
                    'old_price' => $item['old'],
                    'new_price' => $item['new'],
                    'stock' => $item['stock'],
                    'pro_unit' => $item['unit'],
                    'description' => $description,
                    'meta_description' => self::DEMO_TAG . ' | ' . $item['badge'] . ' ' . $item['name'],
                    // A spread of flags so the home page sections that filter
                    // on them all have something to show.
                    'feature_product' => $position % 3 === 0 ? 1 : 0,
                    'topsale' => $position % 4 === 0 ? 1 : 0,
                    'is_recommended' => 1,
                    'recommended_sort_order' => $position,
                    'status' => 1,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    $productId = $existing->id;
                    DB::table('products')->where('id', $productId)->update($payload);
                    $updated++;
                } else {
                    $productId = DB::table('products')->insertGetId($payload + ['created_at' => $now]);
                    $inserted++;
                }

                $this->ensureImage($productId, $slug, $item['name'], $item['badge'], $now);
                $this->ensureVariants($productId, $productCode, $slug, $item, $packValues, $now);
            }

            $this->ensureFlashSale($now);
            $this->ensurePromoBanners($now);
        });

        $this->line(sprintf('Catalogue seeded: %d inserted, %d updated.', $inserted, $updated));
        $this->line(sprintf('Brand: %s (#%d)', self::BRAND_NAME, $brandId));
        $this->line('Categories: ' . implode(', ', array_keys($categoryIds)));
        $this->line(sprintf('Products total in database: %d', DB::table('products')->count()));

        return self::SUCCESS;
    }

    /** Category ids keyed by display name, created on demand. */
    private function ensureCategories($now): array
    {
        $ids = [];

        foreach ($this->categoryMap() as $name => $slug) {
            $existing = DB::table('categories')->where('slug', $slug)->first();

            $ids[$name] = $existing
                ? $existing->id
                : DB::table('categories')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return $ids;
    }

    /**
     * Display name to slug for every demo category.
     *
     * The catalogue refers to categories by their display name, and the name
     * cannot be derived from the slug: "dairy-eggs" would become "Dairy Eggs"
     * instead of the intended "Dairy & Eggs". The mapping is therefore
     * explicit rather than generated.
     *
     * @return array<string, string>
     */
    private function categoryMap(): array
    {
        return [
            'Fresh Vegetables' => 'fresh-vegetables',
            'Fresh Fruits' => 'fresh-fruits',
            'Dairy & Eggs' => 'dairy-eggs',
            'Meat & Seafood' => 'meat-seafood',
            'Bakery & Pantry' => 'bakery-pantry',
            'Pantry Staples' => 'pantry-staples',
            'Rice & Flour' => 'rice-flour',
        ];
    }

    /** @return array<int, string> */
    private function categorySlugs(): array
    {
        return array_values($this->categoryMap());
    }

    private function ensureBrand($now): int
    {
        $existing = DB::table('brands')->where('slug', self::BRAND_SLUG)->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('brands')->insertGetId([
            'name' => self::BRAND_NAME,
            'slug' => self::BRAND_SLUG,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureImage(int $productId, string $slug, string $name, string $badge, $now): void
    {
        $image = 'uploads/product/' . $slug . '.jpg';
        $existing = DB::table('productimages')->where('product_id', $productId)->first();

        if ($existing) {
            DB::table('productimages')->where('id', $existing->id)->update([
                'image' => $image,
                'title' => $name,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('productimages')->insert([
            'product_id' => $productId,
            'image' => $image,
            'title' => $name . ' - ' . $badge,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Create the shared pack-size attribute and its three values on demand.
     *
     * attribute_values are the currency the frontend matches on: checkStock and
     * resolveSelectedVariant compare the value ids stored in each variant's
     * attributes_json against the ids the shopper clicked. Without real rows
     * here, no variant can ever match (the old seeder wrote a flat
     * {"size":"Standard"} that referenced nothing).
     *
     * @return array{id: int, values: array<string, int>} attribute id + value ids keyed by label
     */
    private function ensurePackAttribute($now): array
    {
        $attribute = DB::table('attributes')->where('slug', self::PACK_ATTRIBUTE_SLUG)->first();

        $attributeId = $attribute
            ? (int) $attribute->id
            : DB::table('attributes')->insertGetId([
                'name' => self::PACK_ATTRIBUTE_NAME,
                'slug' => self::PACK_ATTRIBUTE_SLUG,
                'kind' => 'custom',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $valueIds = [];
        foreach (self::PACK_SIZES as $index => $pack) {
            $existing = DB::table('attribute_values')
                ->where('attribute_id', $attributeId)
                ->where('value', $pack['label'])
                ->first();

            $valueIds[$pack['label']] = $existing
                ? (int) $existing->id
                : DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $attributeId,
                    'value' => $pack['label'],
                    'code' => Str::slug($pack['label']),
                    'multiplier' => $pack['multiplier'],
                    'kind' => 'custom',
                    'status' => 1,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return ['id' => $attributeId, 'values' => $valueIds];
    }

    /**
     * Replace a product's variants with the real attribute set:
     *
     *  - DEFAULT  : sku "{code}-DEFAULT", empty attributes. The cart falls back
     *               to exactly this sku when the shopper selects nothing, so
     *               this sku must literally be "{product_code}-DEFAULT".
     *  - V1/V2/V3 : one variant per pack size, attributes_json in the shape the
     *               original site itself used:
     *               {"attrs":{"pack-size":"Pack of 2"},"ids":{"pack-size":"<id>"}}.
     *
     * Each variant gets a variant_stocks row because enforceVariantStockOrFail
     * refuses any variant without one.
     */
    private function ensureVariants(int $productId, string $productCode, string $slug, array $item, array $packAttribute, $now): void
    {
        $image = 'uploads/product/' . $slug . '.jpg';
        $basePrice = (int) $item['new'];
        $stock = (int) $item['stock'];
        $baseWeight = $this->unitWeightGram((string) ($item['unit'] ?? ''));

        // Wholesale replace: the old seeder wrote a flat {"size":"Standard"}
        // variant per product, so simply upserting by sku would leave those
        // dead rows (and their stock rows) behind. Delete children first.
        $oldVariantIds = DB::table('product_variants')
            ->where('product_id', $productId)
            ->pluck('id');

        if ($oldVariantIds->isNotEmpty()) {
            DB::table('variant_stocks')->whereIn('variant_id', $oldVariantIds)->delete();
            DB::table('inventory_reservations')->whereIn('variant_id', $oldVariantIds)->delete();
            DB::table('inventory_movements')->whereIn('variant_id', $oldVariantIds)->delete();
            DB::table('product_variants')->whereIn('id', $oldVariantIds)->delete();
        }

        $variantRows = [];
        $stockRows = [];

        // DEFAULT variant: empty attributes, exact sku the cart falls back to.
        $variantRows[] = [
            'product_id' => $productId,
            'sku' => $productCode . '-DEFAULT',
            'title' => 'Default',
            'image' => $image,
            'purchase_price' => (int) round($basePrice * 0.72),
            'sale_price' => $basePrice,
            'weight_gram' => $baseWeight,
            'status' => 1,
            'attributes_json' => json_encode(['ids' => [], 'attrs' => []]),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $stockRows[] = ['variant_key' => 0, 'available' => $stock, 'on_hand' => $stock];

        // One variant per pack size, with real attribute ids and proportional
        // pricing (exact multiplier, no invented discount) and split stock.
        foreach (self::PACK_SIZES as $index => $pack) {
            $label = $pack['label'];
            $multiplier = $pack['multiplier'];
            $valueId = $packAttribute['values'][$label] ?? null;
            if ($valueId === null) {
                continue;
            }

            $variantRows[] = [
                'product_id' => $productId,
                'sku' => $productCode . $pack['sku_suffix'],
                'title' => $label,
                'image' => $image,
                'purchase_price' => (int) round($basePrice * 0.72 * $multiplier),
                'sale_price' => $basePrice * $multiplier,
                'price_override' => $basePrice * $multiplier,
                'weight_gram' => $baseWeight !== null ? $baseWeight * $multiplier : null,
                'status' => 1,
                'attributes_json' => json_encode([
                    'attrs' => [self::PACK_ATTRIBUTE_SLUG => $label],
                    'ids' => [self::PACK_ATTRIBUTE_SLUG => (string) $valueId],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $packStock = (int) floor($stock / $multiplier);
            $stockRows[] = ['variant_key' => $index + 1, 'available' => $packStock, 'on_hand' => $packStock];
        }

        foreach ($variantRows as $variantIndex => $row) {
            $variantId = DB::table('product_variants')->insertGetId($row);
            $stockRow = $stockRows[$variantIndex] ?? end($stockRows);
            DB::table('variant_stocks')->insert([
                'variant_id' => $variantId,
                'available' => $stockRow['available'],
                'on_hand' => $stockRow['on_hand'],
                'reserved' => 0,
                'damaged' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Parse a unit string into grams where the unit is a weight.
     * "500 g" -> 500, "1 kg" -> 1000, "1 L" ~ 1000, else null.
     */
    private function unitWeightGram(string $unit): ?int
    {
        $unit = trim($unit);
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*(g|kg|ml|l|L)$/', $unit, $m)) {
            return null;
        }

        $value = (float) $m[1];
        $suffix = strtolower($m[2]);

        return match ($suffix) {
            'g' => (int) round($value),
            'kg' => (int) round($value * 1000),
            'ml' => (int) round($value),
            'l' => (int) round($value * 1000),
            default => null,
        };
    }

    /**
     * A demo flash sale window that is actually running.
     *
     * The section guards on `isRunning() && products->count() > 0`. The row
     * already in flash_sales expired in April, and the pivot table was empty,
     * so the home section could never appear. The window runs from yesterday
     * until three days from now: long enough to be visible during review, and
     * it expires on its own rather than needing to be switched off.
     */
    private function ensureFlashSale($now): void
    {
        $existing = DB::table('flash_sales')->orderBy('id')->first();

        $payload = [
            'title' => 'Zevo Fresh Weekly Sale',
            'start_time' => $now->copy()->subDay(),
            'end_time' => $now->copy()->addDays(3),
            'status' => 1,
            'updated_at' => $now,
        ];

        $flashSaleId = $existing
            ? tap(DB::table('flash_sales')->where('id', $existing->id)->update($payload), fn () => $existing->id)
            : DB::table('flash_sales')->insertGetId($payload + ['created_at' => $now]);

        $productIds = DB::table('products')
            ->whereIn('slug', self::FLASH_SALE_SLUGS)
            ->pluck('id', 'slug');

        foreach (self::FLASH_SALE_SLUGS as $position => $slug) {
            if (! $productIds->has($slug)) {
                continue;
            }
            $productId = $productIds->get($slug);
            $pivot = DB::table('flash_sale_products')
                ->where('flash_sale_id', $flashSaleId)
                ->where('product_id', $productId)
                ->first();

            if ($pivot) {
                continue;
            }

            DB::table('flash_sale_products')->insert([
                'flash_sale_id' => $flashSaleId,
                'product_id' => $productId,
                'sort_order' => $position,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Promo banners, so the promo section has something to render. */
    private function ensurePromoBanners($now): void
    {
        $titles = [
            'artisan-sourdough-bread' => 'Baked fresh every morning',
            'desi-ghee' => 'Desi ghee, slow churned',
            'red-rice' => 'Red rice from local farms',
        ];

        foreach (array_values(self::PROMO_SLUGS) as $position => $slug) {
            $exists = DB::table('promo_banners')
                ->where('image', 'uploads/product/' . $slug . '.jpg')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('promo_banners')->insert([
                'title' => $titles[$slug] ?? 'Fresh from Zevo Fresh',
                'image' => 'uploads/product/' . $slug . '.jpg',
                'link' => route('category', 'fresh-vegetables'),
                'sort_order' => $position,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** @return array<int, string> */
    private function catalogImages(): array
    {
        return array_map(fn (array $item) => 'uploads/product/' . $item['slug'] . '.jpg', self::CATALOGUE);
    }

    /** A short factual description. No invented awards or certifications. */
    private function descriptionFor(array $item): string
    {
        return sprintf(
            '<p><strong>%s</strong> is part of the Zevo Fresh demo range, labelled %s and packed as %s. '
            . 'The image on this card was generated for the demo catalogue rather than photographed, '
            . 'so the packaging you receive may differ.</p>'
            . '<p>Sold at Tk %d, reduced from Tk %d. Stock on hand: %d units.</p>'
            . '<p>Delivery is available inside Dhaka and across Bangladesh. Cash on delivery is accepted.</p>',
            e($item['name']),
            e($item['badge']),
            e($item['unit']),
            $item['new'],
            $item['old'],
            $item['stock']
        );
    }

    /**
     * Image paths that really exist on disk.
     *
     * This must read the filesystem, not the productimages table: before the
     * first seed the table is empty, so a database lookup reported every image
     * as missing and refused to write. MediaPath::product resolves the
     * relative "uploads/product/..." form, so that is the shape checked here.
     *
     * @return array<int, string>
     */
    private function existingImages(): array
    {
        $directory = public_path('uploads/product');
        if (! is_dir($directory)) {
            return [];
        }

        $files = array_map('basename', glob($directory . '/*.jpg') ?: []);

        return array_map(fn ($file) => 'uploads/product/' . $file, $files);
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function demoProductIds()
    {
        return DB::table('products')
            ->where('meta_description', 'like', self::DEMO_TAG . '%')
            ->get(['id', 'slug']);
    }
}