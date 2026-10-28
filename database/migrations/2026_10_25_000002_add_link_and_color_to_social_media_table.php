<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The social icon form has always posted `link` and `color`, and the storefront
 * footer has always read `$social->link` - but the table only ever had title,
 * icon and status. The columns were dropped silently on save, so every icon
 * added from the admin pointed at "#".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media', function (Blueprint $table) {
            $table->string('link', 255)->nullable()->after('icon');
            $table->string('color', 20)->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('social_media', function (Blueprint $table) {
            $table->dropColumn(['link', 'color']);
        });
    }
};
