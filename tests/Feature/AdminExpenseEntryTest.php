<?php

namespace Tests\Feature;

use App\Models\BusinessExpense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1 expense entry UI, docs/report-completion-plan.md item 1.
 *
 * The important claim is not "a form exists"; it is that a saved row changes
 * the dashboard Expense and Estimated Net Profit. The dashboard test recomputes
 * the sum with independent SQL and then looks for that exact number in the
 * rendered KPI, so a hard-coded or stale number cannot pass.
 *
 * Marker: EXPENSE_UI_OK
 */
class AdminExpenseEntryTest extends TestCase
{
    use DatabaseTransactions;

    private function userWithRole(string $role): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->first();

        $this->assertNotNull($user, "No user with role {$role}, so the expense screen cannot be authenticated.");

        return $user;
    }

    public function test_admin_saves_expense_and_dashboard_kpis_change(): void
    {
        $admin = $this->userWithRole('Admin');

        $categoryName = 'TEST EXPENSE CATEGORY '.uniqid();
        $this->actingAs($admin)
            ->post(route('admin.reports.expense-categories.store'), [
                'name' => $categoryName,
                'sort_order' => 7,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.reports.expenses.index'));

        $category = ExpenseCategory::query()->where('name', $categoryName)->first();
        $this->assertNotNull($category);

        // A far-future date keeps the KPI independent of the live order data.
        $spentAt = '2099-12-31';
        $amount = 1234.56;

        $before = (float) DB::table('business_expenses')
            ->whereDate('spent_at', $spentAt)
            ->sum('amount');

        $this->actingAs($admin)
            ->post(route('admin.reports.expenses.store'), [
                'expense_category_id' => $category->id,
                'amount' => $amount,
                'spent_at' => $spentAt,
                'payment_method' => 'cash',
                'note' => 'TEST EXPENSE ENTRY',
            ])
            ->assertRedirect(route('admin.reports.expenses.index'));

        $this->assertDatabaseHas('business_expenses', [
            'expense_category_id' => $category->id,
            'amount' => $amount,
            'spent_at' => $spentAt,
            'created_by' => $admin->id,
        ]);

        $independent = (float) DB::table('business_expenses')
            ->whereDate('spent_at', $spentAt)
            ->sum('amount');

        $this->assertSame(round($before + $amount, 2), round($independent, 2));

        $response = $this->actingAs($admin)->get(route('admin.reports.dashboard', [
            'from' => $spentAt,
            'to' => $spentAt,
        ]));

        $response->assertOk();
        $response->assertSee('data-kpi="expense">BDT '.number_format($independent, 0), false);
        $response->assertSee('data-kpi="profit">BDT -'.number_format($independent, 0), false);
    }

    public function test_expense_screen_is_gated_by_profit_permission(): void
    {
        $support = $this->userWithRole('Support');

        $this->actingAs($support)
            ->get(route('admin.reports.expenses.index'))
            ->assertForbidden();

        $this->actingAs($support)
            ->post(route('admin.reports.expenses.store'), [])
            ->assertForbidden();

        $this->actingAs($support)
            ->post(route('admin.reports.expense-categories.store'), [])
            ->assertForbidden();
    }

    public function test_expense_validation_rejects_bad_input(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)
            ->post(route('admin.reports.expenses.store'), [
                'expense_category_id' => 999999,
                'amount' => 0,
                'spent_at' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['expense_category_id', 'amount', 'spent_at']);
    }

    public function test_category_crud_keeps_the_ledger_label_editable(): void
    {
        $admin = $this->userWithRole('Admin');

        $name = 'TEST CATEGORY '.uniqid();
        $this->actingAs($admin)
            ->post(route('admin.reports.expense-categories.store'), [
                'name' => $name,
                'sort_order' => 3,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $category = ExpenseCategory::query()->where('name', $name)->first();
        $this->assertNotNull($category);

        $this->actingAs($admin)
            ->post(route('admin.reports.expense-categories.update'), [
                'category_id' => $category->id,
                'name' => $name.' RENAMED',
                'sort_order' => 4,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name' => $name.' RENAMED',
            'sort_order' => 4,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.expense-categories.toggle'), [
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'is_active' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.expense-categories.destroy'), [
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }

    public function test_expense_delete_removes_the_row(): void
    {
        $admin = $this->userWithRole('Admin');

        $category = ExpenseCategory::create([
            'name' => 'TEST DELETE CATEGORY '.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $expense = BusinessExpense::create([
            'expense_category_id' => $category->id,
            'amount' => 50,
            'spent_at' => '2099-12-30',
            'payment_method' => 'cash',
            'note' => 'TEST DELETE',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.expenses.destroy'), ['expense_id' => $expense->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('business_expenses', ['id' => $expense->id]);
    }
}
