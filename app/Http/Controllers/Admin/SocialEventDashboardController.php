<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\GeneralSetting;
use App\Models\VisitorMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialEventDashboardController extends Controller
{
    private function canonicalEventExpression(string $column = 'event_name'): string
    {
        return "CASE WHEN {$column} = 'BeginCheckout' THEN 'InitiateCheckout' ELSE {$column} END";
    }

    private function acceptedEventNamesForQuery(array $allowedEvents): array
    {
        $accepted = $allowedEvents;
        $accepted[] = 'BeginCheckout';
        return array_values(array_unique($accepted));
    }

    public function index(Request $request)
    {
        $days = (int) $request->get('days', 7);
        if ($days < 1) {
            $days = 1;
        }
        if ($days > 90) {
            $days = 90;
        }

        $since = Carbon::now()->subDays($days);

        $allowedEvents = [
            'PageView',
            'ViewContent',
            'Search',
            'AddToCart',
            'AddToWishlist',
            'AddPaymentInfo',
            'InitiateCheckout',
            'Purchase',
            'Lead',
            'CompleteRegistration',
            'Contact',
            'CustomizeProduct',
            'FakeOrderAttempt',
        ];
        $acceptedEvents = $this->acceptedEventNamesForQuery($allowedEvents);
        $canonicalExpr = $this->canonicalEventExpression('event_name');

        $eventCounts = EventLog::query()
            ->selectRaw("{$canonicalExpr} as event_name, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->whereIn('event_name', $acceptedEvents)
            ->groupBy(DB::raw($canonicalExpr))
            ->pluck('total', 'event_name')
            ->toArray();

        foreach ($allowedEvents as $ev) {
            if (!array_key_exists($ev, $eventCounts)) {
                $eventCounts[$ev] = 0;
            }
        }

        $settings = GeneralSetting::first();
        $trackingStatus = [
            'facebook' => [
                'server' => (int) ($settings->facebook_server_side_tracking ?? 0) === 1,
                'enhanced_ecommerce' => (int) ($settings->facebook_enhanced_ecommerce ?? 0) === 1,
            ],
            'google' => [
                'server' => (int) ($settings->gtm_server_side_tracking ?? 0) === 1,
            ],
            'tiktok' => [
                'browser' => (int) ($settings->tiktok_browser_tracking ?? 0) === 1,
                'server' => (int) ($settings->tiktok_server_side_tracking ?? 0) === 1,
                'test_mode' => (int) ($settings->tiktok_test_mode ?? 0) === 1,
            ],
        ];

        $topEvents = DB::table('event_logs')
            ->selectRaw("{$canonicalExpr} as event_name, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw($canonicalExpr))
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topUrls = EventLog::query()
            ->select('url', DB::raw('COUNT(*) as total'))
            ->whereNotNull('url')
            ->where('created_at', '>=', $since)
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recentLogs = DB::table('event_logs as e')
            ->leftJoin('visitors_master as v', 'v.visitor_id', '=', 'e.visitor_id')
            ->where('e.created_at', '>=', $since)
            ->orderByDesc('e.id')
            ->limit(50)
            ->select([
                'e.id',
                'e.visitor_id',
                DB::raw($this->canonicalEventExpression('e.event_name') . ' as event_name'),
                'e.url',
                'e.event_time',
                'e.created_at',
                'v.ip as visitor_ip',
                'v.country as visitor_country',
                'v.city as visitor_city',
                'v.isp as visitor_isp',
            ])
            ->get();

        $topCountries = VisitorMaster::query()
            ->select('country', DB::raw('COUNT(*) as total'))
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->where('created_at', '>=', $since)
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topCities = VisitorMaster::query()
            ->select('city', DB::raw('COUNT(*) as total'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->where('created_at', '>=', $since)
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $visitorCount = VisitorMaster::query()
            ->where('created_at', '>=', $since)
            ->count();

        $eventCount = EventLog::query()
            ->where('created_at', '>=', $since)
            ->count();

        return view('backEnd.social-events.dashboard', compact(
            'days',
            'allowedEvents',
            'eventCounts',
            'trackingStatus',
            'visitorCount',
            'eventCount',
            'topEvents',
            'topUrls',
            'recentLogs',
            'topCountries',
            'topCities'
        ));
    }
}
