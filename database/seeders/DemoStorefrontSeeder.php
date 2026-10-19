<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Productimage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoStorefrontSeeder extends Seeder
{
    public function run()
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'demo-brand'],
            ['name' => 'Demo Brand', 'name_bn' => 'ডেমো ব্র্যান্ড', 'status' => 1]
        );

        $rootCategories = [
            'Fashion & Apparel',
            'Wall Clock',
            'Canvas',
            'Combo Set',
            'Men\'s Sunnah',
            'Woman\'s Sunnah',
        ];

        $categories = [];
        foreach ($rootCategories as $name) {
            $slug = Str::slug($name);
            $categories[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'parent_id' => 0,
                    'status' => 1,
                    'image' => 'assets/images/default.png',
                ]
            );
        }

        if (Product::where('status', 1)->count() >= 10) {
            return;
        }

        $demoProducts = [
            ['Premium 3D Canvas - CWC-201', 'canvas', 1150, 1250],
            ['Adora Winter Hoodie', 'fashion-apparel', 980, 1100],
            ['Classic Wall Clock - WC-10', 'wall-clock', 650, 750],
            ['Combo Set - Gift Pack', 'combo-set', 890, 990],
            ['Men\'s Panjabi Premium', 'mens-sunnah', 1550, 1750],
            ['Woman\'s Abaya Classic', 'womans-sunnah', 2100, 2350],
            ['Canvas Art - Modern 01', 'canvas', 700, 850],
            ['Wall Clock - Minimal 02', 'wall-clock', 499, 599],
            ['Fashion Shirt - FS-5', 'fashion-apparel', 799, 899],
            ['Combo Set - Couple Pack', 'combo-set', 1199, 1399],
        ];

        $nextId = (int) (Product::max('id') ?? 0) + 1;

        foreach ($demoProducts as $idx => $row) {
            [$name, $catSlug, $newPrice, $oldPrice] = $row;

            $category = $categories[$catSlug] ?? null;
            if (!$category) {
                continue;
            }

            $slug = Str::slug($name . '-' . ($nextId + $idx));
            $productCode = 'P' . str_pad((string) ($nextId + $idx), 4, '0', STR_PAD_LEFT);

            $product = Product::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'product_code' => $productCode,
                    'purchase_price' => (int) ($newPrice - 100),
                    'old_price' => (int) $oldPrice,
                    'new_price' => (int) $newPrice,
                    'stock' => 50,
                    'description' => 'Demo product description',
                    'meta_description' => 'Demo product meta description',
                    'topsale' => 1,
                    'feature_product' => 1,
                    'status' => 1,
                ]
            );

            Productimage::firstOrCreate(
                ['product_id' => $product->id],
                ['image' => 'assets/images/default.png']
            );
        }
    }
}
