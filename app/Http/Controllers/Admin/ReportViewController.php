<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Toastr;

/**
 * Saved report filter sets (report_views).
 *
 * A view is stored as the query string the report page already reads, so the
 * page has one source of truth for its filters. Store redirects straight to
 * that URL with the same filters, which is what makes "save then reopen" show
 * the identical report.
 */
class ReportViewController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'view_key' => ['required', Rule::in(array_keys(ReportView::ROUTES))],
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        // Only the filters the report page actually reads are stored; an empty
        // pair means "whole baseline", exactly like an empty filter card.
        $filters = array_filter([
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        ReportView::query()->create([
            'user_id' => $request->user()->id,
            'view_key' => $data['view_key'],
            'name' => $data['name'],
            'filters' => $filters,
            'is_shared' => $request->boolean('is_shared'),
        ]);

        Toastr::success('Saved view stored.');

        return redirect()->route(ReportView::ROUTES[$data['view_key']], $filters);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $view = ReportView::query()->findOrFail($id);

        // A shared view is still owned by one account: only that account may
        // delete it. Everyone else can use it, not remove it.
        abort_unless(
            (int) $view->user_id === (int) $request->user()->id,
            403,
            'Only the owner can delete this saved view.'
        );

        $route = $view->routeName();
        $filters = $view->filterQuery();

        $view->delete();

        Toastr::success('Saved view deleted.');

        return redirect()->route($route, $filters);
    }
}
