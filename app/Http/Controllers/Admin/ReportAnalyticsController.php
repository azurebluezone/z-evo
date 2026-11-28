<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsGeoBreakdown;
use App\Http\Controllers\Controller;
use App\Models\ReportView;
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
    use BuildsGeoBreakdown;

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
                DB::raw("SUM(CASE WHEN payment_status = 'cancelled' THEN amount ELSE 0 END) as cancelled_amount"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN gateway_fee ELSE 0 END) as fee"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN settled_amount ELSE 0 END) as settled"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN amount - gateway_fee - settled_amount ELSE 0 END) as gap")
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
                'fee' => round((float) $row->fee, 2),
                'settled' => round((float) $row->settled, 2),
                'gap' => round((float) $row->gap, 2),
            ])
            ->all();

        // Collected - gateway fee - settled, per method and for the range.
        $metrics['fee'] = round((float) array_sum(array_column($rows, 'fee')), 2);
        $metrics['settled'] = round((float) array_sum(array_column($rows, 'settled')), 2);
        $metrics['settlement_gap'] = round((float) array_sum(array_column($rows, 'gap')), 2);

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
            'savedViews' => ReportView::forPage('payments', auth()->id()),
            'filters' => $this->filters($request),
            'settlementTargets' => DB::table('payments')
                ->where('payment_status', 'paid')
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'order_id', 'amount', 'payment_method', 'settlement_status']),
        ]);
    }

    /**
     * Record one settlement for a payment.
     *
     * The entry is a row in payment_settlements; the parent payment's
     * settled_amount and gateway_fee are then recomputed from the settlement
     * rows, so the payment row is a projection and is never edited by hand.
     * The report reads payments.gateway_fee / settled_amount, which keeps the
     * metric dictionary's source columns authoritative.
     */
    public function storePaymentSettlement(Request $request)
    {
        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'settled_amount' => ['required', 'numeric', 'min:0'],
            'gateway_fee' => ['nullable', 'numeric', 'min:0'],
            'settled_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:191'],
        ]);

        $paymentId = (int) $data['payment_id'];
        $settledAmount = round((float) $data['settled_amount'], 2);
        $gatewayFee = round((float) ($data['gateway_fee'] ?? 0), 2);
        $settledAt = $data['settled_at'] ?? now()->toDateString();
        $reference = $data['reference'] ?? null;

        DB::transaction(function () use ($paymentId, $settledAmount, $gatewayFee, $settledAt, $reference, $data): void {
            $payment = DB::table('payments')->where('id', $paymentId)->lockForUpdate()->first();
            abort_if($payment === null, 404, 'The payment does not exist.');

            DB::table('payment_settlements')->insert([
                'payment_id' => $paymentId,
                'settled_amount' => $settledAmount,
                'gateway_fee' => $gatewayFee,
                'status' => 'settled',
                'settled_at' => $settledAt,
                'reference' => $reference,
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totals = DB::table('payment_settlements')
                ->where('payment_id', $paymentId)
                ->selectRaw('SUM(settled_amount) as settled, SUM(gateway_fee) as fee, MAX(settled_at) as last_settled')
                ->first();

            DB::table('payments')->where('id', $paymentId)->update([
                'settled_amount' => (float) $totals->settled,
                'gateway_fee' => (float) $totals->fee,
                'settlement_status' => 'settled',
                'settled_at' => $totals->last_settled,
                'settlement_reference' => $reference,
                'updated_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Payment settlement recorded.');
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
                DB::raw("SUM(CASE WHEN status_normalized = 'delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN status_normalized IN ('returned','cancelled') THEN 1 ELSE 0 END) as returned"),
                DB::raw("SUM(CASE WHEN status_normalized = 'in_transit' THEN 1 ELSE 0 END) as in_transit"),
                DB::raw('SUM(shipping_cost) as cost'),
                DB::raw('SUM(cod_amount - remitted_amount) as cod_outstanding'),
                DB::raw('SUM(cod_amount) as cod_expected'),
                DB::raw('SUM(remitted_amount) as remitted'),
                DB::raw('SUM(return_charge) as return_charge'),
                DB::raw('SUM(cod_amount - remitted_amount - return_charge) as shortfall'),
                DB::raw("SUM(CASE WHEN settlement_status IS NULL OR settlement_status NOT IN ('pending','remitted','settled') THEN 1 ELSE 0 END) as settlement_unknown"),
                DB::raw('MAX(last_synced_at) as last_synced_at')
            )
            ->groupBy('provider')
            ->orderByDesc('shipments')
            ->get()
            ->map(function ($row) {
                $shipments = (int) $row->shipments;
                $delivered = (int) $row->delivered;
                $returned = (int) $row->returned;
                $inTransit = (int) $row->in_transit;

                return [
                    'provider' => (string) $row->provider,
                    'shipments' => $shipments,
                    'delivered' => $delivered,
                    'returned' => $returned,
                    'in_transit' => $inTransit,
                    'cost' => (float) $row->cost,
                    'cod_outstanding' => round((float) $row->cod_outstanding, 2),
                    'cod_expected' => round((float) $row->cod_expected, 2),
                    'remitted' => round((float) $row->remitted, 2),
                    'return_charge' => round((float) $row->return_charge, 2),
                    'shortfall' => round((float) $row->shortfall, 2),
                    'settlement_unknown' => (int) $row->settlement_unknown,
                    'avg_cost' => $shipments > 0 ? round((float) $row->cost / $shipments, 2) : 0.0,
                    'delivery_rate' => $shipments > 0 ? round($delivered / $shipments * 100, 1) : 0.0,
                    'last_synced_at' => $row->last_synced_at,
                ];
            })
            ->all();

        $delivered = array_sum(array_column($rows, 'delivered'));
        $returned = array_sum(array_column($rows, 'returned'));
        $inTransit = array_sum(array_column($rows, 'in_transit'));
        $codOutstanding = round((float) array_sum(array_column($rows, 'cod_outstanding')), 2);
        $settlementUnknown = (int) array_sum(array_column($rows, 'settlement_unknown'));

        $metrics = [
            'shipments' => $shipments,
            'delivered' => $delivered,
            'in_transit' => $inTransit,
            'returned' => $returned,
            'cost' => $cost,
            'cod_outstanding' => $codOutstanding,
            'cod_expected' => round((float) array_sum(array_column($rows, 'cod_expected')), 2),
            'remitted' => round((float) array_sum(array_column($rows, 'remitted')), 2),
            'return_charge' => round((float) array_sum(array_column($rows, 'return_charge')), 2),
            'courier_shortfall' => round((float) array_sum(array_column($rows, 'shortfall')), 2),
            'settlement_unknown' => $settlementUnknown,
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

        // Same courier_shipments.created_at base as the totals above.
        $geo = $this->geoBreakdown(
            'courier_shipments',
            'order_id',
            'created_at',
            $range,
            'COUNT(*)',
            [
                'delivered' => "SUM(CASE WHEN t.status_normalized = 'delivered' THEN 1 ELSE 0 END)",
                'cost' => 'SUM(t.shipping_cost)',
            ]
        );

        return view('backEnd.reports.analytics.courier', [
            'metrics' => $metrics,
            'rows' => $rows,
            'events' => $events,
            'charts' => $charts,
            'geo' => $geo,
            'geoTotal' => round((float) $geo->sum('cost'), 2),
            'savedViews' => ReportView::forPage('couriers', auth()->id()),
            'filters' => $this->filters($request),
            'settlementTargets' => DB::table('courier_shipments')
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'order_id', 'provider', 'cod_amount', 'remitted_amount']),
        ]);
    }

    /**
     * Record one courier remittance for a shipment.
     *
     * The entry is a row in courier_settlements; the matching shipment's
     * remitted_amount is then recomputed from the settlement rows, so the
     * shipment row is a projection and the shortfall moves without a manual
     * edit. settlement_status is left alone: the report already surfaces an
     * unknown value instead of guessing one.
     */
    public function storeCourierSettlement(Request $request)
    {
        $data = $request->validate([
            'shipment_id' => ['required', 'integer', 'exists:courier_shipments,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'remitted_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:191'],
        ]);

        $shipmentId = (int) $data['shipment_id'];
        $amount = round((float) $data['amount'], 2);
        $remittedAt = $data['remitted_at'] ?? now()->toDateString();
        $reference = $data['reference'] ?? null;

        DB::transaction(function () use ($shipmentId, $amount, $remittedAt, $reference, $data): void {
            $shipment = DB::table('courier_shipments')->where('id', $shipmentId)->lockForUpdate()->first();
            abort_if($shipment === null, 404, 'The shipment does not exist.');

            DB::table('courier_settlements')->insert([
                'order_id' => $shipment->order_id,
                'provider' => $shipment->provider,
                'consignment_id' => $shipment->consignment_id,
                'amount' => $amount,
                'status' => 'settled',
                'remitted_at' => $remittedAt,
                'reference' => $reference,
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $remitted = (float) DB::table('courier_settlements')
                ->where('order_id', $shipment->order_id)
                ->where('provider', $shipment->provider)
                ->sum('amount');

            DB::table('courier_shipments')->where('id', $shipmentId)->update([
                'remitted_amount' => round($remitted, 2),
                'updated_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Courier remittance recorded.');
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
            $base->whereBetween('order_returns.created_at', $range);
        }

        $returnCount = (clone $base)->distinct()->count('order_id');
        $refundRequired = (float) (clone $base)->where('refund_required', 1)->sum('refund_amount');
        $refunded = (float) (clone $base)->where('refunded', 1)->sum('refund_amount');

        // Item-level return rate: returned units / units placed in the same
        // range. The numerator follows the return's created_at (the return
        // event) and the denominator follows the parent order's created_at,
        // the same basis the order-level rate uses (docs/report-metrics.md
        // 3.5). One return row can carry several items; qty is the unit.
        $returnedUnits = (int) DB::table('order_return_items as i')
            ->join('order_returns as r', 'r.id', '=', 'i.order_return_id')
            ->when($range !== null, fn ($query) => $query->whereBetween('r.created_at', $range))
            ->sum('i.qty');

        $soldUnits = (int) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->when($range !== null, fn ($query) => $query->whereBetween('o.created_at', $range))
            ->sum('d.qty');

        $metrics = [
            'order_count' => $orderCount,
            'delivered_count' => $deliveredCount,
            'return_count' => $returnCount,
            'return_rate' => $orderCount > 0 ? round($returnCount / $orderCount * 100, 1) : 0.0,
            'returned_units' => $returnedUnits,
            'sold_units' => $soldUnits,
            'item_return_rate' => $soldUnits > 0 ? round($returnedUnits / $soldUnits * 100, 1) : 0.0,
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
            ->when($range !== null, fn ($query) => $query->whereBetween('r.created_at', $range))
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

        // Same order_returns.created_at base as the refund totals above.
        $geo = $this->geoBreakdown(
            'order_returns',
            'order_id',
            'created_at',
            $range,
            'COUNT(*)',
            [
                'refund_required' => 'SUM(CASE WHEN t.refund_required = 1 THEN t.refund_amount ELSE 0 END)',
                'refunded' => 'SUM(CASE WHEN t.refunded = 1 THEN t.refund_amount ELSE 0 END)',
            ]
        );

        return view('backEnd.reports.analytics.returns', [
            'metrics' => $metrics,
            'rows' => $rows,
            'items' => $items,
            'charts' => $charts,
            'geo' => $geo,
            'geoTotal' => round((float) $geo->sum('refund_required'), 2),
            'savedViews' => ReportView::forPage('returns', auth()->id()),
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
            'savedViews' => ReportView::forPage('coupons', auth()->id()),
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
