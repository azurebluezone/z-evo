<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `orders.user_id` was written by the admin but the column was never created.
 *
 * Three places assume it exists:
 *
 * 1. Admin\OrderController@order_assign() -
 *      Order::whereIn('id', ...)->update(['user_id' => $request->user_id])
 *    The "Assign User" item in the orders screen posts to it, and the orders
 *    screen renders a modal with a select of admin users. The update raised
 *    SQLSTATE[42S22] "Unknown column 'user_id'", so the button was dead.
 *
 * 2. Admin\OrderController@order_report() filtered the sales report with
 *    `whereHas('order', fn => where('user_id', ...))`. The report's
 *    "Assign User" filter raised the same error, so the whole report page
 *    returned a 500 whenever that filter was used.
 *
 * 3. The order board's assign action reads the same column back.
 *
 * The column is the admin user the order is assigned to (it holds a users.id,
 * not a customers.id - orders.customer_id is the customer, and it already
 * exists).
 *
 * Added as nullable so the existing rows and every path that does not assign
 * a user are untouched. Indexed because the report filters on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('customer_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
