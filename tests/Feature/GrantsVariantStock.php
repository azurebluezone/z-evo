<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/**
 * Stock lives on the variant since the legacy products.stock column was
 * dropped, and InventoryLedger::reserve() correctly refuses a sale when the
 * variant has no balance. A product fixture that is going to be ordered
 * therefore has to own a default variant WITH stock; creating the product row
 * alone now produces a 500 instead of a usable order.
 */
trait GrantsVariantStock
{
    /**
     * Give the product's default variant a real opening balance.
     *
     * OrderPlacementService resolves a line without a variant_id to the
     * `{product_code}-DEFAULT` SKU, so that is the row reserved against.
     */
    protected function grantDefaultStock(object $product, int $qty = 10): int
    {
        $variantId = DB::table('product_variants')
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->value('id');

        if (!$variantId) {
            $variantId = DB::table('product_variants')->insertGetId([
                'product_id' => $product->id,
                'sku' => ($product->product_code ?: ('P' . $product->id)) . '-DEFAULT',
                'title' => 'Default',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('variant_stocks')->updateOrInsert(
            ['variant_id' => (int) $variantId],
            [
                'on_hand' => $qty,
                'reserved' => 0,
                'available' => $qty,
                'damaged' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return (int) $variantId;
    }
}
