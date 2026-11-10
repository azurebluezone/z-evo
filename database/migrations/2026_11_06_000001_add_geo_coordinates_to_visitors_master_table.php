<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitors previously kept country/city/division/ISP but threw away the
 * coordinates the resolver had already looked up. Without a latitude and
 * longitude the analytics map could only draw a decorative gradient.
 *
 * All columns are nullable: old rows keep working, and a visitor whose IP is
 * private simply has no map point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors_master', function (Blueprint $table) {
            if (!Schema::hasColumn('visitors_master', 'district')) {
                $table->string('district', 100)->nullable()->after('division')->index();
            }
            if (!Schema::hasColumn('visitors_master', 'latitude')) {
                $table->string('latitude', 32)->nullable()->after('district');
            }
            if (!Schema::hasColumn('visitors_master', 'longitude')) {
                $table->string('longitude', 32)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('visitors_master', 'timezone')) {
                $table->string('timezone', 60)->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visitors_master', function (Blueprint $table) {
            foreach (['district', 'latitude', 'longitude', 'timezone'] as $column) {
                if (Schema::hasColumn('visitors_master', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
