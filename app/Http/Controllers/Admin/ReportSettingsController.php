<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DailyReportMail;
use App\Models\ReportSetting;
use App\Services\Admin\ReportMetricsService;
use App\Services\Admin\StockAlertSignal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Toastr;

/**
 * Stock thresholds, the report email and the auto-send schedule.
 *
 * Thresholds are stored exactly as submitted. The defaults live in the
 * migration (warning 0, remaining 2) and `ReportSetting::current()` creates
 * the singleton with those defaults, so leaving the admin alone means 0 and 2,
 * while setting 0 explicitly also means 0.
 */
class ReportSettingsController extends Controller
{
    public function edit()
    {
        $settings = ReportSetting::current();

        return view('backEnd.settings.report', [
            'settings' => $settings,
            'mailer' => (string) config('mail.default'),
            'mailHost' => (string) config('mail.mailers.smtp.host'),
            'mailFrom' => (string) config('mail.from.address'),
            'signal' => app(StockAlertSignal::class)->state($settings),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stock_warning_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'stock_remaining_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'report_email' => ['nullable', 'email', 'max:190'],
            'send_time' => ['required', 'date_format:H:i'],
        ]);

        $data['auto_send_enabled'] = $request->boolean('auto_send_enabled');
        $data['send_time'] = $data['send_time'].':00';

        ReportSetting::current()->update($data);

        Toastr::success('Report settings updated.');

        return redirect()->route('admin.reports.settings.edit');
    }

    public function sendTest(Request $request, ReportMetricsService $metrics): RedirectResponse
    {
        $request->validate([
            'report_email' => ['required', 'email', 'max:190'],
        ]);

        $email = (string) $request->input('report_email');

        [$from, $to, $label] = $metrics->range(null, null);

        try {
            Mail::to($email)->send(new DailyReportMail($metrics->summary($from, $to), $label));
            Toastr::success('Test report sent to '.$email);
        } catch (\Throwable $e) {
            Toastr::error('Mail failed: '.$e->getMessage());
        }

        return redirect()->route('admin.reports.settings.edit');
    }

    public function dismissStockAlert(StockAlertSignal $signal): RedirectResponse
    {
        $settings = ReportSetting::current();
        $state = $signal->state($settings);

        $settings->update([
            'stock_alert_dismissed_signature' => $state['signature'],
        ]);

        Toastr::success('Stock alert dismissed.');

        return redirect()->back();
    }
}
