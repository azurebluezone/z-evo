<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Four analytics reports that the schema already supports.
 *
 * Every figure here is a database aggregate over the existing columns. No new
 * table, no cached copy, and nothing hard-coded: the same queries run on the
 * seeded development baseline and on production.
 *
 * The rules the old reports got wrong, and these keep:
 *   - orders.amount is authoritative revenue (discount is already deducted).
 *   - courier cost comes from courier_shipments.shipping_cost, never from the
 *     raw_payload JSON that the sync overwrites.
 *   - statuses are matched on the stored slug, and the legacy numeric ids are
 *     accepted alongside them.
 */
class ReportAnalyticsController extends Controller
{
    private const DELIVERED = ['delivered', '4'];
    private const CANCELLED = ['cancelled', '5'];

    /* ------------------------------------------------------------------ */
    /* Payment methods                                                     */
    /* ------------------------------------------------------------------ */

    public function paymentMethods(Request $request)
    {
        $range = $this->range($request);

        $base = DB::table('payments');
        if ($range !== null) {
            $base->whereBetween('created_at', $range);
        }

        $statusRows = (clone $base)
            ->select('payment_status', DB::raw('COUNT(*) as rows_count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_status')
            ->get();

        $status = [];
        foreach ($statusRows as $row) {
            $status[strtolower((string) $row->payment_status)] = [
                'count' => (int) $row->rows_count,
                'amount' => (float) $row->total,
            ];
        }

        $totalCount = (clone $base)->count();
        $totalAmount = (float) (clone $base)->sum('amount');

        $metrics = [
            'total_count' => $totalCount,
            'total_amount' => $totalAmount,
            'paid_count' => $status['paid']['count'] ?? 0,
            'paid_amount' => $status['paid']['amount'] ?? 0.0,
            'pending_count' => $status['pending']['count'] ?? 0,
            'pending_amount' => $status['pending']['amount'] ?? 0.0,
            'refunded_count' => $status['refunded']['count'] ?? 0,
            'refunded_amount' => $status['refunded']['amount'] ?? 0.0,
            'cancelled_count' => $status['cancelled']['count'] ?? 0,
            'cancelled_amount' => $status['cancelled']['amount'] ?? 0.0,
        ];

        $metrics['success_rate'] = $totalCount > 0
            ? round($metrics['paid_count'] / $totalCount * 100, 1)
            : 0.0;
        $metrics['avg_paid'] = $metrics['paid_count'] > 0
            ? round($metrics['paid_amount'] / $metrics['paid_count'], 2)
            : 0.0;

        $rows = (clone $base)
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_amount"),
                DB::raw("SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount"),
                DB::raw("SUM(CASE WHEN payment_status = 'refunded' THEN amount ELSE 0 END) as refunded_amount"),
                DB::raw("SUM(CASE WHEN payment_status = 'cancelled' THEN amount ELSE 0 END) as cancelled_amount")
            )
            ->groupBy('payment_method')
            ->orderByDesc('paid_amount')
            ->get()
            ->map(fn ($row) => [
                'method' => (string) $row->payment_method,
                'total_count' => (int) $row->total_count,
                'total_amount' => (float) $row->total_amount,
                'paid_count' => (int) $row->paid_count,
                'paid_amount' => (float) $row->paid_amount,
                'pending_amount' => (float) $row->pending_amount,
                'refunded_amount' => (float) $row->refunded_amount,
                'cancelled_amount' => (float) $row->cancelled_amount,
            ])
            ->all();

        $charts = [];
        $labels = [];
        $series = [];
        foreach ($rows as $row) {
            if ($row['paid_amount'] > 0) {
                $labels[] = $row['method'];
                $series[] = $row['paid_amount'];
            }
        }
        if (count($labels) > 0) {
            $charts[] = [
                'el' => '#paymentMethodChart',
                'type' => 'donut',
                'labels' => $labels,
                'series' => $series,
                'height' => 300,
            ];
        }

        return view('backEnd.reports.analytics.payment', [
            'metrics' => $metrics,
            'rows' => $rows,
            'charts' => $charts,
            'filters' => $this->filters($request),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Courier performance                                                 */
    /* ------------------------------------------------------------------ */

    public function courierPerformance(Request $request)
    {
        $range = $this->range($request);

        $base = DB::table('courier_shipments');
        if ($range !== null) {
            $base->whereBetween('created_at', $range);
        }

        $shipments = (clone $base)->count();
        $cost = (float) (clone $base)->sum('shipping_cost');

        $rows = (clone $base)
            ->select(
                'provider',
                DB::raw('COUNT(*) as shipments'),
                DB::raw("SUM(CASE WHEN LOWER(current_status) = 'delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN LOWER(current_status) IN ('returned','cancelled','failed','return') THEN 1 ELSE 0 END) as returned"),
                DB::raw('SUM(shipping_cost) as cost'),
                DB::raw('MAX(last_synced_at) as last_synced_at')
            )
            ->groupBy('provider')
            ->orderByDesc('shipments')
            ->get()
            ->map(function ($row) {
                $shipments = (int) $row->shipments;
                $delivered = (int) $row->delivered;
                $returned = (int) $row->returned;

                return [
                    'provider' => (string) $row->provider,
                    'shipments' => $shipments,
                    'delivered' => $delivered,
                    'returned' => $returned,
                    'in_transit' => max(0, $shipments - $delivered - $returned),
                    'cost' => (float) $row->cost,
                    'avg_cost' => $shipments > 0 ? round((float) $row->cost / $shipments, 2) : 0.0,
                    'delivery_rate' => $shipments > 0 ? round($delivered / $shipments * 100, 1) : 0.0,
                    'last_synced_at' => $row->last_synced_at,
                ];
            })
            ->all();

        $delivered = array_sum(array_column($rows, 'delivered'));
        $returned = array_sum(array_column($rows, 'returned'));

        $metrics = [
            'shipments' => $shipments,
            'delivered' => $delivered,
            'in_transit' => max(0, $shipments - $delivered - $returned),
            'returned' => $returned,
            'cost' => $cost,
            'avg_cost' => $shipments > 0 ? round($cost / $shipments, 2) : 0.0,
            'delivery_rate' => $shipments > 0 ? round($delivered / $shipments * 100, 1) : 0.0,
        ];

        $events = DB::table('courier_events')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['provider', 'courier_status', 'order_status_slug', 'message', 'created_at']);

        $charts = [];
        if ($shipments > 0) {
            $charts[] = [
                'el' => '#courierChart',
                'type' => 'bar',
                'stacked' => true,
                'labels' => array_column($rows, 'provider'),
                'series' => [
                    ['name' => 'Delivered', 'data' => array_column($rows, 'delivered')],
                    ['name' => 'In transit', 'data' => array_column($rows, 'in_transit')],
                    ['name' => 'Returned', 'data' => array_column($rows, 'returned')],
                ],
                'height' => 300,
            ];
        }

        return view('backEnd.reports.analytics.courier', [
            'metrics' => $metrics,
            'rows' => $rows,
            'events' => $events,
            'charts' => $charts,
            'filters' => $this->filters($request),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Returns and cancellations                                           */
    /* ------------------------------------------------------------------ */

    public function returnsReport(Request $request)
    {
        $range = $this->range($request);

        $orders = DB::table('orders');
        if ($range !== null) {
            $orders->whereBetween('created_at', $range);
        }

        $orderCount = (clone $orders)->count();
        $deliveredCount = (clone $orders)->whereIn('order_status', self::DELIVERED)->count();
        $cancelledCount = (clone $orders)->whereIn('order_status', self::CANCELLED)->count();
        $cancelledAmount = (float) (clone $orders)->whereIn('order_status', self::CANCELLED)->sum('amount');

        $base = DB::table('order_returns');
        if ($range !== null) {
            $base->whereBetween('created_at', $range);
        }

        $returnCount = (clone $base)->count();
        $refundRequired = (float) (clone $base)->where('refund_required', 1)->sum('refund_amount');
        $refunded = (float) (clone $base)->where('refunded', 1)->sum('refund_amount');

        $metrics = [
            'order_count' => $orderCount,
            'delivered_count' => $deliveredCount,
            'return_count' => $returnCount,
            'return_rate' => $orderCount > 0 ? round($returnCount / $orderCount * 100, 1) : 0.0,
            'refund_required' => $refundRequired,
            'refunded' => $refunded,
            'refund_pending' => max(0.0, $refundRequired - $refunded),
            'restocked' => (clone $base)->where('restocked', 1)->count(),
            'damaged' => (clone $base)->where('restock_condition', 'damaged')->count(),
            'cancelled_count' => $cancelledCount,
            'cancelled_amount' => $cancelledAmount,
            'cancel_rate' => $orderCount > 0 ? round($cancelledCount / $orderCount * 100, 1) : 0.0,
        ];

        $rows = (clone $base)
            ->leftJoin('orders as o', 'o.id', '=', 'order_returns.order_id')
            ->select(
                'order_returns.id',
                'order_returns.order_id',
                'order_returns.status',
                'order_returns.return_mode',
                'order_returns.reason',
                'order_returns.restock_condition',
                'order_returns.restocked',
                'order_returns.refund_required',
                'order_returns.refunded',
                'order_returns.refund_amount',
                'order_returns.created_at',
                'o.invoice_id',
                'o.amount as order_amount'
            )
            ->orderByDesc('order_returns.created_at')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'invoice_id' => (string) ($row->invoice_id ?? ''),
                'status' => (string) $row->status,
                'return_mode' => (string) $row->return_mode,
                'reason' => (string) ($row->reason ?? ''),
                'restock_condition' => (string) ($row->restock_condition ?? ''),
                'restocked' => (int) $row->restocked,
                'refund_required' => (int) $row->refund_required,
                'refunded' => (int) $row->refunded,
                'refund_amount' => (float) $row->refund_amount,
                'created_at' => $row->created_at,
            ])
            ->all();

        $items = DB::table('order_return_items as i')
            ->leftJoin('order_details as d', 'd.id', '=', 'i.order_detail_id')
            ->leftJoin('order_returns as r', 'r.id', '=', 'i.order_return_id')
            ->select(
                'i.qty',
                'i.refund_amount',
                'i.restock_bucket',
                'd.product_name',
                'd.product_size',
                'd.product_color',
                'r.id as return_id'
            )
            ->orderBy('i.id')
            ->get();

        $charts = [];
        if ($refundRequired > 0 || $refunded > 0) {
            $charts[] = [
                'el' => '#refundChart',
                'type' => 'donut',
                'labels' => ['Refunded', 'Pending'],
                'series' => [$refunded, max(0.0, $refundRequired - $refunded)],
                'height' => 280,
            ];
        }

        return view('backEnd.reports.analytics.returns', [
            'metrics' => $metrics,
            'rows' => $rows,
            'items' => $items,
            'charts' => $charts,
            'filters' => $this->filters($request),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Coupon impact                                                       */
    /* ------------------------------------------------------------------ */

    public function couponImpact(Request $request)
    {
        $range = $this->range($request);

        // Usage grain: one row per coupon use. Do not join orders here, or an
        // order with two usages multiplies every order-level sum below.
        $base = DB::table('coupon_usages as u');
        if ($range !== null) {
            $base->whereBetween('u.created_at', $range);
        }

        $usageCount = (clone $base)->count();
        $totalDiscount = (float) (clone $base)->sum('u.discount_amount');
        $linkedOrders = (clone $base)->whereNotNull('u.order_id')->distinct()->count('u.order_id');
        $checkoutUsages = (clone $base)->where('u.source', 'checkout')->count();
        $posUsages = (clone $base)->where('u.source', 'pos')->count();
        $unlinked = (clone $base)->whereNull('u.order_id')->count();

        // Order grain: the metric dictionary defines coupon revenue as the
        // amount of each distinct order, counted once no matter how many
        // coupon rows that order has.
        $revenueOrderIds = (clone $base)
            ->whereNotNull('u.order_id')
            ->select('u.order_id')
            ->distinct();

        $revenue = (float) DB::table('orders')
            ->whereIn('id', $revenueOrderIds)
            ->sum('amount');

        $metrics = [
            'usage_count' => $usageCount,
            'coupon_count' => (clone $base)->distinct()->count('u.coupon_code'),
            'total_discount' => $totalDiscount,
            'linked_orders' => $linkedOrders,
            'revenue' => $revenue,
            'avg_discount' => $usageCount > 0 ? round($totalDiscount / $usageCount, 2) : 0.0,
            'avg_order_value' => $linkedOrders > 0 ? round($revenue / $linkedOrders, 2) : 0.0,
            'checkout_usages' => $checkoutUsages,
            'pos_usages' => $posUsages,
            'unlinked' => $unlinked,
        ];

        // Revenue per coupon code: one amount per (coupon_code, order_id),
        // so two rows of the same code on one order still count once.
        $revenueRows = (clone $base)
            ->whereNotNull('u.order_id')
            ->select('u.coupon_code', 'u.order_id')
            ->distinct();

        $revenueByCode = DB::query()
            ->fromSub($revenueRows, 'uo')
            ->join('orders as o', 'o.id', '=', 'uo.order_id')
            ->select('uo.coupon_code', DB::raw('SUM(o.amount) as revenue'))
            ->groupBy('uo.coupon_code')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->coupon_code => (float) $row->revenue])
            ->all();

        $rows = (clone $base)
            ->leftJoin('coupons as c', 'c.code', '=', 'u.coupon_code')
            ->select(
                'u.coupon_code',
                DB::raw('COUNT(*) as usages'),
                DB::raw('SUM(u.discount_amount) as discount'),
                DB::raw('COUNT(DISTINCT u.order_id) as orders'),
                DB::raw("SUM(CASE WHEN u.source = 'checkout' THEN 1 ELSE 0 END) as checkout_usages"),
                DB::raw("SUM(CASE WHEN u.source = 'pos' THEN 1 ELSE 0 END) as pos_usages"),
                DB::raw('MAX(c.type) as type'),
                DB::raw('MAX(c.value) as value'),
                DB::raw('MAX(c.usage_limit) as usage_limit'),
                DB::raw('MAX(c.used_count) as used_count'),
                DB::raw('MAX(c.status) as status')
            )
            ->groupBy('u.coupon_code')
            ->orderByDesc('discount')
            ->get()
            ->map(fn ($row) => [
                'code' => (string) $row->coupon_code,
                'usages' => (int) $row->usages,
                'discount' => (float) $row->discount,
                'orders' => (int) $row->orders,
                'revenue' => (float) ($revenueByCode[$row->coupon_code] ?? 0),
                'checkout_usages' => (int) $row->checkout_usages,
                'pos_usages' => (int) $row->pos_usages,
                'type' => $row->type !== null ? (string) $row->type : '',
                'value' => $row->value !== null ? (float) $row->value : null,
                'usage_limit' => $row->usage_limit !== null ? (int) $row->usage_limit : null,
                'used_count' => $row->used_count !== null ? (int) $row->used_count : null,
                'status' => $row->status !== null ? (int) $row->status : null,
            ])
            ->all();

        $charts = [];
        if (count($rows) > 0) {
            $charts[] = [
                'el' => '#couponChart',
                'type' => 'bar',
                'labels' => array_column($rows, 'code'),
                'series' => [
                    ['name' => 'Discount given', 'data' => array_column($rows, 'discount')],
                ],
                'height' => 300,
            ];
        }

        return view('backEnd.reports.analytics.coupon', [
            'metrics' => $metrics,
            'rows' => $rows,
            'charts' => $charts,
            'filters' => $this->filters($request),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Shared helpers                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Both dates must be present, otherwise the report covers everything.
     */
    private function range(Request $request): ?array
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        if (! $start || ! $end) {
            return null;
        }

        try {
            return [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function filters(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];
    }
}
