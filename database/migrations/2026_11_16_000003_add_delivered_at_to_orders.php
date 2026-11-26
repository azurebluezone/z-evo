<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status timestamps that mean what they say.
 *
 * Reports used to measure deliveries by `updated_at`, which any later edit
 * rewrites. These columns are written once, by the order lifecycle, and are the
 * only dates the realised-profit and trend reports should use.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->index()->after('order_status');
            }

            if (! Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->index()->after('delivered_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('orders', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
        });
    }
};
