<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\InventoryLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The report fixtures that a happy-path order set cannot produce.
 *
 * DevOrderSeeder gives reports revenue, stock and status. It cannot give them a
 * return, a refund, coupon attribution or a courier cost, because those are
 * separate events. This seeder adds exactly the four cases the analytics plan
 * needs to display something real:
 *
 *   returns        AD-ORD-0029 (sellable, refunded) and AD-ORD-0030 (damaged,
 *                  refund pending) — header + line items + a restock movement
 *   coupon usages  AD-ORD-0003, 0015, 0023 — attribution rows, then used_count
 *   couriers       two Pathao parcels (delivered) and four Steadfast parcels
 *                  (shipped), each with shipping_cost and an event timeline
 *
 * IDEMPOTENT: every row is matched on its natural key (order, coupon, provider,
 * consignment + status), and the ledger restock is idempotent by
 * (variant, type, return id) inside InventoryLedger itself.
 *
 * It runs AFTER DevOrderSeeder and fails if those orders are not there: silently
 * producing a report with zero returns would hide the missing step.
 */
class DevReportFixtureSeeder extends Seeder
{
    private const INVOICE_PREFIX = 'AD-ORD-';

    public function run(): void
    {
        $this->seedReturns();
        $this->seedCouponUsages();
        $this->seedCourierShipments();

        $this->assertFixtures();
    }

    // ---------------------------------------------------------------- returns

