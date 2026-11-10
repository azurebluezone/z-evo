#!/usr/bin/env php
<?php
/**
 * Create or drop the QA campaign used by tools/landing-responsive-check.mjs.
 *
 * Usage:
 *   php tools/landing_fixture.php create [view_name]
 *   php tools/landing_fixture.php template <view_name>
 *   php tools/landing_fixture.php drop
 *
 * All rows use the qa-responsive- prefix so they are easy to find and remove.
 */

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

const CAMPAIGN_SLUG = 'qa-responsive-fixture';
const PRODUCT_SLUG = 'qa-responsive-product';

$command = $argv[1] ?? 'create';
$viewName = $argv[2] ?? 'template-1';

function templateId(string $viewName): int
{
    $id = DB::table('templates')->where('view_name', $viewName)->value('id');
    if (!$id) {
        fwrite(STDERR, "Template not found: {$viewName}\n");
        exit(1);
    }

    return (int) $id;
}

function createFixture(string $viewName): void
{
    $templateId = templateId($viewName);
    $now = now();

    DB::table('products')->updateOrInsert(
        ['slug' => PRODUCT_SLUG],
        [
            'name' => 'QA Responsive Product',
            'category_id' => 1,
            'subcategory_id' => null,
            'childcategory_id' => null,
            'brand_id' => null,
            'product_code' => 'QARESP',
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
            'stock' => 20,
            'pro_unit' => 'pcs',
            'pro_video' => null,
            'meta_description' => 'QA responsive fixture',
            'description' => 'QA responsive product description with a deliberately long sentence to prove the landing page wraps text instead of widening the viewport.',
            'document' => null,
            'topsale' => null,
            'feature_product' => null,
            'is_recommended' => 0,
            'recommended_sort_order' => 0,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $product = DB::table('products')->where('slug', PRODUCT_SLUG)->first();
    if (!$product) {
        fwrite(STDERR, "Product fixture was not created.\n");
        exit(1);
    }

    DB::table('productimages')->updateOrInsert(
        ['product_id' => $product->id, 'image' => 'public/uploads/product/qa-responsive.webp'],
        ['title' => 'QA responsive image', 'created_at' => $now, 'updated_at' => $now]
    );

    DB::table('campaigns')->updateOrInsert(
        ['slug' => CAMPAIGN_SLUG],
        [
            'name' => 'QA Responsive Campaign',
            'date' => $now->toDateString(),
            'short_description' => 'QA responsive short description',
            'review' => 'QA responsive review',
            'description' => 'QA responsive campaign description',
            'hero_title' => 'QA responsive hero',
            'hero_subtitle' => 'QA responsive subtitle',
            'hero_button_text' => 'Order Now',
            'hero_button_link' => null,
            'features_title' => null,
            'features_subtitle' => null,
            'benefits_title' => null,
            'benefits_subtitle' => null,
            'testimonials_title' => null,
            'testimonials_subtitle' => null,
            'faq_title' => null,
            'faq_subtitle' => null,
            'cta_title' => null,
            'cta_subtitle' => null,
            'cta_button_text' => 'Order Now',
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'custom_css' => null,
            'custom_js' => null,
            'show_features' => 0,
            'show_benefits' => 0,
            'show_testimonials' => 0,
            'show_faq' => 0,
            'show_cta' => 0,
            'campaign_type' => 'product',
            'start_date' => $now->copy()->subDay()->toDateString(),
            'end_date' => $now->copy()->addDays(2)->toDateString(),
            'is_featured' => 0,
            'sort_order' => 0,
            'image_one' => '',
            'image_two' => null,
            'image_three' => null,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'template_id' => $templateId,
            'product_id' => $product->id,
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'campaign_discount_percent' => null,
            'allow_coupon' => 0,
        ]
    );

    echo 'FIXTURE_OK ' . CAMPAIGN_SLUG . ' view=' . $viewName . PHP_EOL;
}

function switchTemplate(string $viewName): void
{
    $updated = DB::table('campaigns')
        ->where('slug', CAMPAIGN_SLUG)
        ->update(['template_id' => templateId($viewName), 'updated_at' => now()]);

    if ($updated === 0 && DB::table('campaigns')->where('slug', CAMPAIGN_SLUG)->doesntExist()) {
        fwrite(STDERR, "Campaign fixture missing; run create first.\n");
        exit(1);
    }

    echo 'FIXTURE_TEMPLATE_OK ' . $viewName . PHP_EOL;
}

function dropFixture(): void
{
    // The harness loads the fixture page in a real browser, so the event layer
    // writes PageView rows (and now campaign attribution rows) into event_logs.
    // They are QA-only; remove them with the fixture instead of leaking them
    // into the analytics dashboard.
    DB::table('event_logs')
        ->where('page_path', 'like', '/campaign/' . CAMPAIGN_SLUG . '%')
        ->delete();

    $campaign = DB::table('campaigns')->where('slug', CAMPAIGN_SLUG)->first();
    if ($campaign) {
        DB::table('event_logs')->where('campaign_id', $campaign->id)->delete();
        DB::table('order_details')->where('campaign_id', $campaign->id)->update(['campaign_id' => null]);
        DB::table('campaigns')->where('id', $campaign->id)->delete();
    }

    $product = DB::table('products')->where('slug', PRODUCT_SLUG)->first();
    if ($product) {
        DB::table('productimages')->where('product_id', $product->id)->delete();
        DB::table('products')->where('id', $product->id)->delete();
    }

    echo 'FIXTURE_DROPPED' . PHP_EOL;
}

switch ($command) {
    case 'create':
        createFixture($viewName);
        break;
    case 'template':
        switchTemplate($viewName);
        break;
    case 'drop':
        dropFixture();
        break;
    default:
        fwrite(STDERR, "Unknown command: {$command}\n");
        exit(1);
}
