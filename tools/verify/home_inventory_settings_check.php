<?php

/**
 * Home inventory + admin homepage settings acceptance check.
 *
 * 1. Inventory: inside a rolled-back transaction, every active variant of a
 *    real in-stock product is set to zero. The card must then render the
 *    Out of Stock state and must not offer Add to Cart / Buy Now.
 * 2. Settings: a real home-page request is made after changing the
 *    recommended title and the featured section status through the same
 *    GeneralSetting row the admin screen writes. The marker must appear and
 *    the disabled rail must disappear; the original values are restored in a
 *    finally block (plus a shutdown guard).
 *
 * Prints HOME_INVENTORY_SETTINGS_OK only when every assertion passes.
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GeneralSetting;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$base = getenv('APP_URL') ?: 'http://127.0.0.1:8000';
$failures = [];
$notes = [];

$check = function (bool $condition, string $message) use (&$failures, &$notes): void {
    if ($condition) {
        $notes[] = 'PASS ' . $message;
    } else {
        $failures[] = $message;
    }
};

$httpGet = function (string $url): ?string {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => "Accept: text/html\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);

    return $body === false ? null : $body;
};

// ---------------------------------------------------------------------------
// 1. Inventory: zero stock on a real product.
// ---------------------------------------------------------------------------
$target = DB::table('product_variants as v')
    ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
    ->join('products as p', 'p.id', '=', 'v.product_id')
    ->where('v.status', 1)
    ->where('p.status', 1)
    ->groupBy('v.product_id')
    ->select('v.product_id')
    ->selectRaw('SUM(GREATEST(COALESCE(s.available, 0) - COALESCE(s.reserved, 0), 0)) as qty')
    ->havingRaw('SUM(GREATEST(COALESCE(s.available, 0) - COALESCE(s.reserved, 0), 0)) > 0')
    ->first();

$check($target !== null, 'found an in-stock product to drive the zero-stock case');

if ($target !== null) {
    $productId = (int) $target->product_id;

    DB::beginTransaction();

    try {
        $variantIds = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->pluck('id');

        DB::table('variant_stocks')
            ->whereIn('variant_id', $variantIds)
            ->update(['available' => 0, 'reserved' => 0]);

        if (Schema::hasTable('inventory_reservations')) {
            DB::table('inventory_reservations')
                ->whereIn('variant_id', $variantIds)
                ->delete();
        }

        $product = Product::with('image')->find($productId);

        $check($product !== null, 'zero-stock product loaded');
        $check($product !== null && !$product->isInStock(), 'isInStock() is false when every variant is zero');

        if ($product !== null) {
            $html = view('frontend.sections.partials._home-product-cards', [
                'products' => collect([$product]),
            ])->render();

            $check(str_contains($html, 'Out of Stock'), 'zero-stock card renders Out of Stock');
            $check(!str_contains($html, 'Add to Cart'), 'zero-stock card offers no Add to Cart');
            $check(!str_contains($html, 'Buy Now'), 'zero-stock card offers no Buy Now');
            $check(str_contains($html, 'data-in-stock="0"'), 'zero-stock card carries data-in-stock="0"');
        }
    } finally {
        DB::rollBack();
    }
}

// ---------------------------------------------------------------------------
// 2. Admin homepage settings -> frontend.
// ---------------------------------------------------------------------------
$adminController = __DIR__ . '/../../app/Http/Controllers/Admin/HomepageSettingsController.php';
$controllerSource = file_get_contents($adminController) ?: '';
$check(str_contains($controllerSource, "'home_recommended_title'"), 'admin controller saves the recommended title');
$check(str_contains($controllerSource, "'home_featured_section'"), 'admin controller saves the featured section status');

$settings = GeneralSetting::query()->orderBy('id')->first();
$check($settings !== null, 'general settings row exists');

if ($settings !== null) {
    $originalTitle = $settings->home_recommended_title;
    $originalFeatured = (int) ($settings->home_featured_section ?? 1);
    $marker = 'ZZ-HOME-' . strtoupper(bin2hex(random_bytes(4)));

    // If this script dies before the finally block, still put the values back.
    register_shutdown_function(function () use ($originalTitle, $originalFeatured) {
        try {
            $row = GeneralSetting::query()->orderBy('id')->first();
            if ($row !== null) {
                $row->home_recommended_title = $originalTitle;
                $row->home_featured_section = $originalFeatured;
                $row->save();
            }
        } catch (\Throwable $ignored) {
        }
    });

    try {
        $settings->home_recommended_title = $marker;
        $settings->home_featured_section = 1;
        $settings->save();

        $html = $httpGet($base . '/');
        $check($html !== null, 'home page reachable for the settings test');

        if ($html !== null) {
            $check(str_contains($html, $marker), 'admin recommended title appears on the home page');
            $check(str_contains($html, 'data-home-section-wrapper="featured"'), 'enabled featured section renders');
        }

        $settings->home_featured_section = 0;
        $settings->save();

        $htmlOff = $httpGet($base . '/');
        $check($htmlOff !== null, 'home page reachable after disabling a section');

        if ($htmlOff !== null) {
            $check(!str_contains($htmlOff, 'data-home-section-wrapper="featured"'), 'disabled featured section disappears from the home page');
        }
    } finally {
        $settings->home_recommended_title = $originalTitle;
        $settings->home_featured_section = $originalFeatured;
        $settings->save();
    }

    $check(
        $settings->fresh()->home_recommended_title === $originalTitle &&
            (int) $settings->fresh()->home_featured_section === $originalFeatured,
        'original homepage settings restored'
    );
}

// ---------------------------------------------------------------------------
// Result.
// ---------------------------------------------------------------------------
if ($failures !== []) {
    fwrite(STDERR, "HOME_INVENTORY_SETTINGS_FAIL\n");
    foreach ($notes as $line) {
        fwrite(STDERR, $line . "\n");
    }
    foreach ($failures as $line) {
        fwrite(STDERR, 'FAIL ' . $line . "\n");
    }
    exit(1);
}

foreach ($notes as $line) {
    echo $line . "\n";
}
echo "HOME_INVENTORY_SETTINGS_OK\n";
