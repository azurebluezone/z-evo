<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomepageDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Get some active products
        $products = DB::table('products')
            ->where('status', 1)
            ->limit(10)
            ->pluck('id');

        if ($products->isEmpty()) {
            $this->command->error('No active products found!');
            return;
        }

        $this->command->info('Found ' . $products->count() . ' products');

        // 1. Update products to be recommended
        $recommendedIds = $products->take(5);
        foreach ($recommendedIds as $index => $productId) {
            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'is_recommended' => 1,
                    'recommended_sort_order' => $index,
                    'updated_at' => Carbon::now(),
                ]);
        }
        $this->command->info('Set ' . $recommendedIds->count() . ' products as recommended');

        // 2. Create a new active flash sale
        $now = Carbon::now();
        $flashSaleId = DB::table('flash_sales')->insertGetId([
            'title' => '🔥 Summer Flash Sale 2026',
            'start_time' => $now->copy()->subHour(), // Started 1 hour ago
            'end_time' => $now->copy()->addDays(3),  // Ends in 3 days
            'banner_image' => null,
            'banner_link' => 'javascript:void(0)',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->command->info('Created flash sale ID: ' . $flashSaleId);

        // 3. Add products to flash sale
        $flashSaleProducts = $products->take(8);
        foreach ($flashSaleProducts as $index => $productId) {
            DB::table('flash_sale_products')->insert([
                'flash_sale_id' => $flashSaleId,
                'product_id' => $productId,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->command->info('Added ' . $flashSaleProducts->count() . ' products to flash sale');

        // 4. Enable homepage sections in general_settings
        DB::table('general_settings')
            ->where('id', 1)
            ->update([
                'home_flash_sale_section' => 1,
                'home_flash_sale_title' => '⚡ Flash Sale',
                'home_recommended_section' => 1,
                'home_recommended_title' => '⭐ Recommended for You',
                'updated_at' => $now,
            ]);
        $this->command->info('Enabled homepage sections in settings');

        $this->command->info('✅ Demo data seeded successfully!');
    }
}
