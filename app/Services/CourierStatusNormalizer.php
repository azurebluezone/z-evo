<?php

namespace App\Services;

/**
 * Courier status -> provider-agnostic bucket.
 *
 * One mapping for every write path and every report. The model hook on
 * CourierShipment calls this whenever current_status changes, so the API
 * senders and the webhook cannot drift apart. Reports read only
 * status_normalized; raw current_status stays as display and support data.
 *
 * Unknown words are not forced into a bucket: they keep their own lowercased
 * slug, so an unrecognised status is visible instead of silently counting as
 * "in transit" (docs/report-metrics.md 3.4).
 */
class CourierStatusNormalizer
{
    public const DELIVERED = 'delivered';
    public const RETURNED = 'returned';
    public const CANCELLED = 'cancelled';
    public const IN_TRANSIT = 'in_transit';
    public const PENDING = 'pending';

    private const MAP = [
        // Delivered.
        'delivered' => self::DELIVERED,
        'success' => self::DELIVERED,
        'completed' => self::DELIVERED,
        'partial_delivered' => self::DELIVERED,
        'partial_delivered_approval_pending' => self::DELIVERED,
        'delivered_approval_pending' => self::DELIVERED,

        // Returned to the merchant.
        'returned' => self::RETURNED,
        'return' => self::RETURNED,
        'reverse' => self::RETURNED,
        'return_dispatched' => self::RETURNED,
        'return_approval_pending' => self::RETURNED,
        'return_requested' => self::RETURNED,
        'return_processing' => self::RETURNED,

        // Failed delivery / cancelled by either side.
        'cancelled' => self::CANCELLED,
        'canceled' => self::CANCELLED,
        'cancelled_approval_pending' => self::CANCELLED,
        'failed' => self::CANCELLED,

        // Moving towards the customer.
        'in_transit' => self::IN_TRANSIT,
        'in-transit' => self::IN_TRANSIT,
        'shipped' => self::IN_TRANSIT,
        'dispatched' => self::IN_TRANSIT,
        'picked' => self::IN_TRANSIT,
        'picked_up' => self::IN_TRANSIT,
        'assigned' => self::IN_TRANSIT,
        'processing' => self::IN_TRANSIT,
        'out_for_delivery' => self::IN_TRANSIT,
        'on_the_way' => self::IN_TRANSIT,
        'on_the_way_to_deliver' => self::IN_TRANSIT,
        'hold' => self::IN_TRANSIT,
        'waiting_for_pickup' => self::IN_TRANSIT,

        // Created but not moving yet.
        'pending' => self::PENDING,
        'created' => self::PENDING,
        'booked' => self::PENDING,
        'ready_to_ship' => self::PENDING,
    ];

    public static function normalize(?string $status): string
    {
        $raw = strtolower(trim((string) $status));

        if ($raw === '') {
            return self::PENDING;
        }

        if (isset(self::MAP[$raw])) {
            return self::MAP[$raw];
        }

        $slug = trim((string) preg_replace('/[^a-z0-9_]+/', '_', substr($raw, 0, 24)), '_');

        return $slug === '' ? self::PENDING : $slug;
    }

    /**
     * Buckets that mean the parcel will not be paid for: returned or cancelled.
     *
     * @return array<int, string>
     */
    public static function failedBuckets(): array
    {
        return [self::RETURNED, self::CANCELLED];
    }
}
