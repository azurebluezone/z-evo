@extends('backEnd.layouts.app')
@section('title', 'Visitor Analytics')

@section('css')
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/leaflet/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-visitors-analytics.css') }}">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="icon-base ri ri-bar-chart-box-line me-2"></i>Visitor Analytics</h4>
        <p class="text-muted mb-0">
            @if($from && $to)
                Showing {{ number_format($totalVisitors) }} visitors from
                {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}
            @elseif($from || $to)
                Showing {{ number_format($totalVisitors) }} visitors on
                {{ ($from ?? $to)->format('d M Y') }}
            @else
                Showing all-time visitor traffic
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.visitors.list', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="icon-base ri ri-list-check me-2"></i>Visitor List
        </a>
        <a href="{{ route('admin.visitors.phone-blocks') }}" class="btn btn-outline-danger">
            <i class="icon-base ri ri-shield-user-line me-2"></i>Phone Blocks
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card rounded-0 mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0"><i class="icon-base ri ri-filter-3-line me-2"></i>Filter</h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                data-bs-target="#analyticsFilterBody" aria-expanded="true" aria-controls="analyticsFilterBody">
            <i class="icon-base ri ri-arrow-down-s-line"></i>
        </button>
    </div>
    <div class="collapse show" id="analyticsFilterBody">
        <div class="card-body">
            <form method="get" action="{{ route('admin.visitors.index') }}">
                <div class="row g-3">
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="form-floating form-floating-outline">
                            <input type="date" name="from" id="analytics-from" class="form-control"
                                   value="{{ request()->get('from') }}">
                            <label for="analytics-from">From Date</label>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="form-floating form-floating-outline">
                            <input type="date" name="to" id="analytics-to" class="form-control"
                                   value="{{ request()->get('to') }}">
                            <label for="analytics-to">To Date</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="country" id="analytics-country" class="form-control"
                                   value="{{ request()->get('country') }}" placeholder="Country">
                            <label for="analytics-country">Country</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="city" id="analytics-city" class="form-control"
                                   value="{{ request()->get('city') }}" placeholder="City">
                            <label for="analytics-city">City</label>
                        </div>
                    </div>
                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="icon-base ri ri-search-line me-1"></i>Apply
                        </button>
                        <a href="{{ route('admin.visitors.index') }}" class="btn btn-outline-secondary" title="Reset filters">
                            <i class="icon-base ri ri-refresh-line"></i>
                        </a>
                    </div>
                </div>

                <div class="collapse mt-3" id="analyticsMoreFilters">
                    <div class="row g-3">
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="visitor_id" id="analytics-visitor-id" class="form-control"
                                       value="{{ request()->get('visitor_id') }}" placeholder="Visitor ID">
                                <label for="analytics-visitor-id">Visitor ID</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="ip" id="analytics-ip" class="form-control"
                                       value="{{ request()->get('ip') }}" placeholder="IP Address">
                                <label for="analytics-ip">IP Address</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="division" id="analytics-division" class="form-control"
                                       value="{{ request()->get('division') }}" placeholder="Division">
                                <label for="analytics-division">Division</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="district" id="analytics-district" class="form-control"
                                       value="{{ request()->get('district') }}" placeholder="District">
                                <label for="analytics-district">District</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="isp" id="analytics-isp" class="form-control"
                                       value="{{ request()->get('isp') }}" placeholder="ISP">
                                <label for="analytics-isp">ISP</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="device_type" id="analytics-device" class="form-control"
                                       value="{{ request()->get('device_type') }}" placeholder="Device">
                                <label for="analytics-device">Device Type</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="browser" id="analytics-browser" class="form-control"
                                       value="{{ request()->get('browser') }}" placeholder="Browser">
                                <label for="analytics-browser">Browser</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="os" id="analytics-os" class="form-control"
                                       value="{{ request()->get('os') }}" placeholder="Operating System">
                                <label for="analytics-os">Operating System</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="channel" id="analytics-channel" class="form-control"
                                       value="{{ request()->get('channel') }}" placeholder="Channel">
                                <label for="analytics-channel">Channel</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-8 col-lg-9">
                            <div class="form-floating form-floating-outline">
                                <input type="text" name="agent" id="analytics-agent" class="form-control"
                                       value="{{ request()->get('agent', request()->get('user_agent')) }}"
                                       placeholder="User agent contains">
                                <label for="analytics-agent">User Agent (browser string)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-sm btn-link px-0" type="button" data-bs-toggle="collapse"
                            data-bs-target="#analyticsMoreFilters" aria-expanded="false" aria-controls="analyticsMoreFilters">
                        <i class="icon-base ri ri-add-line me-1"></i>More column filters
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Visitor Statistics Cards -->
<div class="row g-3 mb-4">
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
                    <small class="text-body-secondary">{{ $growthLabel }}</small>
                </p>
            </div>
        </div>
    </div>

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
                <h6 class="mb-0 fw-normal">Unique IPs</h6>
                <p class="mb-0">
                    @if($uniqueVisitorsGrowth >= 0)
                        <span class="me-1 fw-medium text-success">+{{ $uniqueVisitorsGrowth }}%</span>
                    @else
                        <span class="me-1 fw-medium text-danger">{{ $uniqueVisitorsGrowth }}%</span>
                    @endif
                    <small class="text-body-secondary">{{ $growthLabel }}</small>
                </p>
            </div>
        </div>
    </div>

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
                    <small class="text-body-secondary">{{ $growthLabel }}</small>
                </p>
            </div>
        </div>
    </div>

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
                    <small class="text-body-secondary">
                        single-page visits ({{ number_format($singlePageVisitors) }} of {{ number_format($totalVisitors) }})
                    </small>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Traffic Overview Chart -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                <div>
                    <h5 class="mb-0">Traffic Overview</h5>
                    <small class="text-muted" id="trafficChartSummary">
                        {{ number_format($totalVisitors) }} visitors in the selected range
                    </small>
                </div>
                <div class="btn-group" role="group" aria-label="Chart range">
                    <button type="button" class="btn btn-outline-secondary active" data-chart-range="daily" aria-pressed="true">Daily</button>
                    <button type="button" class="btn btn-outline-secondary" data-chart-range="weekly" aria-pressed="false">Weekly</button>
                    <button type="button" class="btn btn-outline-secondary" data-chart-range="monthly" aria-pressed="false">Monthly</button>
                </div>
            </div>
            <div class="card-body">
                <div id="trafficChart" class="visitors-chart" role="img" aria-label="Visitor traffic chart"></div>
                <noscript>
                    <p class="text-muted mb-0">JavaScript is required to draw the traffic chart.</p>
                </noscript>
            </div>
        </div>
    </div>
