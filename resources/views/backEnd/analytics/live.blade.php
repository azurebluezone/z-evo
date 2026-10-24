@extends('backEnd.layouts.app')
@section('title', 'Live now — Real-time Analytics')

@section('css')
<style>
    /* Live-now page. Only page-local layout glue; every colour, card and badge
       comes from the admin design system so this page cannot drift from it. */
    .live-dot {
        width: .5rem; height: .5rem; border-radius: 50%;
        background: #28a745; display: inline-block;
        box-shadow: 0 0 0 0 rgba(40, 167, 69, .55);
        animation: live-pulse 2s infinite;
    }
    @keyframes live-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(40, 167, 69, .5); }
        70%  { box-shadow: 0 0 0 .55rem rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }
    .live-table-wrap { max-height: 34rem; overflow: auto; }
    .live-table thead th {
        position: sticky; top: 0; z-index: 2;
        background: var(--bs-card-bg, #fff);
        font-size: .72rem; letter-spacing: .04em; text-transform: uppercase;
        color: var(--bs-secondary-color);
    }
    .live-table tbody tr { transition: background-color .2s ease; }
    .live-table tbody tr.is-new { animation: row-in .6s ease; }
    @keyframes row-in { from { background-color: rgba(40, 167, 69, .12); } to { background-color: transparent; } }
    .live-table { font-size: .82rem; }
    .live-table td, .live-table th { vertical-align: middle; padding: .45rem .5rem; }
    .live-visitor { font-family: var(--bs-font-monospace); font-size: .78rem; }
    .live-page { max-width: 10rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .live-isp { max-width: 7.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .live-device { max-width: 7.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .live-method { display: block; font-size: .7rem; line-height: 1.1; }
    .live-map { height: 320px; border-radius: .5rem; z-index: 1; }
    .live-map-empty {
        height: 320px; display: grid; place-items: center; text-align: center;
        border: 1px dashed var(--bs-border-color); border-radius: .5rem;
    }
    .division-chip[aria-pressed="true"] { outline: 2px solid var(--bs-primary); outline-offset: 1px; }
    .stat-delta { font-size: .78rem; }
    .live-updated { font-variant-numeric: tabular-nums; }
    @media (max-width: 575.98px) {
        .live-page { max-width: 8rem; }
        .live-isp, .live-device { max-width: 6rem; }
        .live-table-wrap { max-height: 26rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .live-dot, .live-table tbody tr.is-new { animation: none; }
    }
</style>
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
<script>
    /**
     * Live-now page behaviour.
     *
     * One endpoint, one payload (the same JSON the dashboard polls), so the
     * cards, the table and the map can never show different numbers. Rows are
     * built with createElement + textContent — scraped or user-supplied page
     * paths must never be able to inject markup into the admin.
     */
    (function () {
        'use strict';

        var CFG = {
            url: @json(route('admin.analytics.live') . '?' . http_build_query($query)),
            interval: 10000,
            channelBadge: @json($channelBadge)
        };

        var tbody = document.getElementById('live-rows');
        var autoBox = document.getElementById('live-auto');
        var pausedBadge = document.getElementById('live-paused-badge');
        var errorBox = document.getElementById('live-error');
        var updatedEl = document.getElementById('live-updated');
        var divisionBadge = document.getElementById('live-division-badge');
        var divisionLabel = document.getElementById('live-division-label');

        var timer = null;
        var paused = false;
        var hovering = false;
        var divisionFilter = null;
        var lastOk = null;
        var map = null;
        var layer = null;

        function el(tag, className, text) {
            var node = document.createElement(tag);
            if (className) { node.className = className; }
            if (text !== undefined && text !== null) { node.textContent = String(text); }
            return node;
        }

        function fmt(value) {
            var n = Number(value) || 0;
            return n.toLocaleString('en-US');
        }

        function setBadge(node, value) {
            if (!node) { return; }
            node.textContent = (value > 0 ? '+' : '') + value + '%';
            node.className = 'badge ' + (value > 0 ? 'bg-label-success'
                : value < 0 ? 'bg-label-danger' : 'bg-label-secondary');
        }

        function updateStats(stats) {
            if (!stats) { return; }

            document.getElementById('stat-visitors').textContent = fmt(stats.visitors_now);
            document.getElementById('stat-visitors-prev').textContent = fmt(stats.visitors_prev);
            setBadge(document.getElementById('stat-visitors-delta-badge'), stats.visitors_delta);

            document.getElementById('stat-events-per-min').textContent = Number(stats.events_per_min).toFixed(1);
            document.getElementById('stat-events').textContent = fmt(stats.events_now);
            setBadge(document.getElementById('stat-events-delta-badge'), stats.events_delta);

            var page = stats.top_page;
            document.getElementById('stat-page').textContent = page ? page.path : '—';
            document.getElementById('stat-page').title = page ? page.path : '';
            document.getElementById('stat-page-events').textContent = page ? fmt(page.events) : '0';
            document.getElementById('stat-page-share').textContent = page ? page.share + '%' : '—';

            var channel = stats.top_channel;
            document.getElementById('stat-channel').textContent = channel ? channel.label : '—';
            document.getElementById('stat-channel-visitors').textContent = channel ? fmt(channel.visitors) : '0';
            document.getElementById('stat-channel-share').textContent = channel ? channel.share + '%' : '—';
        }

        function buildRow(row) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-division', row.division || '');

            var tdTime = el('td', 'text-nowrap');
            var time = el('span', null, row.time_hms || '');
            time.title = (row.time_ago ? row.time_ago + ' · ' : '') + (row.event_time || '');
            tdTime.appendChild(time);

            var tdVisitor = el('td');
            var link = el('a', 'live-visitor', row.short_uuid);
            link.href = @json(url('admin/analytics/visitor')) + '/' + encodeURIComponent(row.visitor_id);
            link.title = row.visitor_id || '';
            tdVisitor.appendChild(link);

            var tdPage = el('td');
            var page = el('div', 'live-page', row.page_path || '—');
            page.title = row.page_path || '';
            tdPage.appendChild(page);

            var tdChannel = el('td');
            if (row.channel) {
                var tone = CFG.channelBadge[row.channel] || 'secondary';
                tdChannel.appendChild(el('span', 'badge bg-label-' + tone, row.channel_label));
            } else {
                tdChannel.appendChild(el('span', 'text-body-secondary', '—'));
            }

            var tdDevice = el('td', 'text-body-secondary');
            var device = el('div', 'live-device', row.device || '—');
            device.title = row.device || '';
            tdDevice.appendChild(device);

            var tdIsp = el('td', 'text-body-secondary');
            var isp = el('div', 'live-isp', row.isp || '—');
            isp.title = row.isp || '';
            tdIsp.appendChild(isp);

            var tdEvent = el('td', 'text-nowrap');
            tdEvent.appendChild(el('span', 'badge bg-label-dark', row.event));
            if (row.tracking_method) {
                tdEvent.appendChild(el('span', 'live-method text-body-secondary', row.tracking_method));
            }

            tr.appendChild(tdTime);
            tr.appendChild(tdVisitor);
            tr.appendChild(tdPage);
            tr.appendChild(tdChannel);
            tr.appendChild(tdDevice);
            tr.appendChild(tdIsp);
            tr.appendChild(tdEvent);

            return tr;
        }

        function emptyRow(message) {
            var tr = document.createElement('tr');
            tr.id = 'live-empty-row';
            var td = document.createElement('td');
            td.colSpan = 7;
            td.className = 'text-center py-5';
            td.appendChild(el('div', 'fw-medium', message));
            td.appendChild(el('div', 'text-body-secondary small',
                'Widen the range above, or wait — this table refreshes automatically.'));
            tr.appendChild(td);
            return tr;
        }

        function renderRows(rows) {
            var fragment = document.createDocumentFragment();

            if (!rows || !rows.length) {
                fragment.appendChild(emptyRow('No visitors in the last ' + CFG.windowMinutes + ' minutes.'));
            } else {
                rows.forEach(function (row) { fragment.appendChild(buildRow(row)); });
            }

            tbody.textContent = '';
            tbody.appendChild(fragment);
            document.getElementById('live-row-count').textContent = (rows ? rows.length : 0) + ' rows';

            applyDivisionFilter();
        }

        function applyDivisionFilter() {
            var shown = 0;

            Array.prototype.forEach.call(tbody.querySelectorAll('tr[data-division]'), function (tr) {
                var match = !divisionFilter || tr.getAttribute('data-division') === divisionFilter;
                tr.classList.toggle('d-none', !match);
                if (match) { shown++; }
            });

            if (divisionFilter) {
                divisionBadge.classList.remove('d-none');
                divisionLabel.textContent = divisionFilter;
                document.getElementById('live-row-count').textContent = shown + ' rows';
            } else {
                divisionBadge.classList.add('d-none');
            }

            Array.prototype.forEach.call(document.querySelectorAll('.division-chip'), function (chip) {
                chip.setAttribute('aria-pressed', chip.getAttribute('data-division') === divisionFilter ? 'true' : 'false');
            });
        }

        function setDivision(value) {
            divisionFilter = (divisionFilter === value) ? null : value;
            applyDivisionFilter();
        }

        // ---------------------------------------------------------------- map
        function initMap() {
            var node = document.getElementById('live-map');

            if (!node || typeof window.L === 'undefined' || map) {
                return;
            }

            map = L.map(node, { scrollWheelZoom: false, attributionControl: true })
                .setView([23.7, 90.35], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 12,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);

            layer = L.layerGroup().addTo(map);
        }

        function drawMap(payload) {
            var data = payload.map;

            if (!data) { return; }

            document.getElementById('map-total').textContent = fmt(data.total);
            document.getElementById('map-scope').textContent = data.scope === 'live'
                ? 'Last ' + CFG.windowMinutes + ' minutes'
                : 'No live visitors — showing today';

            var hasPoints = data.points.length > 0;

            document.getElementById('live-map').classList.toggle('d-none', !hasPoints);
            document.getElementById('live-map-empty').classList.toggle('d-none', hasPoints);

            // Chips: rebuilt every poll so the counts stay truthful.
            var chipHost = document.getElementById('division-chips');
            var chips = document.createDocumentFragment();

            data.points.forEach(function (point) {
                var chip = el('button', 'btn btn-sm btn-outline-secondary division-chip');
                chip.type = 'button';
                chip.setAttribute('data-division', point.division);
                chip.setAttribute('aria-pressed', divisionFilter === point.division ? 'true' : 'false');
                chip.appendChild(document.createTextNode(point.division + ' '));
                chip.appendChild(el('span', 'badge bg-label-primary ms-1', fmt(point.visitors)));
                chip.addEventListener('click', function () { setDivision(point.division); });
                chips.appendChild(chip);
            });

            chipHost.textContent = '';
            chipHost.appendChild(chips);

            if (!hasPoints) { return; }

            initMap();

            if (!map || !layer) { return; }

            // The container was display:none until now, so Leaflet measured a
            // zero-size box on creation.
            map.invalidateSize();
            layer.clearLayers();

            var max = data.max || 1;

            data.points.forEach(function (point) {
                // Pixel radius, not metres: the map answers "which division is
                // busy", so the dot must stay readable at every zoom level.
                L.circleMarker([point.lat, point.lng], {
                    radius: 8 + 18 * Math.sqrt(point.visitors / max),
                    color: '#0d6efd',
                    weight: 2,
                    fillColor: '#0d6efd',
                    fillOpacity: 0.22 + 0.4 * (point.visitors / max)
                })
                    .bindTooltip(point.division + ' — ' + fmt(point.visitors) + ' visitors')
                    .on('click', function () { setDivision(point.division); })
                    .addTo(layer);
            });
        }

        // ------------------------------------------------------------- polling
        function setPausedUI() {
            var isPaused = paused || hovering;
            pausedBadge.classList.toggle('d-none', !isPaused);
        }

        function refresh() {
            if (paused || hovering || document.hidden) {
                setPausedUI();
                return;
            }

            fetch(CFG.url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    errorBox.classList.add('d-none');

                    if (payload.stats) {
                        CFG.windowMinutes = payload.stats.window_minutes || 5;
                    }

                    updateStats(payload.stats);
                    renderRows(payload.rows);
                    drawMap(payload);

                    lastOk = new Date();
                    updatedEl.textContent = 'Updated ' + lastOk.toLocaleTimeString();
                })
                .catch(function (error) {
                    errorBox.classList.remove('d-none');
                    document.getElementById('live-error-time').textContent = lastOk
                        ? lastOk.toLocaleTimeString()
                        : 'the last successful load';
                    // Keep the last good data on screen; never blank the table.
                    console.error('live refresh failed:', error.message);
                });
        }

        function start() {
            if (timer) { clearInterval(timer); }
            timer = setInterval(refresh, CFG.interval);
        }

        // ------------------------------------------------------------- wiring
        document.getElementById('live-refresh').addEventListener('click', refresh);
        document.getElementById('live-error-retry').addEventListener('click', refresh);

        autoBox.addEventListener('change', function () {
            paused = !autoBox.checked;
            setPausedUI();
            if (!paused) { refresh(); }
        });

        tbody.addEventListener('mouseenter', function () {
            hovering = true;
            setPausedUI();
        });

        tbody.addEventListener('mouseleave', function () {
            hovering = false;
            setPausedUI();
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) { refresh(); }
        });

        document.getElementById('live-division-clear').addEventListener('click', function () {
            divisionFilter = null;
            applyDivisionFilter();
        });

        Array.prototype.forEach.call(document.querySelectorAll('.division-chip'), function (chip) {
            chip.addEventListener('click', function () { setDivision(chip.getAttribute('data-division')); });
        });

        CFG.windowMinutes = {{ (int) $window_minutes }};
        if (document.querySelectorAll('.division-chip').length) {
            initMap();
            if (map) { map.invalidateSize(); }
        }
        applyDivisionFilter();
        start();
    })();
</script>
@endsection
