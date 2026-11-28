<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MarketingMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The Marketing report, Phase 3 item 3.
 *
 * Read-only. Every number comes from MarketingMetricsService, which is also
 * what the dashboard blocks read, so the two screens can never show a
 * different ROAS for the same range.
 */
class MarketingReportController extends Controller
{
    public function index(Request $request, MarketingMetricsService $metrics)
    {
        [$from, $to, $label] = $this->range($request->query('from'), $request->query('to'));

        $totals = $metrics->totals($from, $to);

        return view('backEnd.reports.marketing.report', [
            'rows' => $totals['channels'],
            'totals' => $totals,
            'label' => $label,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * Default window is the current month: marketing spend is reviewed by
     * month, unlike the single-day default of the operations dashboard.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function range(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

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
}
