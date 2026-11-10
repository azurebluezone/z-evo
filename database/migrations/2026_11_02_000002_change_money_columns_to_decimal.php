<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money columns were INT, so a coupon discount of ৳12.50 was stored as 12 and
 * the customer was overcharged by the paisa. DECIMAL(12,2) keeps two decimal
 * places through checkout, order rows, line items and the payment record.
 *
 * 12 digits total (10 before the decimal) is far above any order this shop can
 * take and stays compatible with the DECIMAL(12,2) already used by
 * coupon_usages.discount_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->change();
            $table->decimal('discount', 12, 2)->change();
            $table->decimal('shipping_charge', 12, 2)->change();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->change();
            $table->decimal('sale_price', 12, 2)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('amount')->change();
            $table->integer('discount')->change();
            $table->integer('shipping_charge')->change();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->integer('purchase_price')->change();
            $table->integer('sale_price')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->integer('amount')->change();
        });
    }
};
