<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\CourierFraudService;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class Phase5OrderDetailsConsistencyTest extends TestCase
{
    protected array $createdProductIds = [];
    protected array $createdCustomerIds = [];
    protected array $createdShippingChargeIds = [];
    protected array $createdVariantIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        Cart::instance('shopping')->destroy();

        if (!empty($this->createdVariantIds)) {
            DB::table('variant_stocks')->whereIn('variant_id', $this->createdVariantIds)->delete();
            DB::table('inventory_movements')->whereIn('variant_id', $this->createdVariantIds)->delete();
            DB::table('product_variants')->whereIn('id', $this->createdVariantIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('order_details')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        if (!empty($this->createdCustomerIds)) {
            DB::table('orders')->whereIn('customer_id', $this->createdCustomerIds)->delete();
            DB::table('payments')->whereIn('customer_id', $this->createdCustomerIds)->delete();
            DB::table('shippings')->whereIn('customer_id', $this->createdCustomerIds)->delete();
            DB::table('customers')->whereIn('id', $this->createdCustomerIds)->delete();
        }

        if (!empty($this->createdShippingChargeIds)) {
            DB::table('shipping_charges')->whereIn('id', $this->createdShippingChargeIds)->delete();
        }

        Mockery::close();
        parent::tearDown();
    }

    private function createBaseProduct(array $overrides = []): object
    {
        $id = DB::table('products')->insertGetId(array_merge([
            'name' => 'Phase5 Product ' . Str::random(6),
            'slug' => 'phase5-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'P5-' . strtoupper(Str::random(8)),
            'purchase_price' => 100,
            'old_price' => 150,
            'new_price' => 120,
            'stock' => 50,
            'meta_description' => null,
            'description' => null,
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $this->createdProductIds[] = $id;

        return DB::table('products')->where('id', $id)->first();
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

    public function test_checkout_to_order_details_stays_consistent_end_to_end(): void
    {
        $product = $this->createBaseProduct([
            'new_price' => 120,
            'purchase_price' => 90,
        ]);
        $variantId = $this->createDefaultVariantWithStock((int) $product->id, (string) $product->product_code, 10);

        $shippingChargeId = DB::table('shipping_charges')->insertGetId([
            'name' => 'Phase5 Area',
            'amount' => 50,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdShippingChargeIds[] = $shippingChargeId;

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Phase5 Customer',
            'slug' => 'phase5-customer-' . Str::random(6),
            'phone' => '018' . random_int(10000000, 99999999),
            'email' => 'phase5_' . Str::random(6) . '@example.test',
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

        $qty = 2;
        $unitPrice = 120;
        $discount = 20;
        $expectedSubtotal = $unitPrice * $qty;
        $expectedAmount = $expectedSubtotal + 50 - $discount;

        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $unitPrice,
            'options' => [
                'image' => '',
                'old_price' => (int) $product->old_price,
                'slug' => $product->slug,
                'purchase_price' => (int) $product->purchase_price,
                'variant_id' => $variantId,
                'product_size' => 'variant_id:' . $variantId,
                'product_color' => 'Default',
            ],
        ]);

        $courier = Mockery::mock(CourierFraudService::class);
        $courier->shouldReceive('checkAndAttachToOrder')->andReturn(null);
        $this->app->instance(CourierFraudService::class, $courier);

        $facebook = Mockery::mock(FacebookTrackingService::class);
        $facebook->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(FacebookTrackingService::class, $facebook);

        $tiktok = Mockery::mock(TikTokTrackingService::class);
        $tiktok->shouldReceive('isEnabled')->andReturn(false);
        $this->app->instance(TikTokTrackingService::class, $tiktok);

        $response = $this->withSession(['discount' => $discount])->post('/customer/order-save', [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Phase5 Address',
            'area' => $shippingChargeId,
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(302);

        $order = DB::table('orders')->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame($expectedAmount, (int) $order->amount);

        $detail = DB::table('order_details')->where('order_id', (int) $order->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame((int) $product->id, (int) $detail->product_id);
        $this->assertSame($qty, (int) $detail->qty);
        $this->assertSame($unitPrice, (int) $detail->sale_price);
        $this->assertSame('variant_id:' . $variantId, (string) $detail->product_size);
        $this->assertSame('Default', (string) $detail->product_color);

        $subtotalFromDetails = (int) DB::table('order_details')
            ->where('order_id', (int) $order->id)
            ->selectRaw('SUM(sale_price * qty) as subtotal')
            ->value('subtotal');

        $this->assertSame($expectedSubtotal, $subtotalFromDetails);
        $this->assertSame(
            (int) $order->amount,
            $subtotalFromDetails + (int) $order->shipping_charge - (int) $order->discount
        );

        $successPage = $this->get('/customer/order-success/' . (int) $order->id);
        $successPage->assertOk();
        $successPage->assertSee((string) $product->name);
        $successPage->assertSee((string) number_format($expectedSubtotal, 0), false);

        $invoicePage = $this->get('/customer/invoice?id=' . (int) $order->id);
        $invoicePage->assertOk();
        $invoicePage->assertSee((string) $expectedSubtotal, false);
    }
}

