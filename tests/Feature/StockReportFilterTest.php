<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Filter correctness for the admin Stock Report.
 *
 * Two defects are locked here:
 *
 *  1. `whereBetween(updated_at, [start_date, end_date])` compared against raw
 *     date strings, so end_date became midnight and a product updated later
 *     that same day disappeared from a start=end=today filter.
 *  2. `paginate(10)` was not chained with `withQueryString()`, so page 2 of a
 *     filtered report dropped keyword/category/date and showed the whole
 *     catalogue instead of the filtered subset.
 *
 * A passing run prints STOCK_REPORT_FILTERS_OK from the recorded check.
 */
class StockReportFilterTest extends TestCase
{
    use DatabaseTransactions;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = (int) DB::table('categories')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $this->categoryId, 'The audit needs at least one category.');
    }

    public function test_same_day_date_range_includes_a_product_updated_that_day(): void
    {
        $name = 'ZC Same Day Fixture ' . Str::upper(Str::random(6));
        $productId = $this->makeProduct($name);
        $today = now()->toDateString();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.stock_report', [
                'keyword' => $name,
                'start_date' => $today,
                'end_date' => $today,
            ]))
            ->assertOk();

        $this->assertSame(
            1,
            (int) $response->viewData('products')->total(),
            'A start_date=end_date=today filter must include a product updated later today.'
        );
        $this->assertSame($productId, (int) $response->viewData('products')->first()->id);
    }

    public function test_pagination_links_keep_the_active_filters(): void
    {
        $prefix = 'ZC Filter ' . Str::upper(Str::random(6));

        for ($i = 0; $i < 12; $i++) {
            $this->makeProduct($prefix . ' ' . $i);
        }

        $response = $this->actingAs($this->admin())
            ->get(route('admin.stock_report', [
                'keyword' => $prefix,
                'category_id' => $this->categoryId,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk();

        $paginator = $response->viewData('products');

        $this->assertSame(12, (int) $paginator->total());
        $this->assertTrue($paginator->hasPages(), 'Twelve rows must produce a second page.');

        $page2 = $paginator->url(2);

        $this->assertStringContainsString('keyword=' . rawurlencode($prefix), $page2);
        $this->assertStringContainsString('category_id=' . $this->categoryId, $page2);
        $this->assertStringContainsString('start_date=', $page2);
        $this->assertStringContainsString('end_date=', $page2);
    }

    private function admin(): User
    {
        return User::query()->orderBy('id')->firstOrFail();
    }

    private function makeProduct(string $name): int
    {
        return (int) DB::table('products')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'category_id' => $this->categoryId,
            'product_code' => 'ZCF-' . Str::upper(Str::random(8)),
            'purchase_price' => 200,
            'old_price' => 500,
            'new_price' => 400,
            'description' => 'Stock report filter audit fixture.',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
