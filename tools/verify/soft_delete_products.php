<?php

/**
 * Live-database proof for the products soft delete.
 *
 * Creates one throwaway product inside a transaction, soft-deletes it,
 * asserts Eloquent hides it while the row survives, restores it, then rolls
 * the whole transaction back. Nothing is left in the database.
 *
 * Usage: php tools/verify/soft_delete_products.php
 * Prints SOFTDEL-ROUNDTRIP-OK only when every assertion passes.
 */

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$failures = [];

function zc_check(bool $ok, string $label, array &$failures): void
{
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $label . PHP_EOL;

    if (! $ok) {
        $failures[] = $label;
    }
}

zc_check(Schema::hasColumn('products', 'deleted_at'), 'products.deleted_at column exists', $failures);

DB::beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));

    $product = Product::create([
        'name'           => 'ZC soft delete probe ' . $suffix,
        'slug'           => 'zc-soft-delete-probe-' . $suffix,
        'category_id'    => 0,
        'product_code'   => 'ZC-SOFTDEL-' . $suffix,
        'purchase_price' => 0,
        'new_price'      => 0,
        'status'         => 0,
    ]);

    $id = (int) $product->id;

    $product->delete();

    zc_check(Product::find($id) === null, 'default query hides the trashed product', $failures);
    zc_check(Product::withTrashed()->find($id) !== null, 'withTrashed() still finds the row', $failures);
    zc_check(
        (bool) DB::table('products')->where('id', $id)->whereNotNull('deleted_at')->exists(),
        'the row is kept with deleted_at set (no hard DELETE)',
        $failures
    );

    Product::withTrashed()->find($id)->restore();

    zc_check(Product::find($id) !== null, 'restore() brings the product back', $failures);
} catch (\Throwable $e) {
    zc_check(false, 'round trip threw: ' . $e->getMessage(), $failures);
} finally {
    DB::rollBack();
}

if ($failures === []) {
    echo 'SOFTDEL-ROUNDTRIP-OK' . PHP_EOL;
    exit(0);
}

echo 'SOFTDEL-ROUNDTRIP-FAIL' . PHP_EOL;
exit(1);
