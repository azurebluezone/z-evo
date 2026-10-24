<?php

namespace App\Support;

use App\Models\Order;

/**
 * Deterministic event identifiers.
 *
 * Meta (and GA4 via sGTM) deduplicate a browser hit against a server hit only
 * when both carry the *same* event_id. Random ids on each side can never match,
 * which is why the order-scoped id is derived from the order itself: the
 * controller that commits the order, the confirmation page that fires the pixel,
 * and any retried delivery all compute the same value without storing it.
 *
 * Shape: 32 lowercase hex characters, which satisfies
 * EventRecorder::resolveEventId() (`[A-Za-z0-9_-]{1,64}`) and Meta's own limit.
 */
final class EventId
{
    /**
     * The one Purchase id for an order.
     *
     * Includes invoice_id so a re-used auto-increment id (restored dump, second
     * shop on the same database) cannot collide with an older conversion.
     */
    public static function purchase(Order $order): string
    {
        return self::hash('purchase', (string) $order->id, (string) $order->invoice_id);
    }

    /**
     * Same id from raw values, for places that only have the id (views, jobs).
     */
    public static function purchaseFor(int|string $orderId, int|string|null $invoiceId): string
    {
        return self::hash('purchase', (string) $orderId, (string) $invoiceId);
    }

    private static function hash(string $scope, string ...$parts): string
    {
        return substr(hash('sha256', $scope . '|' . implode('|', $parts)), 0, 32);
    }
}
