<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CourierOrderSender;
use App\Services\OrderPlacementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * End-to-end audit of the shop's stock and order workflow.
 *
 * The suite runs against the live `z_evo` database (no RefreshDatabase), so
 * every row it creates is rolled back by DatabaseTransactions. It exercises:
 *
 *   manual stock adjustment (+ / - / damage / below-zero refusal)
 *   the whole-dataset inventory KPIs (not just the 50 rows on page one)
 *   storefront availability for simple and multi-variant products
 *   the reservation -> sale -> delivered-once -> cancel-release lifecycle
 *   the visitor cart + checkout HTTP path
 *   a courier send with no credentials (must fail gracefully, never book)
 *   the admin flash contract (attributes on <body>) and the responsive
 *   inventory toolbar.
 *
 * A passing run prints SHOP_WORKFLOW_AUDIT_OK from the recorded check.
 */
class ShopWorkflowAuditTest extends TestCase
{
    use DatabaseTransactions;

    /** Value id stored in the fixture variant's attributes_json. */
    private const ATTRIBUTE_VALUE_ID = 910001;
    private const SECOND_ATTRIBUTE_VALUE_ID = 910002;

    private int $productId;
    private int $variantId;
    private string $slug;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        try {
            \Cart::instance('shopping')->destroy();
        } catch (\Throwable $e) {
            // A cold session store is not a failure.
        }
        $this->flushSession();

