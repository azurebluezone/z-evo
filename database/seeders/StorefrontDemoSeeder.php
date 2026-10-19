<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\GeneralSetting;
use App\Models\HomepageCollection;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\PromoBanner;
use App\Models\Service;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * StorefrontDemoSeeder
 * ====================
 * Fills the storefront with everything the redesigned home page and catalog
 * page draw, so neither page can be half empty:
 *
 *      banners            -> the hero slider on /            (home/sections/home/hero)
 *      categories         -> the category tiles on /         (home/sections/home/categories)
 *      brands             -> "Shop by brand" on /             (home/sections/home/brands)
 *      products + images  -> every product rail on /          (home/sections/home/product-rail)
 *      flash_sale         -> the countdown banner on /        (home/sections/home/flash-sale)
 *      collections        -> the classic homepage rails, kept working
 *      services           -> ShopPromises-adjacent service rows
 *      general_settings   -> every "Home Settings" switch turned on, and
 *                            frontend_theme pinned to 'modern'
 *
 * WHY THIS FILE EXISTS ALONGSIDE FreshCartTestDataSeeder
 * ------------------------------------------------------
 * That seeder was written against an older schema and now fails on the first
 * insert: brands.name_bn is NOT NULL with no default, and the products it
 * writes point at product images that do not exist. Rather than edit a file
 * someone may be running, this one seeds what the current schema actually
 * accepts and is written defensively - it asks Schema which columns exist
 * before writing, so it survives the next migration as well.
 *
 * IMAGES
 * ------
 * Real photography already sits in public/uploads/{product,category,brand,
 * banner,events}. This seeder only REFERENCES those files by path; it never
 * writes media. When a file is missing it falls back, in order:
 *
 *      the category's own image  ->  assets/images/default.png
 *
 * so no card, tile or banner is ever left with a broken image.
 *
 * SAFE TO RE-RUN
 * --------------
 * Every write is an upsert keyed on a stable natural key (slug, or the media
 * path), so running it twice updates instead of duplicating. Product images
 * are matched on (product_id, image) for the same reason.
 */
class StorefrontDemoSeeder extends Seeder
{
    /** Uploads root, relative to the public directory. */
    private const MEDIA = 'uploads/';

    /**
     * Photography downloaded from work/code.html, the approved catalog design.
     *
     * These are the exact pictures the reference page shows, so the cards in
     * the shop match the design instead of merely resembling it. They live in
     * their own folder so that switching back to the older photos is a single
     * path change, not a hunt through public/uploads/product.
     */
    private const DESIGN_MEDIA = 'uploads/product/code-design/';

    /**
     * Design photo per product name, mapped onto the seeded catalogue.
     *
     * The reference shows twelve cards; these are the twelve we seed, keyed
     * by the name in work/code.html. A product with no entry keeps whatever
     * photo it already had.
     */
    private const DESIGN_PHOTOS = [
        'Organic Hass Avocados'   => 'organic-hass-avocado.jpg',
        'Crisp Farm Broccoli'     => 'crispy-fresh-broccoli.jpg',
        'Pure Organic Whole Milk' => 'pure-organic-milk.jpg',
        'Artisan Raw Honey'       => 'artisan-raw-honey.jpg',
        'Farm Fresh Tomatoes'     => 'farm-fresh-tomatoes.jpg',
        'Organic Potatoes'        => 'organic-potatoes.jpg',
        'Organic Spinach Bundle'  => 'fresh-spinach-bundle.jpg',
        'Free Range Eggs'         => 'free-range-eggs.jpg',
        'Fresh Carrots'           => 'fresh-carrots.jpg',
        'Red Apple'               => 'red-apple.jpg',
        'Skinless Chicken'        => 'skinless-chicken.jpg',
        'Artisan Sourdough Bread' => 'artisan-sourdough-bread.jpg',
    ];

    /** Last-resort image, present in every install. */
    private const FALLBACK = 'assets/images/default.png';

    /** Web-root relative path to a fallback that certainly exists. */
    private const SAFE_FALLBACK = 'uploads/category/default.png';

