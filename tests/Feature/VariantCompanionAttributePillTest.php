<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The storefront variant picker must expose one pill group per real attribute.
 *
 * VariantAttributes::buildFromRequest() stores companion metadata next to the
 * selection: `<slug>` holds the value id (also mirrored in `ids`) and
 * `<slug>_code` holds the value code, usually a hex colour for a swatch.
 *
 * The detail page used to turn `_code` into its own pill group. That group had
 * no id, so a shopper could never complete a selection: the page script
 * requires one pick per group before it fetches variant-detail, so clicking a
 * colour fired no request and the photo, price and stock never changed.
 *
 * These tests pin both halves of the fix: the companion key is not an
 * attribute, and a group that cannot resolve to a value is dropped so it can
 * never deadlock the picker again.
 *
 * Runs against the live `z_evo` database; DatabaseTransactions rolls every row
 * back.
 */
class VariantCompanionAttributePillTest extends TestCase
{
    use DatabaseTransactions;

    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $categoryId = (int) DB::table('categories')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $categoryId, 'The pill test needs at least one category.');

        $this->productId = (int) DB::table('products')->insertGetId([
            'name' => 'ZC Companion Pill Tee',
            'slug' => 'zc-companion-pill-' . Str::lower(Str::random(8)),
            'category_id' => $categoryId,
            'product_code' => 'ZCP-' . Str::upper(Str::random(8)),
            'purchase_price' => 300,
            'old_price' => 900,
            'new_price' => 800,
            'description' => 'Companion attribute pill fixture.',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0:string,1:int} the attribute slug and the picked value id
     */
    private function colourAttribute(string $value = 'Sky Blue', string $code = '#4a90d9'): array
    {
        $slug = 'zc-color-' . Str::lower(Str::random(6));

        $attributeId = (int) DB::table('attributes')->insertGetId([
            'name' => 'ZC Colour ' . Str::upper(Str::random(4)),
            'slug' => $slug,
            'kind' => 'custom',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $valueId = (int) DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attributeId,
            'value' => $value,
            'code' => $code,
            'kind' => 'custom',
            'status' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$slug, $valueId];
    }

    private function variant(string $attributesJson): void
    {
        DB::table('product_variants')->insert([
            'product_id' => $this->productId,
            'sku' => 'ZCP-' . Str::upper(Str::random(10)),
            'title' => 'Sky Blue',
            'price_override' => 750,
            'image' => 'uploads/product/zc-companion-placeholder.jpg',
            'status' => 1,
            'attributes_json' => $attributesJson,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_companion_code_key_does_not_create_a_second_pill_group(): void
    {
        [$slug, $valueId] = $this->colourAttribute();

        // Exactly what VariantAttributes::buildFromRequest() stores for a
        // colour variant: the label plus its hex in `attrs`, the id in `ids`.
        $this->variant((string) json_encode([
            'attrs' => [$slug => 'Sky Blue', $slug . '_code' => '#4a90d9'],
            'ids' => [$slug => (string) $valueId],
        ]));

        $response = $this->get(route('product', $this->productId));
        $response->assertOk();

        $html = (string) $response->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'data-attr-slug="'),
            'a colour variant must render exactly one pill group'
        );
        $this->assertStringContainsString('data-attr-slug="' . $slug . '"', $html);
        $this->assertStringNotContainsString(
            $slug . '_code',
            $html,
            'the companion _code key must never reach the markup'
        );
        $this->assertStringContainsString('data-attr-id="' . $valueId . '"', $html);
        $this->assertStringContainsString('data-label="Sky Blue"', $html);
        $this->assertStringContainsString(
            'data-colour="#4a90d9"',
            $html,
            'the stored hex still drives the swatch'
        );
    }

    public function test_a_group_with_no_resolvable_value_is_dropped(): void
    {
        [$slug] = $this->colourAttribute();

        // Legacy or hand-written data: only the companion key, no id at all.
        // Rendering it as a group would leave the picker permanently stuck.
        $this->variant((string) json_encode([
            'attrs' => [$slug . '_code' => '#123456'],
            'ids' => [],
        ]));

        $response = $this->get(route('product', $this->productId));
        $response->assertOk();

        $html = (string) $response->getContent();

        $this->assertSame(0, substr_count($html, 'data-attr-slug="'), 'an unresolvable group must be dropped');
        $this->assertSame(0, substr_count($html, 'fm-attr-pill'), 'an unresolvable group must render no pill');
        $this->assertStringContainsString('id="fmDetailMainPhoto"', $html, 'the page still renders the buy box');
    }
}
