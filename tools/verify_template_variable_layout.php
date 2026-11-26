<?php

/**
 * Release check for the Template Variables layout.
 *
 * The page must show each variable as plain label text plus one switch, with
 * every group switch and every variable switch sitting in the same right-hand
 * column. It must not repeat the variable key as small code text.
 *
 * Also pins the design-manager header: the nine design cards are nine, and the
 * stat cards above them count the nine designs instead of the six legacy
 * landing_pages rows.
 *
 * Usage: php tools/verify_template_variable_layout.php
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

// 1. The blade source is a one-column list of rows with a switch column.
if (!str_contains($view, 'admin-page-template-variables.css')) {
    $fail('template variables page does not load its page CSS');
}
if (!str_contains($view, 'template-variable-row')) {
    $fail('variable rows are not wrapped in template-variable-row');
}
if (!str_contains($view, 'template-variable-switch')) {
    $fail('switches are not wrapped in template-variable-switch');
}
if (str_contains($view, 'col-lg-6')) {
    $fail('the group grid still uses two columns (col-lg-6)');
}
if (str_contains($view, '{{ $key }}</code>') || str_contains($view, '<code class="ms-1">')) {
    $fail('the variable key is still repeated as small code text');
}

$cssFile = $base . '/public/admin/assets/css/admin-page-template-variables.css';
if (!is_file($cssFile)) {
    $fail('public/admin/assets/css/admin-page-template-variables.css is missing');
} else {
    $css = (string) file_get_contents($cssFile);
    if (!str_contains($css, '.template-variable-switch.form-check')) {
        $fail('page CSS has no .template-variable-switch.form-check rule');
    }
    if (!preg_match('~\.template-variable-switch\.form-check\s*\{[^}]*justify-content:\s*flex-end~s', $css)) {
        $fail('the switch column is not right-aligned (justify-content: flex-end)');
    }
    if (!preg_match('~\.template-variable-switch\.form-check\s*\{[^}]*flex:\s*0 0 3rem~s', $css)) {
        $fail('the switch column does not have the fixed 3rem width');
    }
}

// 2. Render the real admin page for design 157.
$template = Template::query()->where('view_name', 'ht2')->first();
$html = '';

if (!$template) {
    $fail('template ht2 (design 157) is missing');
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
    $rows = $xpath->query('//*[contains(@class, "template-variable-row")]');

    if ($groupToggles->length !== $expectedGroups) {
        $fail("rendered {$groupToggles->length} group switches, expected {$expectedGroups}");
    }
    if ($variableToggles->length !== $expectedVariables) {
        $fail("rendered {$variableToggles->length} variable switches, expected {$expectedVariables}");
    }
    if ($rows->length !== $expectedVariables) {
        $fail("rendered {$rows->length} variable rows, expected {$expectedVariables}");
    }

    // Both the group toggle and every variable toggle sit in the fixed
    // right-hand switch column.
    foreach ([['group', $groupToggles], ['variable', $variableToggles]] as [$kind, $toggles]) {
        foreach ($toggles as $input) {
            /** @var DOMElement $input */
            $wrapper = $input->parentNode;
            $wrapperClass = $wrapper instanceof DOMElement ? (string) $wrapper->getAttribute('class') : '';

            if (!str_contains($wrapperClass, 'template-variable-switch')) {
                $fail("a {$kind} switch is outside the shared right-hand switch column");
            }
            if (!str_contains($wrapperClass, 'form-check') || !str_contains($wrapperClass, 'form-switch') || !str_contains($wrapperClass, 'custom-switch-sharp')) {
                $fail("a {$kind} switch is not a product-form switch");
            }
        }
    }

    // The visible row is the label only: no code key, no source/repeater badge.
    foreach ($rows as $row) {
        /** @var DOMElement $row */
        if ($xpath->query('.//code', $row)->length > 0) {
            $fail('a variable row still renders the key as <code>');
        }
        if ($xpath->query('.//*[contains(@class, "badge")]', $row)->length > 0) {
            $fail('a variable row still renders a small source/repeater badge');
        }
    }

    $paymentInput = null;
    foreach ($variableToggles as $input) {
        if ($input instanceof DOMElement && $input->getAttribute('value') === 'payment_methods') {
            $paymentInput = $input;
            break;
        }
    }

    if (!$paymentInput) {
        $fail('the payment_methods variable row is missing');
    } else {
        $row = $paymentInput->parentNode?->parentNode;
        $labelNode = $row instanceof DOMElement
            ? $xpath->query('.//*[contains(@class, "template-variable-label")]', $row)->item(0)
            : null;
        $text = $labelNode instanceof DOMElement ? trim((string) preg_replace('~\s+~', ' ', $labelNode->textContent)) : '';
        if ($text !== 'Payment methods') {
            $fail("payment_methods visible label is '{$text}', expected only 'Payment methods'");
        }
    }

    if (!str_contains($html, 'js-group-toggle') || !str_contains($html, 'js-variable')) {
        $fail('the rendered page lost the group-sync hooks');
    }

    // 3. The design manager: nine cards, nine counted designs.
    $manageResponse = $kernel->handle(Request::create('/admin/landing-pages/manage', 'GET'));
    $manageHtml = (string) $manageResponse->getContent();
    $manageDoc = new DOMDocument();
    libxml_use_internal_errors(true);
    $manageDoc->loadHTML('<?xml encoding="utf-8" ?>' . $manageHtml);
    libxml_clear_errors();
    $manageXpath = new DOMXPath($manageDoc);

    $cards = $manageXpath->query('//*[@data-design-card]');
    if ($cards->length !== 9) {
        $fail("design manager rendered {$cards->length} cards, expected 9");
    }

    $stat = static function (DOMXPath $xpath, string $key): ?string {
        $node = $xpath->query('//*[@data-stat="' . $key . '"]')->item(0);

        return $node ? trim((string) preg_replace('~[^0-9]~', '', $node->textContent)) : null;
    };

    $total = $stat($manageXpath, 'total-design-templates');
    $active = $stat($manageXpath, 'active-design-templates');
    $inactive = $stat($manageXpath, 'inactive-design-templates');

    if ($total !== '9') {
        $fail('manager stat "Total Design Templates" is ' . var_export($total, true) . ', expected 9');
    }
    if ($active !== '9') {
        $fail('manager stat "Active Design Templates" is ' . var_export($active, true) . ', expected 9');
    }
    if ($inactive !== '0') {
        $fail('manager stat "Inactive Design Templates" is ' . var_export($inactive, true) . ', expected 0');
    }
}

if ($errors) {
    foreach (array_unique($errors) as $error) {
        fwrite(STDERR, 'FAIL ' . $error . PHP_EOL);
    }
    exit(1);
}

echo 'LAYOUT groups=' . $expectedGroups . ' variables=' . $expectedVariables
    . ' single_column=1 switch_column=right designs=9' . PHP_EOL;
echo 'TEMPLATE_VARIABLES_LAYOUT_OK' . PHP_EOL;
exit(0);
