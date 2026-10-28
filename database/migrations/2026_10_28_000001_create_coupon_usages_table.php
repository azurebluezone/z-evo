<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per coupon use on a real order (storefront checkout or POS).
 *
 * Before this table, `coupons.used_count` existed but no code ever wrote to
 * it and no order recorded which coupon it honoured - only the discounted
 * total reached `orders.discount`. That made "how many times was this code
 * used?" unanswerable (reported 2026-10-28). This table is the usage log:
 * the admin coupon details page counts today and total from here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('coupon_id');
            // Null allowed: a row keeps its coupon_id even if the coupon is
            // later deleted, so historical counts survive.
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('coupon_code', 100);
            // Source channel, so the details view can separate till sales
            // from online orders. 'checkout' | 'pos'.
            $table->string('source', 20)->default('checkout');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index('coupon_id');
            $table->index('order_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
