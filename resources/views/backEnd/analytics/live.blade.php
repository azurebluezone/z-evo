@extends('backEnd.layouts.app')
@section('title', 'Live now — Real-time Analytics')

@section('css')
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-analytics-live.css') }}">
@endsection

@section('content')
@php
    $channelBadge = [
        'facebook' => 'primary', 'google' => 'info', 'tiktok' => 'dark',
        'organic' => 'success', 'direct' => 'secondary', 'referral' => 'warning',
        'unknown' => 'secondary',
    ];
    $delta = fn ($value) => $value > 0 ? 'up' : ($value < 0 ? 'down' : 'flat');
@endphp

<!-- ═══════════════ HEADER ═══════════════ -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 d-flex align-items-center gap-2">
            <span class="live-dot" aria-hidden="true"></span>
            Live now
        </h4>
        <p class="mb-0 text-body-secondary">
            Activity from the last <strong>{{ $window_minutes }} minutes</strong>.
            Cards compare with the same window one hour earlier.
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="text-body-secondary small live-updated" id="live-updated">
            Updated {{ now()->format('H:i:s') }}
        </span>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="live-auto" checked>
            <label class="form-check-label small" for="live-auto">Auto-refresh</label>
        </div>
        <button class="btn btn-sm btn-outline-primary" type="button" id="live-refresh">
            <i class="icon-base ri ri-refresh-line me-1" aria-hidden="true"></i>Refresh
        </button>
    </div>
</div>

