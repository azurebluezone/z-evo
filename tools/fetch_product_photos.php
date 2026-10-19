<?php

/**
 * fetch_product_photos.php
 * ------------------------
 * Replaces the generated vector illustrations in public/uploads/product with
 * real photographs from Wikimedia Commons, branded for this storefront.
 *
 * WHY COMMONS
 * Every image is fetched from Wikimedia Commons through its public API and the
 * licence, author and source page are written next to the file as a .json
 * sidecar. CC BY and CC BY-SA require attribution, so that record is not
 * optional; it is what makes the swap lawful rather than merely convenient.
 *
 * WHY A REVIEW FILE MATTERS
 * Commons has excellent photographs of avocados, apples and eggs, but a shop
 * name such as "Pure Organic Whole Milk" matches a photo of a supermarket shelf
 * just as readily as one of a milk pack. A first automated pass produced four
 * clearly wrong matches (milk-carton children, a shop interior, chicken with
 * rice, mustard seeds instead of oil). Those candidates are therefore written
 * to review/candidates.json and NOT downloaded until a human approves them.
 *
 * BRANDING
 * The storefront runs on the emerald palette already used across the fm theme
 * (#1b5e20 primary, #16a34a light). Each photo gets a subtle emerald brand
 * plate and a "Zevo" wordmark so a cropped or reposted file still carries the
 * shop's identity.
 *
 * USAGE
 *   php tools/fetch_product_photos.php --plan            # search + review file, no download
 *   php tools/fetch_product_photos.php --apply <slug>    # download one approved product
 *   php tools/fetch_product_photos.php --apply-all      # download every approved entry
 */

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const COMMONS_API = 'https://commons.wikimedia.org/w/api.php';
// Wikimedia asks every client to identify itself. Change the contact if this
// store ever goes public.
const USER_AGENT = 'ZevoEcommerce/1.0 (product photos; contact: admin@example.com)';

const PRODUCT_DIR = __DIR__ . '/../public/uploads/product';
const REVIEW_FILE = __DIR__ . '/../work/admin-resp-audit/photo-review.json';

/* Brand palette taken from the live fm theme, not invented here. */
const BRAND_DEEP  = [6, 46, 16];     /* #062e10 */
const BRAND_MAIN  = [27, 94, 32];    /* #1b5e20 */
const BRAND_LIGHT = [22, 163, 74];   /* #16a34a */
const BRAND_TEXT  = [255, 255, 255];

function stripHtml(?string $s): string
{
    return trim(html_entity_decode(strip_tags((string) $s), ENT_QUOTES, 'UTF-8'));
}

function isIllustration(string $title): bool
{
    $t = strtolower($title);
    foreach (['logo', 'coat of arms', 'map', 'diagram', 'chart', 'graph', 'icon',
              'flag', 'seal', 'poster', 'drawing', 'painting', 'illustration',
              'schematic', 'svg', 'signature', 'stamp', 'plaque'] as $w) {
        if (str_contains($t, $w)) {
            return true;
        }
    }
    return false;
}

function httpGet(string $url, int $timeout = 40): string
{
    $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "User-Agent: " . USER_AGENT . "\r\n"]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new RuntimeException('HTTP GET failed: ' . $url);
    }
    return $raw;
}

/** Search Commons and return the best acceptable photograph for one query. */
function searchCommonsOnce(string $query): ?array
{
    $params = [
        'action' => 'query', 'generator' => 'search',
        'gsrsearch' => $query . ' filetype:bitmap',
        'gsrlimit' => '14', 'gsrnamespace' => '6',
        'prop' => 'imageinfo', 'iiprop' => 'url|size|mime|extmetadata',
        'iiurlwidth' => '1600', 'format' => 'json',
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
        if (!in_array($info['mime'] ?? '', ['image/jpeg', 'image/png'], true)) continue;
        if ($w < 900 || $h < 600) continue;
        if ($w / max($h, 1) < 1.2) continue;
        if (isIllustration($page['title'] ?? '')) continue;
        if (empty($info['thumburl'])) continue;

        $meta = $info['extmetadata'] ?? [];
        $cand = [
            'title'   => $page['title'] ?? '',
            'thumb'   => $info['thumburl'],
            'page'    => $info['descriptionurl'] ?? '',
            'artist'  => stripHtml($meta['Artist']['value'] ?? ''),
            'license' => stripHtml($meta['LicenseShortName']['value'] ?? ''),
            'width'   => $w, 'height' => $h,
            'index'   => $page['index'] ?? 99,
        ];
        if ($best === null || $cand['index'] < $best['index']) {
            $best = $cand;
        }
    }
    return $best;
}

