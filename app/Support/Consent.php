<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Cookie consent state, read from the first-party `consent` cookie.
 *
 * The cookie is written by the banner (plain JS, not HttpOnly on purpose - the
 * page has to be able to read and update it) and it is excluded from cookie
 * encryption so a raw JSON value can be stored and parsed here.
 *
 * Shape: {"analytics":"granted|denied","marketing":"granted|denied","v":1,"ts":169...}
 *
 * Defaults are DENIED. Nothing is forwarded to Meta, TikTok, GA4 or a GTM
 * container until the visitor says otherwise; first-party storage continues and
 * records the decision on every row, which is what makes the funnel honest
 * instead of silently empty.
 */
final class Consent
{
    public const COOKIE = 'consent';

    /** Bump when the categories or their meaning change: the banner re-asks. */
    public const VERSION = 1;

    /** Remember the choice for 180 days. */
    public const TTL_DAYS = 180;

    public const GRANTED = 'granted';
    public const DENIED = 'denied';

    /**
     * @return array{analytics: string, marketing: string, v: int, ts: int|null, decided: bool}
     */
    public static function state(?Request $request = null): array
    {
        $request = $request ?? request();

        $fallback = [
            'analytics' => self::DENIED,
            'marketing' => self::DENIED,
            'v' => self::VERSION,
            'ts' => null,
            'decided' => false,
        ];

        $raw = $request->cookie(self::COOKIE);

        if (!is_string($raw) || $raw === '') {
            return $fallback;
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return $fallback;
        }

        // A decision taken under an older policy does not carry over: the
        // visitor is asked again rather than silently opted in.
        if ((int) ($decoded['v'] ?? 0) !== self::VERSION) {
            return $fallback;
        }

        return [
            'analytics' => self::normalise($decoded['analytics'] ?? null),
            'marketing' => self::normalise($decoded['marketing'] ?? null),
            'v' => self::VERSION,
            'ts' => isset($decoded['ts']) ? (int) $decoded['ts'] : null,
            'decided' => true,
        ];
    }

    /** Analytics: GA4 / GTM container only. First-party storage is unaffected. */
    public static function analytics(?Request $request = null): bool
    {
        return self::state($request)['analytics'] === self::GRANTED;
    }

    /** Marketing: Meta pixel, Meta CAPI, TikTok pixel/Events API. */
    public static function marketing(?Request $request = null): bool
    {
        return self::state($request)['marketing'] === self::GRANTED;
    }

    public static function hasDecision(?Request $request = null): bool
    {
        return self::state($request)['decided'];
    }

    /**
     * The value every stored event carries, so a report can separate rows the
     * visitor allowed from rows stored under a denial.
     *
     * @return array{analytics: string, marketing: string, v: int}
     */
    public static function forEvent(?Request $request = null): array
    {
        $state = self::state($request);

        return [
            'analytics' => $state['analytics'],
            'marketing' => $state['marketing'],
            'v' => self::VERSION,
        ];
    }

    private static function normalise($value): string
    {
        return $value === self::GRANTED ? self::GRANTED : self::DENIED;
    }
}
