<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'frontend_empty_image')) {
                $table->string('frontend_empty_image')->nullable()->after('secondary_color');
            }
            if (!Schema::hasColumn('general_settings', 'about_url')) {
                $table->string('about_url')->nullable()->after('frontend_empty_image');
            }
            if (!Schema::hasColumn('general_settings', 'contact_url')) {
                $table->string('contact_url')->nullable()->after('about_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('general_settings', 'frontend_empty_image')) {
                $columns[] = 'frontend_empty_image';
            }
            if (Schema::hasColumn('general_settings', 'about_url')) {
                $columns[] = 'about_url';
            }
            if (Schema::hasColumn('general_settings', 'contact_url')) {
                $columns[] = 'contact_url';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
