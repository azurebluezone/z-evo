<?php
/**
 * Verifies the new landing-template shell end to end.
 *
 *   php tools/verify_landing_template_layout.php
 *
 * The check inserts one temporary campaign inside a transaction, renders it
 * through the real FrontendController path, and rolls everything back. It
 * exits 0 and prints LANDING_TEMPLATE_LAYOUT_OK only when every assertion
 * passes.
 */
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\LandingPageController as AdminLandingPageController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Models\Campaign;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Template;
use App\Support\LandingTemplateVars;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

$registry = (array) config('landing_templates', []);
$groups = (array) ($registry['groups'] ?? []);
$templatesConfig = (array) ($registry['templates'] ?? []);

$variableCount = 0;
foreach ($groups as $group) {
    $variableCount += count((array) ($group['variables'] ?? []));
}

check($variableCount === 72, 'registry exposes 72 variables', $failures, $checks);
check(count($groups) === 15, 'registry exposes 15 groups', $failures, $checks);
check(count($templatesConfig) === 9, 'registry exposes 9 designs', $failures, $checks);
check(isset($templatesConfig['ht2']), 'ht2 design is registered', $failures, $checks);

$layoutPath = $base . '/resources/views/layouts/templates.blade.php';
$ht2Path = $base . '/resources/views/templates/ht2.blade.php';
$toggleView = 'backEnd.landing-pages.template-variables';

foreach ([
    $base . '/public/frontend/landing/ht2.css',
    $base . '/public/frontend/landing/ht2-tailwind-config.js',
    $base . '/public/frontend/landing/templates-layout.css',
    $layoutPath,
    $ht2Path,
] as $file) {
    check(is_file($file), 'required file exists: ' . str_replace($base . '/', '', $file), $failures, $checks);
}

check(view()->exists($toggleView), 'admin template-variable view exists', $failures, $checks);
check(Route::has('landing-pages.template-variables'), 'admin template-variable GET route exists', $failures, $checks);
check(Route::has('landing-pages.template-variables.update'), 'admin template-variable POST route exists', $failures, $checks);
check(method_exists(AdminLandingPageController::class, 'templateVariables'), 'LandingPageController::templateVariables exists', $failures, $checks);
check(method_exists(AdminLandingPageController::class, 'updateTemplateVariables'), 'LandingPageController::updateTemplateVariables exists', $failures, $checks);

$layoutSource = (string) file_get_contents($layoutPath);
contains($layoutSource, "frontend.partials.event-layer-head", 'layout includes event-layer-head', $failures, $checks);
contains($layoutSource, "frontend.partials.event-layer-body", 'layout includes event-layer-body', $failures, $checks);
contains($layoutSource, "frontend.landing.partials.app-header", 'layout includes app header', $failures, $checks);
contains($layoutSource, "frontend.landing.partials.order-section", 'layout includes shared order form', $failures, $checks);
missing($layoutSource, '<footer', 'layout contains no footer', $failures, $checks);

$ht2Source = (string) file_get_contents($ht2Path);
missing($ht2Source, '<footer', 'ht2 view contains no footer', $failures, $checks);
missing($ht2Source, 'NavigationHeader', 'ht2 view drops the original navigation header', $failures, $checks);

DB::beginTransaction();

