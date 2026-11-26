<?php

/**
 * Release check for the admin Landing Page Designs cards.
 *
 * Proves, with the real rendered admin page and the real files on disk:
 *   1. every one of the nine designs serves its own preview image from the
 *      stable public path frontend/landing/pictures/designs/<view>.png;
 *   2. each public preview is byte-identical to work/zevo/lpage source;
 *   3. the card image resolver uses the registry entry, not the old generated
 *      placeholder hero;
 *   4. active designs render a green "Active" toggle and inactive designs a
 *      red "Inactive" toggle, posting to the matching status route;
 *   5. those routes really flip templates.is_active (inside a rolled-back
 *      transaction, so no data is left changed);
 *   6. the landing-page edit form uses form-label d-block fw-semibold.
 *
 * Usage: php tools/verify_landing_cards.php
 */

require __DIR__ . '/../vendor/autoload.php';

$base = dirname(__DIR__);
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\LandingPageController;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$errors = [];

$fail = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

/** view_name => source file in work/zevo/lpage */
$designs = [
    'ht2' => '01_premium_collection.png',
    't1' => '02_funnel_offer.png',
    'th3' => '03_premium_wall_art.png',
    'th4' => '04_artisan_honey.png',
    'th5' => '05_natural_honey.png',
    'th6' => '06_rose_editorial_abaya.png',
    'th7' => '07_fashion_editorial_abaya.png',
    'th8' => '08_dark_premium_panjabi.png',
    'th9' => '09_soft_commerce_hoodie.png',
];

// 1-2. Public copies exist, are stable, and match the work/zevo source bytes.
foreach ($designs as $view => $sourceFile) {
    $source = $base . '/work/zevo/lpage/' . $sourceFile;
    $public = $base . '/public/frontend/landing/pictures/designs/' . $view . '.png';

    if (!is_file($source)) {
        $fail("source image missing: work/zevo/lpage/{$sourceFile}");
        continue;
    }
    if (!is_file($public)) {
        $fail("public image missing: public/frontend/landing/pictures/designs/{$view}.png");
        continue;
    }
    if (hash_file('sha256', $source) !== hash_file('sha256', $public)) {
        $fail("public image differs from source: {$view}.png");
    }
}

// 3. Registry entries point at those files.
if (count((array) config('landing_templates.templates', [])) < count($designs)) {
    $fail('landing_templates registry is missing design entries');
}
foreach (array_keys($designs) as $view) {
    $configured = (string) config('landing_templates.templates.' . $view . '.card_image', '');
    $expected = 'frontend/landing/pictures/designs/' . $view . '.png';
    if ($configured !== $expected) {
        $fail("config card_image for {$view} is '{$configured}', expected '{$expected}'");
    }
}

// 4. The status column exists; the controller writes it.
if (!Schema::hasColumn('templates', 'is_active')) {
    $fail('templates.is_active column is missing');
}
if (!Route::has('landing-pages.active') || !Route::has('landing-pages.inactive')) {
    $fail('landing-pages.active / landing-pages.inactive routes are missing');
}

// 5. Render the real admin page through the HTTP kernel as an admin.
$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
Auth::loginUsingId(1);

$request = Request::create('/admin/landing-pages/manage', 'GET');
$response = $httpKernel->handle($request);
$html = (string) $response->getContent();

if ($response->getStatusCode() !== 200) {
    $fail('admin manage page returned HTTP ' . $response->getStatusCode());
}

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
libxml_clear_errors();
$xpath = new DOMXPath($doc);

$cards = $xpath->query('//*[@data-design-card]');
$seen = [];

foreach ($cards as $card) {
    /** @var DOMElement $card */
    $img = $xpath->query('.//img[@data-card-image]', $card)->item(0);
    $form = $xpath->query('.//form[@data-status-toggle]', $card)->item(0);
    $button = $form ? $xpath->query('.//button[@type="submit"]', $form)->item(0) : null;

    if (!$img || !$form || !$button) {
        $fail('a design card is missing its image, status form or status button');
        continue;
    }

    $srcPath = (string) parse_url($img->getAttribute('src'), PHP_URL_PATH);
    $view = basename($srcPath, '.png');
    if (!isset($designs[$view])) {
        $fail('card image is not one of the nine designs: ' . $srcPath);
        continue;
    }
    $seen[$view] = true;

    $expectedPath = '/frontend/landing/pictures/designs/' . $view . '.png';
    if (!str_ends_with($srcPath, $expectedPath)) {
        $fail("{$view}: card image is '{$srcPath}', expected '*{$expectedPath}'");
    }
    if (str_contains($srcPath, '/generated/')) {
        $fail("{$view}: card still uses the old generated placeholder hero");
    }

    $template = Template::where('view_name', $view)->first();
    if (!$template) {
        $fail("{$view}: template row not found");
        continue;
    }

    $isActive = (bool) $template->is_active;
    $buttonText = trim((string) preg_replace('~\s+~', ' ', $button->textContent));
    $buttonClass = (string) $button->getAttribute('class');
    $formAction = (string) parse_url($form->getAttribute('action'), PHP_URL_PATH);

    if ($isActive) {
        if ($buttonText !== 'Active') {
            $fail("{$view}: active card button text is '{$buttonText}', expected 'Active'");
        }
        if (!str_contains($buttonClass, 'btn-success')) {
            $fail("{$view}: active card button is not green (missing btn-success)");
        }
        if (str_contains($buttonClass, 'btn-outline-warning') || str_contains($buttonClass, 'btn-danger')) {
            $fail("{$view}: active card button still uses a warning/red class");
        }
        if (!str_ends_with($formAction, '/admin/landing-pages/inactive')) {
            $fail("{$view}: active card form posts to '{$formAction}', expected the inactive route");
        }
    } else {
        if ($buttonText !== 'Inactive') {
            $fail("{$view}: inactive card button text is '{$buttonText}', expected 'Inactive'");
        }
        if (!str_contains($buttonClass, 'btn-danger')) {
            $fail("{$view}: inactive card button is not red (missing btn-danger)");
        }
        if (!str_ends_with($formAction, '/admin/landing-pages/active')) {
            $fail("{$view}: inactive card form posts to '{$formAction}', expected the active route");
        }
    }

    if (!$form->getAttribute('method')) {
        $fail("{$view}: status form has no method");
    }
    if ($xpath->query('.//input[@name="_token"]', $form)->length === 0) {
        $fail("{$view}: status form has no CSRF token");
    }

    echo 'CARD ' . str_pad($view, 4) . ' image=' . $srcPath
        . ' status=' . ($isActive ? 'active' : 'inactive')
        . ' button="' . $buttonText . '" form=' . $formAction . PHP_EOL;
}

