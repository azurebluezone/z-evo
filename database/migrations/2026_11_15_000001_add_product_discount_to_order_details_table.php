<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * order_details.product_discount
 *
 * The POS screens have always written a per-line discount - the cart carries
 * it as an option, the order line item stores it, and the order edit screen
 * reads it back to rebuild the cart:
 *
 *     app/Http/Controllers/Admin/OrderController.php
 *         $order_details->product_discount = $cart->options->product_discount;  (create + edit)
 *         'product_discount' => $ordetails->product_discount,                    (edit, back into the cart)
 *
 * But the column was never in the schema: the original
 * create_order_details_table migration had id, order_id, product_id,
 * product_name, purchase_price, sale_price, qty and timestamps - no
 * product_discount. So every insert of an order line item died with
 *
 *     SQLSTATE[42S22]: Column not found: 1054 Unknown column 'product_discount'
 *     in 'field list' (insert into `order_details` ...)
 *
 * The order row itself was already saved by then, so the admin ended up with
 * an order that had NO line items and an error toast reading
 * "Order creation failed" - the POS screen could not save a real order.
 *
 * DECIMAL(12,2) matches the money columns that
 * 2026_11_02_000002_change_money_columns_to_decimal.php gave this table, so a
 * discount of ৳12.50 keeps its paisa instead of being truncated.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_details')) {
            return;
        }

        if (! Schema::hasColumn('order_details', 'product_discount')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->decimal('product_discount', 12, 2)->default(0)->after('purchase_price');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_details')) {
            return;
        }

        if (Schema::hasColumn('order_details', 'product_discount')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->dropColumn('product_discount');
            });
        }
    }
};
