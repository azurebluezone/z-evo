<?php
/**
 * Verifies that all nine new landing designs are fully dynamic.
 *
 *   php tools/verify_landing_dynamic_all.php
 *
 * Coverage:
 *  - nine active templates, nine live campaigns, one per design
 *  - every campaign renders through the real FrontendController route (HTTP 200)
 *  - per-campaign hero/trust/comparison/review/FAQ/variant/gallery content
 *  - local Bootstrap/Font Awesome/CSS/JS assets exist and are referenced
 *  - shared event layer, app header and single order form are present
 *  - ht2 per-variable gating (trust/compare/review/FAQ)
 *  - a status-on campaign still publishes when optional fields are empty
 *  - the home page and /home listing expose all nine live campaigns
 *
 * Prints LANDING_DYNAMIC_ALL_OK only when every assertion passes.
 */
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Models\Campaign;
use App\Models\Template;
use App\Support\LandingTemplateVars;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$failures = [];
$checks = 0;

function check(bool $condition, string $label, array &$failures, int &$checks): void
{
    $checks++;

    if (!$condition) {
        $failures[] = $label;
    }
}

function contains(string $haystack, string $needle, string $label, array &$failures, int &$checks): void
{
    check(str_contains($haystack, $needle), $label, $failures, $checks);
}

function missing(string $haystack, string $needle, string $label, array &$failures, int &$checks): void
{
    check(!str_contains($haystack, $needle), $label, $failures, $checks);
}

function publicPathOf(string $url): ?string
{
    $path = parse_url($url, PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return null;
    }

    $path = ltrim(urldecode($path), '/');

    foreach (['frontend/', 'assets/', 'uploads/'] as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return public_path($path);
        }
    }

    return null;
}

$designs = ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'];
$legacy = ['default', 'herbal-health', 'template-1', 'template-2', 'template-3', 'sample-template'];
$registry = (array) config('landing_templates', []);
$definitions = (array) ($registry['templates'] ?? []);

check(count($definitions) === 9, 'registry defines nine designs', $failures, $checks);

$layoutPath = $base . '/resources/views/layouts/templates.blade.php';
check(is_file($layoutPath), 'shared landing layout exists', $failures, $checks);
$layoutSource = is_file($layoutPath) ? (string) file_get_contents($layoutPath) : '';
contains($layoutSource, "frontend.partials.event-layer-head", 'layout renders event-layer-head', $failures, $checks);
contains($layoutSource, "frontend.partials.event-layer-body", 'layout renders event-layer-body', $failures, $checks);
contains($layoutSource, "frontend.landing.partials.app-header", 'layout renders the app-name header', $failures, $checks);
contains($layoutSource, "frontend.landing.partials.order-section", 'layout renders the single order form', $failures, $checks);
contains($layoutSource, 'landing-interactions.js', 'layout loads the shared interactions script', $failures, $checks);
missing($layoutSource, '<footer', 'layout renders no footer', $failures, $checks);

// Source-level checks: every view extends the shared layout and drops the old
// standalone header/footer/customer form/demo scripts.
foreach ($designs as $key) {
    $viewPath = $base . '/resources/views/templates/' . $key . '.blade.php';
    check(is_file($viewPath), 'view exists: ' . $key, $failures, $checks);
    check(view()->exists('templates.' . $key), 'Blade can compile templates/' . $key, $failures, $checks);

    if (!is_file($viewPath)) {
        continue;
    }

    $source = (string) file_get_contents($viewPath);
    contains($source, "@extends('layouts.templates')", $key . ' extends the shared layout', $failures, $checks);
    missing($source, '<footer', $key . ' contains no footer', $failures, $checks);
    missing($source, 'name="customer_name"', $key . ' drops the old customer form', $failures, $checks);
    missing($source, 'sweetalert', $key . ' drops SweetAlert', $failures, $checks);
    missing($source, 'zombie-direct-order-form', $key . ' drops the demo order handler', $failures, $checks);
    contains($source, '#landing-order', $key . ' links to the shared order anchor', $failures, $checks);
}

// Template rows: all nine new designs active, every legacy design inactive.
$activeNew = Template::whereIn('view_name', $designs)->where('is_active', true)->count();
$activeLegacy = Template::whereIn('view_name', $legacy)->where('is_active', true)->count();
check($activeNew === 9, 'all nine new templates are active', $failures, $checks);
check($activeLegacy === 0, 'all six legacy templates are inactive', $failures, $checks);

