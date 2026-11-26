<?php

namespace Database\Seeders;

use App\Models\ShippingCharge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The three delivery rates the shop runs on, seeded exactly once.
 *
 * `shipping_charges` starts EMPTY on a fresh install, and checkout needs at
 * least one row or every order dies on "Invalid shipping area selected".
 *
 * The values are fixed policy:
 *
 *   ঢাকার ভিতরে       70
 *   ঢাকার বাহিরে     130
 *   Standard delivery 120
 *
 * and ShippingCharge marks them read-only in the admin screens. This seeder is
 * the only writer: it upserts by the canonical name and removes legacy spelling
 * duplicates (`ঢাকার বাইরে।`) so checkout never shows the same zone twice.
 *
 * IDEMPOTENT: names are the natural key; a second run repairs status and amount
 * and deletes nothing that is still referenced.
 */
class DevShippingChargeSeeder extends Seeder
{
    /** canonical name => amount */
    public const RATES = [
        'Standard delivery' => 120,
        'ঢাকার ভিতরে' => 70,
        'ঢাকার বাহিরে' => 130,
    ];

    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            foreach (self::RATES as $name => $amount) {
                $existing = DB::table('shipping_charges')->where('name', $name)->first();

                if ($existing) {
                    DB::table('shipping_charges')->where('id', $existing->id)->update([
                        'amount' => $amount,
                        'status' => 1,
                        'updated_at' => $now,
                    ]);

                    continue;
                }

                DB::table('shipping_charges')->insert([
                    'name' => $name,
                    'amount' => $amount,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->removeLegacySpellings();
        });

        $this->command?->info(sprintf(
            'Shipping rates: %s',
            implode(' · ', array_map(
                fn ($name, $amount) => $name . ' ' . $amount,
                array_keys(self::RATES),
                array_values(self::RATES)
            ))
        ));
    }

    /**
     * Delete rows that normalize to a locked name but are not the canonical row.
     *
     * `orders`/`shippings` store the area NAME as a string (there is no foreign
     * key to shipping_charges), so removing the duplicate cannot orphan a real
     * order. Checkout, however, renders every active row, and two spellings of
     * "ঢাকার বাহিরে" would quote two different prices for one zone.
     */
    private function removeLegacySpellings(): void
    {
        $canonical = array_map(
            [ShippingCharge::class, 'normalizeName'],
            array_keys(self::RATES)
        );

        foreach (DB::table('shipping_charges')->orderBy('id')->get() as $row) {
            if (in_array((string) $row->name, array_keys(self::RATES), true)) {
                continue;
            }

            if (in_array(ShippingCharge::normalizeName((string) $row->name), $canonical, true)) {
                DB::table('shipping_charges')->where('id', (int) $row->id)->delete();
            }
        }
    }
}
