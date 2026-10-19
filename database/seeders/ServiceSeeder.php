<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::create([
            'title' => 'Secure Payment',
            'subtitle' => 'All Cards Accepted',
            'image' => 'assets/images/frontend/services/6739fca46ea611731853476.png',
            'sort_order' => 1,
            'status' => true
        ]);

        Service::create([
            'title' => 'Free Shipping',
            'subtitle' => 'On All Order',
            'image' => 'assets/images/frontend/services/6739fcbe2422c1731853502.png',
            'sort_order' => 2,
            'status' => true
        ]);

        Service::create([
            'title' => 'Online Support',
            'subtitle' => 'Technical 24/7',
            'image' => 'assets/images/frontend/services/6739f654a40241731851860.png',
            'sort_order' => 3,
            'status' => true
        ]);

        Service::create([
            'title' => 'Big Savings',
            'subtitle' => 'Weekend Sales',
            'image' => 'assets/images/frontend/services/6739f65c524471731851868.png',
            'sort_order' => 4,
            'status' => true
        ]);
    }
}
