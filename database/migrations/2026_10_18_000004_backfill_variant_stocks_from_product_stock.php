<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill variant_stocks for variants that have no stock row yet.
 *
 * ---------------------------------------------------------------------------
 * THE BOOTSTRAP DEADLOCK THIS FIXES
 * ---------------------------------------------------------------------------
 * 2026_03_03_125102_create_variant_stocks_table created the table empty and
 * nothing ever seeded it. Stock in this shop predates the variant system and
 * lives in products.stock.
 *
 * The storefront is the only place that reads stock on the shopping path:
 *
 *     ShoppingController::enforceVariantStockOrFail()
 *         $stock    = variant_stocks row for the variant   -> absent, so NULL
 *         $available = (int) ($stock->available ?? 0)       -> 0
 *         $reserved  = SUM(active inventory_reservations)
 *         $availableToPromise = max(0, 0 - reserved)       -> 0
 *         => "Out of stock for this variant."
 *
 * So on any install where variant_stocks was never populated, EVERY variant
 * reads as out of stock and nothing can be added to a cart.
 *
 * The recovery path that exists does not help, because it is only on the admin
 * side: Admin\OrderController (and ProductController) lazily insert a missing
 * variant_stocks row when an order is approved - seeding on_hand/available from
 * products.stock. But an order can never be approved if no order can be placed.
 * Observed on this database: variant_stocks had 0 rows while products.stock was
 * 50, and add-to-cart silently failed, leaving order_save to bounce off its
 * "shopping empty" guard.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS IS SAFE
 * ---------------------------------------------------------------------------
 * * Idempotent. It inserts ONLY for variants that have no row. Re-running it
 *   never touches a stock figure, so it cannot undo a later adjustment or a
 *   sale. This is deliberately different from `php artisan stock:set-default`,
 *   which overwrites every product to one flat number.
 * * Non-destructive. products.stock is only READ. Nothing is reduced, and the
 *   legacy column is left exactly as it is, because Admin\OrderController and
 *   ProductController still mirror writes to it.
 * * consistent with the existing lazy-init. The seeding rule is copied from
 *   those call sites - on_hand = available = (int) products.stock, reserved = 0
 *   - so a backfilled variant and a lazily created one are indistinguishable.
 *
 * WHY reserved STAYS 0
 * Every write site in the codebase sets reserved = 0 and nothing ever
 * increments it; active holds live in inventory_reservations and are subtracted
 * at READ time by enforceVariantStockOrFail(), Product::stockLike(), and the
 * two FrontendController availability helpers. Backfilling a non-zero reserved
 * here would double-count every active reservation.
 *
 * WHY A MOVEMENT ROW IS WRITTEN
 * inventory_movements is the audit ledger the admin ledger screen reads. Stock
 * that appears from nowhere would make on_hand disagree with the sum of the
 * ledger, so each backfilled variant gets one OPENING_BALANCE movement using
 * the same vocabulary as the rest of the app (bucket 'sellable',
 * ref_type 'backfill'). type 'OPENING_BALANCE' is new to the table but honest:
 * it is neither an ADJUSTMENT nor a sale, and inventing a sale would corrupt
 * the order reconciliations the ledger feeds.
 *
 * DOWN is a no-op on purpose: deleting rows here could destroy stock figures
 * that real adjustments and sales have since moved. The rows are left in place.
 */
return new class extends Migration
{
    /** Process in batches so a large catalogue does not build one huge query. */
    private const CHUNK = 500;

    public function up(): void
    {
        if (! Schema::hasTable('variant_stocks') || ! Schema::hasTable('product_variants')) {
            return;
        }

        $hasMovements = Schema::hasTable('inventory_movements');
        $hasDamaged   = Schema::hasColumn('variant_stocks', 'damaged');
        $hasProducts  = Schema::hasTable('products');
        $now = now();
        $backfilled = 0;

        DB::table('product_variants')
            ->select(['product_variants.id', 'product_variants.product_id'])
            ->orderBy('product_variants.id')
            ->chunkById(self::CHUNK, function ($variants) use (
                &$backfilled, $hasMovements, $hasDamaged, $hasProducts, $now
            ) {
                $ids = $variants->pluck('id')->map(fn ($id) => (int) $id)->all();

                $alreadyStocked = DB::table('variant_stocks')
                    ->whereIn('variant_id', $ids)
                    ->pluck('variant_id')
                    ->map(fn ($id) => (int) $id)
                    ->flip();

                // One lookup for the whole chunk instead of one per variant.
                $stockByProduct = $hasProducts
                    ? DB::table('products')->pluck('stock', 'id')
                    : collect();

                $stockRows   = [];
                $moveRows    = [];

                foreach ($variants as $variant) {
                    $variantId = (int) $variant->id;

                    if ($alreadyStocked->has($variantId)) {
                        continue;   // never overwrite an existing figure
                    }

                    $initial = (int) ($stockByProduct[(int) $variant->product_id] ?? 0);
                    if ($initial < 0) {
                        $initial = 0;
                    }

                    $stockRow = [
                        'variant_id' => $variantId,
                        'on_hand'    => $initial,
                        'reserved'   => 0,
                        'available'  => $initial,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($hasDamaged) {
                        $stockRow['damaged'] = 0;
                    }

                    $stockRows[] = $stockRow;

                    if ($hasMovements) {
                        $moveRows[] = [
                            'variant_id'  => $variantId,
                            'type'        => 'OPENING_BALANCE',
                            'bucket'      => Schema::hasColumn('inventory_movements', 'bucket')
                                ? 'sellable'
                                : null,
                            'qty_delta'   => $initial,
                            'ref_type'    => 'backfill',
                            'ref_id'      => null,
                            'created_by'  => null,
                            'note'        => 'Opening balance seeded from products.stock '
                                . 'because variant_stocks had no row for this variant.',
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                }

                if ($stockRows === []) {
                    return;
                }

                // Drop null bucket for installs without that column.
                if ($moveRows !== [] && ! Schema::hasColumn('inventory_movements', 'bucket')) {
                    foreach ($moveRows as $i => $row) {
                        unset($moveRows[$i]['bucket']);
                    }
                }

                DB::transaction(function () use ($stockRows, $moveRows, $hasMovements, &$backfilled) {
                    DB::table('variant_stocks')->insert($stockRows);

                    if ($hasMovements && $moveRows !== []) {
                        DB::table('inventory_movements')->insert($moveRows);
                    }

                    $backfilled += count($stockRows);
                });
            }, 'id');
    }

    public function down(): void
    {
        // Intentionally empty.
        //
        // Rolling this back would mean deleting variant_stocks rows, and by the
        // time this migration is rolled back those rows may have been moved by
        // real adjustments, order deductions and returns. Destroying live stock
        // figures is strictly worse than leaving a seeded row in place, so down
        // is a no-op and the backfill is treated as permanent.
    }
};
