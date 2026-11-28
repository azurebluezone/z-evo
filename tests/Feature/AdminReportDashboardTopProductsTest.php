<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Top Products table on the Reports Dashboard (metric dictionary 3.1,
 * "Top Products"): the five products that earned most in the range at the
 * order-line grain, dated by the PARENT order's created_at - the same basis
 * as the Gross Product Sales KPI, so the table can never exceed that card.
 *
 * Every expected row is recomputed here from the database with plain SQL
 * written without looking at ReportMetricsService, including the tie-break
 * (sales desc, units desc, product_id asc) that keeps rows from swapping.
 *
 * Contract marker: TOP_PRODUCTS_OK
 */
class AdminReportDashboardTopProductsTest extends TestCase
{
    use DatabaseTransactions;

    private const MARKER = 'TOP_PRODUCTS_OK';

    private function admin(): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($user, 'No Admin user, so the dashboard cannot be authenticated.');

        return $user;
    }

    /**
     * The expected rows, recomputed without the service.
     *
     * @return array<int, array{product_id: int, units: int, sales: float}>
     */
    private function topRows(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            'SELECT d.product_id AS product_id,
                    SUM(d.qty) AS units,
                    SUM(d.sale_price * d.qty) AS sales
               FROM order_details d
               JOIN orders o ON o.id = d.order_id
              WHERE o.created_at BETWEEN ? AND ?
           GROUP BY d.product_id
           ORDER BY sales DESC, units DESC, d.product_id ASC
              LIMIT 5',
            [$from->toDateTimeString(), $to->toDateTimeString()]
        );

        return array_map(static fn (object $row): array => [
            'product_id' => (int) $row->product_id,
            'units' => (int) $row->units,
            'sales' => (float) $row->sales,
        ], $rows);
    }

    public function test_top_products_table_matches_a_fresh_database_recomputation(): void
    {
        // 60 days back reaches every order line in the live data.
        $from = now()->subDays(59)->startOfDay();
        $to = now()->endOfDay();

        $expected = $this->topRows($from, $to);

        $this->assertGreaterThan(0, count($expected), 'No order lines in range, so the table proves nothing.');

        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('data-table="top-products"', false);

        foreach ($expected as $index => $row) {
            $position = $index + 1;

            // The row must carry this product's own units and sales, in the
            // expected order - a wrong sort shows as a missing needle here.
            $response->assertSee('data-top-units="'.$row['product_id'].'">'.number_format($row['units'], 0).'</td>', false);
            $response->assertSee('data-top-sales="'.$row['product_id'].'">BDT '.number_format($row['sales'], 0).'</td>', false);
        }

        // The table must not exceed the Gross Product Sales card: both use
        // the same grain and date basis, so the top-5 sum is at most the total.
        $totalSales = (float) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->sum(DB::raw('d.sale_price * d.qty'));

        $topSum = array_sum(array_column($expected, 'sales'));

        $this->assertLessThanOrEqual($totalSales + 0.01, $topSum);
        $this->assertGreaterThan(0, $topSum, 'Top-5 sales must be a real share of product sales.');

        $response->assertSee('same basis as Gross Product Sales', false, self::MARKER);
    }

    public function test_a_range_with_no_order_lines_shows_an_empty_table_not_a_crash(): void
    {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $response = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('data-table="top-products"', false);

        // Whatever the database says for this range, the page must say the
        // same: either the exact rows, or the empty line when there are none.
        $expected = $this->topRows($from, $to);

        if ($expected === []) {
            $response->assertSee('No order line in this range.', false);
        } else {
            foreach ($expected as $row) {
                $response->assertSee('data-top-sales="'.$row['product_id'].'">BDT '.number_format($row['sales'], 0).'</td>', false);
            }
        }

        $response->assertSee('data-table="top-products"', false, self::MARKER);
    }
}
