<?php

/**
 * Release check for the landing-page polish slice.
 *
 * Usage: php tools/verify_landing_polish.php
 *
 * File-level checks only: fast enough for goal_contract close and safe to run
 * before the HTTP/DB feature tests. The feature tests prove runtime behavior.
 */

$root = dirname(__DIR__);
$failures = [];

$read = static function (string $relative) use ($root, &$failures): string {
    $path = $root . '/' . $relative;
    if (!is_file($path)) {
        $failures[] = 'missing ' . $relative;
        return '';
    }
    return (string) file_get_contents($path);
};

$assertContains = static function (string $haystack, string $needle, string $label) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $label;
    }
};

$assertNotContains = static function (string $haystack, string $needle, string $label) use (&$failures): void {
    if (str_contains($haystack, $needle)) {
        $failures[] = $label;
    }
};

// 1. Fluid admin shell, no nested page container.
$layout = $read('resources/views/backEnd/layouts/app.blade.php');
$navbar = $read('resources/views/backEnd/layouts/partials/navbar.blade.php');
$footer = $read('resources/views/backEnd/layouts/partials/footer.blade.php');

$assertContains($layout, 'container-fluid flex-grow-1 container-p-y', 'layout is not fluid');
$assertNotContains($layout, 'container-xxl flex-grow-1 container-p-y', 'layout still uses container-xxl');
$assertContains($navbar, 'layout-navbar container-fluid', 'navbar is not fluid');
$assertContains($footer, '<div class="container-fluid">', 'footer is not fluid');

$innerContainers = [];
$viewsRoot = $root . '/resources/views/backEnd';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    if (str_contains($path, '/layouts/')) {
        continue;
    }
    if (str_contains((string) file_get_contents($path), 'container-xxl flex-grow-1 container-p-y')) {
        $innerContainers[] = $path;
    }
}

// 2. Content panel copy and legacy fields.
$contentFields = $read('resources/views/backEnd/campaign/partials/content-fields.blade.php');
$campaignCreate = $read('resources/views/backEnd/campaign/create.blade.php');
$campaignEdit = $read('resources/views/backEnd/campaign/edit.blade.php');

$assertContains($contentFields, 'contentFieldsFor', 'content fields are not registry-driven');
$assertNotContains($contentFields, 'One row per item', 'old repeater subtitle remains');
$assertNotContains($contentFields, "Fields follow the selected template", 'old panel subtitle remains');
$assertNotContains($contentFields, "Template 1 and 2 use the title", 'legacy feature hint remains');
$assertNotContains($contentFields, "'label' => 'Features'", 'legacy Features definition remains');
$assertNotContains($contentFields, "'label' => 'Image URL'", 'legacy Image URL field remains');

$assertNotContains($campaignCreate, 'name="show_features"', 'create form still has Show Features');
$assertNotContains($campaignEdit, 'name="show_features"', 'edit form still has Show Features');
$assertNotContains($campaignCreate, 'Section Visibility', 'create form still has Section Visibility');
$assertNotContains($campaignEdit, 'Section Visibility', 'edit form still has Section Visibility');

// 3. Layered card image fallback.
$resolver = $read('app/Support/LandingCardImage.php');
$landingIndex = $read('resources/views/backEnd/landing-pages/index.blade.php');

$assertContains($resolver, 'MediaPath::fallbackPath()', 'appearance fallback tier missing');
$assertContains($resolver, 'self::BRAND_FALLBACK', 'branding fallback tier missing');
$assertContains($resolver, 'branding/card-fallback.jpg', 'branding fallback path missing');
$assertContains($landingIndex, 'LandingCardImage::forCampaign', 'manage cards do not use the resolver');

// 4. Branding filebox: logo, final fallback, video, manifest.
$brandingFiles = [
    'public/branding/logo.png',
    'public/branding/card-fallback.jpg',
    'public/branding/brand-intro.mp4',
    'public/branding/manifest.json',
];
$brandingCount = 0;
foreach ($brandingFiles as $relative) {
    if (is_file($root . '/' . $relative) && filesize($root . '/' . $relative) > 0) {
        $brandingCount++;
    } else {
        $failures[] = 'missing branding file ' . $relative;
    }
}

if ($brandingCount === 4) {
    $manifest = json_decode((string) file_get_contents($root . '/public/branding/manifest.json'), true);
    if (!is_array($manifest) || count((array) ($manifest['files'] ?? [])) !== 3) {
        $failures[] = 'branding manifest does not list three files';
    }
}

// 5. Shared order form is included by every design.
$templateLayout = $read('resources/views/layouts/templates.blade.php');
$orderSection = $read('resources/views/frontend/landing/partials/order-section.blade.php');
$orderTest = $read('tests/Feature/LandingOrderFormTest.php');

$assertContains($templateLayout, 'partials.order-section', 'templates layout does not include the order form');
$assertContains($orderSection, "route('landing.order')", 'order form does not post to landing.order');
$assertContains($orderSection, 'name="product_id"', 'order form has no product_id');
$assertContains($orderSection, 'name="campaign_id"', 'order form has no campaign_id');
$assertContains($orderTest, 'admin/campaign', 'admin preview order test missing');
$assertContains($orderTest, 'landing-order', 'order form assertion missing');

// 6. Documentation and image inventory.
$inventoryDoc = $read('docs/IMAGE-INVENTORY-2026-11-12.md');
$verificationDoc = $read('docs/LANDING-VERIFICATION-2026-11-12.md');

$assertContains($inventoryDoc, 'Project image inventory', 'image inventory doc missing header');
$assertContains($verificationDoc, 'gc-mwcq0v50', 'verification doc does not reference this contract');
$assertContains($verificationDoc, 'ses_e33b1873cffegXtyQYUttSYJw5', 'verification doc does not reference this session');

$inventoryJson = json_decode((string) @file_get_contents($root . '/docs/image-inventory.json'), true);
$imageCount = (int) ($inventoryJson['totals']['count'] ?? 0);
if ($imageCount < 150) {
    $failures[] = 'image inventory has fewer than 150 images';
}

if ($failures !== []) {
    echo 'FAIL ' . implode('; ', $failures) . PHP_EOL;
    exit(1);
}

printf(
    "layout=fluid inner_pages=%d copy=clean legacy=removed fallback=layered branding=%d order=present docs=2 images=%d\n",
    count($innerContainers),
    $brandingCount,
    $imageCount
);
