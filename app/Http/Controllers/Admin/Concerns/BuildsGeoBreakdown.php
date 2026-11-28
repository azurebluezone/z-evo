<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * District and division totals for the reports whose rows hang off an order.
 *
 * The order -> geography link is the shippings row written at checkout
 * (OrderPlacementService). Legacy rows carry no district_id and a few orders
 * have no shippings row at all, so both cases land in an explicit Unassigned
 * bucket. That is deliberate: the district totals have to add up to the
 * national total for the same range (docs/report-completion-plan.md Phase 2
 * item 5), which only holds if no row is silently dropped.
 *
 * The date column is always the parent report's own date base, so a filtered
 * report and its breakdown never cover different periods.
 */
trait BuildsGeoBreakdown
{
    /**
     * @param  string  $table  base table, addressed as alias "t"
     * @param  string  $orderKey  column on the base table that points at orders.id
     * @param  string  $dateColumn  date column on the base table, same base as the parent report
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @param  string  $countExpression  raw aggregate for the bucket's row count
     * @param  array<string, string>  $sumExpressions  alias => raw aggregate, both over "t"
     * @return Collection<int, object>
     */
    protected function geoBreakdown(
        string $table,
        string $orderKey,
        string $dateColumn,
        ?array $range,
        string $countExpression,
        array $sumExpressions,
        ?callable $constraint = null
    ): Collection {
        // One row per order, so a second shippings row can never multiply the
        // order-level money this report sums.
        $districtOfOrder = DB::table('shippings')
            ->select(
                'order_id',
                DB::raw('MIN(district_id) as district_id'),
                DB::raw('MIN(division) as division')
            )
            ->groupBy('order_id');

        $query = DB::table($table.' as t')
            ->leftJoinSub($districtOfOrder, 'geo', 'geo.order_id', '=', 't.'.$orderKey)
            ->leftJoin('districts as d', 'd.id', '=', 'geo.district_id');

        if ($range !== null) {
            $query->whereBetween('t.'.$dateColumn, $range);
        }

        if ($constraint !== null) {
            $constraint($query);
        }

        // districts is the authoritative copy; shippings.division is the
        // fallback for rows written before the districts row existed.
        $division = "COALESCE(d.division, geo.division, 'Unassigned')";
        $district = "COALESCE(d.district, 'Unassigned')";

        $selects = [
            $division.' as division',
            $district.' as district',
            $countExpression.' as row_count',
        ];

        foreach ($sumExpressions as $alias => $expression) {
            $selects[] = $expression.' as '.$alias;
        }

        return $query
            ->selectRaw(implode(', ', $selects))
            ->groupByRaw($division.', '.$district)
            ->orderByRaw($division)
            ->orderByRaw($district)
            ->get();
    }
}
