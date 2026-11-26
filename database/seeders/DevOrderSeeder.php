<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderLifecycle;
use App\Services\OrderPlacementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The canonical dev order set: 30 variant-aware orders, 10 customers, 3 coupons.
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS IS NOT
 * ---------------------------------------------------------------------------
 * It does not insert into `orders` / `order_details` directly. Every order goes
 * through App\Services\OrderPlacementService::place(), the same path the
 * storefront and the landing page use, and every status change goes through
 * App\Services\OrderLifecycle, so:
 *
 *   - every order_details row has a real variant_id;
 *   - the hold is written to the inventory ledger and inventory_reservations;
 *   - shipped/delivered book SALE, cancelled books RELEASE;
 *   - delivered_at / cancelled_at are stamped by the transition, not invented;
 *   - an audit row exists for creation and each status change.
 *
 * That is why this seeder is also the proof that the real order path works: a
 * broken place() or a missing variant fails the seed loudly instead of leaving
 * a half-linked dataset.
 *
 * ---------------------------------------------------------------------------
 * DETERMINISTIC SPEC
 * ---------------------------------------------------------------------------
 * invoice          AD-ORD-0001 .. AD-ORD-0030
 * product index    = order index (catalog order in catalog.json)
 * qty              = [1,2,3,1,2][i % 5]
 * zone             = inside / outside / standard, cycling
 * free shipping    = order 5, 12, 20, 26 (0-based 4,11,19,25)
 * payment method   = COD / bkash / nagad / card, cycling
 * coupon           = order 3 ADORA10, 15 FLAT100, 23 WELCOME50
 * multi-line       = i % 7 == 0 (adds the product 7 rows later)
 * status           = 12 delivered, 10 pending, 4 shipped, 2 cancelled, 2 returned
 * placed_at        = 2026-11-15 10:00 minus (29 - i) days
 *
 * IDEMPOTENT: the invoice number is the natural key. A second run skips every
 * order it already placed instead of duplicating it.
 */
class DevOrderSeeder extends Seeder
{
    private const INVOICE_PREFIX = 'AD-ORD-';

    private const ORDER_COUNT = 30;

    /** 2026-11-15 10:00:00 — every other date is derived from this. */
    private const ANCHOR = '2026-11-15 10:00:00';

    /** Holds stay live for the dev window: a pending order must not look expired. */
    private const RESERVATION_TTL_MINUTES = 172800; // 120 days

    /** 0-based order indexes that ship free. */
    private const FREE_SHIPPING_INDEXES = [4, 11, 19, 25];

    private const QTY_CYCLE = [1, 2, 3, 1, 2];

    private const PAYMENT_METHODS = ['Cash On Delivery', 'bkash', 'nagad', 'card'];

    /** index => coupon code */
    private const COUPONS = [
        2 => 'ADORA10',
        14 => 'FLAT100',
        22 => 'WELCOME50',
    ];

    private const ZONES = [
        [
            'name' => 'ঢাকার ভিতরে',
            'amount' => 70,
            'address' => 'House 12, Road 3, Dhanmondi, Dhaka',
        ],
        [
            'name' => 'ঢাকার বাহিরে',
            'amount' => 130,
            'address' => 'Station Road, Agrabad, Chattogram',
        ],
        [
            'name' => 'Standard delivery',
            'amount' => 120,
            'address' => 'Main Road, Chashara, Narayanganj',
        ],
    ];

    private const CUSTOMERS = [
        ['name' => 'Adora Dev Customer 01', 'phone' => '01710000001', 'address' => 'House 12, Road 3, Dhanmondi, Dhaka'],
        ['name' => 'Adora Dev Customer 02', 'phone' => '01710000002', 'address' => 'House 8, Road 5, Uttara, Dhaka'],
        ['name' => 'Adora Dev Customer 03', 'phone' => '01710000003', 'address' => 'House 21, Road 7, Mirpur, Dhaka'],
        ['name' => 'Adora Dev Customer 04', 'phone' => '01710000004', 'address' => 'House 3, Road 2, Banani, Dhaka'],
        ['name' => 'Adora Dev Customer 05', 'phone' => '01710000005', 'address' => 'House 44, Road 11, Gulshan, Dhaka'],
        ['name' => 'Adora Dev Customer 06', 'phone' => '01710000006', 'address' => 'Station Road, Agrabad, Chattogram'],
        ['name' => 'Adora Dev Customer 07', 'phone' => '01710000007', 'address' => 'Zindabazar, Sylhet'],
        ['name' => 'Adora Dev Customer 08', 'phone' => '01710000008', 'address' => 'Shaheb Bazar, Rajshahi'],
        ['name' => 'Adora Dev Customer 09', 'phone' => '01710000009', 'address' => 'Khan Jahan Ali Road, Khulna'],
        ['name' => 'Adora Dev Customer 10', 'phone' => '01710000010', 'address' => 'Nathullabad, Barishal'],
    ];