    public function run(): void
    {
        $this->command?->info('Seeding storefront demo data...');

        $bannerCategory = $this->seedBannerCategory();
        $brands         = $this->seedBrands();
        $categories     = $this->seedCategories();
        $subcategories  = $this->seedSubcategories($categories);
        $products       = $this->seedProducts($categories, $subcategories, $brands);

        $this->seedBanners($bannerCategory);
        $this->seedPromoBanners();
        $this->seedFlashSale($products);
        $this->seedCollections($categories);
        $this->seedServices();
        $this->configureHomepage();
        $this->applyDesignPhotos();
        $this->refreshPlaceholderImages();
        $this->refreshBranding();

        $this->command?->info(sprintf(
            'Done: %d categories, %d subcategories, %d brands, %d products.',
            count($categories),
            count($subcategories),
            count($brands),
            count($products)
        ));
    }

    // ------------------------------------------------------------------ media

    /**
     * Whether a media path resolves to a file inside public/.
     *
     * Stored values come in three shapes (see App\Support\MediaPath): with the
     * public/ prefix, relative to the web root, or a bare filename. All three
     * are accepted here, and anything remote is treated as usable because it
     * cannot be checked from the filesystem.
     */
    private function mediaExists(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        if (preg_match('~^(https?:)?//~i', $path) === 1) {
            return true;
        }

        foreach ($this->candidatePaths($path) as $candidate) {
            if (is_file(public_path($candidate))) {
                return true;
            }
        }

        return false;
    }

    /** Every filesystem location a stored value could be pointing at. */
    private function candidatePaths(string $path): array
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [$path];

        // "public/uploads/x.jpg" -> "uploads/x.jpg"
        if (str_starts_with($path, 'public/')) {
            $candidates[] = substr($path, strlen('public/'));
        }

        // "uploads/product/x.jpg" -> also try public/uploads/product/x.jpg
        if (! str_starts_with($path, 'assets/')) {
            $candidates[] = 'public/' . $path;
        }

        // A bare filename ("x.jpg") has always meant the product folder.
        if (! str_contains($path, '/')) {
            $candidates[] = self::MEDIA . 'product/' . $path;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Return $path when the file exists, otherwise $fallbackWhenMissing, and
     * finally the site's fallback image.
     *
     * $fallbackWhenMissing lets a product fall back to its category's photo
     * before giving up on a generic grey box.
     */
    private function mediaOr(string $path, ?string $fallbackWhenMissing = null): string
    {
        if ($this->mediaExists($path)) {
            return $path;
        }

        if ($fallbackWhenMissing && $this->mediaExists($fallbackWhenMissing)) {
            return $fallbackWhenMissing;
        }

        return $this->mediaExists(self::SAFE_FALLBACK) ? self::SAFE_FALLBACK : self::FALLBACK;
    }

    /** Build an insert payload containing only the columns the table has. */
    private function columns(string $table, array $values): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $existing = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($values, $existing);
    }

    // ----------------------------------------------------------------- brands

    private function seedBrands(): array
    {
        $rows = [
            ['name' => 'Fresh Farm',    'image' => 'uploads/brand/1731219136-ecofresh-logo.webp'],
            ['name' => 'Organic Valley', 'image' => 'uploads/brand/1731219306-indian.webp'],
            ["name" => "Nature's Best",  'image' => 'uploads/brand/1731219064-hi-pure-logo.webp'],
            ['name' => 'Farm Pure',      'image' => 'uploads/brand/1731219064-hi-pure-logo.webp'],
        ];

        $out = [];

        foreach ($rows as $row) {
            $slug = Str::slug($row['name']);

            $payload = $this->columns('brands', [
                'name'    => $row['name'],
                // name_bn is NOT NULL with no default, so it has to be sent.
                'name_bn' => $row['name'],
                'slug'    => $slug,
                'image'   => $this->mediaOr($row['image'], self::SAFE_FALLBACK),
                'status'  => 1,
            ]);

            $out[$row['name']] = Brand::updateOrCreate(['slug' => $slug], $payload);
        }

        return $out;
    }

