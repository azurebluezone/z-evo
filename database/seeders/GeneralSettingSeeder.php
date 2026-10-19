<?php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    public function run(): void
    {
        GeneralSetting::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'GS Corporation',
                'white_logo' => 'uploads/settings/default-logo.webp',
                'dark_logo' => 'uploads/settings/default-logo.webp',
                'favicon' => 'uploads/settings/default-favicon.webp',
                'copyright' => null,
                'status' => 1,
            ]
        );
    }
}
