<?php

namespace Database\Seeders;

use App\Support\SeedAssets;
use Illuminate\Database\Seeder;

/**
 * Publishes the Adora 2026 demo bundle from `public/seed-assets/adora-2026/`
 * into the public upload tree:
 *
 *   uploads/product/seed/<slug>/   4 gallery + 3 variant images per product
 *   uploads/category/zevo-*.jpg    12 category photos
 *
 * Run directly:
 *
 *     php artisan db:seed --class=SeedAssetSeeder
 *
 * It is also part of DatabaseSeeder, so `migrate:fresh --seed` produces a
 * storefront whose images exist on disk without the external backup drive.
 * Idempotent: matching SHA-256 files are skipped.
 */
class SeedAssetSeeder extends Seeder
{
    public function run(): void
    {
        $summary = SeedAssets::publishAll();

        $this->command?->info(sprintf(
            'Seed assets: %d products (%d gallery, %d variant images), %d category images, %d copied, %d unchanged (%d bytes).',
            $summary['products'],
            $summary['gallery'],
            $summary['variants'],
            $summary['categories'],
            $summary['copied'],
            $summary['unchanged'],
            $summary['bytes']
        ));
    }
}
