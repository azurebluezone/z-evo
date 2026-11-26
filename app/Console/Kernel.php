<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Shared-hosting queue drain. With QUEUE_CONNECTION=database there is
        // no supervisor or Horizon on a cheap host: this runs at most 50
        // seconds every minute, exits when the queue is empty, and the overlap
        // lock stops a slow worker from being started twice. With the default
        // sync driver it is a no-op.
        //
        // Requires the one cron line documented in
        // docs/PLAN-notifications-2026-11-16.md:
        //   * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
        $schedule->command('queue:work --stop-when-empty --max-time=50 --tries=3')
            ->everyMinute()
            ->withoutOverlapping(10);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
