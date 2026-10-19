<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add product_size and product_color to order_details.
 *
 * WHY
 * CustomerController@order_save() has always written both values onto each
 * order line, but neither column existed on the table, so the INSERT failed:
 *
 *     SQLSTATE[42S22] Column not found: 1054 Unknown column 'product_color'
 *     in 'field list'
 *
 * and every guest checkout returned HTTP 500. Verified live against this
 * database before the migration - it was the third fatal defect in the same
 * method (after `orders.note` and the NOT NULL `customers.email`).
 *
 * WHY THE COLUMNS ARE NEEDED RATHER THAN JUST DROPPED
 * These lines are not decorative. The cart stores, per line:
 *
 *     options.variant_name    the label the customer actually chose ("Navy / L")
 *     options.product_color   the colour
 *     options.product_size    "variant_id:123" - a storage marker, not a size
 *
 * order_details.variant_id already records WHICH variant row was bought, but
 * that is a foreign key: rendering it on an invoice, an admin order screen or a
 * packing slip needs the human-readable label, and the variant row can be
 * edited or deleted later, which would leave historic orders unreadable.
 * Storing the text at purchase time is what makes an order a true record.
 *
 * Both are nullable so existing rows stay valid and so a product with no
 * variant simply stores null.
 *
 * order_save() maps product_size to the variant_name when it has one, and only
 * falls back to the raw "variant_id:N" marker otherwise - the raw marker is not
 * shown to customers.
 *
 * DOWN drops them again, which discards the historic size/colour text on every
 * past order line. That is the correct warning to leave behind.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_details')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            if (! Schema::hasColumn('order_details', 'product_size')) {
                $table->string('product_size', 155)->nullable()->after('product_name');
            }

            if (! Schema::hasColumn('order_details', 'product_color')) {
                $table->string('product_color', 155)->nullable()->after('product_size');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_details')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['product_size', 'product_color'],
                fn (string $column) => Schema::hasColumn('order_details', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
