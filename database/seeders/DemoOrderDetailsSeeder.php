<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoOrderDetailsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there's a category
        $category = DB::table('categories')->first();
        if (! $category) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Demo Category',
                'slug' => 'demo-category',
                'parent_id' => 0,
                'image' => 'public/uploads/category/default.png',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $categoryId = $category->id;
        }

        // Create or reuse a product
        $product = DB::table('products')->first();
        if (! $product) {
            $productId = DB::table('products')->insertGetId([
                'name' => 'Demo Product',
                'slug' => 'demo-product-' . Str::random(6),
                'category_id' => $categoryId,
                'brand_id' => null,
                'product_code' => 'DEMO-' . time(),
                'purchase_price' => 100,
                'old_price' => null,
                'new_price' => 120,
                'stock' => 100,
                'meta_description' => 'Demo product',
                'description' => 'Demo product created by seeder',
                'topsale' => 0,
                'feature_product' => 0,
                'campaign_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $productId = $product->id;
        }

        // Create a product variant
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'DEMO-SKU-' . Str::random(6),
            'title' => 'Demo Variant',
            'price_override' => null,
            'weight_gram' => null,
            'status' => 1,
            'attributes_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an order
        $orderId = DB::table('orders')->insertGetId([
            'invoice_id' => 'INV-' . time(),
            'amount' => 120,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => 0,
            'order_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Prepare order_details row
        $detail = [
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => 'Demo Product',
            'purchase_price' => 100,
            'sale_price' => 120,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // If schema supports product_size, store legacy marker so backfill can pick it up
        if (Schema::hasColumn('order_details', 'product_size')) {
            $detail['product_size'] = 'variant_id:' . $variantId;
        }

        // If schema has variant_id column, also set it directly
        if (Schema::hasColumn('order_details', 'variant_id')) {
            $detail['variant_id'] = $variantId;
        }

        DB::table('order_details')->insert($detail);

        $this->command->info('Inserted demo order_details with variant_id: ' . $variantId);
    }
}
