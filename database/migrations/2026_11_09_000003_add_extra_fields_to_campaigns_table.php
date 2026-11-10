<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template-specific landing content.
 *
 * Product data (title, description, image, gallery) stays on the product row.
 * This column only holds copy an admin writes for the selected template:
 * repeatable rows (features, benefits, ...) and small text fields
 * (video_url, contact_phone, social links).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('extra_fields')->nullable()->after('allow_coupon');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('extra_fields');
        });
    }
};
