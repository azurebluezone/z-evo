<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\CohortRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Cohort retention report, Phase 3 item 4.
 *
 * Read-only. The rows come from CohortRetentionService, which is the only place
 * the cohort definition exists, so a second screen cannot compute a different
 * retention number from the same orders.
 */
class CohortRetentionController extends Controller
{
    public function index(Request $request, CohortRetentionService $cohorts)
    {
        [$from, $to, $label] = $this->range($request->query('from'), $request->query('to'));

        $rows = $cohorts->cohorts($from, $to);

        return view('backEnd.reports.cohorts', [
            'rows' => $rows,
            'totals' => $cohorts->totals($rows),
            'maxOffset' => CohortRetentionService::MAX_OFFSET,
            'label' => $label,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * Default window: the last six whole cohort months including this one.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function range(?string $from, ?string $to): array
    {
        $start = $from
            ? Carbon::parse($from)->startOfMonth()
            : Carbon::now()->subMonths(5)->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfMonth() : Carbon::now()->endOfMonth();

        if ($start->greaterThan($end)) {
            $swap = $start->copy();
            $start = $end->copy()->startOfMonth();
            $end = $swap->copy()->endOfMonth();
        }

        $label = $start->format('M Y').' - '.$end->format('M Y');

        return [$start, $end, $label];
    }
}
