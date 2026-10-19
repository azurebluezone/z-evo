<?php

namespace App\Console\Commands;

use App\Services\ProductStockSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Create the missing stock rows that make a product unbuyable.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS EXISTS NEXT TO stock:backfill-default-variants
 * ---------------------------------------------------------------------------
 * That command handles only products that have NO variants at all:
 *
 *     ->leftJoin('product_variants as v', 'v.product_id', '=', 'p.id')
 *     ->whereNull('v.id')
 *
 * A product that already has variants therefore never reaches it - and those are
 * precisely the products that break, because the cart resolves a DEFAULT variant
 * (sku = {product_code}-DEFAULT) and then reads variant_stocks, which has no row
 * for it:
 *
 *     $available = (int) ($stock->available ?? 0);   // => 0
 *     => "Out of stock for this variant."
 *
 * Observed on this database: product P0001 with products.stock = 50, variants
 * "Demo Variant" and "Default", and ZERO variant_stocks rows. Add-to-cart
 * answered HTTP 422 and no order could be placed.
 *
 * ---------------------------------------------------------------------------
 * IDEMPOTENT AND NON-DESTRUCTIVE
 * ---------------------------------------------------------------------------
 * Only variants with NO row are touched; an existing on_hand figure is never
 * overwritten, so running this after sales or manual adjustments cannot change
 * any real stock number. This is deliberately NOT `stock:set-default`, which
 * writes one flat figure across every product and would erase per-variant work.
 *
 * Every created row is accompanied by an OPENING_BALANCE entry in
 * inventory_movements so the admin ledger still reconciles against variant_stocks.
 *
 * Safe to re-run, and safe to run on a live shop.
 */
class InitVariantStocksCommand extends Command
{
    protected $signature = 'stock:init-variant-stocks';

    protected $description = 'Create variant_stocks rows for variants that have none (fixes products that cannot be added to a cart)';

    public function handle(): int
    {
        $before = (int) DB::table('variant_stocks')->count();
        $variants = (int) DB::table('product_variants')->count();

        $this->line("Variants: {$variants} · stock rows before: {$before}");

        $created = app(ProductStockSync::class)->initializeMissingVariantStocks();

        $after = (int) DB::table('variant_stocks')->count();

        $this->info("Stock rows created: {$created}");
        $this->line("Stock rows now: {$after}");

        if ($after < $variants) {
            $this->warn('Still ' . ($variants - $after) . ' variant(s) without a stock row - check for orphans.');

            return self::FAILURE;
        }

        $this->info('Every variant now has a stock row.');

        return self::SUCCESS;
    }
}