// Generated hero/gallery JPEGs are real images on disk.
foreach ($designs as $key) {
    $hero = public_path('frontend/landing/pictures/generated/' . $key . '-hero.jpg');
    check(is_file($hero), 'generated hero exists: ' . $key, $failures, $checks);

    if (is_file($hero)) {
        $info = @getimagesize($hero);
        check(is_array($info) && ($info[2] ?? null) === IMAGETYPE_JPEG, 'generated hero is JPEG: ' . $key, $failures, $checks);
        check(($info[0] ?? 0) >= 1200 && ($info[1] ?? 0) >= 800, 'generated hero has hero dimensions: ' . $key, $failures, $checks);
    }
}

foreach (['wallet-front.jpg', 'wallet-open.jpg', 'wallet-detail.jpg', 'wallet-lifestyle.jpg'] as $galleryName) {
    $path = public_path('frontend/landing/pictures/generated/' . $galleryName);
    check(is_file($path), 'generated gallery exists: ' . $galleryName, $failures, $checks);
}

// Each design has exactly one live campaign with its own content.
$campaigns = [];

foreach ($designs as $key) {
    $template = Template::where('view_name', $key)->first();

    if (!$template) {
        $failures[] = 'template row missing: ' . $key;
        continue;
    }

    $campaign = Campaign::with(['template', 'product.image', 'product.images'])
        ->where('template_id', $template->id)
        ->where('status', 1)
        ->orderBy('sort_order')
        ->first();

    check($campaign !== null, 'live campaign exists for ' . $key, $failures, $checks);

    if ($campaign) {
        $campaigns[$key] = $campaign;
        check((string) $campaign->image_one !== '', $key . ' campaign has image_one', $failures, $checks);
        $heroPath = public_path(preg_replace('~^public/~', '', (string) $campaign->image_one));
        check(is_file($heroPath), $key . ' campaign image_one exists on disk', $failures, $checks);
        check(is_array($campaign->extra_fields) && $campaign->extra_fields !== [], $key . ' campaign carries extra_fields', $failures, $checks);
        check(!empty($campaign->extra_fields['hero_image']), $key . ' campaign carries a hero_image override', $failures, $checks);
        check(!empty($campaign->extra_fields['gallery_images']), $key . ' campaign carries gallery_images', $failures, $checks);
        check(!empty($campaign->extra_fields['trust_items']), $key . ' campaign carries trust_items', $failures, $checks);
        check(!empty($campaign->extra_fields['comparison']), $key . ' campaign carries comparison', $failures, $checks);
        check(!empty($campaign->extra_fields['reviews']), $key . ' campaign carries reviews', $failures, $checks);
        check(!empty($campaign->extra_fields['variants']), $key . ' campaign carries variants', $failures, $checks);

        if (in_array('faq', (array) ($definitions[$key]['default_groups'] ?? []), true)) {
            check(!empty($campaign->extra_fields['faqs']), $key . ' campaign carries faqs', $failures, $checks);
        }
    }
}

// Render every campaign through the real public route.
$eventNames = ['page_view', 'view_item', 'scroll_depth', 'outbound_click', 'begin_checkout', 'add_to_cart', 'form_start', 'generate_lead'];
$rendered = [];

