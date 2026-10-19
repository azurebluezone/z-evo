<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\CourierFraudService;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class Phase4TikTokWiringAndVariantStockGuardTest extends TestCase
{
    protected array $createdProductIds = [];
    protected array $createdCustomerIds = [];
    protected array $createdShippingChargeIds = [];
    protected array $createdVariantIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        Cart::instance('shopping')->destroy();

        if (!empty($this->createdVariantIds)) {
            DB::table('variant_stocks')->whereIn('variant_id', $this->createdVariantIds)->delete();
            DB::table('inventory_movements')->whereIn('variant_id', $this->createdVariantIds)->delete();
            DB::table('product_variants')->whereIn('id', $this->createdVariantIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('order_details')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        if (!empty($this->createdCustomerIds)) {
            DB::table('orders')->whereIn('customer_id', $this->createdCustomerIds)->delete();
            DB::table('payments')->whereIn('customer_id', $this->createdCustomerIds)->delete();
            DB::table('shippings')->whereIn('customer_id', $this->createdCustomerIds)->delete();
            DB::table('customers')->whereIn('id', $this->createdCustomerIds)->delete();
        }

        if (!empty($this->createdShippingChargeIds)) {
            DB::table('shipping_charges')->whereIn('id', $this->createdShippingChargeIds)->delete();
        }

        Mockery::close();
        parent::tearDown();
    }

    private function createBaseProduct(array $overrides = []): object
    {
        $id = DB::table('products')->insertGetId(array_merge([
            'name' => 'Phase4 Product ' . Str::random(6),
            'slug' => 'phase4-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'P4-' . strtoupper(Str::random(8)),
            'purchase_price' => 100,
            'old_price' => 150,
            'new_price' => 120,
            'stock' => 50,
            'meta_description' => null,
            'description' => null,
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $this->createdProductIds[] = $id;

        return DB::table('products')->where('id', $id)->first();
    }

    private function createDefaultVariantWithStock(int $productId, string $productCode, int $available): int
    {
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => $productCode . '-DEFAULT',
            'title' => 'Default',
            'price_override' => null,
            'weight_gram' => null,
            'status' => 1,
            'attributes_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('variant_stocks')->insert([
            'variant_id' => $variantId,
            'on_hand' => $available,
            'reserved' => 0,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdVariantIds[] = $variantId;

        return $variantId;
    }

    public function test_add_to_cart_dispatches_tiktok_server_side_event(): void
    {
        $product = $this->createBaseProduct();

        $facebook = Mockery::mock(FacebookTrackingService::class);
        $facebook->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(FacebookTrackingService::class, $facebook);

        $tiktok = Mockery::mock(TikTokTrackingService::class);
        $tiktok->shouldReceive('isEnabled')->once()->andReturn(true);
        $tiktok->shouldReceive('sendAddToCartEvent')->once()->withArgs(function ($productData, $userData) use ($product) {
            return (int) ($productData['product_id'] ?? 0) === (int) $product->id
                && (int) ($productData['quantity'] ?? 0) === 2
                && empty($userData['email']);
        })->andReturn(true);
        $this->app->instance(TikTokTrackingService::class, $tiktok);

        $response = $this->postJson('/cart/store', [
            'id' => $product->id,
            'qty' => 2,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_checkout_dispatches_tiktok_initiate_checkout_event(): void
    {
        $product = $this->createBaseProduct();
        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => 1,
            'price' => (float) $product->new_price,
            'options' => [
                'image' => '',
                'old_price' => (int) $product->old_price,
                'slug' => $product->slug,
                'purchase_price' => (int) $product->purchase_price,
                'product_size' => '',
                'product_color' => '',
            ],
        ]);

        $facebook = Mockery::mock(FacebookTrackingService::class);
        $facebook->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(FacebookTrackingService::class, $facebook);

        $tiktok = Mockery::mock(TikTokTrackingService::class);
        $tiktok->shouldReceive('isEnabled')->once()->andReturn(true);
        $tiktok->shouldReceive('sendInitiateCheckoutEvent')->once()->withArgs(function ($checkoutData) use ($product) {
            return (int) ($checkoutData['num_items'] ?? 0) === 1
                && in_array((int) $product->id, array_map('intval', $checkoutData['product_ids'] ?? []), true);
        })->andReturn(true);
        $this->app->instance(TikTokTrackingService::class, $tiktok);

        $response = $this->get('/customer/checkout');
        $response->assertOk();
    }

    public function test_order_save_dispatches_tiktok_purchase_event(): void
    {
        $product = $this->createBaseProduct();
        $variantId = $this->createDefaultVariantWithStock((int) $product->id, (string) $product->product_code, 10);

        $shippingChargeId = DB::table('shipping_charges')->insertGetId([
            'name' => 'Phase4 Area',
            'amount' => 60,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdShippingChargeIds[] = $shippingChargeId;

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Phase4 Customer',
            'slug' => 'phase4-customer-' . Str::random(6),
            'phone' => '017' . random_int(10000000, 99999999),
            'email' => 'phase4_' . Str::random(6) . '@example.test',
            'password' => bcrypt('secret123'),
            'verify' => 1,
            'image' => 'public/uploads/default/user.png',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::findOrFail($customerId);
        $this->createdCustomerIds[] = $customerId;
        $this->be($customer, 'customer');

        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => 1,
            'price' => (float) $product->new_price,
            'options' => [
                'image' => '',
                'old_price' => (int) $product->old_price,
                'slug' => $product->slug,
                'purchase_price' => (int) $product->purchase_price,
                'variant_id' => $variantId,
                'product_size' => 'variant_id:' . $variantId,
                'product_color' => 'Default',
            ],
        ]);

        $facebook = Mockery::mock(FacebookTrackingService::class);
        $facebook->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(FacebookTrackingService::class, $facebook);

        $tiktok = Mockery::mock(TikTokTrackingService::class);
        $tiktok->shouldReceive('isEnabled')->once()->andReturn(true);
        $tiktok->shouldReceive('sendPurchaseEvent')->once()->withArgs(function ($orderData, $userData) use ($product) {
            return in_array((int) $product->id, array_map('intval', $orderData['product_ids'] ?? []), true)
                && (int) ($orderData['num_items'] ?? 0) === 1
                && !empty($userData['phone']);
        })->andReturn(true);
        $this->app->instance(TikTokTrackingService::class, $tiktok);

        $courier = Mockery::mock(CourierFraudService::class);
        $courier->shouldReceive('checkAndAttachToOrder')->andReturn(null);
        $this->app->instance(CourierFraudService::class, $courier);

        $response = $this->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Phase4 Address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(302);
        $this->assertSame(0, Cart::instance('shopping')->count());
    }

    public function test_variant_stock_guard_blocks_increment_when_stock_exhausted(): void
    {
        $product = $this->createBaseProduct();
        $variantId = $this->createDefaultVariantWithStock((int) $product->id, (string) $product->product_code, 1);

        $facebook = Mockery::mock(FacebookTrackingService::class);
        $facebook->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(FacebookTrackingService::class, $facebook);

        $tiktok = Mockery::mock(TikTokTrackingService::class);
        $tiktok->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(TikTokTrackingService::class, $tiktok);

        $add = $this->postJson('/cart/store', [
            'id' => $product->id,
            'qty' => 1,
        ]);
        $add->assertOk();

        $cartItem = Cart::instance('shopping')->content()->first();
        $this->assertNotNull($cartItem);
        $this->assertSame($variantId, (int) ($cartItem->options->variant_id ?? 0));

        $increment = $this->getJson('/cart/increment?id=' . urlencode((string) $cartItem->rowId));
        $increment->assertStatus(422);
        $increment->assertJson(['success' => false]);
    }
}