    // ------------------------------------------------------------- categories

    private function seedCategories(): array
    {
        $rows = [
            ['name' => 'Fresh Farm Vegetables',   'image' => 'uploads/category/1783497539-img-20260708-wa0000.webp'],
            ['name' => 'Fresh Fruits',            'image' => 'uploads/category/1783531102-img-20260708-wa0016.webp'],
            ['name' => 'Dairy & Eggs',            'image' => 'uploads/category/1783531225-img-20260708-wa0020.webp'],
            ['name' => 'Meat & Seafood',          'image' => 'uploads/category/1783531137-img-20260708-wa0017.webp'],
            ['name' => 'Bakery & Pantry',         'image' => 'uploads/category/1783531270-img-20260708-wa0018.webp'],
            ['name' => 'Wild Raw Honey & Pantry', 'image' => 'uploads/category/1783531253-img-20260708-wa0019.webp'],
            ['name' => 'Grains & Pulses',         'image' => 'uploads/category/1783499227-img-20260708-wa0005.webp'],
        ];

        $out = [];

        foreach ($rows as $index => $row) {
            $slug = Str::slug($row['name']);

            $payload = $this->columns('categories', [
                'name'        => $row['name'],
                'slug'        => $slug,
                // categories.parent_id is NOT NULL; top level uses 0.
                'parent_id'   => 0,
                'image'       => $this->mediaOr($row['image'], self::SAFE_FALLBACK),
                'meta_title'  => $row['name'],
                'meta_decription' => 'Shop ' . $row['name'] . ' at GS Corporation. Fresh, local and delivered fast across Bangladesh.',
                'status'      => 1,
                'home_page'   => 1,
                'is_featured' => 1,
                'featured_sort_order' => $index,
                'front_view'  => 0,
            ]);

            $out[$row['name']] = Category::updateOrCreate(['slug' => $slug], $payload);
        }

        return $out;
    }

    private function seedSubcategories(array $categories): array
    {
        $map = [
            'Fresh Farm Vegetables'   => ['Leafy Greens', 'Root Vegetables', 'Green & Seasonal'],
            'Fresh Fruits'            => ['Tropical Fruits', 'Imported Fruits'],
            'Dairy & Eggs'            => ['Milk & Cream', 'Eggs', 'Ghee & Butter'],
            'Meat & Seafood'          => ['Chicken', 'Mutton & Beef'],
            'Bakery & Pantry'         => ['Breads', 'Artisan Bakery'],
            'Wild Raw Honey & Pantry' => ['Raw Honey', 'Cold Pressed Oils'],
            'Grains & Pulses'         => ['Rice & Grains', 'Pulses'],
        ];

        $out = [];

        foreach ($map as $categoryName => $names) {
            $category = $categories[$categoryName] ?? null;
            if (! $category) {
                continue;
            }

            foreach ($names as $name) {
                $slug = Str::slug($name);

                $out[$name] = Subcategory::updateOrCreate(
                    ['slug' => $slug],
                    $this->columns('subcategories', [
                        'subcategoryName' => $name,
                        'slug'        => $slug,
                        'category_id'  => $category->id,
                        'image'        => $category->image,
                        'meta_title'   => $name,
                        'meta_decription' => $name . ' - shop online at GS Corporation.',
                        'status'       => 1,
                    ])
                );
            }
        }

        return $out;
    }

    // --------------------------------------------------------------- products
    //
    // Every row points at a photo that is already in public/uploads/product.
    // The flags are what feed the four home rails:
    //     feature_product -> Featured    topsale -> Best selling
    //     is_recommended  -> Recommended old > new -> Discount
    // The spread is deliberate: each rail gets a different subset so the home
    // page does not show four identical rows of the same eight products.

