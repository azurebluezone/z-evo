<?php

namespace Tests\Feature;

use App\Models\AbandonedCart;
use App\Models\User;
use App\Services\Analytics\CartActivityRecorder;
use App\Services\OrderPlacementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 item 5 — the cart lifecycle is stored, not inferred.
 *
 * The four claims pinned here:
 *   1. a cart mutation stores one row per cart key and a second mutation
 *      refreshes it instead of creating a duplicate;
 *   2. `markAbandoned()` moves only the carts idle past the window;
 *   3. an order placed through OrderPlacementService converts the row and
 *      stores the order id;
 *   4. the report prints the stored status and the recovery action writes
 *      recovered_at.
 *
 * The suite runs against the live MySQL connection, so the whole test class is
 * wrapped in a transaction and rolls back.
 *
 * Marker: ABANDONED_CART_OK
 */
class AbandonedCartTest extends TestCase
{
    use DatabaseTransactions;
    use GrantsVariantStock;

    private function recorder(): CartActivityRecorder
    {
        return app(CartActivityRecorder::class);
    }

    private function cartKey(string $label): string
    {
        return 'test-cart-'.$label.'-'.Str::uuid();
    }

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($admin, 'No Admin user, so the abandoned cart report cannot be authenticated.');

        return $admin;
    }

    private function createCustomer(): int
    {
        return (int) DB::table('customers')->insertGetId([
            'name' => 'Cart Test Customer '.Str::random(6),
            'slug' => 'cart-test-customer-'.Str::random(8),
            'phone' => '016'.random_int(10000000, 99999999),
            'email' => 'cart-test-'.Str::random(8).'@example.test',
            'balance' => 0,
            'password' => bcrypt('secret'),
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProduct(): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'Cart Test Product '.Str::random(6),
            'slug' => 'cart-test-product-'.Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'CT-'.strtoupper(Str::random(8)),
            'purchase_price' => 250,
            'old_price' => 600,
            'new_price' => 500,
            'meta_description' => null,
            'description' => null,
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = DB::table('products')->where('id', $id)->first();

        $this->grantDefaultStock($product);

        return $product;
    }

    public function test_a_cart_mutation_stores_one_row_and_the_next_one_refreshes_it(): void
    {
        $key = $this->cartKey('snapshot');

        $first = $this->recorder()->record(2, 1500.00, $key);

        $this->assertNotNull($first);
        $this->assertSame(AbandonedCart::STATUS_OPEN, $first->status);
        $this->assertSame(2, (int) $first->item_count);
        $this->assertSame('1500.00', (string) $first->subtotal);
        $this->assertSame(40, strlen((string) $first->recovery_token));

        $firstSeen = $first->first_seen_at;

        $second = $this->recorder()->record(3, 2100.50, $key);

        $this->assertSame($first->id, $second->id, 'A second mutation must reuse the cart row.');
        $this->assertSame(1, AbandonedCart::query()->where('cart_key', $key)->count());

        $fresh = $second->fresh();
        $this->assertSame(3, (int) $fresh->item_count);
        $this->assertSame('2100.50', (string) $fresh->subtotal);
        $this->assertTrue(
            $fresh->first_seen_at->equalTo($firstSeen),
            'first_seen_at must keep the moment the cart was first seen.'
        );

        $this->assertNull(
            $this->recorder()->record(0, 0, $key.'-empty'),
            'An empty cart has nothing to abandon and must not create a row.'
        );
    }

    public function test_only_carts_idle_past_the_window_are_marked_abandoned(): void
    {
        $staleKey = $this->cartKey('stale');
        $freshKey = $this->cartKey('fresh');

        $this->recorder()->record(1, 500.00, $staleKey);
        $this->recorder()->record(1, 700.00, $freshKey);

        DB::table('abandoned_carts')
            ->where('cart_key', $staleKey)
            ->update(['last_activity_at' => now()->subHours(30)]);

        $flagged = $this->recorder()->markAbandoned(24);

        $this->assertGreaterThanOrEqual(1, $flagged);

        $stale = AbandonedCart::query()->where('cart_key', $staleKey)->firstOrFail();
        $fresh = AbandonedCart::query()->where('cart_key', $freshKey)->firstOrFail();

        $this->assertSame(AbandonedCart::STATUS_ABANDONED, $stale->status);
        $this->assertNotNull($stale->abandoned_at);
        $this->assertSame(AbandonedCart::STATUS_OPEN, $fresh->status, 'A cart active now must stay open.');
        $this->assertNull($fresh->abandoned_at);

        config(['reports.abandoned_cart_window_hours' => 3]);
        $this->assertSame(3, $this->recorder()->windowHours());
    }

    public function test_placing_an_order_converts_the_tracked_cart(): void
    {
        $key = $this->cartKey('converted');
        $customerId = $this->createCustomer();
        $product = $this->createProduct();

        $this->recorder()->record(1, 500.00, $key, $customerId);

        $order = app(OrderPlacementService::class)->place(
            [[
                'product_id' => (int) $product->id,
                'name' => (string) $product->name,
                'qty' => 1,
                'price' => 500,
                'regular_price' => 500,
                'purchase_price' => 250,
            ]],
            [
                'customer_id' => $customerId,
                'name' => 'Cart Test Buyer',
                'phone' => '01600000003',
                'address' => 'Test address',
                'area' => 'ঢাকার ভিতরে',
                'payment_method' => 'Cash On Delivery',
            ],
            [
                'shipping_fee' => 60,
                'fraud_check' => false,
                'notify' => false,
                'cart_key' => $key,
            ]
        );

        $cart = AbandonedCart::query()->where('cart_key', $key)->firstOrFail();

        $this->assertSame(AbandonedCart::STATUS_CONVERTED, $cart->status);
        $this->assertSame((int) $order->id, (int) $cart->order_id);
        $this->assertNotNull($cart->converted_at);
        $this->assertFalse($cart->isRecoverable());
    }

    public function test_the_report_prints_the_stored_status_and_the_recovery_action_persists(): void
    {
        $key = $this->cartKey('report');

        $this->recorder()->record(2, 900.00, $key);

        DB::table('abandoned_carts')
            ->where('cart_key', $key)
            ->update(['last_activity_at' => now()->subHours(48)]);

        $this->recorder()->markAbandoned(24);

        $cart = AbandonedCart::query()->where('cart_key', $key)->firstOrFail();
        $this->assertSame(AbandonedCart::STATUS_ABANDONED, $cart->status);

        $page = $this->actingAs($this->admin())->get(route('admin.reports.abandoned-carts', [
            'status' => 'abandoned',
        ]));

        $page->assertOk();
        $page->assertSee('data-abandoned-cart-row="'.$key.'"', false);
        $page->assertSee('data-abandoned-cart-status="abandoned"', false);
        $page->assertSee('data-abandoned-cart-items="2"', false);
        $page->assertSee('data-abandoned-cart-subtotal="900.00"', false);

        $recovered = $this->actingAs($this->admin())->post(
            route('admin.reports.abandoned-carts.recovered', $cart->id)
        );

        $recovered->assertRedirect();

        $cart->refresh();
        $this->assertSame(AbandonedCart::STATUS_RECOVERED, $cart->status);
        $this->assertNotNull($cart->recovered_at, 'Recovery must be a stored timestamp, not an inference.');
    }
}
