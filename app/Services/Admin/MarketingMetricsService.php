<?php

namespace App\Services\Admin;

use App\Models\MarketingSpend;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Marketing efficiency: spend against the revenue it produced.
 *
 * One service for the dashboard blocks and the Marketing report, so the two
 * cannot disagree. The definitions are fixed here and nowhere else:
 *
 *   spend              sum of marketing_spends.spent, by spent_at, in range
 *   attributed revenue sum of orders.amount for DELIVERED orders whose
 *                      delivered_at falls in range and that carry an
 *                      order_attributions row; grouped by that row's source
 *   delivered orders   the same order set, counted
 *   new customers      customers whose FIRST delivered order (all time) is in
 *                      range, attributed to that order's source
 *   ROAS               attributed revenue / spend          (null, not 0, when
 *                      nothing was spent: an infinite return is not a number)
 *   CPA                spend / delivered orders
 *   CAC                spend / new customers
 *
 * Two honest gaps are reported instead of hidden:
 *   - unattributed_revenue: delivered revenue with no attribution row. It is
 *     excluded from ROAS on purpose, because dividing it by a channel's spend
 *     would invent a return.
 *   - spend on a channel with no delivered order yet: ROAS and CAC are null.
 *
 * Date bases differ by nature: spend is the day the money left (spent_at), the
 * revenue side is the day the order was delivered (delivered_at). The report
 * states this rather than pretending the two are the same clock.
 */
class MarketingMetricsService
{
    private const DELIVERED = ['delivered', '4'];

    /**
     * One row per channel, sorted by spend then revenue.
     *
     * @return array<int, array<string, mixed>>
     */
    public function channelRows(Carbon $from, Carbon $to): array
    {
        $spend = $this->spendByChannel($from, $to);
        $revenue = $this->revenueByChannel($from, $to);
        $newCustomers = $this->newCustomersByChannel($from, $to);

        $channels = collect(MarketingSpend::CHANNELS)
            ->merge($spend->keys())
            ->merge($revenue->keys())
            ->merge($newCustomers->keys())
            ->unique()
            ->values();

        return $channels
            ->map(function (string $channel) use ($spend, $revenue, $newCustomers) {
                $spent = (float) ($spend[$channel]->spend ?? 0);
                $orders = (int) ($revenue[$channel]->orders ?? 0);
                $earned = (float) ($revenue[$channel]->revenue ?? 0);
                $new = (int) ($newCustomers[$channel]->customers ?? 0);

                return [
                    'channel' => $channel,
                    'entries' => (int) ($spend[$channel]->entries ?? 0),
                    'spend' => round($spent, 2),
                    'orders' => $orders,
                    'revenue' => round($earned, 2),
                    'new_customers' => $new,
                    'roas' => $spent > 0 ? round($earned / $spent, 2) : null,
                    'cpa' => $orders > 0 ? round($spent / $orders, 2) : null,
                    'cac' => $new > 0 ? round($spent / $new, 2) : null,
                ];
            })
            ->sortBy([['spend', 'desc'], ['revenue', 'desc'], ['channel', 'asc']])
            ->values()
            ->all();
    }

    /**
     * The same numbers for the whole range, so the dashboard cards and the
     * report footer are a sum of exactly the rows above.
     *
     * @return array<string, mixed>
     */
    public function totals(Carbon $from, Carbon $to): array
    {
        $rows = $this->channelRows($from, $to);

        $spend = round((float) array_sum(array_column($rows, 'spend')), 2);
        $revenue = round((float) array_sum(array_column($rows, 'revenue')), 2);
        $orders = (int) array_sum(array_column($rows, 'orders'));
        $newCustomers = (int) array_sum(array_column($rows, 'new_customers'));

        return [
            'spend' => $spend,
            'revenue' => $revenue,
            'orders' => $orders,
            'new_customers' => $newCustomers,
            'roas' => $spend > 0 ? round($revenue / $spend, 2) : null,
            'cpa' => $orders > 0 ? round($spend / $orders, 2) : null,
            'cac' => $newCustomers > 0 ? round($spend / $newCustomers, 2) : null,
            'unattributed_revenue' => $this->unattributedRevenue($from, $to),
            'channels' => $rows,
        ];
    }

    private function spendByChannel(Carbon $from, Carbon $to)
    {
        return DB::table('marketing_spends')
            ->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('LOWER(channel) as channel, COUNT(*) as entries, COALESCE(SUM(spent), 0) as spend')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');
    }

    private function revenueByChannel(Carbon $from, Carbon $to)
    {
        // order_attributions.order_id is unique, so the join cannot duplicate
        // an order's amount.
        return DB::table('orders as o')
            ->join('order_attributions as a', 'a.order_id', '=', 'o.id')
            ->whereIn('o.order_status', self::DELIVERED)
            ->whereBetween('o.delivered_at', [$from, $to])
            ->selectRaw('LOWER(a.source) as channel, COUNT(*) as orders, COALESCE(SUM(o.amount), 0) as revenue')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');
    }

    /**
     * Customers whose first-ever delivered order landed in this range.
     *
     * MIN(delivered_at) per customer is the first-delivery date; joining it
     * back to orders finds that exact order, and its attribution row names the
     * channel that acquired the customer. COUNT(DISTINCT customer_id) keeps
     * two same-day delivered orders from counting the customer twice.
     */
    private function newCustomersByChannel(Carbon $from, Carbon $to)
    {
        $firstDelivered = DB::table('orders')
            ->selectRaw('customer_id, MIN(delivered_at) as first_delivered_at')
            ->whereIn('order_status', self::DELIVERED)
            ->whereNotNull('customer_id')
            ->whereNotNull('delivered_at')
            ->groupBy('customer_id');

        return DB::table('orders as o')
            ->joinSub($firstDelivered, 'first', function ($join) {
                $join->on('first.customer_id', '=', 'o.customer_id')
                    ->on('first.first_delivered_at', '=', 'o.delivered_at');
            })
            ->join('order_attributions as a', 'a.order_id', '=', 'o.id')
            ->whereIn('o.order_status', self::DELIVERED)
            ->whereBetween('o.delivered_at', [$from, $to])
            ->selectRaw('LOWER(a.source) as channel, COUNT(DISTINCT o.customer_id) as customers')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');
    }

    /**
     * Delivered revenue with no attribution row: real money whose source we
     * cannot name. Reported, never divided by spend.
     */
    private function unattributedRevenue(Carbon $from, Carbon $to): float
    {
        return round((float) DB::table('orders as o')
            ->leftJoin('order_attributions as a', 'a.order_id', '=', 'o.id')
            ->whereIn('o.order_status', self::DELIVERED)
            ->whereBetween('o.delivered_at', [$from, $to])
            ->whereNull('a.id')
            ->sum('o.amount'), 2);
    }
}
