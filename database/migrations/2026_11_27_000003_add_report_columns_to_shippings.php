<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 — shippings.
 *
 * C2 district_id  the basis of the district report (Sales, Courier, Returns)
 * C3 division     rollup column for the same pages
 *
 * No backfill: shippings.area holds free text ('ঢাকার ভিতরে', 'ঢাকার বাহিরে',
 * 'Standard delivery') and the districts table is empty, so an old row cannot
 * be mapped to a district_id without inventing one. Old rows stay NULL; the
 * checkout form fills the column from now on. No foreign key, for the same
 * reason (LIVE_SCHEMA_VERIFIED 2026-11-27).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->unsignedInteger('district_id')->nullable()->after('area');
            $table->string('division', 32)->nullable()->after('district_id');

            $table->index('district_id');
            $table->index('division');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['division']);
            $table->dropIndex(['district_id']);

            $table->dropColumn(['district_id', 'division']);
        });
    }
};
