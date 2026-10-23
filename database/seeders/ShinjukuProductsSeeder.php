<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ShinjukuProductsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding Shinjuku halal food products...');

        $jsonPath = base_path('work/t/data/shinjuku_products.json');
        if (! is_file($jsonPath)) {
            $this->command?->error("Missing $jsonPath");
            return;
        }
        $rows = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($rows)) {
            $this->command?->error('Invalid JSON');
            return;
        }

        // Ensure a category exists for these imported products.
        $category = DB::table('categories')->where('slug', 'shinjuku-halal-food')->first();
        if (! $category) {
            $payload = $this->columns('categories', [
                'name'            => 'Shinjuku Halal Food',
                'slug'            => 'shinjuku-halal-food',
                'parent_id'       => 0,
                'image'           => 'uploads/category/default.png',
                'meta_title'      => 'Shinjuku Halal Food',
                'meta_decription' => 'Imported from shinjukuhalalfood.com',
                'status'          => 1,
            ]) + ['created_at' => now(), 'updated_at' => now()];
            $id = DB::table('categories')->insertGetId($payload);
            $category = DB::table('categories')->where('id', $id)->first();
        }
        $categoryId = $category->id;

        $count = 0;
        foreach ($rows as $index => $row) {
            $name = $row['name'] ?? '';
            $slug = $row['slug'] ?? Str::slug($name);
            if ($slug === '') {
                $slug = 'shinjuku-product-' . ($index + 1);
            }

            $price = (int) ($row['price'] ?? 0);
            if ($price <= 0) {
                $price = 100;
            }

            $productPayload = $this->columns('products', [
                'name'             => $name,
                'slug'             => $slug,
                'category_id'      => $categoryId,
                'brand_id'         => null,
                'product_code'     => 'SH-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'purchase_price'   => (int) round($price * 0.72),
                'old_price'        => $price,
                'new_price'        => $price,
                'stock'            => 25,
                'meta_description' => $name,
                'description'      => strip_tags($row['description'] ?? $name),
                'topsale'          => 0,
                'feature_product'  => 0,
                'campaign_id'      => null,
                'status'           => 1,
            ]);

            $product = Product::updateOrCreate(['slug' => $slug], $productPayload);

            // Replace images for idempotency.
            DB::table('productimages')->where('product_id', $product->id)->delete();

            foreach (($row['images'] ?? []) as $img) {
                $rel = $img['rel'] ?? null;
                if (! $rel) {
                    continue;
                }
                if (! is_file(public_path($rel))) {
                    continue;
                }
                DB::table('productimages')->insert(
                    $this->columns('productimages', [
                        'image'      => $rel,
                        'title'      => $row['title'] ?? $name,
                        'product_id' => $product->id,
                    ]) + ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Ensure at least one image row exists.
            $hasImg = DB::table('productimages')->where('product_id', $product->id)->exists();
            if (! $hasImg) {
                DB::table('productimages')->insert(
                    $this->columns('productimages', [
                        'image'      => 'uploads/category/default.png',
                        'title'      => $name,
                        'product_id' => $product->id,
                    ]) + ['created_at' => now(), 'updated_at' => now()]
                );
            }

            $count++;
        }

        $this->command?->info("Seeded $count Shinjuku products.");
    }

    private function columns(string $table, array $values): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }
        $existing = array_flip(Schema::getColumnListing($table));
        return array_intersect_key($values, $existing);
    }
}
