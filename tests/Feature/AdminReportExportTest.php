<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Report export, report-management-final section 8 item 9.
 *
 * The page used to serialise the visible <table> to CSV, so the Excel button
 * downloaded page one as .csv. These tests open the downloaded bytes as a real
 * ZIP workbook and compare the sheet values with plain SQL that shares no code
 * with ReportExportController:
 *   - [Content_Types].xml, workbook, styles and sheet1.xml exist;
 *   - the header row is the documented column list;
 *   - the TOTAL row covers the whole filter, not the visible page;
 *   - a product row matches GROUP BY product_id over delivered lines;
 *   - the line export has every delivered line, the order export every
 *     distinct delivered order;
 *   - a date filter that matches nothing produces only header + TOTAL;
 *   - CSV carries the same header and row count.
 *
 * Marker: REPORT_XLSX_OK
 */
class AdminReportExportTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so no admin page can be authenticated.');

        return $admin;
    }

    private function export(array $query): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())->get(route('admin.report_export', $query));
    }

    /**
     * @return array<string,string> entry name => bytes
     */
    private function unzip(string $bytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'zc-xlsx-test-');
        $this->assertNotFalse($path, 'Could not create a temp file for the workbook.');
        file_put_contents($path, $bytes);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'The export is not a readable zip archive.');

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $entries[$name] = $zip->getFromIndex($i);
        }
        $zip->close();
        @unlink($path);

        return $entries;
    }

    /**
     * @return array<int,array<int,string>> rows of cells keyed by zero-based column
     */
    private function sheetRows(string $xml): array
    {
        $sx = simplexml_load_string($xml);
        $this->assertNotFalse($sx, 'xl/worksheets/sheet1.xml is not valid XML.');

        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $index = $this->columnIndex($ref);
                $type = (string) $cell['t'];
                $cells[$index] = $type === 'inlineStr'
                    ? (string) $cell->is->t
                    : (string) $cell->v;
            }
            ksort($cells);
            $rows[] = $cells;
        }

        return $rows;
    }

    private function columnIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $match);
        $index = 0;
        foreach (str_split($match[1]) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function totalRow(array $rows): array
    {
        foreach ($rows as $row) {
            if (($row[0] ?? null) === 'TOTAL') {
                return $row;
            }
        }

        $this->fail('REPORT_XLSX_OK: the export has no TOTAL row.');
    }

    public function test_stock_xlsx_is_a_real_workbook_and_matches_sql(): void
    {
        $response = $this->export(['report' => 'stock', 'format' => 'xlsx']);
        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );

        $bytes = $response->getContent();
        $this->assertStringStartsWith('PK', $bytes, 'An XLSX file starts with the ZIP signature PK.');

        $entries = $this->unzip($bytes);
        foreach (['[Content_Types].xml', 'xl/workbook.xml', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $part) {
            $this->assertArrayHasKey($part, $entries, "REPORT_XLSX_OK: the workbook is missing {$part}.");
        }

        $rows = $this->sheetRows($entries['xl/worksheets/sheet1.xml']);
        $this->assertSame(
            ['SL', 'Product Name', 'Avg. Price', 'Stock', 'Stock Value', 'Units Sold', 'Revenue', 'COGS'],
            $rows[0],
            'REPORT_XLSX_OK: the Product Report header row changed.'
        );

        $agg = DB::selectOne(
            "SELECT COALESCE(SUM(d.qty),0) AS units, "
            . "COALESCE(SUM(d.sale_price * d.qty),0) AS revenue, "
            . "COALESCE(SUM(d.purchase_price * d.qty),0) AS cogs "
            . "FROM order_details d JOIN orders o ON o.id = d.order_id "
            . "JOIN products p ON p.id = d.product_id "
            . "WHERE p.status = 1 AND o.order_status IN ('delivered','4')"
        );

        $total = $this->totalRow($rows);
        $this->assertSame((string) (int) $agg->units, $total[5], 'TOTAL Units Sold must equal the independent SQL.');
        $this->assertSame(number_format((float) $agg->revenue, 2, '.', ''), $total[6], 'TOTAL Revenue must equal the independent SQL.');
        $this->assertSame(number_format((float) $agg->cogs, 2, '.', ''), $total[7], 'TOTAL COGS must equal the independent SQL.');

        // One product row, chosen so its name is unique and the sheet row can
        // be matched by name without guessing which duplicate it is.
        $pick = DB::selectOne(
            "SELECT * FROM ("
            . "SELECT p.id, p.name, COALESCE(SUM(d.qty),0) AS units, "
            . "COALESCE(SUM(d.sale_price * d.qty),0) AS revenue, "
            . "COALESCE(SUM(d.purchase_price * d.qty),0) AS cogs "
            . "FROM products p JOIN order_details d ON d.product_id = p.id "
            . "JOIN orders o ON o.id = d.order_id "
            . "WHERE p.status = 1 AND o.order_status IN ('delivered','4') "
            . "GROUP BY p.id, p.name"
            . ") t WHERE (SELECT COUNT(*) FROM products p2 WHERE p2.name = t.name AND p2.status = 1) = 1 "
            . "ORDER BY t.units DESC LIMIT 1"
        );
        $this->assertNotNull($pick, 'The live catalogue has no uniquely named product with delivered sales.');

        $productRow = null;
        foreach (array_slice($rows, 1) as $row) {
            if (($row[1] ?? null) === $pick->name) {
                $productRow = $row;
                break;
            }
        }
        $this->assertNotNull($productRow, 'REPORT_XLSX_OK: the chosen product row is missing from the export.');
        $this->assertSame((string) (int) $pick->units, $productRow[5], 'Product Units Sold must match GROUP BY product_id.');
        $this->assertSame(number_format((float) $pick->revenue, 2, '.', ''), $productRow[6], 'Product Revenue must match GROUP BY product_id.');
        $this->assertSame(number_format((float) $pick->cogs, 2, '.', ''), $productRow[7], 'Product COGS must match GROUP BY product_id.');
    }

    public function test_order_line_xlsx_covers_every_filtered_line(): void
    {
        $response = $this->export(['report' => 'order', 'format' => 'xlsx']);
        $response->assertOk();

        $entries = $this->unzip($response->getContent());
        $rows = $this->sheetRows($entries['xl/worksheets/sheet1.xml']);

        $this->assertSame(
            ['Invoice', 'Customer', 'Phone', 'Product', 'Purchase', 'Sale', 'Qty', 'Total'],
            $rows[0],
            'REPORT_XLSX_OK: the Sales Report line header row changed.'
        );

        $agg = DB::selectOne(
            "SELECT COUNT(*) AS line_count, COALESCE(SUM(d.qty),0) AS qty, "
            . "COALESCE(SUM(d.purchase_price * d.qty),0) AS purchase, "
            . "COALESCE(SUM(d.sale_price * d.qty),0) AS sales "
            . "FROM order_details d JOIN orders o ON o.id = d.order_id "
            . "WHERE o.order_status IN ('delivered','4')"
        );

        $this->assertSame(
            (int) $agg->line_count,
            count($rows) - 2,
            'REPORT_XLSX_OK: the export must contain every filtered line, not page one.'
        );

        $total = $this->totalRow($rows);
        $this->assertSame(number_format((float) $agg->purchase, 2, '.', ''), $total[4], 'TOTAL Purchase must match the independent SQL.');
        $this->assertSame(number_format((float) $agg->sales, 2, '.', ''), $total[5], 'TOTAL Sales must match the independent SQL.');
        $this->assertSame((string) (int) $agg->qty, $total[6], 'TOTAL Qty must match the independent SQL.');
        $this->assertSame(number_format((float) $agg->sales, 2, '.', ''), $total[7], 'TOTAL line Total must match the independent SQL.');
    }

    public function test_order_grain_xlsx_counts_distinct_orders(): void
    {
        $response = $this->export(['report' => 'order', 'format' => 'xlsx', 'view' => 'order']);
        $response->assertOk();

        $entries = $this->unzip($response->getContent());
        $rows = $this->sheetRows($entries['xl/worksheets/sheet1.xml']);

        $this->assertSame(
            ['Invoice', 'Date', 'Customer', 'Lines', 'Qty', 'Sales', 'Discount', 'Shipping', 'Order Total', 'Status', 'Payment', 'Courier'],
            $rows[0],
            'REPORT_XLSX_OK: the Sales Report order header row changed.'
        );

        $agg = DB::selectOne(
            "SELECT COUNT(*) AS orders, COALESCE(SUM(discount),0) AS discount, "
            . "COALESCE(SUM(shipping_charge),0) AS shipping, COALESCE(SUM(amount),0) AS total "
            . "FROM orders WHERE order_status IN ('delivered','4') "
            . "AND id IN (SELECT DISTINCT order_id FROM order_details)"
        );

        $this->assertSame(
            (int) $agg->orders,
            count($rows) - 2,
            'REPORT_XLSX_OK: the order view must print one row per distinct delivered order.'
        );

        $total = $this->totalRow($rows);
        $this->assertSame(number_format((float) $agg->discount, 2, '.', ''), $total[6], 'TOTAL Discount must match the order grain.');
        $this->assertSame(number_format((float) $agg->shipping, 2, '.', ''), $total[7], 'TOTAL Shipping must match the order grain.');
        $this->assertSame(number_format((float) $agg->total, 2, '.', ''), $total[8], 'TOTAL Order Total must match the order grain.');
    }

    public function test_filters_are_applied_to_the_export(): void
    {
        $stock = $this->export([
            'report' => 'stock',
            'format' => 'xlsx',
            'start_date' => '2099-01-01',
        ]);
        $stock->assertOk();
        $stockRows = $this->sheetRows($this->unzip($stock->getContent())['xl/worksheets/sheet1.xml']);

        $this->assertCount(2, $stockRows, 'REPORT_XLSX_OK: a future start_date must leave only header + TOTAL.');
        $stockTotal = $this->totalRow($stockRows);
        $this->assertSame('0', $stockTotal[5], 'A filtered-out Product Report must not keep the unfiltered Units Sold.');
        $this->assertSame('0.00', $stockTotal[6], 'A filtered-out Product Report must not keep the unfiltered Revenue.');

        $order = $this->export([
            'report' => 'order',
            'format' => 'xlsx',
            'start_date' => '2099-01-01',
        ]);
        $order->assertOk();
        $orderRows = $this->sheetRows($this->unzip($order->getContent())['xl/worksheets/sheet1.xml']);

        $this->assertCount(2, $orderRows, 'REPORT_XLSX_OK: a future start_date must leave only header + TOTAL.');
        $orderTotal = $this->totalRow($orderRows);
        $this->assertSame('0.00', $orderTotal[5], 'A filtered-out Sales Report must not keep the unfiltered Sales.');
        $this->assertSame('0', $orderTotal[6], 'A filtered-out Sales Report must not keep the unfiltered Qty.');
    }

    public function test_csv_has_the_same_header_and_row_count(): void
    {
        $xlsx = $this->export(['report' => 'stock', 'format' => 'xlsx']);
        $xlsx->assertOk();
        $xlsxRows = $this->sheetRows($this->unzip($xlsx->getContent())['xl/worksheets/sheet1.xml']);

        $csv = $this->export(['report' => 'stock', 'format' => 'csv']);
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));

        $body = $csv->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV must start with a UTF-8 BOM.');

        $lines = preg_split('/\r\n|\n|\r/', trim(substr($body, 3)));
        $this->assertCount(count($xlsxRows), $lines, 'REPORT_XLSX_OK: CSV and XLSX must carry the same rows.');

        $header = str_getcsv($lines[0]);
        $this->assertSame($xlsxRows[0], $header, 'CSV and XLSX headers must match.');

        $last = str_getcsv($lines[count($lines) - 1]);
        $this->assertSame('TOTAL', $last[0], 'CSV must end with the same TOTAL row.');
    }

    public function test_unknown_format_or_report_is_404(): void
    {
        $this->export(['report' => 'stock', 'format' => 'pdf'])->assertNotFound();
        $this->actingAs($this->admin())
            ->get('/admin/report-export/users?format=xlsx')
            ->assertNotFound();
    }
}