    private function seedProducts(array $categories, array $subcategories, array $brands): array
    {
        // name, image, category, subcategory, brand, new, old, unit, flags
        // flags: f = featured, t = topsale, r = recommended, d = discounted
        $rows = [
            ['Organic Hass Avocados',   'organic-hass-avocado.jpg',   'Fresh Fruits',            'Tropical Fruits',   'Fresh Farm',    499,  620,  '500g', 'ftrd'],
            ['Crisp Farm Broccoli',     'crispy-fresh-broccoli.jpg',  'Fresh Farm Vegetables',   'Leafy Greens',      'Organic Valley', 249,  310,  '400g', 'ftrd'],
            ['Pure Organic Whole Milk', 'pure-organic-milk.jpg',      'Dairy & Eggs',            'Milk & Cream',      "Nature's Best",  380,  450,  '1L',   'ftrd'],
            ['Artisan Raw Honey',       'artisan-raw-honey.jpg',      'Wild Raw Honey & Pantry', 'Raw Honey',         'Farm Pure',     890, 1150,  '500g', 'ftrd'],
            ['Farm Fresh Tomatoes',     'farm-fresh-tomatoes.jpg',    'Fresh Farm Vegetables',   'Green & Seasonal',  'Fresh Farm',    180,  240,  '1kg',  'ftrd'],
            ['Organic Potatoes',        'organic-potatoes.jpg',       'Fresh Farm Vegetables',   'Root Vegetables',   'Organic Valley', 210,  260,  '2kg',  'ftrd'],
            ['Organic Spinach Bundle',  'fresh-spinach-bundle.jpg',   'Fresh Farm Vegetables',   'Leafy Greens',      'Fresh Farm',    120,  150,  '500g', 'ftrd'],
            ['Free Range Eggs',         'free-range-eggs.jpg',        'Dairy & Eggs',            'Eggs',              'Farm Pure',     240,  300,  '12 pcs','ftrd'],
            ['Fresh Carrots',           'fresh-carrots.jpg',          'Fresh Farm Vegetables',   'Root Vegetables',   'Fresh Farm',    160,  200,  '1kg',  'ftrd'],
            ['Red Apple',               'red-apple.jpg',              'Fresh Fruits',            'Imported Fruits',   'Farm Pure',     320,  400,  '1kg',  'ftrd'],
            ['Skinless Chicken',        'skinless-chicken.jpg',       'Meat & Seafood',          'Chicken',           'Fresh Farm',    420,  560,  '1kg',  'ftrd'],
            ['Artisan Sourdough Bread', 'artisan-sourdough-bread.jpg','Bakery & Pantry',         'Artisan Bakery',    'Organic Valley', 150,  200,  '400g', 'ftrd'],
            ['Red Rice',                'red-rice.jpg',               'Grains & Pulses',         'Rice & Grains',     "Nature's Best",  260,  320,  '1kg',  'fd'],
            ['Mustard Oil (1L)',        'mustard-oil.jpg',            'Wild Raw Honey & Pantry', 'Cold Pressed Oils', 'Farm Pure',     330,  440,  '1L',   'fd'],
            ['Desi Ghee',               'desi-ghee.jpg',              'Dairy & Eggs',            'Ghee & Butter',     "Nature's Best",  750,  890,  '500g', 'fd'],
            ['Fresh Banana',            'fresh-banana.jpg',           'Fresh Fruits',            'Tropical Fruits',   'Fresh Farm',    140,  175,  '1kg',  'td'],
            ['Roasted Chickpeas',       'roasted-chickpeas.jpg',      'Grains & Pulses',         'Pulses',            'Organic Valley', 190,  240,  '300g', 'td'],
            ['Red Onion',               'red-onion.jpg',              'Fresh Farm Vegetables',   'Green & Seasonal',  'Fresh Farm',    130,  160,  '1kg',  'td'],
            ['Green Chili',             'green-chili.jpg',            'Fresh Farm Vegetables',   'Green & Seasonal',  'Fresh Farm',     85,  110,  '500g', 'td'],
            ['Fresh Creamer Pack',      'fresh-creamer-pack.jpg',     'Dairy & Eggs',            'Milk & Cream',      'Farm Pure',     260,  320,  '12 pcs','td'],
        ];

        $out = [];

        foreach ($rows as $index => $row) {
            [$name, $image, $categoryName, $subcategoryName, $brandName, $new, $old, $unit, $flags] = $row;

            $slug      = Str::slug($name);
            $category  = $categories[$categoryName] ?? null;
            $brand     = $brands[$brandName] ?? null;

            if (! $category) {
                $this->command?->warn("  skipped '$name': category '$categoryName' missing");
                continue;
            }

            $payload = $this->columns('products', [
                'name'          => $name,
                'slug'          => $slug,
                'category_id'   => $category->id,
                'subcategory_id'=> $subcategories[$subcategoryName]->id ?? null,
                'childcategory_id' => null,
                'brand_id'      => $brand?->id,
                'product_code'  => 'GS-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'purchase_price'=> (int) round($new * 0.72),
                'old_price'     => $old,
                'new_price'     => $new,
                'stock'         => 25 + ($index * 3),
                'pro_unit'      => $unit,
                'meta_description' => $name . ' - buy online at GS Corporation.',
                'description'   => $name . ' sourced fresh and delivered to your door across Bangladesh.',
                'topsale'       => str_contains($flags, 't') ? 1 : 0,
                'feature_product' => str_contains($flags, 'f') ? 1 : 0,
                'is_recommended'=> str_contains($flags, 'r') ? 1 : 0,
                // recommended_sort_order is NOT NULL with no default in this
                // schema, so an un-recommended product still needs a value.
                // 0 sorts it last; the rail itself filters on is_recommended.
                'recommended_sort_order' => str_contains($flags, 'r') ? $index : 0,
                'campaign_id'   => null,
                'status'        => 1,
            ]);

            $product = Product::updateOrCreate(['slug' => $slug], $payload);

            // The card reads Product->image, a hasOne relation, so exactly ONE
            // row may exist per product - a second row is silently ignored
            // and the card keeps showing whichever row sorts first. Delete
            // first, then write, so a re-run cannot accumulate rows.
            $imagePath = $this->mediaOr(self::MEDIA . 'product/' . $image, $category->image);

            DB::table('productimages')->where('product_id', $product->id)->delete();

            DB::table('productimages')->insert(
                $this->columns('productimages', [
                    'image'      => $imagePath,
                    'title'      => $name,
                    'product_id' => $product->id,
                ]) + ['created_at' => now(), 'updated_at' => now()]
            );

            $out[$name] = $product;
        }

        return $out;
    }

