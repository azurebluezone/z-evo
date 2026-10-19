<?php

namespace App\Console\Commands;

use App\Models\CourierFraudCheck;
use App\Models\Order;
use Illuminate\Console\Command;

class BackfillOrderRiskLevelsCommand extends Command
{
    protected $signature = 'orders:backfill-risk-levels {--chunk=500 : Number of orders to process per batch}';

    protected $description = 'Backfill missing order risk_label and risk_score_percent from latest courier_fraud_checks; set unknown when not found';

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^0-9\+]/', '', $phone);
        if (str_starts_with($phone, '88') && strlen($phone) > 11) {
            $phone = ltrim($phone, '88');
        }
        if (str_starts_with($phone, '+88')) {
            $phone = substr($phone, 3);
        }

        return $phone;
    }

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize < 1) {
            $chunkSize = 500;
        }

        $updated = 0;
        $setUnknown = 0;
        $skippedHasRisk = 0;
        $skippedNoPhone = 0;

        Order::query()
            ->with('shipping')
            ->orderBy('id')
            ->chunk($chunkSize, function ($orders) use (&$updated, &$setUnknown, &$skippedHasRisk, &$skippedNoPhone) {
                $phones = $orders
                    ->map(fn ($o) => $o->shipping ? $this->normalizePhone((string) ($o->shipping->phone ?? '')) : null)
                    ->filter(fn ($p) => $p)
                    ->unique()
                    ->values();

                $latestChecks = collect();
                if ($phones->isNotEmpty()) {
                    $latestChecks = CourierFraudCheck::query()
                        ->select('phone', 'risk_label', 'success_rate_percent', 'checked_at')
                        ->whereIn('phone', $phones)
                        ->orderByDesc('checked_at')
                        ->get()
                        ->groupBy('phone')
                        ->map(fn ($rows) => $rows->first());
                }

                foreach ($orders as $order) {
                    if (!empty($order->risk_label)) {
                        $skippedHasRisk++;
                        continue;
                    }

                    $phone = $order->shipping ? $this->normalizePhone((string) ($order->shipping->phone ?? '')) : '';
                    if ($phone === '') {
                        $order->risk_label = 'unknown';
                        $order->risk_score_percent = null;
                        $order->risk_last_checked_at = now();
                        $order->risk_source = 'unknown';
                        $order->save();

                        $setUnknown++;
                        $skippedNoPhone++;
                        continue;
                    }

                    $check = $latestChecks->get($phone);
                    if ($check) {
                        $order->risk_label = (string) $check->risk_label;
                        $order->risk_score_percent = $check->success_rate_percent;
                        $order->risk_last_checked_at = $check->checked_at ?? now();
                        $order->risk_source = 'courier_fraud_checks';
                        $order->save();

                        $updated++;
                        continue;
                    }

                    $order->risk_label = 'unknown';
                    $order->risk_score_percent = null;
                    $order->risk_last_checked_at = now();
                    $order->risk_source = 'unknown';
                    $order->save();

                    $setUnknown++;
                }
            });

        $this->info('Backfill completed.');
        $this->line('Updated from checks: ' . $updated);
        $this->line('Set to unknown: ' . $setUnknown);
        $this->line('Skipped (already had risk): ' . $skippedHasRisk);
        $this->line('Skipped (no phone): ' . $skippedNoPhone);

        return self::SUCCESS;
    }
}
