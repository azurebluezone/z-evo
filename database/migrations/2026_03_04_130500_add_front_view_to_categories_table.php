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
        if (!Schema::hasColumn('categories', 'front_view')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->tinyInteger('front_view')->default(0)->comment('0=Hidden from front, 1=Show on front');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('categories', 'front_view')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('front_view');
            });
        }
    }
};
