<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Stock Alerts velocity columns, report-management-final section 8 item 8:
 * Last Sold, Avg Daily Sold (rolling 30 days on orders.delivered_at) and
 * Days Remaining.
 *
 * Live rows are checked against plain SQL that shares no code with
 * ReportStockAlertController. The live catalogue has no delivered order in
 * the last 30 days (the newest delivered_at is 2026-10-28), so the positive
 * branch of Days Remaining is exercised with a fixture inside the
 * transaction:
 *   - 30 units delivered 5 days ago  -> 1.00/day
 *   - 60 units delivered 40 days ago -> outside the window, must not count
 *   - available 10                   -> 10 days left
 *   - a second fixture variant with no delivered sales -> dash, never
 *     infinity.
 *
 * Read-only for live data; DatabaseTransactions rolls the fixture back.
 *
 * Marker: STOCK_VELOCITY_OK
 */
class AdminReportStockAlertVelocityTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so no admin page can be authenticated.');

        return $admin;
    }

    private function html(string $url): string
    {
        $response = $this->actingAs($this->admin())->get($url);
        $response->assertOk();

        return $response->getContent();
    }

    /**
     * The velocity attributes of every variant row, keyed by variant id.
     */
    private function rows(string $html): array
    {
        $pattern = '/data-variant-id="(\d+)"\s+data-last-sold="([^"]*)"\s+'
            . 'data-avg-daily="([^"]+)"\s+data-days-left="([^"]*)"/';

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $rows[(int) $match[1]] = [
                'last_sold' => $match[2],
                'avg' => $match[3],
                'days' => $match[4],
            ];
        }

        return $rows;
    }

    public function test_live_rows_match_independent_sql(): void
    {
        $html = $this->html(route('admin.reports.stock-alerts'));
        $rows = $this->rows($html);

        $this->assertNotEmpty($rows, 'STOCK_VELOCITY_OK: the Stock Alerts page rendered no variant rows.');

        $ids = array_keys($rows);
        $stats = DB::select(
            'SELECT d.variant_id, MAX(o.delivered_at) AS last_sold, '
            . 'COALESCE(SUM(CASE WHEN o.delivered_at >= ? THEN d.qty ELSE 0 END),0) AS units_30d '
            . 'FROM order_details d JOIN orders o ON o.id = d.order_id '
            . "WHERE o.order_status IN ('delivered','4') AND d.variant_id IN ("
            . implode(',', array_fill(0, count($ids), '?'))
            . ') GROUP BY d.variant_id',
            array_merge([now()->subDays(30)->toDateTimeString()], $ids)
        );

        $byVariant = [];
        foreach ($stats as $stat) {
            $byVariant[(int) $stat->variant_id] = $stat;
        }

        foreach ($rows as $variantId => $rendered) {
            $expected = $byVariant[$variantId] ?? null;
            $units30 = (int) ($expected->units_30d ?? 0);
            $avg = $units30 / 30;

            $this->assertSame(
                (string) ($expected->last_sold ?? ''),
                $rendered['last_sold'],
                "STOCK_VELOCITY_OK: Last Sold of variant {$variantId} disagrees with MAX(orders.delivered_at)."
            );
            $this->assertSame(
                number_format($avg, 2),
                $rendered['avg'],
                "STOCK_VELOCITY_OK: Avg / day of variant {$variantId} disagrees with delivered units in 30 days / 30."
            );
        }
    }

    public function test_fixture_velocity_and_days_remaining(): void
    {
        $categoryId = (int) DB::table('categories')->orderBy('id')->value('id');
        $customerId = (int) DB::table('customers')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $categoryId, 'The audit needs at least one category.');
        $this->assertGreaterThan(0, $customerId, 'The audit needs at least one customer.');

        $name = 'ZC Velocity Fixture ' . Str::upper(Str::random(6));
        $productId = (int) DB::table('products')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'category_id' => $categoryId,
            'product_code' => 'ZCV-' . Str::upper(Str::random(8)),
            'purchase_price' => 100,
            'old_price' => 300,
            'new_price' => 250,
            'description' => 'Stock alerts velocity audit fixture.',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sellingVariant = $this->makeVariant($productId, 'Selling', 10);
        $quietVariant = $this->makeVariant($productId, 'Quiet', 5);

        // One timestamp for insert and assertion: calling now() twice can
        // cross a second boundary and make the exact-match assert flaky.
        $deliveredAt = now()->subDays(5);

        $this->makeDeliveredLine($productId, $sellingVariant, $name, $customerId, 30, $deliveredAt);
        $this->makeDeliveredLine($productId, $sellingVariant, $name, $customerId, 60, now()->subDays(40));

        $rows = $this->rows($this->html(route('admin.reports.stock-alerts')));

        $this->assertArrayHasKey($sellingVariant, $rows, 'The fixture variant must appear in the balances table.');
        $this->assertSame(
            $deliveredAt->toDateTimeString(),
            $rows[$sellingVariant]['last_sold'],
            'STOCK_VELOCITY_OK: Last Sold must be the latest delivered_at, not the order creation date.'
        );
        $this->assertSame(
            '1.00',
            $rows[$sellingVariant]['avg'],
            'STOCK_VELOCITY_OK: 30 units inside the window must be 1.00/day; the 40-day-old line must stay out.'
        );
        $this->assertSame(
            '10',
            $rows[$sellingVariant]['days'],
            'STOCK_VELOCITY_OK: available 10 at 1.00/day must print 10 days.'
        );

        $this->assertArrayHasKey($quietVariant, $rows, 'The quiet fixture variant must appear in the balances table.');
        $this->assertSame('', $rows[$quietVariant]['last_sold']);
        $this->assertSame('0.00', $rows[$quietVariant]['avg']);
        $this->assertSame(
            '',
            $rows[$quietVariant]['days'],
            'A variant with no delivered sales must show a dash, never infinity or a fabricated number.'
        );
    }

    public function test_per_variant_threshold_changes_the_bucket(): void
    {
        // Pin the global thresholds so the fixture proves the variant path,
        // not whatever the live settings happen to be. Rolled back.
        DB::table('report_settings')->update([
            'stock_warning_threshold' => 0,
            'stock_remaining_threshold' => 2,
        ]);

        $productId = (int) DB::table('products')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $productId, 'The audit needs at least one product.');

        // available 4 with a per-variant threshold of 5 must be "low"; the
        // same balance with no override is above the global line and healthy.
        $variantThreshold = $this->makeVariant($productId, 'Threshold fixture', 4, 5);
        $globalVariant = $this->makeVariant($productId, 'Global fixture', 4, 0);

        $html = $this->html(route('admin.reports.stock-alerts'));

        $this->assertMatchesRegularExpression(
            '/data-variant-id="'.$variantThreshold.'"[^>]*data-status="low"[^>]*data-threshold="5"/',
            $html,
            'STOCK_VELOCITY_OK: a variant threshold of 5 must classify available 4 as low.'
        );
        $this->assertMatchesRegularExpression(
            '/data-variant-id="'.$globalVariant.'"[^>]*data-status="healthy"[^>]*data-threshold="0"/',
            $html,
            'STOCK_VELOCITY_OK: without an override the global threshold must still apply.'
        );
    }

    private function makeVariant(int $productId, string $title, int $available, int $threshold = 0): int
    {
        $variantId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'ZCV-' . Str::upper(Str::random(10)),
            'title' => $title,
            'price_override' => 250,
            'purchase_price' => 100,
            'status' => 1,
            'attributes_json' => json_encode([
                'ids' => [930000 + $available],
                'attrs' => [$title],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('variant_stocks')->insert([
            'variant_id' => $variantId,
            'on_hand' => $available,
            'reserved' => 0,
            'available' => $available,
            'damaged' => 0,
            'low_stock_threshold' => $threshold,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $variantId;
    }

    private function makeDeliveredLine(
        int $productId,
        int $variantId,
        string $productName,
        int $customerId,
        int $qty,
        \DateTimeInterface $deliveredAt
    ): void {
        $orderId = (int) DB::table('orders')->insertGetId([
            'invoice_id' => 'ZCV-' . Str::upper(Str::random(10)),
            'amount' => 250 * $qty,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => $customerId,
            'order_status' => '4',
            'delivered_at' => $deliveredAt,
            'created_at' => $deliveredAt,
            'updated_at' => $deliveredAt,
        ]);

        DB::table('order_details')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'product_name' => $productName,
            'purchase_price' => 100,
            'sale_price' => 250,
            'qty' => $qty,
            'created_at' => $deliveredAt,
            'updated_at' => $deliveredAt,
        ]);
    }
}