    private function seedReturns(): void
    {
        $ledger = app(InventoryLedger::class);
        $adminId = DB::table('users')->orderBy('id')->value('id');
        $adminId = $adminId !== null ? (int) $adminId : null;

        $spec = [
            'AD-ORD-0029' => [
                'bucket' => 'sellable',
                'refunded' => 1,
                'reason' => 'Size did not fit; customer returned the parcel unopened.',
                'note' => 'Refunded to bkash after inspection.',
            ],
            'AD-ORD-0030' => [
                'bucket' => 'damaged',
                'refunded' => 0,
                'reason' => 'Parcel arrived with a torn seam.',
                'note' => 'Refund pending quality review; unit moved to damaged stock.',
            ],
        ];

        foreach ($spec as $invoice => $return) {
            $order = $this->order($invoice);
            $lines = DB::table('order_details')->where('order_id', (int) $order->id)->orderBy('id')->get();

            if ($lines->isEmpty()) {
                throw new RuntimeException("Order {$invoice} has no lines; cannot build a return.");
            }

            $refundAmount = 0.0;
            foreach ($lines as $line) {
                $refundAmount += ((float) $line->sale_price) * ((int) $line->qty);
            }

            $createdAt = Carbon::parse((string) $order->created_at)->addHours(8);
            $approvedAt = $createdAt->copy()->addHour();

            $returnId = DB::table('order_returns')->where('order_id', (int) $order->id)->value('id');

            if ($returnId === null) {
                $returnId = (int) DB::table('order_returns')->insertGetId([
                    'order_id' => (int) $order->id,
                    'status' => 'approved',
                    'return_mode' => 'customer_return',
                    'restock_condition' => $return['bucket'],
                    'restocked' => 1,
                    'refund_required' => 1,
                    'refunded' => $return['refunded'],
                    'refund_amount' => $refundAmount,
                    'refund_note' => $return['note'],
                    'reason' => $return['reason'],
                    'requested_by' => null,
                    'approved_by' => $adminId,
                    'approved_at' => $approvedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $approvedAt,
                ]);
            }

            foreach ($lines as $line) {
                $qty = (int) $line->qty;
                $lineRefund = ((float) $line->sale_price) * $qty;

                DB::table('order_return_items')->updateOrInsert(
                    [
                        'order_return_id' => $returnId,
                        'order_detail_id' => (int) $line->id,
                    ],
                    [
                        'variant_id' => (int) $line->variant_id,
                        'qty' => $qty,
                        'refund_amount' => $lineRefund,
                        'restock_bucket' => $return['bucket'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]
                );

                // The ledger is the SSOT: a sellable return re-enters available,
                // a damaged one lands in `damaged` and is never sold again.
                $ledger->restock(
                    (int) $line->variant_id,
                    $qty,
                    $return['bucket'],
                    'return',
                    $returnId,
                    $approvedAt,
                    $adminId,
                    'Return restock for ' . $invoice
                );
            }
        }
    }

    // ----------------------------------------------------------- coupon usage

    private function seedCouponUsages(): void
    {
        $map = [
            'AD-ORD-0003' => 'ADORA10',
            'AD-ORD-0015' => 'FLAT100',
            'AD-ORD-0023' => 'WELCOME50',
        ];

        foreach ($map as $invoice => $code) {
            $order = $this->order($invoice);
            $coupon = Coupon::query()->where('code', $code)->first();

            if (! $coupon) {
                throw new RuntimeException("Coupon {$code} is missing; cannot attribute {$invoice}.");
            }

            $exists = DB::table('coupon_usages')
                ->where('order_id', (int) $order->id)
                ->where('coupon_id', (int) $coupon->id)
                ->exists();

            if (! $exists) {
                CouponUsage::record($coupon, (int) $order->id, (float) $order->discount, 'checkout');
            }
        }

        // Normalise the counter from the rows that actually exist. A second run
        // must end on the same number, not on 2x the usages.
        foreach (Coupon::query()->get() as $coupon) {
            $used = (int) DB::table('coupon_usages')->where('coupon_id', (int) $coupon->id)->count();

            if ((int) $coupon->used_count !== $used) {
                DB::table('coupons')->where('id', (int) $coupon->id)->update([
                    'used_count' => $used,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // ----------------------------------------------------------------- courier

    private function seedCourierShipments(): void
    {
        // Two delivered Pathao parcels.
        foreach (['AD-ORD-0001', 'AD-ORD-0002'] as $offset => $invoice) {
            $order = $this->order($invoice);
            $placedAt = Carbon::parse((string) $order->created_at);
            $consignment = 'AD-PTH-' . str_pad((string) ($offset + 1), 4, '0', STR_PAD_LEFT);

            $shipment = CourierShipment::query()->updateOrCreate(
                ['order_id' => (int) $order->id, 'provider' => 'pathao'],
                [
                    'merchant_order_id' => (string) $order->invoice_id,
                    'consignment_id' => $consignment,
                    'tracking_code' => 'PTH-TRK-' . str_pad((string) ($offset + 1), 4, '0', STR_PAD_LEFT),
                    'current_status' => 'Delivered',
                    'shipping_cost' => 60,
                    'raw_payload' => [
                        'code' => 200,
                        'data' => [
                            'consignment_id' => $consignment,
                            'delivery_fee' => 60,
                            'order_status' => 'Delivered',
                        ],
                    ],
                    'last_synced_at' => $placedAt->copy()->addHours(6),
                    'created_at' => $placedAt->copy()->addMinutes(30),
                    'updated_at' => $placedAt->copy()->addHours(6),
                ]
            );

            $this->seedEvent($shipment, $order, 'pathao', $consignment, 'Pending', 'pending', $placedAt->copy()->addMinutes(30));
            $this->seedEvent($shipment, $order, 'pathao', $consignment, 'in_transit', 'processing', $placedAt->copy()->addHours(2));
            $this->seedEvent($shipment, $order, 'pathao', $consignment, 'Delivered', 'delivered', $placedAt->copy()->addHours(6));
        }

        // Four shipped SteadFast parcels, still on the road.
        foreach (['AD-ORD-0023', 'AD-ORD-0024', 'AD-ORD-0025', 'AD-ORD-0026'] as $offset => $invoice) {
            $order = $this->order($invoice);
            $placedAt = Carbon::parse((string) $order->created_at);
            $consignment = 'AD-STF-' . str_pad((string) ($offset + 23), 4, '0', STR_PAD_LEFT);

            $shipment = CourierShipment::query()->updateOrCreate(
                ['order_id' => (int) $order->id, 'provider' => 'steadfast'],
                [
                    'merchant_order_id' => (string) $order->invoice_id,
                    'consignment_id' => $consignment,
                    'tracking_code' => 'STF-TRK-' . str_pad((string) ($offset + 23), 4, '0', STR_PAD_LEFT),
                    'current_status' => 'in_transit',
                    'shipping_cost' => 70,
                    'raw_payload' => [
                        'status' => 200,
                        'consignment' => [
                            'consignment_id' => $consignment,
                            'delivery_fee' => 70,
                            'status' => 'in_transit',
                        ],
                    ],
                    'last_synced_at' => $placedAt->copy()->addHours(4),
                    'created_at' => $placedAt->copy()->addHours(2),
                    'updated_at' => $placedAt->copy()->addHours(4),
                ]
            );

            $this->seedEvent($shipment, $order, 'steadfast', $consignment, 'in_review', 'processing', $placedAt->copy()->addHours(2));
            $this->seedEvent($shipment, $order, 'steadfast', $consignment, 'in_transit', 'processing', $placedAt->copy()->addHours(4));
        }
    }

    private function seedEvent(
        CourierShipment $shipment,
        Order $order,
        string $provider,
        string $consignment,
        string $courierStatus,
        string $orderSlug,
        Carbon $at
    ): void {
        DB::table('courier_events')->updateOrInsert(
            [
                'provider' => $provider,
                'consignment_id' => $consignment,
                'courier_status' => $courierStatus,
            ],
            [
                'shipment_id' => (int) $shipment->id,
                'order_id' => (int) $order->id,
                'order_status_slug' => $orderSlug,
                'payload' => json_encode([
                    'status' => $courierStatus,
                    'consignment_id' => $consignment,
                    'delivery_fee' => (float) $shipment->shipping_cost,
                ], JSON_UNESCAPED_UNICODE),
                'message' => 'Seeded courier timeline event: ' . $courierStatus,
                'created_at' => $at,
            ]
        );
    }

    // ------------------------------------------------------------------ guard

    private function order(string $invoice): Order
    {
        $order = Order::query()->where('invoice_id', $invoice)->first();

        if (! $order) {
            throw new RuntimeException("{$invoice} is missing; run DevOrderSeeder first.");
        }

        return $order;
    }

    private function assertFixtures(): void
    {
        $returns = DB::table('order_returns')->count();
        $returnItems = DB::table('order_return_items')->count();
        $usages = DB::table('coupon_usages')->count();
        $shipments = DB::table('courier_shipments')->count();
        $events = DB::table('courier_events')->count();

        if ($returns < 2 || $returnItems < 3) {
            throw new RuntimeException("Return fixtures incomplete: {$returns} returns, {$returnItems} items.");
        }

        if ($usages !== 3) {
            throw new RuntimeException("Expected 3 coupon usages, found {$usages}.");
        }

        if ($shipments < 6 || $events < 6) {
            throw new RuntimeException("Courier fixtures incomplete: {$shipments} shipments, {$events} events.");
        }

        $this->command?->info(sprintf(
            'Report fixtures: %d returns / %d items · %d coupon usages · %d shipments / %d events.',
            $returns,
            $returnItems,
            $usages,
            $shipments,
            $events
        ));
    }
}
