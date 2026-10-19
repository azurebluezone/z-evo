<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives `districts` the unique key its seeder assumes.
 *
 * ---------------------------------------------------------------------------
 * WHY
 * ---------------------------------------------------------------------------
 * DistrictShippingSeeder inserts with upsert(..., ['district', 'area_name'], ...).
 * On MySQL that compiles to INSERT ... ON DUPLICATE KEY UPDATE, and the
 * "duplicate" it relies on is whatever UNIQUE index covers those two columns.
 *
 * The table only ever had a PRIMARY key on `id` (checked with SHOW INDEX), so
 * there was nothing to collide with: a second run would have INSERTED every one
 * of the 495+ rows again, silently doubling the geography and filling the
 * profile dropdown with each area twice. The seeder would have looked
 * idempotent and would not have been.
 *
 * Adding the index makes the seeder's own claim true, and stops any future
 * duplicate by hand, seed, or import.
 *
 * ---------------------------------------------------------------------------
 * SAFETY
 * ---------------------------------------------------------------------------
 * `districts` is pure reference data with no admin CRUD (only FrontendController
 * and CustomerController read it), so no workflow writes rows that a unique key
 * could reject. The table is empty at the time this runs - the seeder has never
 * completed - so there is nothing to de-duplicate first. The down() drops the
 * index only; no data is touched either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('districts')) {
            return;
        }

        // Guarded so re-running is harmless and so a hand-made index does not
        // cause a "duplicate key name" error.
        $already = collect(Schema::getIndexes('districts'))
            ->contains(fn (array $index) => $index['name'] === 'districts_district_area_name_unique');

        if ($already) {
            return;
        }

        Schema::table('districts', function (Blueprint $table) {
            $table->unique(['district', 'area_name'], 'districts_district_area_name_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('districts')) {
            return;
        }

        Schema::table('districts', function (Blueprint $table) {
            $table->dropUnique('districts_district_area_name_unique');
        });
    }
};
