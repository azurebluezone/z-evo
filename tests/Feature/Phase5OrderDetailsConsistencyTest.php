<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CookiePage;
use App\Models\Customer;
use App\Models\User;
use App\Services\CourierFraudService;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use App\Support\Consent;
use App\Support\FrontendTheme;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class Phase5OrderDetailsConsistencyTest extends TestCase
{
    protected array $createdProductIds = [];
    protected array $createdCustomerIds = [];
    protected array $createdShippingChargeIds = [];
    protected array $createdVariantIds = [];
    protected array $createdCouponIds = [];

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

        if (!empty($this->createdCouponIds)) {
            DB::table('coupon_usages')->whereIn('coupon_id', $this->createdCouponIds)->delete();
            DB::table('coupon_products')->whereIn('coupon_id', $this->createdCouponIds)->delete();
            DB::table('coupons')->whereIn('id', $this->createdCouponIds)->delete();
        }

        if (!empty($this->createdCustomerIds)) {
            // Order placement notifies every admin. Those rows point at an
            // order this test is about to delete, so leaving them behind would
            // put a "New Order Placed" message about a phantom order in the
            // panel.
            $orderIds = DB::table('orders')
                ->whereIn('customer_id', $this->createdCustomerIds)
                ->pluck('id');

            foreach ($orderIds as $orderId) {
                DB::table('notifications')
                    ->where('data', 'like', '%"order_id":' . (int) $orderId . '%')
                    ->delete();
            }

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
            'name' => 'Phase5 Product ' . Str::random(6),
            'slug' => 'phase5-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'P5-' . strtoupper(Str::random(8)),
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

    /**
     * @return array{0: object, 1: int, 2: int, 3: Customer}
     */
    private function prepareCheckoutScenario(array $productOverrides = []): array
    {
        $product = $this->createBaseProduct(array_merge([
            'new_price' => 120,
            'purchase_price' => 90,
        ], $productOverrides));
        $variantId = $this->createDefaultVariantWithStock((int) $product->id, (string) $product->product_code, 10);

        $shippingChargeId = DB::table('shipping_charges')->insertGetId([
            'name' => 'Phase5 Area',
            'amount' => 50,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdShippingChargeIds[] = $shippingChargeId;

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Phase5 Customer',
            'slug' => 'phase5-customer-' . Str::random(6),
            'phone' => '018' . random_int(10000000, 99999999),
            'email' => 'phase5_' . Str::random(6) . '@example.test',
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

        return [$product, $variantId, $shippingChargeId, $customer];
    }

    private function addProductToCart(object $product, int $variantId, int $qty = 2, float $unitPrice = 120.0): void
    {
        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $unitPrice,
            'options' => [
                'image' => '',
                'old_price' => (int) $product->old_price,
                'slug' => $product->slug,
                'purchase_price' => (float) $product->purchase_price,
                'variant_id' => $variantId,
                'product_size' => 'variant_id:' . $variantId,
                'product_color' => 'Default',
            ],
        ]);
    }

    private function mockNonBlockingServices(): void
    {
        $courier = Mockery::mock(CourierFraudService::class);
        $courier->shouldReceive('checkAndAttachToOrder')->andReturn(null);
        $this->app->instance(CourierFraudService::class, $courier);

        $facebook = Mockery::mock(FacebookTrackingService::class);
        $facebook->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(FacebookTrackingService::class, $facebook);

        $tiktok = Mockery::mock(TikTokTrackingService::class);
        $tiktok->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(TikTokTrackingService::class, $tiktok);
    }

    private function createCoupon(float $value = 20.0, ?int $restrictedToProductId = null): Coupon
    {
        $coupon = Coupon::create([
            'code' => 'P5-' . strtoupper(Str::random(8)),
            'type' => 'fixed',
            'value' => $value,
            'min_amount' => null,
            'max_discount' => null,
            'start_at' => null,
            'end_at' => null,
            'usage_limit' => 1,
            'used_count' => 0,
            'status' => 1,
        ]);
        $this->createdCouponIds[] = $coupon->id;

        if ($restrictedToProductId !== null) {
            $coupon->products()->attach($restrictedToProductId);
        }

        return $coupon;
    }

    public function test_checkout_to_order_details_stays_consistent_end_to_end(): void
    {
        [$product, $variantId, $shippingChargeId, $customer] = $this->prepareCheckoutScenario();

        $qty = 2;
        $unitPrice = 120.0;
        $discount = 20.0;
        $expectedSubtotal = (int) ($unitPrice * $qty);
        $expectedAmount = $expectedSubtotal + 50 - (int) $discount;

        $this->addProductToCart($product, $variantId, $qty, $unitPrice);
        $this->mockNonBlockingServices();

        $coupon = $this->createCoupon($discount);

        $response = $this->withSession([
            'discount' => $discount,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Phase5 Address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(302);

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame($expectedAmount, (int) $order->amount);
        $this->assertSame((int) $discount, (int) $order->discount);

        $detail = DB::table('order_details')->where('order_id', (int) $order->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame((int) $product->id, (int) $detail->product_id);
        $this->assertSame($qty, (int) $detail->qty);
        $this->assertSame((int) $unitPrice, (int) $detail->sale_price);
        $this->assertSame('variant_id:' . $variantId, (string) $detail->product_size);
        $this->assertSame('Default', (string) $detail->product_color);

        $subtotalFromDetails = (int) DB::table('order_details')
            ->where('order_id', (int) $order->id)
            ->selectRaw('SUM(sale_price * qty) as subtotal')
            ->value('subtotal');

        $this->assertSame($expectedSubtotal, $subtotalFromDetails);
        $this->assertSame(
            (int) $order->amount,
            $subtotalFromDetails + (int) $order->shipping_charge - (int) $order->discount
        );

        $this->assertSame(1, (int) DB::table('coupons')->where('id', $coupon->id)->value('used_count'));
        $this->assertSame(1, DB::table('coupon_usages')
            ->where('coupon_id', $coupon->id)
            ->where('order_id', $order->id)
            ->count());

        $successPage = $this->get('/customer/order-success/' . (int) $order->id);
        $successPage->assertOk();
        $successPage->assertSee((string) $product->name);
        $successPage->assertSee((string) number_format($expectedSubtotal, 0), false);

        $invoicePage = $this->get('/customer/invoice?id=' . (int) $order->id);
        $invoicePage->assertOk();
        $invoicePage->assertSee((string) $expectedSubtotal, false);
    }

    public function test_session_discount_without_a_real_coupon_is_ignored(): void
    {
        [$product, $variantId, $shippingChargeId, $customer] = $this->prepareCheckoutScenario();

        $this->addProductToCart($product, $variantId, 2, 120.0);
        $this->mockNonBlockingServices();

        $response = $this->withSession(['discount' => 20])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Phase5 Address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(302);

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame(290, (int) $order->amount);
        $this->assertSame(0, (int) $order->discount);
        $this->assertSame(0, DB::table('coupon_usages')->where('order_id', $order->id)->count());
    }

    public function test_restricted_coupon_only_discounts_eligible_cart_lines(): void
    {
        [$product, $variantId, $shippingChargeId, $customer] = $this->prepareCheckoutScenario();
        $otherProduct = $this->createBaseProduct();

        $this->addProductToCart($product, $variantId, 2, 120.0);
        $this->mockNonBlockingServices();

        $coupon = $this->createCoupon(20.0, (int) $otherProduct->id);

        // Apply-time and order-time share this method, so the ineligible cart
        // must produce zero before the order is even submitted.
        $this->assertSame(0.0, $coupon->calculateDiscountForItems(Cart::instance('shopping')->content()));

        $response = $this->withSession([
            'discount' => 20,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Phase5 Address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(302);

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame(290, (int) $order->amount);
        $this->assertSame(0, (int) $order->discount);
        $this->assertSame(0, (int) DB::table('coupons')->where('id', $coupon->id)->value('used_count'));
        $this->assertSame(0, DB::table('coupon_usages')->where('coupon_id', $coupon->id)->count());
    }

    /**
     * The coupon controller has always been able to produce 20.50; the old INT
     * columns silently rounded it away. This walks the real checkout path and
     * checks the paisa survives in orders, order_details and payments.
     */
    public function test_order_money_columns_keep_paisa_through_checkout(): void
    {
        [$product, $variantId, $shippingChargeId, $customer] = $this->prepareCheckoutScenario([
            'new_price' => 120,
            'purchase_price' => 90,
        ]);

        $this->addProductToCart($product, $variantId, 2, 120.75);
        $this->mockNonBlockingServices();

        $coupon = $this->createCoupon(20.50);

        $response = $this->withSession([
            'discount' => 20.50,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Phase5 Address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(302);

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('271.00', (string) $order->amount);
        $this->assertSame('20.50', (string) $order->discount);
        $this->assertSame('50.00', (string) $order->shipping_charge);

        $detail = DB::table('order_details')->where('order_id', (int) $order->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame('120.75', (string) $detail->sale_price);
        $this->assertSame('90.00', (string) $detail->purchase_price);

        $payment = DB::table('payments')->where('order_id', (int) $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('271.00', (string) $payment->amount);

        $this->assertSame('20.50', (string) DB::table('coupon_usages')
            ->where('order_id', (int) $order->id)
            ->value('discount_amount'));
    }

    public function test_cookie_page_prints_the_locked_retention_truth(): void
    {
        $response = $this->get('/privacy/cookies');

        $response->assertOk();
        $response->assertSee('id="retention-notice"', false);
        $response->assertSee(Consent::RETENTION_NOTICE);
        $response->assertSee('for 1 year from the date it is collected');
    }

    public function test_admin_can_edit_cookie_copy_but_not_the_retention_truth(): void
    {
        $original = CookiePage::query()->first();
        $originalValues = $original?->only(['title', 'intro', 'body', 'updated_by']);

        try {
            $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();

            $this->actingAs($admin)
                ->get('/admin/cookie-page')
                ->assertOk()
                ->assertSee(Consent::RETENTION_NOTICE)
                ->assertSee('Data retention - locked');

            $this->actingAs($admin)
                ->post('/admin/cookie-page', [
                    'title' => 'Our cookie promise',
                    'intro' => 'Edited in the test.',
                    'body' => '<p>Editable policy copy.</p>',
                    // A crafted field must not become a column write: the
                    // controller validates only title/intro/body.
                    'retention_notice' => 'Nothing is stored.',
                ])
                ->assertRedirect(route('cookie-page.edit'));

            $page = CookiePage::query()->firstOrFail();
            $this->assertSame('Our cookie promise', $page->title);
            $this->assertSame('<p>Editable policy copy.</p>', $page->body);
            $this->assertArrayNotHasKey('retention_notice', $page->getAttributes());

            $this->get('/privacy/cookies')
                ->assertOk()
                ->assertSee('Our cookie promise')
                ->assertSee(Consent::RETENTION_NOTICE)
                ->assertDontSee('Nothing is stored.');
        } finally {
            if ($original !== null && $originalValues !== null) {
                // A fresh query, not $original->update(): the model still holds
                // the pre-test attributes, so Eloquent sees no dirty field and
                // would skip the write.
                CookiePage::query()->whereKey($original->getKey())->update($originalValues);
            } else {
                CookiePage::query()->delete();
            }
        }
    }

    public function test_custom_page_renders_through_the_active_modern_theme(): void
    {
        FrontendTheme::forget();

        try {
            $response = $this->withSession([FrontendTheme::PREVIEW_KEY => FrontendTheme::MODERN])
                ->get('/terms-and-conditions');

            $response->assertOk();
            $response->assertSee('data-purpose="custom-page"', false);
            $response->assertSee('fm-page__title', false);
            $response->assertDontSee('createpage-section', false);
        } finally {
            FrontendTheme::clearPreview();
            FrontendTheme::forget();
        }
    }
}
