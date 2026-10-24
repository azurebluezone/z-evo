<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Productimage;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Admin image removal is a POST with a CSRF token.
 *
 * Both image routes were GET links until this change:
 *
 *   GET admin/products/image/destroy?id=…
 *   GET admin/campaign/image/destroy?id=…
 *
 * A GET that deletes is a CSRF hole: any third-party page can drop
 * <img src="http://shop/admin/products/image/destroy?id=7"> into a browser
 * where an admin is signed in. Neither handler validated the id either, so a
 * stale id was a fatal error on `null->image`, and File::delete() was given a
 * path relative to the project root while the file actually lives under
 * public/ - the row went away and the file stayed behind.
 *
 * These tests pin the method, the id check, the row deletion and the file
 * deletion. Rows/files carry __qa-delete-test in their name and are removed in
 * tearDown.
 */
class AdminImageDeleteTest extends TestCase
{
    private const IMAGE_PATH = 'uploads/product/__qa-delete-test.jpg';

    protected function tearDown(): void
    {
        Productimage::where('image', self::IMAGE_PATH)->delete();
        File::delete(public_path(self::IMAGE_PATH));

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so this test cannot authenticate.');

        return $admin;
    }

    private function productImage(): Productimage
    {
        $product = Product::query()->where('status', 1)->first();
        $this->assertNotNull($product, 'No published product in this database.');

        $absolute = public_path(self::IMAGE_PATH);
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, 'qa');
        $this->assertFileExists($absolute);

        return Productimage::create([
            'image' => self::IMAGE_PATH,
            'title' => 'QA delete test',
            'product_id' => $product->id,
        ]);
    }

    public function test_product_image_delete_is_post_only(): void
    {
        $image = $this->productImage();

        $this->actingAs($this->admin())
            ->get('/admin/products/image/destroy?id=' . $image->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('productimages', ['id' => $image->id]);
        $this->assertFileExists(public_path(self::IMAGE_PATH));
    }

    public function test_product_image_delete_removes_the_row_and_the_file(): void
    {
        $image = $this->productImage();

        $response = $this->actingAs($this->admin())
            ->post('/admin/products/image/destroy', ['id' => $image->id]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('productimages', ['id' => $image->id]);
        $this->assertFileDoesNotExist(public_path(self::IMAGE_PATH));
    }

    public function test_product_image_delete_rejects_an_unknown_id(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/products/image/destroy', ['id' => 99999999])
            ->assertStatus(404);
    }

    public function test_product_edit_renders_a_post_delete_form_per_photo(): void
    {
        $image = $this->productImage();

        $response = $this->actingAs($this->admin())
            ->get('/admin/products/' . $image->product_id . '/edit');

        $response->assertOk();
        // The hidden form is outside the product form (nested forms are
        // invalid) and the Remove button points at it with form="...".
        $response->assertSee('id="fmRemoveProductImage' . $image->id . '"', false);
        $response->assertSee('form="fmRemoveProductImage' . $image->id . '"', false);
    }

    public function test_campaign_image_delete_is_post_only(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/campaign/image/destroy?id=1')
            ->assertStatus(405);
    }
}