foreach (array_keys($designs) as $view) {
    if (!isset($seen[$view])) {
        $fail("no card rendered for design {$view}");
    }
}

// 6. The inactive state must render red, not the old warning colour.
DB::beginTransaction();
try {
    $ht2 = Template::where('view_name', 'ht2')->first();

    if ($ht2) {
        $ht2->is_active = false;
        $ht2->save();

        $inactiveResponse = $httpKernel->handle(Request::create('/admin/landing-pages/manage', 'GET'));
        $inactiveHtml = (string) $inactiveResponse->getContent();

        $inactiveDoc = new DOMDocument();
        libxml_use_internal_errors(true);
        $inactiveDoc->loadHTML('<?xml encoding="utf-8" ?>' . $inactiveHtml);
        libxml_clear_errors();
        $inactiveXpath = new DOMXPath($inactiveDoc);

        $target = null;
        foreach ($inactiveXpath->query('//*[@data-design-card]') as $cardNode) {
            $imgNode = $inactiveXpath->query('.//img[@data-card-image]', $cardNode)->item(0);
            if ($imgNode && str_ends_with((string) parse_url($imgNode->getAttribute('src'), PHP_URL_PATH), '/designs/ht2.png')) {
                $target = $cardNode;
                break;
            }
        }

        if (!$target) {
            $fail('inactive render: ht2 card not found');
        } else {
            $form = $inactiveXpath->query('.//form[@data-status-toggle]', $target)->item(0);
            $button = $form ? $inactiveXpath->query('.//button[@type="submit"]', $form)->item(0) : null;
            $text = $button ? trim((string) preg_replace('~\s+~', ' ', $button->textContent)) : '';
            $class = $button ? (string) $button->getAttribute('class') : '';
            $action = $form ? (string) parse_url($form->getAttribute('action'), PHP_URL_PATH) : '';

            if ($text !== 'Inactive') {
                $fail("inactive render: button text is '{$text}', expected 'Inactive'");
            }
            if (!str_contains($class, 'btn-danger')) {
                $fail('inactive render: button is not red (missing btn-danger)');
            }
            if (str_contains($class, 'btn-success')) {
                $fail('inactive render: button still uses green btn-success');
            }
            if (!str_ends_with($action, '/admin/landing-pages/active')) {
                $fail("inactive render: form posts to '{$action}', expected the active route");
            }

            echo 'INACTIVE-STATE ht2 button="' . $text . '" class="' . $class . '" form=' . $action . PHP_EOL;
        }
    }
} finally {
    DB::rollBack();
}

// 7. The toggle routes must really change templates.is_active. Roll it back.
$template = Template::where('view_name', 'ht2')->first();
if ($template) {
    DB::beginTransaction();
    try {
        $controller = $app->make(LandingPageController::class);
        $wasActive = (bool) $template->is_active;

        try {
            $controller->inactive(new Request(['hidden_id' => $template->id]));
        } catch (Throwable $e) {
            // Toastr/session may be unavailable outside HTTP; the write has
            // already happened, so keep asserting the column.
        }
        $afterInactive = (bool) $template->fresh()->is_active;

        try {
            $controller->active(new Request(['hidden_id' => $template->id]));
        } catch (Throwable $e) {
        }
        $afterActive = (bool) $template->fresh()->is_active;

        if ($wasActive && $afterInactive) {
            $fail('inactive action did not clear templates.is_active');
        }
        if (!$afterActive) {
            $fail('active action did not set templates.is_active');
        }
        echo 'TOGGLE ht2 ' . ($wasActive ? 'active' : 'inactive') . ' -> inactive -> active'
            . ' observed=' . ($afterInactive ? 'active' : 'inactive') . '/' . ($afterActive ? 'active' : 'inactive') . PHP_EOL;
    } finally {
        DB::rollBack();
    }
}

// 7. Edit form label style matches product create.
$editView = (string) file_get_contents($base . '/resources/views/backEnd/landing-pages/edit.blade.php');
if (!str_contains($editView, 'class="form-label d-block fw-semibold">Status</label>')) {
    $fail('landing-pages edit form Status label is not form-label d-block fw-semibold');
}
$createView = (string) file_get_contents($base . '/resources/views/backEnd/landing-pages/create.blade.php');
if (!str_contains($createView, 'class="form-label d-block fw-semibold">Status</label>')) {
    $fail('landing-pages create form Status label is not form-label d-block fw-semibold');
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, 'FAIL ' . $error . PHP_EOL);
    }
    exit(1);
}

echo 'LANDING_CARDS_VERIFIED_OK' . PHP_EOL;
exit(0);
