<?php

namespace Database\Seeders;

use App\Models\EventLog;
use App\Models\VisitorMaster;
use Illuminate\Database\Seeder;

class EventLogsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $visitors = VisitorMaster::query()->pluck('visitor_id')->all();
        if (count($visitors) === 0) {
            return;
        }

        $allowed = [
            'PageView',
            'AddToCart',
            'BeginCheckout',
            'Purchase',
            'FakeOrderAttempt',
        ];

        $now = now();
        $rows = [];

        $urls = [
            url('/'),
            url('/shop'),
            url('/cart'),
            url('/checkout'),
            url('/contact'),
        ];

        for ($i = 0; $i < 60; $i++) {
            $rows[] = [
                'visitor_id' => $visitors[array_rand($visitors)],
                'event_name' => $allowed[array_rand($allowed)],
                'url' => $urls[array_rand($urls)],
                'event_time' => $now->copy()->subMinutes(random_int(1, 60 * 24 * 7)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        EventLog::insert($rows);
    }
}
