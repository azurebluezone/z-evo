<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the front-end template selector.
 *
 * The column stores the key of the template set that should answer front-end
 * view lookups. It defaults to the original design so an existing install keeps
 * rendering exactly what it rendered before this feature existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('general_settings', 'frontend_theme')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('frontend_theme', 32)->default('classic')->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('general_settings', 'frontend_theme')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('frontend_theme');
        });
    }
};
