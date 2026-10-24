<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * orders.order_status is a varchar(55) that has held two different spellings:
 *
 *   - "1"        the numeric id written by the storefront order_save(),
 *                the admin status forms and CourierWebhookController
 *   - "pending"  the slug left behind by older code paths
 *
 * Every admin query filters `where('order_status', $orderStatus->id)`, so the
 * slug rows are invisible on their own tab, are missing from the KPI counts,
 * and render with an empty status badge. This normalizes the legacy rows to
 * the id spelling that the current writers use.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('order_statuses')) {
            return;
        }

        $statuses = DB::table('order_statuses')->get(['id', 'slug']);

        foreach ($statuses as $status) {
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
        // Data normalization is intentionally irreversible: restoring the
        // slug spelling would re-hide the rows from the admin tabs.
    }
};
