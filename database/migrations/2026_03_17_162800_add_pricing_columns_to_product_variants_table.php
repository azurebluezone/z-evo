<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->integer('purchase_price')->nullable()->after('price_override');
            $table->integer('sale_price')->nullable()->after('purchase_price');
            $table->integer('discount_price')->nullable()->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'sale_price', 'discount_price']);
        });
    }
};
