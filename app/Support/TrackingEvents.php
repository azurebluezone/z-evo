<?php

namespace App\Support;

/**
 * The canonical event vocabulary shared by the browser collector and the
 * Conversions API endpoint.
 *
 * One list, two callers: if the browser is allowed to send an event, the server
 * is allowed to forward it, and nothing else. Keeping this in one class is what
 * stops the two whitelists from drifting apart (the CAPI route previously
 * accepted *any* event name, including an empty one).
 */
final class TrackingEvents
{
    /** Meta-standard commerce events, plus the two local security events. */
    public const COMMERCE = [
        'PageView',
        'ViewContent',
        'Search',
        'AddToCart',
        'AddToWishlist',
        'AddPaymentInfo',
        'InitiateCheckout',
        'AddShippingInfo',
        'BeginCheckout',
        'Purchase',
        'Lead',
        'CompleteRegistration',
        'Contact',
        'CustomizeProduct',
        'SelectItem',
        'RemoveFromCart',
        'ViewCart',
        'StartTrial',
        'Subscribe',
        'FakeOrderAttempt',
    ];

    /** Prefixes for the first-party engagement events the journey view uses. */
    public const ENGAGEMENT_PREFIXES = [
        'Click_',
        'Scroll_',
        'Outbound_',
        'Engagement_',
    ];

    public static function isCommerce(?string $name): bool
    {
        if (!is_string($name) || trim($name) === '') {
            return false;
        }

        $name = trim($name);
        $canonical = $name === 'BeginCheckout' ? 'InitiateCheckout' : $name;

        return in_array($canonical, self::COMMERCE, true);
    }

    public static function isEngagement(?string $name): bool
    {
        if (!is_string($name) || trim($name) === '') {
            return false;
        }

        $name = trim($name);

        foreach (self::ENGAGEMENT_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)
                && preg_match('/^[A-Za-z0-9_\-]{1,40}$/', substr($name, strlen($prefix))) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function isAllowed(?string $name): bool
    {
        return self::isCommerce($name) || self::isEngagement($name);
    }
}