foreach ($campaigns as $key => $campaign) {
    try {
        $response = $app->handle(Request::create('/campaign/' . $campaign->slug, 'GET'));
        $status = $response->getStatusCode();
        $html = (string) $response->getContent();
        $rendered[$key] = $html;

        check($status === 200, $key . ' campaign route returns HTTP 200', $failures, $checks);
        check(strlen($html) > 20000, $key . ' page is a full document', $failures, $checks);

        contains($html, 'window.__TRACK__', $key . ' renders tracking identity', $failures, $checks);
        contains($html, 'window.__CONSENT__', $key . ' renders consent state', $failures, $checks);
        contains($html, 'window.__EVENT_LAYER__', $key . ' renders the event layer', $failures, $checks);
        contains($html, 'window.__CAMPAIGN__', $key . ' renders campaign attribution', $failures, $checks);
        contains($html, 'landing-interactions.js', $key . ' loads shared interactions', $failures, $checks);
        contains($html, 'data-purpose="app-name-header"', $key . ' renders the app-name header', $failures, $checks);
        contains($html, 'data-purpose="system-order-form"', $key . ' renders the system order form', $failures, $checks);
        contains($html, 'action="' . route('landing.order') . '"', $key . ' order form posts to /landing-order', $failures, $checks);
        contains($html, 'name="campaign_id" value="' . $campaign->id . '"', $key . ' order form carries the campaign id', $failures, $checks);
        contains($html, 'name="product_id" value="' . $campaign->product_id . '"', $key . ' order form carries the product id', $failures, $checks);
        check(substr_count($html, 'id="landing-order"') === 1, $key . ' has exactly one order section', $failures, $checks);
        check(substr_count($html, 'data-purpose="system-order-form"') === 1, $key . ' has exactly one order form', $failures, $checks);
        missing($html, '<footer', $key . ' page has no footer', $failures, $checks);
        missing($html, 'sweetalert', $key . ' page loads no SweetAlert', $failures, $checks);
        missing($html, 'cdn.jsdelivr.net/npm/bootstrap', $key . ' page uses local Bootstrap', $failures, $checks);
        missing($html, 'cdnjs.cloudflare.com/ajax/libs/font-awesome', $key . ' page uses local Font Awesome', $failures, $checks);
        missing($html, 'name="customer_name"', $key . ' page has no legacy customer form', $failures, $checks);

        foreach ($eventNames as $eventName) {
            contains($html, $eventName, $key . ' event layer exposes ' . $eventName, $failures, $checks);
        }

        if ($key === 'ht2') {
            contains($html, 'id="countdown-timer"', 'ht2 renders its countdown timer', $failures, $checks);
            contains($html, 'var end = ', 'ht2 renders its countdown data', $failures, $checks);
        } else {
            contains($html, 'data-countdown-end=', $key . ' renders a live countdown hook', $failures, $checks);
        }

        if ($campaign->product) {
            contains($html, e($campaign->product->name), $key . ' shows the selected product name', $failures, $checks);
        }

        $tpl = LandingTemplateVars::make(null, $campaign->template, $campaign);
        contains($html, e($campaign->hero_title), $key . ' shows its own campaign hero title', $failures, $checks);
        contains($html, $tpl->moneyBn($campaign->campaign_price), $key . ' shows its own campaign price', $failures, $checks);
        contains($html, '১০০% অরিজিনাল', $key . ' shows campaign trust rows', $failures, $checks);
        contains($html, 'পণ্যের মান সত্যিই ভালো', $key . ' shows campaign review text', $failures, $checks);
        contains($html, '২টির কম্বো', $key . ' shows campaign variant labels', $failures, $checks);
        contains($html, asset('frontend/landing/pictures/generated/' . $key . '-hero.jpg'), $key . ' links its generated hero image', $failures, $checks);

        if (in_array('faq', (array) ($definitions[$key]['default_groups'] ?? []), true)) {
            contains($html, 'ডেলিভারি পেতে কতদিন লাগে?', $key . ' shows campaign FAQ content', $failures, $checks);
        }

        // Every local asset the page references must exist under public/.
        preg_match_all('~(?:src|href)="([^"]+)"~i', $html, $matches);
        $localAssets = [];

        foreach (array_unique($matches[1] ?? []) as $url) {
            $assetPath = publicPathOf($url);

            if ($assetPath === null) {
                continue;
            }

            if (!preg_match('~\.(css|js|jpe?g|png|webp|svg|ico|gif)(\?|$)~i', $assetPath)) {
                continue;
            }

            $localAssets[$assetPath] = true;
            check(is_file($assetPath), $key . ' references an existing local asset: ' . str_replace($base . '/', '', $assetPath), $failures, $checks);
        }

        check($localAssets !== [], $key . ' page references local assets', $failures, $checks);

        // The template's declared local assets must actually be printed.
        foreach ((array) ($definitions[$key]['assets']['css'] ?? []) as $asset) {
            if (preg_match('~^https?://~i', $asset)) {
                continue;
            }
            contains($html, asset($asset), $key . ' prints declared CSS ' . $asset, $failures, $checks);
            check(is_file(public_path($asset)), $key . ' declared CSS exists ' . $asset, $failures, $checks);
        }

        foreach (['head_js', 'body_js'] as $assetGroup) {
            foreach ((array) ($definitions[$key]['assets'][$assetGroup] ?? []) as $asset) {
                if (preg_match('~^https?://~i', $asset)) {
                    continue;
                }
                contains($html, asset($asset), $key . ' prints declared JS ' . $asset, $failures, $checks);
                check(is_file(public_path($asset)), $key . ' declared JS exists ' . $asset, $failures, $checks);
            }
        }
    } catch (Throwable $exception) {
        $failures[] = $key . ' render threw ' . get_class($exception) . ': ' . $exception->getMessage();
    }
}

// ht2 per-variable gating: disable four individual variables and prove each
// one disappears while the rest of its section stays rendered.
DB::beginTransaction();

