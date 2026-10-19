<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds reviews.customer_id.
 *
 * WHY THIS MIGRATION IS NEEDED
 * -------------------------------------------------------------------------
 * The column is referenced in three places that all predate this file:
 *
 *   app/Models/Review.php               customer() belongsTo relation
 *   FrontendController::details()       eager loads 'reviews.customer'
 *   CustomerController                  writes customer_id when saving
 *
 * ...but no migration ever created it, so on any database built from these
 * migrations the reviews table has eight columns and no customer_id. The
 * product page then fails with
 *
 *   SQLSTATE[42S22]: Column not found: 1054 Unknown column 'customer_id'
 *
 * for EVERY product that carries an active review, because the eager load
 * asks the database for the column even when no review needs it.
 *
 * Nullable and indexed: reviews written by a guest (name/email only) have no
 * customer, so the column must allow null. The index serves the belongsTo
 * lookup from Review::customer().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reviews', 'customer_id')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')
                ->nullable()
                ->after('product_id')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reviews', 'customer_id')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};