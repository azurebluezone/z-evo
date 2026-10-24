@extends('backEnd.layouts.app')
@section('title','Dashboard')
@section('content')
<div class="row g-3">
    <div class="col-12">
        <!-- Quick Actions -->
        <div class="d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-start align-items-md-center gap-2 mb-3 no-print">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h4 class="mb-0">Dashboard</h4>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-2 w-100 w-md-auto ms-md-auto">
                <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-sm">All Orders</a>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary btn-sm">Invoice List</a>
                <a href="{{ route('products.create') }}" class="btn btn-outline-secondary btn-sm">Create Product</a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">Manage Products</a>
                <a href="{{ route('coupons.create') }}" class="btn btn-outline-secondary btn-sm">Create Coupon</a>
                <a href="{{ route('admin.invoices.index') }}?status=pending&print=bulk" class="btn btn-outline-primary btn-sm"><i class="ri-printer-line"></i> Bulk Print</a>
                {{-- POS is not routable yet. OrderController has the full cart_*/order_*
                     POS family on the `pos_shopping` cart instance, but no route and no
                     view were ever added - so this button used to 404 at /admin/pos.
                     Un-comment the moment a POS screen exists. --}}
                {{-- <a href="{{ url('/admin/pos') }}" class="btn btn-outline-success btn-sm"><i class="ri-shopping-cart-line"></i> POS Interface</a> --}}
                <a href="{{ route('products.index') }}?stock_alert=1" class="btn btn-outline-info btn-sm"><i class="ri-stock-line"></i> Stock Update</a>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="row g-3 mb-4">
            <!-- Today's Sale -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Today's Sale</p>
                                <h4 class="mb-0 text-primary">৳{{ number_format($today_sale ?? 0) }}</h4>
                                <small class="text-success"><i class="ri-arrow-up-line"></i> Cash In Today</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ri ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Orders -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body position-relative">
                        <a href="{{ route('admin.orders', ['slug' => 'pending']) }}" class="stretched-link" aria-label="View pending orders"></a>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Pending Orders</p>
                                <h4 class="mb-0 text-warning">{{ number_format($pending_orders ?? 0) }}</h4>
                                <small class="text-warning"><i class="ri-time-line"></i> Need Processing</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="icon-base ri ri-shopping-bag-3-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted text-truncate" style="font-size: 0.8rem;">Low Stock</p>
                                <h4 class="mb-0 text-danger">{{ number_format($low_stock_alert ?? 0) }}</h4>
                                <small class="text-danger text-nowrap"><i class="icon-base ri ri-alert-line"></i> Stock &lt; 5</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="icon-base ri ri-store-3-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Customers -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Total Customers</p>
                                <h4 class="mb-0 text-info">{{ number_format($total_customers ?? 0) }}</h4>
                                <small class="text-info"><i class="ri-user-add-line"></i> Registered Users</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ri ri-user-heart-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legacy Stats Cards (kept for reference) -->
        <div class="card mb-4">
            <div class="card-widget-separator-wrapper">
                <div class="card-body card-widget-separator">
                    <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                                <div>
                                    <h4 class="mb-0">৳{{ number_format($total_revenue ?? 0) }}</h4>
                                    <p class="mb-0">Total Revenue (Delivered)</p>
                                </div>
                                <div class="avatar me-sm-6">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-money-dollar-circle-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6">
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                                <div>
                                    <h4 class="mb-0">{{ number_format($total_order ?? 0) }}</h4>
                                    <p class="mb-0">Total Orders</p>
                                </div>
                                <div class="avatar me-lg-6">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-file-list-3-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none">
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                                <div>
                                    <h4 class="mb-0">{{ number_format($today_visitors ?? 0) }}</h4>
                                    <p class="mb-0">Today's Visitors</p>
                                </div>
                                <div class="avatar me-sm-6">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-eye-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0">{{ number_format($social_event_count_today ?? 0) }}</h4>
                                    <p class="mb-0">Social Events Today</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-notification-3-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <!-- Weekly Sales Trend (7 Days) -->
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Sales Trend (Last 7 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div id="sales-analytics" style="min-height: 360px;"></div>
                    </div>
                </div>
            </div>

            <!-- Live Visitors -->
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        @php
                            $liveVisitorsGrowth = (float) (($live_visitors_summary['growth_percent'] ?? 0));
                            $liveVisitorsGrowthUp = $liveVisitorsGrowth >= 0;
                            $liveVisitorsCount = (int) (($live_visitors_summary['live'] ?? 0));
                        @endphp
                        <div class="d-flex justify-content-between mb-1">
                            <h5 class="mb-0">Live Visitors</h5>
                            <div class="d-flex align-items-center {{ $liveVisitorsGrowthUp ? 'text-success' : 'text-danger' }}">
                                <p class="mb-0 me-2">{{ $liveVisitorsGrowthUp ? '+' : '' }}{{ number_format($liveVisitorsGrowth, 1) }}%</p>
                                <i class="icon-base ri {{ $liveVisitorsGrowthUp ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }} mt-n1"></i>
                            </div>
                        </div>
                        <p class="card-subtitle mb-0">Total {{ number_format($liveVisitorsCount) }} Visitors Are Live</p>
                    </div>
                    <div class="card-body">
                        <div id="liveVisitors" style="min-height: 200px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle Row: Social Feed + Weekly Sales Widget -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Social Event Feed</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm" id="social-event-feed-table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($social_event_feed ?? []) as $row)
                                    @php
                                        $eventName = (string) ($row['event_name'] ?? '');
                                        $url = (string) ($row['url'] ?? '');
                                        $createdAt = $row['created_at'] ?? null;
                                        $created = $createdAt ? \Carbon\Carbon::parse($createdAt)->diffForHumans() : '';
                                    @endphp
                                    <tr>
                                        <td style="white-space: normal; word-break: break-word;">
                                            <div class="fw-medium">{{ $eventName }}</div>
                                            @if($url)
                                                <div class="text-muted small">{{ $url }}</div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $created }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">No events found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-1">Weekly Sales</h5>
                            <div class="dropdown">
                                <x-admin.button variant="text-secondary" ripple="true" class="rounded-pill text-body-secondary border-0 p-1" type="button" id="weeklySalesDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base ri ri-more-2-line"></i>
                                </x-admin.button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="weeklySalesDropdown">
                                    <a class="dropdown-item waves-effect" href="javascript:void(0);">Refresh</a>
                                    <a class="dropdown-item waves-effect" href="javascript:void(0);">Update</a>
                                    <a class="dropdown-item waves-effect" href="javascript:void(0);">Share</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0 card-subtitle">Total ৳{{ number_format((float) ($weekly_sales_widget['total_revenue'] ?? 0)) }} Sales</p>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6 d-flex align-items-center">
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-primary rounded">
                                        <i class="icon-base ri ri-funds-line icon-24px"></i>
                                    </div>
                                </div>
                                <div class="ms-3 d-flex flex-column">
                                    <p class="mb-0">Revenue</p>
                                    <h6 class="mb-0">৳{{ number_format((float) ($weekly_sales_widget['total_revenue'] ?? 0)) }}</h6>
                                </div>
                            </div>
                            <div class="col-6 d-flex align-items-center">
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="icon-base ri ri-eye-line icon-24px"></i>
                                    </div>
                                </div>
                                <div class="ms-3 d-flex flex-column">
                                    <p class="mb-0">Visitors</p>
                                    <h6 class="mb-0">{{ number_format((int) ($weekly_sales_widget['total_visitors'] ?? 0)) }}</h6>
                                </div>
                            </div>
                        </div>
                        <div id="weeklySalesChart" style="min-height: 235px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Row -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Transactions (Latest 5 Orders)</h5>
                        <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="recent-orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                        <th>Order Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recent_orders as $order)
                                    <tr>
                                        <td><span class="fw-medium">#{{ $order['invoice_id'] ?? $order['id'] }}</span></td>
                                        <td>{{ $order['customer_name'] ?? 'Guest' }}</td>
                                        <td>৳{{ number_format($order['amount'] ?? 0, 2) }}</td>
                                        <td>
                                            @php
                                                $paymentStatus = strtolower($order['payment_status'] ?? 'unpaid');
                                                $paymentBadgeClass = match($paymentStatus) {
                                                    'paid', 'completed', 'success' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'failed', 'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $paymentBadgeClass }}">{{ ucfirst($order['payment_status'] ?? 'Unpaid') }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusSlug = $order['status_slug'] ?? 'unknown';
                                                $statusBadgeClass = match($statusSlug) {
                                                    'delivered' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'processing', 'confirmed' => 'bg-info',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $statusBadgeClass }}">{{ $order['status'] ?? 'Unknown' }}</span>
                                        </td>
                                        <td>{{ isset($order['created_at']) ? \Carbon\Carbon::parse($order['created_at'])->format('d-m-Y H:i') : '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No recent orders found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Safety & Support Widget + Latest Customers -->
        <div class="row g-3">
            <!-- Fraud Alert & Security -->
            <div class="col-12 col-xl-6">
                <div class="card h-100 border border-danger border-opacity-25">
                    <div class="card-header bg-danger bg-opacity-10">
                        <h5 class="mb-0 text-danger"><i class="ri ri-error-warning-line"></i> Safety & Support Alerts</h5>
                    </div>
                    <div class="card-body">
                        <!-- Failed Login Attempts -->
                        <div class="d-flex align-items-center mb-3 p-2 rounded {{ $failed_login_attempts > 0 ? 'bg-warning bg-opacity-10' : 'bg-success bg-opacity-10' }}">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded {{ $failed_login_attempts > 0 ? 'bg-warning' : 'bg-success' }}">
                                    <i class="ri-lock-unlock-line {{ $failed_login_attempts > 0 ? 'text-dark' : 'text-white' }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Failed Login Attempts (1h)</h6>
                                <small class="{{ $failed_login_attempts > 5 ? 'text-danger' : 'text-muted' }}">
                                    {{ $failed_login_attempts }} failed attempts detected
                                </small>
                            </div>
                            @if($failed_login_attempts > 5)
                                <span class="badge bg-danger">Warning</span>
                            @endif
                        </div>

                        <!-- Abnormal Orders -->
                        <div class="d-flex align-items-center mb-3 p-2 rounded {{ count($abnormal_orders ?? []) > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }}">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded {{ count($abnormal_orders ?? []) > 0 ? 'bg-danger' : 'bg-success' }}">
                                    <i class="ri-shopping-cart-2-line text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Abnormal Orders (5min)</h6>
                                <small class="{{ count($abnormal_orders ?? []) > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ count($abnormal_orders ?? []) }} suspicious patterns detected
                                </small>
                            </div>
                            @if(count($abnormal_orders ?? []) > 0)
                                <span class="badge bg-danger">Alert</span>
                            @endif
                        </div>

                        @if(count($abnormal_orders ?? []) > 0)
                        <div class="mt-3">
                            <h6 class="small fw-bold text-muted mb-2">Detected Patterns:</h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($abnormal_orders as $abnormal)
                                <li class="small mb-1">
                                    <i class="ri-error-warning-line text-danger"></i>
                                    <strong>{{ $abnormal['identifier'] ?? 'Unknown' }}</strong>
                                    ({{ $abnormal['count'] }} orders in 5min)
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Latest Customers -->
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Latest Customers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="latest-customers-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($latest_customer as $customer)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $customer->name }}</td>
                                        <td>{{ $customer->phone ?? 'N/A' }}</td>
                                        <td>{{ $customer->created_at->format('d-m-Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('script')
    <script>
      (function () {
        var trend7 = @json($trend7 ?? ['labels' => [], 'series' => []]);
        var liveVisitorsTrend = @json($live_visitors_trend ?? ['series' => []]);
        var weeklySalesWidget = @json($weekly_sales_widget ?? ['labels' => [], 'revenue' => [], 'visitors' => []]);
        var salesChart = null;

        function renderSales() {
          var el = document.querySelector('#sales-analytics');
          if (!el || typeof ApexCharts === 'undefined') return;

          var d = trend7 || { labels: [], series: [] };
          var options = {
            chart: { height: 360, type: 'area', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: 'Revenue', data: d.series }],
            xaxis: { categories: d.labels, axisBorder: { show: false }, axisTicks: { show: false } },
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2, stops: [0, 90, 100] } },
            grid: { strokeDashArray: 4, borderColor: '#e0e0e0' },
            colors: ['#696cff'],
            dataLabels: { enabled: false },
            yaxis: { labels: { formatter: function(val) { return '৳' + val; } } },
            tooltip: { y: { formatter: function(val) { return '৳' + val; } } }
          };

          if (salesChart) { salesChart.destroy(); }
          salesChart = new ApexCharts(el, options);
          salesChart.render();
        }

        function renderLiveVisitors() {
          var el = document.querySelector('#liveVisitors');
          if (!el || typeof ApexCharts === 'undefined') return;

          var series = (liveVisitorsTrend && Array.isArray(liveVisitorsTrend.series)) ? liveVisitorsTrend.series : [];
          var options = {
            chart: {
              height: 200,
              type: 'bar',
              toolbar: { show: false },
              fontFamily: 'Inter, sans-serif'
            },
            series: [{ name: 'Live Visitors', data: series }],
            plotOptions: {
              bar: {
                borderRadius: 6,
                columnWidth: '60%',
                distributed: true
              }
            },
            dataLabels: { enabled: false },
            grid: { show: false, padding: { left: 0, right: 0 } },
            xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { show: false },
            colors: ['#72e128'],
            tooltip: { enabled: true }
          };

          var c = new ApexCharts(el, options);
          c.render();
        }

        function renderWeeklySales() {
          var el = document.querySelector('#weeklySalesChart');
          if (!el || typeof ApexCharts === 'undefined') return;

          var labels = (weeklySalesWidget && Array.isArray(weeklySalesWidget.labels)) ? weeklySalesWidget.labels : [];
          var revenue = (weeklySalesWidget && Array.isArray(weeklySalesWidget.revenue)) ? weeklySalesWidget.revenue : [];
          var visitors = (weeklySalesWidget && Array.isArray(weeklySalesWidget.visitors)) ? weeklySalesWidget.visitors : [];

          var options = {
            chart: {
              height: 235,
              type: 'line',
              stacked: false,
              toolbar: { show: false },
              fontFamily: 'Inter, sans-serif'
            },
            series: [
              { name: 'Revenue', type: 'column', data: revenue },
              { name: 'Visitors', type: 'line', data: visitors }
            ],
            stroke: { width: [0, 3], curve: 'smooth' },
            plotOptions: {
              bar: {
                borderRadius: 8,
                columnWidth: '45%'
              }
            },
            dataLabels: { enabled: false },
            xaxis: { categories: labels, axisBorder: { show: false } },
            yaxis: [
              { show: false },
              { opposite: true, show: false }
            ],
            grid: { strokeDashArray: 4, borderColor: '#e0e0e0' },
            colors: ['#696cff', '#ffab00'],
            legend: { show: false },
            tooltip: { shared: true, intersect: false }
          };

          var c = new ApexCharts(el, options);
          c.render();
        }

        function initCharts() {
          if (typeof ApexCharts === 'undefined') {
            setTimeout(initCharts, 100);
            return;
          }
          renderSales();
          renderLiveVisitors();
          renderWeeklySales();
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', initCharts);
        } else {
          initCharts();
        }
      })();
    </script>
@endpush

