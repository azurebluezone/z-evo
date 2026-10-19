<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_recommended')) {
                $table->tinyInteger('is_recommended')->default(0)->after('feature_product');
            }
            if (!Schema::hasColumn('products', 'recommended_sort_order')) {
                $table->unsignedInteger('recommended_sort_order')->default(0)->after('is_recommended');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'recommended_sort_order')) {
                $table->dropColumn('recommended_sort_order');
            }
            if (Schema::hasColumn('products', 'is_recommended')) {
                $table->dropColumn('is_recommended');
            }
        });
    }
};
