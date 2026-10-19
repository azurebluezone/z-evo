<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'subcategory_id')) {
                $table->integer('subcategory_id')->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('products', 'childcategory_id')) {
                $table->integer('childcategory_id')->nullable()->after('subcategory_id');
            }
            if (!Schema::hasColumn('products', 'pro_unit')) {
                $table->string('pro_unit')->nullable()->after('stock');
            }
            if (!Schema::hasColumn('products', 'pro_video')) {
                $table->string('pro_video')->nullable()->after('pro_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'subcategory_id')) {
                $table->dropColumn('subcategory_id');
            }
            if (Schema::hasColumn('products', 'childcategory_id')) {
                $table->dropColumn('childcategory_id');
            }
            if (Schema::hasColumn('products', 'pro_unit')) {
                $table->dropColumn('pro_unit');
            }
            if (Schema::hasColumn('products', 'pro_video')) {
                $table->dropColumn('pro_video');
            }
        });
    }
};
