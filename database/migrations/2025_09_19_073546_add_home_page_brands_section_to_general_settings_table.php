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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->tinyInteger('home_page_brands_section')->default(1)->comment('0=Hide brands section from home page, 1=Show brands section on home page');
            $table->tinyInteger('home_page_hot_deal_section')->default(1)->comment('0=Hide hot deal section from home page, 1=Show hot deal section on home page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['home_page_brands_section', 'home_page_hot_deal_section']);
        });
    }
};
