<?php

/**
 * Seed the admin Landing Pages manager with one real page per active template.
 *
 * The two legacy demo rows are repurposed in place, then the remaining
 * templates are added. Product, campaign, price and content come from the
 * live campaign copy so the public landing page and the campaign stay in sync.
 *
 * Usage: php tools/seed_landing_pages.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Campaign;
use App\Models\LandingPage;
use App\Models\Product;

$map = [
    'red-apple-fresh-offer' => [
        'legacy_slug' => 'demo-default-offer',
        'campaign_id' => 767,
        'template_id' => 1,
        'product_id' => 20,
        'sort_order' => 1,
    ],
    'raw-honey-herbal-offer' => [
        'legacy_slug' => 'demo-second-offer',
        'campaign_id' => 768,
        'template_id' => 2,
        'product_id' => 14,
        'sort_order' => 2,
    ],
    'mustard-oil-countdown-offer' => [
        'legacy_slug' => null,
        'campaign_id' => 769,
        'template_id' => 3,
        'product_id' => 24,
        'sort_order' => 3,
    ],
    'red-rice-modern-offer' => [
        'legacy_slug' => null,
        'campaign_id' => 770,
        'template_id' => 4,
        'product_id' => 23,
        'sort_order' => 4,
    ],
    'sourdough-brand-offer' => [
        'legacy_slug' => null,
        'campaign_id' => 771,
        'template_id' => 5,
        'product_id' => 22,
        'sort_order' => 5,
    ],
    'desi-ghee-sample-offer' => [
        'legacy_slug' => null,
        'campaign_id' => 772,
        'template_id' => 6,
        'product_id' => 25,
        'sort_order' => 6,
    ],
];

DB::transaction(function () use ($map) {
    foreach ($map as $slug => $spec) {
        $campaign = Campaign::find($spec['campaign_id']);
        $product = Product::with('image', 'images')->find($spec['product_id']);

        if (!$campaign) {
            throw new RuntimeException("Campaign {$spec['campaign_id']} not found for {$slug}");
        }
        if (!$product) {
            throw new RuntimeException("Product {$spec['product_id']} not found for {$slug}");
        }

        $image = optional($product->image)->image
            ?: optional($product->images->first())->image
            ?: null;

        if (!$image) {
            throw new RuntimeException("Product {$product->id} has no image for {$slug}");
        }

        $extra = is_array($campaign->extra_fields) ? $campaign->extra_fields : [];
        $extra['product_id'] = (int) $product->id;
        $extra['campaign_id'] = (int) $campaign->id;
        $extra['old_price'] = $product->old_price !== null ? (float) $product->old_price : null;
        $extra['new_price'] = (float) $campaign->campaign_price;
        $extra['show_features'] = true;
        $extra['show_benefits'] = true;
        $extra['show_testimonials'] = true;
        $extra['show_faq'] = true;
        $extra['show_cta'] = true;

        if ((int) $spec['template_id'] === 5) {
            $gallery = $product->images
                ->pluck('image')
                ->filter()
                ->unique()
                ->values()
                ->all();
            if (count($gallery) > 0) {
                $extra['gallery_images'] = $gallery;
            }
        }

        $data = [
            'title' => $product->name,
            'subtitle' => $campaign->hero_subtitle ?: $campaign->short_description,
            'description' => $campaign->description,
            'cta_text' => $campaign->hero_button_text ?: 'অর্ডার করুন',
            'cta_link' => '/landing-order',
            'image_url' => $image,
            'extra_fields' => $extra,
            'template_id' => (int) $spec['template_id'],
            'slug' => $slug,
            'status' => 1,
            'sort_order' => (int) $spec['sort_order'],
        ];

        $page = LandingPage::where('slug', $slug)->first();

        if (!$page && $spec['legacy_slug']) {
            $page = LandingPage::where('slug', $spec['legacy_slug'])->first();
        }

        if ($page) {
            $page->update($data);
        } else {
            LandingPage::create($data);
        }
    }

    // The two old demo URLs must not survive as stale pages.
    LandingPage::whereIn('slug', ['demo-default-offer', 'demo-second-offer'])->delete();
});
