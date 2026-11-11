<?php

namespace Tests\Feature;

use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Landing campaign add-to-cart flow.
 *
 * Pins the Phase 2 contract:
 *  - the campaign price reaches the cart, not only the landing view;
 *  - products.new_price is never rewritten;
 *  - campaign and normal lines stay separate;
 *  - an expired or mismatched campaign is rejected server-side;
 *  - a disabled campaign falls back to the product price.
 *
 * The suite runs against the live MySQL connection, so every row created here
 * is deleted in tearDown even when an assertion fails.
 */
class LandingCampaignOrderTest extends TestCase
{
    protected array $createdProductIds = [];
    protected array $createdCampaignIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Cart::instance('shopping')->destroy();

        $this->mock(FacebookTrackingService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(false);
        });
        $this->mock(TikTokTrackingService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(false);
        });
    }

    protected function tearDown(): void
    {
        Cart::instance('shopping')->destroy();

        if (!empty($this->createdCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('product_variants')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_campaign_price_reaches_cart_without_mutating_product_price(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true);

        $response = $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect(route('customer.checkout'));
        $this->assertSame(1, Cart::instance('shopping')->count());

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(900.0, (float) $item->price);
        $this->assertSame((int) $campaign->id, (int) $item->options->campaign_id);
        $this->assertSame(1200.0, (float) $item->options->regular_price);
        $this->assertSame(900.0, (float) $item->options->campaign_price);
        $this->assertSame('campaign', $item->options->price_source);

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

    public function test_normal_line_and_campaign_line_stay_separate(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true);

        $this->post('/landing-order', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.checkout'));

        $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.checkout'));

        $this->assertSame(2, Cart::instance('shopping')->count());

        $prices = Cart::instance('shopping')->content()
            ->map(function ($item) {
                return (float) $item->price;
            })
            ->sort()
            ->values()
            ->all();

        $this->assertSame([900.0, 1200.0], $prices);
    }

    public function test_expired_campaign_is_rejected_server_side(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, true, [
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->from('/landing')->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect('/landing');
        $response->assertSessionHas('error', 'This offer has ended.');
        $this->assertSame(0, Cart::instance('shopping')->count());
    }

    public function test_campaign_for_another_product_is_rejected(): void
    {
        $product = $this->createProduct(1200);
        $other = $this->createProduct(1500);
        $campaign = $this->createCampaign((int) $other->id, 900, true);

        $response = $this->from('/landing')->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect('/landing');
        $response->assertSessionHas('error', 'This offer does not match the selected product.');
        $this->assertSame(0, Cart::instance('shopping')->count());
    }

    public function test_disabled_campaign_falls_back_to_product_price(): void
    {
        $product = $this->createProduct(1200);
        $campaign = $this->createCampaign((int) $product->id, 900, false);

        $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.checkout'));

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(1200.0, (float) $item->price);
        $this->assertSame('product', $item->options->price_source);
        $this->assertEmpty($item->options->campaign_id);
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
            'stock' => 50,
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

        return DB::table('products')->where('id', $id)->first();
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