    // ---------------------------------------------------------------- banners

    private function seedBannerCategory(): ?BannerCategory
    {
        if (! Schema::hasTable('banner_categories')) {
            return null;
        }

        return BannerCategory::firstOrCreate(
            ['name' => 'Homepage'],
            $this->columns('banner_categories', ['name' => 'Homepage', 'status' => 1])
        );
    }

    /**
     * The hero slider. The home page takes Banners where category_id = 1
     * (see FrontendController::frontendHome), so the row has to land on that
     * id - falling back to the row this seeder just created.
     */
    private function seedBanners(?BannerCategory $bannerCategory): void
    {
        $categoryId = $bannerCategory?->id ?? 1;

        $rows = [
            [
                'title' => 'Fresh Picks of the Day',
                'image' => 'uploads/banner/1731189481-banner-1.webp',
                'link'  => '/products',
            ],
            [
                'title' => 'Organic Harvest Festival',
                'image' => 'uploads/banner/1731189650-banner-2.webp',
                'link'  => '/products?type=discount',
            ],
            [
                'title' => 'Farm Direct Vegetables',
                'image' => 'uploads/banner/1731189770-banner-3.webp',
                'link'  => '/category/fresh-farm-vegetables',
            ],
        ];

        foreach ($rows as $row) {
            $image = $this->mediaOr($row['image'], self::SAFE_FALLBACK);

            Banner::updateOrCreate(
                ['image' => $image, 'title' => $row['title']],
                $this->columns('banners', [
                    'category_id' => $categoryId,
                    'link'        => $row['link'],
                    'image'       => $image,
                    'video'       => null,
                    'title'       => $row['title'],
                    'media_type'  => 'image',
                    'status'      => 1,
                ])
            );
        }
    }