        $categoryId = (int) DB::table('categories')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $categoryId, 'The audit needs at least one category.');

        $this->slug = 'zc-audit-romper-' . Str::lower(Str::random(8));

        $this->productId = (int) DB::table('products')->insertGetId([
            'name' => 'ZC Audit Romper',
            'slug' => $this->slug,
            'category_id' => $categoryId,
            'product_code' => 'ZCA-' . Str::upper(Str::random(8)),
            'purchase_price' => 200,
            'old_price' => 500,
            'new_price' => 400,
            'description' => 'Stock and order workflow audit fixture.',
            'topsale' => 1,
            'feature_product' => 1,
            'is_recommended' => 1,
            'recommended_sort_order' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->variantId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $this->productId,
            'sku' => 'ZCA-' . Str::upper(Str::random(10)),
            'title' => 'Audit Size',
            'price_override' => null,
            'status' => 1,
            'attributes_json' => json_encode([
                'ids' => [self::ATTRIBUTE_VALUE_ID],
                'attrs' => ['Audit Size'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('variant_stocks')->insert([
            'variant_id' => $this->variantId,
            'on_hand' => 20,
            'reserved' => 0,
            'available' => 20,
            'damaged' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::query()->orderBy('id')->firstOrFail();
    }

    /* ---------------------------------------------------------------------
     * Inventory: projection and ledger
     * ------------------------------------------------------------------ */

    public function test_manual_adjustment_updates_projection_and_ledger(): void
    {
        $movementsBefore = $this->movementCount();

        $this->adjust(['type' => 'ADJUSTMENT', 'qty_delta' => 5, 'note' => 'audit add'])
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $stock = $this->stock();
        $this->assertSame(25, (int) $stock->on_hand);
        $this->assertSame(25, (int) $stock->available);
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $this->variantId,
            'type' => 'ADJUSTMENT',
            'qty_delta' => 5,
        ]);

        $this->adjust(['type' => 'ADJUSTMENT', 'qty_delta' => -3])
            ->assertRedirect(route('admin.inventory.index'));

        $stock = $this->stock();
        $this->assertSame(22, (int) $stock->on_hand);
        $this->assertSame(22, (int) $stock->available);
        $this->assertSame($movementsBefore + 2, $this->movementCount());

        // A negative adjustment that would go below zero is a validation
        // error, not a silent clamp: the admin must see why it refused.
        $this->adjust(['type' => 'ADJUSTMENT', 'qty_delta' => -100])
            ->assertSessionHasErrors('qty_delta');

        $this->assertSame(22, (int) $this->stock()->on_hand);
        $this->assertSame(22, (int) $this->stock()->available);
        $this->assertSame($movementsBefore + 2, $this->movementCount());
    }

    public function test_damage_moves_units_out_of_sellable_stock_without_changing_on_hand(): void
    {
        $this->adjust(['type' => 'DAMAGE', 'qty_delta' => 2, 'note' => 'torn seam'])
            ->assertRedirect(route('admin.inventory.index'));

        $stock = $this->stock();
        $this->assertSame(20, (int) $stock->on_hand, 'A damaged unit is still physically in the warehouse.');
        $this->assertSame(2, (int) $stock->damaged);
        $this->assertSame(18, (int) $stock->available);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $this->variantId,
            'type' => 'DAMAGE',
            'bucket' => 'damaged',
            'qty_delta' => 2,
        ]);

        // Cannot damage more units than are sellable.
        $this->adjust(['type' => 'DAMAGE', 'qty_delta' => 999])
            ->assertSessionHasErrors('qty_delta');

        $this->assertSame(2, (int) $this->stock()->damaged);
        $this->assertSame(20, (int) $this->stock()->on_hand);
    }

    public function test_inventory_kpis_count_the_whole_filtered_dataset(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('inventory-toolbar', false);

        $this->assertSame(
            (int) DB::table('product_variants')->count(),
            (int) $response->viewData('kpiTotal'),
            'KPI total must not be capped by the 50-row table page.'
        );

        [$expectedOut, $expectedLow] = $this->expectedAvailabilityCounts();

        $this->assertSame($expectedOut, (int) $response->viewData('kpiOut'));
        $this->assertSame($expectedLow, (int) $response->viewData('kpiLow'));

        // The live catalogue has more than one page of variants, so a
        // page-limited KPI would disagree with this count.
        $this->assertGreaterThan(50, (int) DB::table('product_variants')->count());
    }

    /* ---------------------------------------------------------------------
     * Storefront availability
     * ------------------------------------------------------------------ */

    public function test_out_of_stock_product_details_and_home_card_block_the_action(): void
    {
        DB::table('variant_stocks')->where('variant_id', $this->variantId)->update([
            'on_hand' => 0,
            'available' => 0,
            'damaged' => 0,
            'updated_at' => now(),
        ]);

        $this->get(route('product', $this->slug))
            ->assertOk()
            ->assertSee('Out of stock');

        $home = $this->get('/')->assertOk();
        $html = $home->getContent();

        // The active home renders the fm v2 card. Its stock contract is the
        // sold-out pill plus a disabled action, not the old data-in-stock
        // attribute the legacy card carried.
        $marker = 'class="fm-product-card" data-product-id="' . $this->productId . '"';
        $this->assertStringContainsString($marker, $html);

        $start = (int) strpos($html, $marker);
        $end = strpos($html, '</article>', $start);
        $card = substr($html, $start, $end === false ? 6000 : $end - $start);

        $this->assertStringContainsString('fm-product-soldout', $card, 'an out-of-stock card must show the sold-out pill');
        $this->assertStringContainsString('disabled', $card, 'an out-of-stock card must disable the action');
    }

    public function test_any_variant_availability_and_reservations_reduce_storefront_stock(): void
    {
        DB::table('product_variants')->insert([
            'id' => $this->variantId + 100000,
            'product_id' => $this->productId,
            'sku' => 'ZCA-SECOND-' . Str::upper(Str::random(6)),
            'title' => 'Audit Second Size',
            'status' => 1,
            'attributes_json' => json_encode([
                'ids' => [self::SECOND_ATTRIBUTE_VALUE_ID],
                'attrs' => ['Audit Second Size'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondVariantId = $this->variantId + 100000;

        DB::table('variant_stocks')->insert([
            'variant_id' => $secondVariantId,
            'on_hand' => 5,
            'reserved' => 0,
            'available' => 5,
            'damaged' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::findOrFail($this->productId);
        $this->assertTrue($product->isInStock());
        $this->assertSame(25, $product->displayStockQuantity());

        // Hold the first variant's 20 units. Only the second size is left,
        // so the product is still sellable and the count is 5.
        $this->placeOrder($this->variantId, 20, 1);

        $products = Product::findOrFail($this->productId);
        $this->assertTrue($products->isInStock());
        $this->assertSame(5, $products->displayStockQuantity());

        $home = $this->get('/')->assertOk();
        $html = $home->getContent();

        $marker = 'class="fm-product-card" data-product-id="' . $this->productId . '"';
        $this->assertStringContainsString($marker, $html);

        $start = (int) strpos($html, $marker);
        $end = strpos($html, '</article>', $start);
        $card = substr($html, $start, $end === false ? 6000 : $end - $start);

        $this->assertStringNotContainsString('fm-product-soldout', $card, 'a product with a sellable variant must not be sold out');
        $this->assertStringNotContainsString('disabled', $card, 'a product with a sellable variant must keep its action enabled');

        // Hold the second size too: the product is now out of stock even
        // though both variant rows still have on_hand stock.
        $this->placeOrder($secondVariantId, 5, 2);

        $none = Product::findOrFail($this->productId);
        $this->assertFalse($none->isInStock());
        $this->assertSame(0, $none->displayStockQuantity());

        $home = $this->get('/')->assertOk();
        $html = $home->getContent();
        $start = (int) strpos($html, $marker);
        $end = strpos($html, '</article>', $start);
        $card = substr($html, $start, $end === false ? 6000 : $end - $start);

        $this->assertStringContainsString('fm-product-soldout', $card);
        $this->assertStringContainsString('disabled', $card);
    }

    /* ---------------------------------------------------------------------
     * Visitor cart -> checkout -> admin processing
     * ------------------------------------------------------------------ */

    public function test_visitor_cart_checkout_then_admin_ships_and_delivers_once(): void
    {
        $this->post(route('cart.store'), [
            'id' => $this->productId,
            'qty' => 2,
            'variant' => [self::ATTRIBUTE_VALUE_ID],
        ])->assertRedirect(route('customer.checkout'));

        $this->assertSame(2, (int) \Cart::instance('shopping')->content()->sum('qty'));

        $checkout = $this->post(route('customer.ordersave'), [
            'name' => 'Audit Buyer',
            'phone' => '01712345678',
            'address' => '12 Audit Road, Barishal',
            'area' => 1,
            'payment_method' => 'Cash On Delivery',
        ]);
        $checkout->assertRedirect();

        $location = (string) $checkout->headers->get('Location');
        $this->assertMatchesRegularExpression('~/customer/order-success/(\d+)$~', $location);
        $orderId = (int) Str::afterLast($location, '/');
        $order = Order::findOrFail($orderId);

        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_id' => $order->id,
            'variant_id' => $this->variantId,
            'qty' => 2,
        ]);

        // Moving to Shipped is what writes the SALE.
        $this->actingAs($this->admin)
            ->post(route('admin.order_change'), ['id' => $order->id, 'status' => 3])
            ->assertRedirect();

        $stock = $this->stock();
        $this->assertSame(18, (int) $stock->on_hand);
        $this->assertSame(18, (int) $stock->available);
        $this->assertSame(1, $this->movementCount('SALE', $order->id));
        $this->assertSame(0, (int) DB::table('inventory_reservations')->where('order_id', $order->id)->count());

        // Delivered after Shipped must not decrement again.
        $this->actingAs($this->admin)
            ->post(route('admin.order_change'), ['id' => $order->id, 'status' => 4])
            ->assertRedirect();

        $this->assertSame(18, (int) $this->stock()->on_hand);
        $this->assertSame(1, $this->movementCount('SALE', $order->id));
        $this->assertNotNull(Order::findOrFail($order->id)->delivered_at);
    }

    public function test_cancelling_a_reserved_order_releases_the_hold_without_a_sale(): void
    {
        $order = $this->placeOrder($this->variantId, 3, 1);
        $this->assertSame(20, (int) $this->stock()->on_hand);
        $this->assertSame(1, (int) DB::table('inventory_reservations')->where('order_id', $order->id)->count());

        $this->actingAs($this->admin)
            ->post(route('admin.order_change'), ['id' => $order->id, 'status' => 5])
            ->assertRedirect();

        $this->assertSame(20, (int) $this->stock()->on_hand);
        $this->assertSame(0, (int) DB::table('inventory_reservations')->where('order_id', $order->id)->count());
        $this->assertSame(0, $this->movementCount('SALE', $order->id));
    }

    /* ---------------------------------------------------------------------
     * Courier + admin UI contracts
     * ------------------------------------------------------------------ */

    public function test_courier_without_credentials_fails_gracefully_and_books_nothing(): void
    {
        DB::table('courier_apis')->where('type', 'steadfast')->update(['status' => 0]);

        $order = $this->placeOrder($this->variantId, 1, 1);

        $result = app(CourierOrderSender::class)->send($order, 'steadfast');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('SteadFast', (string) $result['message']);
        $this->assertFalse(DB::table('courier_shipments')->where('order_id', $order->id)->exists());

        $response = $this->actingAs($this->admin)
            ->post(route('admin.order.courier.store', ['order' => $order->id, 'provider' => 'steadfast']));

        $response->assertRedirect(route('admin.order.courier.show', [
            'order' => $order->id,
            'provider' => 'steadfast',
        ]));
        $response->assertSessionHas('courier_error');
        $this->assertFalse(DB::table('courier_shipments')->where('order_id', $order->id)->exists());
    }

    public function test_admin_flash_attributes_live_on_body_and_inventory_toolbar_is_responsive(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['success' => 'Stock adjusted successfully'])
            ->get(route('admin.inventory.index'))
            ->assertOk();

        $html = (string) $response->getContent();

        $this->assertSame(
            1,
            preg_match('/<body[^>]*data-flash-success=/s', $html),
            'Admin JS reads the flash values from <body>.'
        );
        $this->assertSame(
            0,
            preg_match('/<html[^>]*data-flash-success=/s', $html),
            'Flash values must not be left on <html>; admin-app-alerts.js cannot see them there.'
        );

        $this->assertStringContainsString('inventory-toolbar', $html);
        $this->assertStringContainsString('admin-responsive.css', $html);

        $cssPath = public_path('admin/assets/css/admin-responsive.css');
        $this->assertFileExists($cssPath);
        $this->assertStringContainsString('.inventory-toolbar', (string) file_get_contents($cssPath));
    }

    /* ---------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    private function adjust(array $payload)
    {
        return $this->actingAs($this->admin)
            ->post(route('admin.inventory.adjustment.store'), $payload + [
                'variant_id' => $this->variantId,
            ]);
    }

    private function stock(): object
    {
        $stock = DB::table('variant_stocks')->where('variant_id', $this->variantId)->first();
        $this->assertNotNull($stock, 'The fixture variant has no stock row.');

        return $stock;
    }

    private function movementCount(?string $type = null, ?int $orderId = null): int
    {
        $query = DB::table('inventory_movements')->where('variant_id', $this->variantId);

        if ($type !== null) {
            $query->where('type', $type);
        }
        if ($orderId !== null) {
            $query->where('ref_type', 'order')->where('ref_id', $orderId);
        }

        return (int) $query->count();
    }

    private function placeOrder(int $variantId, int $qty, int $customerId): Order
    {
        return app(OrderPlacementService::class)->place(
            [[
                'product_id' => $this->productId,
                'name' => 'ZC Audit Romper',
                'qty' => $qty,
                'price' => 400,
                'regular_price' => 400,
                'purchase_price' => 200,
                'variant_id' => $variantId,
            ]],
            [
                'customer_id' => $customerId,
                'name' => 'Audit Buyer',
                'phone' => '01712345678',
                'address' => '12 Audit Road, Barishal',
                'area' => 'Standard delivery',
                'payment_method' => 'Cash On Delivery',
            ],
            [
                'shipping_fee' => 0,
                'notify' => false,
                'fraud_check' => false,
            ]
        );
    }

    /**
     * Recompute the KPI buckets in PHP, without the controller's SQL.
     *
     * @return array{0:int,1:int}
     */
    private function expectedAvailabilityCounts(): array
    {
        $rows = DB::table('product_variants as v')
            ->leftJoin('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->select(
                'v.id',
                DB::raw('COALESCE(s.available, 0) as available'),
                DB::raw('COALESCE(s.reserved, 0) as reserved')
            )
            ->get();

        $holds = DB::table('inventory_reservations')
            ->where('expires_at', '>', now())
            ->groupBy('variant_id')
            ->select('variant_id', DB::raw('SUM(qty) as qty'))
            ->get()
            ->pluck('qty', 'variant_id');

        $out = 0;
        $low = 0;

        foreach ($rows as $row) {
            $atp = max(0, (int) $row->available - (int) $row->reserved - (int) ($holds[$row->id] ?? 0));

            if ($atp <= 0) {
                $out++;
            } elseif ($atp <= 2) {
                $low++;
            }
        }

        return [$out, $low];
    }
}
