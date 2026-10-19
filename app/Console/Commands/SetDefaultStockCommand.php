<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetDefaultStockCommand extends Command
{
    protected $signature = 'stock:set-default {amount=10 : Stock amount to set for products and default variants}';

    protected $description = 'Set a default stock amount for all products and their DEFAULT variants (variant_stocks).';

    public function handle(): int
    {
        $amount = (int) $this->argument('amount');
        if ($amount < 0) {
            $amount = 0;
        }

        $now = now();

        DB::beginTransaction();
        try {
            $productsUpdated = DB::table('products')->update([
                'stock' => $amount,
                'updated_at' => $now,
            ]);

            $defaultVariants = DB::table('product_variants')
                ->where('sku', 'like', '%-DEFAULT')
                ->get(['id']);

            $variantsUpdated = 0;
            foreach ($defaultVariants as $variant) {
                $variantId = (int) $variant->id;
                if ($variantId <= 0) {
                    continue;
                }

                $exists = DB::table('variant_stocks')->where('variant_id', $variantId)->exists();

                if ($exists) {
                    DB::table('variant_stocks')->where('variant_id', $variantId)->update([
                        'on_hand' => $amount,
                        'reserved' => 0,
                        'available' => $amount,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('variant_stocks')->insert([
                        'variant_id' => $variantId,
                        'on_hand' => $amount,
                        'reserved' => 0,
                        'available' => $amount,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $variantsUpdated++;
            }

            DB::commit();

            $this->info("Products updated: {$productsUpdated}");
            $this->info("Default variants updated: {$variantsUpdated}");
            $this->info("Default stock amount: {$amount}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
