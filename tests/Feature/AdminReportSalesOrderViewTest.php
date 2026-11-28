<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sales Report, report-management-final section 8 item 6: the order-level
 * view and the four order-grain summary cards.
 *
 * Every expected number is recomputed from the database with plain SQL that
 * shares no code with OrderController::order_report:
 *   - delivered means order_status 'delivered' or '4' (the two spellings the
 *     column has held, docs/report-metrics.md 1.5);
 *   - the summary aggregates `orders` over the DISTINCT order ids the filter
 *     keeps, never over line rows (duplicate rule 1.6) - the live data has
 *     delivered orders with more than one line;
 *   - page 1 follows the controller's own order (created_at desc, id desc,
 *     10 rows per page), and the footer's line total covers the whole filter.
 *
 * Read-only against the development database; DatabaseTransactions keeps the
 * render from writing anything back.
 *
 * Marker: SALES_ORDER_VIEW_OK
 */
class AdminReportSalesOrderViewTest extends TestCase
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
     * The rendered value next to data-report-summary="<key>".
     */
    private function summary(string $html, string $key): string
    {
        $pattern = '/data-report-summary="' . preg_quote($key, '/') . '">([^<]*)</';
        $this->assertMatchesRegularExpression(
            $pattern,
            $html,
            "SALES_ORDER_VIEW_OK: summary card [{$key}] is missing from the Sales Report."
        );

        preg_match($pattern, $html, $m);

        return trim($m[1]);
    }

    /**
     * Independent SQL: the DISTINCT delivered order ids the page filter keeps.
     *
     * $start / $end are the same whole-day bounds the controller applies
     * (Start Date 00:00:00, End Date 23:59:59).
     */
    private function deliveredIds(?string $start = null, ?string $end = null): array
    {
        $sql = 'SELECT DISTINCT d.order_id AS id FROM order_details d '
            . 'JOIN orders o ON o.id = d.order_id '
            . "WHERE o.order_status IN ('delivered','4')";
        $bindings = [];

        if ($start !== null) {
            $sql .= ' AND d.updated_at >= ?';
            $bindings[] = $start;
        }
        if ($end !== null) {
            $sql .= ' AND d.updated_at <= ?';
            $bindings[] = $end;
        }

        $ids = array_map(fn ($row) => (int) $row->id, DB::select($sql, $bindings));
        sort($ids);

        return $ids;
    }

    /**
     * Independent SQL: order-grain totals over that exact id set.
     */
    private function orderAggregate(array $ids): object
    {
        return DB::table('orders')->whereIn('id', $ids)
            ->selectRaw(
                'COUNT(*) AS c, COALESCE(SUM(discount),0) AS d, '
                . 'COALESCE(SUM(shipping_charge),0) AS s, COALESCE(SUM(amount),0) AS a'
            )
            ->first();
    }

    /**
     * The data-order-* attributes of every order-view row, in page order.
     */
    private function orderRows(string $html): array
    {
        $pattern = '/data-report-view="order"\s+data-order-id="(\d+)"\s+'
            . 'data-order-total="([^"]+)"\s+data-order-discount="([^"]+)"\s+'
            . 'data-order-shipping="([^"]+)"\s+data-order-lines="(\d+)"\s+'
            . 'data-order-qty="(\d+)"/';

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $rows[] = [
                'id' => (int) $match[1],
                'total' => $match[2],
                'discount' => $match[3],
                'shipping' => $match[4],
                'lines' => (int) $match[5],
                'qty' => (int) $match[6],
            ];
        }

        return $rows;
    }

    public function test_summary_cards_match_independent_sql(): void
    {
        $html = $this->html(route('admin.order_report'));

        $ids = $this->deliveredIds();
        $agg = $this->orderAggregate($ids);

        $expected = [
            'orders' => (string) (int) $agg->c,
            'order_discount' => number_format((float) $agg->d, 2),
            'shipping' => number_format((float) $agg->s, 2),
            'order_total' => number_format((float) $agg->a, 2),
        ];

        foreach ($expected as $key => $value) {
            $this->assertSame(
                $value,
                $this->summary($html, $key),
                "SALES_ORDER_VIEW_OK: summary card [{$key}] disagrees with independent SQL "
                . 'over ' . count($ids) . ' distinct delivered orders.'
            );
        }

        $this->assertStringContainsString('data-report-grain="line"', $html);
    }

    public function test_order_view_page_one_matches_independent_sql(): void
    {
        $html = $this->html(route('admin.order_report', ['view' => 'order']));

        $this->assertStringContainsString('data-report-grain="order"', $html);
        $this->assertStringContainsString('view=line', $html, 'The grain toggle must offer the line view.');

        $ids = $this->deliveredIds();
        $rendered = $this->orderRows($html);

        if ($ids === []) {
            $this->assertSame([], $rendered, 'No delivered order exists, so the order view must be empty.');
            $this->markTestSkipped('No delivered order in the database to compare against.');
        }

        // The controller prints orders created_at desc, id desc, 10 per page.
        $pageOne = DB::select(
            'SELECT id, amount, discount, shipping_charge FROM orders WHERE id IN ('
            . implode(',', array_fill(0, count($ids), '?'))
            . ') ORDER BY created_at DESC, id DESC LIMIT 10',
            $ids
        );

        $this->assertSame(
            array_map(fn ($row) => (int) $row->id, $pageOne),
            array_column($rendered, 'id'),
            'SALES_ORDER_VIEW_OK: order view page 1 is not the top 10 delivered orders by created_at, id.'
        );

        $pageOneIds = array_map(fn ($row) => (int) $row->id, $pageOne);
        $lineStats = DB::select(
            'SELECT order_id, COUNT(*) AS line_count, COALESCE(SUM(qty),0) AS qty '
            . 'FROM order_details WHERE order_id IN ('
            . implode(',', array_fill(0, count($pageOneIds), '?'))
            . ') GROUP BY order_id',
            $pageOneIds
        );
        $linesByOrder = [];
        foreach ($lineStats as $stat) {
            $linesByOrder[(int) $stat->order_id] = $stat;
        }

        foreach ($pageOne as $position => $row) {
            $renderedRow = $rendered[$position];
            $lineStat = $linesByOrder[(int) $row->id] ?? null;

            $this->assertSame(
                number_format((float) $row->amount, 2),
                $renderedRow['total'],
                "SALES_ORDER_VIEW_OK: Order Total of order {$row->id} disagrees with orders.amount."
            );
            $this->assertSame(
                number_format((float) $row->discount, 2),
                $renderedRow['discount'],
                "Order Discount of order {$row->id} disagrees with orders.discount."
            );
            $this->assertSame(
                number_format((float) $row->shipping_charge, 2),
                $renderedRow['shipping'],
                "Shipping Charged of order {$row->id} disagrees with orders.shipping_charge."
            );
            $this->assertSame(
                (int) ($lineStat->line_count ?? 0),
                $renderedRow['lines'],
                "Lines of order {$row->id} disagrees with COUNT(*) over its order_details rows."
            );
            $this->assertSame(
                (int) ($lineStat->qty ?? 0),
                $renderedRow['qty'],
                "Qty of order {$row->id} disagrees with SUM(qty) over its order_details rows."
            );
        }
    }

    public function test_order_grain_counts_distinct_orders_not_lines(): void
    {
        $ids = $this->deliveredIds();
        $lineCount = (int) DB::select(
            "SELECT COUNT(*) AS c FROM order_details d JOIN orders o ON o.id = d.order_id "
            . "WHERE o.order_status IN ('delivered','4')"
        )[0]->c;

        $lineHtml = $this->html(route('admin.order_report'));
        $orderHtml = $this->html(route('admin.order_report', ['view' => 'order']));

        // The footer prints the filter's line total across every page.
        $this->assertMatchesRegularExpression('/Filtered totals · ([\d,]+) lines/', $lineHtml);
        preg_match('/Filtered totals · ([\d,]+) lines/', $lineHtml, $footer);
        $footerLines = (int) str_replace(',', '', $footer[1]);

        $this->assertSame(
            $lineCount,
            $footerLines,
            'SALES_ORDER_VIEW_OK: the footer line total disagrees with an independent COUNT(*).'
        );

        // The summary card counts DISTINCT orders - line rows would print a
        // bigger number the moment any delivered order carries two lines.
        $this->assertSame(
            (string) count($ids),
            $this->summary($orderHtml, 'orders'),
            'SALES_ORDER_VIEW_OK: the orders card must equal COUNT(DISTINCT order_id), not the line count.'
        );

        $this->assertSame(
            min(10, count($ids)),
            substr_count($orderHtml, 'data-report-view="order"'),
            'The order view must render one row per distinct order on page 1, capped by the paginator.'
        );
        $this->assertSame(
            min(10, $lineCount),
            substr_count($lineHtml, 'data-report-view="line"'),
            'The line view must render one row per order line on page 1, capped by the paginator.'
        );

        if (count($ids) < $lineCount) {
            // Live data proves the duplicate rule: more lines than orders.
            $this->assertNotSame(
                (string) $lineCount,
                $this->summary($orderHtml, 'orders'),
                'SALES_ORDER_VIEW_OK: with multi-line orders in range the card must NOT print the line count.'
            );
        }
    }
}
