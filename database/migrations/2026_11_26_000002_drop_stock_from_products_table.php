<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DROP-LEGACY-STOCK: remove the legacy `products.stock` column.
 *
 * Stock lives in `variant_stocks` (projection) backed by
 * `inventory_movements` (ledger). The product-level column predates the
 * variant system, drifted from the projection, and was the source of the
 * "admin says 50, shop says out of stock" bug.
 *
 * Every read and write path was moved to the variant projection before this
 * migration runs. The `down()` restores the column shape only; it cannot
 * rebuild the old figures, which is why the drop is treated as one-way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'stock')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'stock')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('new_price');
        });
    }
};
