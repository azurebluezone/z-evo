<?php

namespace Tests\Feature;

use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * sample-template must sell through the same product order path as the other
 * templates.
 *
 * The template used to render an <a href> to /landing-order, which is POST
 * only, so the primary CTA ended at a 405. This test renders the real campaign
 * page and posts the form fields it contains.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase); every row
 * it creates is deleted in tearDown.
 */
class SampleTemplateOrderTest extends TestCase
{
    private array $createdProductIds = [];
    private array $createdCampaignIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        Cart::instance('shopping')->destroy();

        if (!empty($this->createdCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_sample_template_renders_a_post_order_form_for_the_product(): void
    {
        [$product, $campaign] = $this->createCampaignPage();

        $response = $this->get('/campaign/' . $campaign->slug);

        $response->assertOk();
        $response->assertSee('method="POST"', false);
        $response->assertSee('action="' . route('landing.order') . '"', false);
        $response->assertSee('name="product_id"', false);
        $response->assertSee('value="' . $product->id . '"', false);
        $response->assertSee('name="campaign_id"', false);
        $response->assertSee('value="' . $campaign->id . '"', false);
        $response->assertSee('name="quantity"', false);

        // The dead CTA was a GET anchor to the POST-only route.
        $response->assertDontSee('href="' . route('landing.order') . '"', false);
    }

    public function test_sample_template_form_fields_reach_the_campaign_cart(): void
    {
        [$product, $campaign] = $this->createCampaignPage();

        $this->get('/campaign/' . $campaign->slug)->assertOk();

        $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.checkout'));

        $this->assertSame(1, Cart::instance('shopping')->count());

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(900.0, (float) $item->price);
        $this->assertSame((int) $campaign->id, (int) $item->options->campaign_id);
        $this->assertSame(1200.0, (float) $item->options->regular_price);
        $this->assertSame('campaign', $item->options->price_source);
        $this->assertSame((int) $product->id, (int) $item->id);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    /**
     * @return array{0: object, 1: object}
     */
    private function createCampaignPage(): array
    {
        $templateId = DB::table('templates')->where('view_name', 'sample-template')->value('id');
        $this->assertNotNull($templateId, 'The sample-template row is missing from the templates table.');

        $productId = DB::table('products')->insertGetId([
            'name' => 'Sample Template Product ' . Str::random(6),
            'slug' => 'sample-template-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'STP-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'Sample template product description',
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdProductIds[] = $productId;
        $product = DB::table('products')->where('id', $productId)->first();

        $campaignId = DB::table('campaigns')->insertGetId([
            'name' => 'Sample Template Campaign ' . Str::random(6),
            'slug' => 'sample-template-campaign-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'Sample template short description',
            'review' => 'Sample review',
            'description' => 'Sample template campaign description',
            'image_one' => '',
            'status' => '1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'template_id' => $templateId,
            'product_id' => $productId,
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdCampaignIds[] = $campaignId;
        $campaign = DB::table('campaigns')->where('id', $campaignId)->first();

        return [$product, $campaign];
    }
}
