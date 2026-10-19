<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps products.stock and variant_stocks from drifting apart.
 *
 * ---------------------------------------------------------------------------
 * THE SPLIT THIS EXISTS TO END
 * ---------------------------------------------------------------------------
 * Stock is really held per VARIANT (variant_stocks). `products.stock` is a
 * legacy column that predates the variant system and is still written and
 * displayed by several admin screens.
 *
 * Most write paths mirror both. Two did not:
 *
 *   Admin\ProductController@price_update   wrote products.stock only
 *   Admin\ProductController@quick_update   wrote products.stock only
 *
 * The result is a product whose admin row shows 50 units while the storefront -
 * which reads variant_stocks and nothing else - says "out of stock". An admin
 * can restock a product from the grid and it still cannot be bought, with no
 * error anywhere to explain why.
 *
 * ---------------------------------------------------------------------------
 * WHY THE LEGACY COLUMN IS NOT SIMPLY DELETED
 * ---------------------------------------------------------------------------
 * It is still read by Product::displayStockQuantity() as a fallback when a
 * product has no variant, and it is what the admin grids bind their input to.
 * Removing it would be a much larger change than the bug warrants. Making the
 * two writers behave like the other writers is the minimal correct fix.
 *
 * ---------------------------------------------------------------------------
 * WHAT syncProductStockToVariants DOES
 * ---------------------------------------------------------------------------
 * For every variant of the product that does NOT already carry its own
 * explicitly managed stock, the legacy figure is copied across and the delta is
 * written to inventory_movements so the ledger still reconciles.
 *
 * Variants the admin has stocked individually are LEFT ALONE. If the admin
 * set Navy=3 and Red=7 on the variant screen, a bulk edit of the product row
 * must not flatten both to the product's number - that would destroy work the
 * admin did on purpose.
 */
