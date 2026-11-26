<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real soft deletes for `products`.
 *
 * Why: ProductController::destroy() called ->delete() on a plain Model, which
 * issued a hard DELETE. Every order_details / review / inventory row that
 * pointed at the product kept the id but lost the product, so order history
 * and stock reconciliation could no longer resolve it.
 *
 * With deleted_at + the SoftDeletes trait the row stays, Eloquent hides it from
 * normal queries (storefront, admin list, route-model binding), and a mistaken
 * delete can be restored. Related rows (images, sizes, colours, variants) are
 * deliberately left in place so a restore is complete.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard against a hand-applied column: re-adding it would fail.
        if (Schema::hasColumn('products', 'deleted_at')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'deleted_at')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