    private function seedPromoBanners(): void
    {
        if (! Schema::hasTable('promo_banners')) {
            return;
        }

        $rows = [
            ['title' => 'Weekend Mega Sale', 'image' => 'uploads/banner/1731189481-banner-1.webp', 'link' => '/products?type=discount'],
            ['title' => 'New Season Arrivals', 'image' => 'uploads/banner/1731189650-banner-2.webp', 'link' => '/products'],
        ];

        foreach ($rows as $index => $row) {
            $image = $this->mediaOr($row['image'], self::SAFE_FALLBACK);

            PromoBanner::updateOrCreate(
                ['title' => $row['title']],
                $this->columns('promo_banners', [
                    'title'      => $row['title'],
                    'image'      => $image,
                    'link'       => $row['link'],
                    'sort_order' => $index,
                    'status'     => 1,
                ])
            );
        }
    }

    // ------------------------------------------------------------- flash sale

    /**
     * A sale that is running RIGHT NOW, so the home page countdown has a real
     * target to tick down to instead of hiding the whole section.
     */
    private function seedFlashSale(array $products): void
    {
        if (! Schema::hasTable('flash_sales')) {
            return;
        }

        $sale = FlashSale::updateOrCreate(
            ['title' => 'Flash Sale - 60% Off'],
            $this->columns('flash_sales', [
                'title'        => 'Flash Sale - 60% Off',
                'start_time'   => Carbon::now()->subHour(),
                'end_time'     => Carbon::now()->addDays(2),
                'banner_image' => $this->mediaOr('uploads/banner/1731189481-banner-1.webp', self::SAFE_FALLBACK),
                'banner_link'  => '/products?type=discount',
                'status'       => 1,
            ])
        );

        if (! Schema::hasTable('flash_sale_products')) {
            return;
        }

        // The four most discounted products, which is what the design shows.
        $deals = array_slice(array_values($products), 0, 4);

        foreach ($deals as $order => $product) {
            DB::table('flash_sale_products')->updateOrInsert(
                ['flash_sale_id' => $sale->id, 'product_id' => $product->id],
                ['sort_order' => $order]
            );
        }
    }

    private function seedCollections(array $categories): void
    {
        if (! Schema::hasTable('homepage_collections')) {
            return;
        }

        $rows = [
            ['title' => 'Fresh Vegetables', 'category' => 'Fresh Farm Vegetables'],
            ['title' => 'Daily Fruits',      'category' => 'Fresh Fruits'],
            ['title' => 'Dairy & Eggs',      'category' => 'Dairy & Eggs'],
        ];

        foreach ($rows as $index => $row) {
            $category = $categories[$row['category']] ?? null;

            HomepageCollection::updateOrCreate(
                ['title' => $row['title']],
                $this->columns('homepage_collections', [
                    'title'         => $row['title'],
                    'category_id'   => $category?->id,
                    'banner_image'  => $category?->image,
                    'product_limit' => 12,
                    'sort_order'    => $index,
                    'status'        => 1,
                ])
            );
        }
    }

    private function seedServices(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        $rows = [
            ['title' => 'Fast delivery',   'subtitle' => 'Inside Dhaka in 24 hours, nationwide in 2-3 days'],
            ['title' => 'Secure payment',  'subtitle' => 'Cash on delivery, cards and mobile banking'],
            ['title' => 'Easy returns',    'subtitle' => 'Return any product within 7 days'],
            ['title' => 'Support',         'subtitle' => 'Every day from 9 am to 9 pm'],
        ];

        foreach ($rows as $index => $row) {
            Service::updateOrCreate(
                ['title' => $row['title']],
                $this->columns('services', [
                    'title'      => $row['title'],
                    'subtitle'   => $row['subtitle'],
                    // services.image is NOT NULL; the service strip never
                    // renders it, so the site's fallback keeps the row valid.
                    'image'      => $this->mediaOr('', self::SAFE_FALLBACK),
                    'sort_order' => $index,
                    'status'     => 1,
                ])
            );
        }
    }

