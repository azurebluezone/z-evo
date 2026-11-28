<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 — order_details.
 *
 * C17 cost_status  'ok' / 'missing' / 'estimated' — the Product and Profit pages
 *                  must not show a margin built on a zero purchase price.
 *
 * Indexes order_id and product_id: every report joins this table by order or
 * by product and neither index existed (SHOW INDEX FROM order_details showed
 * only PRIMARY, variant_id and campaign_id).
 *
 * Backfill states the rule from the metric dictionary: purchase_price NULL or
 * 0 means the cost is missing. Live check 2026-11-27: 36 rows, 0 null, 0 zero,
 * so every existing row stays 'ok'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('cost_status', 16)->default('ok');

            $table->index('cost_status');
            $table->index('order_id');
            $table->index('product_id');
        });

        DB::table('order_details')
            ->whereNull('purchase_price')
            ->orWhere('purchase_price', 0)
            ->update(['cost_status' => 'missing']);
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['order_id']);
            $table->dropIndex(['cost_status']);

            $table->dropColumn('cost_status');
        });
    }
};
