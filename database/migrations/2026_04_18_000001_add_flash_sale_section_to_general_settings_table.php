<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'home_flash_sale_section')) {
                $table->tinyInteger('home_flash_sale_section')->default(1);
            }
            if (!Schema::hasColumn('general_settings', 'home_flash_sale_title')) {
                $table->string('home_flash_sale_title')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $columns = [
                'home_flash_sale_section',
                'home_flash_sale_title',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
