<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Every number the dashboard, the final report and the daily email need.
 *
 * One service so the three surfaces cannot disagree. Each date column is
 * chosen deliberately and named in the UI:
 *   revenue / COGS  -> orders.delivered_at (a delivered order has the date)
 *   courier cost    -> courier_shipments.created_at (no delivered_at column)
 *   refunds         -> order_returns.created_at (no refunded_at column)
 *   payments        -> payments.created_at
 * Revenue is orders.amount, which is already net of the discount, so the
 * discount is never subtracted a second time.
 */
class ReportMetricsService
{
    public function __construct(private StockAlertSignal $signal)
    {
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function range(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::today();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay();

        if ($start->greaterThan($end)) {
            $swap = $start->copy();
            $start = $end->copy()->startOfDay();
            $end = $swap->copy()->endOfDay();
        }

        $label = $start->isSameDay($end)
            ? $start->format('d M Y')
            : $start->format('d M Y').' - '.$end->format('d M Y');

        return [$start, $end, $label];
    }

    /**
     * The scalar KPIs for one range, in one place, so the current period and
     * the previous period can never be computed with two different formulas.
     *
     * Date bases follow the metric dictionary (docs/report-metrics.md):
     *   delivered revenue / COGS / AOV -> orders.delivered_at
     *   placed orders / product sales  -> orders.created_at (parent order)
     *   courier / refunds / payments   -> their own created_at
     *
     * @return array<string, float|int>
     */
    private function kpiTotals(Carbon $from, Carbon $to): array
    {
        $delivered = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to]);

        $orderIds = (clone $delivered)->pluck('id')->all();
        $deliveredCount = count($orderIds);
        $revenue = (float) (clone $delivered)->sum('amount');
        $shippingRevenue = (float) (clone $delivered)->sum('shipping_charge');

        // AOV = Net Sales / Delivered Orders (dictionary 3.1). Net Sales is
        // delivered revenue minus the shipping the customer paid.
        $netSales = $revenue - $shippingRevenue;
        $aov = $deliveredCount > 0 ? $netSales / $deliveredCount : 0.0;

        $cogs = 0.0;
        if ($orderIds !== []) {
            $cogs = (float) DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->sum(DB::raw('purchase_price * qty'));
        }

        // Gross Product Sales: order lines whose PARENT ORDER was created in
        // the range, every status. The join is to the parent (one row per
        // order), so the order-line grain is preserved and no line is counted
        // twice (dictionary 1.6 and 3.1).
        $productSales = (float) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->sum(DB::raw('d.sale_price * d.qty'));

        $courierCost = (float) DB::table('courier_shipments')
            ->whereBetween('created_at', [$from, $to])
            ->sum('shipping_cost');

        $refundPaid = (float) DB::table('order_returns')
            ->where('refunded', 1)
            ->whereBetween('created_at', [$from, $to])
            ->sum('refund_amount');

        // Operating expenses are a date column, not a timestamp: an expense
        // belongs to the day it was spent. Dictionary 3.2.
        $businessExpenses = (float) DB::table('business_expenses')
            ->whereDate('spent_at', '>=', $from->toDateString())
            ->whereDate('spent_at', '<=', $to->toDateString())
            ->sum('amount');

        // Gross Profit is strictly Net Sales - COGS. Net Profit is the same
        // number minus every other cost, so the two names never describe the
        // same total (dictionary 3.2).
        $grossProfit = $netSales - $cogs;
        $expense = $cogs + $courierCost + $refundPaid + $businessExpenses;
        $profit = $revenue - $expense;

        $paid = (float) DB::table('payments')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $pending = (float) DB::table('payments')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $placed = DB::table('orders')->whereBetween('created_at', [$from, $to]);
        $placedCount = (clone $placed)->count();
        $placedAmount = (float) (clone $placed)->sum('amount');

        // Payment still owed on the orders placed in the range.
        $placedPendingPayment = (float) DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('p.payment_status', 'pending')
            ->whereBetween('o.created_at', [$from, $to])
            ->sum('p.amount');

