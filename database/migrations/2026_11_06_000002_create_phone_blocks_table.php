<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phone-based blocking for fake/anonymous orders.
 *
 * An unverified customer is only a phone number, so that number is the identity
 * the storefront can actually enforce. The unique key is the normalised form
 * ("8801..." / "+8801..." / "01..." all collapse to "01..."), which is why the
 * admin can block a number typed in any of those shapes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('original_phone', 40)->nullable();
            $table->string('reason', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_blocked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_blocks');
    }
};
