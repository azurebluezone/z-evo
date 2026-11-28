<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Payment settlement on the Payment Methods report
 * (docs/report-completion-plan.md Phase 1 item 3, docs/report-metrics.md 3.3).
 *
 * One settlement entry writes a payment_settlements row and recomputes the
 * parent payment's settled_amount and gateway_fee from its settlement rows.
 * The page must then show collected - gateway fee - settled, per method and
 * for the range, equal to an independent SQL sum.
 *
 * Fixtures are rolled back by DatabaseTransactions.
 *
 * Marker: PAYMENT_SETTLEMENT_OK
 */
class AdminPaymentSettlementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the report cannot be authenticated.');

        return $admin;
    }

    private function paidPayment(string $method, int $amount): int
    {
        $order = DB::table('orders')->orderBy('id')->first(['id', 'customer_id']);
        $this->assertNotNull($order, 'The audit needs at least one order.');

        return (int) DB::table('payments')->insertGetId([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_settlement_entry_writes_row_and_syncs_payment(): void
    {
        $paymentId = $this->paidPayment('settle-truth', 1000);

        $response = $this->actingAs($this->admin())->post(route('admin.reports.payments.settlement'), [
            'payment_id' => $paymentId,
            'settled_amount' => 900,
            'gateway_fee' => 20,
            'settled_at' => now()->toDateString(),
            'reference' => 'BANK-TRUTH-1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_settlements', [
            'payment_id' => $paymentId,
            'settled_amount' => 900,
            'gateway_fee' => 20,
            'status' => 'settled',
            'reference' => 'BANK-TRUTH-1',
        ]);

        $payment = DB::table('payments')->where('id', $paymentId)->first();
        $this->assertSame(900.0, (float) $payment->settled_amount);
        $this->assertSame(20.0, (float) $payment->gateway_fee);
        $this->assertSame('settled', $payment->settlement_status);
        $this->assertNotNull($payment->settled_at);
    }

    public function test_page_gap_equals_independent_sql_sum(): void
    {
        $paymentId = $this->paidPayment('settle-truth-gap', 1000);

        $this->actingAs($this->admin())->post(route('admin.reports.payments.settlement'), [
            'payment_id' => $paymentId,
            'settled_amount' => 900,
            'gateway_fee' => 20,
            'settled_at' => now()->toDateString(),
        ])->assertRedirect();

        $collected = (float) DB::table('payments')->where('payment_status', 'paid')->sum('amount');
        $fee = (float) DB::table('payments')->where('payment_status', 'paid')->sum('gateway_fee');
        $settled = (float) DB::table('payments')->where('payment_status', 'paid')->sum('settled_amount');
        $gap = round($collected - $fee - $settled, 2);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.payments'));

        $response->assertOk();
        $response->assertSee('data-metric="settlement-gap"', false);

        $metrics = $response->viewData('metrics');
        $this->assertSame(
            $gap,
            round((float) $metrics['settlement_gap'], 2),
            'The page settlement gap must equal collected - gateway fee - settled from SQL.'
        );

        // The per-method row shows the same arithmetic for its own payments.
        $response->assertSee('data-method-gap="settle-truth-gap"', false);
        $response->assertSee('BDT 80.00');
    }

    public function test_support_cannot_record_a_settlement(): void
    {
        $support = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Support'))->first();
        $this->assertNotNull($support, 'No Support user, so the denial cannot be proven.');

        $paymentId = $this->paidPayment('settle-truth-denied', 500);

        $this->actingAs($support)->post(route('admin.reports.payments.settlement'), [
            'payment_id' => $paymentId,
            'settled_amount' => 500,
        ])->assertForbidden();

        $this->assertDatabaseMissing('payment_settlements', ['payment_id' => $paymentId]);
    }
}
