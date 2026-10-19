<?php

/**
 * sync_design_images.php
 * ----------------------
 * Binds the placeholder images in a static design mock-up to the real product
 * images stored in the database.
 *
 * WHY
 * work/code.html is the catalog design reference: its 12 cards already carry
 * the exact product names that exist in the `products` table, but the <img src>
 * values are Google CDN placeholders that will never work in production. This
 * script replaces each placeholder with the real image served from
 * public/uploads/product/, matched by product name, so the mock-up shows the
 * data the storefront actually ships.
 *
 * MATCHING
 * The alt text of each card is matched against the product name with a
 * normalised comparison (lower-cased, punctuation stripped). A hand written
 * alias table covers the few cards whose label was shortened for layout
 * reasons ("Chicken (Skinless)" -> "Skinless Chicken").
 *
 * NOTHING is written unless the product is found and its image file actually
 * exists on disk, so a renamed product can never silently blank a card.
 *
 * USAGE
 *   php tools/sync_design_images.php                       # dry run, prints a report
 *   php tools/sync_design_images.php --write              # apply
 *   php tools/sync_design_images.php work/other.html --write
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\MediaPath;

/* ------------------------------------------------------------------ */
/* arguments                                                          */
/* ------------------------------------------------------------------ */
$args = array_slice($argv, 1);
$write = in_array('--write', $args, true);
$target = null;
foreach ($args as $a) {
    if (str_starts_with($a, '--')) {
        continue;
    }
    $target = $a;
}
$target = $target ?: 'work/code.html';
$targetPath = str_starts_with($target, '/') ? $target : $root . '/' . $target;

if (! is_file($targetPath)) {
    fwrite(STDERR, "Target not found: {$targetPath}\n");
    exit(1);
}

/* ------------------------------------------------------------------ */
/* load products                                                      */
/* ------------------------------------------------------------------ */
$products = App\Models\Product::query()
    ->with(['image', 'images'])
    ->get()
    ->map(function ($p) {
        $raw = $p->image?->image ?? null;
        if (blank($raw)) {
            $raw = $p->images?->first()?->image ?? null;
        }
        if (blank($raw) && isset($p->thumbnail_image)) {
            $raw = $p->thumbnail_image;
        }

        // MediaPath::url() prefixes asset() and returns http://localhost:8000/...,
        // which is useless inside a static mock-up opened from the file system.
        // normalize() gives the web-root relative form instead.
        return [
            'id'    => $p->id,
            'name'  => $p->name,
            'image' => MediaPath::normalize($raw, 'product'),
        ];
    })
    ->filter(fn ($r) => filled($r['image']))
    ->values();

if ($products->isEmpty()) {
    fwrite(STDERR, "No products with a usable image were found in the database.\n");
    exit(1);
}

/* normalise for comparison: lower case, keep only letters and digits */
$norm = function (?string $s): string {
    return preg_replace('/[^a-z0-9]+/i', '', strtolower((string) $s));
};

/* Card labels that were shortened for layout; maps label -> product name. */
$aliases = [
    'chickenskinless' => 'Skinless Chicken',
    'chicken'         => 'Skinless Chicken',
    'artisanbread'    => 'Artisan Sourdough Bread',
    'bread'           => 'Artisan Sourdough Bread',
    'milk'            => 'Pure Organic Whole Milk',
    'avocado'         => 'Organic Hass Avocados',
];

$index = [];
foreach ($products as $p) {
    $index[$norm($p['name'])] = $p;
}

/* ------------------------------------------------------------------ */
/* rewrite                                                            */
/* ------------------------------------------------------------------ */
$html = file_get_contents($targetPath);

$report = [];
$replacements = 0;

$html = preg_replace_callback(
    '/(<article\b[^>]*\bclass="[^"]*fm-product-card[^"]*"[^>]*>)(.*?)(<\/article>)/is',
    function ($m) use (&$report, &$replacements, $index, $aliases, $norm, $root) {
        [$all, $open, $inner] = $m;

        if (! preg_match('/\balt="([^"]*)"/i', $inner, $am)) {
            return $all;
        }
        $alt = html_entity_decode($am[1], ENT_QUOTES, 'UTF-8');

        if (! preg_match('/(<img\b[^>]*\bsrc=")([^"]*)(")/i', $inner, $sm)) {
            return $all;
        }
        $src = $sm[2];

        // Already pointing at a real local upload: nothing to do.
        if (! preg_match('#^https?://(?!127\.0\.0\.1|localhost)#i', $src)
            && ! str_contains($src, 'googleusercontent')) {
            $report[] = ['alt' => $alt, 'status' => 'skip', 'src' => $src];
            return $all;
        }

        $key = $norm($alt);
        $hit = $index[$key] ?? null;
        if (! $hit && isset($aliases[$key])) {
            $hit = $index[$norm($aliases[$key])] ?? null;
        }

        if (! $hit) {
            $report[] = ['alt' => $alt, 'status' => 'NO MATCH', 'src' => $src];
            return $all;
        }

        // Refuse to point at a file that is not actually on disk.
        $disk = $root . '/public/' . ltrim($hit['image'], '/');
        if (! is_file($disk)) {
            $report[] = ['alt' => $alt, 'status' => 'IMAGE FILE MISSING', 'image' => $hit['image']];
            return $all;
        }

        $replacements++;
        $report[] = [
            'alt'     => $alt,
            'status'  => 'matched',
            'product' => $hit['name'],
            'old'     => substr($src, 0, 48) . '...',
            'new'     => $hit['image'],
        ];

        $newInner = substr_replace($inner, $hit['image'], strpos($inner, $sm[2]), strlen($sm[2]));

        return $open . $newInner . $m[3];
    },
    $html
);

/* ------------------------------------------------------------------ */
/* report                                                             */
/* ------------------------------------------------------------------ */
$pad = fn ($s, $n) => str_pad((string) $s, $n);

echo str_repeat('=', 74), "\n";
echo 'Design image sync  ->  ', str_replace($root . '/', '', $targetPath), "\n";
echo str_repeat('=', 74), "\n";
printf("%-28s %-16s %s\n", 'CARD ALT', 'STATUS', 'IMAGE');
echo str_repeat('-', 74), "\n";

foreach ($report as $r) {
    printf("%-28s %-16s %s\n",
        $pad(mb_strimwidth($r['alt'], 0, 27, '…'), 28),
        $r['status'],
        $r['new'] ?? '-');
}

echo str_repeat('-', 74), "\n";
$matched  = count(array_filter($report, fn ($r) => $r['status'] === 'matched'));
$skipped  = count(array_filter($report, fn ($r) => $r['status'] === 'skip'));
$unresolv = count($report) - $matched - $skipped;
printf("cards=%d  matched=%d  already-real=%d  unresolved=%d\n",
    count($report), $matched, $skipped, $unresolv);

if (! $write) {
    echo "\nDRY RUN - nothing written. Re-run with --write to apply.\n";
    exit($unresolv > 0 ? 2 : 0);
}

if ($replacements === 0) {
    echo "\nNothing to write.\n";
    exit(0);
}

$backup = $targetPath . '.bak-' . date('Ymd-His');
copy($targetPath, $backup);
file_put_contents($targetPath, $html);

echo "\nAPPLIED: {$replacements} image(s) replaced.\n";
echo "Backup:  " . str_replace($root . '/', '', $backup) . "\n";