<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Models\ReportSetting;
use App\Services\Admin\ReportMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the daily A-to-Z report.
 *
 * The scheduler calls this every minute; the command decides whether this is
 * the minute the admin asked for. It refuses to send twice on the same day,
 * so a scheduler retry cannot spam the inbox.
 */
class SendDailyReport extends Command
{
    protected $signature = 'reports:send-daily {--force : Ignore the configured clock, still once per day}';

    protected $description = 'Send the A-to-Z report to the configured admin email.';

    public function handle(ReportMetricsService $metrics): int
    {
        $settings = ReportSetting::current();

        if (! $settings->auto_send_enabled) {
            $this->info('Auto send is disabled.');

            return self::SUCCESS;
        }

        if (! $settings->report_email) {
            $this->warn('No report email is configured.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && now()->format('H:i') !== $settings->sendTimeHm()) {
            return self::SUCCESS;
        }

        if ($settings->last_sent_on && $settings->last_sent_on->isToday()) {
            $this->info('Already sent today.');

            return self::SUCCESS;
        }

        [$from, $to, $label] = $metrics->range(null, null);

        Mail::to($settings->report_email)->send(
            new DailyReportMail($metrics->summary($from, $to), $label)
        );

        $settings->update(['last_sent_on' => now()->toDateString()]);

        $this->info('Report sent to '.$settings->report_email);

        return self::SUCCESS;
    }
}
