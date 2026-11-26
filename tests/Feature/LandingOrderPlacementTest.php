<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\ShippingCharge;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The landing page order form.
 *
 * Two promises are pinned here:
 *
 *  1. Every landing design shows the SAME fields the storefront checkout
 *     saves - name, address, phone, delivery area and payment method - next to
 *     the landing-only fields (product, campaign, quantity), under the same
 *     field names, so nothing has to be typed twice.
 *
 *  2. A landing order is stored like a normal product order: the same
 *     orders / shippings / payments / order_details rows, written by the same
 *     OrderPlacementService the checkout uses.
 *
 * The suite runs against the live MySQL connection, so every row created here
 * is deleted in tearDown even when an assertion fails.
 */
class LandingOrderPlacementTest extends TestCase
{
    use GrantsVariantStock;
    use EnsuresDesignCampaigns;

    private const DESIGN_VIEWS = ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'];

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
            $variantIds = DB::table('product_variants')
                ->whereIn('product_id', $this->createdProductIds)
                ->pluck('id')
                ->all();

            if (!empty($variantIds)) {
                DB::table('variant_stocks')->whereIn('variant_id', $variantIds)->delete();
                DB::table('inventory_movements')->whereIn('variant_id', $variantIds)->delete();
                DB::table('inventory_reservations')->whereIn('variant_id', $variantIds)->delete();
            }

            DB::table('productimages')->where('product_id', $this->createdProductIds)->delete();
            DB::table('product_variants')->where('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        $this->cleanupDesignCampaignFixtures();

        parent::tearDown();
    }

    public function test_every_design_landing_page_renders_the_normal_order_fields(): void
    {
        foreach ($this->campaignsByDesign() as $view => $campaign) {
            $response = $this->get('/campaign/' . $campaign->slug);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertSame(
                1,
                preg_match_all('~action="[^"]*/landing-order"~', $html),
                $view . ' must contain exactly one landing order form.'
            );

            // The fields a normal product order saves.
            foreach (['name', 'address', 'phone', 'area', 'payment_method'] as $field) {
                $this->assertStringContainsString(
                    'name="' . $field . '"',
                    $html,
                    $view . ' must ask for the "' . $field . '" field of a normal order.'
                );
            }

            // The landing-only fields.
            foreach (['product_id', 'campaign_id', 'quantity', '_token'] as $field) {
                $this->assertStringContainsString('name="' . $field . '"', $html, $view . ' lost the "' . $field . '" field.');
            }

            $this->assertStringContainsString('name="product_id" value="' . $campaign->product_id . '"', $html);
            $this->assertStringContainsString('name="campaign_id" value="' . $campaign->id . '"', $html);
        }
    }

    public function test_landing_order_stores_the_same_rows_as_a_normal_product_order(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true);
        $area = ShippingCharge::where('status', 1)->orderBy('id')->first();

        $this->assertNotNull($area, 'No active shipping area exists, so the order form cannot be tested.');

        $phone = '017' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $this->createdPhones[] = $phone;

        $response = $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 2,
            'name' => 'ল্যান্ডিং টেস্ট ক্রেতা',
            'phone' => $phone,
            'address' => 'বাসা নং ১২, রোড নং ৫, উত্তরা, ঢাকা',
            'area' => $area->id,
            'payment_method' => 'Cash On Delivery',
        ]);

        $orderId = (int) DB::table('orders')->orderByDesc('id')->value('id');
        $this->createdOrderIds[] = $orderId;

        $response->assertRedirect(route('customer.order_success', ['id' => $orderId]));

        // The order row: the money comes from the campaign price plus the
        // delivery charge of the chosen area.
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNotNull($order);
        $this->assertEqualsWithDelta(900 * 2 + (float) $area->amount, (float) $order->amount, 0.01);
        $this->assertEqualsWithDelta((float) $area->amount, (float) $order->shipping_charge, 0.01);
        $this->assertNotEmpty($order->customer_id);

        // The shipping row holds exactly what the form asked for.
        $shipping = DB::table('shippings')->where('order_id', $orderId)->first();
        $this->assertNotNull($shipping);
        $this->assertSame('ল্যান্ডিং টেস্ট ক্রেতা', $shipping->name);
        $this->assertSame($phone, $shipping->phone);
        $this->assertSame('বাসা নং ১২, রোড নং ৫, উত্তরা, ঢাকা', $shipping->address);
        $this->assertSame($area->name, $shipping->area);

        // The payment row holds the chosen method.
        $payment = DB::table('payments')->where('order_id', $orderId)->first();
        $this->assertNotNull($payment);
        $this->assertSame('Cash On Delivery', $payment->payment_method);
        $this->assertEqualsWithDelta((float) $order->amount, (float) $payment->amount, 0.01);
        $this->assertSame('pending', $payment->payment_status);

        // The detail row holds the product, the quantity and the campaign price.
        $detail = DB::table('order_details')->where('order_id', $orderId)->first();
        $this->assertNotNull($detail);
        $this->assertSame((int) $product->id, (int) $detail->product_id);
        $this->assertSame(2, (int) $detail->qty);
        $this->assertEqualsWithDelta(900.0, (float) $detail->sale_price, 0.01);
        $this->assertEqualsWithDelta(1200.0, (float) $detail->regular_price, 0.01);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);

        // The product price itself is never rewritten.
        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_landing_order_rejects_missing_or_invalid_customer_details(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true);
        $area = ShippingCharge::where('status', 1)->orderBy('id')->first();

        $ordersBefore = (int) DB::table('orders')->count();

        $response = $this->from('/campaign/' . $campaign->slug)->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
            'name' => '',
            'phone' => '12345',
            'address' => '',
            'area' => $area->id,
            'payment_method' => 'Cash On Delivery',
        ]);

        $response->assertRedirect('/campaign/' . $campaign->slug);
        $response->assertSessionHasErrors(['name', 'phone', 'address']);

        $this->assertSame($ordersBefore, (int) DB::table('orders')->count(), 'A rejected form must not write an order.');
    }

    /**
     * @return \Illuminate\Support\Collection<string, Campaign>
     */
    private function campaignsByDesign()
    {
        $campaigns = collect();

        foreach (self::DESIGN_VIEWS as $view) {
            $campaigns->put($view, $this->activeCampaignForDesign($view));
        }

        return $campaigns;
    }

    private function createProduct(int $newPrice): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'Landing Order Test Product ' . Str::random(6),
            'slug' => 'landing-order-test-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'LO-' . strtoupper(Str::random(8)),
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
            'name' => 'Landing Order Test ' . Str::random(6),
            'slug' => 'landing-order-test-' . Str::random(8),
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