function searchCommons(array $queries): ?array
{
    foreach ($queries as $q) {
        $hit = searchCommonsOnce($q);
        if ($hit) {
            $hit['query'] = $q;
            return $hit;
        }
    }
    return null;
}

/**
 * Queries to try for a product.
 *
 * The reviewed per-product terms in tools/photo_queries.php win. The generic
 * fallback only runs for a product that is not listed there, so a new product
 * added later still gets a candidate instead of silently failing.
 */
function queriesFor(string $name): array
{
    $map = require __DIR__ . '/photo_queries.php';
    $key = trim($name);
    if (isset($map[$key])) {
        return $map[$key];
    }

    $queries = [trim($name) . ' food'];
    $short = trim(preg_replace('/\s+/', ' ', (string) preg_replace(
        '/\b(organic|fresh|farm|artisan|raw|desi|cold pressed|skinless)\b/i', '', $name
    )));
    if ($short !== '' && strcasecmp($short, trim($name)) !== 0) {
        $queries[] = $short;
        $queries[] = $short . ' fresh';
    }
    return $queries;
}

/* ------------------------------------------------------------------ */
/* Branding: centre-crop to square, then apply the emerald brand plate */
/* ------------------------------------------------------------------ */
function brandPhoto(string $sourcePath, string $targetPath, string $productName): void
{
    if (!function_exists('imagecreatetruecolor')) {
        throw new RuntimeException('The GD extension is not available in this PHP build.');
    }

    $src = @imagecreatefromstring(file_get_contents($sourcePath));
    if (!$src) {
        throw new RuntimeException('Could not decode the downloaded image.');
    }
    $sw = imagesx($src);
    $sh = imagesy($src);

    /* Centre-crop to a square so the storefront card never crops oddly. */
    $side = min($sw, $sh);
    $sx = intdiv($sw - $side, 2);
    $sy = intdiv($sh - $side, 2);

    $size = 1000;
    $out = imagecreatetruecolor($size, $size);
    imagecopyresampled($out, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);

    /* Bottom brand plate: a deep emerald gradient so the wordmark stays
       legible on any photograph, without hiding the product itself. */
    $plateTop = (int) ($size * 0.72);
    for ($y = $plateTop; $y < $size; $y++) {
        $t = ($y - $plateTop) / max(1, $size - $plateTop);
        $r = (int) (BRAND_MAIN[0] + (BRAND_DEEP[0] - BRAND_MAIN[0]) * $t);
        $g = (int) (BRAND_MAIN[1] + (BRAND_DEEP[1] - BRAND_MAIN[1]) * $t);
        $b = (int) (BRAND_MAIN[2] + (BRAND_DEEP[2] - BRAND_MAIN[2]) * $t);
        $color = imagecolorallocate($out, $r, $g, $b);
        imageline($out, 0, $y, $size, $y, $color);
    }

    /* Left accent bar, echoing the header's emerald underline. */
    $accent = imagecolorallocate($out, BRAND_LIGHT[0], BRAND_LIGHT[1], BRAND_LIGHT[2]);
    imagefilledrectangle($out, 0, $plateTop + 30, 10, $size, $accent);

    /* Wordmark: "ZEVO" in the brand green on white. GD ships a built-in
       font, so no external font file has to exist for this to render. */
    $white = imagecolorallocate($out, 255, 255, 255);
    $mint  = imagecolorallocate($out, 190, 242, 200);
    imagestring($out, 5, 44, $plateTop + 62, 'ZEVO', $mint);
    imagestring($out, 4, 44, $plateTop + 108, substr($productName, 0, 34), $white);
    imagestring($out, 3, 44, $plateTop + 148, 'Fresh Food . Honest Price', $mint);

    imagejpeg($out, $targetPath, 88);
    imagedestroy($src);
    imagedestroy($out);
}

/* ------------------------------------------------------------------ */
/* commands                                                            */
/* ------------------------------------------------------------------ */
$args = array_slice($argv, 1);
$has = fn (string $f) => in_array($f, $args, true);
$positional = array_values(array_filter($args, fn ($a) => !str_starts_with($a, '--')));

$products = App\Models\Product::orderBy('id')->get();

