<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 item 5: an abandoned cart is a stored row, not a guess made later
 * from logs.
 *
 * One row per cart lifecycle. `cart_key` is the session id the shopping cart
 * itself lives in, so every cart mutation can find the same row. The status
 * column is the whole point of the table:
 *
 *   open       items are in the cart and the customer may still check out
 *   abandoned  idle longer than the configured window, no order
 *   recovered  an admin marked it recovered (the state is recorded, not implied)
 *   converted  the cart produced an order; order_id names it
 *
 * Nothing here is derived at read time: the report reads the stored status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();

            // The shopping cart is session-backed (hardevine/shoppingcart), so
            // the session id is the stable handle for one cart lifecycle.
            $table->string('cart_key', 64)->unique();

            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('email', 155)->nullable();
            $table->string('phone', 55)->nullable();

            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->string('status', 16)->default('open')->index();
            $table->string('recovery_token', 64)->nullable()->unique();

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            // orders.id is INT UNSIGNED on this schema.
            $table->unsignedInteger('order_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
