<?php

namespace Tests\Feature;

use App\Models\OrderAttribution;
use App\Services\Analytics\OrderAttributionRecorder;
use App\Services\OrderPlacementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 item 2 — one primary attribution row per order.
 *
 * The promise is narrow and checkable: when an order is written through
 * OrderPlacementService, exactly one order_attributions row appears, carrying
 * the visitor's first-touch channel and utm fields; when there is no visitor
 * evidence the source is an honest "direct"; and a second write for the same
 * order does not create a second row (the unique index on order_id is the
 * schema-level version of the same rule).
 *
 * The suite runs against the live MySQL connection, so every row it creates is
 * removed in tearDown even when an assertion fails.
 *
 * Marker: ATTRIBUTION_OK
 */
class OrderAttributionTest extends TestCase
{
    use GrantsVariantStock;

    protected array $createdOrderIds = [];
    protected array $createdCustomerIds = [];
    protected array $createdProductIds = [];
    protected array $createdVisitorIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdOrderIds as $orderId) {
            DB::table('order_attributions')->where('order_id', $orderId)->delete();
            DB::table('order_details')->where('order_id', $orderId)->delete();
            DB::table('inventory_reservations')->where('order_id', $orderId)->delete();
            DB::table('payments')->where('order_id', $orderId)->delete();
            DB::table('shippings')->where('order_id', $orderId)->delete();
            DB::table('orders')->where('id', $orderId)->delete();
        }

        if (!empty($this->createdCustomerIds)) {
            DB::table('customers')->whereIn('id', $this->createdCustomerIds)->delete();
        }

        if (!empty($this->createdVisitorIds)) {
            DB::table('visitors_master')->whereIn('visitor_id', $this->createdVisitorIds)->delete();
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

    public function test_order_carries_the_visitors_first_touch_channel_and_utm_fields(): void
    {
        $visitorId = $this->createVisitor('facebook', 'cpc', 'eid-retargeting');
        $order = $this->placeOrder(['visitor_id' => $visitorId]);

        $row = OrderAttribution::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($row, 'Placing an order must write one attribution row.');
        $this->assertSame('facebook', $row->source);
        $this->assertSame('cpc', $row->medium);
        $this->assertSame('eid-retargeting', $row->campaign);
        $this->assertSame($visitorId, $row->visitor_id);
        $this->assertSame('first_touch', $row->model);
        $this->assertNotNull($row->attributed_at);
    }

    public function test_a_second_record_call_keeps_exactly_one_row_and_the_first_touch(): void
    {
        $visitorId = $this->createVisitor('google', 'paid_search', 'brand-exact');
        $order = $this->placeOrder(['visitor_id' => $visitorId]);

        app(OrderAttributionRecorder::class)->record($order);

        $rows = OrderAttribution::query()->where('order_id', $order->id)->get();

        $this->assertCount(1, $rows, 'One order must never have two primary attribution rows.');
        $this->assertSame('google', $rows->first()->source);
        $this->assertSame('brand-exact', $rows->first()->campaign);
    }

    public function test_an_order_without_visitor_evidence_is_attributed_to_direct(): void
    {
        $order = $this->placeOrder();

        $row = OrderAttribution::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($row, 'Even a direct order must have its primary attribution row.');
        $this->assertSame('direct', $row->source);
        $this->assertNull($row->visitor_id);
        $this->assertNull($row->medium);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function placeOrder(array $options = [])
    {
        $customerId = $this->createCustomer();
        $product = $this->createProduct();

        $order = app(OrderPlacementService::class)->place(
            [[
                'product_id' => (int) $product->id,
                'name' => (string) $product->name,
                'qty' => 1,
                'price' => 500,
                'regular_price' => 500,
                'purchase_price' => 250,
            ]],
            [
                'customer_id' => $customerId,
                'name' => 'Attribution Test Buyer',
                'phone' => '01700000002',
                'address' => 'Test address',
                'area' => 'ঢাকার ভিতরে',
                'payment_method' => 'Cash On Delivery',
            ],
            array_merge([
                'shipping_fee' => 60,
                'fraud_check' => false,
                'notify' => false,
            ], $options)
        );

        $this->createdOrderIds[] = (int) $order->id;

        return $order;
    }

    private function createVisitor(string $channel, ?string $medium, ?string $campaign): string
    {
        $visitorId = (string) Str::uuid();

        DB::table('visitors_master')->insert([
            'visitor_id' => $visitorId,
            'ip' => '127.0.0.1',
            'channel' => $channel,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdVisitorIds[] = $visitorId;

        return $visitorId;
    }

    private function createCustomer(): int
    {
        $id = DB::table('customers')->insertGetId([
            'name' => 'Attribution Test Customer ' . Str::random(6),
            'slug' => 'attribution-test-customer-' . Str::random(8),
            'phone' => '017' . random_int(10000000, 99999999),
            'email' => 'attribution-test-' . Str::random(8) . '@example.test',
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
            'name' => 'Attribution Test Product ' . Str::random(6),
            'slug' => 'attribution-test-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'AT-' . strtoupper(Str::random(8)),
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
