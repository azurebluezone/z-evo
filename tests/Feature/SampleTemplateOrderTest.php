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
    use GrantsVariantStock;

    private array $createdProductIds = [];
    private array $createdCampaignIds = [];
    private array $createdOrderIds = [];
    private array $createdPhones = [];

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
            DB::table('order_details')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('inventory_reservations')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('payments')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('shippings')->whereIn('order_id', $this->createdOrderIds)->delete();
            DB::table('orders')->whereIn('id', $this->createdOrderIds)->delete();
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
            DB::table('product_variants')->whereIn('product_id', $this->createdProductIds)->delete();
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

    public function test_sample_template_form_fields_place_the_campaign_order(): void
    {
        [$product, $campaign] = $this->createCampaignPage();

        $this->get('/campaign/' . $campaign->slug)->assertOk();

        // The landing form places the order directly now (OrderPlacementService);
        // it no longer parks a line in the cart for a later checkout.
        $area = DB::table('shipping_charges')->where('status', 1)->orderBy('id')->first();
        $this->assertNotNull($area, 'Checkout needs at least one active shipping area.');

        $phone = '018' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $this->createdPhones[] = $phone;

        $response = $this->post('/landing-order', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'quantity' => 1,
            'name' => 'Sample Template Buyer',
            'phone' => $phone,
            'address' => '12 Sample Road, Dhaka',
            'area' => $area->id,
            'payment_method' => 'Cash On Delivery',
        ]);

        $customerId = (int) DB::table('customers')->where('phone', $phone)->value('id');
        $this->assertGreaterThan(0, $customerId, 'The landing order did not create a customer.');

        $orderId = (int) DB::table('orders')->where('customer_id', $customerId)->orderByDesc('id')->value('id');
        $this->assertGreaterThan(0, $orderId, 'The landing order did not create an order.');
        $this->createdOrderIds[] = $orderId;

        $response->assertRedirect(route('customer.order_success', ['id' => $orderId]));

        $detail = DB::table('order_details')->where('order_id', $orderId)->first();
        $this->assertNotNull($detail, 'The order has no line.');
        $this->assertSame(900.0, (float) $detail->sale_price);
        $this->assertSame(1200.0, (float) $detail->regular_price);
        $this->assertSame((int) $campaign->id, (int) $detail->campaign_id);
        $this->assertSame((int) $product->id, (int) $detail->product_id);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    /**
     * @return array{0: object, 1: object}
     */
    private function createCampaignPage(): array
    {
        $templateId = DB::table('templates')->where('view_name', 'ht2')->value('id');
        $this->assertNotNull($templateId, 'The ht2 template row is missing from the templates table.');

        $productId = DB::table('products')->insertGetId([
            'name' => 'Sample Template Product ' . Str::random(6),
            'slug' => 'sample-template-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'STP-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
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
        $this->grantDefaultStock($product);

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