if ($has('--plan')) {
    $rows = [];
    echo str_repeat('=', 96), "\n";
    echo "PHOTO PLAN - search only, nothing downloaded\n";
    echo str_repeat('=', 96), "\n";
    printf("%-4s %-26s %-6s %-20s %s\n", '#', 'PRODUCT', 'LIC', 'SIZE', 'COMMONS FILE');
    echo str_repeat('-', 96), "\n";

    foreach ($products as $p) {
        try {
            $hit = searchCommons(queriesFor($p->name));
        } catch (Throwable $e) {
            printf("%-4s %-26s %s\n", $p->id, mb_strimwidth($p->name, 0, 25, '…'), 'ERROR: ' . mb_strimwidth($e->getMessage(), 0, 40, '…'));
            continue;
        }
        if (!$hit) {
            printf("%-4s %-26s %s\n", $p->id, mb_strimwidth($p->name, 0, 25, '…'), 'NO CANDIDATE');
            $rows[] = ['product_id' => $p->id, 'name' => $p->name, 'approved' => false, 'reason' => 'no candidate'];
            continue;
        }
        printf("%-4s %-26s %-6s %-20s %s\n",
            $p->id,
            mb_strimwidth($p->name, 0, 25, '…'),
            mb_strimwidth($hit['license'] ?: '?', 0, 5, '…'),
            $hit['width'] . 'x' . $hit['height'],
            mb_strimwidth(str_replace('File:', '', $hit['title']), 0, 34, '…'));

        $rows[] = [
            'product_id' => $p->id, 'name' => $p->name,
            'approved' => false,
            'candidate' => $hit,
        ];
    }

    if (!is_dir(dirname(REVIEW_FILE))) {
        mkdir(dirname(REVIEW_FILE), 0775, true);
    }
    file_put_contents(REVIEW_FILE, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo str_repeat('-', 96), "\n";
    echo "Review file written: " . REVIEW_FILE . "\n";
    echo "Open each 'candidate.page' link, confirm it really shows the product,\n";
    echo "then set \"approved\": true for the good ones and re-run --apply-all.\n";
    exit(0);
}

if ($has('--apply') || $has('--apply-all')) {
    if (!is_file(REVIEW_FILE)) {
        fwrite(STDERR, "No review file. Run --plan first.\n");
        exit(1);
    }
    $rows = json_decode(file_get_contents(REVIEW_FILE), true);
    $only = $positional[0] ?? null;

    $done = 0;
    foreach ($rows as $row) {
        if (empty($row['approved'])) {
            continue;
        }
        if ($only !== null && $row['name'] !== $only && (string) $row['product_id'] !== $only) {
            continue;
        }
        $c = $row['candidate'];
        try {
            $tmp = sys_get_temp_dir() . '/commons-' . $row['product_id'] . '.bin';
            file_put_contents($tmp, httpGet($c['thumb']));

            $base = App\Support\MediaPath::normalize(
                App\Models\Productimage::where('product_id', $row['product_id'])->value('image')
                    ?? 'uploads/product/' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($row['name'])) . '.jpg',
                'product'
            );
            $target = realpath(__DIR__ . '/../public') . '/' . ltrim($base, '/');
            brandPhoto($tmp, $target, $row['name']);

            /* Attribution sidecar: CC BY / CC BY-SA both require the author,
               the licence and the source to travel with the image. */
            file_put_contents($target . '.json', json_encode([
                'product'   => $row['name'],
                'product_id'=> $row['product_id'],
                'source'    => 'Wikimedia Commons',
                'file'      => $c['title'],
                'artist'    => $c['artist'],
                'license'   => $c['license'],
                'url'       => $c['page'],
                'query'     => $c['query'] ?? null,
                'fetched_at'=> date('c'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            @unlink($tmp);
            printf("OK   %-26s <- %s\n", mb_strimwidth($row['name'], 0, 25, '…'), str_replace('File:', '', $c['title']));
            $done++;
        } catch (Throwable $e) {
            printf("FAIL %-26s %s\n", $row['name'], $e->getMessage());
        }
    }
    printf("\napplied=%d  (skipped %d not approved)\n", $done, count($rows) - $done);
    echo "Attribution written beside every image as <file>.jpg.json\n";
    exit(0);
}

echo "Usage:\n";
echo "  php tools/fetch_product_photos.php --plan\n";
echo "  php tools/fetch_product_photos.php --apply-all\n";
echo "  php tools/fetch_product_photos.php --apply \"Fresh Carrots\"\n";