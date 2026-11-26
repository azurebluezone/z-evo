<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\VariantStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * The only place order stock changes.
 *
 * ---------------------------------------------------------------------------
 * WHAT THE FOUR BUCKETS MEAN
 * ---------------------------------------------------------------------------
 * `on_hand`   physical units in the warehouse, damaged units included.
 * `reserved`  kept at 0 for backwards compatibility; live holds live in
 *             `inventory_reservations` and are subtracted at read time.
 * `available` sellable units; equals `on_hand` while no damaged return exists.
 * `damaged`   units that came back broken and must never be sold.
 *
 * The movement ledger is the history (`inventory_movements`); `variant_stocks`
 * is its projection. The projection is only ever written here, never by a
 * controller.
 *
 * ---------------------------------------------------------------------------
 * MOVEMENT SEMANTICS
 * ---------------------------------------------------------------------------
 * OPENING_BALANCE  +qty  on_hand and available rise
 * RESERVE          -qty  a hold is created; on_hand does not move
 * SALE             -qty  the hold becomes a sale; on_hand falls
 * RELEASE          +qty  a hold is cancelled; on_hand does not move
 * RETURN           +qty  a sellable return re-enters available
 * RETURN_DAMAGED   +qty  a broken return enters damaged, not available
 *
 * RESERVE/RELEASE are hold movements, so a naive SUM(qty_delta) is not the
 * on-hand figure. Reports must count on-hand types only; the verifier does.
 */
