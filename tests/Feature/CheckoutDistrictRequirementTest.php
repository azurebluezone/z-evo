<?php

namespace Tests\Feature;

use App\Models\District;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 item 2b — the storefront checkout must ask "where".
 *
 * The district is not optional decoration: it is the only geography the report
 * layer can group by. Two promises are pinned here:
 *
 *  1. /customer/checkout renders a district_id select filled from the
 *     districts reference table (the live Modern theme hides the form behind a
 *     non-empty cart, so the test puts a line in the cart first).
 *  2. /customer/order-save rejects a submission that omits district_id before
 *     it touches the cart, so an order can never be stored without it.
 *
 * Marker: CHECKOUT_DISTRICT_OK
 */
class CheckoutDistrictRequirementTest extends TestCase
{
    use SeedsCampaignCartLines;

    protected array $createdProductIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        Cart::instance('shopping')->destroy();

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('product_variants')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_checkout_page_renders_the_district_select(): void
    {
        $this->assertGreaterThan(
            0,
            District::query()->count(),
            'The districts reference table is empty; seed it before this test.'
        );

        $product = $this->createProduct();
        $this->seedNormalCartLine($product);

        $response = $this->get('/customer/checkout');

        $response->assertOk();
        $response->assertSee('name="district_id"', false);
        $response->assertSee('District / Area', false);
    }

    public function test_order_save_rejects_a_missing_district(): void
    {
        $shippingChargeId = (int) DB::table('shipping_charges')->value('id');

        $this->assertGreaterThan(0, $shippingChargeId, 'No shipping_charges row exists for the checkout fixture.');

        $ordersBefore = (int) DB::table('orders')->count();

        $response = $this->post('/customer/order-save', [
            'name' => 'District Required Buyer',
            'phone' => '01712345678',
            'address' => '12 Test Road, Barishal',
            'area' => $shippingChargeId,
            'payment_method' => 'Cash On Delivery',
        ]);

        $response->assertSessionHasErrors(['district_id']);
        $this->assertSame(
            $ordersBefore,
            (int) DB::table('orders')->count(),
            'A checkout without district_id must not write an order.'
        );
    }

    private function createProduct(): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'Checkout District Product ' . Str::random(6),
            'slug' => 'checkout-district-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'CD-' . strtoupper(Str::random(8)),
            'purchase_price' => 250,
            'old_price' => 600,
            'new_price' => 500,
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
}