try {
    $template = Template::updateOrCreate(
        ['view_name' => 'ht2'],
        [
            'name' => $templatesConfig['ht2']['name'] ?? 'HT2 - Premium product',
            'description' => 'Verification row for the ht2 landing layout.',
            'is_active' => true,
            'config' => [
                'landing_template' => 'ht2',
                'enabled_variables' => LandingTemplateVars::defaultEnabledVariables('ht2'),
                'extra_fields' => [],
            ],
        ]
    );

    $product = Product::with(['image', 'images'])->where('status', 1)->first() ?? Product::with(['image', 'images'])->first();

    $campaign = Campaign::create([
        'name' => 'Landing layout verification ' . Str::random(6),
        'slug' => 'landing-layout-verification-' . Str::random(10),
        'date' => now()->toDateString(),
        'short_description' => 'Verification short description',
        'description' => 'Verification description',
        'review' => 'Verification review',
        'image_one' => '',
        'hero_title' => 'Verification Hero Product',
        'hero_subtitle' => 'Verification hero subtitle',
        'hero_button_text' => 'Verification Order CTA',
        'status' => '1',
        'template_id' => $template->id,
        'product_id' => $product?->id,
        'extra_fields' => [
            'announce_text' => 'Verification announcement',
            'announce_highlight' => 'Verification highlight',
            'hero_badge' => 'Verification badge',
            'submit_label' => 'Verification submit',
            'trust_items' => [
                ['icon' => 'fa-solid fa-shield-halved', 'title' => 'Verification trust', 'text' => 'Verification trust text'],
            ],
            'reviews' => [
                ['name' => 'Verification Buyer', 'location' => 'Dhaka', 'text' => 'Verification review text', 'rating' => 5, 'verified' => 'Verified'],
            ],
            'faqs' => [
                ['question' => 'Verification question?', 'answer' => 'Verification answer.'],
            ],
        ],
    ]);

    $campaign->load(['template', 'product.image', 'product.images', 'images']);

    $html = app(FrontendController::class)->renderCampaign($campaign)->render();

    check(strlen($html) > 5000, 'rendered campaign is a full page', $failures, $checks);
    contains($html, 'frontend/landing/ht2.css', 'ht2 CSS is loaded by the layout', $failures, $checks);
    contains($html, 'frontend/landing/templates-layout.css', 'shared layout CSS is loaded', $failures, $checks);
    contains($html, 'frontend/landing/ht2-tailwind-config.js', 'ht2 tailwind config is loaded', $failures, $checks);
    contains($html, 'window.__TRACK__', 'event layer head is rendered', $failures, $checks);
    contains($html, 'window.__CONSENT__', 'event layer consent state is rendered', $failures, $checks);
    contains($html, 'data-purpose="app-name-header"', 'app-name header is rendered', $failures, $checks);
    contains($html, 'data-purpose="system-order-form"', 'shared order form is rendered', $failures, $checks);
    contains($html, 'id="landing-order"', 'order section has the shared anchor', $failures, $checks);
    contains($html, 'action="' . route('landing.order') . '"', 'order form posts to /landing-order', $failures, $checks);
    contains($html, 'name="product_id"', 'order form carries product_id', $failures, $checks);
    contains($html, 'name="campaign_id"', 'order form carries campaign_id', $failures, $checks);
    contains($html, 'name="quantity"', 'order form carries quantity', $failures, $checks);
    contains($html, 'name="_token"', 'order form carries CSRF token', $failures, $checks);
    contains($html, 'Verification Hero Product', 'hero title resolves from the campaign/product', $failures, $checks);
    contains($html, 'Verification submit', 'submit label resolves from campaign content', $failures, $checks);
    missing($html, '<footer', 'rendered page has no footer', $failures, $checks);
    missing($html, 'name="customer_name"', 'old ht2 customer form is not rendered', $failures, $checks);
    check(substr_count($html, 'id="landing-order"') === 1, 'exactly one shared order section is rendered', $failures, $checks);

    $settingsName = GeneralSetting::first()->name ?? config('app.name', 'ZEvo');
    contains($html, $settingsName, 'application name is shown in the header', $failures, $checks);

    if ($product) {
        contains($html, 'name="product_id" value="' . $product->id . '"', 'order form product matches the campaign product', $failures, $checks);
    }

    contains($html, 'name="campaign_id" value="' . $campaign->id . '"', 'order form campaign matches the rendered campaign', $failures, $checks);

    // Admin campaign form: the ht2 template must expose only enabled content
    // variables, and the repeater column map must follow the same list.
    $panel = view('backEnd.campaign.partials.content-fields', [
        'templates' => collect([$template]),
        'edit_data' => null,
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ])->render();

    check((bool) preg_match("/data-template-fields='([^']+)'/s", $panel, $match), 'campaign panel exposes a template field map', $failures, $checks);

    if (!empty($match[1])) {
        $fieldMap = json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
        $entry = $fieldMap[(string) $template->id] ?? null;
        $fields = is_array($entry) ? ($entry['fields'] ?? []) : [];
        $columns = is_array($entry) ? ($entry['columns'] ?? []) : [];

        check(in_array('announce_text', $fields, true), 'campaign form includes announce_text', $failures, $checks);
        check(in_array('announce_highlight', $fields, true), 'campaign form includes announce_highlight', $failures, $checks);
        check(in_array('hero_badge', $fields, true), 'campaign form includes hero_badge', $failures, $checks);
        check(in_array('submit_label', $fields, true), 'campaign form includes submit_label', $failures, $checks);
        check(in_array('trust_items', $fields, true), 'campaign form includes trust_items', $failures, $checks);
        check(in_array('reviews', $fields, true), 'campaign form includes reviews', $failures, $checks);
        check(in_array('variants', $fields, true), 'campaign form includes variants', $failures, $checks);
        check(in_array('comparison', $fields, true), 'campaign form includes comparison', $failures, $checks);
        check(in_array('faqs', $fields, true), 'campaign form includes faqs', $failures, $checks);
        check(!in_array('features', $fields, true), 'campaign form hides the old features field for ht2', $failures, $checks);
        check(!in_array('benefits', $fields, true), 'campaign form hides the old benefits field for ht2', $failures, $checks);
        check(($columns['reviews'] ?? []) === ['name', 'location', 'text', 'rating', 'avatar', 'date', 'verified'], 'review repeater columns follow the registry', $failures, $checks);
        check(($columns['trust_items'] ?? []) === ['icon', 'title', 'text'], 'trust repeater columns follow the registry', $failures, $checks);
    }
} catch (Throwable $exception) {
    $failures[] = 'render threw ' . get_class($exception) . ': ' . $exception->getMessage();
} finally {
    DB::rollBack();
}

if ($failures !== []) {
    fwrite(STDERR, "LANDING_TEMPLATE_LAYOUT_FAILED\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo 'LANDING_TEMPLATE_LAYOUT_OK (' . $checks . " checks)\n";
exit(0);
