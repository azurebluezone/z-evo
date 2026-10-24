<?php

namespace App\Services\Analytics;

use App\Models\EventLog;
use App\Models\VisitorMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central event ingestion used by BOTH tracking paths:
 *
 *   - browser  : POST /events/collect  (the client-side queue in event-layer-head)
 *   - server   : server-side CAPI dispatch (Facebook CAPI / TikTok Events API /
 *                GA4 measurement protocol) fired from PHP
 *
 * Both paths must pass the *same* `event_id` for the same logical event. That id
 * is the deduplication key: the first arrival is stored as `primary`, the second
 * matching arrival is dropped and only recorded in the dedup counter, so the
 * dashboard reports one event instead of two.
 *
 * This class is the only place that writes to `event_logs` for tracking purposes,
 * which is what keeps the Browser vs Server comparison honest.
 */
class EventRecorder
{
    public const METHOD_BROWSER = 'browser';
    public const METHOD_SERVER = 'server';

    public const STATUS_PRIMARY = 'primary';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_BROWSER_ONLY = 'browser_only';
    public const STATUS_SERVER_ONLY = 'server_only';

    /** Dedup-key states returned by resolveEventId(). */
    private const EVENT_ID_OK = 'ok';
    private const EVENT_ID_ABSENT = 'absent';
    private const EVENT_ID_INVALID = 'invalid';

