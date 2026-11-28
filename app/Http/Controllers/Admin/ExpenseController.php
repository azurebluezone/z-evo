<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessExpense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Toastr;

/**
 * Expense entry, Phase 1 of docs/report-completion-plan.md.
 *
 * Every number on the dashboard is an aggregate over the database, so this
 * screen does not "push" anything into the report: a saved row is visible on
 * the next dashboard load because ReportMetricsService sums business_expenses
 * by spent_at. The independent SQL test locks that behaviour.
 */
class ExpenseController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $expenses = BusinessExpense::query()
            ->with(['category', 'creator'])
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(20);

        $total = (float) BusinessExpense::query()->sum('amount');

        return view('backEnd.reports.expenses.index', compact('categories', 'expenses', 'total'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'spent_at' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:191'],
        ]);

        $data['created_by'] = $request->user()->id;

        BusinessExpense::create($data);

        Toastr::success('Expense saved.', 'Success');

        return redirect()->route('admin.reports.expenses.index');
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'expense_id' => ['required', 'integer', Rule::exists('business_expenses', 'id')],
        ]);

        BusinessExpense::query()->whereKey($data['expense_id'])->delete();

        Toastr::success('Expense deleted.', 'Success');

        return redirect()->route('admin.reports.expenses.index');
    }
}
