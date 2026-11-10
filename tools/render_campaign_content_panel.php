<?php

/**
 * Render the admin campaign content partial into a standalone QA page.
 *
 * Used by tools/campaign-content-panel-check.mjs so the template filtering and
 * add/remove-row JS can be exercised in a real browser without an admin login.
 * The generated file is removed by the caller.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$templates = App\Models\Template::active()->get();

// The partial uses @error; a standalone render has no session middleware to
// share the view error bag, so provide an empty one.
view()->share('errors', new Illuminate\Support\ViewErrorBag());

$partial = view('backEnd.campaign.partials.content-fields', [
    'edit_data' => null,
    'templates' => $templates,
])->render();

$options = '';
foreach ($templates as $template) {
    $options .= '<option value="' . e($template->id) . '">' . e($template->name) . '</option>';
}

$templateMap = json_encode(
    $templates->mapWithKeys(fn ($template) => [$template->view_name => (string) $template->id])
);

$html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
    . '<meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">'
    . '</head><body class="p-3">'
    . '<label class="form-label" for="qa-template">Template</label>'
    . '<select class="form-select mb-3" name="template_id" id="qa-template">' . $options . '</select>'
    . $partial
    . '<script>window.__QA_TEMPLATES__ = ' . $templateMap . ';</script>'
    . '<script src="/admin/assets/js/admin-campaign-content.js"></script>'
    . '</body></html>';

$target = $argv[1] ?? (__DIR__ . '/../public/qa-campaign-content-panel.html');
file_put_contents($target, $html);

echo $target . PHP_EOL;
