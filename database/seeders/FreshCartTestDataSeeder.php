<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\HomepageCollection;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\PromoBanner;
use App\Models\Service;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FreshCartTestDataSeeder
 * ----------------------
 * Seeds the storefront with the demo catalogue that matches the approved
 * reference design (work/4db2d5a5-e6dd-48bc-affb-b79b5dfffe5f.png).
 *
 * Design notes
 *  - Product photography already exists in public/uploads/product as 1000x1000
 *    branded files. This seeder only links them, it never writes media.
 *  - Category / brand / banner / promo images are existing files in
 *    public/uploads/... They are referenced by path, so no upload step runs.
 *  - Flags are set on purpose so every homepage rail has something to render:
 *      feature_product      -> Featured Products
 *      topsale              -> Best Selling Products
 *      old_price > new_price-> Discount Products
 *      is_recommended       -> Recommended Products
 *      status = 1           -> visible on the storefront
 *
 * Safe to re-run: every write is an upsert keyed on a stable natural key
 * (slug / image filename), so running it twice does not duplicate rows.
 */
class FreshCartTestDataSeeder extends Seeder
{
    /** Uploads root, relative to the public directory. */
    private const MEDIA = 'uploads/';

    public function run(): void
    {
        $this->command?->info('Seeding FreshCart demo catalogue...');

        $brands = $this->seedBrands();
        $categories = $this->seedCategories();
        $subcategories = $this->seedSubcategories($categories);
        $products = $this->seedProducts($categories, $subcategories, $brands);

        $this->seedBanners();
        $this->seedPromoBanners();
        $this->seedFlashSale($products);
        $this->seedCollections($categories);
        $this->seedServices();

        $this->command?->info('Done: ' . count($categories) . ' categories, '
            . count($products) . ' products.');
    }

    // ------------------------------------------------------------------ brands
    private function seedBrands(): array
    {
        $rows = [
            ['name' => 'Fresh Farm',   'image' => 'uploads/brand/1731219136-ecofresh-logo.webp'],
            ['name' => 'Organic Valley','image' => 'uploads/brand/1731219306-indian.webp'],
            ['name' => "Nature's Best",'image' => 'uploads/brand/1731219064-hi-pure-logo.webp'],
        ];

        $out = [];
        foreach ($rows as $row) {
            if (! $this->mediaExists($row['image'])) {
                $row['image'] = 'uploads/category/default.png';
            }
            $out[$row['name']] = Brand::updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                $row + ['status' => 1]
            );
        }

