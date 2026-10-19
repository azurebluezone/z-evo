<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcomPixel;
use App\Models\GeneralSetting;
use App\Models\GoogleTagManager;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class SocialEventController extends Controller
{
    public function index()
    {
        $settings = GeneralSetting::first();
        $pixels = EcomPixel::orderBy('id', 'desc')->get();
        $gtms = GoogleTagManager::orderBy('id', 'desc')->get();

        // Dynamic event stats
        $totalEvents = \App\Models\EventLog::count();
        $todayEvents = \App\Models\EventLog::whereDate('created_at', now()->toDateString())->count();
        $weekEvents = \App\Models\EventLog::where('created_at', '>=', now()->subDays(7))->count();

        // Event breakdown by type
        $eventBreakdown = \App\Models\EventLog::select('event_name', \DB::raw('COUNT(*) as count'))
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(13)
            ->get();

        // Top countries
        $topCountries = \App\Models\VisitorMaster::select('country', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Top cities
        $topCities = \App\Models\VisitorMaster::select('city', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Visitor stats
        $totalVisitors = \App\Models\VisitorMaster::count();
        $todayVisitors = \App\Models\VisitorMaster::whereDate('created_at', now()->toDateString())->count();
        $weekVisitors = \App\Models\VisitorMaster::where('created_at', '>=', now()->subDays(7))->count();

        // Platform-specific event counts
        $fbPixelSet = !empty($settings->facebook_pixel_id);
        $ttPixelSet = !empty($settings->tiktok_pixel_id);
        $gtmSet = !empty($settings->gtm_server_container_id);

        // Recent events for timeline (joined with visitors_master for ip/country)
        $recentEvents = \App\Models\EventLog::select('event_logs.*', 'visitors_master.ip', 'visitors_master.country', 'visitors_master.city')
            ->leftJoin('visitors_master', 'event_logs.visitor_id', '=', 'visitors_master.visitor_id')
            ->orderByDesc('event_logs.created_at')
            ->limit(10)
            ->get();

        return view('backEnd.social-events.index', compact(
            'settings', 'pixels', 'gtms',
            'totalEvents', 'todayEvents', 'weekEvents',
            'eventBreakdown', 'topCountries', 'topCities',
            'totalVisitors', 'todayVisitors', 'weekVisitors',
            'fbPixelSet', 'ttPixelSet', 'gtmSet',
            'recentEvents'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'facebook_pixel_id' => 'nullable|string|max:255',
            'facebook_conversion_api_token' => 'nullable|string',
            'facebook_conversion_api_version' => 'nullable|in:v18.0,v17.0,v16.0,v15.0',
            'facebook_custom_events' => 'nullable|string',

            'whatsapp_number' => 'nullable|string|max:255',
            'messenger_link' => 'nullable|string|max:255',
            'support_phone' => 'nullable|string|max:255',

            'gtm_server_container_id' => 'nullable|string|max:255',
            'gtm_server_url' => 'nullable|string|max:255',

            'tiktok_pixel_id' => 'nullable|string|max:255',
            'tiktok_access_token' => 'nullable|string',
            'tiktok_test_mode' => 'nullable|boolean',
        ]);

        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'Settings not found');
            return redirect()->back();
        }

        $settings->update([
            'facebook_pixel_id' => $request->facebook_pixel_id,
            'facebook_conversion_api_token' => $request->facebook_conversion_api_token,
            'facebook_conversion_api_version' => $request->facebook_conversion_api_version ?? ($settings->facebook_conversion_api_version ?? 'v17.0'),
            'facebook_custom_events' => $request->facebook_custom_events,
            'facebook_server_side_tracking' => $request->has('facebook_server_side_tracking') ? 1 : 0,
            'facebook_enhanced_ecommerce' => $request->has('facebook_enhanced_ecommerce') ? 1 : 0,

            'whatsapp_number' => $request->whatsapp_number,
            'messenger_link' => $request->messenger_link,
            'support_phone' => $request->support_phone,

            'gtm_server_container_id' => $request->gtm_server_container_id,
            'gtm_server_url' => $request->gtm_server_url,
            'gtm_server_side_tracking' => $request->has('gtm_server_side_tracking') ? 1 : 0,

            'tiktok_pixel_id' => $request->tiktok_pixel_id,
            'tiktok_access_token' => $request->tiktok_access_token,
            'tiktok_server_side_tracking' => $request->has('tiktok_server_side_tracking') ? 1 : 0,
            'tiktok_browser_tracking' => $request->has('tiktok_browser_tracking') ? 1 : 0,
            'tiktok_test_mode' => $request->has('tiktok_test_mode') ? 1 : 0,
        ]);

        Toastr::success('Success', 'Social event settings updated');
        return redirect()->back();
    }
}
