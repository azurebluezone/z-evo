<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Brian2694\Toastr\Facades\Toastr;

class FacebookTrackingController extends Controller
{
    /**
     * Display the Facebook tracking configuration page
     */
    public function index()
    {
        $settings = GeneralSetting::first();
        return view('backEnd.facebook-tracking.index', compact('settings'));
    }

    /**
     * Update Facebook tracking configuration
     */
    public function update(Request $request)
    {
        $request->validate([
            'facebook_pixel_id' => 'nullable|string|max:255',
            'facebook_conversion_api_token' => 'nullable|string',
            'facebook_conversion_api_version' => 'required|in:v17.0,v16.0,v15.0',
            'facebook_custom_events' => 'nullable|string',
        ]);

        $settings = GeneralSetting::first();
        
        if (!$settings) {
            Toastr::error('Settings not found', 'Error');
            return redirect()->back();
        }

        $input = $request->only([
            'facebook_pixel_id',
            'facebook_conversion_api_token',
            'facebook_conversion_api_version',
            'facebook_custom_events'
        ]);

        // Handle boolean fields
        $input['facebook_server_side_tracking'] = $request->facebook_server_side_tracking ? 1 : 0;
        $input['facebook_enhanced_ecommerce'] = $request->facebook_enhanced_ecommerce ? 1 : 0;

        $settings->update($input);

        Toastr::success('Facebook tracking configuration updated successfully', 'Success');
        return redirect()->route('facebook-tracking.index');
    }

    /**
     * Test Facebook tracking configuration
     */
    public function test(Request $request)
    {
        try {
            $facebookTracking = app(\App\Services\FacebookTrackingService::class);
            
            if (!$facebookTracking->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook tracking is not enabled or properly configured'
                ]);
            }

            // Send a test event
            $result = $facebookTracking->sendEvent('TestEvent', [
                'test_parameter' => 'test_value',
                'currency' => 'BDT',
                'value' => 100
            ], [
                'email' => 'test@example.com',
                'phone' => '1234567890'
            ]);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test event sent successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test event'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get tracking status
     */
    public function status()
    {
        $settings = GeneralSetting::first();
        $facebookTracking = app(\App\Services\FacebookTrackingService::class);
        
        return response()->json([
            'enabled' => $facebookTracking->isEnabled(),
            'pixel_id' => $settings->facebook_pixel_id,
            'api_version' => $settings->facebook_conversion_api_version,
            'server_side_tracking' => $settings->facebook_server_side_tracking,
            'enhanced_ecommerce' => $settings->facebook_enhanced_ecommerce
        ]);
    }
}
