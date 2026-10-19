<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\VisitorMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EventLogController extends Controller
{
    /**
     * Receive batched events from client-side tracking JS
     * Stores events in event_logs table
     */
    public function batch(Request $request)
    {
        $visitorId = $request->cookie('visitor_id', $request->input('visitor_id'));

        if (!$visitorId) {
            // Try from visitor header
            $visitorId = $request->header('X-Visitor-Id');
        }

        if (!$visitorId) {
            return response()->json(['error' => 'visitor_id is required'], 400);
        }

        // Validate visitor exists
        $visitor = VisitorMaster::where('visitor_id', $visitorId)->first();
        if (!$visitor) {
            // Create visitor if not exists (defensive)
            $visitor = VisitorMaster::create([
                'visitor_id' => $visitorId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 1,
            ]);
        }

        // Check if visitor is blocked
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

        $stored = 0;
        $errors = [];

        foreach ($events as $index => $event) {
            if (!is_array($event)) {
                $errors[] = "Event at index {$index} is not an object";
                continue;
            }

            $eventName = $event['event_name'] ?? null;
            if (!is_string($eventName) || trim($eventName) === '') {
                $errors[] = "Event at index {$index}: event_name is required";
                continue;
            }

            $eventName = trim($eventName);

            // Validate event name against known patterns
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,50}$/', $eventName)) {
                $errors[] = "Event at index {$index}: invalid event_name format";
                continue;
            }

            try {
                EventLog::create([
                    'visitor_id' => $visitorId,
                    'event_name' => $eventName,
                    'url' => $event['url'] ?? $request->header('Referer') ?? request()->url(),
                    'event_details' => isset($event['event_data']) ? json_encode($event['event_data']) : null,
                    'event_time' => $event['event_time'] ?? now(),
                ]);
                $stored++;
            } catch (\Exception $e) {
                $errors[] = "Event at index {$index}: " . $e->getMessage();
                Log::error('EventLog batch store error', [
                    'visitor_id' => $visitorId,
                    'event_name' => $eventName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Optionally update visitor geo-data if needed
        if ($stored > 0 && (empty($visitor->country) || empty($visitor->city))) {
            try {
                $position = \Stevebauman\Location\Facades\Location::get($request->ip());
                if ($position) {
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
                }
            } catch (\Throwable $e) {
                // Silent fail for geo-location
            }
        }

        return response()->json([
            'success' => true,
            'stored' => $stored,
            'total' => count($events),
            'errors' => $errors,
            'visitor_id' => $visitorId,
        ]);
    }

    /**
     * Store a single event (fallback / non-batch)
     */
    public function store(Request $request)
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

        try {
            EventLog::create([
                'visitor_id' => $visitorId,
                'event_name' => $request->input('event_name'),
                'url' => $request->input('url') ?? $request->header('Referer') ?? request()->url(),
                'event_details' => $request->has('event_data') ? json_encode($request->input('event_data')) : null,
                'event_time' => $request->input('event_time') ?? now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Event stored']);
        } catch (\Exception $e) {
            Log::error('EventLog store error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to store event'], 500);
        }
    }
}
