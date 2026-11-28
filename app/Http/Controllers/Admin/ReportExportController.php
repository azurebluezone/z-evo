<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsReportQueries;
use App\Http\Controllers\Admin\Concerns\WritesXlsxFiles;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Download endpoint for the Product Report and the Sales Report.
 *
 * Both pages used to serialise the visible <table> to CSV in the browser, so
 * the "Excel" button downloaded a .csv and only ever contained page one. This
 * controller reads the same BuildsReportQueries source as the pages, applies
 * the same filters, ignores pagination and writes a real XLSX (or a CSV when
 * asked). The filter set is the page's query string; `page` is not a filter.
 */
class ReportExportController extends Controller
{
    use BuildsReportQueries;
    use WritesXlsxFiles;

    public function download(Request $request, string $report)
    {
        abort_unless(in_array($report, ['stock', 'order'], true), 404);

        $format = strtolower((string) $request->query('format', 'xlsx'));
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        if ($report === 'stock') {
            [$filename, $headers, $rows] = $this->stockSheet($request);
        } else {
            $view = $request->query('view') === 'order' ? 'order' : 'line';
            [$filename, $headers, $rows] = $view === 'order'
                ? $this->orderGrainSheet($request)
                : $this->orderLineSheet($request);
        }

        $filename .= '-' . now()->format('Y-m-d') . '.' . $format;

        if ($format === 'csv') {
            return $this->csvResponse($filename, $headers, $rows);
        }

        return $this->xlsxResponse(
            $filename,
            $headers,
            $rows,
            $report === 'stock' ? 'Product Report' : 'Sales Report'
        );
    }

    private function stockSheet(Request $request): array
    {
        $rows = $this->reportStockRows($request)->get();
        $this->castReportStockRows($rows);

        $inventory = $this->reportStockTotals($request);
        $sales = $this->reportStockSalesTotals($request);

        $headers = ['SL', 'Product Name', 'Avg. Price', 'Stock', 'Stock Value', 'Units Sold', 'Revenue', 'COGS'];
        $data = [];
        $sl = 0;

        foreach ($rows as $product) {
            $data[] = [
                ++$sl,
                (string) $product->name,
                (float) ($product->unit_price ?? $product->new_price),
                (int) $product->stock_on_hand,
                (float) $product->stock_value,
                (int) $product->units_sold,
                (float) $product->revenue,
                (float) $product->cogs,
            ];
        }

        $data[] = [
            'TOTAL',
            '',
            '',
            $inventory['units'],
            $inventory['stock_value'],
            $sales['units_sold'],
            $sales['revenue'],
            $sales['cogs'],
        ];

        return ['stock-report', $headers, $data];
    }

    private function orderLineSheet(Request $request): array
    {
        $lines = $this->reportOrderLineQuery($request)->get();
        $totals = $this->reportOrderLineTotals($this->reportOrderLineQuery($request));

        $headers = ['Invoice', 'Customer', 'Phone', 'Product', 'Purchase', 'Sale', 'Qty', 'Total'];
        $data = [];

        foreach ($lines as $line) {
            $data[] = [
                (string) ($line->order?->invoice_id ?? ''),
                (string) ($line->shipping?->name ?? ''),
                (string) ($line->shipping?->phone ?? ''),
                (string) $line->product_name,
                (float) $line->purchase_price,
                (float) $line->sale_price,
                (int) $line->qty,
                (float) $line->qty * (float) $line->sale_price,
            ];
        }

        $data[] = [
            'TOTAL',
            '',
            '',
            '',
            $totals['purchase'],
            $totals['sales'],
            $totals['items'],
            $totals['sales'],
        ];

        return ['order-report-lines', $headers, $data];
    }

    private function orderGrainSheet(Request $request): array
    {
        $lineQuery = $this->reportOrderLineQuery($request);
        $orderIds = (clone $lineQuery)->toBase()->distinct()->pluck('order_details.order_id')->all();
        $entries = $this->reportOrderGrainRows($lineQuery, $orderIds);
        $summary = $this->reportOrderSummary($orderIds);

        $headers = ['Invoice', 'Date', 'Customer', 'Lines', 'Qty', 'Sales', 'Discount', 'Shipping', 'Order Total', 'Status', 'Payment', 'Courier'];
        $data = [];
        $lineCount = 0;
        $qty = 0;
        $sales = 0.0;

        foreach ($entries as $entry) {
            $order = $entry->row;
            $data[] = [
                (string) $order->invoice_id,
                \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
                (string) ($entry->shipping->name ?? '-'),
                (int) $entry->line_count,
                (int) $entry->qty,
                (float) $entry->sales,
                (float) $order->discount,
                (float) $order->shipping_charge,
                (float) $order->amount,
                (string) $entry->status,
                (string) $entry->payment,
                (string) $entry->courier,
            ];
            $lineCount += (int) $entry->line_count;
            $qty += (int) $entry->qty;
            $sales += (float) $entry->sales;
        }

        $data[] = [
            'TOTAL',
            '',
            '',
            $lineCount,
            $qty,
            $sales,
            $summary['order_discount'],
            $summary['shipping'],
            $summary['order_total'],
            '',
            '',
            '',
        ];

        return ['order-report-orders', $headers, $data];
    }

    private function csvResponse(string $filename, array $headers, array $rows)
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            abort(500, 'Could not build the CSV export.');
        }

        // UTF-8 BOM so Excel opens the file with the right encoding.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function xlsxResponse(string $filename, array $headers, array $rows, string $sheetName)
    {
        $bytes = $this->buildXlsx($headers, $rows, $sheetName);

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }
}
