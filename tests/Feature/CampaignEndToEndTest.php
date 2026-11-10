<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
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
 * Full campaign path, in the order a merchant and a customer use it:
 *
 *   admin creates a campaign -> public landing page -> add to cart ->
 *   checkout -> order line snapshot -> inventory reservation.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase), so every
 * row is created with a QA prefix and deleted in tearDown. The only stand-ins
 * are the outbound courier/analytics services (mocked as disabled) and the
 * campaign image upload (the test posts a path, not a binary).
 */
class CampaignEndToEndTest extends TestCase
{
    private array $createdProductIds = [];
    private array $createdVariantIds = [];
    private array $createdCustomerIds = [];
    private array $createdShippingChargeIds = [];
    private array $createdCampaignIds = [];
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

        if (!empty($this->createdVariantIds)) {
            DB::table('variant_stocks')->whereIn('variant_id', $this->createdVariantIds)->delete();
            DB::table('inventory_movements')->whereIn('variant_id', $this->createdVariantIds)->delete();
            DB::table('product_variants')->whereIn('id', $this->createdVariantIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            // order_save may create a default variant for a product that had
            // none; delete those before the product row.
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

        parent::tearDown();
    }

    public function test_admin_campaign_reaches_order_snapshot_without_mutating_product_price(): void
    {
        $product = $this->createProduct();
        $this->createDefaultVariantWithStock((int) $product->id, (string) $product->product_code, 10);
        $this->createProductImage((int) $product->id);
        $shippingChargeId = $this->createShippingCharge();
        $customer = $this->createCustomer();
        $this->mockNonBlockingServices();

        $campaign = $this->createCampaignThroughAdmin($product);

        // actingAs() without a guard switches the *default* guard, so a
        // following be($customer, 'customer') would otherwise leave the admin
        // user on the customer guard. Authenticate the customer only after the
        // admin request has finished.
        $this->be($customer, 'customer');

        $this->assertSame((int) $product->id, (int) $campaign->product_id);
        $this->assertSame(900.0, (float) $campaign->campaign_price);
        $this->assertSame(1, (int) $campaign->campaign_price_enabled);
        $this->assertSame(0, (int) $campaign->allow_coupon);

        $landing = $this->get('/campaign/' . $campaign->slug);
        $landing->assertOk();
        $landing->assertSee((string) $product->name);
        $landing->assertSee('QA campaign product description');
        $landing->assertSee('900');
        $landing->assertSee('1200');
        $landing->assertSee('name="campaign_id"', false);
        $landing->assertSee('value="' . $campaign->id . '"', false);

        $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.checkout'));

        $cartItem = Cart::instance('shopping')->content()->first();
        $this->assertNotNull($cartItem, 'The campaign product did not reach the cart.');
        $this->assertSame(900.0, (float) $cartItem->price);
        $this->assertSame((int) $campaign->id, (int) $cartItem->options->campaign_id);
        $this->assertSame(1200.0, (float) $cartItem->options->regular_price);
        $this->assertSame('campaign', $cartItem->options->price_source);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));

        $checkout = $this->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'QA campaign address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
        $checkout->assertStatus(302);
        $checkout->assertRedirectContains('/customer/order-success/');

        $order = DB::table('orders')
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($order, 'Checkout did not create an order.');
        $this->createdOrderIds[] = (int) $order->id;

        $this->assertSame(950.0, (float) $order->amount);
        $this->assertSame(0.0, (float) $order->discount);
        $this->assertSame(50.0, (float) $order->shipping_charge);

        $detail = DB::table('order_details')->where('order_id', $order->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame(1, (int) $detail->qty);
        $this->assertSame(900.0, (float) $detail->sale_price);
        $this->assertSame(1200.0, (float) $detail->regular_price);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);

        $reservation = DB::table('inventory_reservations')->where('order_id', $order->id)->first();
        $this->assertNotNull($reservation, 'No inventory reservation was written.');
        $this->assertSame(1, (int) $reservation->qty);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));

        $productPage = $this->get('/products/show/' . $product->slug);
        $productPage->assertOk();
        $productPage->assertSee('1200');

        DB::table('campaigns')->where('id', $campaign->id)->delete();

        $afterDelete = DB::table('order_details')->where('order_id', $order->id)->first();
        $this->assertNotNull($afterDelete, 'Deleting the campaign deleted the order line.');
        $this->assertNull($afterDelete->campaign_id);
        $this->assertSame(900.0, (float) $afterDelete->sale_price);
        $this->assertSame(1200.0, (float) $afterDelete->regular_price);
    }

    private function createCampaignThroughAdmin(object $product): object
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user exists, so the campaign cannot be created through HTTP.');

        $name = 'QA Campaign E2E ' . Str::random(6);

        $response = $this->actingAs($admin, 'web')->post('/admin/campaign/save', [
            'name' => $name,
            'short_description' => 'QA campaign short description',
            'description' => 'QA campaign description',
            'review' => 'QA review',
            'campaign_type' => 'product',
            'template_id' => 3,
            'product_id' => $product->id,
            'status' => 1,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'image_one' => 'public/uploads/campaign/qa-e2e.webp',
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('campaign.index'));
        $response->assertSessionHasNoErrors();

        $campaign = DB::table('campaigns')->where('name', $name)->latest('id')->first();
        $this->assertNotNull($campaign, 'The admin campaign POST did not create a row.');
        $this->createdCampaignIds[] = (int) $campaign->id;

        return $campaign;
    }

    private function createProduct(): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'QA Campaign Product ' . Str::random(6),
            'slug' => 'qa-campaign-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'QAC-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'QA campaign product description',
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

    private function createProductImage(int $productId): void
    {
        DB::table('productimages')->insert([
            'product_id' => $productId,
            'image' => 'public/uploads/product/qa-campaign.webp',
            'title' => 'QA campaign image',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function createShippingCharge(): int
    {
        $id = DB::table('shipping_charges')->insertGetId([
            'name' => 'QA Campaign Area ' . Str::random(4),
            'amount' => 50,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdShippingChargeIds[] = $id;

        return $id;
    }

    private function createCustomer(): Customer
    {
        $id = DB::table('customers')->insertGetId([
            'name' => 'QA Campaign Customer',
            'slug' => 'qa-campaign-customer-' . Str::random(6),
            'phone' => '019' . random_int(10000000, 99999999),
            'email' => 'qa_campaign_' . Str::random(6) . '@example.test',
            'password' => bcrypt('secret123'),
            'verify' => 1,
            'image' => 'public/uploads/default/user.png',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdCustomerIds[] = $id;

        return Customer::findOrFail($id);
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

        // Ga4TrackingService is final, so Mockery cannot subclass it. Its
        // sendPurchase() is wrapped in try/catch and only runs when
        // tracking.ga4.server_side is on; the test environment leaves it off,
        // so the real service is a no-op here.
        $this->assertFalse(app(Ga4TrackingService::class)->isEnabled());
    }
}
