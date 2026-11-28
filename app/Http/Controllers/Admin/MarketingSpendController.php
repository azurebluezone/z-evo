<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingSpend;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Toastr;

/**
 * Marketing spend entry, Phase 3 item 1 of docs/report-completion-plan.md.
 *
 * Same shape as ExpenseController: the screen writes one row to the existing
 * marketing_spends table and reads its totals back with plain aggregates, so
 * the ROAS and CPA reports can never disagree with what was entered here.
 *
 * Admin-only (report-profit-view) because spend is the denominator of ROAS.
 */
class MarketingSpendController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('start_date');
        $to = $request->query('end_date');

        $spends = MarketingSpend::query()
            ->betweenDays($from, $to)
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Totals come from the same filtered query, not from the paginated
        // page, so a channel split never loses the rows on page 2.
        $totals = MarketingSpend::query()
            ->betweenDays($from, $to)
            ->selectRaw('COALESCE(SUM(spent), 0) as spent, COALESCE(SUM(impressions), 0) as impressions, COALESCE(SUM(clicks), 0) as clicks, COUNT(*) as entries')
            ->first();

        $byChannel = MarketingSpend::query()
            ->betweenDays($from, $to)
            ->selectRaw('channel, COALESCE(SUM(spent), 0) as spent, COUNT(*) as entries')
            ->groupBy('channel')
            ->orderByDesc('spent')
            ->get();

        return view('backEnd.reports.marketing.index', [
            'channels' => MarketingSpend::CHANNELS,
            'spends' => $spends,
            'byChannel' => $byChannel,
            'totals' => [
                'spent' => (float) $totals->spent,
                'impressions' => (int) $totals->impressions,
                'clicks' => (int) $totals->clicks,
                'entries' => (int) $totals->entries,
            ],
            'filters' => ['start_date' => $from, 'end_date' => $to],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(MarketingSpend::CHANNELS)],
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'spent' => ['required', 'numeric', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'spent_at' => ['required', 'date'],
            'source_reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['impressions'] = $data['impressions'] ?? 0;
        $data['clicks'] = $data['clicks'] ?? 0;

        MarketingSpend::query()->create($data);

        Toastr::success('Marketing spend saved.', 'Success');

        return redirect()->route('admin.reports.marketing-spends.index', array_filter([
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'spend_id' => ['required', 'integer', Rule::exists('marketing_spends', 'id')],
        ]);

        MarketingSpend::query()->whereKey($data['spend_id'])->delete();

        Toastr::success('Marketing spend deleted.', 'Success');

        return redirect()->route('admin.reports.marketing-spends.index');
    }
}
