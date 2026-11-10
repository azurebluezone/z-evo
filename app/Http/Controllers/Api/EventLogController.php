<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisitorMaster;
use App\Services\Analytics\EventRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Legacy event endpoints.
 *
 * They used to insert one row per event, which is one INSERT per row and a
 * second write path with different dedup rules from /events/collect.
 * Both endpoints now hand the whole batch to EventRecorder, so the browser and
 * server paths share the same canonical names, event_id dedup, visitor
 * counters and analytics-cache invalidation.
 */
class EventLogController extends Controller
{
    public function batch(Request $request, EventRecorder $recorder)
    {
        $visitorId = $request->cookie('visitor_id', $request->input('visitor_id'));

        if (!$visitorId) {
            $visitorId = $request->header('X-Visitor-Id');
        }

        if (!$visitorId) {
            return response()->json(['error' => 'visitor_id is required'], 400);
        }

        $visitor = VisitorMaster::where('visitor_id', $visitorId)->first();

        if (!$visitor) {
            $visitor = VisitorMaster::create([
                'visitor_id' => $visitorId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 1,
            ]);
        }

        if ((int) $visitor->status === 0) {
            return response()->json(['error' => 'Visitor is blocked'], 403);
        }

        $events = $request->input('events', []);

        if (!is_array($events) || empty($events)) {
            return response()->json(['error' => 'events array is required'], 400);
        }

        if (count($events) > 100) {
            return response()->json(['error' => 'Too many events in batch (max 100)'], 422);
        }

        $normalised = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                $normalised[] = $event;
                continue;
            }

            // The recorder understands both the new (`name`, `ts`) and the
            // legacy (`event_name`, `event_time`) keys. Fill the shared context
            // here so every row carries the same URL and channel.
            $event['url'] = $event['url'] ?? $request->header('Referer') ?? $request->url();
            $event['channel'] = $event['channel'] ?? ($visitor->channel ?? null);
            $event['session_id'] = $event['session_id'] ?? $request->cookie('session_id');

            $normalised[] = $event;
        }

        $result = $recorder->recordBatch($visitorId, $normalised, EventRecorder::METHOD_BROWSER);

        if ($result['stored'] > 0 && (empty($visitor->country) || empty($visitor->city))) {
            $this->fillVisitorGeo($request, $visitor);
        }

        return response()->json([
            'success' => true,
            'stored' => $result['stored'],
            'duplicates' => $result['duplicates'],
            'total' => count($events),
            'errors' => $result['rejected'],
            'visitor_id' => $visitorId,
        ]);
    }

    public function store(Request $request, EventRecorder $recorder)
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|max:50',
            'event_data' => 'nullable|array',
            'url' => 'nullable|string|max:2048',
            'event_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $visitorId = $request->cookie('visitor_id', $request->header('X-Visitor-Id'));

        if (!$visitorId) {
            return response()->json(['error' => 'visitor_id is required'], 400);
        }

        $visitor = VisitorMaster::firstOrCreate(
            ['visitor_id' => $visitorId],
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 1,
            ]
        );

        if ((int) $visitor->status === 0) {
            return response()->json(['error' => 'Visitor is blocked'], 403);
        }

        $result = $recorder->recordBatch($visitorId, [[
            'name' => $request->input('event_name'),
            'event_data' => $request->input('event_data'),
            'url' => $request->input('url') ?? $request->header('Referer') ?? $request->url(),
            'ts' => $request->input('event_time'),
        ]], EventRecorder::METHOD_BROWSER);

        if ($result['stored'] === 0 && $result['duplicates'] === 0) {
            return response()->json([
                'error' => $result['rejected'][0] ?? 'Failed to store event',
            ], 422);
        }

        if ($result['stored'] > 0) {
            $this->fillVisitorGeo($request, $visitor);
        }

        return response()->json([
            'success' => true,
            'message' => $result['duplicates'] > 0 ? 'Event already stored' : 'Event stored',
        ]);
    }

    private function fillVisitorGeo(Request $request, VisitorMaster $visitor): void
    {
        try {
            $position = \Stevebauman\Location\Facades\Location::get($request->ip());

            if (!$position) {
                return;
            }

            $updateData = [];

            if (empty($visitor->country) && !empty($position->countryName)) {
                $updateData['country'] = $position->countryName;
            }

            if (empty($visitor->city) && !empty($position->cityName)) {
                $updateData['city'] = $position->cityName;
            }

            if (!empty($updateData)) {
                $visitor->update($updateData);
            }
        } catch (\Throwable $e) {
            Log::debug('Visitor geo lookup skipped', ['error' => $e->getMessage()]);
        }
    }
}
