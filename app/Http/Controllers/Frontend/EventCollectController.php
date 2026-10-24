<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\VisitorMaster;
use App\Services\Analytics\EventRecorder;
use App\Support\TrackingEvents;
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
        $visitorId = $request->input('visitor_id');
        if (empty($visitorId)) {
            $visitorId = $request->cookie('visitor_id');
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
}