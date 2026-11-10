<?php

/**
 * Verify the six live landing campaigns.
 *
 * Exit 0 and print LANDING_TEMPLATES_LIVE_OK only when:
 *   - one campaign per active template exists, active and inside its window;
 *   - each campaign is tied to its expected real catalog product;
 *   - the six campaigns use distinct templates and distinct products;
 *   - every component flag is on and the template's required content rows exist;
 *   - the public route renders 200 with the expected section markers, order form
 *     and campaign_id, without a PHP/SQL error page.
 *
 * Usage: php tools/verify_landing_campaigns.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Campaign;
use App\Services\CampaignPricing;
use Illuminate\Http\Request;

$expected = [
    'red-apple-fresh-offer' => [
        'template' => 'default',
        'product_id' => 20,
        'markers' => ['hero-section', 'hero-title', 'name="campaign_id"', 'action="' . route('landing.order') . '"'],
    ],
    'raw-honey-herbal-offer' => [
        'template' => 'herbal-health',
        'product_id' => 14,
        'markers' => ['benefits-section', 'testimonials-section', 'id="order"', 'name="campaign_id"'],
        'rows' => ['benefits' => 3, 'testimonials' => 3],
    ],
    'mustard-oil-countdown-offer' => [
        'template' => 'template-1',
        'product_id' => 24,
        'markers' => ['countdown-container', 'feature-list-wrapper', 'আমাদের কাছে কেনো কিনবেন', 'id="order"', 'name="campaign_id"'],
        'rows' => ['features' => 3, 'why_us' => 3],
        'text' => ['contact_phone'],
    ],
    'red-rice-modern-offer' => [
        'template' => 'template-2',
        'product_id' => 23,
        'markers' => ['countdown-group', 'features-grid', 'why-us-wrapper', 'id="order"', 'name="campaign_id"'],
        'rows' => ['features' => 3, 'why_us' => 3],
        'text' => ['contact_phone'],
    ],
    'sourdough-brand-offer' => [
        'template' => 'template-3',
        'product_id' => 22,
        'markers' => ['hero-video-container', 'gallery-section', 'package-option', 'social-icons', 'id="order-now"', 'name="campaign_id"'],
        'rows' => ['features' => 3, 'benefits' => 3, 'packages' => 2],
        'text' => ['video_url', 'contact_phone', 'email', 'facebook', 'instagram', 'youtube'],
        'gallery' => 2,
    ],
    'desi-ghee-sample-offer' => [
        'template' => 'sample-template',
        'product_id' => 25,
        'markers' => ['feature-card', 'benefit-item', 'testimonial-card', 'faq-item', 'name="campaign_id"'],
        'rows' => ['features' => 3, 'benefits' => 3, 'testimonials' => 3, 'faqs' => 4],
    ],
];

$errors = [];
$warnings = [];

try {
    $httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
} catch (Throwable $e) {
    fwrite(STDERR, 'BOOT_FAIL ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$seenTemplates = [];
$seenProducts = [];

foreach ($expected as $slug => $spec) {
    $campaign = Campaign::with(['template', 'product.image', 'product.images'])->where('slug', $slug)->first();
    if (!$campaign) {
        $errors[] = "{$slug}: campaign row is missing";
        continue;
    }

    if ((int) $campaign->status !== 1) {
        $errors[] = "{$slug}: status is not active";
    }
    if (!$campaign->template) {
        $errors[] = "{$slug}: template relation is missing";
    } elseif ($campaign->template->view_name !== $spec['template']) {
        $errors[] = "{$slug}: template is {$campaign->template->view_name}, expected {$spec['template']}";
    } else {
        $seenTemplates[] = $campaign->template->view_name;
    }
    if (!$campaign->product) {
        $errors[] = "{$slug}: product relation is missing";
    } elseif ((int) $campaign->product_id !== (int) $spec['product_id']) {
        $errors[] = "{$slug}: product_id is {$campaign->product_id}, expected {$spec['product_id']}";
    } else {
        $seenProducts[] = (int) $campaign->product_id;
    }

    if ((int) $campaign->campaign_price_enabled !== 1) {
        $errors[] = "{$slug}: campaign_price_enabled is off";
    }
    if ($campaign->product) {
        $pricing = app(CampaignPricing::class)->resolve($campaign->product, null, $campaign);
        if ($pricing['source'] !== 'campaign') {
            $errors[] = "{$slug}: pricing source is {$pricing['source']} ({$pricing['reason']}), expected campaign";
        } elseif (abs($pricing['price'] - (float) $campaign->campaign_price) > 0.01) {
            $errors[] = "{$slug}: resolved price {$pricing['price']} does not match campaign_price {$campaign->campaign_price}";
        }
        if ((float) $campaign->campaign_price >= (float) $campaign->product->new_price) {
            $errors[] = "{$slug}: campaign price is not below the product price";
        }
    }

    if ($campaign->start_date && $campaign->start_date->copy()->startOfDay()->gt(now())) {
        $errors[] = "{$slug}: campaign has not started";
    }
    if ($campaign->end_date && $campaign->end_date->copy()->endOfDay()->lt(now())) {
        $errors[] = "{$slug}: campaign has expired";
    }

    foreach (['show_features', 'show_benefits', 'show_testimonials', 'show_faq', 'show_cta'] as $flag) {
        if (!$campaign->{$flag}) {
            $errors[] = "{$slug}: {$flag} is off";
        }
    }

    $extra = is_array($campaign->extra_fields) ? $campaign->extra_fields : [];
    foreach (($spec['rows'] ?? []) as $field => $minimum) {
        $count = isset($extra[$field]) && is_array($extra[$field]) ? count($extra[$field]) : 0;
        if ($count < $minimum) {
            $errors[] = "{$slug}: {$field} has {$count} rows, expected at least {$minimum}";
        }
    }
    foreach (($spec['text'] ?? []) as $field) {
        if (empty($extra[$field]) || !is_scalar($extra[$field])) {
            $errors[] = "{$slug}: extra_fields.{$field} is empty";
        }
    }
    if (isset($spec['gallery'])) {
        $galleryCount = $campaign->product ? $campaign->product->images->count() : 0;
        if ($galleryCount < $spec['gallery']) {
            $errors[] = "{$slug}: product gallery has {$galleryCount} images, expected at least {$spec['gallery']}";
        }
    }

    $request = Request::create('/campaign/' . $slug, 'GET');
    try {
        $response = $httpKernel->handle($request);
        $status = $response->getStatusCode();
        $html = (string) $response->getContent();
    } catch (Throwable $e) {
        $errors[] = "{$slug}: HTTP render threw " . get_class($e) . ': ' . $e->getMessage();
        continue;
    }

    if ($status !== 200) {
        $errors[] = "{$slug}: public route returned HTTP {$status}";
    }
    foreach ($spec['markers'] as $marker) {
        if (!str_contains($html, $marker)) {
            $errors[] = "{$slug}: rendered HTML is missing marker '{$marker}'";
        }
    }
    if ($campaign->product && !str_contains($html, (string) $campaign->product->name)) {
        $errors[] = "{$slug}: rendered HTML is missing product name '{$campaign->product->name}'";
    }
    foreach (['Whoops', 'Fatal error', 'Undefined variable', 'SQLSTATE', 'Class not found'] as $errorNeedle) {
        if (stripos($html, $errorNeedle) !== false) {
            $errors[] = "{$slug}: rendered HTML contains error marker '{$errorNeedle}'";
        }
    }
    if (!str_contains($html, 'name="campaign_id"')) {
        $errors[] = "{$slug}: order form is missing campaign_id";
    }

    echo sprintf(
        "CHECK %-32s id=%-3d template=%-14s product=%-24s http=%d html=%d\n",
        $slug,
        $campaign->id,
        $campaign->template->view_name ?? '?',
        $campaign->product->name ?? '?',
        $status,
        strlen($html)
    );
}

if (count($seenTemplates) !== count(array_unique($seenTemplates))) {
    $errors[] = 'Template IDs are not distinct across the six campaigns';
}
if (count($seenProducts) !== count(array_unique($seenProducts))) {
    $errors[] = 'Product IDs are not distinct across the six campaigns';
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, 'FAIL ' . $error . PHP_EOL);
    }
    fwrite(STDERR, 'LANDING_TEMPLATES_VERIFY_FAILED errors=' . count($errors) . PHP_EOL);
    exit(1);
}

echo 'LANDING_TEMPLATES_LIVE_OK' . PHP_EOL;