    /** code => [type, value, min_amount, max_discount, usage_limit] */
    private const COUPON_SPEC = [
        'ADORA10' => ['percent', 10, 200, 150, 100],
        'FLAT100' => ['fixed', 100, 200, null, 50],
        'WELCOME50' => ['fixed', 50, 0, null, null],
    ];

    public function run(): void
    {
        $catalog = $this->catalog();
        $anchor = Carbon::parse(self::ANCHOR);
        $customers = $this->seedCustomers($anchor);
        $this->seedCoupons($anchor);

        $placed = 0;
        $skipped = 0;

        DB::transaction(function () use ($catalog, $anchor, $customers, &$placed, &$skipped): void {
            for ($i = 0; $i < self::ORDER_COUNT; $i++) {
                $invoice = $this->invoice($i);

                if (Order::query()->where('invoice_id', $invoice)->exists()) {
                    $skipped++;

                    continue;
                }

                $placedAt = $anchor->copy()->subDays(29 - $i);
                $final = $this->finalStatus($i);
                $zone = self::ZONES[$i % count(self::ZONES)];
                $isFree = in_array($i, self::FREE_SHIPPING_INDEXES, true);
                $couponCode = self::COUPONS[$i] ?? null;
                $coupon = $couponCode ? Coupon::query()->where('code', $couponCode)->first() : null;

                if ($couponCode && ! $coupon) {
                    throw new RuntimeException("Coupon {$couponCode} is missing; DevOrderSeeder cannot price order {$invoice}.");
                }

                $lines = $this->lines($catalog, $i);
                $subtotal = $this->subtotal($lines);

                $discount = $coupon ? (float) $coupon->calculateDiscount($subtotal) : 0.0;

                if ($coupon && $discount <= 0) {
                    throw new RuntimeException(
                        "Coupon {$couponCode} gives no discount on order {$invoice} (subtotal {$subtotal}); fix the fixture."
                    );
                }

                $customer = $customers[$i % count($customers)];
                $paymentMethod = self::PAYMENT_METHODS[$i % count(self::PAYMENT_METHODS)];

                $order = app(OrderPlacementService::class)->place(
                    $lines,
                    [
                        'customer_id' => (int) $customer['id'],
                        'name' => $customer['name'],
                        'phone' => $customer['phone'],
                        'address' => $customer['address'],
                        'area' => $zone['name'],
                        'payment_method' => $paymentMethod,
                    ],
                    [
                        'invoice_id' => $invoice,
                        'placed_at' => $placedAt,
                        'shipping_fee' => $isFree ? 0.0 : (float) $zone['amount'],
                        'shipping_is_free' => $isFree,
                        'shipping_list_price' => $isFree ? (float) $zone['amount'] : null,
                        'discount' => $discount,
                        'payment_status' => $this->paymentStatus($final, $paymentMethod, $i),
                        'reservation_ttl_minutes' => self::RESERVATION_TTL_MINUTES,
                        // A seeded order must not depend on the fraud API, and
                        // must not slow 30 orders down with 30 HTTP calls.
                        'fraud_check' => false,
                        'source' => 'system',
                        'user_id' => null,
                        // Historical fixtures must not create inbox rows.
                        'notify' => false,
                    ]
                );

                $this->advance($order, $final, $placedAt);

                $placed++;
            }
        });

        $this->assertCounts();

        $this->command?->info(sprintf(
            'Dev orders: %d placed, %d already present (30 expected).',
            $placed,
            $skipped
        ));
    }

