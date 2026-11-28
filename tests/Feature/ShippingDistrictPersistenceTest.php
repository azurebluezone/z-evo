<?php

namespace Tests\Feature;

use App\Models\District;
use App\Services\OrderPlacementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 item 2a — shippings geography.
 *
 * Two promises are pinned here:
 *
 *  1. When an order is placed with a district_id, the shippings row stores
 *     that id AND copies the division from the same districts reference row,
 *     so the two columns can never disagree.
 *
 *  2. When no district_id is supplied, both columns stay NULL. The migration
 *     deliberately refuses to invent a district for old free-text rows, and
 *     this test keeps that rule for new rows too.
 *
 * The suite runs against the live MySQL connection, so every row created here
 * is deleted in tearDown even when an assertion fails.
 *
 * Marker: SHIPPING_DISTRICT_OK
 */
class ShippingDistrictPersistenceTest extends TestCase
{
    use GrantsVariantStock;

    protected array $createdProductIds = [];
    protected array $createdOrderIds = [];
    protected array $createdCustomerIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdOrderIds as $orderId) {
            DB::table('order_details')->where('order_id', $orderId)->delete();
            DB::table('inventory_reservations')->where('order_id', $orderId)->delete();
            DB::table('payments')->where('order_id', $orderId)->delete();
            DB::table('shippings')->where('order_id', $orderId)->delete();
            DB::table('orders')->where('id', $orderId)->delete();
        }

        if (!empty($this->createdCustomerIds)) {
            DB::table('customers')->whereIn('id', $this->createdCustomerIds)->delete();
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

            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('product_variants')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_district_id_and_division_are_copied_from_the_reference_row(): void
    {
        $district = District::query()->whereNotNull('division')->orderBy('id')->first();

        $this->assertNotNull(
            $district,
            'No districts row has a division. Run 2026_11_28_000001 and the DistrictShippingSeeder first.'
        );

        $customerId = $this->createCustomer();
        $product = $this->createProduct();
        $line = [[
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'qty' => 1,
            'price' => 500,
            'regular_price' => 500,
            'purchase_price' => 250,
        ]];

        $order = app(OrderPlacementService::class)->place(
            $line,
            [
                'customer_id' => $customerId,
                'name' => 'District Persistence Buyer',
                'phone' => '01700000000',
                'address' => 'Test address',
                'area' => 'ঢাকার ভিতরে',
                'district_id' => (int) $district->id,
                'payment_method' => 'Cash On Delivery',
            ],
            [
                'shipping_fee' => 60,
                'fraud_check' => false,
                'notify' => false,
            ]
        );

        $this->createdOrderIds[] = (int) $order->id;

        $shipping = DB::table('shippings')->where('order_id', $order->id)->first();

        $this->assertNotNull($shipping, 'The order must have a shippings row.');
        $this->assertSame((int) $district->id, (int) $shipping->district_id);
        $this->assertSame((string) $district->division, (string) $shipping->division);
        $this->assertSame(
            (string) DB::table('districts')->where('id', $district->id)->value('division'),
            (string) $shipping->division,
            'shippings.division must equal the districts reference row, not a guessed value.'
        );
    }

    public function test_no_district_id_leaves_both_columns_null(): void
    {
        $customerId = $this->createCustomer();
        $product = $this->createProduct();
        $line = [[
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'qty' => 1,
            'price' => 500,
            'regular_price' => 500,
            'purchase_price' => 250,
        ]];

        $order = app(OrderPlacementService::class)->place(
            $line,
            [
                'customer_id' => $customerId,
                'name' => 'No District Buyer',
                'phone' => '01700000001',
                'address' => 'Test address',
                'area' => 'ঢাকার ভিতরে',
                'payment_method' => 'Cash On Delivery',
            ],
            [
                'shipping_fee' => 60,
                'fraud_check' => false,
                'notify' => false,
            ]
        );

        $this->createdOrderIds[] = (int) $order->id;

        $shipping = DB::table('shippings')->where('order_id', $order->id)->first();

        $this->assertNotNull($shipping, 'The order must have a shippings row.');
        $this->assertNull($shipping->district_id, 'A caller without a district must not get an invented one.');
        $this->assertNull($shipping->division, 'A caller without a district must not get an invented division.');
    }

    private function createCustomer(): int
    {
        $id = DB::table('customers')->insertGetId([
            'name' => 'District Test Customer ' . Str::random(6),
            'slug' => 'district-test-customer-' . Str::random(8),
            'phone' => '017' . random_int(10000000, 99999999),
            'email' => 'district-test-' . Str::random(8) . '@example.test',
            'balance' => 0,
            'password' => bcrypt('secret'),
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdCustomerIds[] = $id;

        return (int) $id;
    }

    private function createProduct(): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'District Persistence Product ' . Str::random(6),
            'slug' => 'district-persistence-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'DP-' . strtoupper(Str::random(8)),
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

        $product = DB::table('products')->where('id', $id)->first();

        $this->grantDefaultStock($product);

        return $product;
    }
}
