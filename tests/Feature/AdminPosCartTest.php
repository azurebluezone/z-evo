<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\User;
use Cart;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The POS cart endpoints are small, but they run the till. A missing row id
 * used to reach the shopping-cart package and turn into a 500; these tests
 * pin the contract the JavaScript expects: 200 with the cart, 404 with a
 * message when the row is gone, and no exception either way.
 */
class AdminPosCartTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user to authenticate as.');

        return $admin;
    }

    private function addRow(int $qty = 2): string
    {
        $product = Product::query()->first();
        $this->assertNotNull($product, 'Seed a product first.');

        $row = Cart::instance('pos_shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => '',
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'product_discount' => 0,
            ],
        ]);

        return $row->rowId;
    }

    public function test_cart_details_renders_with_an_empty_cart(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.order.cart_details'))
            ->assertOk();
    }

    public function test_increment_and_decrement_change_the_quantity(): void
    {
        $this->actingAs($this->admin());
        $rowId = $this->addRow(2);

        $this->get(route('admin.order.cart_increment', ['id' => $rowId, 'qty' => 2]))->assertOk();
        $this->assertSame(3, Cart::instance('pos_shopping')->get($rowId)->qty);

        $this->get(route('admin.order.cart_decrement', ['id' => $rowId, 'qty' => 3]))->assertOk();
        $this->assertSame(2, Cart::instance('pos_shopping')->get($rowId)->qty);
    }

    public function test_decrement_at_one_removes_the_row_instead_of_leaving_zero(): void
    {
        $this->actingAs($this->admin());
        $rowId = $this->addRow(1);

        $this->get(route('admin.order.cart_decrement', ['id' => $rowId, 'qty' => 1]))->assertOk();
        $this->assertFalse(Cart::instance('pos_shopping')->content()->has($rowId));
    }

    public function test_remove_deletes_the_row(): void
    {
        $this->actingAs($this->admin());
        $rowId = $this->addRow(2);

        $this->get(route('admin.order.cart_remove', ['id' => $rowId]))->assertOk();
        $this->assertTrue(Cart::instance('pos_shopping')->content()->isEmpty());
    }

    public function test_a_stale_row_id_is_a_404_not_a_500(): void
    {
        $this->actingAs($this->admin());

        $this->getJson(route('admin.order.cart_increment', ['id' => 'missing-row', 'qty' => 1]))
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_product_discount_is_stored_on_the_row(): void
    {
        $this->actingAs($this->admin());
        $rowId = $this->addRow(2);

        $response = $this->get(route('admin.order.product_discount', ['id' => $rowId, 'discount' => 15]));
        $response->assertOk();

        // The package derives the row id from the options, so changing the
        // discount issues a new id. The page re-renders the cart from this
        // response, and the test follows the same id the browser would get.
        $updatedId = $response->json('rowId');
        $this->assertNotNull($updatedId);

        $this->assertSame(15, (int) Cart::instance('pos_shopping')->get($updatedId)->options->product_discount);
    }

    public function test_shipping_charge_returns_the_configured_amount(): void
    {
        $this->actingAs($this->admin());

        $charge = ShippingCharge::query()->where('status', 1)->first();

        if (! $charge) {
            $id = DB::table('shipping_charges')->insertGetId([
                'name' => 'Test zone',
                'amount' => 120,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $charge = ShippingCharge::query()->find($id);
        }

        $response = $this->get(route('admin.order.cart_shipping', ['id' => $charge->id]));
        $response->assertOk();

        $this->assertSame((int) $charge->amount, (int) json_decode($response->getContent(), true));
    }

    public function test_an_unknown_shipping_charge_is_a_404(): void
    {
        $this->actingAs($this->admin());

        $this->getJson(route('admin.order.cart_shipping', ['id' => 999999]))
            ->assertNotFound();
    }
}
