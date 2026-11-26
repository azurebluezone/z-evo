<?php

namespace App\Support;

use App\Models\VariantStock;
use Illuminate\Support\Facades\DB;

/**
 * Seed stock for a product through the variant projection.
 *
 * The legacy `products.stock` column was dropped (migration
 * 2026_11_26_000002). Demo seeders that only knew a product-level quantity
 * call this instead: it makes sure the product has a variant, then writes the
 * quantity to `variant_stocks` and its OPENING_BALANCE movement.
 *
 * Idempotent: a product that already has a stocked variant is left untouched,
 * so re-running a seeder cannot reset a balance.
 */
class SeedStock
{
    /**
     * @return int|null the variant id, or null when the product is invalid
     */
    public static function ensureForProduct(int $productId, int $qty): ?int
    {
        if ($productId <= 0) {
            return null;
        }

        $qty = max(0, $qty);

        // Already stocked through the projection: do not touch it.
        $stockedVariant = DB::table('product_variants as v')
            ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->where('v.product_id', $productId)
            ->orderBy('v.id')
            ->value('v.id');

        if ($stockedVariant) {
            return (int) $stockedVariant;
        }

        // A variant without a stock row: give it the opening quantity.
        $variantId = (int) DB::table('product_variants')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->value('id');

        if ($variantId <= 0) {
            $base = trim((string) DB::table('products')->where('id', $productId)->value('product_code'));
            if ($base === '') {
                $base = 'P' . $productId;
            }

            $sku = $base . '-DEFAULT';
            if (strlen($sku) > 80) {
                $sku = substr($sku, 0, 80);
            }

            $suffix = 2;
            while (DB::table('product_variants')->where('sku', $sku)->exists()) {
                $sku = substr($base, 0, 70) . '-DEFAULT-' . $suffix;
                $suffix++;
                if ($suffix > 50) {
                    return null;
                }
            }

            $now = now();

            $variantId = (int) DB::table('product_variants')->insertGetId([
                'product_id' => $productId,
                'sku' => $sku,
                'title' => 'Default',
                'status' => 1,
                'attributes_json' => json_encode(['ids' => [], 'attrs' => []]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        VariantStock::ensureRow($variantId, $qty);

        return $variantId;
    }
}
