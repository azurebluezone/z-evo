<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'home_promo_section')) {
                $table->tinyInteger('home_promo_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_promo_title')) {
                $table->string('home_promo_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_featured_section')) {
                $table->tinyInteger('home_featured_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_featured_title')) {
                $table->string('home_featured_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_collections_section')) {
                $table->tinyInteger('home_collections_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_collections_title')) {
                $table->string('home_collections_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_brands_section')) {
                $table->tinyInteger('home_brands_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_brands_title')) {
                $table->string('home_brands_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_service_section')) {
                $table->tinyInteger('home_service_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_service_title')) {
                $table->string('home_service_title')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $columns = [
                'home_promo_section',
                'home_promo_title',
                'home_featured_section',
                'home_featured_title',
                'home_collections_section',
                'home_collections_title',
                'home_brands_section',
                'home_brands_title',
                'home_service_section',
                'home_service_title',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
