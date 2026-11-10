<?php

/**
 * Verify the six pages in the admin Landing Pages manager.
 *
 * Exit 0 and print LANDING_PAGES_ALL_UPDATED_OK only when:
 *   - exactly six landing_pages rows exist, one per active template 1-6;
 *   - each row is active, has a distinct product/campaign, the product image,
 *     campaign price, product_id/campaign_id and the template's content rows;
 *   - no legacy Demo title/slug remains;
 *   - each public /landing-pages/{slug} returns 200 with the template markers,
 *     order form and hidden product_id + campaign_id.
 *
 * Usage: php tools/verify_landing_pages.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$expected = [
    'red-apple-fresh-offer' => [
        'template_id' => 1,
        'product_id' => 20,
        'campaign_id' => 767,
        'markers' => ['hero-title', 'name="product_id"', 'name="campaign_id"', 'action="/landing-order"'],
        'rows' => [],
    ],
    'raw-honey-herbal-offer' => [
        'template_id' => 2,
        'product_id' => 14,
        'campaign_id' => 768,
        'markers' => ['benefits-section', 'testimonials-section', 'id="order"', 'name="product_id"', 'name="campaign_id"', 'action="/landing-order"'],
        'rows' => ['benefits' => 3, 'testimonials' => 3],
    ],
    'mustard-oil-countdown-offer' => [
        'template_id' => 3,
        'product_id' => 24,
        'campaign_id' => 769,
        'markers' => ['countdown-container', 'feature-list-wrapper', 'আমাদের কাছে কেনো কিনবেন', 'id="order"', 'name="product_id"', 'name="campaign_id"', 'action="/landing-order"'],
        'rows' => ['features' => 3, 'why_us' => 3],
    ],
    'red-rice-modern-offer' => [
        'template_id' => 4,
        'product_id' => 23,
        'campaign_id' => 770,
        'markers' => ['countdown-group', 'features-grid', 'why-us-wrapper', 'id="order"', 'name="product_id"', 'name="campaign_id"', 'action="/landing-order"'],
        'rows' => ['features' => 3, 'why_us' => 3],
    ],
    'sourdough-brand-offer' => [
        'template_id' => 5,
        'product_id' => 22,
        'campaign_id' => 771,
        'markers' => ['hero-video-container', 'gallery-section', 'package-option', 'social-icons', 'id="order-now"', 'name="product_id"', 'name="campaign_id"', 'action="/landing-order"'],
        'rows' => ['features' => 3, 'benefits' => 3, 'packages' => 2, 'gallery_images' => 3],
    ],
    'desi-ghee-sample-offer' => [
        'template_id' => 6,
        'product_id' => 25,
        'campaign_id' => 772,
        'markers' => ['feature-card', 'benefit-item', 'testimonial-card', 'faq-item', 'name="product_id"', 'name="campaign_id"', 'action="/landing-order"'],
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

$total = LandingPage::count();
if ($total !== 6) {
    $errors[] = "Expected exactly 6 landing pages, found {$total}";
}

$demoRows = LandingPage::where('title', 'like', 'Demo%')->orWhere('slug', 'like', 'demo-%')->count();
if ($demoRows > 0) {
    $errors[] = "Legacy Demo landing pages still exist: {$demoRows}";
}

$seenTemplates = [];
$seenProducts = [];
$seenCampaigns = [];

foreach ($expected as $slug => $spec) {
    $page = LandingPage::with(['template', 'template'])
        ->where('slug', $slug)
        ->first();

    if (!$page) {
        $errors[] = "Missing landing page {$slug}";
        continue;
    }

    if (!(int) $page->status) {
        $errors[] = "{$slug}: status is not active";
    }
    if ((int) $page->template_id !== $spec['template_id']) {
        $errors[] = "{$slug}: template_id {$page->template_id} != {$spec['template_id']}";
    }

    $seenTemplates[] = (int) $page->template_id;

    $productId = (int) ($page->extra_fields['product_id'] ?? 0);
    $campaignId = (int) ($page->extra_fields['campaign_id'] ?? 0);
    if ($productId !== $spec['product_id']) {
        $errors[] = "{$slug}: product_id {$productId} != {$spec['product_id']}";
    }
    if ($campaignId !== $spec['campaign_id']) {
        $errors[] = "{$slug}: campaign_id {$campaignId} != {$spec['campaign_id']}";
    }

    $seenProducts[] = $productId;
    $seenCampaigns[] = $campaignId;

    $product = App\Models\Product::with('image')->find($spec['product_id']);
    if (!$product) {
        $errors[] = "{$slug}: product {$spec['product_id']} missing from products";
    } else {
        if (trim((string) $page->title) !== trim((string) $product->name)) {
            $errors[] = "{$slug}: title '{$page->title}' != product '{$product->name}'";
        }

        $expectedImage = optional($product->image)->image;
        if ($expectedImage && $page->image_url !== $expectedImage) {
            $errors[] = "{$slug}: image_url '{$page->image_url}' != '{$expectedImage}'";
        }
    }

    if ($page->cta_link !== '/landing-order') {
        $errors[] = "{$slug}: cta_link '{$page->cta_link}' != '/landing-order'";
    }
    if (!$page->cta_text) {
        $errors[] = "{$slug}: missing cta_text";
    }
    if (!$page->description) {
        $errors[] = "{$slug}: missing description";
    }

    $extra = is_array($page->extra_fields) ? $page->extra_fields : [];
    if (!isset($extra['old_price']) || !isset($extra['new_price'])) {
        $errors[] = "{$slug}: missing old_price/new_price";
    }

    foreach ($spec['rows'] as $key => $minimum) {
        $count = isset($extra[$key]) && is_array($extra[$key]) ? count($extra[$key]) : 0;
        if ($count < $minimum) {
            $errors[] = "{$slug}: {$key} has {$count}, need {$minimum}";
        }
    }

    $request = Request::create('/landing-pages/' . $slug, 'GET');
    try {
        $response = $httpKernel->handle($request);
        $status = $response->getStatusCode();
        $html = $response->getContent();
    } catch (Throwable $e) {
        $errors[] = "{$slug}: HTTP exception " . $e->getMessage();
        continue;
    }

    if ($status !== 200) {
        $errors[] = "{$slug}: HTTP {$status}";
        continue;
    }

    foreach ($spec['markers'] as $marker) {
        if (!str_contains($html, $marker)) {
            $errors[] = "{$slug}: marker missing: {$marker}";
        }
    }

    if ($product && !str_contains($html, $product->name)) {
        $errors[] = "{$slug}: product name not rendered";
    }

    if (isset($extra['new_price']) && !str_contains($html, (string) $extra['new_price'])) {
        $warnings[] = "{$slug}: price string not found in HTML";
    }

    echo 'CHECK ' . str_pad($slug, 32) . ' id=' . $page->id
        . ' template=' . ($page->template->view_name ?? '?')
        . ' product=' . str_pad($product->name ?? '?', 28)
        . ' http=' . $status
        . ' html=' . strlen($html) . PHP_EOL;
}

if (count(array_unique($seenTemplates)) !== count($expected)) {
    $errors[] = 'Templates are not distinct';
}
if (count(array_unique($seenProducts)) !== count($expected)) {
    $errors[] = 'Products are not distinct';
}
if (count(array_unique($seenCampaigns)) !== count($expected)) {
    $errors[] = 'Campaigns are not distinct';
}

foreach ($warnings as $warning) {
    fwrite(STDERR, 'WARN ' . $warning . PHP_EOL);
}

// The admin list is the page the user reported; prove it renders all six rows.
Auth::loginUsingId(1);
$adminRequest = Request::create('/admin/landing-pages/manage', 'GET');
try {
    $adminResponse = $httpKernel->handle($adminRequest);
    $adminStatus = $adminResponse->getStatusCode();
    $adminHtml = $adminResponse->getContent();
} catch (Throwable $e) {
    $errors[] = 'admin list: HTTP exception ' . $e->getMessage();
    $adminStatus = 0;
    $adminHtml = '';
}

if ($adminStatus !== 200) {
    $errors[] = "admin list: HTTP {$adminStatus}";
} else {
    foreach ($expected as $slug => $spec) {
        $product = App\Models\Product::find($spec['product_id']);
        if ($product && !str_contains($adminHtml, $product->name)) {
            $errors[] = "admin list: missing row for {$product->name}";
        }
    }
    if (str_contains($adminHtml, 'demo-default-offer') || str_contains($adminHtml, 'demo-second-offer')) {
        $errors[] = 'admin list: legacy demo slug still rendered';
    }
    echo 'ADMIN ' . str_pad('/admin/landing-pages/manage', 32) . ' http=' . $adminStatus
        . ' rows=' . substr_count($adminHtml, '<tr') . ' html=' . strlen($adminHtml) . PHP_EOL;
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, 'FAIL ' . $error . PHP_EOL);
    }
    exit(1);
}

echo 'LANDING_PAGES_ALL_UPDATED_OK' . PHP_EOL;
exit(0);
