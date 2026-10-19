<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\VisitorMaster;
use App\Services\Analytics\BangladeshGeo;
use App\Services\Analytics\EventRecorder;
use App\Services\Analytics\TrafficSourceResolver;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Real-time Analytics & Event Attribution dashboard.
 *
 * Every figure on this page is computed from `visitors_master` + `event_logs`
 * after applying the same filter set, so the KPI cards, the charts and the live
 * table can never disagree with each other.
 *
 * Filter contract (shared by the view and the JSON endpoints):
 *   preset        today | yesterday | last7 | last30 | last90 | custom
 *   from / to     Y-m-d, only used when preset = custom
 *   channel       facebook | google | tiktok | direct | organic | referral
 *   method        browser | server | hybrid
 *   division      any of the eight divisions
 *   search        visitor uuid or IP fragment
 */
class AnalyticsDashboardController extends Controller
{
    private const LIVE_WINDOW_MINUTES = 5;

    // ---------------------------------------------------------------- filters

    /**
     * Normalise the raw request into a single filter array used everywhere.
     *
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        $preset = (string) $request->get('preset', 'last7');

        $allowedPresets = ['today', 'yesterday', 'last7', 'last30', 'last90', 'custom'];
        if (!in_array($preset, $allowedPresets, true)) {
            $preset = 'last7';
        }

        [$from, $to] = $this->resolveRange($preset, $request);

        $channel = (string) $request->get('channel', '');
        if (!in_array($channel, TrafficSourceResolver::CHANNEL_OPTIONS, true)) {
            $channel = '';
        }

        $method = (string) $request->get('method', '');
        if (!in_array($method, ['browser', 'server', 'hybrid'], true)) {
            $method = '';
        }

        $division = (string) $request->get('division', '');
        if (!in_array($division, BangladeshGeo::DIVISIONS, true)) {
            $division = '';
        }

        $search = trim((string) $request->get('search', ''));
        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        return [
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'channel' => $channel,
            'method' => $method,
            'division' => $division,
            'search' => $search,
            'query' => array_filter([
                'preset' => $preset,
                'from' => $preset === 'custom' ? $from->toDateString() : null,
                'to' => $preset === 'custom' ? $to->toDateString() : null,
                'channel' => $channel ?: null,
                'method' => $method ?: null,
                'division' => $division ?: null,
                'search' => $search ?: null,
            ], static fn ($v) => $v !== null && $v !== ''),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(string $preset, Request $request): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'today' => [$today, $today->copy()->endOfDay()],
            'yesterday' => [
                $today->copy()->subDay()->startOfDay(),
                $today->copy()->subDay()->endOfDay(),
            ],
            'last30' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
            'last90' => [$today->copy()->subDays(89)->startOfDay(), $today->copy()->endOfDay()],
            'custom' => $this->customRange($request, $today),
            default => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function customRange(Request $request, Carbon $today): array
    {
        $from = $this->safeDate((string) $request->get('from', ''));
        $to = $this->safeDate((string) $request->get('to', ''));

        $from ??= $today->copy()->subDays(6)->startOfDay();
        $to ??= $today->copy()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Cap the window so a careless "custom" range cannot scan years of events.
        if ($from->diffInDays($to) > 365) {
            $from = $to->copy()->subDays(365);
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function safeDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Apply the visitor-side filters (everything except tracking_method, which
     * only exists on the event table).
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<VisitorMaster>
     */
    private function visitorQuery(array $filters)
    {
        $query = VisitorMaster::query()
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        if ($filters['channel'] !== '') {
            $query->where('channel', $filters['channel']);
        }

        if ($filters['division'] !== '') {
            $query->where('division', $filters['division']);
        }

        if ($filters['search'] !== '') {
            // UUID is matched exactly when it looks like one, otherwise as a
            // fragment so an admin can hunt by partial IP.
            $query->where(function ($q) use ($filters) {
                $q->where('visitor_id', $filters['search'])
                    ->orWhere('ip', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query;
    }

    /**
     * Apply the event-side filters. Because `event_logs` carries denormalised
     * copies of channel/device, the same filters can be pushed down without
     * always joining `visitors_master`.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<EventLog>
     */
    private function eventQuery(array $filters)
    {
        $query = EventLog::query()
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        if ($filters['channel'] !== '') {
            $query->where('channel', $filters['channel']);
        }

        if ($filters['method'] === 'browser') {
            $query->where('tracking_method', EventRecorder::METHOD_BROWSER);
        } elseif ($filters['method'] === 'server') {
            $query->where('tracking_method', EventRecorder::METHOD_SERVER);
        }

        if ($filters['division'] !== '' || $filters['search'] !== '') {
            // These live on the visitor table, so join when they are in play.
            $query->join('visitors_master', 'visitors_master.visitor_id', '=', 'event_logs.visitor_id')
                ->addSelect([
                    'event_logs.id',
                    'event_logs.visitor_id',
                    'event_logs.event_name',
                    'event_logs.url',
                    'event_logs.page_path',
                    'event_logs.event_time',
                    'event_logs.channel',
                    'event_logs.tracking_method',
                    'event_logs.device_type',
                    'event_logs.dedup_status',
                    'event_logs.created_at',
                ]);

            if ($filters['division'] !== '') {
                $query->where('visitors_master.division', $filters['division']);
            }

            if ($filters['search'] !== '') {
                $query->where(function ($q) use ($filters) {
                    $q->where('visitors_master.visitor_id', $filters['search'])
                        ->orWhere('visitors_master.ip', 'like', '%' . $filters['search'] . '%');
                });
            }
        }

        return $query;
    }

    // ------------------------------------------------------------------- page

    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        $payload = $this->buildDashboardData($filters);

        return view('backEnd.analytics.index', array_merge($filters, [
            'query' => $request->query(),
            'divisions' => BangladeshGeo::DIVISIONS,
            'channels' => TrafficSourceResolver::CHANNEL_OPTIONS,
            'presets' => ['today', 'yesterday', 'last7', 'last30', 'last90', 'custom'],
        ], $payload));
    }

    /**
     * Aggregate everything the dashboard renders. Shared by the Blade view and
     * the JSON endpoints so numbers can never drift between them.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildDashboardData(array $filters): array
    {
        return [
            'kpi' => $this->kpiData($filters),
            'location' => $this->locationData($filters),
            'attribution' => $this->attributionData($filters),
            'journey' => $this->journeyData($filters),
            'trend' => $this->trendData($filters),
            'live' => $this->liveData($filters),
        ];
    }

    // -------------------------------------------------------------- KPI cards

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function kpiData(array $filters): array
    {
        $visitors = $this->visitorQuery($filters);

        $totalVisitors = (clone $visitors)->count();
        $pageviews = (int) (clone $visitors)->sum('pageview_count');
        $events = (clone $visitors)->sum('event_count');

        // --- Top division -------------------------------------------------------
        $divisionTop = (clone $visitors)
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->select('division', DB::raw('COUNT(*) as total'))
            ->groupBy('division')
            ->orderByDesc('total')
            ->first();

        // --- Top channel ---------------------------------------------------------
        $channelTop = (clone $visitors)
            ->whereNotNull('channel')
            ->where('channel', '!=', '')
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->groupBy('channel')
            ->orderByDesc('total')
            ->first();

        // --- Browser vs server split --------------------------------------------
        $browserEvents = (int) (clone $visitors)->sum('browser_event_count');
        $serverEvents = (int) (clone $visitors)->sum('server_event_count');
        $attributionTotal = $browserEvents + $serverEvents;

        $previous = $this->previousRangeKpi($filters);

        return [
            'total_visitors' => $totalVisitors,
            'unique_visitors' => (int) (clone $visitors)->distinct('ip')->count('ip'),
            'pageviews' => $pageviews,
            'events' => (int) $events,
            'live_visitors' => $this->liveVisitorCount($filters),

            'top_division' => $divisionTop?->division,
            'top_division_count' => (int) ($divisionTop->total ?? 0),
            'top_division_pct' => $this->pct((int) ($divisionTop->total ?? 0), $totalVisitors),

            'top_channel' => $channelTop?->channel,
            'top_channel_count' => (int) ($channelTop->total ?? 0),
            'top_channel_pct' => $this->pct((int) ($channelTop->total ?? 0), $totalVisitors),

            'browser_events' => $browserEvents,
            'server_events' => $serverEvents,
            'server_pct' => $this->pct($serverEvents, $attributionTotal),
            'browser_pct' => $this->pct($browserEvents, $attributionTotal),

            'vs_previous_visitors' => $previous['visitor_delta'],
            'vs_previous_pageviews' => $previous['pageview_delta'],
            'has_previous' => $previous['has_data'],
        ];
    }

    /**
     * Growth is only meaningful against a window of identical length that sits
     * immediately before the selected one.
     *
     * @param  array<string, mixed>  $filters
     * @return array{visitor_delta:null|float, pageview_delta:null|float, has_data:bool}
     */
    private function previousRangeKpi(array $filters): array
    {
        $from = $filters['from'];
        $to = $filters['to'];

        $spanSeconds = $from->diffInSeconds($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($spanSeconds);

        $previous = [
            'preset' => 'custom',
            'from' => $prevFrom,
            'to' => $prevTo,
            'channel' => $filters['channel'],
            'method' => $filters['method'],
            'division' => $filters['division'],
            'search' => '',
        ];

        $visitors = (int) $this->visitorQuery($previous)->count();
        $pageviews = (int) $this->visitorQuery($previous)->sum('pageview_count');

        if ($visitors === 0 && $pageviews === 0) {
            return ['visitor_delta' => null, 'pageview_delta' => null, 'has_data' => false];
        }

        $currentVisitors = (int) $this->visitorQuery($filters)->count();
        $currentPageviews = (int) $this->visitorQuery($filters)->sum('pageview_count');

        return [
            'visitor_delta' => $visitors > 0 ? $this->delta($currentVisitors, $visitors) : null,
            'pageview_delta' => $pageviews > 0 ? $this->delta($currentPageviews, $pageviews) : null,
            'has_data' => true,
        ];
    }

    // ------------------------------------------------------- location section

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function locationData(array $filters): array
    {
        $base = $this->visitorQuery($filters);

        $divisionRows = (clone $base)
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->select('division', DB::raw('COUNT(*) as total'))
            ->groupBy('division')
            ->orderByDesc('total')
            ->get();

        $divisionTotal = (int) $divisionRows->sum('total');

        $divisions = $divisionRows->map(fn ($row) => [
            'name' => $row->division,
            'total' => (int) $row->total,
            'pct' => $this->pct((int) $row->total, $divisionTotal),
        ])->values()->all();

        $districts = (clone $base)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->select('city', 'division', DB::raw('COUNT(*) as total'))
            ->groupBy('city', 'division')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->city,
                'division' => $row->division,
                'total' => (int) $row->total,
            ])->values()->all();

        $isps = (clone $base)
            ->whereNotNull('isp')
            ->where('isp', '!=', '')
            ->select('isp', DB::raw('COUNT(*) as total'))
            ->groupBy('isp')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->isp,
                'total' => (int) $row->total,
                'pct' => $this->pct((int) $row->total, (int) $base->count()),
            ])->values()->all();

        $devices = (clone $base)
            ->whereNotNull('device_type')
            ->where('device_type', '!=', '')
            ->select('device_type', DB::raw('COUNT(*) as total'))
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->device_type,
                'total' => (int) $row->total,
            ])->values()->all();

        $browsers = (clone $base)
            ->whereNotNull('browser')
            ->where('browser', '!=', '')
            ->select('browser', DB::raw('COUNT(*) as total'))
            ->groupBy('browser')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->browser,
                'total' => (int) $row->total,
            ])->values()->all();

        return [
            'divisions' => $divisions,
            'districts' => $districts,
            'isps' => $isps,
            'devices' => $devices,
            'browsers' => $browsers,
            'unknown_geo' => (int) $this->visitorQuery($filters)
                ->where(function ($q) {
                    $q->whereNull('division')->orWhere('division', '');
                })->count(),
        ];
    }

    // ----------------------------------------------------- attribution section

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function attributionData(array $filters): array
    {
        $events = $this->eventQuery($filters);

        // --- Channel donut --------------------------------------------------------
        $channelRows = (clone $events)
            ->whereNotNull('channel')
            ->where('channel', '!=', '')
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get();

        $channelTotal = (int) $channelRows->sum('total');

        $channels = $channelRows->map(fn ($row) => [
            'name' => $this->label($row->channel),
            'key' => $row->channel,
            'total' => (int) $row->total,
            'pct' => $this->pct((int) $row->total, $channelTotal),
        ])->values()->all();

        // --- Browser vs server ----------------------------------------------------
        $methodRows = (clone $events)
            ->select('tracking_method', DB::raw('COUNT(*) as total'))
            ->groupBy('tracking_method')
            ->pluck('total', 'tracking_method');

        $browser = (int) ($methodRows[EventRecorder::METHOD_BROWSER] ?? 0);
        $server = (int) ($methodRows[EventRecorder::METHOD_SERVER] ?? 0);
        $methodTotal = $browser + $server;

        // --- Per-event browser/server comparison ---------------------------------
        $perEvent = (clone $events)
            ->select(
                'event_name',
                DB::raw('SUM(CASE WHEN tracking_method = "browser" THEN 1 ELSE 0 END) as browser_total'),
                DB::raw('SUM(CASE WHEN tracking_method = "server" THEN 1 ELSE 0 END) as server_total'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'event' => $row->event_name,
                'browser' => (int) $row->browser_total,
                'server' => (int) $row->server_total,
                'total' => (int) $row->total,
            ])->values()->all();

        // --- Dedup health ---------------------------------------------------------
        $dedupRows = (clone $events)
            ->select('dedup_status', DB::raw('COUNT(*) as total'))
            ->groupBy('dedup_status')
            ->pluck('total', 'dedup_status');

        $deduplicated = (int) ($dedupRows[EventRecorder::STATUS_PRIMARY] ?? 0);

        return [
            'channels' => $channels,
            'channel_total' => $channelTotal,
            'browser' => $browser,
            'server' => $server,
            'browser_pct' => $this->pct($browser, $methodTotal),
            'server_pct' => $this->pct($server, $methodTotal),
            'per_event' => $perEvent,
            'dedup' => [
                'primary' => $deduplicated,
                'browser_only' => (int) ($dedupRows[EventRecorder::STATUS_BROWSER_ONLY] ?? 0),
                'server_only' => (int) ($dedupRows[EventRecorder::STATUS_SERVER_ONLY] ?? 0),
                'dedup_pct' => $this->pct($deduplicated, $methodTotal),
            ],
            'top_urls' => $this->topUrls($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function topUrls(array $filters): array
    {
        return $this->eventQuery($filters)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->select('url', DB::raw('COUNT(*) as total'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'url' => $row->url,
                'total' => (int) $row->total,
                'visitors' => (int) $row->visitors,
            ])->values()->all();
    }

    // --------------------------------------------------------- journey section

    /**
     * Builds the Sankey node/link graph plus the per-step drop-off percentages.
     *
     * The flow is derived from each visitor's own event sequence in time order,
     * not from aggregate counts, so "Landing ➜ Scroll 50% ➜ Outbound_Click" is a
     * real observed path rather than a guess.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function journeyData(array $filters): array
    {
        $rows = $this->eventQuery($filters)
            ->orderBy('visitor_id')
            ->orderBy('event_time')
            ->select('visitor_id', 'event_name', 'page_path', 'outbound_host', 'event_time')
            ->limit(20000)
            ->get();

        /** @var array<string, array<int, array<string, mixed>>> $paths */
        $paths = [];
        $stepCounts = [];

        foreach ($rows as $row) {
            $step = $this->journeyStep($row);

            if ($step === null) {
                continue;
            }

            $visitorId = (string) $row->visitor_id;
            $paths[$visitorId][] = $step;
            $stepCounts[$step['node']] = ($stepCounts[$step['node']] ?? 0) + 1;
        }

        // --- Nodes ----------------------------------------------------------------
        $nodeTotals = [];
        foreach ($stepCounts as $node => $count) {
            $nodeTotals[$this->nodeLabel($node)] = ($nodeTotals[$this->nodeLabel($node)] ?? 0) + $count;
        }

        arsort($nodeTotals);
        $nodes = [];
        foreach (array_slice($nodeTotals, 0, 14, true) as $label => $count) {
            $nodes[] = ['name' => $label, 'total' => $count];
        }

        // --- Links ----------------------------------------------------------------
        $linkMap = [];
        $entryCounts = [];
        $exitCounts = [];

        foreach ($paths as $steps) {
            $lastIndex = count($steps) - 1;

            if ($lastIndex < 0) {
                continue;
            }

            $entryCounts[$this->nodeLabel($steps[0]['node'])] =
                ($entryCounts[$this->nodeLabel($steps[0]['node'])] ?? 0) + 1;

            $lastLabel = $this->nodeLabel($steps[$lastIndex]['node']);
            $exitCounts[$lastLabel] = ($exitCounts[$lastLabel] ?? 0) + 1;

            for ($i = 0; $i < $lastIndex; $i++) {
                $source = $this->nodeLabel($steps[$i]['node']);
                $target = $this->nodeLabel($steps[$i + 1]['node']);

                if ($source === $target) {
                    continue;
                }

                $key = $source . '>' . $target;
                $linkMap[$key]['source'] = $source;
                $linkMap[$key]['target'] = $target;
                $linkMap[$key]['value'] = ($linkMap[$key]['value'] ?? 0) + 1;
            }
        }

        $links = array_values($linkMap);
        usort($links, static fn ($a, $b) => $b['value'] <=> $a['value']);
        $links = array_slice($links, 0, 24);

        // --- Drop-off --------------------------------------------------------------
        $nodeNameSet = array_column($nodes, 'name');

        $dropoff = [];
        foreach ($nodeNameSet as $label) {
            $entered = $entryCounts[$label] ?? 0;
            $exited = $exitCounts[$label] ?? 0;
            $total = $nodeTotals[$label] ?? 0;

            if ($total === 0) {
                continue;
            }

            $dropoff[] = [
                'step' => $label,
                'entered' => $entered,
                'exited' => $exited,
                'drop_pct' => $this->pct($exited, max($total, $entered, 1)),
            ];
        }

        usort($dropoff, static fn ($a, $b) => $b['drop_pct'] <=> $a['drop_pct']);

        return [
            'nodes' => $nodes,
            'links' => $links,
            'dropoff' => array_slice($dropoff, 0, 10),
            'sample_size' => count($paths),
            'funnel' => $this->funnelSteps($filters),
        ];
    }

    /**
     * Map a raw event row onto one Sankey node.
     *
     * @param  \stdClass  $row
     * @return array{node:string, rank:int}|null
     */
    private function journeyStep($row): ?array
    {
        $name = (string) $row->event_name;

        // Outbound clicks are the drop-off leg and read better by destination.
        if ($name === 'Outbound_Click' || str_starts_with($name, 'Outbound_')) {
            $host = $this->shortHost($row->outbound_host);

            return $host === null ? null : ['node' => 'Outbound:' . $host, 'rank' => 90];
        }

        if (str_starts_with($name, 'Scroll_')) {
            return ['node' => 'Scroll ' . substr($name, 7) . '%', 'rank' => 20];
        }

        if (str_starts_with($name, 'Click_')) {
            return ['node' => 'Click ' . str_replace('_', ' ', substr($name, 6)), 'rank' => 30];
        }

        return match ($name) {
            'PageView' => ['node' => 'PageView', 'rank' => 10],
            'ViewContent' => ['node' => 'ViewContent', 'rank' => 40],
            'AddToCart' => ['node' => 'AddToCart', 'rank' => 60],
            'InitiateCheckout' => ['node' => 'Checkout', 'rank' => 70],
            'AddPaymentInfo' => ['node' => 'Payment', 'rank' => 80],
            'Purchase' => ['node' => 'Purchase', 'rank' => 100],
            'Lead', 'Contact' => ['node' => 'Lead', 'rank' => 90],
            default => null,
        };
    }

    private function nodeLabel(string $node): string
    {
        return $node;
    }

    /**
     * Classic top-of-funnel counts for the ordered commerce steps.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function funnelSteps(array $filters): array
    {
        $stages = [
            'PageView' => 'Landed',
            'ViewContent' => 'Product View',
            'AddToCart' => 'Add to Cart',
            'InitiateCheckout' => 'Checkout',
            'Purchase' => 'Purchased',
        ];

        $counts = $this->eventQuery($filters)
            ->whereIn('event_name', array_keys($stages))
            ->select('event_name', DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('event_name')
            ->pluck('visitors', 'event_name');

        $rows = [];
        $baseline = 0;

        foreach ($stages as $event => $label) {
            $count = (int) ($counts[$event] ?? 0);

            if ($baseline === 0 && $count > 0) {
                $baseline = $count;
            }

            $rows[] = [
                'label' => $label,
                'event' => $event,
                'visitors' => $count,
                'pct' => $this->pct($count, max($baseline, 1)),
                'drop_pct' => $baseline > 0 && $count > 0 ? round((1 - $count / $baseline) * 100, 1) : 0.0,
            ];
        }

        return $rows;
    }

    // ------------------------------------------------------------ trend series

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function trendData(array $filters): array
    {
        // Hourly granularity for short windows, daily for long ones.
        $useHourly = $filters['from']->diffInHours($filters['to']) <= 48;

        $groupExpr = $useHourly
            ? "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')"
            : 'DATE(created_at)';

        $rows = $this->eventQuery($filters)
            ->select([
                DB::raw($groupExpr . ' as bucket'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT visitor_id) as visitors'),
                DB::raw('SUM(CASE WHEN tracking_method = "server" THEN 1 ELSE 0 END) as server_total'),
            ])
            ->groupBy(DB::raw($groupExpr))
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $series = [];
        $cursor = $useHourly
            ? $filters['from']->copy()->startOfHour()
            : $filters['from']->copy()->startOfDay();

        $end = $filters['to']->copy();

        // Guard against a pathological range producing millions of empty points.
        $maxPoints = 400;

        while ($cursor->lte($end) && count($series) < $maxPoints) {
            $key = $useHourly
                ? $cursor->format('Y-m-d H:i:s')
                : $cursor->format('Y-m-d');

            $row = $rows->get($key);

            $series[] = [
                'bucket' => $key,
                'label' => $useHourly ? $cursor->format('d M H:i') : $cursor->format('d M'),
                'events' => (int) ($row->total ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
                'server' => (int) ($row->server_total ?? 0),
            ];

            $cursor = $useHourly ? $cursor->addHour() : $cursor->addDay();
        }

        return ['series' => $series, 'granularity' => $useHourly ? 'hour' : 'day'];
    }

    // ------------------------------------------------------------- live stream

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function liveData(array $filters): array
    {
        $rows = $this->liveQuery($filters)->limit(60)->get();

        return $rows->map(fn ($row) => $this->presentLiveRow($row))->all();
    }

    /**
     * The live feed is deliberately narrower than the dashboard query: it is the
     * "what is happening right now" table.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<EventLog>
     */
    private function liveQuery(array $filters)
    {
        $query = EventLog::query()
            ->leftJoin('visitors_master', 'visitors_master.visitor_id', '=', 'event_logs.visitor_id')
            ->whereBetween('event_logs.created_at', [$filters['from'], $filters['to']])
            ->orderByDesc('event_logs.id')
            ->select([
                'event_logs.id',
                'event_logs.visitor_id',
                'event_logs.event_name',
                'event_logs.url',
                'event_logs.page_path',
                'event_logs.channel',
                'event_logs.tracking_method',
                'event_logs.dedup_status',
                'event_logs.device_type',
                'event_logs.event_time',
                'event_logs.created_at',
                'visitors_master.ip',
                'visitors_master.isp',
                'visitors_master.city',
                'visitors_master.division',
                'visitors_master.country',
                'visitors_master.browser',
                'visitors_master.os',
                'visitors_master.status',
                'visitors_master.event_count',
                'visitors_master.last_seen_at',
            ]);

        if ($filters['channel'] !== '') {
            $query->where('event_logs.channel', $filters['channel']);
        }

        if ($filters['method'] === 'browser') {
            $query->where('event_logs.tracking_method', EventRecorder::METHOD_BROWSER);
        } elseif ($filters['method'] === 'server') {
            $query->where('event_logs.tracking_method', EventRecorder::METHOD_SERVER);
        }

        if ($filters['division'] !== '') {
            $query->where('visitors_master.division', $filters['division']);
        }

        if ($filters['search'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->where('event_logs.visitor_id', $filters['search'])
                    ->orWhere('visitors_master.ip', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query;
    }

    /**
     * @param  \stdClass  $row
     * @return array<string, mixed>
     */
    private function presentLiveRow($row): array
    {
        return [
            'id' => $row->id,
            'visitor_id' => $row->visitor_id,
            'short_uuid' => 'usr_' . substr(str_replace('-', '', (string) $row->visitor_id), 0, 8),
            'ip' => $row->ip,
            'isp' => $row->isp,
            'location' => trim(implode(', ', array_filter([
                $row->division,
                $row->city,
            ]))),
            'country' => $row->country,
            'device' => trim(implode(' / ', array_filter([
                $this->label($row->browser),
                $this->label($row->os),
                $this->label($row->device_type),
            ]))),
            'channel' => $row->channel,
            'channel_label' => $this->label($row->channel),
            'event' => $row->event_name,
            'tracking_method' => $row->tracking_method,
            'dedup_status' => $row->dedup_status,
            'page_path' => $row->page_path,
            'event_time' => optional($row->event_time)->toDateTimeString() ?? (string) $row->created_at,
            'time_ago' => optional($row->created_at)->diffForHumans() ?? '',
            'status' => (int) ($row->status ?? 1),
            'event_count' => (int) ($row->event_count ?? 0),
            'active' => $row->last_seen_at !== null
                && Carbon::parse($row->last_seen_at)->gt(now()->subMinutes(self::LIVE_WINDOW_MINUTES)),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function liveVisitorCount(array $filters): int
    {
        return (int) $this->visitorQuery($filters)
            ->where('last_seen_at', '>=', now()->subMinutes(self::LIVE_WINDOW_MINUTES))
            ->count();
    }

    // ---------------------------------------------------------- JSON endpoints

    /**
     * Poll target for the live activity table.
     */
    public function live(Request $request)
    {
        $filters = $this->resolveFilters($request);

        return response()->json([
            'rows' => $this->liveData($filters),
            'live_visitors' => $this->liveVisitorCount($filters),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Drill-down for a single visitor: full journey + every event.
     */
    public function visitor(Request $request, string $visitorId)
    {
        $visitor = VisitorMaster::where('visitor_id', $visitorId)->first();

        if (!$visitor) {
            abort(404, 'Visitor not found');
        }

        $events = EventLog::where('visitor_id', $visitorId)
            ->orderBy('event_time')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                // No visitor join here, so the visitor-side columns are absent;
                // presentLiveRow tolerates that via null coalescing.
                return $this->presentLiveRow($row);
            })
            ->values()
            ->all();

        return response()->json([
            'visitor' => [
                'visitor_id' => $visitor->visitor_id,
                'ip' => $visitor->ip,
                'isp' => $visitor->isp,
                'division' => $visitor->division,
                'city' => $visitor->city,
                'country' => $visitor->country,
                'device_type' => $visitor->device_type,
                'browser' => $visitor->browser,
                'os' => $visitor->os,
                'channel' => $visitor->channel,
                'utm_source' => $visitor->utm_source,
                'utm_campaign' => $visitor->utm_campaign,
                'landing_page' => $visitor->landing_page,
                'referrer_host' => $visitor->referrer_host,
                'event_count' => (int) $visitor->event_count,
                'pageview_count' => (int) $visitor->pageview_count,
                'browser_event_count' => (int) $visitor->browser_event_count,
                'server_event_count' => (int) $visitor->server_event_count,
                'status' => (int) $visitor->status,
                'first_seen' => optional($visitor->created_at)->toDateTimeString(),
                'last_seen' => optional($visitor->last_seen_at)->toDateTimeString(),
            ],
            'events' => $events,
        ]);
    }

    // --------------------------------------------------------------- helpers

    private function pct(int $part, int $whole): float
    {
        if ($whole <= 0) {
            return 0.0;
        }

        return round(($part / $whole) * 100, 1);
    }

    private function delta(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function shortHost(?string $host): ?string
    {
        $host = trim((string) $host);

        if ($host === '') {
            return null;
        }

        $host = preg_replace('/^www\./i', '', $host) ?? $host;

        return mb_substr($host, 0, 30);
    }

    /**
     * Turn a snake_case key into a display label.
     */
    private function label(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ucfirst(str_replace('_', ' ', $value));
    }
}