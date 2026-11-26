<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Productimage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Product create/edit must keep variant-wise data and every gallery image.
 *
 * Two defects motivated this test:
 *
 *  1. Variant image inputs were posted as a plain `variant_image[]` array.
 *     Empty file inputs are dropped by the form's WAF-safety submit hook, so
 *     when an earlier variant row had no photo the remaining files shifted
 *     index and were attached to the wrong variant.
 *  2. The edit form posted existing image ids as `image[]` and paired the new
 *     files with the same `title[]` array, but PHP indexes file inputs and text
 *     inputs independently. A new upload therefore took the title of the first
 *     existing image, and edits to existing titles were never saved.
 *
 * The test posts the real admin payloads. DB writes run inside a transaction
 * that is rolled back; every moved file is deleted afterwards.
 */
class ProductVariantImageStoreTest extends TestCase
{
    /** @var string[] */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $relative) {
            $absolute = public_path($relative);
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user to authenticate as.');

        return $admin;
    }

    private function categoryId(): int
    {
        $id = DB::table('categories')->value('id');
        $this->assertNotNull($id, 'Seed at least one category.');

        return (int) $id;
    }

    /**
     * @return array{0:int,1:int,2:int} attribute id, first value id, second value id
     */
    private function attribute(string $name, string $first, string $second): array
    {
        $attributeId = DB::table('attributes')->insertGetId([
            'name' => $name,
            'slug' => 'zc-' . strtolower($name) . '-' . uniqid(),
            'kind' => 'custom',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $valueIds = [];
        foreach ([$first, $second] as $sort => $value) {
            $valueIds[] = DB::table('attribute_values')->insertGetId([
                'attribute_id' => $attributeId,
                'value' => $value,
                'code' => strtolower($value),
                'kind' => 'custom',
                'status' => 1,
                'sort_order' => $sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$attributeId, $valueIds[0], $valueIds[1]];
    }

    private function emptyUpload(string $name): UploadedFile
    {
        return new UploadedFile(
            tempnam(sys_get_temp_dir(), 'zc-empty'),
            $name,
            'image/jpeg',
            UPLOAD_ERR_NO_FILE,
            true
        );
    }

    private function rememberFiles(Product $product): void
    {
        foreach (Productimage::query()->where('product_id', $product->id)->pluck('image') as $path) {
            $this->createdFiles[] = (string) $path;
        }

        foreach (DB::table('product_variants')->where('product_id', $product->id)->whereNotNull('image')->pluck('image') as $path) {
            $this->createdFiles[] = (string) $path;
        }
    }

    public function test_variant_and_multi_image_store(): void
    {
        DB::beginTransaction();

        try {
            $admin = $this->admin();
            $categoryId = $this->categoryId();
            [, $redId, $blueId] = $this->attribute('ZC Color', 'Red', 'Blue');
            [, $sizeMId, $sizeLId] = $this->attribute('ZC Size', 'M', 'L');

            $name = 'ZC Variant Product ' . uniqid();
            $createPayload = [
                'name' => $name,
                'category_id' => $categoryId,
                'new_price' => 1200,
                'purchase_price' => 800,
                'description' => '<p>variant store test</p>',
                'status' => 1,
                'image' => [
                    UploadedFile::fake()->create('zc-front.jpg', 60, 'image/jpeg'),
                    UploadedFile::fake()->create('zc-gallery-one.jpg', 60, 'image/jpeg'),
                    UploadedFile::fake()->create('zc-gallery-two.jpg', 60, 'image/jpeg'),
                    // A browser posts an empty entry for every blank file input;
                    // validation must ignore it instead of rejecting the form.
                    3 => $this->emptyUpload('zc-empty-gallery.jpg'),
                ],
                'title' => ['Front photo'],
                'variant_attrs_ids' => [
                    json_encode(['ids' => ['color' => $redId, 'size' => $sizeMId]]),
                    json_encode(['ids' => ['color' => $blueId, 'size' => $sizeLId]]),
                ],
                'variant_attrs_text' => [
                    json_encode(['attrs' => ['Color' => 'Red', 'Size' => 'M']]),
                    json_encode(['attrs' => ['Color' => 'Blue', 'Size' => 'L']]),
                ],
                'variant_purchase_price' => [700, 750],
                'variant_sale_price' => [1000, 1100],
                'variant_discount_price' => [900, 990],
                'variant_initial_stock' => [3, 4],
                // Only the SECOND row has a photo: the first row's empty input is
                // dropped by the form hook, so this proves keyed alignment.
                'variant_image' => [
                    0 => $this->emptyUpload('zc-empty-variant.jpg'),
                    1 => UploadedFile::fake()->create('zc-variant-blue.jpg', 40, 'image/jpeg'),
                ],
            ];

            $this->actingAs($admin)->post(route('products.store'), $createPayload)->assertRedirect();

            $product = Product::query()->where('name', $name)->first();
            $this->assertNotNull($product, 'Product create did not store the product.');
            $this->rememberFiles($product);

            // 1. Multiple gallery images are all stored, with usable titles.
            $images = Productimage::query()->where('product_id', $product->id)->orderBy('id')->get();
            $this->assertCount(3, $images, 'every uploaded gallery image must be stored');
            $this->assertSame('Front photo', $images[0]->title, 'the main image keeps the submitted title');
            foreach ($images as $image) {
                $this->assertNotSame('', (string) $image->title, 'a stored image must never have an empty title');
                $this->assertFileExists(public_path($image->image));
            }

            // 2. Each color/size combination keeps its own prices, stock and image.
            $variants = DB::table('product_variants')->where('product_id', $product->id)->orderBy('id')->get();
            $default = $variants->first(fn ($v) => str_ends_with((string) $v->sku, '-DEFAULT'));
            $this->assertNotNull($default, 'the default variant must exist');

            $combos = $variants->filter(fn ($v) => !str_ends_with((string) $v->sku, '-DEFAULT'))->values();
            $this->assertCount(2, $combos, 'both color/size combinations must be stored');

            $first = $combos[0];
            $second = $combos[1];

            $firstAttrs = json_decode((string) $first->attributes_json, true);
            $secondAttrs = json_decode((string) $second->attributes_json, true);
            $this->assertSame('Red', $firstAttrs['attrs']['Color'] ?? null);
            $this->assertSame('M', $firstAttrs['attrs']['Size'] ?? null);
            $this->assertSame('Blue', $secondAttrs['attrs']['Color'] ?? null);
            $this->assertSame('L', $secondAttrs['attrs']['Size'] ?? null);

            $this->assertSame(700, (int) $first->purchase_price);
            $this->assertSame(1000, (int) $first->sale_price);
            $this->assertSame(900, (int) $first->discount_price);
            $this->assertSame(750, (int) $second->purchase_price);
            $this->assertSame(1100, (int) $second->sale_price);
            $this->assertSame(990, (int) $second->discount_price);
            $this->assertSame(1000, (int) $first->price_override, 'price_override mirrors the sale price');

            $this->assertNull($first->image, 'the photo-less first row must not borrow the second row image');
            $this->assertNotNull($second->image, 'the second row keeps its own photo');
            $this->assertFileExists(public_path($second->image));

            $stocks = DB::table('variant_stocks')
                ->whereIn('variant_id', [$first->id, $second->id])
                ->pluck('on_hand', 'variant_id');
            $this->assertSame(3, (int) $stocks[$first->id]);
            $this->assertSame(4, (int) $stocks[$second->id]);
            $this->assertSame(7, (int) $product->fresh()->stockOnHand(), 'product stock equals the variant stock sum');

            // 3. Edit keeps old images, saves edited titles and appends keyed new uploads.
            $existing = Productimage::query()->where('product_id', $product->id)->orderBy('id')->get();
            $newImage = UploadedFile::fake()->create('zc-added-one.jpg', 30, 'image/jpeg');
            $newImageTwo = UploadedFile::fake()->create('zc-added-two.jpg', 30, 'image/jpeg');
            $emptyTemp = tempnam(sys_get_temp_dir(), 'zc-empty');
            $emptyFile = new UploadedFile($emptyTemp, 'empty.jpg', 'image/jpeg', UPLOAD_ERR_NO_FILE, true);

            $updatePayload = [
                'id' => $product->id,
                'name' => $name . ' edited',
                'category_id' => $categoryId,
                'new_price' => 1300,
                'purchase_price' => 900,
                'description' => '<p>updated</p>',
                'status' => 1,
                'existing_title' => [
                    $existing[0]->id => 'Front renamed',
                    $existing[2]->id => 'Gallery two renamed',
                ],
                'new_image' => [
                    10 => $newImage,
                    11 => $newImageTwo,
                    12 => $emptyFile,
                ],
                'new_title' => [
                    10 => 'Added one',
                    11 => 'Added two',
                ],
            ];

            $this->actingAs($admin)->post(route('products.update'), $updatePayload)->assertRedirect();
            $this->rememberFiles($product);

            $after = Productimage::query()->where('product_id', $product->id)->orderBy('id')->get();
            $this->assertCount(5, $after, 'old images must stay while the two new uploads are appended');
            $this->assertSame('Front renamed', $after[0]->title);
            $this->assertSame('Gallery two renamed', $after[2]->title);
            $titles = $after->pluck('title')->all();
            $this->assertContains('Added one', $titles);
            $this->assertContains('Added two', $titles);
            $this->assertNotContains('empty.jpg', $titles, 'an empty file entry must be ignored');
            foreach ($after as $image) {
                $this->assertFileExists(public_path($image->image));
            }

            fwrite(STDOUT, PHP_EOL . 'PRODUCT_VARIANT_IMAGE_STORE_OK' . PHP_EOL);
        } finally {
            DB::rollBack();
        }
    }
}
