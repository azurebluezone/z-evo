<?php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate, not firstOrCreate: an existing install already owns
        // row 1, so firstOrCreate would silently keep an old default such as
        // "GS Corporation" or "Z EVO" and the new site name would never reach
        // it. This seeder is the demo baseline; a live install customises the
        // row after seeding.
        GeneralSetting::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Z Evo-Commerce',
                'white_logo' => 'uploads/settings/default-logo.webp',
                'dark_logo' => 'uploads/settings/default-logo.webp',
                'favicon' => 'uploads/settings/default-favicon.webp',
                'copyright' => null,
                'status' => 1,
            ]
        );
    }
}
