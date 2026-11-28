<?php

namespace App\Console\Commands;

use App\Services\Analytics\CartActivityRecorder;
use Illuminate\Console\Command;

/**
 * Phase 3 item 5 — move idle carts from `open` to `abandoned`.
 *
 * The window is configuration, not a constant: `config('reports.
 * abandoned_cart_window_hours')` with `ABANDONED_CART_WINDOW_HOURS` as the
 * environment override. `--window` exists for a one-off backfill.
 *
 * Schedule it however the host runs cron; the command is idempotent because it
 * only touches rows that are still `open`.
 */
class MarkAbandonedCarts extends Command
{
    protected $signature = 'carts:mark-abandoned
                            {--window= : Hours of inactivity before a cart counts as abandoned}';

    protected $description = 'Flag open carts idle past the configured window as abandoned';

    public function handle(CartActivityRecorder $recorder): int
    {
        $hours = $this->option('window') !== null
            ? max(1, (int) $this->option('window'))
            : $recorder->windowHours();

        $count = $recorder->markAbandoned($hours);

        $this->info("Marked {$count} cart(s) abandoned after {$hours} hour(s) of inactivity.");

        return self::SUCCESS;
    }
}