class ProductStockSync
{
    /**
     * Create the stock row for ONE variant that has none.
     *
     * ---------------------------------------------------------------------------
     * WHY THE STOREFRONT NEEDS THIS
     * ---------------------------------------------------------------------------
     * The storefront reads stock from variant_stocks alone:
     *
     *     ShoppingController::enforceVariantStockOrFail()
     *         $available = (int) ($stock->available ?? 0)   // absent row => 0
     *         => "Out of stock for this variant."
     *
     * So a single missing row makes a product unbuyable. With no selection the
     * cart resolves the DEFAULT variant (sku = {product_code}-DEFAULT), which is
     * exactly the row the old backfill never created for products that already
     * had variants - so those products deadlock: they cannot be bought, and the
     * admin's lazy insert only runs while APPROVING an order that can never be
     * placed.
     *
     * Observed live: product 1 (P0001, products.stock = 50) had variants 1 and 2
     * with ZERO variant_stocks rows, and add-to-cart answered 422
     * "Out of stock for this variant. available: 0".
     *
     * This is the same seeding rule the admin lazy-init and the backfill
     * migration already use - on_hand = available = (int) products.stock,
     * reserved = 0 - so a row created here is indistinguishable from one created
     * anywhere else. It ONLY ever creates a missing row; an existing figure is
     * never touched.
     *
     * @return bool  true when a row was created
     */
    public function ensureVariantStockRow(int $variantId, ?int $byAdmin = null): bool
    {
        if ($variantId <= 0) {
            return false;
        }

        if (DB::table('variant_stocks')->where('variant_id', $variantId)->exists()) {
            return false;
        }

        $variant = DB::table('product_variants')
            ->where('id', $variantId)
            ->first(['id', 'product_id']);

        if (! $variant) {
            return false;
        }

        $legacy = DB::table('products')->where('id', (int) $variant->product_id)->value('stock');
        $initial = max(0, (int) $legacy);
        $now = now();

        DB::table('variant_stocks')->insert([
            'variant_id' => $variantId,
            'on_hand'    => $initial,
            'reserved'   => 0,          // holds live in inventory_reservations
            'available'  => $initial,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (Schema::hasTable('inventory_movements')) {
            DB::table('inventory_movements')->insert([
                'variant_id' => $variantId,
                'type'       => 'OPENING_BALANCE',
                'bucket'     => 'sellable',
                'qty_delta'  => $initial,
                'ref_type'   => 'variant',
                'ref_id'     => $variantId,
                'created_by' => $byAdmin,
                'note'       => 'Opening balance created on first stock read.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return true;
    }

    /**
     * Backfill EVERY variant that has no stock row.
     *
     * The re-runnable form of migration 2026_10_18_000004. The migration only
     * ever runs once, so it could not repair a database whose rows were removed
     * afterwards (which is exactly what happened here). Only missing rows are
     * created; an existing figure is never overwritten, so this cannot undo an
     * adjustment or a sale - unlike `stock:set-default`, which flattens every
     * product to one number.
     *
     * @return int  number of stock rows created
     */
    public function initializeMissingVariantStocks(?int $byAdmin = null): int
    {
        if (! Schema::hasTable('variant_stocks') || ! Schema::hasTable('product_variants')) {
            return 0;
        }

        $created = 0;
        $now = now();

        DB::table('product_variants')
            ->select(['id', 'product_id'])
            ->orderBy('id')
            ->chunkById(500, function ($variants) use (&$created, $byAdmin, $now) {
                $ids = $variants->pluck('id')->map(fn ($id) => (int) $id)->all();

                $stocked = DB::table('variant_stocks')
                    ->whereIn('variant_id', $ids)
                    ->pluck('variant_id')
                    ->map(fn ($id) => (int) $id)
                    ->flip();

                // One lookup for the chunk rather than one per variant.
                $stockByProduct = DB::table('products')->pluck('stock', 'id');

                $stockRows = [];
                $moveRows  = [];

                foreach ($variants as $variant) {
                    $variantId = (int) $variant->id;

                    if ($stocked->has($variantId)) {
                        continue;
                    }

                    $initial = max(0, (int) ($stockByProduct[(int) $variant->product_id] ?? 0));

                    $stockRows[] = [
                        'variant_id' => $variantId,
                        'on_hand'    => $initial,
                        'reserved'   => 0,
                        'available'  => $initial,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (Schema::hasTable('inventory_movements')) {
                        $moveRows[] = [
                            'variant_id' => $variantId,
                            'type'       => 'OPENING_BALANCE',
                            'bucket'     => 'sellable',
                            'qty_delta'  => $initial,
                            'ref_type'   => 'backfill',
                            'ref_id'     => null,
                            'created_by' => $byAdmin,
                            'note'       => 'Opening balance backfilled from products.stock.',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($stockRows !== []) {
                    DB::table('variant_stocks')->insert($stockRows);
                    $created += count($stockRows);
                }

                if ($moveRows !== []) {
                    DB::table('inventory_movements')->insert($moveRows);
                }
            });

        return $created;
    }

    /**
     * Copy a product's legacy stock figure onto its variants.
     *
     * @param int  $productId
     * @param int  $stock        the new products.stock value
     * @param bool $force        overwrite variants that already have their own row
     * @param int|null $byAdmin  auth id for the movement's created_by
     * @return int  number of variant stock rows touched
     */
    public function syncProductStockToVariants(int $productId, int $stock, bool $force = false, ?int $byAdmin = null): int
    {
        if ($stock < 0) {
            $stock = 0;
        }

        $variantIds = DB::table('product_variants')
            ->where('product_id', $productId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($variantIds === []) {
            return 0;
        }

        $existing = DB::table('variant_stocks')
            ->whereIn('variant_id', $variantIds)
            ->get()
            ->keyBy('variant_id');

        $stockRows = [];
        $moveRows  = [];
        $now       = now();
        $touched   = 0;

        foreach ($variantIds as $variantId) {
            $row = $existing->get($variantId);

            // A variant with its own stock row has been managed individually.
            // Respect that unless explicitly told to overwrite.
            if ($row !== null && ! $force) {
                continue;
            }

            $previous = $row !== null ? (int) $row->on_hand : 0;
            $delta    = $stock - $previous;

            if ($delta === 0 && $row !== null) {
                continue;
            }

            $stockRows[] = [
                'variant_id' => $variantId,
                'on_hand'    => $stock,
                'available'  => $stock,      // no per-variant reservations exist yet at this point
                'updated_at' => $now,
            ];

            // Only log a real movement. The ledger is what the admin Inventory
            // screen reads, so a bulk stock edit has to show up there too.
            if ($delta !== 0) {
                $moveRows[] = [
                    'variant_id' => $variantId,
                    'type'       => 'ADJUSTMENT',
                    'bucket'     => 'sellable',
                    'qty_delta'  => $delta,
                    'ref_type'   => 'product',
                    'ref_id'     => $productId,
                    'created_by' => $byAdmin,
                    'note'       => 'Stock edited from the product list (bulk).',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $touched++;
        }

        if ($stockRows === []) {
            return 0;
        }

        DB::transaction(function () use ($variantIds, $stockRows, $moveRows) {
            $insert = [];
            $update = [];

            foreach ($stockRows as $stockRow) {
                // variant_id is the primary key, so an existing row must be
                // updated and a missing one inserted; insertOrIgnore would
                // silently skip the ones that already exist.
                $existing = DB::table('variant_stocks')
                    ->where('variant_id', $stockRow['variant_id'])
                    ->exists();

                if ($existing) {
                    $update[] = $stockRow;
                } else {
                    $insert[] = $stockRow + [
                        'reserved'  => 0,
                        'created_at' => now(),
                    ];
                }
            }

            foreach ($update as $row) {
                DB::table('variant_stocks')
                    ->where('variant_id', $row['variant_id'])
                    ->update([
                        'on_hand'    => $row['on_hand'],
                        'available'  => $row['available'],
                        'updated_at' => $row['updated_at'],
                    ]);
            }

            if ($insert !== []) {
                DB::table('variant_stocks')->insert($insert);
            }

            if ($moveRows !== []) {
                DB::table('inventory_movements')->insert($moveRows);
            }
        });

        return $touched;
    }

    /**
     * Convenience: push whatever products.stock currently holds.
     */
    public function syncForProduct(Product $product, bool $force = false, ?int $byAdmin = null): int
    {
        return $this->syncProductStockToVariants(
            (int) $product->id,
            (int) ($product->stock ?? 0),
            $force,
            $byAdmin
        );
    }
}