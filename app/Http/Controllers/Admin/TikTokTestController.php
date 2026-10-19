<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TikTokTrackingService;
use Illuminate\Http\Request;

class TikTokTestController extends Controller
{
    /**
     * Display test page for TikTok tracking
     */
    public function index()
    {
        return view('backEnd.tiktok-test.index');
    }

    /**
     * Test TikTok connection
     */
    public function testConnection()
    {
        try {
            $tiktokTracking = app(TikTokTrackingService::class);
            $result = $tiktokTracking->testConnection();
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test event to TikTok
     */
    public function sendTestEvent(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string|in:PageView,ViewContent,AddToCart,InitiateCheckout,Purchase',
        ]);

        try {
            $tiktokTracking = app(TikTokTrackingService::class);
            
            $testEventData = [
                'PageView' => [
                    'url' => url('/'),
                    'title' => 'Test Page View'
                ],
                'ViewContent' => [
                    'currency' => 'BDT',
                    'value' => 1000,
                    'content_type' => 'product',
                    'content_name' => 'Test Product',
                ],
                'AddToCart' => [
                    'currency' => 'BDT',
                    'value' => 1000,
                    'content_type' => 'product',
                    'content_name' => 'Test Product',
                ],
                'InitiateCheckout' => [
                    'currency' => 'BDT',
                    'value' => 2000,
                    'content_type' => 'product',
                    'number_of_items' => 2,
                ],
                'Purchase' => [
                    'currency' => 'BDT',
                    'value' => 5000,
                    'content_type' => 'product',
                    'order_id' => 'TEST_' . time(),
                ],
            ];

            $eventData = $testEventData[$request->event_name] ?? [];
            
            $result = $tiktokTracking->sendEvent($request->event_name, $eventData);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test event sent successfully!',
                    'event' => $request->event_name,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send event. Check logs for details.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