        return $out;
    }

    // -------------------------------------------------------------- categories
    private function seedCategories(): array
    {
        $rows = [
            ['name' => 'Fresh Farm Vegetables',  'image' => 'uploads/category/1783497539-img-20260708-wa0000.webp'],
            ['name' => 'Fresh Fruits',           'image' => 'uploads/category/1783531102-img-20260708-wa0016.webp'],
            ['name' => 'Dairy & Eggs',           'image' => 'uploads/category/1783531225-img-20260708-wa0020.webp'],
            ['name' => 'Meat & Seafood',         'image' => 'uploads/category/1783531137-img-20260708-wa0017.webp'],
            ['name' => 'Bakery & Pantry',        'image' => 'uploads/category/1783531270-img-20260708-wa0018.webp'],
            ['name' => 'Wild Raw Honey & Pantry','image' => 'uploads/category/1783531253-img-20260708-wa0019.webp'],
            ['name' => 'Grains & Pulses',        'image' => 'uploads/category/1783499227-img-20260708-wa0005.webp'],
        ];

        $out = [];
        foreach ($rows as $index => $row) {
            if (! $this->mediaExists($row['image'])) {
                $row['image'] = 'uploads/category/default.png';
            }
            $out[$row['name']] = Category::updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                $row + [
                    // categories.parent_id is NOT NULL; top level uses 0.
                    'parent_id'  => 0,
                    'front_view' => 0,
                    'status'     => 1,
                    // These drive the homepage category rail, so they are all
                    // switched on and given a stable display order.
                    'home_page'  => 1,
                    'is_featured' => 1,
                    'featured_sort_order' => $index,
                    'meta_title' => $row['name'],
                    'meta_description' => 'Shop ' . $row['name'] . ' at FreshCart BD. Fresh, local and delivered fast across Bangladesh.',
                ]
            );
        }

        return $out;
    }

    // ------------------------------------------------------------ subcategories
    private function seedSubcategories(array $categories): array
    {
        $map = [
            'Fresh Farm Vegetables' => ['Leafy Greens', 'Root Vegetables', 'Green & Seasonal'],
            'Fresh Fruits'          => ['Tropical Fruits', 'Imported Fruits'],
            'Dairy & Eggs'          => ['Milk & Cream', 'Eggs', 'Ghee & Butter'],
            'Meat & Seafood'        => ['Chicken', 'Mutton & Beef'],
            'Bakery & Pantry'       => ['Breads', 'Artisan Bakery'],
            'Wild Raw Honey & Pantry' => ['Raw Honey', 'Cold Pressed Oils'],
            'Grains & Pulses'       => ['Rice & Grains', 'Pulses'],
        ];

        $out = [];
        foreach ($map as $categoryName => $names) {
            $category = $categories[$categoryName] ?? null;
            if (! $category) {
                continue;
            }
            foreach ($names as $name) {
                $out["{$categoryName}::{$name}"] = Subcategory::updateOrCreate(
                    [
                        'slug'        => Str::slug($name),
                        'category_id' => $category->id,
                    ],
                    [
                        'subcategoryName' => $name,
                        'image'           => 'uploads/category/default.png',
                        'status'          => 1,
                        'meta_title'      => $name,
                        'meta_description'=> $name . ' under ' . $categoryName . '.',
                    ]
                );
            }
        }

        return $out;
    }

    // ----------------------------------------------------------------- products
    private function seedProducts(array $categories, array $subcategories, array $brands): array
    {
        // name => [file, category, subcategory, brand, new, old, unit, featured, topsale, recommended, sort]
        $rows = [
            ['Organic Hass Avocados', 'organic-hass-avocado.jpg', 'Fresh Farm Vegetables', null,       'Organic Valley',  499, 620, '1 kg',   1, 1, 1,  1],
            ['Crisp Farm Broccoli',    'crispy-fresh-broccoli.jpg','Fresh Farm Vegetables', 'Green & Seasonal','Fresh Farm',      249, 310, '500 g',  1, 1, 1,  2],
            ['Pure Organic Whole Milk','pure-organic-milk.jpg',    'Dairy & Eggs',           'Milk & Cream',  'Organic Valley',  420, 560, '1 litre',1, 1, 1,  3],
            ['Artisan Raw Honey',      'artisan-raw-honey.jpg',    'Wild Raw Honey & Pantry','Raw Honey',    "Nature's Best",   680, 850, '500 g',  1, 1, 1,  4],
            ['Farm Fresh Tomatoes',    'farm-fresh-tomatoes.jpg', 'Fresh Farm Vegetables', 'Green & Seasonal','Fresh Farm',      120, 150, '1 kg',   1, 1, 1,  5],
            ['Organic Potatoes',       'organic-potatoes.jpg',     'Fresh Farm Vegetables', 'Root Vegetables','Organic Valley',   90, 120, '1 kg',   1, 0, 1,  6],
            ['Organic Spinach',        'fresh-spinach-bundle.jpg', 'Fresh Farm Vegetables', 'Leafy Greens',  'Fresh Farm',      180, 230, '1 bunch',1, 1, 1,  7],
            ['Farm Fresh Eggs',        'free-range-eggs.jpg',      'Dairy & Eggs',           'Eggs',         'Fresh Farm',      350, 460, '12 pcs', 1, 1, 1,  8],
            ['Fresh Carrots',          'fresh-carrots.jpg',        'Fresh Farm Vegetables', 'Root Vegetables','Fresh Farm',      110, 150, '1 kg',   1, 0, 1,  9],
            ['Red Apple',              'red-apple.jpg',            'Fresh Fruits',           'Imported Fruits','Organic Valley', 220, 280, '4 pcs',  1, 1, 1, 10],
            ['Skinless Chicken',       'skinless-chicken.jpg',     'Meat & Seafood',         'Chicken',      'Fresh Farm',      320, 380, '1 kg',   1, 1, 1, 11],
            ['Artisan Sourdough Bread','artisan-sourdough-bread.jpg','Bakery & Pantry',      'Artisan Bakery',"Nature's Best",   160, 200, '400 g',  1, 1, 1, 12],

            ['Fresh Bananas',          'fresh-banana.jpg',         'Fresh Fruits',           'Tropical Fruits','Fresh Farm',      95, null, '1 dozen',0, 1, 1, 13],
            ['Desi Ghee',              'desi-ghee.jpg',            'Dairy & Eggs',           'Ghee & Butter', 'Fresh Farm',      540, 690, '500 g',  1, 1, 1, 14],
            ['Fresh Creamer Pack',     'fresh-creamer-pack.jpg',   'Dairy & Eggs',           'Milk & Cream',  'Fresh Farm',      210, 260, '200 ml', 0, 1, 0,  0],
            ['Green Chilli',           'green-chili.jpg',          'Fresh Farm Vegetables', 'Green & Seasonal','Fresh Farm',      45, null, '500 g',  0, 1, 1, 15],
            ['Cold Pressed Mustard Oil','mustard-oil.jpg',         'Wild Raw Honey & Pantry','Cold Pressed Oils',"Nature's Best",  380, 470, '1 litre',1, 0, 1, 16],
            ['Red Onions',             'red-onion.jpg',            'Fresh Farm Vegetables', 'Root Vegetables','Fresh Farm',      70, 95,  '1 kg',   0, 1, 0,  0],
            ['Red Rice',               'red-rice.jpg',             'Grains & Pulses',        'Rice & Grains','Organic Valley',  260, 330, '2 kg',   1, 1, 1, 17],
            ['Roasted Chickpeas',      'roasted-chickpeas.jpg',    'Grains & Pulses',        'Pulses',       "Nature's Best",   130, 165, '300 g',  0, 1, 1, 18],
        ];

        $products = [];

        DB::transaction(function () use ($rows, $categories, $subcategories, $brands, &$products) {
            foreach ($rows as [$name, $file, $categoryName, $subName, $brandName, $new, $old, $unit, $featured, $topsale, $recommended, $sort]) {
                $category = $categories[$categoryName] ?? null;
                if (! $category) {
                    $this->command?->warn("  ! missing category {$categoryName}, skipped {$name}");
                    continue;
                }

                $subcategory = $subName ? ($subcategories["{$categoryName}::{$subName}"] ?? null) : null;
                $image = self::MEDIA . 'product/' . $file;

                // Skip rather than create a row whose photo is missing, otherwise
                // the card would ship a broken image in the demo data.
                if (! $this->mediaExists($image)) {
                    $this->command?->warn("  ! missing image {$image}, skipped {$name}");
                    continue;
                }

                $slug = Str::slug($name);

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name'         => $name,
                        'category_id'  => $category->id,
                        'subcategory_id' => $subcategory?->id,
                        'brand_id'     => $brands[$brandName]->id ?? null,
                        'product_code' => 'FC-' . str_pad((string) (count($products) + 1), 4, '0', STR_PAD_LEFT),
                        'purchase_price' => (int) round($new * 0.78),
                        'old_price'    => $old,
                        'new_price'    => $new,
                        'stock'        => random_int(15, 120),
                        'pro_unit'     => $unit,
                        'description'  => $this->descriptionFor($name, $unit),
                        'meta_description' => $name . ' - fresh ' . strtolower($unit) . ' pack, delivered across Bangladesh by FreshCart BD.',
                        'topsale'      => $topsale,
                        'feature_product' => $featured,
                        'is_recommended'   => $recommended,
                        'recommended_sort_order' => $sort,
                        'status'       => 1,
                    ]
                );

                // One primary image per product; the card renders $product->image.
                Productimage::updateOrCreate(
                    ['product_id' => $product->id, 'image' => $image],
                    ['title' => $name]
                );

                $products[$name] = $product;
            }
        });

        return $products;
    }

    // ----------------------------------------------------------------- banners
    private function seedBanners(): void
    {
        $heroCategory = BannerCategory::updateOrCreate(
            ['name' => 'Hero Slider'],
            ['status' => 1]
        );

        // frontendHome() reads banners with category_id = 1 for the hero slider.
        $banners = [
            ['image' => 'uploads/banner/1728722342Slider-new-1.jpg', 'title' => 'Fresh Harvest Week'],
            ['image' => 'uploads/banner/1728722920Slider-new-2.jpg', 'title' => 'Organic Picks of the Day'],
            ['image' => 'uploads/banner/1725962065Adora (1).jpg',    'title' => 'Dairy & Eggs Essentials'],
        ];

        foreach ($banners as $index => $banner) {
            if (! $this->mediaExists($banner['image'])) {
                continue;
            }
            Banner::updateOrCreate(
                ['image' => $banner['image'], 'category_id' => 1],
                [
                    'title'      => $banner['title'],
                    'media_type' => 'image',
                    'link'       => route('products.page'),
                    'status'     => 1,
                ]
            );
        }
    }

    // ----------------------------------------------------------- promo banners
    private function seedPromoBanners(): void
    {
        $rows = [
            ['title' => 'Free delivery over ৳1000', 'image' => 'uploads/promo-banner/1777164827-5.webp'],
            ['title' => 'Harvest sale - up to 25% off', 'image' => 'uploads/promo-banner/1772728857-geminigeneratedimagewsbycvwsbycvwsby1.webp'],
            ['title' => 'Fresh every morning', 'image' => 'uploads/promo-banner/1772610564-z2.webp'],
        ];

        foreach ($rows as $index => $row) {
            if (! $this->mediaExists($row['image'])) {
                continue;
            }
            PromoBanner::updateOrCreate(
                ['title' => $row['title']],
                [
                    'image'      => $row['image'],
                    'link'       => route('products.page'),
                    'sort_order' => $index,
                    'status'     => 1,
                ]
            );
        }
    }

    // --------------------------------------------------------------- flash sale
    private function seedFlashSale(array $products): void
    {
        $flashSale = FlashSale::updateOrCreate(
            ['title' => 'FreshCart Flash Sale'],
            [
                // Running now, so the homepage slider and its countdown render.
                'start_time'   => Carbon::now()->subHour(),
                'end_time'     => Carbon::now()->addDays(3),
                'banner_image' => 'uploads/banner/1701006127banner-1.webp',
                'banner_link'  => route('products.page'),
                'status'       => 1,
            ]
        );

        $picks = array_slice(array_values($products), 0, 6);
        foreach ($picks as $index => $product) {
            $flashSale->products()->syncWithoutDetaching([
                $product->id => ['sort_order' => $index],
            ]);
        }
    }

    // -------------------------------------------------------------- collections
    private function seedCollections(array $categories): void
    {
        $rows = [
            ['title' => 'Farm Fresh Veggies', 'category' => 'Fresh Farm Vegetables'],
            ['title' => 'Dairy & Eggs Box',   'category' => 'Dairy & Eggs'],
            ['title' => 'Bakery Favourites', 'category' => 'Bakery & Pantry'],
        ];

        foreach ($rows as $index => $row) {
            $category = $categories[$row['category']] ?? null;
            if (! $category) {
                continue;
            }
            HomepageCollection::updateOrCreate(
                ['title' => $row['title']],
                [
                    'category_id'  => $category->id,
                    'banner_image' => 'uploads/category/default.png',
                    'product_limit' => 8,
                    'sort_order'   => $index,
                    'status'       => 1,
                ]
            );
        }
    }

    // ----------------------------------------------------------------- services
    private function seedServices(): void
    {
        // Icons come from the template's own service artwork so the strip keeps
        // its intended 48x40 proportions. services.image is NOT NULL.
        $rows = [
            ['title' => 'Home Delivery',  'subtitle' => 'Fresh food at your door in 24 hours', 'image' => 'assets/images/frontend/services/6739fcbe2422c1731853502.png'],
            ['title' => 'Fresh & Safe',   'subtitle' => 'Sourced daily, quality checked',       'image' => 'assets/images/frontend/services/6739f654a40241731851860.png'],
            ['title' => 'Secure Payment', 'subtitle' => 'Cash on delivery and mobile banking', 'image' => 'assets/images/frontend/services/6739fca46ea611731853476.png'],
            ['title' => 'Easy Return',    'subtitle' => '7 day replacement on perishables',    'image' => 'assets/images/frontend/services/6739f65c524471731851868.png'],
        ];

        foreach ($rows as $index => $row) {
            if (! $this->mediaExists($row['image'])) {
                $this->command?->warn("  ! missing service icon {$row['image']}, skipped {$row['title']}");
                continue;
            }
            Service::updateOrCreate(
                ['title' => $row['title']],
                $row + [
                    'sort_order' => $index,
                    'status'     => 1,
                ]
            );
        }
    }

    // ------------------------------------------------------------------ helpers
    private function descriptionFor(string $name, string $unit): string
    {
        return sprintf(
            '%s (%s). Carefully selected and quality checked before dispatch, then packed to stay fresh on the way to your kitchen. Store in a cool, dry place and use within %d days of delivery.',
            $name,
            $unit,
            random_int(3, 7)
        );
    }

    private function mediaExists(string $relative): bool
    {
        return is_file(public_path($relative));
    }
}