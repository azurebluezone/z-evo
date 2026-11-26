<?php

/**
 * Release check for the campaign create/edit forms.
 *
 * Verifies the admin contract without a browser: no helper text, one banner
 * title/status input, consistent column widths, Remix icons on row controls,
 * Quill on long text, real upload controls for repeater images/avatars, the
 * controller merge path for those uploads, and the authentic ht2 hero image
 * with its provenance sidecar.
 *
 * Usage: php tools/verify_campaign_form.php
 */

$root = dirname(__DIR__);
$failures = [];

$check = function ($condition, $message) use (&$failures) {
    if (!$condition) {
        $failures[] = $message;
    }
};

$read = function ($relative) use ($root, &$failures) {
    $path = $root . '/' . $relative;
    if (!is_file($path)) {
        $failures[] = 'missing file: ' . $relative;

        return '';
    }

    return (string) file_get_contents($path);
};

$create = $read('resources/views/backEnd/campaign/create.blade.php');
$edit = $read('resources/views/backEnd/campaign/edit.blade.php');
$partial = $read('resources/views/backEnd/campaign/partials/content-fields.blade.php');
$config = $read('config/landing_templates.php');
$controller = $read('app/Http/Controllers/Admin/CampaignController.php');
$contentJs = $read('public/admin/assets/js/admin-campaign-content.js');
$sharedJs = $read('public/admin/assets/js/admin-app-shared.js');

// 1. Helper/description text is gone from both forms and the content panel.
$helperTexts = [
    'campaign-product-summary',
    'Opens the selected design in a new tab.',
    'Off means the landing page uses the product price.',
    'Fixed price for a simple product.',
    'Percent wins over the fixed price',
    'Default off. If enabled, coupons calculate on the campaign price.',
    'This template does not use extra content fields.',
];

foreach (['create' => $create, 'edit' => $edit, 'partial' => $partial] as $label => $content) {
    foreach ($helperTexts as $needle) {
        $check(strpos($content, $needle) === false, $label . ' still contains helper text: ' . $needle);
    }
}

// 2. Duplicate controls are removed: one banner title and one status per form.
foreach (['create' => $create, 'edit' => $edit] as $label => $content) {
    $check(substr_count($content, 'name="banner_title"') === 1, $label . ' must have exactly one banner_title input');
    $check(substr_count($content, 'name="status"') === 1, $label . ' must have exactly one status input');
    $check(substr_count($content, 'id="banner_title"') === 1, $label . ' must have exactly one banner_title id');
    $check(substr_count($content, 'id="status"') === 1, $label . ' must have exactly one status id');
}

// 3. Layout is consistent: no squeezed 2/3-column fields; dates share col-sm-4.
foreach (['create' => $create, 'edit' => $edit] as $label => $content) {
    $check(!preg_match('/col-sm-[23](\s|")/', $content), $label . ' still uses col-sm-2/col-sm-3 fields');
    $check(substr_count($content, 'col-sm-4 mb-3') >= 4, $label . ' needs a consistent col-sm-4 row');
    $check(substr_count($content, 'col-sm-6') >= 4, $label . ' needs paired col-sm-6 fields');
}

// 4. Remix icons on the row controls.
$check(preg_match('/js-add-row[^>]*>\s*<i class="ri ri-add-line/', $partial) === 1, 'Add row button is missing the ri-add-line icon');
$check(substr_count($partial, 'js-remove-row') >= 2, 'Remove row buttons are missing');
$check(preg_match_all('/js-remove-row[^>]*>\s*<i class="ri ri-delete-bin-line/', $partial) >= 2, 'Remove row button is missing the ri-delete-bin-line icon');

// 5. Quill on Hero Subtitle and repeater textareas.
foreach (['create' => $create, 'edit' => $edit] as $label => $content) {
    $check(substr_count($content, 'js-quill-editor') >= 3, $label . ' needs Quill on short description, description and hero subtitle');
    $check(strpos($content, 'data-quill-target="#hero_subtitle"') !== false, $label . ' hero subtitle is not a Quill editor');
    $check(strpos($content, 'id="hero_subtitle"') !== false, $label . ' hero subtitle hidden input is missing');
}

$check(strpos($partial, 'class="js-quill-editor"') !== false, 'repeater textareas do not use Quill');
$check(strpos($partial, 'data-quill-target="#extra_') !== false, 'repeater Quill editors have no target input');
$check(strpos($partial, "{{ '__INDEX__' }}") !== false, 'repeater row template lost its __INDEX__ placeholder');

// 5b. Repeater editors are compact: the vendor 15rem Quill body is overridden.
$formCss = $read('public/admin/assets/css/admin-campaign-form.css');
$check(strpos($formCss, '#campaignForm .ql-snow .ql-editor') !== false, 'campaign form CSS does not override the vendor Quill min-height');
foreach (['create' => $create, 'edit' => $edit] as $label => $content) {
    $check(strpos($content, 'admin-campaign-form.css') !== false, $label . ' does not load the campaign form CSS');
}

