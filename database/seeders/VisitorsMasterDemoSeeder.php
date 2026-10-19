<?php

namespace Database\Seeders;

use App\Models\VisitorMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VisitorsMasterDemoSeeder extends Seeder
{
    public function run(): void
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Safari/605.1.15',
            'Mozilla/5.0 (Linux; Android 13; SM-A525F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36',
        ];

        $countries = ['Bangladesh', 'India', 'United States', 'United Kingdom', 'United Arab Emirates'];
        $cities = ['Dhaka', 'Chattogram', 'Sylhet', 'Khulna', 'Rajshahi'];
        $isps = ['BTCL', 'Link3', 'AmberIT', 'Carnival', 'Banglalink'];

        for ($i = 0; $i < 10; $i++) {
            VisitorMaster::create([
                'visitor_id' => (string) Str::uuid(),
                'ip' => implode('.', [random_int(1, 223), random_int(0, 255), random_int(0, 255), random_int(1, 254)]),
                'user_agent' => $userAgents[array_rand($userAgents)],
                'country' => $countries[array_rand($countries)],
                'city' => $cities[array_rand($cities)],
                'isp' => $isps[array_rand($isps)],
                'status' => 1,
                'user_id' => null,
            ]);
        }
    }
}