    /** @return array<int, array<string, mixed>> catalog rows by index */
    private function catalog(): array
    {
        $path = public_path('seed-assets/adora-2026/catalog.json');

        if (! is_file($path)) {
            throw new RuntimeException("Missing catalog bundle: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        $products = is_array($decoded) ? ($decoded['products'] ?? []) : [];

        if (! is_array($products) || count($products) !== self::ORDER_COUNT) {
            throw new RuntimeException('DevOrderSeeder expects exactly 30 catalog products.');
        }

        $resolved = [];

        foreach (array_values($products) as $index => $row) {
            $slug = (string) ($row['slug'] ?? '');
            $product = DB::table('products')->where('slug', $slug)->first();

            if (! $product) {
                throw new RuntimeException("Product {$slug} is missing; run DevCatalogSeeder first.");
            }

            $variant = DB::table('product_variants')
                ->where('product_id', (int) $product->id)
                ->orderBy('id')
                ->first();

            if (! $variant) {
                throw new RuntimeException("Product {$slug} has no variant; run DevCatalogSeeder first.");
            }

            if (! DB::table('variant_stocks')->where('variant_id', (int) $variant->id)->exists()) {
                throw new RuntimeException("Variant {$variant->id} ({$slug}) has no stock row.");
            }

            $resolved[$index] = [
                'product_id' => (int) $product->id,
                'variant_id' => (int) $variant->id,
                'variant_title' => (string) ($variant->title ?? 'Default'),
                'name' => (string) $product->name,
                'price' => (float) $product->new_price,
                'regular_price' => (float) ($product->old_price ?? $product->new_price),
                'purchase_price' => (float) $product->purchase_price,
            ];
        }

        return $resolved;
    }

    /** @return array<int, array<string, mixed>> */
    private function lines(array $catalog, int $index): array
    {
        $productIndex = $index % self::ORDER_COUNT;
        $qty = self::QTY_CYCLE[$index % count(self::QTY_CYCLE)];
        $lines = [$this->line($catalog[$productIndex], $qty)];

        // Multi-line orders: a second, different product so the order-level
        // totals and the line-level report have something real to add up.
        if ($index % 7 === 0) {
            $secondIndex = ($productIndex + 7) % self::ORDER_COUNT;
            $lines[] = $this->line($catalog[$secondIndex], 1);
        }

        return $lines;
    }

    /** @return array<string, mixed> */
    private function line(array $product, int $qty): array
    {
        return [
            'product_id' => $product['product_id'],
            'variant_id' => $product['variant_id'],
            'name' => $product['name'],
            'qty' => $qty,
            'price' => $product['price'],
            'regular_price' => $product['regular_price'],
            'purchase_price' => $product['purchase_price'],
            'product_size' => $product['variant_title'],
            'product_color' => null,
        ];
    }

    private function subtotal(array $lines): float
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $total += ((float) $line['price']) * ((int) $line['qty']);
        }

        return $total;
    }

    private function finalStatus(int $index): string
    {
        if ($index < 12) {
            return 'delivered';
        }

        if ($index < 22) {
            return 'pending';
        }

        if ($index < 26) {
            return 'shipped';
        }

        if ($index < 28) {
            return 'cancelled';
        }

        return 'returned';
    }

    private function paymentStatus(string $final, string $method, int $index): string
    {
        if ($final === 'cancelled') {
            return 'cancelled';
        }

        if ($final === 'returned') {
            // One return is refunded, one is still pending (§4B.3 fixtures).
            return $index === 28 ? 'refunded' : 'paid';
        }

        if ($final === 'delivered') {
            return 'paid';
        }

        // Shipped but not delivered: COD is still cash on the van.
        return $method === 'Cash On Delivery' ? 'pending' : 'paid';
    }

    /**
     * Walk the order through the real lifecycle with deterministic times.
     *
     * All offsets stay inside the placed day, so every timestamp is in the past
     * relative to the 2026-11-15 anchor and no report has to filter the future.
     */
    private function advance(Order $order, string $final, Carbon $placedAt): void
    {
        if ($final === 'pending') {
            return;
        }

        $lifecycle = app(OrderLifecycle::class);

        $lifecycle->transition($order, 'processing', $placedAt->copy()->addMinutes(30), null, null, false);

        if ($final === 'cancelled') {
            $lifecycle->transition($order, 'cancelled', $placedAt->copy()->addMinutes(45), null, null, false);

            return;
        }

        $lifecycle->transition($order, 'shipped', $placedAt->copy()->addHours(2), null, null, false);

        if ($final === 'shipped') {
            return;
        }

        $lifecycle->transition($order, 'delivered', $placedAt->copy()->addHours(5), null, null, false);

        if ($final === 'returned') {
            $lifecycle->transition($order, 'returned', $placedAt->copy()->addHours(8), null, null, false);
        }
    }

