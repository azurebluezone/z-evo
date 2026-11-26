<?php

/**
 * Release check for the Template Variables page controls.
 *
 * The page must use the same switch design as the product create form:
 *   <label class="form-label d-block fw-semibold">Section</label>
 *   <div class="form-check form-switch custom-switch-sharp">
 *       <input type="checkbox" class="form-check-input rounded-0">
 *       <label class="form-check-label">Enable</label>
 *   </div>
 *
 * It renders the real admin page for template 157 and checks every group
 * toggle and every variable toggle is inside that switch wrapper, while the
 * existing js-group-toggle / js-variable sync script is untouched.
 *
 * Usage: php tools/verify_template_variable_switches.php
 */

require __DIR__ . '/../vendor/autoload.php';

$base = dirname(__DIR__);
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$errors = [];
$fail = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

$viewFile = $base . '/resources/views/backEnd/landing-pages/template-variables.blade.php';
$view = (string) file_get_contents($viewFile);

// 1. The blade source uses the product-form label and switch classes.
if (!str_contains($view, 'class="form-label d-block fw-semibold mb-2"')) {
    $fail('group heading is not form-label d-block fw-semibold');
}
if (substr_count($view, 'form-check form-switch custom-switch-sharp') < 2) {
    $fail('the switch wrapper is missing from the group or variable rows');
}
if (str_contains($view, '<label class="mb-0 fw-bold" for="group_')) {
    $fail('the old plain group checkbox label is still present');
}
if (!str_contains($view, 'js-group-toggle') || !str_contains($view, 'js-variable')) {
    $fail('the existing JS sync hooks were removed');
}

// 2. Render the real page and inspect the DOM.
$template = Template::query()->where('view_name', 'ht2')->first();

if (!$template) {
    $fail('template ht2 (id 157) is missing');
    $html = '';
} else {
    Auth::loginUsingId(1);
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        Request::create('/admin/landing-pages/' . $template->id . '/template-variables', 'GET')
    );
    $html = (string) $response->getContent();

    if ($response->getStatusCode() !== 200) {
        $fail('template variables page returned HTTP ' . $response->getStatusCode());
    }
}

$groups = (array) config('landing_templates.groups', []);
$expectedGroups = count($groups);
$expectedVariables = 0;
foreach ($groups as $group) {
    $expectedVariables += count((array) ($group['variables'] ?? []));
}

if ($html !== '') {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $groupToggles = $xpath->query('//input[contains(@class, "js-group-toggle")]');
    $variableToggles = $xpath->query('//input[contains(@class, "js-variable")]');
    $headings = $xpath->query('//label[contains(@class, "form-label") and contains(@class, "d-block") and contains(@class, "fw-semibold")]');
    $enableLabels = $xpath->query('//label[contains(@class, "form-check-label") and normalize-space(.) = "Enable"]');

    if ($groupToggles->length !== $expectedGroups) {
        $fail("rendered {$groupToggles->length} group switches, expected {$expectedGroups}");
    }
    if ($variableToggles->length !== $expectedVariables) {
        $fail("rendered {$variableToggles->length} variable switches, expected {$expectedVariables}");
    }
    if ($headings->length < $expectedGroups) {
        $fail("rendered {$headings->length} section headings, expected at least {$expectedGroups}");
    }
    if ($enableLabels->length < $expectedGroups) {
        $fail("rendered {$enableLabels->length} 'Enable' labels, expected at least {$expectedGroups}");
    }

    foreach ($groupToggles as $input) {
        /** @var DOMElement $input */
        $wrapper = $input->parentNode;
        $wrapperClass = $wrapper instanceof DOMElement ? (string) $wrapper->getAttribute('class') : '';
        $inputClass = (string) $input->getAttribute('class');

        if (!str_contains($wrapperClass, 'form-check') || !str_contains($wrapperClass, 'form-switch') || !str_contains($wrapperClass, 'custom-switch-sharp')) {
            $fail('a group toggle is not wrapped in form-check form-switch custom-switch-sharp');
        }
        if (!str_contains($inputClass, 'form-check-input') || !str_contains($inputClass, 'rounded-0')) {
            $fail('a group toggle input is missing form-check-input rounded-0');
        }
    }

    foreach ($variableToggles as $input) {
        /** @var DOMElement $input */
        $wrapper = $input->parentNode;
        $wrapperClass = $wrapper instanceof DOMElement ? (string) $wrapper->getAttribute('class') : '';
        $inputClass = (string) $input->getAttribute('class');

        if (!str_contains($wrapperClass, 'form-check') || !str_contains($wrapperClass, 'form-switch') || !str_contains($wrapperClass, 'custom-switch-sharp')) {
            $fail('a variable toggle is not wrapped in form-check form-switch custom-switch-sharp');
        }
        if (!str_contains($inputClass, 'form-check-input') || !str_contains($inputClass, 'rounded-0')) {
            $fail('a variable toggle input is missing form-check-input rounded-0');
        }
    }

    if (!str_contains($html, 'js-group-toggle') || !str_contains($html, 'js-variable')) {
        $fail('the rendered page lost the group-sync hooks');
    }
}

if ($errors) {
    foreach (array_unique($errors) as $error) {
        fwrite(STDERR, 'FAIL ' . $error . PHP_EOL);
    }
    exit(1);
}

echo 'SWITCHES groups=' . $expectedGroups . ' variables=' . $expectedVariables . PHP_EOL;
echo 'TEMPLATE_VARIABLES_SWITCHES_OK' . PHP_EOL;
exit(0);
