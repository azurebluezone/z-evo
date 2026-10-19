<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FrontendController;

use App\Http\Controllers\Api\CourierWebhookController;


Route::group(['namespace' => 'Api', 'prefix' => 'v1', 'middleware' => 'api'], function () {

    Route::get('app-config', [FrontendController::class, 'appconfig']);
    Route::get('slider', [FrontendController::class, 'slider']);
    Route::get('category-menu', [FrontendController::class, 'categorymenu']);
    Route::get('hotdeal-product', [FrontendController::class, 'hotdealproduct']);
    Route::get('homepage-product', [FrontendController::class, 'homepageproduct']);
    Route::get('footer-menu-left', [FrontendController::class, 'footermenuleft']);
    Route::get('footer-menu-right', [FrontendController::class, 'footermenuright']);
    Route::get('social-media', [FrontendController::class, 'socialmedia']);
    Route::get('contactinfo', [FrontendController::class, 'contactinfo']);

    //  Home Page Api End =================================

    Route::get('category/{id}', [FrontendController::class, 'catproduct']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Courier webhooks
|--------------------------------------------------------------------------
| Both endpoints are PUBLIC by necessity - a courier on the internet has to
| reach them - so authentication is the job of the courier.webhook middleware,
| which compares a per-courier bearer token from courier_apis in constant time
| (hash_equals) and rate limits per courier + IP.
|
| These routes previously had NO authentication at all, which meant anyone who
| could reach the URL could set a real order to "delivered".
*/

Route::post('/pathao-webhook', [CourierWebhookController::class, 'pathao'])
    ->middleware('courier.webhook:pathao')
    ->name('api.pathao.webhook');

Route::post('/steadfast-webhook', [CourierWebhookController::class, 'steadfast'])
    ->middleware('courier.webhook:steadfast')
    ->name('api.steadfast.webhook');

// Facebook Server-Side Tracking API Routes
Route::post('/facebook/track', function (Request $request) {
    try {
        $eventName = $request->input('event_name');
        $eventData = $request->input('event_data', []);
        $userData = $request->input('user_data', []);

        if (!$eventName) {
            return response()->json(['error' => 'Event name is required'], 400);
        }

        $facebookTracking = app(\App\Services\FacebookTrackingService::class);

        if (!$facebookTracking->isEnabled()) {
            return response()->json(['error' => 'Facebook tracking is disabled'], 400);
        }

        $result = $facebookTracking->sendEvent($eventName, $eventData, $userData);

        if ($result) {
            return response()->json(['success' => true, 'message' => 'Event tracked successfully']);
        } else {
            return response()->json(['error' => 'Failed to track event'], 500);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
    }
});

// TikTok Server-Side Tracking API Routes
Route::post('/tiktok/track', function (Request $request) {
    try {
        $tiktokTracking = app(\App\Services\TikTokTrackingService::class);

        if (!$tiktokTracking->isEnabled()) {
            return response()->json(['error' => 'TikTok tracking is disabled'], 400);
        }

        $events = $request->input('events');
        if (is_array($events)) {
            if (count($events) > 50) {
                return response()->json(['error' => 'Too many events'], 422);
            }

            $ok = 0;
            foreach ($events as $ev) {
                if (!is_array($ev)) {
                    continue;
                }
                $eventName = $ev['event_name'] ?? null;
                if (!is_string($eventName) || $eventName === '') {
                    continue;
                }
                $eventData = is_array($ev['event_data'] ?? null) ? $ev['event_data'] : [];
                $userData = is_array($ev['user_data'] ?? null) ? $ev['user_data'] : [];
                if ($tiktokTracking->sendEvent($eventName, $eventData, $userData)) {
                    $ok++;
                }
            }

            return response()->json(['success' => true, 'message' => 'Events tracked', 'tracked' => $ok]);
        }

        $eventName = $request->input('event_name');
        $eventData = $request->input('event_data', []);
        $userData = $request->input('user_data', []);

        if (!$eventName) {
            return response()->json(['error' => 'Event name is required'], 400);
        }

        $result = $tiktokTracking->sendEvent($eventName, $eventData, $userData);

        if ($result) {
            return response()->json(['success' => true, 'message' => 'Event tracked successfully']);
        }

        return response()->json(['error' => 'Failed to track event'], 500);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
    }
});

// TikTok Connection Test Route
Route::post('/tiktok/test-connection', function (Request $request) {
    try {
        $tiktokTracking = app(\App\Services\TikTokTrackingService::class);
        $result = $tiktokTracking->testConnection();
        
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
});

// EventLog API Routes (Client-side batching)
Route::post('/event-log/batch', [\App\Http\Controllers\Api\EventLogController::class, 'batch'])->name('api.event-log.batch');
Route::post('/event-log/store', [\App\Http\Controllers\Api\EventLogController::class, 'store'])->name('api.event-log.store');
