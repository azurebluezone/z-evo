<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache policy for the admin analytics dashboard.
 *
 * The dashboard used to cache its aggregates for three hours with a fixed key.
 * Three hours is long enough that a shop can take orders all morning and still
 * see yesterday's numbers, and there was no invalidation when an order or an
 * event arrived. Five minutes is the freshness/load compromise; a write bumps
 * the version, so the next request rebuilds immediately instead of waiting for
 * the TTL to expire.
 *
 * The version lives in the cache, not in the key alone: old payloads become
 * unreachable after a bump and expire on their own, so no delete-list has to
 * be maintained per filter combination.
 */
final class AnalyticsCache
{
    public const TTL_MINUTES = 5;

    private const VERSION_KEY = 'admin.analytics.cache_version';

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function key(array $filters): string
    {
        return 'admin.analytics.summary.v' . self::version() . '.' . md5(json_encode($filters));
    }

    public static function version(): int
    {
        $value = Cache::get(self::VERSION_KEY, 1);

        return is_numeric($value) ? max(1, (int) $value) : 1;
    }

    /**
     * Called by every write that can change a dashboard number: event
     * ingestion and order saves. Cheap by design - it must be safe to call on
     * every order row update.
     */
    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
