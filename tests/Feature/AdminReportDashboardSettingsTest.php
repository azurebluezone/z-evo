<?php

namespace Tests\Feature;

use App\Mail\DailyReportMail;
use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The report dashboard, the settings form and the scheduled email.
 *
 * DatabaseTransactions keeps the live development database clean: every write
 * here (settings, stock quantities) is rolled back at the end of the test.
 */
class AdminReportDashboardSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private function userWithRole(string $role): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->first();

        $this->assertNotNull($user, "No user with role {$role}, so the report cannot be authenticated.");

        return $user;
    }

    private function deliveredDay(): string
    {
        $day = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNotNull('delivered_at')
            ->max(DB::raw('DATE(delivered_at)'));

        $this->assertNotNull($day, 'The baseline has no delivered order, so the dashboard cannot be proven.');

        return (string) $day;
    }

    public function test_dashboard_recomputes_revenue_expense_and_profit(): void
    {
        $day = $this->deliveredDay();

        $deliveredQuery = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereDate('delivered_at', $day);

        $orderIds = (clone $deliveredQuery)->pluck('id')->all();
        $revenue = (float) (clone $deliveredQuery)->sum('amount');

        $cogs = 0.0;
        if ($orderIds !== []) {
            $cogs = (float) DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->sum(DB::raw('purchase_price * qty'));
        }

        $courier = (float) DB::table('courier_shipments')->whereDate('created_at', $day)->sum('shipping_cost');
        $refund = (float) DB::table('order_returns')->where('refunded', 1)->whereDate('created_at', $day)->sum('refund_amount');
        $expense = $cogs + $courier + $refund;
        $profit = $revenue - $expense;

        $this->assertGreaterThan(0, $revenue, 'The chosen delivered day has no revenue.');

        $response = $this->actingAs($this->userWithRole('Admin'))
            ->get(route('admin.reports.dashboard', ['from' => $day, 'to' => $day]));

        $response->assertOk();
        $response->assertSee('Report Dashboard', false);
        $response->assertSee('BDT '.number_format($revenue, 0), false);
        $response->assertSee('BDT '.number_format($expense, 0), false);
        $response->assertSee('BDT '.number_format($profit, 0), false);
        $response->assertSee('Expense arithmetic', false);
        $response->assertSee('Open stock alerts', false);
    }

    public function test_dashboard_range_filter_selects_an_empty_day(): void
    {
        $response = $this->actingAs($this->userWithRole('Admin'))
            ->get(route('admin.reports.dashboard', ['from' => '2000-01-01', 'to' => '2000-01-01']));

        $response->assertOk();
        $response->assertSee('data-report-range="01 Jan 2000"', false);
        $response->assertSee('BDT 0', false);
        $response->assertSee('No paid payment in this range.', false);
    }

    public function test_settings_defaults_are_zero_and_two(): void
    {
        ReportSetting::query()->delete();

        $settings = ReportSetting::current();

        $this->assertSame(0, (int) $settings->stock_warning_threshold);
        $this->assertSame(2, (int) $settings->stock_remaining_threshold);
        $this->assertFalse((bool) $settings->auto_send_enabled);
    }

    public function test_settings_page_is_admin_only(): void
    {
        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.settings.edit'));

        $response->assertOk();
        $response->assertSee('Warning threshold (pcs)', false);
        $response->assertSee('Remaining badge threshold (pcs)', false);
        $response->assertSee('Mail transport', false);

        $this->actingAs($this->userWithRole('Manager'))->get(route('admin.reports.settings.edit'))->assertForbidden();
        $this->actingAs($this->userWithRole('Support'))->get(route('admin.reports.settings.edit'))->assertForbidden();
    }

    public function test_settings_update_keeps_an_explicit_zero(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)->post(route('admin.reports.settings.update'), [
            'stock_warning_threshold' => 0,
            'stock_remaining_threshold' => 0,
            'report_email' => 'ops@example.com',
            'send_time' => '09:30',
        ])->assertRedirect(route('admin.reports.settings.edit'));

        $settings = ReportSetting::current()->fresh();

        $this->assertSame(0, (int) $settings->stock_warning_threshold);
        $this->assertSame(0, (int) $settings->stock_remaining_threshold);
        $this->assertSame('09:30:00', (string) $settings->send_time);
        $this->assertSame('ops@example.com', $settings->report_email);

        $this->actingAs($admin)->post(route('admin.reports.settings.update'), [
            'stock_warning_threshold' => 5,
            'stock_remaining_threshold' => 2,
            'report_email' => 'ops@example.com',
            'send_time' => '10:00',
            'auto_send_enabled' => '1',
        ])->assertRedirect(route('admin.reports.settings.edit'));

        $settings = ReportSetting::current()->fresh();

        $this->assertSame(5, (int) $settings->stock_warning_threshold);
        $this->assertSame(2, (int) $settings->stock_remaining_threshold);
        $this->assertTrue((bool) $settings->auto_send_enabled);
    }

    public function test_test_mail_uses_the_configured_mailer(): void
    {
        Mail::fake();

        ReportSetting::current()->update(['report_email' => 'ops@example.com']);

        $this->actingAs($this->userWithRole('Admin'))
            ->post(route('admin.reports.settings.test'), ['report_email' => 'ops@example.com'])
            ->assertRedirect(route('admin.reports.settings.edit'));

        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('ops@example.com'));
    }

    public function test_daily_command_sends_once_per_day(): void
    {
        Mail::fake();

        ReportSetting::current()->update([
            'auto_send_enabled' => true,
            'report_email' => 'ops@example.com',
            'send_time' => '00:01:00',
            'last_sent_on' => null,
        ]);

        $this->artisan('reports:send-daily', ['--force' => true])->assertExitCode(0);

        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('ops@example.com'));
        $this->assertNotNull(ReportSetting::current()->fresh()->last_sent_on);
        $this->assertSame(1, Mail::sent(DailyReportMail::class)->count());

        $this->artisan('reports:send-daily', ['--force' => true])->assertExitCode(0);
        $this->assertSame(1, Mail::sent(DailyReportMail::class)->count());
    }

    public function test_daily_command_waits_for_the_configured_clock(): void
    {
        Mail::fake();

        ReportSetting::current()->update([
            'auto_send_enabled' => true,
            'report_email' => 'ops@example.com',
            'send_time' => now()->addHours(2)->format('H:i').':00',
            'last_sent_on' => null,
        ]);

        $this->artisan('reports:send-daily')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_daily_command_does_nothing_when_disabled(): void
    {
        Mail::fake();

        ReportSetting::current()->update([
            'auto_send_enabled' => false,
            'report_email' => 'ops@example.com',
            'send_time' => '00:01:00',
            'last_sent_on' => null,
        ]);

        $this->artisan('reports:send-daily', ['--force' => true])->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_stock_signal_stays_until_dismissed_and_returns_for_a_new_batch(): void
    {
        ReportSetting::current()->update([
            'stock_warning_threshold' => 3,
            'stock_alert_dismissed_signature' => null,
        ]);

        $variantIds = DB::table('variant_stocks')->orderBy('variant_id')->pluck('variant_id')->take(2)->values();
        $this->assertCount(2, $variantIds, 'Two variants are needed to prove the batch signature.');

        DB::table('variant_stocks')->where('variant_id', $variantIds[0])->update(['available' => 2]);

        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)
            ->get(route('admin.reports.dashboard'))
            ->assertOk()
            ->assertSee('data-stock-signal="navbar"', false);

        $this->actingAs($admin)
            ->post(route('admin.reports.stock-alerts.dismiss'))
            ->assertRedirect();

        $this->assertNotNull(ReportSetting::current()->fresh()->stock_alert_dismissed_signature);

        $this->actingAs($admin)
            ->get(route('admin.reports.dashboard'))
            ->assertOk()
            ->assertDontSee('data-stock-signal="navbar"', false);

        DB::table('variant_stocks')->where('variant_id', $variantIds[1])->update(['available' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.reports.dashboard'))
            ->assertOk()
            ->assertSee('data-stock-signal="navbar"', false);
    }

    public function test_manager_sees_the_dashboard_but_support_does_not(): void
    {
        $this->actingAs($this->userWithRole('Manager'))
            ->get(route('admin.reports.dashboard'))
            ->assertOk();

        $this->actingAs($this->userWithRole('Support'))
            ->get(route('admin.reports.dashboard'))
            ->assertForbidden();
    }
}
