<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDefaultVariantsCommand extends Command
{
    protected $signature = 'stock:backfill-default-variants {amount=10 : Stock amount to set for newly created default variants}';

    protected $description = 'Create a DEFAULT variant for products that do not have any variants yet, and initialize variant_stocks.';

    public function handle(): int
    {
        $amount = (int) $this->argument('amount');
        if ($amount < 0) {
            $amount = 0;
        }

        $now = now();

        $createdVariants = 0;
        $createdStocks = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            $products = DB::table('products as p')
                ->leftJoin('product_variants as v', 'v.product_id', '=', 'p.id')
                ->whereNull('v.id')
                ->select('p.id', 'p.product_code', 'p.stock')
                ->orderBy('p.id')
                ->get();

            foreach ($products as $p) {
                $productId = (int) $p->id;
                if ($productId <= 0) {
                    $skipped++;
                    continue;
                }

                $base = (string) ($p->product_code ?? '');
                $base = trim($base);
                if ($base === '') {
                    $base = 'P' . $productId;
                }

                $sku = $base . '-DEFAULT';
                if (strlen($sku) > 80) {
                    $sku = substr($sku, 0, 80);
                }

                // Ensure unique SKU
                $suffix = 2;
                while (DB::table('product_variants')->where('sku', $sku)->exists()) {
                    $candidate = $base . '-DEFAULT-' . $suffix;
                    if (strlen($candidate) > 80) {
                        $candidate = substr($candidate, 0, 80);
                    }
                    $sku = $candidate;
                    $suffix++;
                    if ($suffix > 50) {
                        // something is wrong, bail for this product
                        $skipped++;
                        continue 2;
                    }
                }

                $variantId = DB::table('product_variants')->insertGetId([
                    'product_id' => $productId,
                    'sku' => $sku,
                    'title' => 'Default',
                    'image' => null,
                    'price_override' => null,
                    'weight_gram' => null,
                    'status' => 1,
                    'attributes_json' => json_encode(['ids' => [], 'attrs' => []]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $createdVariants++;

                $initAmount = $amount;
                if (is_numeric($p->stock)) {
                    $legacy = (int) $p->stock;
                    if ($legacy > 0) {
                        $initAmount = $legacy;
                    }
                }

                DB::table('variant_stocks')->insert([
                    'variant_id' => (int) $variantId,
                    'on_hand' => $initAmount,
                    'reserved' => 0,
                    'available' => $initAmount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $createdStocks++;
            }

            DB::commit();

            $this->info("Products without variants processed: " . count($products));
            $this->info("Default variants created: {$createdVariants}");
            $this->info("Variant stocks created: {$createdStocks}");
            $this->info("Skipped: {$skipped}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