    /**
     * Record a batch of events.
     *
     * @param  string  $visitorId
     * @param  array<int, array<string, mixed>>  $events
     * @param  string  $trackingMethod  self::METHOD_BROWSER|METHOD_SERVER
     * @return array{stored:int, duplicates:int, rejected:array<int,string>}
     */
    public function recordBatch(string $visitorId, array $events, string $trackingMethod): array
    {
        $now = now();
        $rows = [];
        $duplicates = 0;
        $rejected = [];

        $visitor = VisitorMaster::where('visitor_id', $visitorId)->first();

        $browserCount = 0;
        $serverCount = 0;
        $eventCount = 0;
        $pageviewCount = 0;
        $lastEventName = null;
        $lastPageUrl = null;

        foreach ($events as $index => $event) {
            if (!is_array($event)) {
                $rejected[] = "Event at index {$index} is not an object";
                continue;
            }

            $name = $this->canonicalName($event['name'] ?? $event['event_name'] ?? null);

            if ($name === null) {
                $rejected[] = "Event at index {$index}: missing event name";
                continue;
            }

            // "absent" => legacy client that sends no dedup key at all.
            //   Accepted, but such events can never be deduplicated, so the
            //   dashboard counts them as browser-only.
            // "invalid" => the key was supplied but is malformed / overlong.
            //   Rejected. Silently dropping the key would let a client opt out
            //   of deduplication and inflate every metric on the dashboard.
            [$eventId, $eventIdState] = $this->resolveEventId($event['event_id'] ?? null);

            if ($eventIdState === self::EVENT_ID_INVALID) {
                $rejected[] = "Event at index {$index}: invalid event_id";
                continue;
            }

            // --- Deduplication -------------------------------------------------
            // The unique guard on event_id is the hard backstop; this read keeps
            // the dedup counters accurate so the admin can see dedup health.
            if ($eventId !== null) {
                $existing = EventLog::where('event_id', $eventId)->first();

                if ($existing) {
                    $duplicates++;
                    $this->markDuplicate($existing, $trackingMethod);

                    continue;
                }
            }

            $url = $this->pickUrl($event);
            $occurredAt = $this->pickEventTime($event);

            $channel = $event['channel'] ?? ($visitor->channel ?? null);

            $rows[] = [
                'visitor_id' => $visitorId,
                'event_name' => $name,
                'event_id' => $eventId,
                'url' => $url,
                'page_path' => $this->pickPagePath($event, $url),
                'event_details' => $this->encodeDetails($event),
                'event_time' => $occurredAt,
                'tracking_method' => $trackingMethod,
                'channel' => $channel,
                'device_type' => $visitor->device_type ?? null,
                'dedup_status' => $this->statusFor($trackingMethod),
                'click_target' => $this->clamp($event['click_target'] ?? null, 500),
                'outbound_host' => $this->outboundHost($event),
                'scroll_depth' => $this->clampScrollDepth($event['scroll_depth'] ?? null),
                'fbclid' => $this->clamp($event['fbclid'] ?? null),
                'ttclid' => $this->clamp($event['ttclid'] ?? null),
                'gclid' => $this->clamp($event['gclid'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $eventCount++;
            if (strcasecmp($name, 'PageView') === 0) {
                $pageviewCount++;
            }
            $trackingMethod === self::METHOD_SERVER ? $serverCount++ : $browserCount++;
            $lastEventName = $name;
            $lastPageUrl = $url;
        }

        if (count($rows) > 0) {
            // insert() would blow up on a concurrent duplicate that slipped past
            // the read above; insertOrIgnore keeps the endpoint non-fatal.
            $inserted = DB::table('event_logs')->insertOrIgnore($rows);
        } else {
            $inserted = 0;
        }

        $this->touchVisitor($visitorId, [
            'browser_delta' => $browserCount,
            'server_delta' => $serverCount,
            'event_delta' => $eventCount,
            'pageview_delta' => $pageviewCount,
            'last_event_name' => $lastEventName,
            'last_page_url' => $lastPageUrl,
            'last_seen_at' => $now,
        ]);

        return [
            'stored' => (int) $inserted,
            'duplicates' => $duplicates,
            'rejected' => $rejected,
        ];
    }

    /**
     * Mark the already-stored row as having been corroborated by the other path.
     *
     * This is what turns `browser_only` into `primary`: once the server-side copy
     * of the same event_id shows up, the event was deduplicated by the ad platform
     * and should be reported once.
     */
    private function markDuplicate(EventLog $existing, string $trackingMethod): void
    {
        $status = $trackingMethod === self::METHOD_SERVER
            ? self::STATUS_PRIMARY
            : ($existing->dedup_status === self::STATUS_SERVER_ONLY
                ? self::STATUS_PRIMARY
                : $existing->dedup_status);

        $existing->dedup_status = $status;
        $existing->dedup_matched_with = $trackingMethod;
        $existing->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function touchVisitor(string $visitorId, array $payload): void
    {
        if (($payload['event_delta'] ?? 0) === 0
            && ($payload['browser_delta'] ?? 0) === 0
            && ($payload['server_delta'] ?? 0) === 0
            && ($payload['last_event_name'] === null)) {
            return;
        }

        VisitorMaster::where('visitor_id', $visitorId)->update([
            // Increments are done in SQL so concurrent batches cannot lose counts.
            'event_count' => DB::raw('COALESCE(event_count, 0) + ' . (int) $payload['event_delta']),
            'pageview_count' => DB::raw('COALESCE(pageview_count, 0) + ' . (int) $payload['pageview_delta']),
            'browser_event_count' => DB::raw('COALESCE(browser_event_count, 0) + ' . (int) $payload['browser_delta']),
            'server_event_count' => DB::raw('COALESCE(server_event_count, 0) + ' . (int) $payload['server_delta']),
            'last_event_name' => $payload['last_event_name'],
            'last_page_url' => $payload['last_page_url'],
            'last_seen_at' => $payload['last_seen_at'],
            'updated_at' => now(),
        ]);
    }

    /**
     * Normalise the known aliases so one logical event is never counted twice.
     */
    public function canonicalName($name): ?string
    {
        if (!is_string($name)) {
            return null;
        }

        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 50) {
            return null;
        }

        // Custom engagement events are namespaced with "Click_", "Scroll_" ...
        // which are longer than the standard Meta names, so they are allowed
        // through on a looser rule.
        if (preg_match('/^(Click_|Scroll_|Outbound_|Engagement_)[A-Za-z0-9_\-]{1,40}$/', $name)) {
            return $name;
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{1,49}$/', $name)) {
            return null;
        }

        return $name === 'BeginCheckout' ? 'InitiateCheckout' : $name;
    }

    /**
     * event_id is the cross-platform dedup key; it must be bounded and hex-safe.
     *
     * @return array{0: ?string, 1: string} [eventId, state]
     */
    private function resolveEventId($eventId): array
    {
        // Not supplied (or explicitly null) — legacy client, accept without a key.
        if ($eventId === null || $eventId === '') {
            return [null, self::EVENT_ID_ABSENT];
        }

        if (!is_string($eventId)) {
            return [null, self::EVENT_ID_INVALID];
        }

        $eventId = trim($eventId);

        if ($eventId === '' || mb_strlen($eventId) > 64) {
            return [null, self::EVENT_ID_INVALID];
        }

        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $eventId)) {
            return [null, self::EVENT_ID_INVALID];
        }

        return [$eventId, self::EVENT_ID_OK];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function pickUrl(array $event): ?string
    {
        foreach (['url', 'page_url', 'event_source_url'] as $key) {
            if (!empty($event[$key]) && is_string($event[$key])) {
                return $this->clamp($event[$key], 1000);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function pickEventTime(array $event): Carbon
    {
        $raw = $event['ts'] ?? $event['event_time'] ?? null;

        if (!empty($raw)) {
            if (is_numeric($raw)) {
                $seconds = (int) $raw;

                // Tolerate millisecond timestamps from JS.
                if ($seconds > 9999999999) {
                    $seconds = (int) floor($seconds / 1000);
                }

                return Carbon::createFromTimestamp($seconds);
            }

            try {
                return Carbon::parse($raw);
            } catch (\Throwable $e) {
                // fall through to now()
            }
        }

        return now();
    }

    /**
     * An explicit `page_path` wins: the client already knows the route and the
     * collector normalises it. Deriving from the URL is the legacy fallback.
     *
     * @param  array<string, mixed>  $event
     */
    private function pickPagePath(array $event, ?string $url): ?string
    {
        if (!empty($event['page_path']) && is_string($event['page_path'])) {
            return $this->clamp($event['page_path'], 500);
        }

        return $this->pagePathFromUrl($url);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function pagePathFromUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return null;
        }

        return $this->clamp($path, 500);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function outboundHost(array $event): ?string
    {
        $raw = $event['outbound_host'] ?? null;

        if (is_string($raw) && $raw !== '') {
            return $this->clamp($raw);
        }

        $url = $event['url'] ?? null;

        if (!is_string($url) || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $this->clamp($host) : null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function clampScrollDepth($value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $depth = (int) $value;

        return max(0, min(100, $depth));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function encodeDetails(array $event): ?string
    {
        $details = $event['event_data'] ?? $event['event_details'] ?? null;

        if ($details === null) {
            // Keep the handful of fields we already promote into real columns out
            // of the blob so it does not duplicate storage.
            unset(
                $event['url'], $event['page_url'], $event['event_source_url'],
                $event['ts'], $event['event_time'], $event['event_id'],
                $event['channel'], $event['click_target'], $event['outbound_host'],
                $event['scroll_depth'], $event['fbclid'], $event['ttclid'],
                $event['gclid'], $event['name'], $event['event_name'],
                $event['page_path']
            );

            $details = $event;
        }

        if (!is_array($details) || $details === []) {
            return null;
        }

        $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : mb_substr($encoded, 0, 60000);
    }

    private function statusFor(string $trackingMethod): string
    {
        return $trackingMethod === self::METHOD_SERVER
            ? self::STATUS_SERVER_ONLY
            : self::STATUS_BROWSER_ONLY;
    }

    private function clamp(?string $value, int $max = 190): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}