<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The four analytics reports.
 *
 * Read-only on purpose: the suite runs against the development database, so
 * nothing here creates, resets or deletes a row. Each test recomputes the
 * aggregate straight from the database and then asserts that the rendered page
 * contains that exact number, which is what proves the page is not hard-coded.
 *
 * The permission tests are part of the contract, not decoration:
 *   - Support holds no report permission and must be refused;
 *   - Manager holds the operational reports but not the money one, so the
 *     payment page must stay closed for it.
 */
class AdminReportAnalyticsTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->first();

        $this->assertNotNull($user, "No user with role {$role}, so the report cannot be authenticated.");

        return $user;
    }

    public function test_payment_report_shows_database_totals(): void
    {
        $paid = (float) DB::table('payments')->where('payment_status', 'paid')->sum('amount');
        $pending = (float) DB::table('payments')->where('payment_status', 'pending')->sum('amount');

        $this->assertGreaterThan(0, $paid, 'The baseline has no paid payment, so the report cannot be proven.');

        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.payments'));

        $response->assertOk();
        $response->assertSee('Payment Method Report', false);
        $response->assertSee('BDT '.number_format($paid, 0), false);
        $response->assertSee('BDT '.number_format($pending, 0), false);
        $response->assertSee('paymentMethodChart', false);
        $response->assertSee('bkash', false);
    }

    public function test_courier_report_shows_database_totals(): void
    {
        $shipments = DB::table('courier_shipments')->count();
        $cost = (float) DB::table('courier_shipments')->sum('shipping_cost');
        $delivered = DB::table('courier_shipments')->whereRaw("LOWER(current_status) = 'delivered'")->count();

        $this->assertGreaterThan(0, $shipments, 'The baseline has no courier shipment.');
        $this->assertGreaterThan(0, $cost, 'The baseline has no courier cost.');

        $rate = round($delivered / $shipments * 100, 1);

        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.couriers'));

        $response->assertOk();
        $response->assertSee('Courier Performance Report', false);
        $response->assertSee('BDT '.number_format($cost, 0), false);
        $response->assertSee(number_format($rate, 1).'%', false);
        $response->assertSee('courierChart', false);
        $response->assertSee('pathao', false);
        $response->assertSee('steadfast', false);
    }

    public function test_returns_report_shows_database_totals(): void
    {
        $required = (float) DB::table('order_returns')->where('refund_required', 1)->sum('refund_amount');
        $refunded = (float) DB::table('order_returns')->where('refunded', 1)->sum('refund_amount');
        $pending = max(0.0, $required - $refunded);

        $this->assertGreaterThan(0, $required, 'The baseline has no refund to report.');

        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.returns'));

        $response->assertOk();
        $response->assertSee('Returns &amp; Cancellations Report', false);
        $response->assertSee('BDT '.number_format($required, 0), false);
        $response->assertSee('BDT '.number_format($refunded, 0), false);
        $response->assertSee('BDT '.number_format($pending, 0), false);
        $response->assertSee('refundChart', false);
        $response->assertSee('customer_return', false);
    }

    public function test_coupon_report_shows_database_totals(): void
    {
        $usages = DB::table('coupon_usages')->count();
        $discount = (float) DB::table('coupon_usages')->sum('discount_amount');
        $revenue = (float) DB::table('coupon_usages as u')
            ->join('orders as o', 'o.id', '=', 'u.order_id')
            ->sum('o.amount');

        $this->assertGreaterThan(0, $usages, 'The baseline has no coupon usage.');
        $this->assertGreaterThan(0, $discount, 'The baseline has no coupon discount.');

        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.coupons'));

        $response->assertOk();
        $response->assertSee('Coupon Impact Report', false);
        $response->assertSee('BDT '.number_format($discount, 0), false);
        $response->assertSee('BDT '.number_format($revenue, 0), false);
        $response->assertSee('couponChart', false);
        $response->assertSee('checkout', false);
    }

    public function test_support_role_is_denied_on_every_analytics_report(): void
    {
        $support = $this->userWithRole('Support');

        $routes = [
            'admin.reports.payments',
            'admin.reports.couriers',
            'admin.reports.returns',
            'admin.reports.coupons',
        ];

        foreach ($routes as $name) {
            $this->actingAs($support)->get(route($name))->assertForbidden();
        }
    }

    public function test_manager_gets_operational_reports_but_not_the_money_report(): void
    {
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get(route('admin.reports.couriers'))->assertOk();
        $this->actingAs($manager)->get(route('admin.reports.returns'))->assertOk();
        $this->actingAs($manager)->get(route('admin.reports.coupons'))->assertOk();
        $this->actingAs($manager)->get(route('admin.reports.payments'))->assertForbidden();
    }
}
