<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line-level return attribution.
 *
 * `order_returns` is the order-level header (reason, mode, approval, refund).
 * The items table answers "which line, how many, how much" so return rate and
 * refund value can be reported per product instead of per order.
 *
 * The UNIQUE index on `order_returns.order_id` is dropped in the same
 * migration: a partial return and a later return for the same order are two
 * events, and the header row must be allowed to repeat. The normal index keeps
 * the `where order_id = ?` lookup fast.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_returns')) {
            if (Schema::hasIndex('order_returns', 'order_returns_order_id_unique', 'unique')) {
                Schema::table('order_returns', function (Blueprint $table) {
                    $table->dropUnique('order_returns_order_id_unique');
                });
            }

            if (! Schema::hasIndex('order_returns', 'order_returns_order_id_index')) {
                Schema::table('order_returns', function (Blueprint $table) {
                    $table->index('order_id', 'order_returns_order_id_index');
                });
            }
        }

        if (! Schema::hasTable('order_return_items')) {
            Schema::create('order_return_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_return_id')->index();
                $table->unsignedBigInteger('order_detail_id')->index();
                $table->unsignedBigInteger('variant_id')->nullable()->index();
                $table->unsignedInteger('qty');
                $table->decimal('refund_amount', 12, 2)->default(0);
                $table->string('restock_bucket', 20)->default('sellable')->index();
                $table->timestamps();

                // One line appears once per return event. Without this a
                // re-run of a return workflow could double the refund value.
                $table->unique(['order_return_id', 'order_detail_id'], 'order_return_items_return_detail_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_items');

        if (Schema::hasTable('order_returns')) {
            if (Schema::hasIndex('order_returns', 'order_returns_order_id_index')) {
                Schema::table('order_returns', function (Blueprint $table) {
                    $table->dropIndex('order_returns_order_id_index');
                });
            }

            // Re-adding the UNIQUE index can fail when a database already has
            // multiple returns for one order. That is a data state this down()
            // deliberately does not destroy; restore it manually after
            // cleaning duplicates.
        }
    }
};
