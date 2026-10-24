<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FacebookTrackingService;
use App\Support\TrackingEvents;
use Illuminate\Http\Request;

/**
 * Browser -> server relay for the Conversions API.
 *
 * The storefront already knows the event name, the custom data and (for a
 * logged-in customer) the identifiers. It does NOT know the access token: that
 * lives in the database and is attached here, which is why the token is no
 * longer printed into the page.
 *
 * Hardening added with the dedupe fix:
 *   - event name must be in the shared vocabulary (no arbitrary events);
 *   - event_id must be the same shape the recorder accepts, and is forwarded
 *     unchanged so Meta can drop the duplicate browser hit;
 *   - payload sizes are capped;
 *   - provider/exception detail is logged, never returned to the caller;
 *   - the route is rate limited (see routes/api.php).
 */
class FacebookTrackController extends Controller
{
    private const MAX_USER_DATA_KEYS = 20;

    private const MAX_EVENT_DATA_BYTES = 8192;

    public function __invoke(Request $request)
    {
        $eventName = $request->input('event_name');

        if (!TrackingEvents::isCommerce(is_string($eventName) ? $eventName : null)) {
            return response()->json(['error' => 'Unsupported event name'], 422);
        }

        $eventData = $request->input('event_data', []);
        $userData = $request->input('user_data', []);

        if (!is_array($eventData) || !is_array($userData)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        if (count($userData) > self::MAX_USER_DATA_KEYS
            || strlen((string) json_encode($eventData)) > self::MAX_EVENT_DATA_BYTES) {
            return response()->json(['error' => 'Payload too large'], 422);
        }

        $eventId = $request->input('event_id');

        if ($eventId !== null && $eventId !== '') {
            if (!is_string($eventId) || preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $eventId) !== 1) {
                return response()->json(['error' => 'Invalid event_id'], 422);
            }
        } else {
            $eventId = null;
        }

        $service = app(FacebookTrackingService::class);

        if (!$service->isEnabled()) {
            return response()->json(['error' => 'Facebook tracking is disabled'], 400);
        }

        $sent = $service->sendEvent($eventName, $eventData, $userData, $eventId);

        if (!$sent) {
            return response()->json(['error' => 'Event could not be sent'], 502);
        }

        return response()->json([
            'success' => true,
            'event_id' => $eventId,
        ]);
    }
}
