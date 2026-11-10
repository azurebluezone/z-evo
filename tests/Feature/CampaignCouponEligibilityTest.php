<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Services\CourierFraudService;
use App\Services\FacebookTrackingService;
use App\Services\Ga4TrackingService;
use App\Services\TikTokTrackingService;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Campaign coupon policy.
 *
 * Pins the approved rule: a campaign cart line is outside the coupon base by
 * default, joins it only when campaigns.allow_coupon is on, and is discounted
 * on the campaign price rather than the product's regular price. The coupon
 * model reads the switch from the database on every calculation, so apply-time
 * and order-time can never disagree.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase); every row
 * it creates is deleted in tearDown.
 */
class CampaignCouponEligibilityTest extends TestCase
{
    private array $createdProductIds = [];
    private array $createdCampaignIds = [];
    private array $createdCouponIds = [];
    private array $createdOrderIds = [];
    private array $createdCustomerIds = [];
    private array $createdShippingChargeIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        Cart::instance('shopping')->destroy();

        if (!empty($this->createdOrderIds)) {
            foreach ($this->createdOrderIds as $orderId) {
                DB::table('notifications')
                    ->where('data', 'like', '%"order_id":' . (int) $orderId . '%')
                    ->delete();
            }

            DB::table('inventory_reservations')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('order_details')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('payments')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('shippings')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('orders')->whereIn('id', $this->createdOrderIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            $autoVariantIds = DB::table('product_variants')
                ->whereIn('product_id', $this->createdProductIds)
                ->pluck('id')
                ->all();

            if (!empty($autoVariantIds)) {
                DB::table('inventory_reservations')->whereIn('variant_id', $autoVariantIds)->delete();
                DB::table('variant_stocks')->whereIn('variant_id', $autoVariantIds)->delete();
                DB::table('inventory_movements')->whereIn('variant_id', $autoVariantIds)->delete();
                DB::table('product_variants')->whereIn('id', $autoVariantIds)->delete();
            }

            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        if (!empty($this->createdCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdCouponIds)) {
            DB::table('coupon_usages')->whereIn('coupon_id', $this->createdCouponIds)->delete();
            DB::table('coupon_products')->whereIn('coupon_id', $this->createdCouponIds)->delete();
            DB::table('coupons')->whereIn('id', $this->createdCouponIds)->delete();
        }

        if (!empty($this->createdCustomerIds)) {
            DB::table('customers')->whereIn('id', $this->createdCustomerIds)->delete();
        }

        if (!empty($this->createdShippingChargeIds)) {
            DB::table('shipping_charges')->whereIn('id', $this->createdShippingChargeIds)->delete();
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_campaign_line_is_excluded_from_coupon_base_by_default(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, 0);
        $coupon = $this->createCoupon('percent', 10);

        $this->addCampaignLine($product, $campaign);
        $this->addNormalLine($product);

        $items = Cart::instance('shopping')->content();

        // 10% of the normal 1200 line only; the 900 campaign line is outside
        // the base even though the campaign row exists.
        $this->assertSame(1200.0, $coupon->eligibleSubtotalForItems($items));
        $this->assertSame(120.0, $coupon->calculateDiscountForItems($items));
        $this->assertSame(0, (int) DB::table('campaigns')->where('id', $campaign->id)->value('allow_coupon'));
    }

    public function test_campaign_line_joins_coupon_base_at_campaign_price_when_switch_is_on(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, 1);
        $coupon = $this->createCoupon('percent', 10);

        $this->addCampaignLine($product, $campaign);
        $this->addNormalLine($product);

        $items = Cart::instance('shopping')->content();

        // 10% of (900 campaign + 1200 normal) = 210. If the campaign line used
        // the regular price instead, the base would be 2400 and the discount
        // 240.
        $this->assertSame(2100.0, $coupon->eligibleSubtotalForItems($items));
        $this->assertSame(210.0, $coupon->calculateDiscountForItems($items));
    }

    public function test_coupon_apply_endpoint_refuses_a_campaign_only_cart_when_switch_is_off(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, 0);
        $coupon = $this->createCoupon('fixed', 100);

        $this->addCampaignLine($product, $campaign);

        $response = $this->post(route('coupon.apply'), ['coupon_code' => $coupon->code]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'error',
            'message' => 'Coupon is not applicable for this cart',
        ]);
        $this->assertNull(session('discount'));
        $this->assertNull(session('coupon_id'));
    }

    public function test_checkout_discount_excludes_campaign_line_when_switch_is_off(): void
    {
        [$product, $campaign, $coupon, $customer, $shippingChargeId] = $this->prepareCampaignCheckout(900, 0);
        $this->addCampaignLine($product, $campaign);

        $response = $this->withSession([
            'discount' => 90,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'QA coupon campaign address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(302);
        $response->assertRedirectContains('/customer/order-success/');

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order, 'Checkout did not create an order.');
        $this->createdOrderIds[] = (int) $order->id;

        // The session claimed a 90 discount, but the campaign line is the only
        // cart line and its allow_coupon switch is off, so checkout must drop
        // the coupon and keep the full campaign price.
        $this->assertSame(0.0, (float) $order->discount);
        $this->assertSame(950.0, (float) $order->amount);
        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));

        $detail = DB::table('order_details')->where('order_id', $order->id)->first();
        $this->assertSame(900.0, (float) $detail->sale_price);
        $this->assertSame(1200.0, (float) $detail->regular_price);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);
        $this->assertSame(0, (int) DB::table('coupons')->where('id', $coupon->id)->value('used_count'));
    }

    public function test_checkout_discount_uses_campaign_price_when_switch_is_on(): void
    {
        [$product, $campaign, $coupon, $customer, $shippingChargeId] = $this->prepareCampaignCheckout(900, 1);
        $this->addCampaignLine($product, $campaign);

        $response = $this->withSession([
            'discount' => 90,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'QA coupon campaign address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(302);
        $response->assertRedirectContains('/customer/order-success/');

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order, 'Checkout did not create an order.');
        $this->createdOrderIds[] = (int) $order->id;

        // 10% of the 900 campaign price, not of the 1200 regular price.
        $this->assertSame(90.0, (float) $order->discount);
        $this->assertSame(860.0, (float) $order->amount);
        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));

        $detail = DB::table('order_details')->where('order_id', $order->id)->first();
        $this->assertSame(900.0, (float) $detail->sale_price);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);
        $this->assertSame(1, (int) DB::table('coupons')->where('id', $coupon->id)->value('used_count'));
    }

    /**
     * @return array{0: object, 1: object, 2: Coupon, 3: Customer, 4: int}
     */
    private function prepareCampaignCheckout(float $campaignPrice, int $allowCoupon): array
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, $campaignPrice, $allowCoupon);
        $coupon = $this->createCoupon('percent', 10);

        $shippingChargeId = DB::table('shipping_charges')->insertGetId([
            'name' => 'QA Coupon Area ' . Str::random(4),
            'amount' => 50,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdShippingChargeIds[] = $shippingChargeId;

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'QA Coupon Customer',
            'slug' => 'qa-coupon-customer-' . Str::random(6),
            'phone' => '019' . random_int(10000000, 99999999),
            'email' => 'qa_coupon_' . Str::random(6) . '@example.test',
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

        $this->mockNonBlockingServices();

        return [$product, $campaign, $coupon, $customer, $shippingChargeId];
    }

    private function addCampaignLine(object $product, object $campaign, int $qty = 1): void
    {
        $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => $qty,
        ])->assertRedirect(route('customer.checkout'));
    }

    private function addNormalLine(object $product, int $qty = 1): void
    {
        $this->post('/landing-order', [
            'product_id' => $product->id,
            'quantity' => $qty,
        ])->assertRedirect(route('customer.checkout'));
    }

    private function createProduct(float $newPrice): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'QA Coupon Product ' . Str::random(6),
            'slug' => 'qa-coupon-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'QCP-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => $newPrice,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'QA coupon campaign product description',
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdProductIds[] = $id;

        return DB::table('products')->where('id', $id)->first();
    }

    private function createCampaign(int $productId, float $price, int $allowCoupon): object
    {
        $id = DB::table('campaigns')->insertGetId([
            'name' => 'QA Coupon Campaign ' . Str::random(6),
            'slug' => 'qa-coupon-campaign-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'QA coupon campaign',
            'review' => 'QA review',
            'description' => 'QA coupon campaign description',
            'image_one' => '',
            'status' => '1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'product_id' => $productId,
            'campaign_price' => $price,
            'campaign_price_enabled' => 1,
            'allow_coupon' => $allowCoupon,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdCampaignIds[] = $id;

        return DB::table('campaigns')->where('id', $id)->first();
    }

    private function createCoupon(string $type, float $value): Coupon
    {
        $coupon = Coupon::create([
            'code' => 'QCP-' . strtoupper(Str::random(8)),
            'type' => $type,
            'value' => $value,
            'min_amount' => null,
            'max_discount' => null,
            'start_at' => null,
            'end_at' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'status' => 1,
        ]);

        $this->createdCouponIds[] = $coupon->id;

        return $coupon;
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

        $this->assertFalse(app(Ga4TrackingService::class)->isEnabled());
    }
}
