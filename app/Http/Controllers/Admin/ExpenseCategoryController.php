<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Toastr;

/**
 * Expense categories are the labels the expense form offers.
 *
 * A category with expenses cannot be deleted: the migration cascades on
 * delete, and silently removing historical spend would make the profit
 * report lie. Deactivate it instead.
 */
class ExpenseCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('expense_categories', 'name')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        ExpenseCategory::create($data);

        Toastr::success('Category saved.', 'Success');

        return redirect()->route('admin.reports.expenses.index');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('expense_categories', 'name')->ignore($request->integer('category_id')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = ExpenseCategory::findOrFail($data['category_id']);
        $category->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        Toastr::success('Category updated.', 'Success');

        return redirect()->route('admin.reports.expenses.index');
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
        ]);

        $category = ExpenseCategory::findOrFail($data['category_id']);
        $category->update(['is_active' => ! $category->is_active]);

        Toastr::success('Category status updated.', 'Success');

        return redirect()->route('admin.reports.expenses.index');
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
        ]);

        $category = ExpenseCategory::withCount('expenses')->findOrFail($data['category_id']);

        if ($category->expenses_count > 0) {
            Toastr::error('This category has expenses and cannot be deleted.', 'Blocked');

            return redirect()->route('admin.reports.expenses.index');
        }

        $category->delete();

        Toastr::success('Category deleted.', 'Success');

        return redirect()->route('admin.reports.expenses.index');
    }
}
