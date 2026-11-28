<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 item 2 — one order has exactly one primary attribution.
 *
 * order_attributions was created on 2026-11-27 with a plain index on order_id
 * and no rows. This migration adds a UNIQUE index on the same column so a
 * second writer (a retried checkout, a backfill, a manual insert) cannot create
 * a competing primary row.
 *
 * The existing plain index stays: MySQL reports error 1553 if it is dropped
 * while order_attributions_order_id_foreign still depends on it, and the
 * redundancy costs nothing on a table with one row per order.
 *
 * The dedupe delete only exists to keep the migration safe on a database where
 * duplicates were inserted by hand; on the current schema it removes nothing.
 * MySQL refuses a DELETE whose subquery reads the same table (error 1093), so
 * the surviving ids are materialised in a derived table.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DELETE FROM order_attributions WHERE id NOT IN ('
            .'SELECT keep_id FROM ('
            .'SELECT MIN(id) AS keep_id FROM order_attributions GROUP BY order_id'
            .') AS keep_rows)'
        );

        Schema::table('order_attributions', function (Blueprint $table) {
            $table->unique('order_id', 'order_attributions_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('order_attributions', function (Blueprint $table) {
            $table->dropUnique('order_attributions_order_id_unique');
        });
    }
};
