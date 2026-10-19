<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\GeneralSetting;
use App\Models\VisitorMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialEventPlatformController extends Controller
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

    private function normalizeFilterEvent($event, array $allowedEvents): ?string
    {
        if (!is_string($event)) {
            return null;
        }

        if ($event === 'BeginCheckout') {
            $event = 'InitiateCheckout';
        }

        return in_array($event, $allowedEvents, true) ? $event : null;
    }

    private function applyEventFilter($query, ?string $event, string $column = 'event_name')
    {
        if (!$event) {
            return $query;
        }

        if ($event === 'InitiateCheckout') {
            return $query->whereIn($column, ['InitiateCheckout', 'BeginCheckout']);
        }

        return $query->where($column, $event);
    }

    public function facebook(Request $request)
    {
        return $this->renderPlatform('facebook', $request);
    }

    public function google(Request $request)
    {
        return $this->renderPlatform('google', $request);
    }

    public function tiktok(Request $request)
    {
        return $this->renderPlatform('tiktok', $request);
    }

    private function renderPlatform(string $platform, Request $request)
    {
        $settings = GeneralSetting::first();

        $days = (int) $request->get('days', 7);
        if ($days < 1) {
            $days = 1;
        }
        if ($days > 90) {
            $days = 90;
        }

        $event = $request->get('event');
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
        $event = $this->normalizeFilterEvent($event, $allowedEvents);
        $acceptedEvents = $this->acceptedEventNamesForQuery($allowedEvents);
        $canonicalExpr = $this->canonicalEventExpression('event_name');

        $since = Carbon::now()->subDays($days);

        $visitorCount = VisitorMaster::query()
            ->where('created_at', '>=', $since)
            ->count();

        $eventCountQuery = EventLog::query()->where('created_at', '>=', $since);
        $this->applyEventFilter($eventCountQuery, $event, 'event_name');
        $eventCount = $eventCountQuery->count();

        $urlCount = EventLog::query()
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->where('created_at', '>=', $since)
            ->count();

        $timeline = EventLog::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();
        if ($event) {
            $timeline = EventLog::query()
                ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
                ->where('created_at', '>=', $since);
            $this->applyEventFilter($timeline, $event, 'event_name');
            $timeline = $timeline
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('day')
                ->get();
        }

        $timelineLabels = $timeline->pluck('day')->map(function ($d) {
            return (string) $d;
        })->toArray();
        $timelineSeries = $timeline->pluck('total')->map(function ($t) {
            return (int) $t;
        })->toArray();

        $platformStatus = [
            'facebook' => [
                'server' => (int) ($settings->facebook_server_side_tracking ?? 0) === 1,
                'enhanced_ecommerce' => (int) ($settings->facebook_enhanced_ecommerce ?? 0) === 1,
            ],
            'google' => [
                'server' => (int) ($settings->gtm_server_side_tracking ?? 0) === 1,
            ],
            'tiktok' => [
                'server' => (int) ($settings->tiktok_server_side_tracking ?? 0) === 1,
                'browser' => (int) ($settings->tiktok_browser_tracking ?? 0) === 1,
                'test_mode' => (int) ($settings->tiktok_test_mode ?? 0) === 1,
            ],
        ];

        $status = $platformStatus[$platform] ?? [];

        $eventCounts = EventLog::query()
            ->selectRaw("{$canonicalExpr} as event_name, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->whereIn('event_name', $acceptedEvents)
            ->groupBy(DB::raw($canonicalExpr))
            ->pluck('total', 'event_name')
            ->toArray();

        $topEvents = DB::table('event_logs')
            ->selectRaw("{$canonicalExpr} as event_name, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw($canonicalExpr))
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recentLogsQuery = DB::table('event_logs as e')
            ->leftJoin('visitors_master as v', 'v.visitor_id', '=', 'e.visitor_id')
            ->where('e.created_at', '>=', $since);

        $this->applyEventFilter($recentLogsQuery, $event, 'e.event_name');

        $recentLogs = $recentLogsQuery
            ->orderByDesc('e.id')
            ->limit(100)
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

        return view('backEnd.social-events.platform', compact(
            'platform',
            'settings',
            'days',
            'event',
            'allowedEvents',
            'status',
            'eventCounts',
            'visitorCount',
            'eventCount',
            'urlCount',
            'timelineLabels',
            'timelineSeries',
            'topEvents',
            'recentLogs',
            'topCountries',
            'topCities'
        ));
    }
}
