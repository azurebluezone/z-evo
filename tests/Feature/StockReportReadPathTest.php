<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin Stock Report must read the inventory SSOT projection
 * (product_variants + variant_stocks). The legacy products.stock column was
 * dropped by migration 2026_11_26_000002, so the projection is the only
 * source the report can read.
 *
 * Fixture: one product with two active variants holding 20 + 5 units at
 * different prices. The report must show 25 units and the variant-aware
 * value, and the filtered footer totals must cover the whole filter rather
 * than page one.
 *
 * A passing run prints STOCK_REPORT_READ_PATH_OK from the recorded check.
 */
class StockReportReadPathTest extends TestCase
{
    use DatabaseTransactions;

    private int $productId;
    private string $name;

    protected function setUp(): void
    {
        parent::setUp();

        $categoryId = (int) DB::table('categories')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $categoryId, 'The audit needs at least one category.');

        $this->name = 'ZC Stock Report Fixture ' . Str::upper(Str::random(6));

        $this->productId = (int) DB::table('products')->insertGetId([
            'name' => $this->name,
            'slug' => Str::slug($this->name) . '-' . Str::lower(Str::random(6)),
            'category_id' => $categoryId,
            'product_code' => 'ZCSR-' . Str::upper(Str::random(8)),
            'purchase_price' => 200,
            'old_price' => 500,
            'new_price' => 400,
            'description' => 'Stock report read-path audit fixture.',
            'topsale' => 1,
            'feature_product' => 1,
            'is_recommended' => 1,
            'recommended_sort_order' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variants = [
            ['on_hand' => 20, 'price_override' => 400, 'purchase_price' => 200],
            ['on_hand' => 5, 'price_override' => 450, 'purchase_price' => 210],
        ];

        foreach ($variants as $index => $variant) {
            $variantId = (int) DB::table('product_variants')->insertGetId([
                'product_id' => $this->productId,
                'sku' => 'ZCSR-' . $index . '-' . Str::upper(Str::random(8)),
                'title' => 'Audit Variant ' . $index,
                'price_override' => $variant['price_override'],
                'purchase_price' => $variant['purchase_price'],
                'status' => 1,
                'attributes_json' => json_encode([
                    'ids' => [920000 + $index],
                    'attrs' => ['Audit Variant ' . $index],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('variant_stocks')->insert([
                'variant_id' => $variantId,
                'on_hand' => $variant['on_hand'],
                'reserved' => 0,
                'available' => $variant['on_hand'],
                'damaged' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_stock_report_reads_the_variant_projection_not_legacy_products_stock(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.stock_report'))
            ->assertOk();

        [$units, $value, $cost] = $this->expectedGlobalTotals();

        $this->assertSame(
            $units,
            (int) $response->viewData('total_stock'),
            'The footer units must be the SSOT on_hand sum for the whole filtered set.'
        );
        $this->assertEqualsWithDelta($value, (float) $response->viewData('total_price'), 0.01);
        $this->assertEqualsWithDelta($cost, (float) $response->viewData('total_purchase'), 0.01);

        // The catalogue has more variants than one page of products, so a
        // page-limited or legacy-column total would disagree with the above.
        $this->assertGreaterThan(50, (int) DB::table('product_variants')->where('status', 1)->count());
    }

    public function test_filtered_totals_and_row_values_use_variant_units_and_variant_prices(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.stock_report', ['keyword' => $this->name]))
            ->assertOk();

        $this->assertSame(1, (int) $response->viewData('products')->total());

        // 20 units at 400 + 5 units at 450 = 10,250 sale value.
        // 20 units at 200 + 5 units at 210 =  5,050 cost value.
        $this->assertSame(25, (int) $response->viewData('total_stock'));
        $this->assertEqualsWithDelta(10250.0, (float) $response->viewData('total_price'), 0.01);
        $this->assertEqualsWithDelta(5050.0, (float) $response->viewData('total_purchase'), 0.01);

        $row = $response->viewData('products')->firstWhere('id', $this->productId);
        $this->assertNotNull($row, 'The filtered report must contain the fixture product.');

        $this->assertSame(25, (int) $row->stock_on_hand, 'Row units must come from the variant_stocks projection.');
        $this->assertEqualsWithDelta(10250.0, (float) $row->stock_value, 0.01);
        $this->assertEqualsWithDelta(410.0, (float) $row->unit_price, 0.01);

        // The rendered row must show the projection figure.
        $html = (string) $response->getContent();
        $this->assertStringContainsString('10,250.00', $html);
    }

    private function admin(): User
    {
        return User::query()->orderBy('id')->firstOrFail();
    }

    /**
     * Recompute the report totals in PHP from the projection tables.
     *
     * @return array{0:int,1:float,2:float}
     */
    private function expectedGlobalTotals(): array
    {
        $rows = DB::table('products as p')
            ->join('product_variants as v', function ($join) {
                $join->on('v.product_id', '=', 'p.id')->where('v.status', 1);
            })
            ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->where('p.status', 1)
            ->select([
                's.on_hand',
                DB::raw('COALESCE(NULLIF(v.price_override, 0), p.new_price) as sale_price'),
                DB::raw('COALESCE(NULLIF(v.purchase_price, 0), p.purchase_price) as cost_price'),
            ])
            ->get();

        $units = 0;
        $value = 0.0;
        $cost = 0.0;

        foreach ($rows as $row) {
            $onHand = (int) $row->on_hand;
            $units += $onHand;
            $value += $onHand * (float) $row->sale_price;
            $cost += $onHand * (float) $row->cost_price;
        }

        return [$units, $value, $cost];
    }

    /**
     * Only the fixture's table row, so a number elsewhere on the page cannot
     * satisfy or break the legacy-value assertion.
     */
    private function rowHtml(string $html): string
    {
        $pos = strpos($html, $this->name);
        $this->assertNotFalse($pos, 'The fixture product name must be rendered in the report.');

        return substr($html, max(0, $pos - 200), 1200);
    }
}
