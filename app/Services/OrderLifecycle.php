<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * One status-change routine for every caller.
 *
 * The admin list, the Kanban board and the courier webhook all move an order
 * through the same lifecycle. Putting it here means the stock side-effect and
 * the audit row cannot be forgotten by one of them.
 *
 * `delivered_at` and `cancelled_at` are set once, from the transition time, so
 * a later edit to `updated_at` cannot move a delivery to another month.
 */
class OrderLifecycle
{
    public function transition(
        Order $order,
        string $statusSlug,
        ?Carbon $at = null,
        ?int $userId = null,
        ?string $note = null,
        bool $notify = true
    ): void {
        $status = OrderStatus::query()->where('slug', $statusSlug)->first();

        if (! $status) {
            throw new RuntimeException("Unknown order status slug: {$statusSlug}");
        }

        $at = $at ?: Carbon::now();
        $beforeId = $order->order_status;
        $before = OrderStatus::find($beforeId);
        $beforeName = $before?->name;
        $beforeSlug = (string) ($before?->slug ?? '');

        if ((string) $beforeId === (string) $status->id) {
            return;
        }

        DB::transaction(function () use (
            $order,
            $status,
            $statusSlug,
            $at,
            $userId,
            $note,
            $beforeId,
            $beforeName
        ): void {
            $order->order_status = $status->id;

            if ($statusSlug === 'delivered' && ! $order->delivered_at) {
                $order->delivered_at = $at;
            }

            if ($statusSlug === 'cancelled' && ! $order->cancelled_at) {
                $order->cancelled_at = $at;
            }

            $order->updated_at = $at;
            $order->save();

            $ledger = app(InventoryLedger::class);

            if (in_array($statusSlug, ['shipped', 'delivered', 'returned'], true)) {
                $ledger->saleOrder($order, $at, $userId);
            } elseif ($statusSlug === 'cancelled') {
                $ledger->releaseOrder($order, $at, $userId);
            }

            $audit = app(AuditLogger::class);
            $audit->orderStatus($order, $beforeId, $status->id, $beforeName, $status->name);

            if ($note !== null && trim($note) !== '') {
                $audit->record('order.status.note', [
                    'subject_type' => 'order',
                    'subject_id' => $order->id,
                    'description' => 'Status note for invoice ' . $order->invoice_id,
                    'note' => $note,
                    'source' => AuditLogger::SOURCE_SYSTEM,
                ]);
            }
        });

        // Committed: tell the customer. A repeated webhook is already a no-op
        // because the equal-status check above returned before the transaction.
        if ($notify) {
            event(new OrderStatusChanged($order->fresh(), $beforeSlug, $statusSlug, $note));
        }
    }
}