<!-- ═══════════════ FILTERS ═══════════════ -->
<form method="GET" action="{{ route('admin.analytics.live') }}" class="card mb-4" id="live-filter-form">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1" for="f-preset">Range</label>
                <select name="preset" id="f-preset" class="form-select form-select-sm">
                    @foreach($presets as $p)
                        <option value="{{ $p }}" @selected($preset === $p)>{{ ucfirst(str_replace('last', 'Last ', $p)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1" for="f-channel">Channel</label>
                <select name="channel" id="f-channel" class="form-select form-select-sm">
                    <option value="">All channels</option>
                    @foreach($channels as $c)
                        <option value="{{ $c }}" @selected($channel === $c)>{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1" for="f-division">Division</label>
                <select name="division" id="f-division" class="form-select form-select-sm">
                    <option value="">All divisions</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d }}" @selected($division === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1" for="f-method">Tracking</label>
                <select name="method" id="f-method" class="form-select form-select-sm">
                    <option value="">Browser + server</option>
                    <option value="browser" @selected($method === 'browser')>Browser only</option>
                    <option value="server" @selected($method === 'server')>Server only</option>
                    <option value="hybrid" @selected($method === 'hybrid')>Deduplicated</option>
                </select>
            </div>
            <div class="col-12 col-xl-2">
                <button class="btn btn-sm btn-primary w-100" type="submit">Apply</button>
            </div>
            <div class="col-12 col-xl-2">
                <a href="{{ route('admin.analytics.live') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </div>
</form>

<!-- ═══════════════ STAT CARDS ═══════════════ -->
<div class="row g-4 mb-4">
    <!-- Visitors now -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-primary">
                        <i class="icon-base ri ri-group-line icon-24px" aria-hidden="true"></i>
                    </span>
                    <span class="badge {{ $stats['visitors_delta'] > 0 ? 'bg-label-success' : ($stats['visitors_delta'] < 0 ? 'bg-label-danger' : 'bg-label-secondary') }}" id="stat-visitors-delta-badge">
                        {{ $stats['visitors_delta'] > 0 ? '+' : '' }}{{ $stats['visitors_delta'] }}%
                    </span>
                </div>
                <h3 class="mb-0" id="stat-visitors">{{ number_format($stats['visitors_now']) }}</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Visitors now</h6>
                <p class="mb-0 mt-2 stat-delta text-body-secondary">
                    <span id="stat-visitors-prev">{{ number_format($stats['visitors_prev']) }}</span>
                    in the same window an hour ago
                </p>
            </div>
        </div>
    </div>

    <!-- Events per minute -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-info">
                        <i class="icon-base ri ri-pulse-line icon-24px" aria-hidden="true"></i>
                    </span>
                    <span class="badge {{ $stats['events_delta'] > 0 ? 'bg-label-success' : ($stats['events_delta'] < 0 ? 'bg-label-danger' : 'bg-label-secondary') }}" id="stat-events-delta-badge">
                        {{ $stats['events_delta'] > 0 ? '+' : '' }}{{ $stats['events_delta'] }}%
                    </span>
                </div>
                <h3 class="mb-0" id="stat-events-per-min">{{ number_format($stats['events_per_min'], 1) }}</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Events / minute</h6>
                <p class="mb-0 mt-2 stat-delta text-body-secondary">
                    <span id="stat-events">{{ number_format($stats['events_now']) }}</span> events in
                    {{ $window_minutes }} min
                </p>
            </div>
        </div>
    </div>

    <!-- Top page -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-success">
                        <i class="icon-base ri ri-file-list-3-line icon-24px" aria-hidden="true"></i>
                    </span>
                    <span class="badge bg-label-secondary" id="stat-page-share">
                        {{ $stats['top_page'] ? $stats['top_page']['share'] . '%' : '—' }}
                    </span>
                </div>
                <h3 class="mb-0 text-truncate" id="stat-page" title="{{ $stats['top_page']['path'] ?? '' }}">
                    {{ $stats['top_page']['path'] ?? '—' }}
                </h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Top page</h6>
                <p class="mb-0 mt-2 stat-delta text-body-secondary">
                    <span id="stat-page-events">{{ $stats['top_page'] ? number_format($stats['top_page']['events']) : '0' }}</span>
                    events
                </p>
            </div>
        </div>
    </div>

    <!-- Top channel -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-warning">
                        <i class="icon-base ri ri-share-forward-line icon-24px" aria-hidden="true"></i>
                    </span>
                    <span class="badge bg-label-secondary" id="stat-channel-share">
                        {{ $stats['top_channel'] ? $stats['top_channel']['share'] . '%' : '—' }}
                    </span>
                </div>
                <h3 class="mb-0" id="stat-channel">{{ $stats['top_channel']['label'] ?? '—' }}</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Top channel</h6>
                <p class="mb-0 mt-2 stat-delta text-body-secondary">
                    <span id="stat-channel-visitors">{{ $stats['top_channel'] ? number_format($stats['top_channel']['visitors']) : '0' }}</span>
                    visitors
                </p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-danger d-none" role="alert" id="live-error">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>
            <i class="icon-base ri ri-error-warning-line me-1" aria-hidden="true"></i>
            Could not refresh the feed. The numbers below are from
            <span id="live-error-time">the last successful load</span>.
        </span>
        <button class="btn btn-sm btn-danger" type="button" id="live-error-retry">Retry</button>
    </div>
</div>

<div class="row g-4">
    <!-- ═══════════ TABLE ═══════════ -->
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0">Live feed</h5>
                    <small class="text-body-secondary">Newest first · pauses while you hover</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-secondary" id="live-row-count">{{ count($rows) }} rows</span>
                    <span class="badge bg-label-secondary d-none" id="live-paused-badge">paused</span>
                    <span class="badge bg-label-secondary d-none" id="live-division-badge">
                        <span id="live-division-label"></span>
                        <button type="button" class="btn-close btn-close-sm ms-1" id="live-division-clear"
                                aria-label="Clear division filter" style="font-size:.5rem"></button>
                    </span>
                </div>
            </div>
            <div class="table-responsive live-table-wrap">
                <table class="table table-hover live-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Time</th>
                            <th scope="col">Visitor</th>
                            <th scope="col">Page</th>
                            <th scope="col">Channel</th>
                            <th scope="col">Device</th>
                            <th scope="col">ISP</th>
                            <th scope="col">Event</th>
                        </tr>
                    </thead>
                    <tbody id="live-rows">
                        @forelse($rows as $row)
                            <tr data-division="{{ $row['division'] }}">
                                <td class="text-nowrap">
                                    <span title="{{ $row['time_ago'] }} · {{ $row['event_time'] }}">{{ $row['time_hms'] }}</span>
                                </td>
                                <td>
                                    <a class="live-visitor" href="{{ route('admin.analytics.visitor', $row['visitor_id']) }}"
                                       title="{{ $row['visitor_id'] }}">{{ $row['short_uuid'] }}</a>
                                </td>
                                <td>
                                    <div class="live-page" title="{{ $row['page_path'] }}">{{ $row['page_path'] ?: '—' }}</div>
                                </td>
                                <td>
                                    @if($row['channel'])
                                        <span class="badge bg-label-{{ $channelBadge[$row['channel']] ?? 'secondary' }}">
                                            {{ $row['channel_label'] }}
                                        </span>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td class="text-body-secondary">
                                    <div class="live-device" title="{{ $row['device'] }}">{{ $row['device'] ?: '—' }}</div>
                                </td>
                                <td class="text-body-secondary">
                                    <div class="live-isp" title="{{ $row['isp'] }}">{{ $row['isp'] ?: '—' }}</div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge bg-label-dark">{{ $row['event'] }}</span>
                                    @if($row['tracking_method'])
                                        <span class="live-method text-body-secondary">{{ $row['tracking_method'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="live-empty-row">
                                <td colspan="7" class="text-center py-5">
                                    <span class="avatar-initial rounded-circle bg-label-secondary mx-auto mb-2 d-grid"
                                          style="width:3rem;height:3rem">
                                        <i class="icon-base ri ri-radar-line icon-24px" aria-hidden="true"></i>
                                    </span>
                                    <div class="fw-medium">No visitors in the last {{ $window_minutes }} minutes.</div>
                                    <div class="text-body-secondary small">
                                        Widen the range above, or wait — this table refreshes automatically.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════ MAP ═══════════ -->
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0">Visitors by division</h5>
                    <small class="text-body-secondary" id="map-scope">
                        @if($map['scope'] === 'live')
                            Last {{ $window_minutes }} minutes
                        @else
                            No live visitors — showing today
                        @endif
                    </small>
                </div>
                <span class="badge bg-label-primary" id="map-total">{{ number_format($map['total']) }}</span>
            </div>
            <div class="card-body">
                {{-- The map is only shown when there is something to plot; an
                     empty basemap plus an empty-state box would say the same
                     thing twice. --}}
                <div id="live-map" class="live-map {{ empty($map['points']) ? 'd-none' : '' }}" role="img"
                     aria-label="Map of Bangladesh showing live visitors per division"></div>
                <div id="live-map-empty" class="live-map-empty {{ empty($map['points']) ? '' : 'd-none' }}">
                    <div>
                        <i class="icon-base ri ri-map-2-line icon-24px text-body-secondary" aria-hidden="true"></i>
                        <div class="fw-medium mt-1">No location data yet</div>
                        <div class="small text-body-secondary">Visitors appear once their IP resolves to a division.</div>
                    </div>
                </div>

                {{-- Keyboard/screen-reader path: the same filter the circles apply. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" id="division-chips">
                    @foreach($map['points'] as $point)
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary division-chip"
                                data-division="{{ $point['division'] }}"
                                aria-pressed="false">
                            {{ $point['division'] }}
                            <span class="badge bg-label-primary ms-1">{{ $point['visitors'] }}</span>
                        </button>
                    @endforeach
                </div>
                @if(empty($map['points']))
                    <p class="small text-body-secondary mb-0">
                        Location is resolved from the visitor IP; unknown IPs are not plotted.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('admin/assets/vendor/libs/leaflet/leaflet.js') }}"></script>
<div class="d-none" data-live-config="{{ json_encode(['url' => route('admin.analytics.live') . '?' . http_build_query($query),'interval' => 10000,'channelBadge' => $channelBadge,'visitorBase' => url('admin/analytics/visitor'),'windowMinutes' => (int) $window_minutes]) }}"></div>
<script src="{{ asset('admin/assets/js/admin-page-analytics-live.js') }}"></script>
@endsection
