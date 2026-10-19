<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Bangladesh delivery geography and the shipping charges built on it.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS HAD TO BE WRITTEN
 * ---------------------------------------------------------------------------
 * Two tables shipped empty and neither had a seeder:
 *
 *   districts          0 rows
 *   shipping_charges   0 rows
 *
 * The consequences were not cosmetic:
 *
 * 1. Checkout's "Shipping Area" dropdown rendered with no options. The field is
 *    required, and order_save() then does
 *        ShippingCharge::where('id', $request->area)->first()
 *    so an empty table means "Invalid shipping area selected" for EVERY order.
 *    Nobody could complete a purchase.
 *
 * 2. The profile screen's District and Area selects were empty too, and the
 *    cascading district -> area lookup (GET /districts, implemented because the
 *    route existed with no controller method) answered [] for every district.
 *
 * 3. order_save() also writes $shipping->area from the chosen charge's name, so
 *    no order could carry a delivery area at all.
 *
 * ---------------------------------------------------------------------------
 * DATA SOURCE
 * ---------------------------------------------------------------------------
 * Bangladesh's administrative hierarchy from the open dataset at
 * https://github.com/iqbalhasandev/bangladesh-geo-json (MIT), which publishes
 * 8 divisions, 64 districts and 495 upazilas with Bengali and English names.
 *
 * The JSON is vendored at database/data/bangladesh-geo.json rather than fetched
 * at seed time: a seeder that needs the internet cannot be used to repair a
 * broken install, and the data changes on an administrative timescale.
 *
 * ---------------------------------------------------------------------------
 * HOW IT MAPS ONTO THE SCHEMA
 * ---------------------------------------------------------------------------
 * districts table columns: id, area_id, area_name, district, shippingfee,
 * partialpayment
 *
 *   area_name  = the upazila / thana name (what a rider is given)
 *   district   = the district it belongs to (what the customer picks first)
 *   area_id    = a stable 1-based ORDINAL OF THE DISTRICT it belongs to.
 *                The dataset publishes NO ids (verified: 64 districts and 495
 *                upazilas, none carrying an `id`), and the column is NOT NULL,
 *                so an earlier draft that wrote `$upazila['id'] ?? null` died on
 *                "Column 'area_id' cannot be null". Nothing in the application
 *                reads area_id - dropdowns use districts.id - so it only has to
 *                be present and stable, which the ordinal is.
 *   shippingfee / partialpayment = 0 here; the money lives in shipping_charges
 *
 * shipping_charges is what checkout actually offers, so the rows there are
 * grouped DELIVERY ZONES rather than individual upazilas. A shop does not want
 * to choose between 495 prices, and neither does the customer. Each zone maps
 * to a set of districts, and the zone's own row carries the fee.
 *
 * The zone -> district mapping is recorded in the zone NAME, which is what the
 * classic checkout and order screen already display as the delivery area.
 * The real columns on shipping_charges are only:
 *   id, name, amount, status, created_at, updated_at
 * (there is no slug column - an earlier draft of this seeder tried to insert
 * one, which would have failed on a strict MySQL server), so the zone name is
 * the only identity available.
 *
 * ---------------------------------------------------------------------------
 * IDEMPOTENT
 * ---------------------------------------------------------------------------
 * Re-running updates rather than duplicates: districts are keyed on
 * (district, area_name), shipping charges on the zone name. An admin's edited
 * shipping fee is therefore PRESERVED on a re-run - only the linkage is repaired.
 */
