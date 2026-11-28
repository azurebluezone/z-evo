<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Stock alerts and the inventory ledger.
 *
 * One source of truth: variant_stocks for the balance and inventory_movements
 * for the history. products.stock is a dropped legacy column and is never
 * read here.
 *
 * The thresholds come from ReportSetting (the Settings form) and from the
 * per-variant `low_stock_threshold`:
 *   effective low line = variant threshold when it is > 0, else stock_warning_threshold
 *   effective remaining line = max(stock_remaining_threshold, effective low line)
 *   available <= 0                                   -> out
 *   0 < available <= effective low line              -> low
 *   low line < available <= effective remaining line -> remaining ("only N left")
 *   above                                            -> healthy
 *
 * This page, the navbar signal, the dashboard and the email all classify a
 * variant the same way.
 *
 * Rows whose variant no longer exists are counted as orphan and shown instead
 * of being silently dropped by an inner join.
 */
class ReportStockAlertController extends Controller
{
    public function index(Request $request)
    {
        $rows = DB::table('variant_stocks as s')
            ->leftJoin('product_variants as v', 'v.id', '=', 's.variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->select(
                's.variant_id',
                's.on_hand',
                's.reserved',
                's.available',
                's.damaged',
                's.low_stock_threshold',
                'v.sku',
                'v.title',
                'p.name as product_name'
            )
            ->orderBy('s.available')
            ->orderBy('s.variant_id')
            ->get();

        $metrics = [
            'total' => 0,
            'out' => 0,
            'low' => 0,
            'remaining' => 0,
            'healthy' => 0,
            'orphans' => 0,
            'on_hand' => 0,
            'reserved' => 0,
            'available' => 0,
            'damaged' => 0,
        ];

        $list = [];

        $settings = ReportSetting::current();
        $warning = (int) $settings->stock_warning_threshold;
        $remainingThreshold = (int) $settings->stock_remaining_threshold;

        // Variant velocity (docs/report-metrics.md, "Stock Alerts velocity
        // rule"): delivered sales only, on orders.delivered_at, ONE aggregate
        // for every variant instead of a query per row. Lines without a
        // variant_id cannot be attributed to a variant and are excluded.
        $velocityFrom = now()->subDays(30);
        $velocity = DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereIn('o.order_status', ['delivered', '4'])
            ->whereNotNull('d.variant_id')
            ->groupBy('d.variant_id')
            ->selectRaw('d.variant_id, MAX(o.delivered_at) AS last_sold_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN o.delivered_at >= ? THEN d.qty ELSE 0 END), 0) AS units_30d', [$velocityFrom])
            ->get()
            ->keyBy('variant_id');

        foreach ($rows as $row) {
            $available = (int) $row->available;
            $variantThreshold = (int) $row->low_stock_threshold;

            // Per-variant threshold wins when it is set (> 0); otherwise the
            // global setting applies. The "remaining" band can never sit below
            // the effective low-stock line, so the buckets stay ordered
            // (docs/report-metrics.md 3.7).
            $threshold = $variantThreshold > 0 ? $variantThreshold : $warning;
            $remainingBand = max($remainingThreshold, $threshold);

            $sale = $velocity->get($row->variant_id);
            $units30 = (int) ($sale->units_30d ?? 0);
            $avgDaily = $units30 / 30;
            $daysRemaining = $avgDaily > 0 ? max(0, (int) floor($available / $avgDaily)) : null;

            if ($row->sku === null) {
                $status = 'orphan';
                $metrics['orphans']++;
            } elseif ($available <= 0) {
                $status = 'out';
                $metrics['out']++;
            } elseif ($available <= $threshold) {
                $status = 'low';
                $metrics['low']++;
            } elseif ($available <= $remainingBand) {
                $status = 'remaining';
                $metrics['remaining']++;
            } else {
                $status = 'healthy';
                $metrics['healthy']++;
            }

            $metrics['total']++;
            $metrics['on_hand'] += (int) $row->on_hand;
            $metrics['reserved'] += (int) $row->reserved;
            $metrics['available'] += $available;
            $metrics['damaged'] += (int) $row->damaged;

            $list[] = [
                'variant_id' => (int) $row->variant_id,
                'sku' => (string) ($row->sku ?? ''),
                'title' => (string) ($row->title ?? ''),
                'product_name' => (string) ($row->product_name ?? ''),
                'on_hand' => (int) $row->on_hand,
                'reserved' => (int) $row->reserved,
                'available' => $available,
                'damaged' => (int) $row->damaged,
                'threshold' => $threshold,
                'status' => $status,
                'last_sold_at' => $sale->last_sold_at ?? null,
                'units_30d' => $units30,
                'avg_daily' => $avgDaily,
                'days_remaining' => $daysRemaining,
            ];
        }

        $statusFilter = (string) $request->input('status', 'all');
        if (in_array($statusFilter, ['out', 'low', 'remaining', 'orphan'], true)) {
            $list = array_values(array_filter($list, fn ($row) => $row['status'] === $statusFilter));
        } else {
            $statusFilter = 'all';
        }

        $movements = DB::table('inventory_movements as m')
            ->leftJoin('product_variants as v', 'v.id', '=', 'm.variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->orderByDesc('m.created_at')
            ->orderByDesc('m.id')
            ->limit(20)
            ->get([
                'm.type',
                'm.bucket',
                'm.qty_delta',
                'm.ref_type',
                'm.ref_id',
                'm.note',
                'm.created_at',
                'p.name as product_name',
                'v.sku',
            ]);

        $movementSummary = DB::table('inventory_movements')
            ->select(
                'type',
                DB::raw('COUNT(*) as rows_count'),
                DB::raw('SUM(qty_delta) as net_qty')
            )
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        $charts = [];
        if ($metrics['total'] > 0) {
            $charts[] = [
                'el' => '#stockStatusChart',
                'type' => 'donut',
                'labels' => ['Healthy', 'Remaining', 'Low', 'Out', 'Orphan'],
                'series' => [$metrics['healthy'], $metrics['remaining'], $metrics['low'], $metrics['out'], $metrics['orphans']],
                'height' => 280,
            ];
        }

        return view('backEnd.reports.analytics.stock-alerts', [
            'metrics' => $metrics,
            'rows' => $list,
            'movements' => $movements,
            'movementSummary' => $movementSummary,
            'charts' => $charts,
            'statusFilter' => $statusFilter,
            'warning' => $warning,
            'remainingThreshold' => $remainingThreshold,
        ]);
    }
}