    // ------------------------------------------------------- homepage settings

    /**
     * Turn on every "Home Settings" switch and pin the modern template.
     *
     * frontend_theme is what decides whether / renders the redesigned page at
     * all; leaving it on 'classic' means the new sections are never reached.
     */
    private function configureHomepage(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        $row = GeneralSetting::where('status', 1)->orderBy('id')->first()
            ?? GeneralSetting::orderBy('id')->first();

        if (! $row) {
            return;
        }

        $values = [
            'frontend_theme' => 'modern',

            'home_page_top_categories_section' => 1,
            'home_page_brands_section'         => 1,
            'home_hero_section'                => 1,
            'home_recommended_section'          => 1,
            'home_category_section'            => 1,
            'home_promo_section'               => 1,
            'home_featured_section'            => 1,
            'home_collections_section'         => 1,
            'home_brands_section'              => 1,
            'home_service_section'             => 1,
            'home_best_selling_section'        => 1,
            'home_discount_products_section'   => 1,
            'home_flash_sale_section'          => 1,
            'home_footer_section'              => 1,
        ];

        $titles = [
            'home_hero_title'               => 'Fresh Food, Healthy Life',
            'home_category_title'           => 'Popular categories',
            'home_recommended_title'        => 'Recommended',
            'home_featured_title'           => 'Featured Products',
            'home_best_selling_title'       => 'Best Selling Products',
            'home_discount_products_title'  => 'Discount Products',
            'home_brands_title'             => 'Shop by brand',
            'home_collections_title'        => 'Collections',
            'home_flash_sale_title'         => 'FLASH SALE',
        ];

        foreach ($titles as $key => $title) {
            $values[$key] = $title;
        }

        // Only write the columns this schema actually has.
        $row->forceFill($this->columns('general_settings', $values))->save();
    }

    // -------------------------------------------------------- design photography

