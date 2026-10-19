<?php

/**
 * variant_test_data.php
 * --------------------
 * Seeds (or removes) a clearly marked set of variant data on one product so the
 * variant UI can actually be exercised and proven.
 *
 * The database this project ships with has zero rows in product_variants,
 * attributes and attribute_values, so the detail page renders no variant pills
 * at all and a bug in the pill handling would be invisible.
 *
 * Every row this writes is tagged with the marker below, and --remove deletes
 * exactly those rows and nothing else. Nothing else in the database is touched.
 *
 * USAGE
 *   php tools/variant_test_data.php            # report current state
 *   php tools/variant_test_data.php --seed     # create the fixture
 *   php tools/variant_test_data.php --remove   # delete exactly the fixture
 */

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/** Every row this tool creates carries this marker, so removal is exact. */
const MARKER = 'ZZTEST';
const PRODUCT_ID = 1;

$args = array_slice($argv, 1);
$seed = in_array('--seed', $args, true);
$remove = in_array('--remove', $args, true);

/* ------------------------------------------------------------------ */
/* report                                                              */
/* ------------------------------------------------------------------ */
$state = function (): array {
    // attribute_values carry no marker of their own (value is "Small"/"Large"),
    // so they are identified by their parent attribute, never by their text.
    $attrIds = DB::table('attributes')
        ->where('slug', 'like', MARKER . '%')->pluck('id');

    $vIds = DB::table('product_variants')
        ->where('product_id', PRODUCT_ID)
        ->where('sku', 'like', MARKER . '%')
        ->pluck('id');

    return [
        'variants'   => $vIds->all(),
        'values'     => DB::table('attribute_values')
            ->whereIn('attribute_id', $attrIds)->pluck('id')->all(),
        'attributes' => $attrIds->all(),
    ];
};

$pad = fn ($s, $n) => str_pad((string) $s, $n);
/* Reads the CURRENT state every time it runs. Capturing $s by value here would
   print the state from before a --seed, which is exactly the bug this replaced. */
$show = function () use ($state, $pad) {
    $s = $state();
    echo str_repeat('=', 66), "\n";
    echo "Variant test fixture  (marker " . MARKER . ", product " . PRODUCT_ID . ")\n";
    echo str_repeat('=', 66), "\n";
    printf("%-16s %s\n", 'attributes', $s['attributes'] ? implode(', ', $s['attributes']) : '(none)');
    printf("%-16s %s\n", 'attribute_values', $s['values'] ? implode(', ', $s['values']) : '(none)');
    printf("%-16s %s\n", 'variants', $s['variants'] ? implode(', ', $s['variants']) : '(none)');
};

/* ------------------------------------------------------------------ */
/* remove                                                              */
/* ------------------------------------------------------------------ */
if ($remove) {
    $before = $state();

    if ($before['variants']) {
        DB::table('variant_stocks')->whereIn('variant_id', $before['variants'])->delete();
        DB::table('product_variants')->whereIn('id', $before['variants'])->delete();
    }
    if ($before['values']) {
        DB::table('attribute_values')->whereIn('id', $before['values'])->delete();
    }
    if ($before['attributes']) {
        DB::table('attributes')->whereIn('id', $before['attributes'])->delete();
    }

    $after = $state();
    echo "REMOVED fixture.\n";
    printf("  variants  %d -> %d\n", count($before['variants']), count($after['variants']));
    printf("  values    %d -> %d\n", count($before['values']), count($after['values']));
    printf("  attrs     %d -> %d\n", count($before['attributes']), count($after['attributes']));
    echo "\nAny other row in the database was left untouched.\n";
    exit(0);
}

/* ------------------------------------------------------------------ */
/* seed                                                                */
/* ------------------------------------------------------------------ */
if ($seed) {
    DB::transaction(function () {
        $now = now();

        // "Size" attribute with two values.
        $attrId = DB::table('attributes')->insertGetId([
            'name' => 'Size', 'slug' => MARKER . '-size', 'kind' => 'select',
            'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $valIds = [];
        foreach ([['Small', 0], ['Large', 1]] as [$label, $sort]) {
            $valIds[$label] = DB::table('attribute_values')->insertGetId([
                'attribute_id' => $attrId, 'value' => $label,
                'code' => MARKER . '-' . strtolower($label),
                'multiplier' => 0, 'kind' => 'select', 'status' => 1,
                'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Two variants. The Large one carries its own photo and its own stock,
        // so a click that swaps the main image and a stock number that changes
        // are both observable from the outside.
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => PRODUCT_ID,
            'sku' => MARKER . '-SMALL',
            'title' => MARKER . ' Small',
            'image' => 'uploads/product/organic-potatoes.jpg',
            'price_override' => 520,
            'status' => 1,
            'attributes_json' => json_encode([
                'attrs' => [MARKER . '-size' => 'Small'],
                'ids'   => [MARKER . '-size' => $valIds['Small']],
            ]),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('variant_stocks')->insert([
            'variant_id' => $variantId, 'on_hand' => 40, 'reserved' => 5,
            'available' => 35, 'damaged' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $variantId2 = DB::table('product_variants')->insertGetId([
            'product_id' => PRODUCT_ID,
            'sku' => MARKER . '-LARGE',
            'title' => MARKER . ' Large',
            'image' => 'uploads/product/fresh-carrots.jpg',
            'price_override' => 780,
            'status' => 1,
            'attributes_json' => json_encode([
                'attrs' => [MARKER . '-size' => 'Large'],
                'ids'   => [MARKER . '-size' => $valIds['Large']],
            ]),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('variant_stocks')->insert([
            'variant_id' => $variantId2, 'on_hand' => 12, 'reserved' => 0,
            'available' => 12, 'damaged' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
    });

    echo "SEEDED fixture.\n\n";
}

$show();
echo "\n";
if (! $seed && ! $remove) {
    echo "Nothing was written. Use --seed or --remove.\n";
}