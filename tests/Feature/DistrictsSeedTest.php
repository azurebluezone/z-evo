<?php

namespace Tests\Feature;

use Database\Seeders\DistrictShippingSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Districts reference data (docs/report-completion-plan.md Phase 2 item 1).
 *
 * The districts table shipped with 0 rows. The vendored
 * database/data/bangladesh-geo.json is the only source; the districts-only
 * mode must be idempotent and must not touch shipping_charges, which has its
 * own owner (DevShippingChargeSeeder).
 *
 * Fixtures are rolled back by DatabaseTransactions.
 *
 * Marker: DISTRICTS_SEEDED_OK
 */
class DistrictsSeedTest extends TestCase
{
    use DatabaseTransactions;

    public function test_districts_only_seed_is_idempotent_and_leaves_zones_alone(): void
    {
        $chargesBefore = DB::table('shipping_charges')->count();

        (new DistrictShippingSeeder())->districtsOnly()->run();
        $first = DB::table('districts')->count();

        (new DistrictShippingSeeder())->districtsOnly()->run();
        $second = DB::table('districts')->count();

        $this->assertGreaterThan(400, $first, 'The vendored dataset must produce more than 400 district/area rows.');
        $this->assertSame($first, $second, 'A second districts-only run must not duplicate rows.');
        $this->assertSame(
            $chargesBefore,
            DB::table('shipping_charges')->count(),
            'districtsOnly() must not write shipping_charges.'
        );
    }

    public function test_a_real_dataset_pair_is_present(): void
    {
        (new DistrictShippingSeeder())->districtsOnly()->run();

        $hierarchy = json_decode((string) file_get_contents(database_path('data/bangladesh-geo.json')), true);
        $this->assertIsArray($hierarchy, 'The vendored geography file must decode.');

        $district = $hierarchy[0]['districts'][0];
        $area = $district['upazilas'][0]['name'];

        $this->assertDatabaseHas('districts', [
            'district' => trim((string) $district['name']),
            'area_name' => trim((string) $area),
        ]);
    }
}
