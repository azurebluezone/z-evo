<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\EventLog;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\VisitorMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function deliveredStatusId(): int
    {
        try {
            $status = OrderStatus::query()->where('slug', 'delivered')->first();
            if ($status && isset($status->id)) {
                return (int) $status->id;
            }
        } catch (\Throwable $e) {
            // no-op
        }

        return 5;
    }

    public function getTotalOrders(): int
    {
        return (int) Cache::remember('admin_dashboard.total_orders', 60, function () {
            return (int) Order::query()->count();
        });
    }

    public function getTodayVisitors(): int
    {
        $start = Carbon::today();

        return (int) Cache::remember('admin_dashboard.today_visitors.' . $start->toDateString(), 60, function () use ($start) {
            return (int) VisitorMaster::query()->where('created_at', '>=', $start)->count();
        });
    }

    public function getTotalRevenueDelivered(): float
    {
        $deliveredStatusId = $this->deliveredStatusId();

        return (float) Cache::remember('admin_dashboard.total_revenue_delivered', 120, function () use ($deliveredStatusId) {
            return (float) Order::query()
                ->where('order_status', $deliveredStatusId)
                ->sum('amount');
        });
    }

    public function getSalesTrendForDays(int $days): array
    {
        $days = max(1, min(90, $days));
        $start = Carbon::today()->subDays($days - 1);
        $deliveredStatusId = $this->deliveredStatusId();

        $cacheKey = 'admin_dashboard.sales_trend_delivered.' . $days . '.' . $start->toDateString();

        return (array) Cache::remember($cacheKey, 120, function () use ($days, $start, $deliveredStatusId) {
            $rows = Order::query()
                ->selectRaw('DATE(created_at) as date, SUM(amount) as amount')
                ->where('order_status', $deliveredStatusId)
                ->whereDate('created_at', '>=', $start)
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $byDate = [];
            foreach ($rows as $row) {
                $byDate[(string) $row->date] = (float) $row->amount;
            }

            $labels = [];
            $series = [];
            for ($i = 0; $i < $days; $i++) {
                $d = (clone $start)->addDays($i);
                $key = $d->toDateString();
                $labels[] = $d->format('d-m-Y');
                $series[] = $byDate[$key] ?? 0;
            }

            return ['labels' => $labels, 'series' => $series];
        });
    }

    public function getSocialEventFeed(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        return (array) Cache::remember('admin_dashboard.social_event_feed.' . $limit, 60, function () use ($limit) {
            return DB::table('event_logs as e')
                ->leftJoin('visitors_master as v', 'v.visitor_id', '=', 'e.visitor_id')
                ->orderByDesc('e.id')
                ->limit($limit)
                ->select([
                    'e.id',
                    'e.visitor_id',
                    'e.event_name',
                    'e.url',
                    'e.event_time',
                    'e.created_at',
                    'v.ip as visitor_ip',
                    'v.country as visitor_country',
                    'v.city as visitor_city',
                ])
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray();
        });
    }

    public function getSocialEventCountToday(): int
    {
        $start = Carbon::today();

        return (int) Cache::remember('admin_dashboard.social_event_count_today.' . $start->toDateString(), 60, function () use ($start) {
            return (int) EventLog::query()->where('created_at', '>=', $start)->count();
        });
    }

    public function getLiveVisitorsSummary(int $windowMinutes = 5): array
    {
        $windowMinutes = max(1, min(60, $windowMinutes));
        $now = Carbon::now();
        $currentStart = (clone $now)->subMinutes($windowMinutes);
        $previousStart = (clone $now)->subMinutes($windowMinutes * 2);

        $cacheKey = 'admin_dashboard.live_visitors.summary.' . $windowMinutes;

        return (array) Cache::remember($cacheKey, 30, function () use ($currentStart, $previousStart, $windowMinutes) {
            $current = (int) EventLog::query()
                ->where('created_at', '>=', $currentStart)
                ->distinct('visitor_id')
                ->count('visitor_id');

            $previous = (int) EventLog::query()
                ->where('created_at', '>=', $previousStart)
                ->where('created_at', '<', $currentStart)
                ->distinct('visitor_id')
                ->count('visitor_id');

            $base = max(1, $previous);
            $growth = (($current - $previous) / $base) * 100;

            return [
                'live' => $current,
                'previous' => $previous,
                'growth_percent' => round($growth, 1),
                'window_minutes' => $windowMinutes,
            ];
        });
    }

    public function getLiveVisitorsTrend(int $points = 12, int $windowMinutes = 5): array
    {
        $points = max(6, min(60, $points));
        $windowMinutes = max(1, min(60, $windowMinutes));

        $end = Carbon::now();
        $start = (clone $end)->subMinutes($points * $windowMinutes);

        $cacheKey = 'admin_dashboard.live_visitors.trend.' . $points . '.' . $windowMinutes;

        return (array) Cache::remember($cacheKey, 30, function () use ($points, $windowMinutes, $start, $end) {
            $rows = EventLog::query()
                ->selectRaw('FLOOR(TIMESTAMPDIFF(MINUTE, ?, created_at) / ?) as bucket, COUNT(DISTINCT visitor_id) as total', [
                    $start->toDateTimeString(),
                    $windowMinutes,
                ])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->groupBy('bucket')
                ->orderBy('bucket', 'asc')
                ->get();

            $byBucket = [];
            foreach ($rows as $row) {
                $byBucket[(int) $row->bucket] = (int) $row->total;
            }

            $series = [];
            for ($i = 0; $i < $points; $i++) {
                $series[] = $byBucket[$i] ?? 0;
            }

            return ['series' => $series];
        });
    }

    public function getWeeklySalesWidgetData(): array
    {
        $trend = $this->getSalesTrendForDays(7);
        $start = Carbon::today()->subDays(6);

        $cacheKey = 'admin_dashboard.weekly_sales_widget.' . $start->toDateString();

        return (array) Cache::remember($cacheKey, 120, function () use ($trend, $start) {
            $visitorRows = VisitorMaster::query()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->whereDate('created_at', '>=', $start)
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $visitorsByDate = [];
            foreach ($visitorRows as $row) {
                $visitorsByDate[(string) $row->date] = (int) $row->total;
            }

            $labels = [];
            $visitors = [];
            for ($i = 0; $i < 7; $i++) {
                $d = (clone $start)->addDays($i);
                $key = $d->toDateString();
                $labels[] = $d->format('D');
                $visitors[] = $visitorsByDate[$key] ?? 0;
            }

            $revenue = array_map(function ($v) {
                return (float) $v;
            }, (array) ($trend['series'] ?? []));

            return [
                'labels' => $labels,
                'revenue' => $revenue,
                'visitors' => $visitors,
                'total_revenue' => array_sum($revenue),
                'total_visitors' => array_sum($visitors),
            ];
        });
    }

    public function getTodaySale(): float
    {
        $start = Carbon::today();

        return (float) Cache::remember('admin_dashboard.today_sale.' . $start->toDateString(), 60, function () use ($start) {
            return (float) Order::query()
                ->whereDate('created_at', '>=', $start)
                ->sum('amount');
        });
    }

    public function getPendingOrdersCount(): int
    {
        return (int) Cache::remember('admin_dashboard.pending_orders', 60, function () {
            $pendingStatusIds = OrderStatus::query()
                ->whereIn('slug', ['pending', 'processing', 'confirmed'])
                ->pluck('id')
                ->toArray();

            if (empty($pendingStatusIds)) {
                $pendingStatusIds = [1, 2, 3];
            }

            return (int) Order::query()
                ->whereIn('order_status', $pendingStatusIds)
                ->count();
        });
    }

    public function getLowStockAlertCount(): int
    {
        return (int) Cache::remember('admin_dashboard.low_stock_alert', 120, function () {
            $threshold = 5;

            return Product::query()
                ->where('stock', '<', $threshold)
                ->where('stock', '>', 0)
                ->count();
        });
    }

    public function getTotalCustomers(): int
    {
        return (int) Cache::remember('admin_dashboard.total_customers', 120, function () {
            return (int) Customer::query()->count();
        });
    }

    public function getRecentOrders(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));

        return (array) Cache::remember('admin_dashboard.recent_orders.' . $limit, 60, function () use ($limit) {
            return Order::query()
                ->with(['customer', 'status', 'payment'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'invoice_id' => $order->invoice_id,
                        'amount' => $order->amount,
                        'customer_name' => $order->customer ? $order->customer->name : 'Guest',
                        'status' => $order->status ? $order->status->name : 'Unknown',
                        'status_slug' => $order->status ? $order->status->slug : 'unknown',
                        'payment_status' => $order->payment ? $order->payment->status : 'unpaid',
                        'created_at' => $order->created_at,
                    ];
                })
                ->toArray();
        });
    }

    public function getFailedLoginAttempts(int $minutes = 60): int
    {
        $window = max(5, min(1440, $minutes));
        $start = Carbon::now()->subMinutes($window);

        return (int) Cache::remember('admin_dashboard.failed_logins.' . $window, 30, function () use ($start) {
            try {
                return (int) DB::table('login_attempts')
                    ->where('success', false)
                    ->where('created_at', '>=', $start)
                    ->count();
            } catch (\Throwable $e) {
                return 0;
            }
        });
    }

    public function getAbnormalOrders(int $minutes = 5, int $threshold = 3): array
    {
        $window = max(1, min(60, $minutes));
        $start = Carbon::now()->subMinutes($window);

        return (array) Cache::remember('admin_dashboard.abnormal_orders.' . $window . '.' . $threshold, 30, function () use ($start, $threshold) {
            try {
                $byCustomer = Order::query()
                    ->selectRaw('customer_id, COUNT(*) as order_count')
                    ->where('created_at', '>=', $start)
                    ->whereNotNull('customer_id')
                    ->groupBy('customer_id')
                    ->having('order_count', '>=', $threshold)
                    ->with('customer:id,name,phone')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'type' => 'customer',
                            'identifier' => $row->customer ? $row->customer->name : 'Customer #' . $row->customer_id,
                            'count' => $row->order_count,
                        ];
                    });

                $byIp = Order::query()
                    ->selectRaw('ip_address, COUNT(*) as order_count')
                    ->where('created_at', '>=', $start)
                    ->whereNotNull('ip_address')
                    ->groupBy('ip_address')
                    ->having('order_count', '>=', $threshold)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'type' => 'ip',
                            'identifier' => $row->ip_address,
                            'count' => $row->order_count,
                        ];
                    });

                return $byCustomer->merge($byIp)->values()->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