    /**
     * Ten customers, keyed by phone. `customers` has no model-level allow-list
     * for the address fields, so the row is written through the query builder.
     *
     * @return array<int, array{id:int, name:string, phone:string, address:string}>
     */
    private function seedCustomers(Carbon $anchor): array
    {
        $rows = [];

        foreach (self::CUSTOMERS as $offset => $customer) {
            $existing = DB::table('customers')->where('phone', $customer['phone'])->first();

            if ($existing) {
                $rows[] = [
                    'id' => (int) $existing->id,
                    'name' => (string) $existing->name,
                    'phone' => (string) $existing->phone,
                    'address' => (string) ($existing->address ?: $customer['address']),
                ];

                continue;
            }

            $id = (int) DB::table('customers')->insertGetId([
                'name' => $customer['name'],
                'slug' => 'dev-customer-' . str_pad((string) ($offset + 1), 2, '0', STR_PAD_LEFT),
                'phone' => $customer['phone'],
                'email' => sprintf('dev.customer%02d@example.test', $offset + 1),
                'balance' => 0,
                'address' => $customer['address'],
                'verify' => 1,
                'image' => 'public/uploads/default/user.png',
                'password' => bcrypt('DevCustomer!2026'),
                'status' => 'active',
                'created_at' => $anchor->copy()->subDays(40),
                'updated_at' => $anchor->copy()->subDays(40),
            ]);

            $rows[] = [
                'id' => $id,
                'name' => $customer['name'],
                'phone' => $customer['phone'],
                'address' => $customer['address'],
            ];
        }

        return $rows;
    }

    private function seedCoupons(Carbon $anchor): void
    {
        foreach (self::COUPON_SPEC as $code => [$type, $value, $min, $max, $limit]) {
            Coupon::query()->updateOrCreate(
                ['code' => $code],
                [
                    'type' => $type,
                    'value' => $value,
                    'min_amount' => $min,
                    'max_discount' => $max,
                    'start_at' => $anchor->copy()->subDays(45),
                    'end_at' => $anchor->copy()->addDays(45),
                    'usage_limit' => $limit,
                    'status' => 1,
                ]
            );
        }
    }

    private function invoice(int $index): string
    {
        return self::INVOICE_PREFIX . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Fail loudly if the spec produced a different dataset than the plan says.
     * A report seeded from a half-written order set is worse than a failed seed.
     */
    private function assertCounts(): void
    {
        $invoices = DB::table('orders')
            ->where('invoice_id', 'like', self::INVOICE_PREFIX . '%')
            ->count();

        if ($invoices !== self::ORDER_COUNT) {
            throw new RuntimeException("Expected " . self::ORDER_COUNT . " dev orders, found {$invoices}.");
        }

        $orphanLines = DB::table('order_details')
            ->whereIn('order_id', function ($query) {
                $query->select('id')->from('orders')->where('invoice_id', 'like', self::INVOICE_PREFIX . '%');
            })
            ->whereNull('variant_id')
            ->count();

        if ($orphanLines > 0) {
            throw new RuntimeException("{$orphanLines} dev order lines have no variant_id.");
        }

        $statuses = OrderStatus::query()->pluck('id', 'slug');

        foreach (['delivered' => 12, 'pending' => 10, 'shipped' => 4, 'cancelled' => 2, 'returned' => 2] as $slug => $expected) {
            $statusId = $statuses[$slug] ?? null;

            if ($statusId === null) {
                throw new RuntimeException("order_statuses has no '{$slug}' row.");
            }

            $actual = DB::table('orders')
                ->where('invoice_id', 'like', self::INVOICE_PREFIX . '%')
                ->where('order_status', (string) $statusId)
                ->count();

            if ($actual !== $expected) {
                throw new RuntimeException("Expected {$expected} {$slug} orders, found {$actual}.");
            }
        }
    }
}
