<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Product Report, report-management-final section 8 item 7: lifetime
 * delivered Product Units Sold / Product Revenue / Product COGS.
 *
 * Every expected number is recomputed with plain SQL that shares no code with
 * OrderController::stock_report:
 *   - delivered means order_status 'delivered' or '4' (docs/report-metrics.md
 *     1.5);
 *   - the figures are grouped on order_details.product_id, not on the
 *     historical product_name;
 *   - they are lifetime (no date window) and only the page's product filters
 *     decide which products are listed;
 *   - a line belongs to the report only when its product is status = 1, which
 *     is what the page lists.
 *
 * Read-only against the development database; DatabaseTransactions keeps the
 * render from writing anything back.
 *
 * Marker: STOCK_SALES_OK
 */
class AdminReportStockSalesTest extends TestCase
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
     * The data-product-* attributes of every product row, keyed by product id.
     */
    private function rows(string $html): array
    {
        $pattern = '/data-product-id="(\d+)"\s+data-units-sold="(\d+)"\s+'
            . 'data-product-revenue="([^"]+)"\s+data-product-cogs="([^"]+)"/';

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $rows[(int) $match[1]] = [
                'units' => (int) $match[2],
                'revenue' => $match[3],
                'cogs' => $match[4],
            ];
        }

        return $rows;
    }

    /**
     * The rendered value next to data-report-total="<key>".
     */
    private function total(string $html, string $key): string
    {
        $pattern = '/data-report-total="' . preg_quote($key, '/') . '">([^<]*)</';
        $this->assertMatchesRegularExpression(
            $pattern,
            $html,
            "STOCK_SALES_OK: footer total [{$key}] is missing from the Product Report."
        );

        preg_match($pattern, $html, $m);

        return trim($m[1]);
    }

    public function test_product_rows_match_independent_sql(): void
    {
        $html = $this->html(route('admin.stock_report'));
        $rows = $this->rows($html);

        $this->assertNotEmpty($rows, 'STOCK_SALES_OK: the Product Report rendered no product rows to compare.');

        $ids = array_keys($rows);
        $stats = DB::select(
            'SELECT d.product_id, COALESCE(SUM(d.qty),0) AS units, '
            . 'COALESCE(SUM(d.sale_price * d.qty),0) AS revenue, '
            . 'COALESCE(SUM(d.purchase_price * d.qty),0) AS cogs '
            . 'FROM order_details d JOIN orders o ON o.id = d.order_id '
            . "WHERE o.order_status IN ('delivered','4') AND d.product_id IN ("
            . implode(',', array_fill(0, count($ids), '?'))
            . ') GROUP BY d.product_id',
            $ids
        );

        $byProduct = [];
        foreach ($stats as $stat) {
            $byProduct[(int) $stat->product_id] = $stat;
        }

        foreach ($rows as $productId => $rendered) {
            $expected = $byProduct[$productId] ?? null;

            $this->assertSame(
                (int) ($expected->units ?? 0),
                $rendered['units'],
                "STOCK_SALES_OK: Units Sold of product {$productId} disagrees with SUM(order_details.qty) over delivered lines."
            );
            $this->assertSame(
                number_format((float) ($expected->revenue ?? 0), 2),
                $rendered['revenue'],
                "STOCK_SALES_OK: Revenue of product {$productId} disagrees with SUM(sale_price * qty) over delivered lines."
            );
            $this->assertSame(
                number_format((float) ($expected->cogs ?? 0), 2),
                $rendered['cogs'],
                "STOCK_SALES_OK: COGS of product {$productId} disagrees with SUM(purchase_price * qty) over delivered lines."
            );
        }
    }

    public function test_footer_totals_match_independent_sql(): void
    {
        $html = $this->html(route('admin.stock_report'));

        $agg = DB::select(
            'SELECT COALESCE(SUM(d.qty),0) AS units, '
            . 'COALESCE(SUM(d.sale_price * d.qty),0) AS revenue, '
            . 'COALESCE(SUM(d.purchase_price * d.qty),0) AS cogs '
            . 'FROM order_details d JOIN orders o ON o.id = d.order_id '
            . 'JOIN products p ON p.id = d.product_id '
            . "WHERE p.status = 1 AND o.order_status IN ('delivered','4')"
        )[0];

        $this->assertSame(
            number_format((float) $agg->units),
            $this->total($html, 'units_sold'),
            'STOCK_SALES_OK: footer Units sold disagrees with the independent delivered-line SUM(qty).'
        );
        $this->assertSame(
            number_format((float) $agg->revenue, 2),
            $this->total($html, 'revenue'),
            'STOCK_SALES_OK: footer Revenue disagrees with the independent delivered-line SUM(sale_price * qty).'
        );
        $this->assertSame(
            number_format((float) $agg->cogs, 2),
            $this->total($html, 'cogs'),
            'STOCK_SALES_OK: footer COGS disagrees with the independent delivered-line SUM(purchase_price * qty).'
        );
    }

    public function test_units_sold_counts_delivered_orders_only(): void
    {
        $html = $this->html(route('admin.stock_report'));

        $delivered = (float) DB::select(
            'SELECT COALESCE(SUM(d.qty),0) AS units FROM order_details d '
            . 'JOIN orders o ON o.id = d.order_id JOIN products p ON p.id = d.product_id '
            . "WHERE p.status = 1 AND o.order_status IN ('delivered','4')"
        )[0]->units;

        $allStatuses = (float) DB::select(
            'SELECT COALESCE(SUM(d.qty),0) AS units FROM order_details d '
            . 'JOIN orders o ON o.id = d.order_id JOIN products p ON p.id = d.product_id '
            . 'WHERE p.status = 1'
        )[0]->units;

        $this->assertSame(number_format($delivered), $this->total($html, 'units_sold'));

        if ($allStatuses > $delivered) {
            // Live data has pending/shipped/cancelled lines too, so a page
            // that forgot the status filter would print the bigger number.
            $this->assertNotSame(
                number_format($allStatuses),
                $this->total($html, 'units_sold'),
                'STOCK_SALES_OK: the footer must count delivered lines only, never every order status.'
            );
        }
    }
}
