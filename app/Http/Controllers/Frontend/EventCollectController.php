<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\VisitorMaster;
use App\Services\Analytics\EventRecorder;
use App\Support\Consent;
use App\Support\TrackingEvents;
use App\Support\TrackToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public, unauthenticated ingestion endpoint for browser-side tracking.
 *
 * Reached by `navigator.sendBeacon` on page unload, so it must stay fast and it
 * must never blow up the visitor's page. Payloads are size-capped and event
 * names are whitelist-checked before anything is written.
 */
class EventCollectController extends Controller
{
    private const MAX_BATCH = 50;

    public function collect(Request $request)
    {
        if (!$this->originAllowed($request)) {
            return response()->json(['message' => 'Origin not allowed'], 403);
        }

        // Identity is proven, not asserted: a valid signed token overrides both
        // the cookie and the body. Without one, a body visitor_id that disagrees
        // with the cookie is rejected instead of being allowed to poison another
        // visitor's timeline. A cached page with no token at all still works.
        $token = $request->input('track_token');
        $token = is_string($token) ? $token : null;

        $cookieVisitorId = $request->cookie('visitor_id');
        $cookieSessionId = $request->cookie('session_id');
        $bodyVisitorId = $request->input('visitor_id');

        $hasToken = is_string($token) && $token !== '';

        if ($hasToken && !TrackToken::verify($token, $cookieVisitorId, $cookieSessionId)) {
            return response()->json(['message' => 'Invalid track_token'], 422);
        }

        if ($hasToken) {
            // The token proves the cookies; the body is never trusted over them.
            $visitorId = $cookieVisitorId;
            $sessionId = $cookieSessionId;
        } else {
            if (is_string($bodyVisitorId) && $bodyVisitorId !== ''
                && is_string($cookieVisitorId) && $cookieVisitorId !== ''
                && $bodyVisitorId !== $cookieVisitorId) {
                return response()->json(['message' => 'visitor_id mismatch'], 422);
            }

            $visitorId = $cookieVisitorId ?: $bodyVisitorId;
            $sessionId = $cookieSessionId;
        }

        if (empty($visitorId) || !Str::isUuid($visitorId)) {
            return response()->json(['message' => 'Invalid visitor_id'], 422);
        }

        $events = $request->input('events', []);
        if (!is_array($events)) {
            return response()->json(['message' => 'Invalid events payload'], 422);
        }

        if (count($events) > self::MAX_BATCH) {
            return response()->json(['message' => 'Too many events'], 422);
        }

        $visitor = VisitorMaster::where('visitor_id', $visitorId)->first();
        if ($visitor && (int) $visitor->status === 0) {
            abort(403, '403 Forbidden');
        }

        if (!$visitor) {
            // sendBeacon can race the normal page request that would have created
            // the visitor row, so bootstrap defensively with what we know here.
            $visitor = VisitorMaster::create([
                'visitor_id' => $visitorId,
                'ip' => (string) $request->getClientIp(),
                'user_agent' => $request->userAgent(),
                'device_type' => (new \App\Services\Analytics\UserAgentParser())
                    ->parse($request->userAgent())['device_type'],
                'status' => 1,
                'last_seen_at' => now(),
            ]);
        }

        $filtered = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $rawName = $event['name'] ?? $event['event_name'] ?? null;

            if (!$this->isAllowedEventName($rawName)) {
                continue;
            }

            $filtered[] = $event;
        }

        if (count($filtered) === 0) {
            return response()->json(['message' => 'No valid events'], 200);
        }

        // Enrich the batch with context the middleware already resolved so the
        // dashboard can group without a second join on every query.
        foreach ($filtered as &$event) {
            $event['channel'] = $event['channel'] ?? $visitor->channel;
            $event['fbclid'] = $event['fbclid'] ?? $request->query('fbclid');
            $event['ttclid'] = $event['ttclid'] ?? $request->query('ttclid');
            $event['gclid'] = $event['gclid'] ?? $request->query('gclid');
            $event['session_id'] = $sessionId;
            $event['page_path'] = $this->resolvePagePath($event);
            // Server-side truth, not the browser's claim: the cookie is the only
            // place consent is actually stored, so a script cannot assert it.
            $event['consent'] = Consent::forEvent($request);
        }
        unset($event);

        try {
            $result = app(EventRecorder::class)->recordBatch(
                $visitorId,
                $filtered,
                EventRecorder::METHOD_BROWSER
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Failed to record events'], 500);
        }

        return response()->json([
            'message' => 'OK',
            'saved' => $result['stored'],
            'duplicates' => $result['duplicates'],
        ]);
    }

    /**
     * Whitelist gate for a public endpoint. The vocabulary itself lives in
     * App\Support\TrackingEvents so the collector and the Conversions API relay
     * can never accept different names.
     */
    private function isAllowedEventName($name): bool
    {
        return TrackingEvents::isAllowed(is_string($name) ? $name : null);
    }

    /**
     * The endpoint is public by necessity (sendBeacon carries no CSRF token), so
     * it is restricted to our own pages instead.
     *
     * Order of checks:
     *   1. `Sec-Fetch-Site: cross-site` is a hard no - the browser states it.
     *   2. An explicit Origin must match an allowed host.
     *   3. Otherwise a Referer must match; sendBeacon always sends one from a page.
     *   4. Neither header present means a non-browser caller. There is no identity
     *      to steal in that case (a random UUID buys nothing), so it is allowed
     *      rather than breaking CLI probes and old clients.
     */
    private function originAllowed(Request $request): bool
    {
        $site = strtolower((string) $request->headers->get('sec-fetch-site'));

        if ($site === 'cross-site') {
            return false;
        }

        $allowed = $this->allowedHosts();

        $origin = $request->headers->get('origin');

        if (is_string($origin) && $origin !== '' && strtolower($origin) !== 'null') {
            return in_array($this->hostOf($origin), $allowed, true);
        }

        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return in_array($this->hostOf($referer), $allowed, true);
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function allowedHosts(): array
    {
        $hosts = [
            $this->hostOf((string) config('app.url')),
            $this->hostOf((string) config('tracking.host')),
            // The host that served the page. An attacker's Origin is their own
            // host, never ours, so including this does not widen the trust.
            strtolower((string) request()->getHost()),
        ];

        return array_values(array_unique(array_filter($hosts)));
    }

    private function hostOf(?string $url): string
    {
        if (!is_string($url) || $url === '') {
            return '';
        }

        $host = parse_url(str_contains($url, '://') ? $url : 'https://' . $url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    /**
     * Every row must have a page path: the journey view groups by it, and a NULL
     * silently disappears from the funnel. Client-side rows now send it, but a
     * cached legacy page may only send `url`, so derive it here as a fallback.
     *
     * @param  array<string, mixed>  $event
     */
    private function resolvePagePath(array $event): string
    {
        if (!empty($event['page_path']) && is_string($event['page_path'])) {
            return mb_substr($event['page_path'], 0, 500);
        }

        foreach (['url', 'page_url', 'event_source_url'] as $key) {
            if (empty($event[$key]) || !is_string($event[$key])) {
                continue;
            }

            $path = parse_url($event[$key], PHP_URL_PATH);

            if (is_string($path) && $path !== '') {
                return mb_substr($path, 0, 500);
            }
        }

        return '/';
    }
}