<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Support\FrontendTheme;
use App\Support\SeedAssets;
use Database\Seeders\DevCatalogSeeder;
use Database\Seeders\SeedAssetSeeder;
use Database\Seeders\ZevoBrandSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Acceptance test for the Z EVO catalog completion.
 *
 * It seeds the committed bundle inside a transaction, then checks the four
 * promises the task made:
 *
 *   1. every category stores a real image that exists on disk;
 *   2. every product has four gallery images and three variants whose images
 *      and prices are distinct, each backed by a stock row and ledger entry;
 *   3. the storefront carries the Z EVO name, logo, favicon, colours and the
 *      one-image + nine-video hero set;
 *   4. the classic and FM product pages render the same four gallery images and
 *      three variant controls, and the variant endpoints return the image and
 *      price that belong to the chosen variant.
 *
 * DatabaseTransactions, NOT RefreshDatabase: this project has no separate
 * testing database and RefreshDatabase would wipe the live `z_evo` data.
 */
class ZevoCatalogCompletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_zevo_catalog_is_complete(): void
    {
        $this->seed([
            SeedAssetSeeder::class,
            DevCatalogSeeder::class,
            ZevoBrandSeeder::class,
        ]);

        $catalog = SeedAssets::catalog();

        $this->assertCount(30, $catalog['products']);
        $this->assertCount(12, $catalog['categories']);

        // ---------------------------------------------------------- categories
        foreach ($catalog['categories'] as $category) {
            $row = DB::table('categories')->where('slug', $category['slug'])->first();

            $this->assertNotNull($row, "Category {$category['slug']} is missing.");
            $this->assertNotSame('uploads/category/default.png', (string) $row->image);
            $this->assertFileExists(public_path((string) $row->image));
        }

        // ------------------------------------------- products, images, variants
        foreach ($catalog['products'] as $entry) {
            $slug = (string) $entry['slug'];
            $product = DB::table('products')->where('slug', $slug)->first();

            $this->assertNotNull($product, "Product {$slug} is missing.");
            $this->assertSame('AD-' . (int) $entry['source_id'], (string) $product->product_code);

            $images = DB::table('productimages')
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->pluck('image')
                ->all();

            $this->assertCount(4, $images, "Product {$slug} must have four gallery images.");

            foreach ($images as $image) {
                $this->assertFileExists(public_path((string) $image), "Gallery image missing for {$slug}: {$image}");
            }

            $variants = DB::table('product_variants')
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->get();

            $this->assertCount(3, $variants, "Product {$slug} must have three variants.");

            $variantImages = [];
            $variantPrices = [];

            foreach ($variants as $variant) {
                $this->assertFileExists(public_path((string) $variant->image));

                $variantImages[] = (string) $variant->image;
                $variantPrices[] = (float) $variant->price_override;

                $decoded = json_decode((string) $variant->attributes_json, true);

                $this->assertIsArray($decoded, "Variant {$variant->sku} has invalid attributes_json.");
                $this->assertNotEmpty($decoded['ids'] ?? [], "Variant {$variant->sku} has no attribute ids.");

                foreach (($decoded['ids'] ?? []) as $attributeSlug => $valueId) {
                    $this->assertNotNull(
                        DB::table('attributes')->where('slug', $attributeSlug)->first(),
                        "Attribute {$attributeSlug} is missing."
                    );

                    $value = DB::table('attribute_values')->where('id', (int) $valueId)->first();

                    $this->assertNotNull($value, "Attribute value {$valueId} for {$variant->sku} is missing.");
                    $this->assertSame(
                        (string) ($decoded['attrs'][$attributeSlug] ?? ''),
                        (string) $value->value
                    );
                }

                $stock = DB::table('variant_stocks')->where('variant_id', $variant->id)->first();

                $this->assertNotNull($stock, "Variant {$variant->sku} has no stock row.");
                $this->assertGreaterThan(0, (int) $stock->available, "Variant {$variant->sku} is not buyable.");

                $this->assertTrue(
                    DB::table('inventory_movements')
                        ->where('variant_id', $variant->id)
                        ->where('type', 'OPENING_BALANCE')
                        ->exists(),
                    "Variant {$variant->sku} has no opening balance ledger entry."
                );
            }

            $this->assertCount(3, array_unique($variantImages), "Variant images must differ for {$slug}.");
            $this->assertCount(3, array_unique($variantPrices), "Variant prices must differ for {$slug}.");
        }

        // ------------------------------------------------------------ branding
        $settings = GeneralSetting::query()->orderBy('id')->first();

        $this->assertNotNull($settings);
        $this->assertSame('Z EVO', (string) $settings->name);

        foreach (['dark_logo', 'white_logo', 'favicon'] as $field) {
            $path = (string) $settings->{$field};

            $this->assertNotSame('', $path, "General setting {$field} is empty.");
            $this->assertFileExists(public_path($path), "Brand file for {$field} is missing: {$path}");
        }

        $this->assertSame('#003c9e', strtolower((string) $settings->primary_color));
        $this->assertSame('#00a3e0', strtolower((string) $settings->secondary_color));

        // ------------------------------------------------------------- banners
        $bannerCategory = DB::table('banner_categories')->where('name', 'Homepage')->first();

        $this->assertNotNull($bannerCategory, 'The Homepage banner category is missing.');

        $banners = DB::table('banners')
            ->where('category_id', $bannerCategory->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(10, $banners, 'The hero slider must hold one image and nine videos.');
        $this->assertSame(1, $banners->where('media_type', 'image')->count());
        $this->assertSame(9, $banners->where('media_type', 'video')->count());

        foreach ($banners as $banner) {
            $this->assertFileExists(public_path((string) $banner->image));

            if ((string) $banner->media_type === 'video') {
                $this->assertFileExists(public_path((string) $banner->video));
            }
        }

        // ------------------------------------------------ storefront, first item
        $entry = $catalog['products'][0];
        $product = DB::table('products')->where('slug', (string) $entry['slug'])->first();

        $this->assertNotNull($product);

        $variants = DB::table('product_variants')
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get();

        // Classic template: four gallery items, three attribute buttons.
        FrontendTheme::save(FrontendTheme::CLASSIC);
        FrontendTheme::forget();

        $classic = $this->get(route('product', $product->id));
        $classic->assertOk();

        $classicHtml = (string) $classic->getContent();

        $this->assertSame(4, $this->countByClass($classicHtml, 'media-item-nav'), 'Classic gallery must show four images.');
        $this->assertSame(4, $this->countByClass($classicHtml, 'zoom-image'), 'Classic zoom set must hold four images.');
        $this->assertSame(3, $this->countByClass($classicHtml, 'attributeBtn'), 'Classic page must show three variant buttons.');

        foreach ($variants as $variant) {
            $ids = array_values(array_map('intval', (array) (json_decode((string) $variant->attributes_json, true)['ids'] ?? [])));

            $response = $this->get(route('products.variant-images', [
                'productId' => $product->id,
                'attribute_values' => json_encode($ids),
            ]));

            $response->assertOk()->assertJsonPath('error', false);
            $this->assertStringContainsString(
                basename((string) $variant->image),
                (string) $response->json('images'),
                'Classic variant-images must return the selected variant photo.'
            );
        }

        // FM template: four thumbnails, three pills.
        FrontendTheme::save(FrontendTheme::MODERN);
        FrontendTheme::forget();

        $modern = $this->get(route('product', $product->id));
        $modern->assertOk();

        $fmHtml = (string) $modern->getContent();

        $this->assertSame(4, $this->countByClass($fmHtml, 'fm-detail-thumb'), 'FM gallery must show four thumbnails.');
        $this->assertSame(3, $this->countByClass($fmHtml, 'fm-attr-pill'), 'FM page must show three variant pills.');

        // Variant endpoint: photo and price change together.
        $seenImages = [];
        $seenPrices = [];

        foreach ($variants as $variant) {
            $ids = array_values(array_map('intval', (array) (json_decode((string) $variant->attributes_json, true)['ids'] ?? [])));

            $response = $this->get(route('products.variant-detail', [
                'productId' => $product->id,
                'attribute_values' => json_encode($ids),
            ]));

            $response->assertOk()->assertJsonPath('found', true);

            $this->assertSame(
                basename((string) $variant->image),
                basename((string) parse_url((string) $response->json('variant.image'), PHP_URL_PATH))
            );
            $this->assertSame((float) $variant->price_override, (float) $response->json('variant.price'));

            $seenImages[] = (string) $response->json('variant.image');
            $seenPrices[] = (float) $response->json('variant.price');
        }

        $this->assertCount(3, array_unique($seenImages), 'Variant detail must return three different photos.');
        $this->assertCount(3, array_unique($seenPrices), 'Variant detail must return three different prices.');

        // Keep PHPUnit output clean after the assertions above.
        fwrite(STDOUT, "\nZEVO_CATALOG_COMPLETE_OK\n");
    }

    /** Count elements whose class attribute contains the exact class token. */
    private function countByClass(string $html, string $class): int
    {
        if (trim($html) === '') {
            return 0;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);

        return (int) $xpath->query(
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]"
        )->length;
    }
}
