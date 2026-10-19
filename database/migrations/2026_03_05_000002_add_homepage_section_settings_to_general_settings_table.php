<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'home_hero_section')) {
                $table->tinyInteger('home_hero_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_hero_title')) {
                $table->string('home_hero_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_recommended_section')) {
                $table->tinyInteger('home_recommended_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_recommended_title')) {
                $table->string('home_recommended_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_category_section')) {
                $table->tinyInteger('home_category_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_category_title')) {
                $table->string('home_category_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_footer_section')) {
                $table->tinyInteger('home_footer_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_footer_title')) {
                $table->string('home_footer_title')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $columns = [
                'home_hero_section',
                'home_hero_title',
                'home_recommended_section',
                'home_recommended_title',
                'home_category_section',
                'home_category_title',
                'home_footer_section',
                'home_footer_title',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
