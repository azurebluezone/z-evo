<?php

namespace App\Services\Admin;

use App\Models\ReportSetting;
use Illuminate\Support\Facades\DB;

/**
 * The low-stock signal.
 *
 * A product is in the batch when `available <= stock_warning_threshold`.
 * With the default threshold of 0 that means out-of-stock only; raising the
 * threshold widens the batch.
 *
 * The signal stays visible until the admin dismisses it. Dismissal stores a
 * signature of the batch (md5 of the sorted variant ids), so the signal comes
 * back when a NEW low-stock product joins the batch, but a quantity change on
 * an already-known product does not nag the admin again.
 */
class StockAlertSignal
{
    public function state(?ReportSetting $settings = null): array
    {
        $settings = $settings ?? ReportSetting::current();
        $warning = (int) $settings->stock_warning_threshold;
        $remaining = (int) $settings->stock_remaining_threshold;

        $rows = DB::table('variant_stocks as s')
            ->leftJoin('product_variants as v', 'v.id', '=', 's.variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->where('s.available', '<=', $warning)
            ->orderBy('s.available')
            ->orderBy('s.variant_id')
            ->get([
                's.variant_id',
                's.available',
                'v.sku',
                'v.title',
                'p.name as product_name',
            ]);

        $ids = $rows->pluck('variant_id')->map(fn ($id) => (int) $id)->all();
        sort($ids);

        $signature = md5(implode(',', $ids));
        $out = 0;
        $low = 0;

        foreach ($rows as $row) {
            if ((int) $row->available <= 0) {
                $out++;
            } else {
                $low++;
            }
        }

        $remainingCount = DB::table('variant_stocks')
            ->where('available', '>', 0)
            ->where('available', '<=', $remaining)
            ->count();

        $items = $rows->take(5)->map(fn ($row) => [
            'variant_id' => (int) $row->variant_id,
            'sku' => (string) ($row->sku ?? ''),
            'title' => (string) ($row->title ?? ''),
            'product_name' => (string) ($row->product_name ?? ''),
            'available' => (int) $row->available,
        ])->all();

        return [
            'count' => count($ids),
            'items' => $items,
            'signature' => $signature,
            'dismissed' => $settings->stock_alert_dismissed_signature === $signature,
            'active' => count($ids) > 0 && $settings->stock_alert_dismissed_signature !== $signature,
            'warning' => $warning,
            'remaining' => $remaining,
            'out' => $out,
            'low' => $low,
            'remaining_count' => $remainingCount,
        ];
    }
}
