<?php

namespace Tests\Feature;

use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use App\Models\ShippingCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Landing campaign order flow.
 *
 * Pins the contract of the landing page order form:
 *  - the campaign price reaches the order line, not only the landing view;
 *  - products.new_price is never rewritten;
 *  - an expired or mismatched campaign is rejected server-side;
 *  - a disabled campaign falls back to the product price.
 *
 * The landing form writes a real order (the same rows the storefront checkout
 * writes), so these rows are cleaned up like the products and campaigns are.
 *
 * The suite runs against the live MySQL connection, so every row created here
 * is deleted in tearDown even when an assertion fails.
 */
class LandingCampaignOrderTest extends TestCase
{
    use GrantsVariantStock;

    protected array $createdProductIds = [];
    protected array $createdCampaignIds = [];
    protected array $createdOrderIds = [];
    protected array $createdPhones = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->mock(FacebookTrackingService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(false);
        });
        $this->mock(TikTokTrackingService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(false);
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->createdOrderIds as $orderId) {
            DB::table('order_details')->where('order_id', $orderId)->delete();
            DB::table('inventory_reservations')->where('order_id', $orderId)->delete();
            DB::table('payments')->where('order_id', $orderId)->delete();
            DB::table('shippings')->where('order_id', $orderId)->delete();
            DB::table('orders')->where('id', $orderId)->delete();
        }

        if (!empty($this->createdPhones)) {
            DB::table('customers')->whereIn('phone', $this->createdPhones)->delete();
        }

        if (!empty($this->createdCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();

            // GrantsVariantStock inserted a variant_stocks row per variant, so
            // the stock rows must go with the variants - otherwise the next run
            // of the suite meets orphan stock rows and AdminReportsFinalTest
            // fails its "orphan stock rows" check. The checkout also wrote an
            // inventory_movements RESERVE line per order, and those lines point
            // at a variant and an order this tearDown is about to delete.
            // whereIn, not where: where('product_id', $array) compiles to
            // `product_id = <first id>` and silently leaves the rest behind.
            $variantIds = DB::table('product_variants')
                ->whereIn('product_id', $this->createdProductIds)
                ->pluck('id');

            DB::table('variant_stocks')->whereIn('variant_id', $variantIds)->delete();
            DB::table('inventory_movements')->whereIn('variant_id', $variantIds)->delete();

            if (!empty($this->createdOrderIds)) {
                DB::table('inventory_movements')
                    ->where('ref_type', 'order')
                    ->whereIn('ref_id', $this->createdOrderIds)
                    ->delete();
            }

            DB::table('product_variants')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_campaign_price_reaches_the_order_without_mutating_product_price(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true);

        $orderId = $this->placeLandingOrder($product, $campaign);
        $detail = DB::table('order_details')->where('order_id', $orderId)->first();

        $this->assertNotNull($detail);
        $this->assertEqualsWithDelta(900.0, (float) $detail->sale_price, 0.01);
        $this->assertEqualsWithDelta(1200.0, (float) $detail->regular_price, 0.01);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_landing_render_maps_campaign_price_and_regular_price(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true, [
            'template_id' => (int) DB::table('templates')->where('view_name', 'ht2')->value('id'),
            'image_one' => '',
        ]);

        $response = $this->get('/campaign/' . $campaign->slug);

        $response->assertOk();
        $response->assertViewHas('landingPage', function ($landingPage) use ($campaign) {
            $extra = $landingPage->extra_fields;

            return (int) $extra['campaign_id'] === (int) $campaign->id
                && (float) $extra['campaign_price'] === 900.0
                && (float) $extra['regular_price'] === 1200.0
                && (float) $extra['new_price'] === 900.0
                && (float) $extra['old_price'] === 1200.0
                && $extra['price_source'] === 'campaign'
                && $extra['campaign_active'] === true;
        });

        $response->assertSee('name="campaign_id"', false);
        $response->assertSee('value="' . $campaign->id . '"', false);
    }

    public function test_a_landing_order_without_a_campaign_uses_the_product_price(): void
    {
        $product = $this->createProduct(1200);

        $orderId = $this->placeLandingOrder($product, null);
        $detail = DB::table('order_details')->where('order_id', $orderId)->first();

        $this->assertNotNull($detail);
        $this->assertEqualsWithDelta(1200.0, (float) $detail->sale_price, 0.01);
        $this->assertNull($detail->campaign_id);
    }

    public function test_expired_campaign_is_rejected_server_side(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true, [
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $ordersBefore = (int) DB::table('orders')->count();

        $response = $this->from('/landing')->post('/landing-order', $this->orderPayload($product, $campaign));

        $response->assertRedirect('/landing');
        $response->assertSessionHas('error', 'This offer has ended.');
        $this->assertSame($ordersBefore, (int) DB::table('orders')->count());
    }

    public function test_campaign_for_another_product_is_rejected(): void
    {
        $product = $this->createProduct(1200);
        $other = $this->createProduct(1500);
        $campaign = $this->createCampaign((int) $other->id, 900, true);

        $ordersBefore = (int) DB::table('orders')->count();

        $response = $this->from('/landing')->post('/landing-order', $this->orderPayload($product, $campaign));

        $response->assertRedirect('/landing');
        $response->assertSessionHas('error', 'This offer does not match the selected product.');
        $this->assertSame($ordersBefore, (int) DB::table('orders')->count());
    }

    public function test_disabled_campaign_falls_back_to_product_price(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, false);

        $orderId = $this->placeLandingOrder($product, $campaign);
        $detail = DB::table('order_details')->where('order_id', $orderId)->first();

        $this->assertNotNull($detail);
        $this->assertEqualsWithDelta(1200.0, (float) $detail->sale_price, 0.01);
        $this->assertNull($detail->campaign_id);
    }

    /**
     * Post the landing order form and return the id of the order it wrote.
     */
    private function placeLandingOrder(object $product, ?object $campaign): int
    {
        $response = $this->post('/landing-order', $this->orderPayload($product, $campaign));

        $orderId = (int) DB::table('orders')->orderByDesc('id')->value('id');
        $this->createdOrderIds[] = $orderId;

        $response->assertRedirect(route('customer.order_success', ['id' => $orderId]));

        return $orderId;
    }

    /**
     * The same fields the landing form posts.
     */
    private function orderPayload(object $product, ?object $campaign): array
    {
        $area = ShippingCharge::where('status', 1)->orderBy('id')->first();
        $phone = '017' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $this->createdPhones[] = $phone;

        return [
            'product_id' => $product->id,
            'campaign_id' => $campaign ? $campaign->id : null,
            'quantity' => 1,
            'name' => 'ল্যান্ডিং ক্যাম্পেইন টেস্ট ক্রেতা',
            'phone' => $phone,
            'address' => 'বাসা নং ১, রোড নং ২, ঢাকা',
            'area' => $area->id,
            'payment_method' => 'Cash On Delivery',
        ];
    }

    private function createProduct(int $newPrice): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'Campaign Test Product ' . Str::random(6),
            'slug' => 'campaign-test-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'CT-' . strtoupper(Str::random(8)),
            'purchase_price' => (int) round($newPrice / 2),
            'old_price' => $newPrice + 300,
            'new_price' => $newPrice,
            'meta_description' => null,
            'description' => null,
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdProductIds[] = $id;

        $product = DB::table('products')->where('id', $id)->first();

        // Stock is variant-owned now; a product fixture without a balance
        // cannot be ordered.
        $this->grantDefaultStock($product);

        return $product;
    }

    private function createCampaign(int $productId, float $price, bool $enabled, array $overrides = []): object
    {
        $id = DB::table('campaigns')->insertGetId(array_merge([
            'name' => 'Campaign Test ' . Str::random(6),
            'slug' => 'campaign-test-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'Test campaign',
            'review' => 'Test review',
            'description' => 'Test campaign description',
            'image_one' => '',
            'status' => '1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'product_id' => $productId,
            'campaign_price' => $price,
            'campaign_price_enabled' => $enabled ? 1 : 0,
            'allow_coupon' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $this->createdCampaignIds[] = $id;

        return DB::table('campaigns')->where('id', $id)->first();
    }
}
