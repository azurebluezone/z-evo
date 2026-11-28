<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cohort retention by the month of a customer's FIRST delivered order.
 *
 * The cohort of a customer is fixed by history, not by the report filter: the
 * month their first delivered order became real. Every later delivered order
 * counts as activity in that cohort, which is why a January cohort can still
 * grow a February cell after the January report was printed.
 *
 * Definitions (also in docs/report-metrics.md section 3.9):
 *   cohort month  DATE_FORMAT(MIN(orders.delivered_at), '%Y-%m-01') per customer
 *   cohort size   customers whose first delivered month is the cohort month
 *   offset k      activity month minus cohort month, in whole months
 *   repeat(k)     distinct customers of the cohort with a delivered order at
 *                 offset k. Offset 0 is the acquisition month itself.
 *   retention(k)  repeat(k) / cohort size
 *
 * Only delivered orders count (['delivered','4']), on orders.delivered_at: a
 * placed-but-never-delivered order is not a customer relationship yet.
 */
class CohortRetentionService
{
    private const DELIVERED = ['delivered', '4'];

    /** M0 .. M6: half a year of retention is what the page shows. */
    public const MAX_OFFSET = 6;

    /**
     * @return array<int, array{cohort: string, customers: int, cells: array<int, int>}>
     */
    public function cohorts(Carbon $from, Carbon $to): array
    {
        $firstDelivered = $this->firstDeliveredSubquery();

        $sizes = DB::query()
            ->fromSub($firstDelivered, 'f')
            ->whereBetween('f.first_delivered_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(f.first_delivered_at, '%Y-%m-01') as cohort_month, COUNT(*) as customers")
            ->groupBy('cohort_month')
            ->orderBy('cohort_month')
            ->pluck('customers', 'cohort_month');

        $activity = DB::table('orders as o')
            ->joinSub($firstDelivered, 'f', 'f.customer_id', '=', 'o.customer_id')
            ->whereIn('o.order_status', self::DELIVERED)
            ->whereNotNull('o.delivered_at')
            ->whereBetween('f.first_delivered_at', [$from, $to])
            ->selectRaw(
                "DATE_FORMAT(f.first_delivered_at, '%Y-%m-01') as cohort_month, "
                ."DATE_FORMAT(o.delivered_at, '%Y-%m-01') as activity_month, "
                .'COUNT(DISTINCT o.customer_id) as customers'
            )
            ->groupBy('cohort_month', 'activity_month')
            ->get();

        $rows = [];

        foreach ($sizes as $cohortMonth => $size) {
            $cohortAt = Carbon::parse($cohortMonth)->startOfMonth();
            $cells = array_fill(0, self::MAX_OFFSET + 1, 0);

            foreach ($activity->where('cohort_month', $cohortMonth) as $row) {
                $activityAt = Carbon::parse($row->activity_month)->startOfMonth();

                if ($activityAt->lessThan($cohortAt)) {
                    continue;
                }

                $offset = (int) round($cohortAt->diffInMonths($activityAt));

                if ($offset <= self::MAX_OFFSET) {
                    $cells[$offset] = (int) $row->customers;
                }
            }

            $rows[] = [
                'cohort' => $cohortAt->format('Y-m'),
                'customers' => (int) $size,
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    /**
     * Whole-report totals: cohort customers and, per offset, how many of them
     * came back. Summing per-cohort cells is correct because a customer belongs
     * to exactly one cohort.
     *
     * @param  array<int, array{cohort: string, customers: int, cells: array<int, int>}>  $rows
     * @return array{customers: int, cells: array<int, int>}
     */
    public function totals(array $rows): array
    {
        $cells = array_fill(0, self::MAX_OFFSET + 1, 0);
        $customers = 0;

        foreach ($rows as $row) {
            $customers += $row['customers'];

            foreach ($row['cells'] as $offset => $count) {
                $cells[$offset] += $count;
            }
        }

        return ['customers' => $customers, 'cells' => $cells];
    }

    /**
     * customer_id -> the timestamp of their first delivered order. One row per
     * customer, so joining it to orders can never invent a customer.
     */
    private function firstDeliveredSubquery()
    {
        return DB::table('orders')
            ->selectRaw('customer_id, MIN(delivered_at) as first_delivered_at')
            ->whereIn('order_status', self::DELIVERED)
            ->whereNotNull('customer_id')
            ->whereNotNull('delivered_at')
            ->groupBy('customer_id');
    }
}
