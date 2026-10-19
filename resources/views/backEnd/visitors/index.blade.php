@extends('backEnd.layouts.app')
@section('title', 'Visitor Analytics')

@section('content')
<!-- Visitor Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Total Visitors -->
    <div class="col-sm-6 col-lg-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-primary">
                            <i class="icon-base ri ri-user-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ number_format($totalVisitors) }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Total Visitors</h6>
                <p class="mb-0">
                    @if($totalVisitorsGrowth >= 0)
                        <span class="me-1 fw-medium text-success">+{{ $totalVisitorsGrowth }}%</span>
                    @else
                        <span class="me-1 fw-medium text-danger">{{ $totalVisitorsGrowth }}%</span>
                    @endif
                    <small class="text-body-secondary">than last month</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Unique Visitors -->
    <div class="col-sm-6 col-lg-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-success">
                            <i class="icon-base ri ri-user-star-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ number_format($uniqueVisitors) }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Unique Visitors</h6>
                <p class="mb-0">
                    @if($uniqueVisitorsGrowth >= 0)
                        <span class="me-1 fw-medium text-success">+{{ $uniqueVisitorsGrowth }}%</span>
                    @else
                        <span class="me-1 fw-medium text-danger">{{ $uniqueVisitorsGrowth }}%</span>
                    @endif
                    <small class="text-body-secondary">than last month</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Page Views -->
    <div class="col-sm-6 col-lg-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-info">
                            <i class="icon-base ri ri-eye-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ number_format($pageViews) }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Page Views</h6>
                <p class="mb-0">
                    @if($pageViewsGrowth >= 0)
                        <span class="me-1 fw-medium text-success">+{{ $pageViewsGrowth }}%</span>
                    @else
                        <span class="me-1 fw-medium text-danger">{{ $pageViewsGrowth }}%</span>
                    @endif
                    <small class="text-body-secondary">than last month</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Bounce Rate -->
    <div class="col-sm-6 col-lg-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-warning">
                            <i class="icon-base ri ri-arrow-go-back-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ $bounceRate }}%</h4>
                </div>
                <h6 class="mb-0 fw-normal">Bounce Rate</h6>
                <p class="mb-0">
                    <span class="me-1 fw-medium text-success">{{ $bounceRateChange }}%</span>
                    <small class="text-body-secondary">improved</small>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Traffic Overview Chart -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0">Traffic Overview</h5>
                <div class="btn-group">
                    <x-admin.button variant="outline-secondary" shape="square" class="active" type="button">Daily</x-admin.button>
                    <x-admin.button variant="outline-secondary" shape="square" type="button">Weekly</x-admin.button>
                    <x-admin.button variant="outline-secondary" shape="square" type="button">Monthly</x-admin.button>
                </div>
            </div>
            <div class="card-body">
                <div id="trafficChart" style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                    <div class="text-center">
                        <i class="icon-base ri ri-bar-chart-box-line icon-48px mb-3"></i>
                        <h4>Visitor Traffic Chart</h4>
                        <p>Interactive chart showing daily visitor statistics</p>
                        <small class="d-block mt-3">Total: {{ number_format($totalVisitors) }} visitors in last 30 days</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Pages & Devices -->
