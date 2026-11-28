<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MarketingMetricsService;
use App\Services\Admin\ReportMetricsService;
use Illuminate\Http\Request;

/**
 * The report dashboard and the printable A-to-Z report.
 *
 * Both read from ReportMetricsService, so the dashboard, the printable report
 * and the daily email can never disagree about a number.
 */
class ReportDashboardController extends Controller
{
    public function index(Request $request, ReportMetricsService $metrics, MarketingMetricsService $marketing)
    {
        [$from, $to, $label] = $metrics->range($request->query('from'), $request->query('to'));
        $summary = $metrics->summary($from, $to);

        return view('backEnd.reports.dashboard', [
            'summary' => $summary,
            'marketing' => $marketing->totals($from, $to),
            'label' => $label,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function finalReport(Request $request, ReportMetricsService $metrics)
    {
        [$from, $to, $label] = $metrics->range($request->query('from'), $request->query('to'));
        $summary = $metrics->summary($from, $to);

        return view('backEnd.reports.email.daily-summary', [
            'summary' => $summary,
            'label' => $label,
        ]);
    }
}
