<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders written after 2026_10_22_000001_normalize_order_status_values ran
 * could still contain the slug spelling: DemoOrderDetailsSeeder inserted
 * 'pending' directly with the query builder, which bypasses model mutators,
 * and the demo order then vanished from the Pending tab (recordsFiltered 0).
 *
 * The seeder now writes the status id and Order has a mutator that converts
 * a slug on write. This pass cleans rows created in between. It is
 * idempotent: numeric values are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_statuses')) {
            return;
        }

        foreach (DB::table('order_statuses')->get(['id', 'slug']) as $status) {
            if ($status->slug === null || $status->slug === '') {
                continue;
            }

            DB::table('orders')
                ->whereRaw('LOWER(TRIM(order_status)) = ?', [strtolower(trim($status->slug))])
                ->update(['order_status' => (string) $status->id]);
        }
    }

    public function down(): void
    {
        // Normalisation is irreversible on purpose: the slug spelling
        // re-hides rows from the admin tabs.
    }
};
