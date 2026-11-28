<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.2 — districts.division.
 *
 * shippings.division (2026_11_27_000003) has to be filled from an authoritative
 * source, not guessed at checkout. The districts reference table only stored
 * the district and the upazila, so the division name was lost even though the
 * vendored dataset (database/data/bangladesh-geo.json) publishes it as the
 * top-level grouping.
 *
 * This migration adds the column and backfills existing rows from that file.
 * Fresh installs run migrations before the seeder, so the backfill is a no-op
 * there and DistrictShippingSeeder writes the same value on insert.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('districts')) {
            return;
        }

        if (!Schema::hasColumn('districts', 'division')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->string('division', 32)->nullable()->after('district');
                $table->index('division');
            });
        }

        $path = database_path('data/bangladesh-geo.json');

        if (!is_file($path)) {
            return;
        }

        $hierarchy = json_decode((string) file_get_contents($path), true);

        if (!is_array($hierarchy)) {
            return;
        }

        foreach ($hierarchy as $division) {
            $divisionName = trim((string) ($division['name'] ?? ''));

            if ($divisionName === '') {
                continue;
            }

            $districtNames = [];

            foreach ($division['districts'] ?? [] as $district) {
                $districtName = trim((string) ($district['name'] ?? ''));

                if ($districtName !== '') {
                    $districtNames[] = $districtName;
                }
            }

            if ($districtNames === []) {
                continue;
            }

            DB::table('districts')
                ->whereIn('district', array_values(array_unique($districtNames)))
                ->whereNull('division')
                ->update(['division' => $divisionName]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('districts') || !Schema::hasColumn('districts', 'division')) {
            return;
        }

        Schema::table('districts', function (Blueprint $table) {
            $table->dropIndex(['division']);
            $table->dropColumn('division');
        });
    }
};
