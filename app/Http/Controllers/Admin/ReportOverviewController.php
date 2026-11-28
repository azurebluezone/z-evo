<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sales and profit overview.
 *
 * Every number is a database aggregate; nothing is hard-coded and no new
 * table is introduced. Two profit views are shown side by side because they
 * answer two different questions:
 *
 *   View A (delivery-inclusive): what the customer paid, minus the courier's
 *   real cost, minus COGS, minus realised refunds, minus operating expenses.
 *
 *   View B (product-only): the same, but with the shipping charge removed
 *   from revenue and the courier cost left out, so the margin belongs to the
 *   goods alone.
 *
 *   Gross Profit is the dictionary's strict Net Sales - COGS; it is a
 *   different number from both Net Profit views and is labelled as such.
 *
 * orders.amount is the authoritative revenue: discount is already deducted
 * there, so orders.discount / order_details.product_discount are never
 * subtracted a second time.
 */
class ReportOverviewController extends Controller
{
    private const DELIVERED_STATUSES = ['delivered', '4'];

    public function index(Request $request)
    {
        $range = $this->range($request);

        $orderedQuery = DB::table('orders');
        $deliveredQuery = DB::table('orders')->whereIn('order_status', self::DELIVERED_STATUSES);

        if ($range !== null) {
            $orderedQuery->whereBetween('created_at', $range);
            $deliveredQuery->whereBetween('delivered_at', $range);
        }

        $orderedCount = (clone $orderedQuery)->count();
        $orderedRevenue = (float) (clone $orderedQuery)->sum('amount');

        $deliveredCount = (clone $deliveredQuery)->count();
        $collected = (float) (clone $deliveredQuery)->sum('amount');
        $shippingRevenue = (float) (clone $deliveredQuery)->sum('shipping_charge');
        $freeShippingValue = (float) (clone $deliveredQuery)
            ->where('shipping_is_free', 1)
            ->sum('shipping_list_price');

        $cogs = (float) $this->deliveredJoin('order_details as d', 'd.order_id', $range)
            ->sum(DB::raw('d.purchase_price * d.qty'));

        $shippingCost = (float) $this->deliveredJoin('courier_shipments as c', 'c.order_id', $range)
            ->sum('c.shipping_cost');

        $refundRealised = (float) $this->deliveredJoin('order_returns as r', 'r.order_id', $range)
            ->where('r.refunded', 1)
            ->sum('r.refund_amount');

        $refundPending = (float) $this->deliveredJoin('order_returns as r', 'r.order_id', $range)
            ->where('r.refund_required', 1)
            ->where('r.refunded', 0)
            ->sum('r.refund_amount');

        $productRevenueNet = $collected - $shippingRevenue;

        $businessExpenses = (float) DB::table('business_expenses')
            ->when($range !== null, function ($query) use ($range) {
                $query->whereDate('spent_at', '>=', $range[0]->toDateString())
                    ->whereDate('spent_at', '<=', $range[1]->toDateString());
            })
            ->sum('amount');

        $grossProfit = $productRevenueNet - $cogs;
        $viewA = $collected - $shippingCost - $cogs - $refundRealised - $businessExpenses;
        $viewB = $productRevenueNet - $cogs - $refundRealised;

        $metrics = [
            'ordered_count' => $orderedCount,
            'ordered_revenue' => $orderedRevenue,
            'delivered_count' => $deliveredCount,
            'collected' => $collected,
            'shipping_revenue' => $shippingRevenue,
            'shipping_cost' => $shippingCost,
            'shipping_margin' => $shippingRevenue - $shippingCost,
            'free_shipping_value' => $freeShippingValue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'refund_realised' => $refundRealised,
            'refund_pending' => $refundPending,
            'business_expenses' => $businessExpenses,
            'product_revenue_net' => $productRevenueNet,
            'view_a' => $viewA,
            'view_b' => $viewB,
            'margin_a' => $collected > 0 ? round($viewA / $collected * 100, 1) : 0.0,
            'margin_b' => $productRevenueNet > 0 ? round($viewB / $productRevenueNet * 100, 1) : 0.0,
            'margin_gross' => $productRevenueNet > 0 ? round($grossProfit / $productRevenueNet * 100, 1) : 0.0,
            'after_pending_refunds' => $viewA - $refundPending,
        ];

        return view('backEnd.reports.overview', [
            'metrics' => $metrics,
            'trend' => $this->trend($orderedQuery, $deliveredQuery),
            'paymentMix' => $this->paymentMix(),
            'filters' => [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ],
        ]);
    }

    /**
     * Join a detail table to its delivered orders.
     */
    private function deliveredJoin(string $table, string $foreignKey, ?array $range)
    {
        $query = DB::table($table)
            ->join('orders as o', 'o.id', '=', $foreignKey)
            ->whereIn('o.order_status', self::DELIVERED_STATUSES);

        if ($range !== null) {
            $query->whereBetween('o.delivered_at', $range);
        }

        return $query;
    }

    /**
     * Ordered and delivered revenue per calendar day, for the chart.
     */
    private function trend($orderedQuery, $deliveredQuery): array
    {
        $ordered = (clone $orderedQuery)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $delivered = (clone $deliveredQuery)
            ->whereNotNull('delivered_at')
            ->selectRaw('DATE(delivered_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $orderedByDay = $ordered->pluck('total', 'day');
        $deliveredByDay = $delivered->pluck('total', 'day');

        $days = $ordered->pluck('day')
            ->merge($delivered->pluck('day'))
            ->unique()
            ->sort()
            ->values();

        $trend = ['labels' => [], 'ordered' => [], 'delivered' => []];

        foreach ($days as $day) {
            $trend['labels'][] = Carbon::parse($day)->format('d M');
            $trend['ordered'][] = round((float) ($orderedByDay[$day] ?? 0), 2);
            $trend['delivered'][] = round((float) ($deliveredByDay[$day] ?? 0), 2);
        }

        return $trend;
    }

    /**
     * Paid amount per payment method.
     */
    private function paymentMix(): array
    {
        $rows = DB::table('payments')
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as orders'))
            ->where('payment_status', 'paid')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('payment_method')->all(),
            'totals' => $rows->map(fn ($row) => round((float) $row->total, 2))->all(),
            'orders' => $rows->pluck('orders')->all(),
        ];
    }

    /**
     * Optional date filter. Both dates must be present; otherwise the report
     * covers everything (the seeded development baseline).
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
}