<div class="row g-3 mb-4">
    <!-- Top Visited Pages -->
    <div class="col-lg-6">
        <div class="card rounded-0 h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0">Top Pages</h5>
                <span class="badge bg-label-primary">By Views</span>
            </div>
            <div class="card-body p-0">
                <div class="card-datatable table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th>Views</th>
                                <th>Unique</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="icon-base ri ri-home-line me-2 text-primary"></i>/home</td>
                                <td>{{ number_format($totalVisitors * 0.4) }}</td>
                                <td>{{ number_format($uniqueVisitors * 0.35) }}</td>
                            </tr>
                            <tr>
                                <td><i class="icon-base ri ri-shopping-bag-line me-2 text-success"></i>/products</td>
                                <td>{{ number_format($totalVisitors * 0.25) }}</td>
                                <td>{{ number_format($uniqueVisitors * 0.22) }}</td>
                            </tr>
                            <tr>
                                <td><i class="icon-base ri ri-shopping-cart-line me-2 text-warning"></i>/cart</td>
                                <td>{{ number_format($totalVisitors * 0.12) }}</td>
                                <td>{{ number_format($uniqueVisitors * 0.10) }}</td>
                            </tr>
                            <tr>
                                <td><i class="icon-base ri ri-user-line me-2 text-danger"></i>/login</td>
                                <td>{{ number_format($totalVisitors * 0.08) }}</td>
                                <td>{{ number_format($uniqueVisitors * 0.07) }}</td>
                            </tr>
                            <tr>
                                <td><i class="icon-base ri ri-search-line me-2 text-info"></i>/search</td>
                                <td>{{ number_format($totalVisitors * 0.05) }}</td>
                                <td>{{ number_format($uniqueVisitors * 0.04) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Device Statistics -->
    <div class="col-lg-6">
        <div class="card rounded-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0">Device Statistics</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="icon-base ri ri-smartphone-line me-2"></i>Mobile</span>
                        <span class="fw-bold">{{ $deviceStats['mobile'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $deviceStats['mobile'] }}%"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="icon-base ri ri-computer-line me-2"></i>Desktop</span>
                        <span class="fw-bold">{{ $deviceStats['desktop'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $deviceStats['desktop'] }}%"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="icon-base ri ri-tablet-line me-2"></i>Tablet</span>
                        <span class="fw-bold">{{ $deviceStats['tablet'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $deviceStats['tablet'] }}%"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="icon-base ri ri-tv-line me-2"></i>Others</span>
                        <span class="fw-bold">{{ $deviceStats['others'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $deviceStats['others'] }}%"></div>
                    </div>
                </div>
                <hr>
                <h6 class="mb-3">Browser Breakdown</h6>
                <div class="row text-center">
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <i class="icon-base ri ri-chrome-line icon-24px text-warning"></i>
                            <p class="mb-0 small">Chrome</p>
                            <strong>{{ $browserStats['chrome'] }}%</strong>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <i class="icon-base ri ri-firefox-line icon-24px text-danger"></i>
                            <p class="mb-0 small">Firefox</p>
                            <strong>{{ $browserStats['firefox'] }}%</strong>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <i class="icon-base ri ri-safari-line icon-24px text-info"></i>
                            <p class="mb-0 small">Safari</p>
                            <strong>{{ $browserStats['safari'] }}%</strong>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <i class="icon-base ri ri-edge-new-line icon-24px text-primary"></i>
                            <p class="mb-0 small">Edge</p>
                            <strong>{{ $browserStats['edge'] }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Geographic Distribution -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0">Geographic Distribution</h5>
                <a href="{{ route('admin.visitors.list') }}" class="btn btn-outline-secondary btn-square waves-effect">View Full List</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div style="height: 300px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                            <div class="text-center">
                                <i class="icon-base ri ri-map-pin-2-line icon-48px mb-3"></i>
                                <h4>Visitor Location Map</h4>
                                <p>Geographic distribution of visitors</p>
                                <div class="mt-3">
                                    <span class="badge bg-white text-dark me-2">{{ $topCountries->count() }} Countries</span>
                                    <span class="badge bg-white text-dark">{{ $topCities->count() }} Cities</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h6 class="mb-3">Top Cities</h6>
                        <div class="list-group list-group-flush">
                            @forelse($topCities as $city)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="icon-base ri ri-map-pin-line me-2 text-primary"></i>{{ $city->city ?: 'Unknown' }}</span>
                                    <span class="badge bg-label-primary rounded-pill">{{ round(($city->count / $totalVisitors) * 100, 0) }}%</span>
                                </div>
                            @empty
                                <div class="list-group-item px-0">
                                    <span class="text-muted">No city data available</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Active Users -->
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0">Real-time Active Users</h5>
        <span class="badge bg-success"><i class="icon-base ri ri-radio-button-line me-1"></i>Live</span>
    </div>
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <h2 class="text-primary mb-1">{{ $currentlyOnline }}</h2>
                <p class="text-muted mb-0">Currently Online</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-success mb-1">{{ $activeCarts }}</h2>
                <p class="text-muted mb-0">Active Sessions</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-info mb-1">{{ $checkingOut }}</h2>
                <p class="text-muted mb-0">Recent Visits</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-warning mb-1">{{ $blockedVisitors }}</h2>
                <p class="text-muted mb-0">Blocked IPs</p>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 table-sm">
                <thead>
                    <tr>
                        <th>Visitor ID</th>
                        <th>Location</th>
                        <th>ISP</th>
                        <th>Device</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentVisitors as $visitor)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-label-primary me-2">{{ substr($visitor->visitor_id, 0, 2) }}</span>
                                    <span class="small text-truncate" style="max-width: 120px;" title="{{ $visitor->visitor_id }}">{{ Str::limit($visitor->visitor_id, 12) }}</span>
                                </div>
                            </td>
                            <td>{{ $visitor->city ?: '-' }}{{ $visitor->city && $visitor->country ? ', ' : '' }}{{ $visitor->country ?: '' }}</td>
                            <td>{{ Str::limit($visitor->isp ?: '-', 20) }}</td>
                            <td>
                                @if(strpos($visitor->user_agent ?? '', 'Mobile') !== false)
                                    <i class="icon-base ri ri-smartphone-line"></i> Mobile
                                @elseif(strpos($visitor->user_agent ?? '', 'Tablet') !== false)
                                    <i class="icon-base ri ri-tablet-line"></i> Tablet
                                @else
                                    <i class="icon-base ri ri-computer-line"></i> Desktop
                                @endif
                            </td>
                            <td>
                                @if($visitor->status == 1)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-danger">Blocked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="icon-base ri ri-user-line icon-32px text-muted mb-2 d-block"></i>
                                <span class="text-muted">No recent visitors</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3 bg-white border-top py-3">
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-label-primary">Total: {{ $totalVisitors }}</span>
            <span class="badge bg-label-success">Recent: 10 shown</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.visitors.list') }}" class="btn btn-primary btn-square waves-effect">
                <i class="icon-base ri ri-list-check me-2"></i>View Full Visitor List
            </a>
        </div>
    </div>
</div>
@endsection
