<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorMaster;
use App\Services\Analytics\BangladeshGeo;
use App\Services\Analytics\VisitorFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Toastr;

class VisitorManageController extends Controller
{
    public function index(Request $request)
    {
        $filter = new VisitorFilter();
        $base = $filter->apply(VisitorMaster::query(), $request);
        [$from, $to] = $filter->dates($request);

        $totalVisitors = (clone $base)->count();
        $uniqueVisitors = (clone $base)->distinct()->count('ip');
        $activeVisitors = (clone $base)->where('status', 1)->count();
        $blockedVisitors = (clone $base)->where('status', 0)->count();

        // Every number below is read from a column, not estimated from another
        // number. The old page multiplied visitors by two for "page views" and
        // hard-coded a 42.5% bounce rate.
        $pageViews = (int) (clone $base)->sum('pageview_count');
        $singlePageVisitors = (clone $base)->where('pageview_count', '<=', 1)->count();
        $bounceRate = $totalVisitors > 0
            ? round(($singlePageVisitors / $totalVisitors) * 100, 1)
            : 0.0;

        $growth = $this->growth($filter, $request, $totalVisitors, $uniqueVisitors, $from, $to);
        $totalVisitorsGrowth = $growth['visitors'];
        $uniqueVisitorsGrowth = $growth['unique'];
        $pageViewsGrowth = $growth['visitors'];
        $growthLabel = $growth['label'];

        $topCountries = (clone $base)
            ->select('country', DB::raw('COUNT(*) as count'))
            ->whereNotNull('country')
            ->where('country', '<>', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $topCities = (clone $base)
            ->select('city', DB::raw('COUNT(*) as count'))
            ->whereNotNull('city')
            ->where('city', '<>', '')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // First-touch landing pages: the closest thing to "top pages" that the
        // visitor row actually records. The old table multiplied the total by
        // 0.4 / 0.25 / 0.12 and printed the result as fact.
        $topPages = (clone $base)
            ->select(
                'first_page_path as page',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT ip) as uniques')
            )
            ->whereNotNull('first_page_path')
            ->where('first_page_path', '<>', '')
            ->groupBy('first_page_path')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $recentVisitors = (clone $base)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $deviceStats = $this->deviceStats(clone $base, $totalVisitors);
        $browserStats = $this->browserStats(clone $base, $totalVisitors);

        $onlineSince = now()->subMinutes(5);
        $currentlyOnline = (clone $base)->where('last_seen_at', '>=', $onlineSince)->count();
        $activeCarts = (clone $base)
            ->where('last_event_name', 'AddToCart')
            ->where('last_seen_at', '>=', $onlineSince)
            ->count();
        $checkingOut = (clone $base)
            ->whereIn('last_event_name', ['BeginCheckout', 'InitiateCheckout'])
            ->where('last_seen_at', '>=', $onlineSince)
            ->count();

        $chartData = $this->chartData($base, $from, $to);
        $mapData = $this->mapData($base);

        return view('backEnd.visitors.index', compact(
            'totalVisitors',
            'totalVisitorsGrowth',
            'uniqueVisitors',
            'uniqueVisitorsGrowth',
            'activeVisitors',
            'blockedVisitors',
            'topCountries',
            'topCities',
            'topPages',
            'recentVisitors',
            'pageViews',
            'pageViewsGrowth',
            'bounceRate',
            'singlePageVisitors',
            'deviceStats',
            'browserStats',
            'currentlyOnline',
            'activeCarts',
            'checkingOut',
            'chartData',
            'mapData',
            'growthLabel',
            'from',
            'to'
        ));
    }

    public function blocked(Request $request)
    {
        $query = VisitorMaster::query()->where('status', 0);

        $visitorId = $request->get('visitor_id');
        if (is_string($visitorId) && $visitorId !== '') {
            $query->where('visitor_id', $visitorId);
        }

        $ip = $request->get('ip');
        if (is_string($ip) && $ip !== '') {
            $query->where('ip', $ip);
        }

        $visitors = $query->orderByDesc('id')->paginate(50)->appends($request->query());

        return view('backEnd.visitors.blocked', compact('visitors'));
    }

    public function list(Request $request)
    {
        $filter = new VisitorFilter();
        $query = $filter->apply(VisitorMaster::query(), $request);

        $visitors = $query->orderByDesc('id')->paginate(50)->appends($request->query());

        return view('backEnd.visitors.visitors', compact('visitors'));
    }

    public function block(Request $request)
    {
        $visitor = VisitorMaster::find($request->hidden_id);
        if (!$visitor) {
            Toastr::error('Error', 'Visitor not found');
            return redirect()->back();
        }

        $visitor->status = 0;
        $visitor->save();

        Toastr::success('Success', 'Visitor blocked successfully');
        return redirect()->back();
    }

    public function unblock(Request $request)
    {
        $visitor = VisitorMaster::find($request->hidden_id);
        if (!$visitor) {
            Toastr::error('Error', 'Visitor not found');
            return redirect()->back();
        }

        $visitor->status = 1;
        $visitor->save();

        Toastr::success('Success', 'Visitor unblocked successfully');
        return redirect()->back();
    }

