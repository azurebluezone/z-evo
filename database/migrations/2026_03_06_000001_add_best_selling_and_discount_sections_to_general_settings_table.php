<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'home_best_selling_section')) {
                $table->tinyInteger('home_best_selling_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_best_selling_title')) {
                $table->string('home_best_selling_title')->nullable();
            }

            if (!Schema::hasColumn('general_settings', 'home_discount_products_section')) {
                $table->tinyInteger('home_discount_products_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_discount_products_title')) {
                $table->string('home_discount_products_title')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $columns = [
                'home_best_selling_section',
                'home_best_selling_title',
                'home_discount_products_section',
                'home_discount_products_title',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
