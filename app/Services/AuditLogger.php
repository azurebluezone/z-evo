<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Writes the audit trail.
 *
 * ---------------------------------------------------------------------------
 * WHY A SERVICE AND NOT MODEL EVENTS
 * ---------------------------------------------------------------------------
 * Most of the writes this has to cover do not go through Eloquent at all:
 * the inventory ledger is written with DB::table('inventory_movements')->insert()
 * at nine call sites, and an order note is a plain column update. An observer
 * would never have seen half of them, so the trail is written where the
 * change happens.
 *
 * ---------------------------------------------------------------------------
 * IT NEVER BREAKS THE REQUEST
 * ---------------------------------------------------------------------------
 * An audit failure must not cost the admin the thing they were doing: a full
 * disk on the audit table should not roll back an order. Every write is
 * wrapped, and a failure is logged and swallowed.
 */
class AuditLogger
{
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_COURIER = 'courier';

    /**
     * Record one action.
     *
     * @param array{
     *     subject_type?: string|null,
     *     subject_id?: string|int|null,
     *     description?: string|null,
     *     note?: string|null,
     *     before?: array|null,
     *     after?: array|null,
     *     source?: string,
     *     user_id?: int|null,
     *     actor_name?: string|null,
     *     ip?: string|null
     * } $attributes
     */
    public function record(string $action, array $attributes = []): ?AuditLog
    {
        try {
            return AuditLog::query()->create([
                'user_id'      => $attributes['user_id'] ?? (Auth::check() ? (int) Auth::id() : null),
                'actor_name'   => $attributes['actor_name'] ?? (Auth::check() ? (string) Auth::user()->name : null),
                'action'       => $action,
                'subject_type' => $attributes['subject_type'] ?? null,
                'subject_id'   => $attributes['subject_id'] === null ? null : (string) $attributes['subject_id'],
                'description'  => $attributes['description'] ?? null,
                'note'         => isset($attributes['note']) && trim((string) $attributes['note']) !== ''
                    ? (string) $attributes['note']
                    : null,
                'before'       => $attributes['before'] ?? null,
                'after'        => $attributes['after'] ?? null,
                'source'       => $attributes['source'] ?? self::SOURCE_WEB,
                'ip'           => $attributes['ip'] ?? (function_exists('request') && request() ? request()->ip() : null),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', [
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * An order note was rewritten.
     *
     * The previous text is kept: the note column itself only holds the latest
     * value, so without this the old text is gone for good.
     */
    public function orderNote(Order $order, ?string $before, ?string $after): ?AuditLog
    {
        if (trim((string) $before) === trim((string) $after)) {
            return null;   // nothing changed; do not fill the log with noise
        }

        return $this->record('order.note.updated', [
            'subject_type' => 'order',
            'subject_id'   => $order->id,
            'description'  => 'Order note updated for invoice ' . $order->invoice_id,
            'before'       => ['admin_note' => $before],
            'after'        => ['admin_note' => $after],
        ]);
    }

    /** The order moved to another status. */
    public function orderStatus(Order $order, $before, $after, ?string $beforeName = null, ?string $afterName = null): ?AuditLog
    {
        if ((string) $before === (string) $after) {
            return null;
        }

        return $this->record('order.status.changed', [
            'subject_type' => 'order',
            'subject_id'   => $order->id,
            'description'  => 'Order status changed for invoice ' . $order->invoice_id
                . ($beforeName || $afterName ? ' (' . ($beforeName ?: 'none') . ' -> ' . ($afterName ?: 'none') . ')' : ''),
            'before'       => ['order_status' => $before, 'name' => $beforeName],
            'after'        => ['order_status' => $after, 'name' => $afterName],
        ]);
    }

    /** An order was created. */
    public function orderCreated(Order $order, string $source = self::SOURCE_WEB): ?AuditLog
    {
        return $this->record('order.created', [
            'subject_type' => 'order',
            'subject_id'   => $order->id,
            'description'  => 'Order ' . $order->invoice_id . ' created for ' . number_format((float) $order->amount, 0),
            'after'        => [
                'invoice_id' => $order->invoice_id,
                'amount'     => (float) $order->amount,
                'status'     => $order->order_status,
            ],
            'source'       => $source,
        ]);
    }

    /**
     * A row was written to the inventory ledger.
     *
     * The movement itself is the record; this is the readable copy of it, with
     * the "who" that the ledger row itself usually lacks.
     */
    public function inventoryMovement(array $movement, string $note = ''): ?AuditLog
    {
        $type = (string) ($movement['type'] ?? 'movement');

        return $this->record('inventory.movement.recorded', [
            // A movement written by a background sync has no session, so the
            // ledger's own created_by is the best name we have.
            'user_id'      => ! empty($movement['created_by']) ? (int) $movement['created_by'] : null,
            'subject_type' => 'inventory',
            'subject_id'   => $movement['variant_id'] ?? null,
            'description'  => trim($type . ' ' . (string) ($movement['qty_delta'] ?? '')
                . ($note !== '' ? ' - ' . $note : '')),
            'after'        => [
                'variant_id' => $movement['variant_id'] ?? null,
                'type'       => $type,
                'bucket'     => $movement['bucket'] ?? null,
                'qty_delta'  => $movement['qty_delta'] ?? null,
                'ref_type'   => $movement['ref_type'] ?? null,
                'ref_id'     => $movement['ref_id'] ?? null,
                'note'       => $movement['note'] ?? null,
            ],
        ]);
    }

    /** Something happened to a courier account. */
    public function courier(string $action, string $type, array $after = [], string $description = ''): ?AuditLog
    {
        return $this->record($action, [
            'subject_type' => 'courier',
            'subject_id'   => $type,
            'description'  => $description !== '' ? $description : $action . ' on ' . $type,
            'after'        => $after ?: null,
        ]);
    }
}
