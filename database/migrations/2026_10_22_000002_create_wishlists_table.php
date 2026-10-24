<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the wishlists table.
 *
 * WHY THIS MIGRATION IS NEEDED
 * -------------------------------------------------------------------------
 * The customer wishlist was session-only (`session('wishlist', [])` in
 * routes/web.php add-to-wishlist/remove-from-wishlist/wishlist-count). The
 * admin panel could therefore never show "who wishlisted which product":
 * session data dies with the browser session and is invisible to the server
 * after the response is sent.
 *
 * The table stores one row per (customer, product). Guests keep the old
 * session behaviour (see the route closures); only logged-in customers
 * (guard: `customer`, model: App\Models\Customer) get durable rows here, so
 * admin can list and manage them at admin/wishlist.
 *
 * Unique index: adding the same product twice must not create a second row
 * (frontend used array_unique on the session; the DB enforces it here).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wishlists')) {
            return;
        }

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->unique(['customer_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
