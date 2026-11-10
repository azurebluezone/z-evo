<?php

namespace Tests\Feature;

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
 * Checkout campaign revalidation.
 *
 * A cart filled while a campaign was live must not be charged that price after
 * the campaign ends, is disabled, changes price, or disappears. order_save
 * reprices every campaign line from CampaignPricing and refuses to place the
 * order when anything changed, so the customer confirms the new total.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase); every row
 * it creates is deleted in tearDown.
 */
class CampaignCheckoutRevalidationTest extends TestCase
{
    private array $createdProductIds = [];
    private array $createdCampaignIds = [];
    private array $createdCustomerIds = [];
    private array $createdShippingChargeIds = [];
    private array $createdOrderIds = [];

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

        if (!empty($this->createdCustomerIds)) {
            DB::table('customers')->whereIn('id', $this->createdCustomerIds)->delete();
        }

        if (!empty($this->createdShippingChargeIds)) {
            DB::table('shipping_charges')->whereIn('id', $this->createdShippingChargeIds)->delete();
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_expired_campaign_line_is_repriced_and_order_is_not_placed(): void
    {
        [$product, $campaign, $customer, $shippingChargeId] = $this->prepareStaleCart();

        DB::table('campaigns')->where('id', $campaign->id)->update([
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->placeOrder($customer, $shippingChargeId);

        $response->assertRedirect(route('customer.checkout'));
        $this->assertSame(0, DB::table('orders')->where('customer_id', $customer->id)->count());

        $item = Cart::instance('shopping')->content()->first();
        $this->assertNotNull($item);
        $this->assertSame(1200.0, (float) $item->price);
        $this->assertNull(data_get($item->options, 'campaign_id'));
        $this->assertNull(data_get($item->options, 'campaign_price'));
        $this->assertSame('product', data_get($item->options, 'price_source'));
        $this->assertSame(1200.0, (float) data_get($item->options, 'regular_price'));
        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_active_campaign_line_still_places_the_order_at_campaign_price(): void
    {
        [$product, $campaign, $customer, $shippingChargeId] = $this->prepareStaleCart();

        $response = $this->placeOrder($customer, $shippingChargeId);

        $response->assertRedirectContains('/customer/order-success/');

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order, 'Checkout did not create an order.');
        $this->createdOrderIds[] = (int) $order->id;

        $this->assertSame(950.0, (float) $order->amount);
        $this->assertSame(0.0, (float) $order->discount);

        $detail = DB::table('order_details')->where('order_id', $order->id)->first();
        $this->assertSame(900.0, (float) $detail->sale_price);
        $this->assertSame(1200.0, (float) $detail->regular_price);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);
        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_campaign_price_change_after_add_reprices_before_charging(): void
    {
        [$product, $campaign, $customer, $shippingChargeId] = $this->prepareStaleCart();

        DB::table('campaigns')->where('id', $campaign->id)->update(['campaign_price' => 800]);

        $response = $this->placeOrder($customer, $shippingChargeId);

        $response->assertRedirect(route('customer.checkout'));
        $this->assertSame(0, DB::table('orders')->where('customer_id', $customer->id)->count());

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(800.0, (float) $item->price);
        $this->assertSame((int) $campaign->id, (int) data_get($item->options, 'campaign_id'));
        $this->assertSame(800.0, (float) data_get($item->options, 'campaign_price'));
        $this->assertSame('campaign', data_get($item->options, 'price_source'));

        // The reviewed cart can now be charged at the live campaign price.
        $retry = $this->placeOrder($customer, $shippingChargeId);
        $retry->assertRedirectContains('/customer/order-success/');

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->createdOrderIds[] = (int) $order->id;
        $this->assertSame(850.0, (float) $order->amount);
        $this->assertSame(800.0, (float) DB::table('order_details')->where('order_id', $order->id)->value('sale_price'));
        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_disabled_campaign_after_add_reprices_to_product_price(): void
    {
        [$product, $campaign, $customer, $shippingChargeId] = $this->prepareStaleCart();

        DB::table('campaigns')->where('id', $campaign->id)->update(['status' => 0]);

        $response = $this->placeOrder($customer, $shippingChargeId);

        $response->assertRedirect(route('customer.checkout'));
        $this->assertSame(0, DB::table('orders')->where('customer_id', $customer->id)->count());

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(1200.0, (float) $item->price);
        $this->assertNull(data_get($item->options, 'campaign_id'));
        $this->assertSame('product', data_get($item->options, 'price_source'));
    }

    public function test_deleted_campaign_after_add_reprices_to_product_price(): void
    {
        [$product, $campaign, $customer, $shippingChargeId] = $this->prepareStaleCart();

        DB::table('campaigns')->where('id', $campaign->id)->delete();
        $this->createdCampaignIds = array_values(array_diff($this->createdCampaignIds, [(int) $campaign->id]));

        $response = $this->placeOrder($customer, $shippingChargeId);

        $response->assertRedirect(route('customer.checkout'));
        $this->assertSame(0, DB::table('orders')->where('customer_id', $customer->id)->count());

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(1200.0, (float) $item->price);
        $this->assertNull(data_get($item->options, 'campaign_id'));
        $this->assertSame('product', data_get($item->options, 'price_source'));
    }

    /**
     * Add a campaign line while the campaign is live, then return the pieces
     * the caller needs to mutate the campaign and attempt checkout.
     *
     * @return array{0: object, 1: object, 2: Customer, 3: int}
     */
    private function prepareStaleCart(): array
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900);

        $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.checkout'));

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(900.0, (float) $item->price, 'The campaign price did not reach the cart.');

        $shippingChargeId = DB::table('shipping_charges')->insertGetId([
            'name' => 'QA Revalidation Area ' . Str::random(4),
            'amount' => 50,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdShippingChargeIds[] = $shippingChargeId;

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'QA Revalidation Customer',
            'slug' => 'qa-revalidation-customer-' . Str::random(6),
            'phone' => '019' . random_int(10000000, 99999999),
            'email' => 'qa_revalidation_' . Str::random(6) . '@example.test',
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

        return [$product, $campaign, $customer, $shippingChargeId];
    }

    private function placeOrder(Customer $customer, int $shippingChargeId): \Illuminate\Testing\TestResponse
    {
        return $this->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'QA revalidation address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
    }

    private function createProduct(float $newPrice): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'QA Revalidation Product ' . Str::random(6),
            'slug' => 'qa-revalidation-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'QRV-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => $newPrice,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'QA revalidation product description',
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

    private function createCampaign(int $productId, float $price): object
    {
        $id = DB::table('campaigns')->insertGetId([
            'name' => 'QA Revalidation Campaign ' . Str::random(6),
            'slug' => 'qa-revalidation-campaign-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'QA revalidation campaign',
            'review' => 'QA review',
            'description' => 'QA revalidation campaign description',
            'image_one' => '',
            'status' => '1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'product_id' => $productId,
            'campaign_price' => $price,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdCampaignIds[] = $id;

        return DB::table('campaigns')->where('id', $id)->first();
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
