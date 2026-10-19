<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        if (!Schema::hasColumn('general_settings', 'home_page_categories_section')) {
            return;
        }

        if (Schema::hasColumn('general_settings', 'home_page_top_categories_section')) {
            return;
        }

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->renameColumn('home_page_categories_section', 'home_page_top_categories_section');
            });
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE `general_settings` CHANGE `home_page_categories_section` `home_page_top_categories_section` TINYINT NOT NULL DEFAULT 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        if (!Schema::hasColumn('general_settings', 'home_page_top_categories_section')) {
            return;
        }

        if (Schema::hasColumn('general_settings', 'home_page_categories_section')) {
            return;
        }

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->renameColumn('home_page_top_categories_section', 'home_page_categories_section');
            });
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE `general_settings` CHANGE `home_page_top_categories_section` `home_page_categories_section` TINYINT NOT NULL DEFAULT 1');
        }
    }
};