    /**
     * Point the twelve catalog products at the photos taken from
     * work/code.html, the approved design.
     *
     * The catalogue was first seeded with the older public/uploads/product
     * shots. Those are fine, but they are not the pictures the design shows,
     * so the reference images are downloaded into their own folder and set
     * here instead - that is what makes the rendered card match code.html.
     *
     * Applied as a separate pass rather than inside seedProducts() so that
     * running this seeder later also upgrades products that already existed.
     * Only the image is changed; name, price, flags and stock are untouched.
     */
    private function applyDesignPhotos(): void
    {
        if (! Schema::hasTable('productimages') || ! Schema::hasTable('products')) {
            return;
        }

        $applied = 0;

        foreach (self::DESIGN_PHOTOS as $name => $file) {
            $path = self::DESIGN_MEDIA . $file;

            // Nothing to do when the download has not happened (a fresh clone
            // without the folder) - the existing photo stays.
            if (! $this->mediaExists($path)) {
                continue;
            }

            $productId = DB::table('products')->where('slug', Str::slug($name))->value('id');

            if (! $productId) {
                continue;
            }

            /*
             * ONE row per product, and it must be the design photo.
             *
             * Product::image() is a hasOne relation, so the card shows
             * whichever row the database hands back first - adding a second
             * row leaves the ORIGINAL photo winning, because its id is lower.
             * That is why the design pictures were invisible on the page even
             * though they were in the table. So the older rows for this
             * product are removed first and the design photo is written as
             * the single row, which is what hasOne expects anyway.
             */
            DB::table('productimages')->where('product_id', $productId)->delete();

            DB::table('productimages')->insert([
                'product_id' => $productId,
                'image'      => $path,
                'title'      => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $applied++;
        }

        if ($applied > 0) {
            $this->command?->info("Applied {$applied} design photo(s) from work/code.html.");
        }
    }

    // -------------------------------------------------------- placeholder art

    /**
     * Replace leftover grey-box artwork on products this seeder did not create.
     *
     * A shop that already had products before this seeder ran still shows
     * assets/images/default.png on every card, which is what makes a freshly
     * seeded home page look empty. Those rows are real products with real
     * names, so rather than deleting anything, each one is given the photo of
     * a same-category product from this catalogue - the picture is then
     * accurate for the category, and nothing is invented.
     *
     * Only rows whose image is missing or is the fallback are touched; a
     * product with genuine artwork is never overwritten.
     */
    private function refreshPlaceholderImages(): void
    {
        if (! Schema::hasTable('productimages')) {
            return;
        }

        $stale = DB::table('productimages')
            ->join('products', 'products.id', '=', 'productimages.product_id')
            ->where(function ($query) {
                $query->whereNull('productimages.image')
                    ->orWhere('productimages.image', self::FALLBACK);
            })
            ->pluck('productimages.id')
            ->all();

        if ($stale === []) {
            return;
        }

        // One reference photo per category, taken from the rows just seeded.
        $reference = DB::table('productimages')
            ->join('products', 'products.id', '=', 'productimages.product_id')
            ->where('products.status', 1)
            ->where('productimages.image', 'like', self::MEDIA . 'product/%')
            ->select('products.category_id', 'productimages.image')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->first()->image);

        $changed = 0;

        foreach ($stale as $imageRowId) {
            $categoryId = DB::table('productimages')
                ->join('products', 'products.id', '=', 'productimages.product_id')
                ->where('productimages.id', $imageRowId)
                ->value('products.category_id');

            $replacement = $reference[$categoryId] ?? self::SAFE_FALLBACK;

            // Update in place rather than inserting: the card's hasOne
            // relation reads exactly one row, so adding a second one would
            // leave the placeholder still on screen.
            DB::table('productimages')
                ->where('id', $imageRowId)
                ->update(['image' => $replacement]);

            $changed++;
        }

        if ($changed > 0) {
            $this->command?->info("Replaced {$changed} placeholder product image(s).");
        }
    }

    // --------------------------------------------------------------- branding

    /**
     * Point the logo and favicon at files that actually exist.
     *
     * The setting can hold a path to a file that has since been deleted
     * (uploads/settings/default-logo.webp is the usual casualty). The header
     * then renders a broken image on EVERY page, which is the first thing a
     * visitor sees. Only values whose file is missing are replaced, and only
     * with an existing file from public/uploads/settings - the shop keeps its
     * own branding rather than being given a generic one.
     */
    private function refreshBranding(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        $row = GeneralSetting::where('status', 1)->orderBy('id')->first()
            ?? GeneralSetting::orderBy('id')->first();

        if (! $row) {
            return;
        }

        $candidates = glob(public_path(self::MEDIA . 'settings/*')) ?: [];

        $pick = function (array $needles) use ($candidates): ?string {
            foreach ($needles as $needle) {
                foreach ($candidates as $file) {
                    if (stripos(basename($file), $needle) !== false) {
                        return self::MEDIA . 'settings/' . basename($file);
                    }
                }
            }

            return null;
        };

        $logo   = $pick(['logo', 'ador', 'zevo']);
        $favicon = $pick(['favicon', 'faveicon']);

        // The favicon is repaired independently of the logo: fixing one does
        // not mean the other is fine, and a broken favicon is invisible on the
        // page but still a 404 in the network panel.
        $updates = [];

        foreach (['dark_logo', 'white_logo'] as $key) {
            if ($this->mediaExists($row->{$key})) {
                continue;
            }

            if ($logo) {
                $updates[$key] = $logo;
            }
        }

        if (! $this->mediaExists($row->favicon) && $favicon) {
            $updates['favicon'] = $favicon;
        }

        if ($updates !== []) {
            $row->forceFill($this->columns('general_settings', $updates))->save();
            $this->command?->info('Fixed branding: ' . implode(', ', array_keys($updates)) . '.');
        }
    }
}