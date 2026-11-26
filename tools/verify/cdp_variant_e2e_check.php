<?php

/**
 * Contract gc-mwx5vjl7 - CDP variant end-to-end verifier.
 *
 * Two subcommands:
 *
 *   php tools/verify/cdp_variant_e2e_check.php find-product "<name>"
 *       Prints {"id":N,"slug":"...","name":"..."} for the newest product with
 *       that exact name. Used by the browser runner after it submits the admin
 *       create form, so the runner never guesses an id.
 *
 *   php tools/verify/cdp_variant_e2e_check.php verify <manifest.json>
 *       Cross-checks the browser manifest against the live database:
 *       gallery rows + files, per-variant image/price/opening stock, the
 *       rendered storefront values, the order line, and the stock ledger
 *       after the admin marked the order delivered.
 *
 * Exits 0 only when every check passes.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$command = $argv[1] ?? '';

if ($command === 'find-product') {
    $name = (string) ($argv[2] ?? '');
    $row = DB::table('products')->where('name', $name)->orderByDesc('id')->first(['id', 'slug', 'name']);

    if (!$row) {
        fwrite(STDERR, "find-product: no product named {$name}\n");
        exit(1);
    }

    echo json_encode([
        'id' => (int) $row->id,
        'slug' => (string) $row->slug,
        'name' => (string) $row->name,
    ], JSON_UNESCAPED_UNICODE), PHP_EOL;
    exit(0);
}

if ($command !== 'verify' || empty($argv[2])) {
    fwrite(STDERR, "usage: cdp_variant_e2e_check.php find-product <name> | verify <manifest.json>\n");
    exit(2);
}

$manifest = json_decode((string) file_get_contents($argv[2]), true);

if (!is_array($manifest)) {
    fwrite(STDERR, "verify: manifest is not readable JSON\n");
    exit(2);
}

$fail = 0;

function check(bool $condition, string $label): void
{
    global $fail;

    if ($condition) {
        echo "PASS: {$label}\n";
        return;
    }

    echo "FAIL: {$label}\n";
    $fail++;
}

function storedFile(string $stored): string
{
    $path = preg_replace('~^public/~', '', $stored) ?? $stored;

    return public_path(ltrim($path, '/'));
}

try {
    $productId = (int) $manifest['product']['id'];
    $orderId = (int) $manifest['order']['id'];
    $orderQty = (int) ($manifest['order']['qty'] ?? $manifest['cart']['qty'] ?? 0);
    $variantA = $manifest['variants']['a'];
    $variantB = $manifest['variants']['b'];

    echo "=== Product and gallery ===\n";

    $product = DB::table('products')->where('id', $productId)->first();
    check($product !== null, 'the admin-created product row exists');
    check($product !== null && (string) $product->name === (string) $manifest['product']['name'], 'the product row carries the submitted name');

    $gallery = DB::table('productimages')
        ->where('product_id', $productId)
        ->orderBy('id')
        ->pluck('image')
        ->all();

    check(count($gallery) >= 2, 'at least two gallery images are stored (got ' . count($gallery) . ')');

    $galleryFilesOk = true;
    foreach ($gallery as $stored) {
        if (!is_file(storedFile((string) $stored))) {
            $galleryFilesOk = false;
            break;
        }
    }
    check($galleryFilesOk, 'every stored gallery image file exists on disk');

    echo "=== Variants: image, price, opening stock ===\n";

    $variantRows = [];

    foreach (['a' => $variantA, 'b' => $variantB] as $key => $expected) {
        $row = DB::table('product_variants')->where('sku', (string) $expected['sku'])->first();
        $variantRows[$key] = $row;

        check($row !== null, "variant {$key} row exists for sku {$expected['sku']}");
        if (!$row) {
            continue;
        }

        check((int) $row->product_id === $productId, "variant {$key} belongs to the created product");
        check(!empty($row->image) && is_file(storedFile((string) $row->image)), "variant {$key} has its own image file on disk");
        check((int) $row->price_override === (int) $expected['priceOverride'], "variant {$key} stores its own price override");

        $decoded = json_decode((string) $row->attributes_json, true);
        $colour = is_array($decoded) ? (int) ($decoded['ids']['color'] ?? 0) : 0;
        check($colour === (int) $expected['attributeValueId'], "variant {$key} keeps the picked colour in attributes_json");

        $stock = DB::table('variant_stocks')->where('variant_id', $row->id)->first();
        check($stock !== null, "variant {$key} has a variant_stocks row");

        $expectedOnHand = (int) $expected['openingStock'];
        if ($key === 'a') {
            $expectedOnHand -= $orderQty;
        }

        check($stock !== null && (int) $stock->on_hand === $expectedOnHand, "variant {$key} on_hand is {$expectedOnHand}");
        check($stock !== null && (int) $stock->available === $expectedOnHand, "variant {$key} available is {$expectedOnHand}");
    }

    echo "=== Storefront values measured in Chromium ===\n";

    check(
        str_contains((string) $variantA['dom']['mainImage'], '/uploads/variant/'),
        'pick A rendered a variant-specific photo'
    );
    check(
        trim((string) $variantA['dom']['priceText']) === 'Tk ' . $variantA['priceOverride'],
        'pick A rendered the variant price (' . $variantA['dom']['priceText'] . ')'
    );
    check(
        str_contains((string) $variantA['dom']['stockText'], '(' . $variantA['openingStock'] . ' left)'),
        'pick A rendered its own stock (' . $variantA['dom']['stockText'] . ')'
    );
    check(
        (string) $variantA['dom']['qtyMax'] === (string) $variantA['openingStock']
        && (string) $variantA['dom']['qtyDataMax'] === (string) $variantA['openingStock'],
        'pick A moved the quantity ceiling to its opening stock'
    );

    check(
        trim((string) $variantB['dom']['priceText']) === 'Tk ' . $variantB['priceOverride'],
        'pick B rendered the other variant price (' . $variantB['dom']['priceText'] . ')'
    );
    check(
        str_contains((string) $variantB['dom']['stockText'], '(' . $variantB['openingStock'] . ' left)'),
        'pick B rendered the other variant stock (' . $variantB['dom']['stockText'] . ')'
    );
    check(
        (string) $variantB['dom']['qtyMax'] === (string) $variantB['openingStock'],
        'pick B moved the quantity ceiling to its opening stock'
    );
    check(
        (string) $variantA['dom']['mainImage'] !== (string) $variantB['dom']['mainImage'],
        'the two picks rendered two different photos'
    );
    $mirrors = array_values(array_filter(
        explode(',', (string) $manifest['cart']['mirrorQty']),
        static fn (string $value): bool => $value !== ''
    ));

    check(
        $mirrors !== [] && count(array_filter($mirrors, static fn (string $value): bool => $value === (string) $orderQty)) === count($mirrors)
        && (string) $manifest['cart']['visibleQty'] === (string) $orderQty,
        'the stepper and its ' . count($mirrors) . ' hidden form mirrors all carry the ordered quantity'
    );

    echo "=== Independent server probe (variant-detail endpoint) ===\n";

    foreach (['a' => $variantA, 'b' => $variantB] as $key => $expected) {
        $url = rtrim((string) $manifest['baseUrl'], '/') . '/products/' . $productId . '/variant-detail?attribute_values='
            . urlencode(json_encode([(int) $expected['attributeValueId']]));

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->timeout(10)->get($url);

        $json = $response->json();
        $bundle = is_array($json) ? ($json['variant'] ?? null) : null;

        check($response->ok() && is_array($bundle), "variant-detail answers for pick {$key}");
        if (!is_array($bundle)) {
            continue;
        }

        $expectedNow = (int) $expected['openingStock'];
        if ($key === 'a') {
            $expectedNow -= $orderQty;
        }

        check(
            str_contains((string) ($bundle['image'] ?? ''), '/uploads/variant/'),
            "variant-detail returns the variant image for pick {$key}"
        );
        check(
            (int) ($bundle['stock_quantity'] ?? -1) === $expectedNow,
            "variant-detail reports {$expectedNow} left for pick {$key} after the sale"
        );
        check(
            abs((float) ($bundle['price'] ?? 0) - (float) $expected['priceOverride']) < 0.001,
            "variant-detail reports the variant price for pick {$key}"
        );
    }

    echo "=== Order and admin status ===\n";

    $order = DB::table('orders')->where('id', $orderId)->first();
    check($order !== null, 'the storefront order row exists');
    check($order !== null && (string) $order->invoice_id !== '', 'the order has an invoice id');

    $status = $order
        ? DB::table('order_statuses')->where('id', (int) $order->order_status)->first()
        : null;
    check($status !== null && (string) $status->slug === 'delivered', 'the admin status change put the order in delivered');

    $rowA = $variantRows['a'] ?? null;
    $rowB = $variantRows['b'] ?? null;

    $line = $rowA
        ? DB::table('order_details')->where('order_id', $orderId)->where('variant_id', (int) $rowA->id)->first()
        : null;

    check($line !== null, 'an order line references the picked variant');
    check($line !== null && (int) $line->qty === $orderQty, "the order line quantity is {$orderQty}");
    check($line !== null && (int) $line->product_id === $productId, 'the order line belongs to the created product');

    if ($rowB) {
        $otherLines = DB::table('order_details')
            ->where('order_id', $orderId)
            ->where('variant_id', (int) $rowB->id)
            ->count();
        check($otherLines === 0, 'the other variant was not part of the order');
    }

    echo "=== Inventory ledger ===\n";

    if ($rowA) {
        $movements = DB::table('inventory_movements')->where('variant_id', (int) $rowA->id);

        $opening = (clone $movements)->where('type', 'OPENING_BALANCE')->first();
        check(
            $opening !== null && (int) $opening->qty_delta === (int) $variantA['openingStock'],
            'an OPENING_BALANCE movement records the opening stock'
        );

        $reserve = (clone $movements)
            ->where('type', 'RESERVE')
            ->where('ref_type', 'order')
            ->where('ref_id', $orderId)
            ->first();
        check(
            $reserve !== null && (int) $reserve->qty_delta === -$orderQty,
            'a RESERVE movement holds exactly the ordered quantity against the order'
        );

        $sale = (clone $movements)
            ->where('type', 'SALE')
            ->where('ref_type', 'order')
            ->where('ref_id', $orderId)
            ->first();
        check(
            $sale !== null && (int) $sale->qty_delta === -$orderQty,
            'a SALE movement records the negative delta against the order'
        );

        $stockRow = DB::table('variant_stocks')->where('variant_id', (int) $rowA->id)->first();
        $ledgerSum = (int) (clone $movements)
            ->whereIn('type', ['OPENING_BALANCE', 'SALE', 'RETURN', 'RETURN_DAMAGED', 'ADJUSTMENT', 'DAMAGE'])
            ->sum('qty_delta');

        check(
            $stockRow !== null && $ledgerSum === (int) $stockRow->on_hand,
            'the ledger sum equals the on_hand projection'
        );

        $openHolds = DB::table('inventory_reservations')
            ->where('variant_id', (int) $rowA->id)
            ->where('order_id', $orderId)
            ->count();
        check($openHolds === 0, 'the checkout hold was consumed when the sale posted');
    }

    if ($rowB) {
        $leak = DB::table('inventory_movements')
            ->where('variant_id', (int) $rowB->id)
            ->whereIn('type', ['RESERVE', 'SALE'])
            ->count();
        check($leak === 0, 'no stock movement leaked onto the unpicked variant');
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'verify: ' . $error->getMessage() . PHP_EOL);
    exit(2);
}

if ($fail > 0) {
    echo "FAILED: {$fail} check(s)\n";
    exit(1);
}

echo "CDP_E2E_DB_VERIFIED\n";
exit(0);
