<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Regression: admin/wishlist (Admin\WishlistController@index) must render
 * for an authenticated admin. Guests stay session-only, so the page is a
 * DB-backed list of wishlists rows (see migration
 * 2026_10_22_000002_create_wishlists_table.php).
 */
class AdminWishlistPageTest extends TestCase
{
    public function test_admin_can_view_wishlist_page(): void
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so this test cannot authenticate.');

        $response = $this->actingAs($admin)->get('/admin/wishlist');

        $response->assertOk();
        $response->assertSee('Wishlists');
    }

    public function test_guest_is_redirected_away_from_wishlist_page(): void
    {
        $response = $this->get('/admin/wishlist');

        $response->assertRedirect();
    }

    public function test_logged_in_customer_add_to_wishlist_writes_a_db_row(): void
    {
        $customer = \App\Models\Customer::query()->first();
        $product = \App\Models\Product::query()->first();

        if (! $customer || ! $product) {
            $this->markTestSkipped('No customer or product in this database.');
        }

        \App\Models\Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->delete();

        $response = $this->actingAs($customer, 'customer')
            ->post('/add-to-wishlist/' . $product->id);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('wishlists', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        // Keep the dev database clean after the probe.
        \App\Models\Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