</div>

<!-- Top Pages & Devices -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card rounded-0 h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0">Top Landing Pages</h5>
                <span class="badge bg-label-primary">By first visit</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th class="text-end">Visitors</th>
                                <th class="text-end">Unique IPs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPages as $page)
                                <tr>
                                    <td class="text-truncate" style="max-width: 22rem;" title="{{ $page->page }}">
                                        <i class="icon-base ri ri-file-list-3-line me-2 text-primary"></i>{{ $page->page }}
                                    </td>
                                    <td class="text-end">{{ number_format($page->views) }}</td>
                                    <td class="text-end">{{ number_format($page->uniques) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No landing page recorded for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card rounded-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0">Device Statistics</h5>
            </div>
            <div class="card-body">
                @foreach([
                    'mobile' => ['Mobile', 'ri-smartphone-line', 'bg-primary'],
                    'desktop' => ['Desktop', 'ri-computer-line', 'bg-success'],
                    'tablet' => ['Tablet', 'ri-tablet-line', 'bg-info'],
                    'others' => ['Others', 'ri-tv-line', 'bg-secondary'],
                ] as $key => [$label, $icon, $colour])
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="icon-base {{ $icon }} me-2"></i>{{ $label }}</span>
                            <span class="fw-bold">{{ $deviceStats[$key] }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar {{ $colour }}" role="progressbar"
                                 style="width: {{ $deviceStats[$key] }}%"
                                 aria-valuenow="{{ $deviceStats[$key] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
                <hr>
                <h6 class="mb-3">Browser Breakdown</h6>
                <div class="row text-center g-2">
                    @foreach([
                        'chrome' => ['Chrome', 'ri-chrome-line', 'text-warning'],
                        'firefox' => ['Firefox', 'ri-firefox-line', 'text-danger'],
                        'safari' => ['Safari', 'ri-safari-line', 'text-info'],
                        'edge' => ['Edge', 'ri-edge-new-line', 'text-primary'],
                    ] as $key => [$label, $icon, $colour])
                        <div class="col-3">
                            <div class="border rounded p-2 h-100">
                                <i class="icon-base {{ $icon }} icon-24px {{ $colour }}"></i>
                                <p class="mb-0 small">{{ $label }}</p>
                                <strong>{{ $browserStats[$key] }}%</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Geographic Distribution -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                <div>
                    <h5 class="mb-0">Geographic Distribution</h5>
                    <small class="text-muted" id="visitorMapSummary">
                        {{ number_format($mapData['total']) }} located visitors
                        @if(count($mapData['points']) > 0)
                            &middot; {{ count($mapData['points']) }} map point{{ count($mapData['points']) === 1 ? '' : 's' }}
                            &middot; {{ $mapData['source'] === 'coordinates' ? 'IP coordinates' : 'division centres' }}
                        @else
                            &middot; nothing to plot yet
                        @endif
                    </small>
                </div>
                <a href="{{ route('admin.visitors.list', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                    View Full List
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div id="visitorMap" class="visitors-map"></div>
                        <div id="visitorMapEmpty" class="alert alert-light border mt-2 mb-0 d-none">
                            <i class="icon-base ri ri-information-line me-1"></i>
                            <span>No public-IP location recorded for this filter yet. New visitors are geolocated on their first request; private/local IPs are never mapped.</span>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h6 class="mb-2">Top Countries</h6>
                        <div class="list-group list-group-flush mb-3">
                            @forelse($topCountries as $country)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-truncate">{{ $country->country ?: 'Unknown' }}</span>
                                    <span class="badge bg-label-info rounded-pill">{{ number_format($country->count) }}</span>
                                </div>
                            @empty
                                <div class="list-group-item px-0"><span class="text-muted">No country data</span></div>
                            @endforelse
                        </div>
                        <h6 class="mb-2">Top Cities</h6>
                        <div class="list-group list-group-flush">
                            @forelse($topCities as $city)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-truncate"><i class="icon-base ri ri-map-pin-line me-2 text-primary"></i>{{ $city->city ?: 'Unknown' }}</span>
                                    <span class="badge bg-label-primary rounded-pill">
                                        {{ $totalVisitors > 0 ? round(($city->count / $totalVisitors) * 100) : 0 }}%
                                    </span>
                                </div>
                            @empty
                                <div class="list-group-item px-0"><span class="text-muted">No city data available</span></div>
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
        <span class="badge bg-success"><i class="icon-base ri ri-radio-button-line me-1"></i>Last 5 minutes</span>
    </div>
    <div class="card-body">
        <div class="row text-center mb-4 g-3">
            <div class="col-md-3">
                <h2 class="text-primary mb-1">{{ number_format($currentlyOnline) }}</h2>
                <p class="text-muted mb-0">Currently Online</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-success mb-1">{{ number_format($activeCarts) }}</h2>
                <p class="text-muted mb-0">Added to Cart</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-info mb-1">{{ number_format($checkingOut) }}</h2>
                <p class="text-muted mb-0">In Checkout</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-warning mb-1">{{ number_format($blockedVisitors) }}</h2>
                <p class="text-muted mb-0">Blocked Visitors</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-sm">
                <thead>
                    <tr>
                        <th>Visitor ID</th>
                        <th>Location</th>
                        <th>ISP</th>
                        <th>Device</th>
                        <th>Last seen</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentVisitors as $visitor)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-label-primary me-2">{{ substr($visitor->visitor_id ?? '?', 0, 2) }}</span>
                                    <span class="small text-truncate" style="max-width: 120px;" title="{{ $visitor->visitor_id }}">
                                        {{ Str::limit((string) $visitor->visitor_id, 12) }}
                                    </span>
                                </div>
                            </td>
                            <td>{{ $visitor->city ?: '-' }}{{ $visitor->city && $visitor->country ? ', ' : '' }}{{ $visitor->country ?: '' }}</td>
                            <td>{{ Str::limit($visitor->isp ?: '-', 20) }}</td>
                            <td>
                                @if(str_contains(strtolower((string) $visitor->device_type), 'mobile'))
                                    <i class="icon-base ri ri-smartphone-line"></i> Mobile
                                @elseif(str_contains(strtolower((string) $visitor->device_type), 'tablet'))
                                    <i class="icon-base ri ri-tablet-line"></i> Tablet
                                @else
                                    <i class="icon-base ri ri-computer-line"></i> {{ $visitor->device_type ?: 'Desktop' }}
                                @endif
                            </td>
                            <td class="small text-nowrap">
                                {{ $visitor->last_seen_at ? $visitor->last_seen_at->diffForHumans() : '-' }}
                            </td>
                            <td>
                                @if((int) $visitor->status === 1)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-danger">Blocked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
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
            <span class="badge bg-label-primary">Total: {{ number_format($totalVisitors) }}</span>
            <span class="badge bg-label-success">Recent: {{ $recentVisitors->count() }} shown</span>
        </div>
        <a href="{{ route('admin.visitors.list', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="icon-base ri ri-list-check me-2"></i>View Full Visitor List
        </a>
    </div>
</div>

<script type="application/json" id="visitorAnalyticsChartData">@json($chartData)</script>
<script type="application/json" id="visitorAnalyticsMapData">@json($mapData)</script>
@endsection

@section('script')
<script src="{{ asset('admin/assets/vendor/libs/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('admin/assets/js/admin-page-visitors-analytics.js') }}"></script>
@endsection
