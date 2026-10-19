@extends('backEnd.layouts.app')
@section('title', 'Social Events - ' . ucfirst($platform))

@section('content')
@php
    $platformLabel = $platform === 'google' ? 'Google (GTM)' : ucfirst($platform);
    $baseRoute = $platform === 'facebook'
        ? route('admin.social-events.facebook')
        : ($platform === 'google'
            ? route('admin.social-events.google')
            : route('admin.social-events.tiktok'));

    $eventOptions = $allowedEvents ?? ['PageView','AddToCart','InitiateCheckout','Purchase','FakeOrderAttempt'];
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title mb-0">{{ $platformLabel }}</h4>
                    <div class="text-muted small">Tracking Overview, Configuration & Logs</div>
                </div>
                <form method="GET" action="{{ $baseRoute }}" class="d-flex gap-2">
                    <input type="hidden" name="event" value="{{ $event ?? '' }}">
                    <select name="days" class="form-control">
                        @foreach([1,3,7,14,30,60,90] as $d)
                            <option value="{{ $d }}" @if((int)$days === (int)$d) selected @endif>Last {{ $d }} day(s)</option>
                        @endforeach
                    </select>
                    <x-admin.button variant="primary" type="submit">Apply</x-admin.button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <h6 class="mb-1">Visitors ({{ $days }} days)</h6>
                    <h3 class="mb-0">{{ $visitorCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <h6 class="mb-1">Events ({{ $days }} days)@if(!empty($event)) - {{ $event }}@endif</h6>
                    <h3 class="mb-0">{{ $eventCount }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <h6 class="mb-1">Unique URLs ({{ $days }} days)</h6>
                    <h3 class="mb-0">{{ $urlCount ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="mb-2">Tracking Status</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @if($platform === 'facebook')
                                <span class="badge {{ ($status['server'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">Server: {{ ($status['server'] ?? false) ? 'Active' : 'Inactive' }}</span>
                                <span class="badge {{ ($status['enhanced_ecommerce'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">E-commerce: {{ ($status['enhanced_ecommerce'] ?? false) ? 'Active' : 'Inactive' }}</span>
                            @elseif($platform === 'google')
                                <span class="badge {{ ($status['server'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">Server: {{ ($status['server'] ?? false) ? 'Active' : 'Inactive' }}</span>
                            @elseif($platform === 'tiktok')
                                <span class="badge {{ ($status['browser'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">Browser: {{ ($status['browser'] ?? false) ? 'Active' : 'Inactive' }}</span>
                                <span class="badge {{ ($status['server'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">Server: {{ ($status['server'] ?? false) ? 'Active' : 'Inactive' }}</span>
                                <span class="badge {{ ($status['test_mode'] ?? false) ? 'bg-warning' : 'bg-label-secondary' }}">Test Mode: {{ ($status['test_mode'] ?? false) ? 'ON' : 'OFF' }}</span>
                            @endif
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.social-events.index') }}" class="btn btn-sm btn-label-secondary">Settings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Quick Event Filter</h5>
                    <div class="small text-muted">Tap a button to view specific event logs</div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap flex-lg-nowrap gap-2 overflow-auto">
                        <a href="{{ $baseRoute . '?' . http_build_query(['days' => $days]) }}" class="btn btn-outline-secondary @if(empty($event)) active @endif">All</a>
                        @foreach($eventOptions as $ev)
                            @php $evCount = (int) (($eventCounts[$ev] ?? 0)); @endphp
                            <a href="{{ $baseRoute . '?' . http_build_query(['days' => $days, 'event' => $ev]) }}"
                               class="btn btn-outline-primary @if(($event ?? null) === $ev) active @endif @if($evCount === 0) disabled @endif"
                               @if($evCount === 0) aria-disabled="true" tabindex="-1" @endif>
                               {{ $ev }}
                               <span class="badge bg-label-secondary ms-1">{{ $evCount }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Timeline ({{ $days }} days)</h5>
                </div>
                <div class="card-body">
                    <div id="events-timeline" style="min-height: 260px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Location Insights</h5>
                </div>
                <div class="card-body">
                    <div class="bg-label-secondary rounded" style="height: 280px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Countries</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Country</th>
                                    <th class="text-end">Visitors</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($topCountries ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->country }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-muted">No data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Cities</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>City</th>
                                    <th class="text-end">Visitors</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($topCities ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->city }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-muted">No data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Logs @if(!empty($event)) - {{ $event }}@endif</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="platform-logs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Visitor</th>
                                    <th>Location</th>
                                    <th>Event</th>
                                    <th>URL</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $log->visitor_id }}</div>
                                        @if(!empty($log->visitor_ip))
                                            <div class="small text-muted">{{ $log->visitor_ip }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">
                                            {{ trim(($log->visitor_city ?? '') . (empty($log->visitor_city) || empty($log->visitor_country) ? '' : ', ') . ($log->visitor_country ?? '')) ?: '-' }}
                                        </div>
                                        @if(!empty($log->visitor_isp))
                                            <div class="small text-muted text-truncate" style="max-width: 240px;">{{ $log->visitor_isp }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $log->event_name }}</td>
                                    <td>
                                        @if(!empty($log->url))
                                            <x-admin.button variant="" size="sm" class="btn-label-secondary view-url" type="button" data-url="{{ $log->url }}" title="View URL">
                                                <i class="icon-base ri ri-external-link-line"></i>
                                            </x-admin.button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->event_time ?? $log->created_at }}</td>
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
@endsection

@section('script')
<script type="module" src="{{ asset('admin/build/apexcharts-DQTXqG1-.js') }}"></script>
<script>
(function() {
    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.DataTable === 'function') {
        window.jQuery(function() {
            window.jQuery('#platform-logs-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });
    }
})();
</script>

<script>
(function() {
    var labels = @json($timelineLabels ?? []);
    var series = @json($timelineSeries ?? []);

    if (typeof window.ApexCharts === 'function') {
        var el = document.querySelector('#events-timeline');
        if (el) {
            var options = {
                chart: {
                    type: 'area',
                    height: 260,
                    toolbar: { show: false }
                },
                series: [{
                    name: 'Events',
                    data: series
                }],
                xaxis: {
                    categories: labels
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient' }
            };

            try {
                (new ApexCharts(el, options)).render();
            } catch (e) {
                // ignore
            }
        }
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.view-url');
        if (!btn) return;
        e.preventDefault();
        var url = btn.getAttribute('data-url') || '';
        if (!url) return;
        if (window.Swal && window.Swal.fire) {
            window.Swal.fire({
                title: 'URL',
                text: url,
                icon: 'info',
                confirmButtonText: 'OK'
            });
        } else {
            alert(url);
        }
    });
})();
</script>
@endsection