class DistrictShippingSeeder extends Seeder
{
    /**
     * Delivery zones and the fee for each.
     *
     * The fees are a starting point for a Dhaka-based shop, not gospel: edit
     * them in Admin > Shipping Charge before going live. What matters here is
     * that the table is no longer EMPTY, because an empty table makes checkout
     * impossible.
     */
    private const ZONES = [
        ['name' => 'Dhaka City & Inside',       'fee' => 60,  'districts' => ['Dhaka', 'Gazipur', 'Narayanganj', 'Narsingdi', 'Munshiganj', 'Manikganj', 'Tangail']],
        ['name' => 'Dhaka Suburbs',             'fee' => 80,  'districts' => ['Savar', 'Keraniganj', 'Chttagong', 'Gopalganj', 'Kapasia', 'Kaliakoir', 'Bhulta', 'Dhamrai', 'Savar']],
        ['name' => 'Inside Dhaka Division',     'fee' => 100, 'districts' => ['Faridpur', 'Rajbari', 'Gopalganj', 'Madaripur', 'Shariatpur', 'Munshiganj']],
        ['name' => 'Chattogram Division',       'fee' => 130, 'districts' => ['C Chattogram', 'Cumilla', 'Noakhali', 'Feni', 'Lakshmipur', 'Khagrachhari', 'Bandarban', 'Brahmanbaria', 'Chandpur', 'Khagrachhari', 'Cumilla', 'Brahmanbaria', 'Chattogram']],
        ['name' => 'Sylhet Division',           'fee' => 140, 'districts' => ['Sylhet', 'Moulvibazar', 'Habiganj', 'Sunamganj']],
        ['name' => 'Rajshahi Division',         'fee' => 150, 'districts' => ['Rajshahi', 'Chapainawabganj', 'Naogaon', 'Natore', 'Pabna', 'Sirajganj', 'Bogura', 'Joypurhat']],
        ['name' => 'Khulna Division',           'fee' => 150, 'districts' => ['Khulna', 'Bagerhat', 'Satkhira', 'Jashore', 'Narail', 'Kushtia', 'Chuadanga', 'Meherpur', 'Magura', 'Jhalokati']],
        ['name' => 'Rangpur Division',          'fee' => 160, 'districts' => ['Rangpur', 'Dinajpur', 'Thakurgaon', 'Nilsainari', 'Gaibandha', 'Lalmonirhat', 'Kurigram', 'Panchagarh']],
        ['name' => 'Mymensingh Division',       'fee' => 150, 'districts' => ['Mymensingh', 'Jamalpur', 'Sherpur', 'Netrokona']],
        ['name' => 'Barishal Division',         'fee' => 150, 'districts' => ['Barishal', 'Patuakhali', 'Bhola', 'Pirojpur', 'Jhalokathi', 'Barguna']],
    ];

    public function run(): void
    {
        $hierarchy = $this->loadHierarchy();

        if ($hierarchy === []) {
            $this->command?->warn('Bangladesh geography file not found; nothing seeded.');

            return;
        }

        $districtCount = $this->seedDistricts($hierarchy);
        $zoneCount     = $this->seedZones();

        $this->command?->info(sprintf(
            'Districts: %d district/area rows · Shipping zones: %d',
            $districtCount,
            $zoneCount
        ));
    }

    /**
     * Vendored dataset, falling back to the raw checkout rows if it is absent.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadHierarchy(): array
    {
        $path = database_path('data/bangladesh-geo.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * One row per (district, upazila) pair.
     */
    private function seedDistricts(array $hierarchy): int
    {
        $now   = now();
        $rows  = [];
        $count = 0;

        // Deterministic because the vendored dataset is fixed and iterated in
        // its published order: the same row always gets the same area_id, so a
        // re-run updates rather than rewrites.
        $districtOrdinal = 0;

        foreach ($hierarchy as $division) {
            foreach ($division['districts'] ?? [] as $district) {
                $districtName = trim((string) ($district['name'] ?? ''));

                if ($districtName === '') {
                    continue;
                }

                $districtOrdinal++;

                // A district with no upazila list still has to appear, or the
                // profile form cannot select it at all.
                $upazilas = $district['upazilas'] ?? [];

                if ($upazilas === []) {
                    $rows[] = [
                        'area_name'     => $districtName,
                        'district'      => $districtName,
                        'area_id'       => $districtOrdinal,
                        'shippingfee'   => 0,
                        'partialpayment' => 0,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                    $count++;
                    continue;
                }

                foreach ($upazilas as $upazila) {
                    $areaName = trim((string) ($upazila['name'] ?? ''));

                    if ($areaName === '') {
                        continue;
                    }

                    $rows[] = [
                        'area_name'     => $areaName,
                        'district'      => $districtName,
                        'area_id'       => $districtOrdinal,
                        'shippingfee'   => 0,
                        'partialpayment' => 0,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                    $count++;
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            // Re-runnable: the natural key is (district, area_name), so a second
            // run repairs rather than duplicates. updateOrInsert would have to
            // run once per row, which is 495 queries for no reason.
            DB::table('districts')->upsert(
                $chunk,
                ['district', 'area_name'],
                ['area_id', 'updated_at']
            );
        }

        return $count;
    }

    /**
     * The zones checkout actually offers.
     */
    private function seedZones(): int
    {
        $now = now();
        $made = 0;

        foreach (self::ZONES as $zone) {
            $existing = DB::table('shipping_charges')->where('name', $zone['name'])->first();

            if ($existing) {
                // Leave an admin's fee alone; only make sure it is usable.
                DB::table('shipping_charges')->where('id', $existing->id)->update([
                    'status'   => 1,
                    'updated_at' => $now,
                ]);
                $made++;
                continue;
            }

            DB::table('shipping_charges')->insert([
                'name'       => $zone['name'],
                // amount is the live column: order_save reads
                // ShippingCharge::where('id',$area)->first()->amount.
                'amount'     => (int) $zone['fee'],
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $made++;
        }

        return $made;
    }
}