<?php

namespace Database\Seeders;

use App\Models\BannerCategory;
use App\Models\GeneralSetting;
use App\Support\ZevoBrandAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Applies the Z EVO brand to the storefront.
 *
 * WHAT THIS OWNS
 * --------------
 *   general_settings  name, dark/white logo, favicon, brand colours, hero and
 *                     footer copy - the settings every front-end template reads
 *   banners           exactly ten hero slides for the Homepage category:
 *                     one banner image + nine supplied MP4 clips
 *   promo_banners     two Z EVO promo tiles instead of the old grocery art
 *
 * The media itself is published first by ZevoBrandAssets from the committed
 * bundle `public/seed-assets/zevo/`, so every stored path points at a file that
 * exists on disk.
 *
 * IDEMPOTENT: the hero set is replaced as an exact set, so re-running the
 * seeder cannot accumulate rows. It runs after StorefrontDemoSeeder, which is
 * the seeder that creates the banner category and the default settings row.
 */
class ZevoBrandSeeder extends Seeder
{
    public function run(): void
    {
        $summary = ZevoBrandAssets::publishAll();
        $manifest = ZevoBrandAssets::manifest();

        $this->applySettings($manifest);
        $this->seedHeroBanners($manifest);
        $this->seedPromoBanners();

        $this->command?->info(sprintf(
            'Z EVO brand: %d files (%d videos), %d copied, %d unchanged; %d hero slides.',
            $summary['files'],
            $summary['videos'],
            $summary['copied'],
            $summary['unchanged'],
            count($this->heroRows($manifest))
        ));
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function applySettings(array $manifest): void
    {
        $settings = GeneralSetting::query()->orderBy('id')->first();

        if (! $settings) {
            $settings = new GeneralSetting();
        }

        $settings->fill([
            'name' => (string) ($manifest['name'] ?? 'Z EVO'),
            'dark_logo' => ZevoBrandAssets::uploadPath((string) $manifest['logo']),
            'white_logo' => ZevoBrandAssets::uploadPath((string) $manifest['logo_white']),
            'favicon' => ZevoBrandAssets::uploadPath((string) $manifest['favicon']),
            'primary_color' => (string) ($manifest['primary_color'] ?? '#003c9e'),
            'secondary_color' => (string) ($manifest['secondary_color'] ?? '#00a3e0'),
            'footer_about_text' => (string) ($manifest['footer_about'] ?? ''),
            'home_hero_title' => (string) ($manifest['hero_title'] ?? 'Z EVO'),
            'copyright' => '© ' . date('Y') . ' Z EVO. All rights reserved.',
            'status' => 1,
        ]);

        $settings->save();
    }

    /**
     * Replace the Homepage hero slider with the Z EVO set.
     *
     * @param array<string, mixed> $manifest
     */
    private function seedHeroBanners(array $manifest): void
    {
        if (! Schema::hasTable('banners')) {
            return;
        }

        $category = null;

        if (Schema::hasTable('banner_categories')) {
            $category = BannerCategory::firstOrCreate(
                ['name' => 'Homepage'],
                ['name' => 'Homepage', 'status' => 1]
            );
        }

        $categoryId = $category?->id ?? 1;

        // The hero set is owned by this seeder. Delete only the Homepage
        // category so an admin's campaign banners elsewhere survive.
        DB::table('banners')->where('category_id', $categoryId)->delete();

        $now = now();

        foreach ($this->heroRows($manifest) as $index => $row) {
            DB::table('banners')->insert([
                'category_id' => $categoryId,
                'link' => '/products',
                'image' => $row['image'],
                'video' => $row['video'],
                'title' => $row['title'],
                'media_type' => $row['media_type'],
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * One image slide plus every supplied video clip, in manifest order.
     *
     * @param array<string, mixed> $manifest
     *
     * @return array<int, array{title: string, image: string, video: string|null, media_type: string}>
     */
    private function heroRows(array $manifest): array
    {
        $bannerFile = (string) ($manifest['banner'] ?? 'banner.jpg');
        $bannerPath = ZevoBrandAssets::uploadPath($bannerFile);
        $bannerTitle = (string) ($manifest['hero_title'] ?? 'Z EVO');

        $rows = [[
            'title' => $bannerTitle,
            'image' => $bannerPath,
            'video' => null,
            'media_type' => 'image',
        ]];

        foreach (($manifest['videos'] ?? []) as $video) {
            $rows[] = [
                'title' => (string) ($video['title'] ?? 'Z EVO'),
                'image' => $bannerPath,
                'video' => ZevoBrandAssets::uploadPath((string) $video['file']),
                'media_type' => 'video',
            ];
        }

        return $rows;
    }

    /**
     * Two promo tiles. The old storefront decoration shipped grocery photos;
     * a Z EVO storefront must not show vegetables beside baby clothing.
     */
    private function seedPromoBanners(): void
    {
        if (! Schema::hasTable('promo_banners')) {
            return;
        }

        $bannerPath = ZevoBrandAssets::uploadPath('banner.jpg');

        $rows = [
            ['title' => 'Z EVO - New Season', 'link' => '/products'],
            ['title' => 'Z EVO - Everyday Essentials', 'link' => '/products?type=discount'],
        ];

        DB::table('promo_banners')->delete();
        $now = now();

        foreach ($rows as $index => $row) {
            DB::table('promo_banners')->insert([
                'title' => $row['title'],
                'image' => $bannerPath,
                'link' => $row['link'],
                'sort_order' => $index,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
