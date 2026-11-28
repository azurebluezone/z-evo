<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 (work/stok/report-column-map-2026-11-27.md) — orders.
 *
 * C1  order_status_id        ends the slug/id split (CAST stops being needed)
 * C15 cancel_reason          why an order was cancelled
 * C18 source                 where the order came from
 * C19 attributed_visitor_id  visitor -> order bridge
 *
 * Plus the index set the report queries filter on: created_at, order_status
 * and (customer_id, created_at). Every index below was verified missing with
 * SHOW INDEX FROM orders before this file was written.
 *
 * Backfill only rewrites what the row already states: order_status holds the
 * numeric id as a string ('1'..'6') today; slug values are matched against
 * order_statuses.slug. Nothing is guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('order_status_id')->nullable()->after('order_status');
            $table->string('cancel_reason', 64)->nullable()->after('cancelled_at');
            $table->string('source', 32)->nullable();
            $table->string('attributed_visitor_id', 64)->nullable();

            $table->index('order_status_id');
            $table->index('cancel_reason');
            $table->index('source');
            $table->index('attributed_visitor_id');
            $table->index('created_at');
            $table->index('order_status');
            $table->index(['customer_id', 'created_at']);
        });

        $this->backfillStatusId();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['order_status']);
            $table->dropIndex(['attributed_visitor_id']);
            $table->dropIndex(['source']);
            $table->dropIndex(['cancel_reason']);
            $table->dropIndex(['order_status_id']);

            $table->dropColumn([
                'order_status_id',
                'cancel_reason',
                'source',
                'attributed_visitor_id',
            ]);
        });
    }

    private function backfillStatusId(): void
    {
        DB::statement(
            'UPDATE orders SET order_status_id = CAST(order_status AS UNSIGNED)'
            . ' WHERE order_status REGEXP "^[0-9]+$" AND order_status_id IS NULL'
        );

        foreach (DB::table('order_statuses')->get(['id', 'slug']) as $status) {
            DB::table('orders')
                ->whereNull('order_status_id')
                ->where('order_status', $status->slug)
                ->update(['order_status_id' => (int) $status->id]);
        }
    }
};
