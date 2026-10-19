<?php
/**
 * Equivalence proof for the admin button conversion.
 *
 * For every distinct class string the admin used before the rewrite, render
 * the <x-admin.button> call that replaced it and compare the two class LISTS
 * as sets. If the sets match, the button a shopkeeper sees is pixel-identical
 * - the conversion changed the call site, not the screen.
 *
 *   php tools/verify_admin_buttons.php
 */
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$snapshot = json_decode(file_get_contents($argv[1] ?? '/tmp/opencode/admin_combos_before.json'), true);

$variantOf = [
    'primary' => 'btn-primary', 'secondary' => 'btn-secondary',
    'outline-primary' => 'btn-outline-primary', 'outline-secondary' => 'btn-outline-secondary',
    'outline-success' => 'btn-outline-success', 'outline-danger' => 'btn-outline-danger',
    'success' => 'btn-success', 'danger' => 'btn-danger', 'warning' => 'btn-warning',
    'info' => 'btn-info', 'light' => 'btn-light', 'dark' => 'btn-dark', 'link' => 'btn-link',
    'text-primary' => 'btn-text-primary', 'text-secondary' => 'btn-text-secondary',
    'text-danger' => 'btn-text-danger', 'text-warning' => 'btn-text-warning',
    'text-success' => 'btn-text-success',
];
$variantRe = '/^btn-(outline-|text-)?(primary|secondary|success|danger|warning|info|light|dark|link)$/';

$ok = $mismatch = $skipped = 0;
$problems = [];

foreach ($snapshot as $classString => $count) {
    $classes = preg_split('/\s+/', trim($classString));
    $variant = ''; $size = 'md'; $shapes = []; $ripple = false; $extras = [];
    $recognised = false;

    foreach ($classes as $token) {
        if ($token === 'btn') { $recognised = true; continue; }
        if ($token === 'btn-sm') { $size = 'sm'; continue; }
        if ($token === 'btn-lg') { $size = 'lg'; continue; }
        if ($token === 'btn-square') { $shapes[] = 'square'; $recognised = true; continue; }
        if ($token === 'btn-icon') { $shapes[] = 'icon'; $recognised = true; continue; }
        if ($token === 'waves-effect') { $ripple = true; $recognised = true; continue; }
        if (preg_match($variantRe, $token)) {
            $variant = preg_replace('/^btn-/', '', $token);
            $recognised = true;
            continue;
        }
        $extras[] = $token;
    }

    // The converter only rewrites a button when it found a variant, a size, a
    // shape or the ripple. A bare "btn p-0" has none of those, so it is left
    // alone - and this file must leave it alone too, or it invents mismatches.
    //
    // A ripple on its own does not count as a button. Layout toggles in this
    // template carry "waves-effect waves-light" on a class of their own
    // (button-menu-mobile); converting one would bolt .btn onto a widget that
    // never had button padding, so the converter skips it and so does this.
    $hasMeaning = $variant !== '' || $size !== 'md' || $shapes;
    if (! $recognised || ! $hasMeaning || in_array('btn-close', $classes, true)) {
        $skipped++;
        continue;
    }

    // Mirror the converter exactly: shape can carry two words, and the ripple
    // is opt-in. An older version of this file passed neither, so it reported
    // 68 false mismatches against a perfectly correct conversion.
    $attrs = $variant !== '' ? 'variant="' . $variant . '"' : 'variant=""';
    if ($size !== 'md') { $attrs .= ' size="' . $size . '"'; }
    if ($shapes) { $attrs .= ' shape="' . implode(' ', $shapes) . '"'; }
    if ($ripple) { $attrs .= ' ripple="true"'; }
    if ($extras) { $attrs .= ' class="' . implode(' ', $extras) . '"'; }

    $html = Blade::render('<x-admin.button ' . $attrs . '>X</x-admin.button>');
    preg_match('/class="([^"]*)"/', $html, $m);
    $after = preg_split('/\s+/', trim($m[1] ?? ''));

    sort($classes);
    sort($after);
    if ($classes === $after) {
        $ok++;
    } else {
        $mismatch++;
        $problems[] = sprintf(
            "  %dx  %s\n       before: %s\n       after : %s",
            $count, $classString, implode(' ', $classes), implode(' ', $after)
        );
    }
}

printf("distinct class strings : %d\n", count($snapshot));
printf("identical after rewrite: %d\n", $ok);
printf("skipped (not converted): %d\n", $skipped);
printf("MISMATCHED             : %d\n", $mismatch);
if ($problems) {
    echo "\n" . implode("\n", $problems) . "\n";
}
exit($mismatch ? 1 : 0);