class InventoryLedger
{
    /**
     * Hold stock for a placed order.
     *
     * Idempotent by (variant, RESERVE, order): a repeated seeder run or a
     * retried checkout cannot reserve the same line twice.
     */
    public function reserveOrder(Order $order, ?Carbon $at = null, int $ttlMinutes = 180, ?int $userId = null): void
    {
        $at = $at ?: Carbon::now();

        DB::transaction(function () use ($order, $at, $ttlMinutes, $userId): void {
            foreach ($this->orderLines($order) as $line) {
                $variantId = (int) ($line->variant_id ?? 0);
                $qty = (int) ($line->qty ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if ($variantId <= 0) {
                    throw new RuntimeException(
                        "Order {$order->id} line {$line->id} has no variant_id; cannot reserve stock."
                    );
                }

                $this->ensureStockRow($variantId);

                if ($this->hasMovement('RESERVE', 'order', (int) $order->id, $variantId)) {
                    continue;
                }

                $stock = DB::table('variant_stocks')
                    ->where('variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    throw new RuntimeException("No stock row for variant {$variantId}.");
                }

                $otherHolds = 0;

                if (Schema::hasTable('inventory_reservations')) {
                    $otherHolds = (int) DB::table('inventory_reservations')
                        ->where('variant_id', $variantId)
                        ->where('order_id', '!=', (int) $order->id)
                        ->where('expires_at', '>', $at)
                        ->sum('qty');
                }

                $availableToPromise = max(0, (int) ($stock->available ?? 0) - $otherHolds);

                if ($availableToPromise < $qty) {
                    throw new RuntimeException(
                        "Not enough stock for variant {$variantId}: need {$qty}, available {$availableToPromise}."
                    );
                }

                if (Schema::hasTable('inventory_reservations')) {
                    DB::table('inventory_reservations')->updateOrInsert(
                        [
                            'order_id' => (int) $order->id,
                            'variant_id' => $variantId,
                        ],
                        [
                            'qty' => $qty,
                            'expires_at' => $at->copy()->addMinutes(max(1, $ttlMinutes)),
                            'created_at' => $at,
                            'updated_at' => $at,
                        ]
                    );
                }

                $this->record([
                    'variant_id' => $variantId,
                    'type' => 'RESERVE',
                    'bucket' => 'sellable',
                    'qty_delta' => -$qty,
                    'ref_type' => 'order',
                    'ref_id' => (int) $order->id,
                    'created_by' => $userId,
                    'note' => 'Checkout reservation for order ' . $order->invoice_id,
                ], $at);
            }
        });
    }

    /**
     * Turn every hold on this order into a sale.
     *
     * Called when an order reaches `shipped`, `delivered` or `returned`. The
     * check by movement type makes a second status callback harmless.
     */
    public function saleOrder(Order $order, ?Carbon $at = null, ?int $userId = null): void
    {
        $at = $at ?: Carbon::now();

        DB::transaction(function () use ($order, $at, $userId): void {
            foreach ($this->orderLines($order) as $line) {
                $variantId = (int) ($line->variant_id ?? 0);
                $qty = (int) ($line->qty ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if ($variantId <= 0) {
                    throw new RuntimeException(
                        "Order {$order->id} line {$line->id} has no variant_id; cannot record a sale."
                    );
                }

                $this->ensureStockRow($variantId);

                if ($this->hasMovement('SALE', 'order', (int) $order->id, $variantId)) {
                    $this->dropReservation((int) $order->id, $variantId);

                    continue;
                }

                $stock = DB::table('variant_stocks')
                    ->where('variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if ((int) ($stock->on_hand ?? 0) < $qty) {
                    throw new RuntimeException(
                        "Cannot sell variant {$variantId}: on_hand {$stock->on_hand}, qty {$qty}."
                    );
                }

                DB::table('variant_stocks')
                    ->where('variant_id', $variantId)
                    ->update([
                        'on_hand' => DB::raw('on_hand - ' . $qty),
                        'available' => DB::raw('available - ' . $qty),
                        'updated_at' => $at,
                    ]);

                $this->record([
                    'variant_id' => $variantId,
                    'type' => 'SALE',
                    'bucket' => 'sellable',
                    'qty_delta' => -$qty,
                    'ref_type' => 'order',
                    'ref_id' => (int) $order->id,
                    'created_by' => $userId,
                    'note' => 'Sale for order ' . $order->invoice_id,
                ], $at);

                $this->dropReservation((int) $order->id, $variantId);
            }
        });
    }

    /**
     * Cancel a hold that never became a sale.
     *
     * A cancelled order that already has a SALE must go through a return, not a
     * release; silently restoring stock would make the ledger lie.
     */
    public function releaseOrder(Order $order, ?Carbon $at = null, ?int $userId = null): void
    {
        $at = $at ?: Carbon::now();

        DB::transaction(function () use ($order, $at, $userId): void {
            foreach ($this->orderLines($order) as $line) {
                $variantId = (int) ($line->variant_id ?? 0);
                $qty = (int) ($line->qty ?? 0);

                if ($qty <= 0 || $variantId <= 0) {
                    continue;
                }

                if ($this->hasMovement('SALE', 'order', (int) $order->id, $variantId)) {
                    // The parcel already left, so cancelling the order does not
                    // put the unit back. Only a RETURN does that; releasing here
                    // would make on_hand disagree with the physical shelf.
                    $this->dropReservation((int) $order->id, $variantId);

                    continue;
                }

                if ($this->hasMovement('RELEASE', 'order', (int) $order->id, $variantId)) {
                    $this->dropReservation((int) $order->id, $variantId);

                    continue;
                }

                if (! Schema::hasTable('inventory_reservations')) {
                    continue;
                }

                $holds = DB::table('inventory_reservations')
                    ->where('order_id', (int) $order->id)
                    ->where('variant_id', $variantId)
                    ->first();

                if (! $holds) {
                    continue;
                }

                $this->record([
                    'variant_id' => $variantId,
                    'type' => 'RELEASE',
                    'bucket' => 'sellable',
                    'qty_delta' => $qty,
                    'ref_type' => 'order',
                    'ref_id' => (int) $order->id,
                    'created_by' => $userId,
                    'note' => 'Reservation released for cancelled order ' . $order->invoice_id,
                ], $at);

                $this->dropReservation((int) $order->id, $variantId);
            }
        });
    }

    /**
     * Put a returned line back into stock.
     *
     * Idempotent by (variant, type, ref_type, ref_id): approving the same
     * return twice cannot restock it twice.
     */
    public function restock(
        int $variantId,
        int $qty,
        string $bucket,
        string $refType,
        int $refId,
        ?Carbon $at = null,
        ?int $userId = null,
        string $note = ''
    ): void {
        if ($variantId <= 0 || $qty <= 0) {
            return;
        }

        $bucket = $bucket === 'damaged' ? 'damaged' : 'sellable';
        $type = $bucket === 'damaged' ? 'RETURN_DAMAGED' : 'RETURN';
        $at = $at ?: Carbon::now();

        DB::transaction(function () use ($variantId, $qty, $bucket, $type, $refType, $refId, $at, $userId, $note): void {
            $this->ensureStockRow($variantId);

            if ($this->hasMovement($type, $refType, $refId, $variantId)) {
                return;
            }

            $stock = DB::table('variant_stocks')
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $update = [
                'on_hand' => DB::raw('on_hand + ' . $qty),
                'updated_at' => $at,
            ];

            if ($bucket === 'sellable') {
                $update['available'] = DB::raw('available + ' . $qty);
            } else {
                $update['damaged'] = DB::raw('damaged + ' . $qty);
            }

            DB::table('variant_stocks')->where('variant_id', $variantId)->update($update);

            $this->record([
                'variant_id' => $variantId,
                'type' => $type,
                'bucket' => $bucket,
                'qty_delta' => $qty,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'created_by' => $userId,
                'note' => $note !== '' ? $note : 'Return restock',
            ], $at);
        });
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function orderLines(Order $order)
    {
        return DB::table('order_details')
            ->select('id', 'order_id', 'product_id', 'variant_id', 'qty')
            ->where('order_id', (int) $order->id)
            ->orderBy('id')
            ->get();
    }

    private function hasMovement(string $type, string $refType, int $refId, int $variantId): bool
    {
        return DB::table('inventory_movements')
            ->where('variant_id', $variantId)
            ->where('type', $type)
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->exists();
    }

    private function dropReservation(int $orderId, int $variantId): void
    {
        if (! Schema::hasTable('inventory_reservations')) {
            return;
        }

        DB::table('inventory_reservations')
            ->where('order_id', $orderId)
            ->where('variant_id', $variantId)
            ->delete();
    }

    private function ensureStockRow(int $variantId): void
    {
        VariantStock::ensureRow($variantId);
    }

    /**
     * Set a product's total on-hand stock from an admin bulk edit.
     *
     * The grid shows one figure per product while the SSOT is per variant, so
     * the delta is applied to the product's default variant (or its first
     * active variant) and written to the ledger as a manual adjustment. The
     * returned total is what the grid should repaint.
     */
    public function adjustProductTotalStock(int $productId, int $targetTotal, ?int $userId = null): int
    {
        $targetTotal = max(0, $targetTotal);

        return DB::transaction(function () use ($productId, $targetTotal, $userId) {
            $currentTotal = (int) DB::table('product_variants as v')
                ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
                ->where('v.product_id', $productId)
                ->where('v.status', 1)
                ->sum('s.on_hand');

            $delta = $targetTotal - $currentTotal;
            if ($delta === 0) {
                return $currentTotal;
            }

            $variantId = $this->resolveAdjustmentVariant($productId);
            if ($variantId <= 0) {
                return $currentTotal;
            }

            VariantStock::ensureRow($variantId);

            $stock = DB::table('variant_stocks')
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $previous = (int) ($stock->on_hand ?? 0);
            $onHand = max(0, $previous + $delta);
            $reserved = (int) ($stock->reserved ?? 0);
            $damaged = (int) ($stock->damaged ?? 0);
            $available = max(0, $onHand - $damaged - $reserved);

            DB::table('variant_stocks')->where('variant_id', $variantId)->update([
                'on_hand' => $onHand,
                'available' => $available,
                'updated_at' => now(),
            ]);

            $this->record([
                'variant_id' => $variantId,
                'type' => 'ADJUSTMENT',
                'bucket' => 'sellable',
                'qty_delta' => $onHand - $previous,
                'ref_type' => 'product',
                'ref_id' => $productId,
                'created_by' => $userId,
                'note' => 'Stock edited from the product list (bulk).',
            ], Carbon::now());

            return $currentTotal + ($onHand - $previous);
        });
    }

    /**
     * The variant a product-level stock edit lands on.
     */
    private function resolveAdjustmentVariant(int $productId): int
    {
        $productCode = (string) DB::table('products')->where('id', $productId)->value('product_code');
        if ($productCode !== '') {
            $defaultId = (int) DB::table('product_variants')
                ->where('product_id', $productId)
                ->where('sku', $productCode . '-DEFAULT')
                ->value('id');

            if ($defaultId > 0) {
                return $defaultId;
            }
        }

        return (int) DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->orderBy('id')
            ->value('id');
    }

    /** @param array<string, mixed> $row */
    private function record(array $row, Carbon $at): void
    {
        $row['created_at'] = $at;
        $row['updated_at'] = $at;

        InventoryMovement::record($row);
    }
}
