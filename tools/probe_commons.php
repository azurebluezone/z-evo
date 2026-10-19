<?php

/**
 * probe_commons.php
 * -----------------
 * Dry run: ask Wikimedia Commons for a real photograph for every product and
 * report what would be used, WITHOUT downloading or writing anything.
 *
 * The products currently ship generated vector illustrations. This probe shows
 * whether a genuine, freely licensed photograph exists for each one before a
 * single file is replaced, so nothing is deleted on the strength of a guess.
 *
 * The lookup rules are copied from the proven Edule pipeline at
 * /home/sahon/Desktop/zevlms/blog/scripts/generate_covers.py: landscape only,
 * minimum size, JPEG/PNG only, illustrations rejected, and the licence and
 * artist are always captured for attribution.
 *
 * USAGE
 *   php tools/probe_commons.php
 */

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const COMMONS_API = 'https://commons.wikimedia.org/w/api.php';
const USER_AGENT = 'ArafatEcommerce/1.0 (product photography research; contact: admin@example.com)';

/** Words that mark a file as a drawing, map or diagram rather than a photograph. */
function isIllustration(string $title): bool
{
    $t = strtolower($title);
    foreach (['logo', 'coat of arms', 'map', 'diagram', 'chart', 'graph', 'icon',
              'flag', 'seal', 'poster', 'drawing', 'painting', 'illustration',
              'schematic', 'svg', 'signature', 'stamp', 'coat', 'plaque'] as $w) {
        if (str_contains($t, $w)) {
            return true;
        }
    }
    return false;
}

function httpGet(string $url, int $timeout = 30): string
{
    $ctx = stream_context_create(['http' => [
        'timeout' => $timeout,
        'header' => "User-Agent: " . USER_AGENT . "\r\n",
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new RuntimeException("HTTP GET failed: $url");
    }
    return $raw;
}

function stripHtml(?string $s): string
{
    return trim(html_entity_decode(strip_tags((string) $s), ENT_QUOTES, 'UTF-8'));
}

/** Search Commons and return the best acceptable photograph candidate. */
function searchCommons(array $queries): ?array
{
    foreach ($queries as $query) {
        $hit = searchCommonsOnce($query);
        if ($hit !== null) {
            $hit['query'] = $query;
            return $hit;
        }
    }
    return null;
}

function searchCommonsOnce(string $query): ?array
{
    $params = [
        'action' => 'query',
        'generator' => 'search',
        'gsrsearch' => $query . ' filetype:bitmap',
        'gsrlimit' => '14',
        'gsrnamespace' => '6',
        'prop' => 'imageinfo',
        'iiprop' => 'url|size|mime|extmetadata',
        'iiurlwidth' => '1600',
        'format' => 'json',
    ];
    $payload = json_decode(httpGet(COMMONS_API . '?' . http_build_query($params)), true);
    $pages = $payload['query']['pages'] ?? [];

    $best = null;
    foreach ($pages as $page) {
        $info = $page['imageinfo'][0] ?? null;
        if (!$info) {
            continue;
        }
        $w = (int)($info['width'] ?? 0);
        $h = (int)($info['height'] ?? 0);
        if (!in_array($info['mime'] ?? '', ['image/jpeg', 'image/png'], true)) {
            continue;
        }
        if ($w < 900 || $h < 600) {
            continue;
        }
        if ($w / max($h, 1) < 1.2) {           // want landscape
            continue;
        }
        if (isIllustration($page['title'] ?? '')) {
            continue;
        }
        if (empty($info['thumburl'])) {
            continue;
        }
        $meta = $info['extmetadata'] ?? [];
        $cand = [
            'index'   => $page['index'] ?? 99,
            'title'   => $page['title'] ?? '',
            'thumb'   => $info['thumburl'],
            'page'    => $info['descriptionurl'] ?? '',
            'artist'  => stripHtml($meta['Artist']['value'] ?? ''),
            'license' => stripHtml($meta['LicenseShortName']['value'] ?? ''),
            'width'   => $w,
            'height'  => $h,
        ];
        if ($best === null || $cand['index'] < $best['index']) {
            $best = $cand;
        }
    }
    return $best;
}

/* ------------------------------------------------------------------ */

$products = App\Models\Product::orderBy('id')->get();

echo str_repeat('=', 92), "\n";
echo "Wikimedia Commons photo probe  (dry run, nothing is downloaded)\n";
echo str_repeat('=', 92), "\n";
printf("%-4s %-26s %-9s %-22s %s\n", '#', 'PRODUCT', 'FOUND', 'LICENSE', 'COMMONS FILE');
echo str_repeat('-', 92), "\n";

$found = 0;
$missing = [];

foreach ($products as $p) {
    /* Several phrasings per product. Commons titles rarely match a shop name
       verbatim ("Pure Organic Whole Milk" finds nothing, "milk bottle" does),
       so a single fixed query would lose products that do have a photo. */
    $queries = [trim($p->name) . ' food'];
    $short = preg_replace('/\b(organic|fresh|farm|artisan|raw|desi|cold pressed|skinless)\b/i', '', $p->name);
    $short = trim(preg_replace('/\s+/', ' ', (string) $short));
    if ($short !== '' && strcasecmp($short, trim($p->name)) !== 0) {
        $queries[] = $short;
        $queries[] = $short . ' fresh';
    }

    $hit = null;
    try {
        $hit = searchCommons($queries);
    } catch (Throwable $e) {
        printf("%-4s %-26s %-9s %s\n", $p->id, mb_strimwidth($p->name, 0, 25, '…'), 'ERROR', mb_strimwidth($e->getMessage(), 0, 40, '…'));
        $missing[] = $p->name;
        continue;
    }

    if (!$hit) {
        printf("%-4s %-26s %-9s\n", $p->id, mb_strimwidth($p->name, 0, 25, '…'), 'no');
        $missing[] = $p->name;
        continue;
    }

    $found++;
    printf("%-4s %-26s %-9s %-22s %s\n",
        $p->id,
        mb_strimwidth($p->name, 0, 25, '…'),
        'yes',
        mb_strimwidth($hit['license'] ?: 'unspecified', 0, 21, '…'),
        mb_strimwidth(str_replace('File:', '', $hit['title']), 0, 28, '…'));
}

echo str_repeat('-', 92), "\n";
printf("products=%d  photo found=%d  no photo=%d\n", $products->count(), $found, count($missing));

if ($missing) {
    echo "\nNo Commons photo for:\n";
    foreach ($missing as $m) {
        echo "  - $m\n";
    }
}
echo "\nThis was a dry run. Nothing was downloaded, deleted or changed.\n";