try {
    $template = Template::where('view_name', 'ht2')->firstOrFail();
    $campaign = Campaign::with(['template', 'product.image', 'product.images', 'images'])
        ->where('template_id', $template->id)
        ->where('status', 1)
        ->firstOrFail();

    $config = (array) $template->config;
    $enabled = (array) ($config['enabled_variables'] ?? []);
    $config['enabled_variables'] = array_values(array_diff($enabled, ['trust_icon', 'compare_theirs', 'review_rating', 'faq_answer']));
    $template->config = $config;
    $template->save();

    $campaign->refresh()->load(['template', 'product.image', 'product.images', 'images']);
    $html = app(FrontendController::class)->renderCampaign($campaign)->render();

    missing($html, 'fa-solid fa-shield-halved', 'ht2 trust_icon off hides trust icons', $failures, $checks);
    contains($html, '১০০% অরিজিনাল', 'ht2 trust_title on keeps trust titles', $failures, $checks);
    missing($html, 'সাধারণ বাজার', 'ht2 compare_theirs off hides the market column', $failures, $checks);
    contains($html, 'ফিচার / বৈশিষ্ট্য', 'ht2 compare_feature on keeps the feature column', $failures, $checks);
    missing($html, 'aria-label="5 out of 5"', 'ht2 review_rating off hides review stars', $failures, $checks);
    contains($html, 'পণ্যের মান সত্যিই ভালো', 'ht2 review_text on keeps review text', $failures, $checks);
    missing($html, 'ঢাকায় ২৪ ঘণ্টা এবং ঢাকার বাইরে ২-৩ কর্মদিবস লাগে।', 'ht2 faq_answer off hides FAQ answers', $failures, $checks);
    contains($html, 'ডেলিভারি পেতে কতদিন লাগে?', 'ht2 faq_question on keeps FAQ questions', $failures, $checks);
} catch (Throwable $exception) {
    $failures[] = 'ht2 toggle render threw ' . get_class($exception) . ': ' . $exception->getMessage();
} finally {
    DB::rollBack();
}

// Optional fields: creating a status-on campaign with nothing else filled must
// succeed and keep it published with a slug, name and date.
DB::beginTransaction();

try {
    $before = (int) (Campaign::max('id') ?? 0);
    $request = Request::create('/admin/campaign/save', 'POST', ['status' => 1]);
    app(CampaignController::class)->store($request);

    $created = Campaign::where('id', '>', $before)->orderByDesc('id')->first();
    check($created !== null, 'optional create stores a campaign', $failures, $checks);

    if ($created) {
        check((int) $created->status === 1, 'optional create stays published', $failures, $checks);
        check((string) $created->name !== '', 'optional create supplies a fallback name', $failures, $checks);
        check((string) $created->date !== '', 'optional create supplies a fallback date', $failures, $checks);
        check((string) $created->slug !== '', 'optional create supplies a slug', $failures, $checks);
        check((string) ($created->campaign_type ?? '') === 'product', 'optional create supplies a campaign type', $failures, $checks);
        check($created->template_id === null, 'optional create accepts an empty template', $failures, $checks);
        check($created->product_id === null, 'optional create accepts an empty product', $failures, $checks);
    }
} catch (Throwable $exception) {
    $failures[] = 'optional create threw ' . get_class($exception) . ': ' . $exception->getMessage();
} finally {
    DB::rollBack();
}

// Optional fields on edit: posting only hidden_id + status must keep the
// existing name and slug instead of blanking them.
DB::beginTransaction();

try {
    $campaign = Campaign::where('slug', 'ht2-premium-collection')->firstOrFail();
    $nameBefore = $campaign->name;
    $slugBefore = $campaign->slug;

    $request = Request::create('/admin/campaign/update', 'POST', [
        'hidden_id' => $campaign->id,
        'status' => 1,
    ]);
    app(CampaignController::class)->update($request);

    $fresh = Campaign::find($campaign->id);
    check($fresh !== null, 'optional update keeps the campaign row', $failures, $checks);

    if ($fresh) {
        check($fresh->name === $nameBefore, 'optional update keeps the existing name', $failures, $checks);
        check($fresh->slug === $slugBefore, 'optional update keeps the existing slug', $failures, $checks);
        check((int) $fresh->status === 1, 'optional update keeps the campaign published', $failures, $checks);
    }
} catch (Throwable $exception) {
    $failures[] = 'optional update threw ' . get_class($exception) . ': ' . $exception->getMessage();
} finally {
    DB::rollBack();
}

// Home and campaign listing expose every live campaign.
foreach (['/' => 'home page', '/home' => 'campaign listing'] as $path => $label) {
    try {
        $response = $app->handle(Request::create($path, 'GET'));
        $html = (string) $response->getContent();
        check($response->getStatusCode() === 200, $label . ' returns HTTP 200', $failures, $checks);

        foreach ($campaigns as $key => $campaign) {
            contains($html, e($campaign->name), $label . ' lists ' . $key . ' campaign', $failures, $checks);
        }
    } catch (Throwable $exception) {
        $failures[] = $label . ' threw ' . get_class($exception) . ': ' . $exception->getMessage();
    }
}

if ($failures !== []) {
    fwrite(STDERR, "LANDING_DYNAMIC_ALL_FAILED\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo 'LANDING_DYNAMIC_ALL_OK (' . $checks . " checks, 9 designs)\n";
exit(0);