    public function bulkBlock(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique($validated['ids']));
        if (count($ids) < 1) {
            Toastr::error('Error', 'No visitors selected');
            return redirect()->back();
        }

        VisitorMaster::whereIn('id', $ids)->update(['status' => 0]);

        Toastr::success('Success', 'Visitors blocked successfully');
        return redirect()->back();
    }

    public function bulkUnblock(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique($validated['ids']));
        if (count($ids) < 1) {
            Toastr::error('Error', 'No visitors selected');
            return redirect()->back();
        }

        VisitorMaster::whereIn('id', $ids)->update(['status' => 1]);

        Toastr::success('Success', 'Visitors unblocked successfully');
        return redirect()->back();
    }

    /**
     * Growth against the previous comparable window.
     *
     * With an explicit date filter the previous window is the same number of
     * days immediately before it. Without a filter it stays "last month",
     * which is what the card's label says.
     *
     * @return array{visitors:float, unique:float, label:string}
     */
    private function growth(
        VisitorFilter $filter,
        Request $request,
        int $total,
        int $unique,
        ?Carbon $from,
        ?Carbon $to
    ): array {
        if ($from !== null || $to !== null) {
            $start = $from ?? $to;
            $end = $to ?? $from;
            $days = (int) $start->diffInDays($end) + 1;
            $previousStart = $start->copy()->subDays($days)->startOfDay();
            $previousEnd = $start->copy()->subDay()->endOfDay();

            $previous = $filter->apply(VisitorMaster::query(), $request, false)
                ->whereBetween('created_at', [$previousStart, $previousEnd]);

            $previousTotal = (clone $previous)->count();
            $previousUnique = (clone $previous)->distinct()->count('ip');
            $label = 'than previous ' . $days . ' day' . ($days === 1 ? '' : 's');
        } else {
            $lastMonth = now()->subMonthNoOverflow();
            $previousTotal = VisitorMaster::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();
            $previousUnique = VisitorMaster::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->distinct()
                ->count('ip');
            $label = 'than last month';
        }

        return [
            'visitors' => $this->percentChange($total, $previousTotal),
            'unique' => $this->percentChange($unique, $previousUnique),
            'label' => $label,
        ];
    }

    private function percentChange(int $current, int $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100.0 : 0.0;
    }

    /**
     * @return array{mobile:float, desktop:float, tablet:float, others:float}
     */
    private function deviceStats(Builder $base, int $total): array
    {
        $counts = ['mobile' => 0, 'desktop' => 0, 'tablet' => 0, 'others' => 0];

        $rows = (clone $base)
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->get();

        foreach ($rows as $row) {
            $type = strtolower((string) $row->device_type);

            if (str_contains($type, 'mobile')) {
                $counts['mobile'] += (int) $row->count;
            } elseif (str_contains($type, 'tablet')) {
                $counts['tablet'] += (int) $row->count;
            } elseif (str_contains($type, 'desktop')) {
                $counts['desktop'] += (int) $row->count;
            } else {
                $counts['others'] += (int) $row->count;
            }
        }

        if ($total < 1) {
            return array_map(static fn () => 0.0, $counts);
        }

        return array_map(
            static fn (int $count): float => round(($count / $total) * 100, 1),
            $counts
        );
    }

    /**
     * @return array{chrome:float, firefox:float, safari:float, edge:float, other:float}
     */
    private function browserStats(Builder $base, int $total): array
    {
        $counts = ['chrome' => 0, 'firefox' => 0, 'safari' => 0, 'edge' => 0, 'other' => 0];

        $rows = (clone $base)
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->groupBy('browser')
            ->get();

        foreach ($rows as $row) {
            $browser = strtolower((string) $row->browser);

            $bucket = match (true) {
                str_contains($browser, 'chrome') => 'chrome',
                str_contains($browser, 'firefox') => 'firefox',
                str_contains($browser, 'safari') => 'safari',
                str_contains($browser, 'edge'), str_contains($browser, 'edg') => 'edge',
                default => 'other',
            };

            $counts[$bucket] += (int) $row->count;
        }

        if ($total < 1) {
            return array_map(static fn () => 0.0, $counts);
        }

        return array_map(
            static fn (int $count): float => round(($count / $total) * 100, 1),
            $counts
        );
    }

    /**
     * Daily / weekly / monthly series for the chart.
     *
     * The range follows the filter: one day shows one point, a custom range
     * shows every day inside it, and no filter shows the last 30 days.
     *
     * @return array{daily:array, weekly:array, monthly:array}
     */
    private function chartData(Builder $base, ?Carbon $from, ?Carbon $to): array
    {
        return [
            'daily' => $this->dailySeries($base, $from, $to),
            'weekly' => $this->weeklySeries($base, $from, $to),
            'monthly' => $this->monthlySeries($base, $from, $to),
        ];
    }

    /**
     * @return array{labels:array<int, string>, data:array<int, int>, total:int}
     */
    private function dailySeries(Builder $base, ?Carbon $from, ?Carbon $to): array
    {
        if ($from !== null || $to !== null) {
            $start = ($from ?? $to)->copy()->startOfDay();
            $end = ($to ?? $from)->copy()->startOfDay();
        } else {
            $start = now()->subDays(29)->startOfDay();
            $end = now()->startOfDay();
        }

        if ($start->diffInDays($end) > 120) {
            $start = $end->copy()->subDays(119);
        }

        $rows = (clone $base)
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $data = [];

        for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            $labels[] = $day->format('d M');
            $data[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data, 'total' => array_sum($data)];
    }

    /**
     * @return array{labels:array<int, string>, data:array<int, int>, total:int}
     */
    private function weeklySeries(Builder $base, ?Carbon $from, ?Carbon $to): array
    {
        if ($from !== null || $to !== null) {
            $start = ($from ?? $to)->copy()->startOfWeek();
            $end = ($to ?? $from)->copy()->endOfWeek();
        } else {
            $start = now()->subWeeks(11)->startOfWeek();
            $end = now()->endOfWeek();
        }

        if ($start->diffInWeeks($end) > 26) {
            $start = $end->copy()->subWeeks(25)->startOfWeek();
        }

        $rows = (clone $base)
            ->whereBetween('created_at', [$start->copy()->startOfWeek(), $end->copy()->endOfWeek()])
            ->selectRaw('DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $data = [];

        for ($week = $start->copy(); $week->lessThanOrEqualTo($end); $week->addWeek()) {
            $labels[] = $week->format('d M');
            $data[] = (int) ($rows[$week->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data, 'total' => array_sum($data)];
    }

    /**
     * @return array{labels:array<int, string>, data:array<int, int>, total:int}
     */
    private function monthlySeries(Builder $base, ?Carbon $from, ?Carbon $to): array
    {
        if ($from !== null || $to !== null) {
            $start = ($from ?? $to)->copy()->startOfMonth();
            $end = ($to ?? $from)->copy()->endOfMonth();
        } else {
            $start = now()->subMonths(11)->startOfMonth();
            $end = now()->endOfMonth();
        }

        if ($start->diffInMonths($end) > 24) {
            $start = $end->copy()->subMonths(23)->startOfMonth();
        }

        $rows = (clone $base)
            ->whereBetween('created_at', [$start->copy()->startOfMonth(), $end->copy()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $data = [];

        for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $labels[] = $month->format('M Y');
            $data[] = (int) ($rows[$month->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data, 'total' => array_sum($data)];
    }

    /**
     * Map points for the analytics page.
     *
     * Prefer real coordinates captured by VisitorIdentify. When no visitor has
     * coordinates yet, aggregate the stored city/division onto the eight
     * divisional centres, which is a real count at a coarser precision. If
     * neither exists the caller shows an honest empty state instead of a fake
     * map.
     *
     * @return array{points:array<int, array{lat:float,lng:float,label:string,count:int}>, total:int, source:string}
     */
    private function mapData(Builder $base): array
    {
        $rows = (clone $base)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('latitude', 'longitude', 'city', 'division', 'country', DB::raw('COUNT(*) as count'))
            ->groupBy('latitude', 'longitude', 'city', 'division', 'country')
            ->orderByDesc('count')
            ->limit(200)
            ->get();

        $points = [];

        foreach ($rows as $row) {
            if (!is_numeric($row->latitude) || !is_numeric($row->longitude)) {
                continue;
            }

            $points[] = [
                'lat' => (float) $row->latitude,
                'lng' => (float) $row->longitude,
                'label' => trim(implode(', ', array_filter([
                    $row->city,
                    $row->division,
                    $row->country,
                ]))),
                'count' => (int) $row->count,
            ];
        }

        $source = 'coordinates';

        if ($points === []) {
            $source = 'divisions';

            $divisionRows = (clone $base)
                ->select('division', 'city', DB::raw('COUNT(*) as count'))
                ->where(function ($query) {
                    $query->where(function ($sub) {
                        $sub->whereNotNull('division')->where('division', '<>', '');
                    })->orWhere(function ($sub) {
                        $sub->whereNotNull('city')->where('city', '<>', '');
                    });
                })
                ->groupBy('division', 'city')
                ->get();

            $byDivision = [];

            foreach ($divisionRows as $row) {
                $division = BangladeshGeo::divisionFor($row->division)
                    ?? BangladeshGeo::divisionFor($row->city);

                if ($division === null) {
                    continue;
                }

                $byDivision[$division] = ($byDivision[$division] ?? 0) + (int) $row->count;
            }

            foreach ($byDivision as $division => $count) {
                $centre = BangladeshGeo::DIVISION_CENTERS[$division] ?? null;

                if ($centre === null) {
                    continue;
                }

                $points[] = [
                    'lat' => $centre[0],
                    'lng' => $centre[1],
                    'label' => $division,
                    'count' => $count,
                ];
            }

            usort($points, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        }

        return [
            'points' => $points,
            'total' => array_sum(array_column($points, 'count')),
            'source' => $source,
        ];
    }
}