        return [
            'delivered_count' => $deliveredCount,
            'revenue' => $revenue,
            'net_sales' => $netSales,
            'product_sales' => $productSales,
            'aov' => $aov,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'courier_cost' => $courierCost,
            'refund_paid' => $refundPaid,
            'business_expenses' => $businessExpenses,
            'expense' => $expense,
            'profit' => $profit,
            'paid' => $paid,
            'pending' => $pending,
            'placed_count' => $placedCount,
            'placed_amount' => $placedAmount,
            'placed_pending_payment' => $placedPendingPayment,
        ];
    }

    /**
     * The five products that earned most in the range, at the order-line
     * grain and on the parent order's created_at - the same basis as the
     * Gross Product Sales KPI, so the table adds up to no more than that
     * card (docs/report-metrics.md 3.1, "Top Products").
     *
     * Ties are broken by sales desc, then units desc, then product_id asc so
     * two products with the same sales cannot swap rows between renders.
     *
     * @return array<int, array{product_id: int, name: string, units: int, sales: float}>
     */
    private function topProducts(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->groupBy('d.product_id')
            ->orderByDesc('sales')
            ->orderByDesc('units')
            ->orderBy('d.product_id')
            ->limit(5)
            ->get([
                'd.product_id',
                DB::raw('MAX(d.product_name) as name'),
                DB::raw('SUM(d.qty) as units'),
                DB::raw('SUM(d.sale_price * d.qty) as sales'),
            ]);

        return $rows->map(static fn ($row): array => [
            'product_id' => (int) $row->product_id,
            'name' => (string) ($row->name ?? ''),
            'units' => (int) $row->units,
            'sales' => (float) $row->sales,
        ])->all();
    }

    /**
     * The window of the same length immediately before the selected range,
     * with the percentage change of every headline KPI.
     *
     * A KPI whose previous value is zero has no percentage - it is null, not
     * infinity, and the page says "no previous period" instead of a number.
     *
     * @return array{from: string, to: string, label: string, totals: array<string, float|int>, delta: array<string, float|null>}
     */
    private function previousPeriod(Carbon $from, Carbon $to, array $current): array
    {
        $dayCount = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        $previousTo = $from->copy()->startOfDay()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->startOfDay()->subDays($dayCount - 1)->startOfDay();

        $totals = $this->kpiTotals($previousFrom, $previousTo);

        $delta = [];
        foreach (array_keys($current) as $key) {
            $before = (float) $totals[$key];
            $now = (float) $current[$key];

            $delta[$key] = $before != 0.0 ? round(($now - $before) / $before * 100, 1) : null;
        }

        return [
            'from' => $previousFrom->toDateTimeString(),
            'to' => $previousTo->toDateTimeString(),
            'label' => $previousFrom->isSameDay($previousTo)
                ? $previousFrom->format('d M Y')
                : $previousFrom->format('d M Y').' - '.$previousTo->format('d M Y'),
            'totals' => $totals,
            'delta' => $delta,
        ];
    }

    public function summary(Carbon $from, Carbon $to): array
    {
        $totals = $this->kpiTotals($from, $to);

        $deliveredCount = (int) $totals['delivered_count'];
        $revenue = (float) $totals['revenue'];
        $netSales = (float) $totals['net_sales'];
        $productSales = (float) $totals['product_sales'];
        $cogs = (float) $totals['cogs'];
        $grossProfit = (float) $totals['gross_profit'];
        $courierCost = (float) $totals['courier_cost'];
        $refundPaid = (float) $totals['refund_paid'];
        $businessExpenses = (float) $totals['business_expenses'];
        $expense = (float) $totals['expense'];
        $profit = (float) $totals['profit'];
        $paid = (float) $totals['paid'];
        $pending = (float) $totals['pending'];
        $aov = (float) $totals['aov'];
        $placedCount = (int) $totals['placed_count'];
        $placedAmount = (float) $totals['placed_amount'];
        $placedPendingPayment = (float) $totals['placed_pending_payment'];

        $paymentMethods = DB::table('payments')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('LOWER(payment_method) as method, COUNT(*) as rows_count, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $courierQuery = DB::table('courier_shipments')->whereBetween('created_at', [$from, $to]);
        $courierCount = (clone $courierQuery)->count();
        $courierDelivered = (clone $courierQuery)
            ->where('status_normalized', 'delivered')
            ->count();

        $returnQuery = DB::table('order_returns')->whereBetween('created_at', [$from, $to]);
        $returnCount = (clone $returnQuery)->count();
        $refundRequired = (float) (clone $returnQuery)->where('refund_required', 1)->sum('refund_amount');
        $refunded = (float) (clone $returnQuery)->where('refunded', 1)->sum('refund_amount');

        $couponQuery = DB::table('coupon_usages')->whereBetween('created_at', [$from, $to]);
        $couponUsages = (clone $couponQuery)->count();
        $couponDiscount = (float) (clone $couponQuery)->sum('discount_amount');
        // Metric dictionary: coupon revenue is the amount of each distinct
        // order, counted once even when the order used more than one coupon.
        $couponRevenue = (float) DB::table('orders')
            ->whereIn('id', function ($query) use ($from, $to) {
                $query->select('order_id')
                    ->from('coupon_usages')
                    ->whereNotNull('order_id')
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->sum('amount');

        $stock = $this->signal->state();

        // Status labels come from order_statuses, never from the raw column.
        // orders.order_status holds either the numeric id ("1") or the slug
        // ("pending"), so both forms map to the same human name.
        $statusNames = [];
        foreach (DB::table('order_statuses')->get(['id', 'slug', 'name']) as $status) {
            $statusNames[(string) $status->id] = (string) $status->name;
            $statusNames[(string) $status->slug] = (string) $status->name;
        }

        // Orders PLACED in the range, by orders.created_at. This is what an
        // admin means by "today": the order they just took, before delivery.
        // The counts themselves come from kpiTotals() above; only the by-status
        // grouping is computed here, because it is a chart payload.
        $placedQuery = DB::table('orders')->whereBetween('created_at', [$from, $to]);

        $placedByStatus = [];
        foreach ((clone $placedQuery)
            ->selectRaw('order_status, COUNT(*) as orders, SUM(amount) as total')
            ->groupBy('order_status')
            ->orderByDesc('total')
            ->get() as $row) {
            $key = (string) $row->order_status;
            $placedByStatus[] = [
                'name' => $statusNames[$key] ?? $key,
                'orders' => (int) $row->orders,
                'total' => (float) $row->total,
            ];
        }

        // Daily placed orders for the trend chart. A quiet day is a real zero,
        // so a range of up to 92 days is zero-filled; a wider custom range is
        // left sparse to keep the page payload small.
        $trendRows = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(amount) as amount')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $salesTrend = [];
        $dayCount = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        if ($dayCount <= 92) {
            $cursor = $from->copy()->startOfDay();
            $lastDay = $to->copy()->startOfDay();

            while ($cursor->lessThanOrEqualTo($lastDay)) {
                $key = $cursor->toDateString();
                $row = $trendRows->get($key);
                $salesTrend[] = [
                    'date' => $key,
                    'label' => $cursor->format('d M'),
                    'orders' => $row ? (int) $row->orders : 0,
                    'amount' => $row ? (float) $row->amount : 0.0,
                ];
                $cursor->addDay();
            }
        } else {
            foreach ($trendRows as $day => $row) {
                $salesTrend[] = [
                    'date' => (string) $day,
                    'label' => Carbon::parse($day)->format('d M'),
                    'orders' => (int) $row->orders,
                    'amount' => (float) $row->amount,
                ];
            }
        }

        // The status donut reuses the placed-by-status grouping above, so the
        // chart and the "By status" list can never disagree.
        $statusMix = [];
        foreach ($placedByStatus as $row) {
            $statusMix[] = [
                'label' => $row['name'],
                'orders' => $row['orders'],
                'amount' => $row['total'],
            ];
        }

        // Payment distribution is counted per payment row, not per order: one
        // order can have more than one payment attempt.
        $paymentMix = [];
        foreach (DB::table('payments')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("LOWER(COALESCE(payment_status, 'unknown')) as status, COUNT(*) as rows_count, SUM(amount) as total")
            ->groupBy('status')
            ->orderByDesc('rows_count')
            ->get() as $row) {
            $paymentMix[] = [
                'status' => (string) $row->status,
                'label' => ucfirst(str_replace('_', ' ', (string) $row->status)),
                'count' => (int) $row->rows_count,
                'amount' => (float) $row->total,
            ];
        }

        // Payment still owed on the orders placed in the range: computed in
        // kpiTotals() so the current and previous periods use one query shape.

        $recentOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'amount', 'order_status', 'created_at'])
            ->map(function ($order) use ($statusNames) {
                $order->status_name = $statusNames[(string) $order->order_status] ?? (string) $order->order_status;

                return $order;
            });

        $label = $from->isSameDay($to)
            ? $from->format('d M Y')
            : $from->format('d M Y').' - '.$to->format('d M Y');

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'label' => $label,
            'previous' => $this->previousPeriod($from, $to, $totals),
            'top_products' => $this->topProducts($from, $to),
            'delivered_count' => $deliveredCount,
            'revenue' => $revenue,
            'net_sales' => $netSales,
            'product_sales' => $productSales,
            'aov' => $aov,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'courier_cost' => $courierCost,
            'refund_paid' => $refundPaid,
            'business_expenses' => $businessExpenses,
            'expense' => $expense,
            'profit' => $profit,
            'paid' => $paid,
            'pending' => $pending,
            'payment_methods' => $paymentMethods,
            'courier_count' => $courierCount,
            'courier_delivered' => $courierDelivered,
            'courier_rate' => $courierCount > 0 ? round($courierDelivered / $courierCount * 100, 1) : 0.0,
            'return_count' => $returnCount,
            'refund_required' => $refundRequired,
            'refunded' => $refunded,
            'refund_pending' => max(0.0, $refundRequired - $refunded),
            'coupon_usages' => $couponUsages,
            'coupon_discount' => $couponDiscount,
            'coupon_revenue' => $couponRevenue,
            'stock' => $stock,
            'placed_count' => $placedCount,
            'placed_amount' => $placedAmount,
            'placed_by_status' => $placedByStatus,
            'placed_pending_payment' => $placedPendingPayment,
            'sales_trend' => $salesTrend,
            'status_mix' => $statusMix,
            'payment_mix' => $paymentMix,
            'status_names' => $statusNames,
            'recent_orders' => $recentOrders,
        ];
    }
}
