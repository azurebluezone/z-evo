<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free shipping needs two facts, not zero.
 *
 * `shipping_charge` is what the customer actually paid (0 when free). The
 * normal rate for that area is snapshotted in `shipping_list_price`, so the
 * report can say "৳120 of delivery was given away" without inventing a second
 * courier cost. `shipping_is_free` is the switch the reports filter on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_is_free')) {
                $table->boolean('shipping_is_free')->default(false)->index()->after('shipping_charge');
            }

            if (! Schema::hasColumn('orders', 'shipping_list_price')) {
                $table->decimal('shipping_list_price', 12, 2)->nullable()->after('shipping_is_free');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_list_price')) {
                $table->dropColumn('shipping_list_price');
            }

            if (Schema::hasColumn('orders', 'shipping_is_free')) {
                $table->dropColumn('shipping_is_free');
            }
        });
    }
};