// 6. Repeater image/avatar columns are uploads with previews.
$check(strpos($partial, 'name="extra_fields_files[') !== false, 'repeater upload inputs are missing');
$check(strpos($partial, 'js-repeater-image') !== false, 'repeater upload class is missing');
$check(strpos($partial, 'js-repeater-image-preview') !== false, 'repeater upload preview is missing');
$check(strpos($partial, 'Image URL') === false, 'partial still shows the Image URL label');
$check(strpos($partial, 'Avatar URL') === false, 'partial still shows the Avatar URL label');
$check(strpos($config, 'Avatar URL') === false, 'config still labels the avatar as a URL');
$check(strpos($config, "'avatar' => ['label' => 'Avatar', 'type' => 'image'") !== false, 'config avatar column is not an image type');
$check(strpos($config, "'image' => ['label' => 'Image', 'type' => 'image'") !== false, 'config variant image column is not an image type');

// 7. Controller merges uploaded repeater files before the whitelist runs.
$check(strpos($controller, "'extra_fields_files' => 'nullable|array'") !== false, 'controller does not validate extra_fields_files');
$check(strpos($controller, "'extra_fields_files.*.*.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096'") !== false, 'controller upload rule is missing');
$check(substr_count($controller, '$this->withRepeaterUploads($request') === 2, 'store and update must both merge repeater uploads');
$check(preg_match('/normalize\(\s*\$this->withRepeaterUploads\(\$request/', $controller) === 1, 'uploads are not merged before normalize');
$check(substr_count($controller, 'private function withRepeaterUploads') === 1, 'withRepeaterUploads helper is missing');
$check(substr_count($controller, 'private function storeRepeaterImage') === 1, 'storeRepeaterImage helper is missing');
$check(strpos($controller, 'extra_fields_files') !== false, 'controller never reads extra_fields_files');
$check(substr_count($controller, "'extra_fields_files'") >= 2, 'extra_fields_files is not excluded from mass assignment in both actions');

// 8. Dynamic rows initialise Quill and preview the chosen file.
$check(strpos($sharedJs, 'window.AdminQuill') !== false, 'admin-app-shared does not expose the Quill initializer');
$check(strpos($contentJs, 'window.AdminQuill.initAll()') !== false, 'new repeater rows never initialise Quill');
$check(strpos($contentJs, 'URL.createObjectURL') !== false, 'repeater image preview never reads the chosen file');

// 9. Both forms share the same structure and include the content panel.
foreach (['create' => $create, 'edit' => $edit] as $label => $content) {
    $check(strpos($content, "backEnd.campaign.partials.content-fields") !== false, $label . ' does not include the content panel');
    $check(strpos($content, 'id="campaignForm"') !== false, $label . ' form id changed');
    $check(strpos($content, 'enctype="multipart/form-data"') !== false, $label . ' form cannot upload files');

    foreach (['sort_order', 'start_date', 'end_date'] as $id) {
        $check(strpos($content, 'for="' . $id . '"') !== false, $label . ' label for ' . $id . ' is missing');
        $check(strpos($content, 'id="' . $id . '"') !== false, $label . ' input id ' . $id . ' is missing');
    }
}

// 10. ht2 hero is an authentic ZEvo image, not the old synthetic placeholder.
$heroRelative = 'public/frontend/landing/pictures/generated/ht2-hero.jpg';
$heroPath = $root . '/' . $heroRelative;
$sidecarPath = $root . '/public/frontend/landing/pictures/generated/ht2-hero.source.json';
$sourceRelative = 'work/zevo/1788849471061.png';
$sourcePath = $root . '/' . $sourceRelative;
$oldPlaceholder = 'c298c5eccd4fc9ddba0ee0014478f8a28c6e66d5d6a865a50e3552be82243411';

$check(is_file($heroPath), 'ht2 hero image is missing');
$check(is_file($sourcePath), 'authentic ZEvo source image is missing');

if (is_file($heroPath)) {
    $info = @getimagesize($heroPath);
    $check($info !== false, 'ht2 hero is not a readable image');
    $check(($info['mime'] ?? '') === 'image/jpeg', 'ht2 hero is not a JPEG');
    $check(($info[0] ?? 0) === 1600 && ($info[1] ?? 0) === 900, 'ht2 hero must be 1600x900');
    $check(filesize($heroPath) > 50000, 'ht2 hero looks too small to be the real artwork');
    $check(hash_file('sha256', $heroPath) !== $oldPlaceholder, 'ht2 hero is still the old synthetic placeholder');
}

$check(is_file($sidecarPath), 'ht2 hero provenance sidecar is missing');

if (is_file($sidecarPath)) {
    $sidecar = json_decode((string) file_get_contents($sidecarPath), true);
    $check(is_array($sidecar), 'ht2 hero sidecar is not valid JSON');

    if (is_array($sidecar)) {
        $check(($sidecar['source'] ?? '') === $sourceRelative, 'sidecar source path does not point at work/zevo');
        $check(($sidecar['source_sha256'] ?? '') === ($sourcePath && is_file($sourcePath) ? hash_file('sha256', $sourcePath) : ''), 'sidecar source hash does not match work/zevo image');
        $check(($sidecar['output_sha256'] ?? '') === ($heroPath && is_file($heroPath) ? hash_file('sha256', $heroPath) : ''), 'sidecar output hash does not match ht2 hero');
        $check(($sidecar['replaces_sha256'] ?? '') === $oldPlaceholder, 'sidecar does not record the replaced placeholder');
    }
}

if ($failures !== []) {
    echo "CAMPAIGN_FORM_FAIL\n";
    foreach ($failures as $failure) {
        echo ' - ' . $failure . "\n";
    }
    exit(1);
}

echo 'CAMPAIGN_FORM_OK forms=2 repeaters=5 quills=3+ hero=1600x900 source=' . $sourceRelative . "\n";
exit(0);
