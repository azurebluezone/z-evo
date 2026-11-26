<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Variant-level buy box: image, price and stock must agree.
 *
 * Three UX defects motivated this test:
 *
 *  1. The stock badge used `stockQuantity > 1`, so a variant with exactly one
 *     sellable unit was labelled "Out of Stock".
 *  2. The quantity stepper had no ceiling, so a shopper could ask for 10 units
 *     of a 3-unit variant and only find out at checkout.
 *  3. The page fetched the photo and the price/stock from two endpoints, which
 *     could resolve out of order and show a mixed pair.
 *
 * The suite runs against the live `z_evo` database (no RefreshDatabase), so
 * every row it creates is rolled back by DatabaseTransactions.
 */
class VariantStorefrontUxTest extends TestCase
{
    use DatabaseTransactions;

    private int $productId;
    private int $variantId;
    private int $valueA;
    private int $valueB;
    private string $slug;
    private string $imagePath;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $categoryId = (int) DB::table('categories')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $categoryId, 'The variant UX test needs at least one category.');

        $this->slug = 'zc-variant-ux-' . Str::lower(Str::random(8));

        $this->productId = (int) DB::table('products')->insertGetId([
            'name' => 'ZC Variant UX Tee',
            'slug' => $this->slug,
            'category_id' => $categoryId,
            'product_code' => 'ZCV-' . Str::upper(Str::random(8)),
            'purchase_price' => 300,
            'old_price' => 900,
            'new_price' => 800,
            'description' => 'Variant buy-box UX fixture.',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attributeId = (int) DB::table('attributes')->insertGetId([
            'name' => 'ZC UX Size',
            'slug' => 'zc-ux-size-' . Str::lower(Str::random(6)),
            'kind' => 'custom',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->valueA = (int) DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attributeId,
            'value' => 'Medium',
            'code' => 'm',
            'kind' => 'custom',
            'status' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->valueB = (int) DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attributeId,
            'value' => 'Red',
            'code' => '#cc0000',
            'kind' => 'custom',
            'status' => 1,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A real file, so MediaPath resolves the variant photo instead of the
        // configured placeholder. It is removed in tearDown.
        $this->imagePath = 'uploads/product/zc-variant-' . Str::lower(Str::random(8)) . '.jpg';
        @mkdir(public_path('uploads/product'), 0775, true);
        file_put_contents(public_path($this->imagePath), 'zc-variant-image-fixture');

        $this->variantId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $this->productId,
            'sku' => 'ZCV-' . Str::upper(Str::random(10)),
            'title' => 'Red / Medium',
            'price_override' => 750,
            'discount_price' => 700,
            'image' => $this->imagePath,
            'status' => 1,
            'attributes_json' => json_encode([
                'ids' => ['Size' => $this->valueA, 'Colour' => $this->valueB],
                'attrs' => ['Size' => 'Medium', 'Colour' => 'Red'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('variant_stocks')->insert([
            'variant_id' => $this->variantId,
            'on_hand' => 4,
            'reserved' => 0,
            'available' => 4,
            'damaged' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::query()->orderBy('id')->firstOrFail();
    }

    protected function tearDown(): void
    {
        $absolute = public_path($this->imagePath ?? '');
        if ($this->imagePath !== '' && is_file($absolute)) {
            @unlink($absolute);
        }

        parent::tearDown();
    }

    public function test_variant_detail_returns_image_price_and_stock_in_one_payload(): void
    {
        $response = $this->getJson(route('products.variant-detail', [
            'productId' => $this->productId,
            'attribute_values' => json_encode([$this->valueA, $this->valueB]),
        ]));

        $response->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('variant.stock_quantity', 4)
            ->assertJsonPath('variant.in_stock', true)
            ->assertJsonPath('variant.price', 700)
            ->assertJsonStructure([
                'variant' => ['image', 'price', 'old_price', 'formatted_price', 'stock_quantity'],
                'images',
                'media_ids',
            ]);

        $variant = (array) $response->json('variant');

        $this->assertStringContainsString(
            basename($this->imagePath),
            (string) $variant['image'],
            'the variant photo must travel with the price and stock'
        );
        $this->assertStringContainsString(
            basename($this->imagePath),
            (string) $response->json('images'),
            'the gallery markup must show the selected variant photo'
        );
        $this->assertSame([md5($this->imagePath)], $response->json('media_ids'));
        $this->assertStringContainsString('<del>', (string) $variant['formatted_price'], 'the save must be visible');
    }

    public function test_details_page_publishes_the_stock_ceiling_and_uses_one_endpoint(): void
    {
        $html = $this->get(route('product', $this->slug))->assertOk()->getContent();

        // The project ships two template sets and ThemeViewFinder picks the
        // active one. Both must publish the same server ceiling, so assert the
        // contract of whichever theme answered instead of hard-coding one.
        if (str_contains($html, 'FM_DETAIL_CONFIG')) {
            $this->assertStringContainsString(
                '"maxQty":4',
                $html,
                'the live FM buy box must publish how many units can ship'
            );
            $this->assertStringContainsString(
                'fm-qty-ceiling.js',
                $html,
                'the FM page must load the ceiling controller before its page script'
            );
        } else {
            $this->assertStringContainsString(
                'data-max-stock="4"',
                $html,
                'the classic buy box must publish how many units can ship'
            );
            $this->assertMatchesRegularExpression(
                '/max="4"\s+name="quantity"/',
                $html,
                'the classic quantity input must carry the ceiling'
            );
        }

        $this->assertStringContainsString('/variant-detail', $html);
        $this->assertStringNotContainsString(
            'products/check-stock?product_id=',
            $html,
            'photo and price must not come from two separate calls'
        );
    }

    public function test_creating_a_variant_from_admin_creates_its_stock_row(): void
    {
        $sku = 'ZCV-' . Str::upper(Str::random(8));

        $this->actingAs($this->admin)->post(route('admin.variants.store'), [
            'product_id' => $this->productId,
            'sku' => $sku,
            'title' => 'Opening stock row',
            'status' => 1,
            'initial_stock' => 9,
        ])->assertRedirect(route('admin.variants.index'));

        $variantId = (int) DB::table('product_variants')->where('sku', $sku)->value('id');
        $this->assertGreaterThan(0, $variantId, 'the variant row must exist');

        $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->first();
        $this->assertNotNull($stock, 'a new variant must never be invisible to the storefront');
        $this->assertSame(9, (int) $stock->on_hand);
        $this->assertSame(9, (int) $stock->available);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variantId,
            'type' => 'OPENING_BALANCE',
            'qty_delta' => 9,
        ]);
    }

    public function test_creating_a_variant_without_stock_still_creates_a_zero_row(): void
    {
        $sku = 'ZCV-' . Str::upper(Str::random(8));

        $this->actingAs($this->admin)->post(route('admin.variants.store'), [
            'product_id' => $this->productId,
            'sku' => $sku,
            'title' => 'Zero opening',
            'status' => 1,
        ])->assertRedirect(route('admin.variants.index'));

        $variantId = (int) DB::table('product_variants')->where('sku', $sku)->value('id');
        $this->assertGreaterThan(0, $variantId);

        $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->first();
        $this->assertNotNull($stock, 'an explicit zero row beats a missing row');
        $this->assertSame(0, (int) $stock->on_hand);
        $this->assertSame(0, (int) $stock->available);
    }
}
