<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The real courier cost, stored where it can be queried.
 *
 * Pathao returns the delivery fee inside `raw_payload.data.delivery_fee`, but a
 * status sync overwrites `raw_payload` on every callback. Using JSON_EXTRACT as
 * a cost column would therefore lose history. `shipping_cost` is the durable
 * number the profit report subtracts; the customer-facing charge stays in
 * `orders.shipping_charge` and must not be mixed with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courier_shipments')) {
            return;
        }

        if (! Schema::hasColumn('courier_shipments', 'shipping_cost')) {
            Schema::table('courier_shipments', function (Blueprint $table) {
                $table->decimal('shipping_cost', 12, 2)->nullable()->after('current_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courier_shipments') && Schema::hasColumn('courier_shipments', 'shipping_cost')) {
            Schema::table('courier_shipments', function (Blueprint $table) {
                $table->dropColumn('shipping_cost');
            });
        }
    }
};
