<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $weightAttrId = DB::table('attributes')->updateOrInsert(
            ['slug' => 'weight-unit'],
            [
                'name' => 'Weight Unit',
                'kind' => 'system',
                'status' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $volumeAttrId = DB::table('attributes')->updateOrInsert(
            ['slug' => 'volume-unit'],
            [
                'name' => 'Volume Unit',
                'kind' => 'system',
                'status' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $weightAttr = DB::table('attributes')->where('slug', 'weight-unit')->first();
        $volumeAttr = DB::table('attributes')->where('slug', 'volume-unit')->first();

        if ($weightAttr) {
            $this->seedUnitValues((int) $weightAttr->id, [
                ['value' => 'g', 'code' => 'g', 'multiplier' => 1, 'base_unit' => 'g'],
                ['value' => 'kg', 'code' => 'kg', 'multiplier' => 1000, 'base_unit' => 'g'],
                ['value' => 'mg', 'code' => 'mg', 'multiplier' => 1, 'base_unit' => 'mg'],
            ]);
        }

        if ($volumeAttr) {
            $this->seedUnitValues((int) $volumeAttr->id, [
                ['value' => 'ml', 'code' => 'ml', 'multiplier' => 1, 'base_unit' => 'ml'],
                ['value' => 'l', 'code' => 'l', 'multiplier' => 1000, 'base_unit' => 'ml'],
                ['value' => 'liter', 'code' => 'l', 'multiplier' => 1000, 'base_unit' => 'ml'],
            ]);
        }

        DB::table('attributes')->updateOrInsert(
            ['slug' => 'weight'],
            [
                'name' => 'Weight',
                'kind' => 'system',
                'status' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('attributes')->updateOrInsert(
            ['slug' => 'volume'],
            [
                'name' => 'Volume',
                'kind' => 'system',
                'status' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $weight = DB::table('attributes')->where('slug', 'weight')->first();
        $volume = DB::table('attributes')->where('slug', 'volume')->first();

        if ($weight) {
            $this->seedPackSizeValues((int) $weight->id, [
                ['value' => '500g', 'code' => 'g', 'multiplier' => 500, 'base_unit' => 'g'],
                ['value' => '1kg', 'code' => 'kg', 'multiplier' => 1000, 'base_unit' => 'g'],
                ['value' => '2kg', 'code' => 'kg', 'multiplier' => 2000, 'base_unit' => 'g'],
                ['value' => '5kg', 'code' => 'kg', 'multiplier' => 5000, 'base_unit' => 'g'],
            ]);
        }

        if ($volume) {
            $this->seedPackSizeValues((int) $volume->id, [
                ['value' => '250ml', 'code' => 'ml', 'multiplier' => 250, 'base_unit' => 'ml'],
                ['value' => '500ml', 'code' => 'ml', 'multiplier' => 500, 'base_unit' => 'ml'],
                ['value' => '1l', 'code' => 'l', 'multiplier' => 1000, 'base_unit' => 'ml'],
                ['value' => '2l', 'code' => 'l', 'multiplier' => 2000, 'base_unit' => 'ml'],
            ]);
        }
    }

    private function seedUnitValues(int $attributeId, array $values): void
    {
        $now = now();

        foreach ($values as $v) {
            DB::table('attribute_values')->updateOrInsert(
                ['attribute_id' => $attributeId, 'value' => $v['value']],
                [
                    'code' => $v['code'] ?? null,
                    'multiplier' => $v['multiplier'] ?? null,
                    'base_unit' => $v['base_unit'] ?? null,
                    'kind' => 'system',
                    'status' => 1,
                    'sort_order' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function seedPackSizeValues(int $attributeId, array $values): void
    {
        $now = now();

        foreach ($values as $v) {
            DB::table('attribute_values')->updateOrInsert(
                ['attribute_id' => $attributeId, 'value' => $v['value']],
                [
                    'code' => $v['code'] ?? null,
                    'multiplier' => $v['multiplier'] ?? null,
                    'base_unit' => $v['base_unit'] ?? null,
                    'kind' => 'system',
                    'status' => 1,
                    'sort_order' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
