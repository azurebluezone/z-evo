<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-variant low-stock threshold.
 *
 * There is no product-level threshold on purpose: every product has variants,
 * and two thresholds would make it impossible to say which one raised an alert.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('variant_stocks')) {
            return;
        }

        if (! Schema::hasColumn('variant_stocks', 'low_stock_threshold')) {
            Schema::table('variant_stocks', function (Blueprint $table) {
                $table->integer('low_stock_threshold')->default(10)->after('damaged');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('variant_stocks') && Schema::hasColumn('variant_stocks', 'low_stock_threshold')) {
            Schema::table('variant_stocks', function (Blueprint $table) {
                $table->dropColumn('low_stock_threshold');
            });
        }
    }
};
