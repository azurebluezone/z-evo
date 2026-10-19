@extends('backEnd.layouts.app')
@section('title', 'Real-time Analytics & Attribution')

@section('content')

@php
    /**
     * Every percentage shown on this page comes from the controller, never from
     * arithmetic in the template. Keeping the view dumb is what guarantees the
     * cards, charts and tables can never drift apart.
     */
    $hasGeo = !empty($location['divisions']);
    $channelPalette = ['facebook' => '#3b5998', 'google' => '#4285f4', 'tiktok' => '#69c9d0',
                       'organic' => '#28a745', 'direct' => '#8993a4', 'referral' => '#fd7e14',
                       'unknown' => '#c3cbd4'];
    $methodPalette = ['browser' => '#3b82f6', 'server' => '#10b981'];
@endphp

<!-- ═══════════════ 1. GLOBAL FILTER BAR ═══════════════ -->
<form method="GET" action="{{ route('admin.analytics.index') }}" id="analytics-filter-form" class="card rounded-0 mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <!-- Date range -->
            <div class="col-12 col-xl-3">
                <label class="form-label small text-body-secondary mb-1">Date Range</label>
                <select name="preset" class="form-select form-select-sm" id="preset-select">
                    @foreach($presets as $p)
                        <option value="{{ $p }}" @if($preset === $p) selected @endif>
                            {{ ucfirst(str_replace('last', 'Last ', $p)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Custom range (only shown for preset=custom) -->
            <div class="col-6 col-xl-2 d-none" id="custom-from-wrap">
                <label class="form-label small text-body-secondary mb-1">From</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-xl-2 d-none" id="custom-to-wrap">
                <label class="form-label small text-body-secondary mb-1">To</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
            </div>

            <!-- Traffic channel -->
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1">Traffic Channel</label>
                <select name="channel" class="form-select form-select-sm">
                    <option value="">All Channels</option>
                    @foreach($channels as $c)
                        <option value="{{ $c }}" @if($channel === $c) selected @endif>{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tracking method -->
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1">Tracking Method</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">All (Browser + Server)</option>
                    <option value="browser" @if($method === 'browser') selected @endif>Browser Only</option>
                    <option value="server" @if($method === 'server') selected @endif>Server-Side Only</option>
                    <option value="hybrid" @if($method === 'hybrid') selected @endif>Hybrid (Deduplicated)</option>
                </select>
            </div>

            <!-- Division -->
            <div class="col-6 col-xl-2">
                <label class="form-label small text-body-secondary mb-1">Division</label>
                <select name="division" class="form-select form-select-sm">
                    <option value="">All Divisions</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d }}" @if($division === $d) selected @endif>{{ $d }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="col-6 col-xl-3">
                <label class="form-label small text-body-secondary mb-1">Search UUID / IP</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="usr id or IP">
                    <button class="btn btn-primary" type="submit"><i class="ri ri-search-line"></i></button>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge bg-label-secondary">
                    {{ $from->format('d M Y') }} → {{ $to->format('d M Y') }}
                </span>
                @if($channel)<span class="badge bg-label-primary">{{ ucfirst($channel) }}</span>@endif
                @if($method)<span class="badge bg-label-info">{{ ucfirst($method) }}</span>@endif
                @if($division)<span class="badge bg-label-success">{{ $division }}</span>@endif
                @if($search)<span class="badge bg-label-warning">Search: {{ $search }}</span>@endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.analytics.index', ['preset' => 'today']) }}" class="btn btn-sm btn-outline-secondary">Today</a>
                <a href="{{ route('admin.analytics.index', ['preset' => 'yesterday']) }}" class="btn btn-sm btn-outline-secondary">Yesterday</a>
                <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </div>
</form>

{{-- Keeps the custom date inputs in the URL when the Custom preset is active. --}}
@if($preset === 'custom')
    <script>
        document.getElementById('custom-from-wrap').classList.remove('d-none');
        document.getElementById('custom-to-wrap').classList.remove('d-none');
    </script>
@endif

<!-- ═══════════════ 2. EXECUTIVE KPI CARDS ═══════════════ -->
<div class="row g-3 mb-3">
    <!-- Total visitors + pageviews -->
    <div class="col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-primary"><i class="ri ri-group-line icon-24px"></i></span>
                    @if($kpi['vs_previous_visitors'] !== null)
                        <span class="badge {{ $kpi['vs_previous_visitors'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                            {{ $kpi['vs_previous_visitors'] >= 0 ? '+' : '' }}{{ $kpi['vs_previous_visitors'] }}%
                        </span>
                    @endif
                </div>
                <h3 class="mb-0">{{ number_format($kpi['total_visitors']) }}</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Total Visitors</h6>
                <hr class="my-2">
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Pageviews</span>
                    <span class="fw-medium">{{ number_format($kpi['pageviews']) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Events</span>
                    <span class="fw-medium">{{ number_format($kpi['events']) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Live now</span>
                    <span class="fw-medium text-success">{{ number_format($kpi['live_visitors']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top division -->
    <div class="col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-success"><i class="ri ri-map-pin-line icon-24px"></i></span>
                    @if($location['unknown_geo'] > 0)
                        <span class="badge bg-label-secondary" title="Visitors whose IP could not be resolved">
                            {{ number_format($location['unknown_geo']) }} unresolved
                        </span>
                    @endif
                </div>
                <h3 class="mb-0">{{ $kpi['top_division'] ?? '—' }}</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Top Division</h6>
                <hr class="my-2">
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Visitors</span>
                    <span class="fw-medium">{{ number_format($kpi['top_division_count']) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Share</span>
                    <span class="fw-medium">{{ $kpi['top_division_pct'] }}%</span>
                </div>
                <div class="progress mt-2" style="height:6px">
                    <div class="progress-bar bg-success" style="width:{{ max($kpi['top_division_pct'], 0) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top channel -->
    <div class="col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-primary"><i class="ri ri-share-forward-line icon-24px"></i></span>
                </div>
                <h3 class="mb-0">{{ $kpi['top_channel'] ? ucfirst($kpi['top_channel']) : '—' }}</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Top Referral Channel</h6>
                <hr class="my-2">
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Visitors</span>
                    <span class="fw-medium">{{ number_format($kpi['top_channel_count']) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Share</span>
                    <span class="fw-medium">{{ $kpi['top_channel_pct'] }}%</span>
                </div>
                <div class="progress mt-2" style="height:6px">
                    <div class="progress-bar" style="background:{{ $channelPalette[$kpi['top_channel']] ?? '#8993a4' }};width:{{ max($kpi['top_channel_pct'], 0) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server vs browser -->
    <div class="col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-initial rounded-3 bg-label-info"><i class="ri ri-server-line icon-24px"></i></span>
                    <span class="badge bg-label-secondary">dedup {{ $attribution['dedup']['dedup_pct'] }}%</span>
                </div>
                <h3 class="mb-0">{{ $kpi['server_pct'] }}%</h3>
                <h6 class="mb-0 fw-normal text-body-secondary">Server CAPI Share</h6>
                <hr class="my-2">
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Browser pixel</span>
                    <span class="fw-medium">{{ number_format($kpi['browser_events']) }} ({{ $kpi['browser_pct'] }}%)</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-body-secondary">Server CAPI</span>
                    <span class="fw-medium">{{ number_format($kpi['server_events']) }} ({{ $kpi['server_pct'] }}%)</span>
                </div>
                <div class="progress mt-2" style="height:6px">
                    <div class="progress-bar bg-info" style="width:{{ max($kpi['server_pct'], 0) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ TREND ═══════════════ -->
<div class="card rounded-0 mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Traffic Trend ({{ ucfirst($trend['granularity']) }}ly)</h5>
        <div class="d-flex gap-3 small">
            <span><i class="ri-circle-fill text-primary me-1"></i>Events</span>
            <span><i class="ri-circle-fill text-success me-1"></i>Visitors</span>
            <span><i class="ri-circle-fill text-info me-1"></i>Server-side</span>
        </div>
    </div>
    <div class="card-body">
        <div id="analytics-trend" style="min-height:300px"></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- ═══════════════ 3. LOCATION & ISP ═══════════════ -->
    <div class="col-12 col-xl-7">
        <div class="card rounded-0 h-100">
            <div class="card-header">
                <h5 class="mb-0">Location Breakdown</h5>
                <small class="text-body-secondary">Division heat grid — tile intensity is proportional to visitor share</small>
            </div>
            <div class="card-body">
                @if(!$hasGeo)
                    <div class="alert alert-warning mb-0">
                        <i class="ri ri-alert-line me-1"></i>
                        No geo data resolved for this range yet. Geo-IP fills in as new traffic arrives from
                        public IPs (localhost and private ranges are never resolvable).
                    </div>
                @else
                    {{-- Schematic Bangladesh grid: divisions placed in their real relative
                         geographic positions. This is a heat grid, not a boundary map —
                         accurate SVG boundaries need a GeoJSON source, tracked separately. --}}
                    <div class="bd-heat-grid mb-3" id="bd-heat-grid">
                        @foreach(['Rajshahi','Mymensingh','Sylhet','Dhaka','Chattogram','Rangpur','Khulna','Barishal'] as $divKey)
                            @php
                                $row = collect($location['divisions'])->firstWhere('name', $divKey);
                                $pct = $row['pct'] ?? 0;
                                $total = $row['total'] ?? 0;
                                // Opacity ramp is capped so the leader does not flatten the rest.
                                $alpha = $pct > 0 ? min(0.15 + ($pct / 100) * 1.6, 1) : 0.04;
                            @endphp
                            <a href="{{ route('admin.analytics.index', array_merge($query, ['division' => $divKey, 'page' => null])) }}"
                               class="bd-tile bd-{{ strtolower(substr($divKey,0,2)) }}"
                               style="--heat:{{ number_format($alpha, 3) }}"
                               title="{{ $divKey }} — {{ number_format($total) }} visitors ({{ $pct }}%)">
                                <span class="bd-tile-name">{{ $divKey }}</span>
                                <span class="bd-tile-val">{{ number_format($total) }}</span>
                                <span class="bd-tile-pct">{{ $pct }}%</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Top Cities / Districts</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 align-middle">
                                <tbody>
                                @forelse($location['districts'] as $d)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $d['name'] }}</div>
                                            @if($d['division'])<div class="small text-body-secondary">{{ $d['division'] }}</div>@endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.analytics.index', array_merge($query, ['search' => ''])) }}"
                                               class="text-decoration-none">{{ number_format($d['total']) }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-body-secondary small">No city data resolved.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Top ISPs</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 align-middle">
                                <tbody>
                                @forelse($location['isps'] as $isp)
                                    <tr>
                                        <td class="fw-medium">{{ $isp['name'] }}</td>
                                        <td style="width:45%">
                                            <div class="progress" style="height:6px">
                                                <div class="progress-bar bg-label-primary" style="width:{{ min($isp['pct'] * 2, 100) }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-end small">
                                            {{ number_format($isp['total']) }}
                                            <span class="text-body-secondary">({{ $isp['pct'] }}%)</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-body-secondary small">No ISP data resolved.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2 mt-3">Devices</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse($location['devices'] as $d)
                                <span class="badge bg-label-secondary">{{ ucfirst($d['name']) }} · {{ number_format($d['total']) }}</span>
                            @empty
                                <span class="badge bg-label-secondary">No device data</span>
                            @endforelse
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach($location['browsers'] as $b)
                                <span class="badge bg-label-info">{{ ucfirst($b['name']) }} · {{ number_format($b['total']) }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ 4. ATTRIBUTION ═══════════════ -->
    <div class="col-12 col-xl-5">
        <div class="card rounded-0 mb-3">
            <div class="card-header">
                <h5 class="mb-0">Channel Distribution</h5>
                <small class="text-body-secondary">Facebook vs Google vs TikTok</small>
            </div>
            <div class="card-body">
                <div id="analytics-channel-donut" style="min-height:280px"></div>
            </div>
        </div>

        <div class="card rounded-0">
            <div class="card-header">
                <h5 class="mb-0">Browser vs Server-Side (CAPI)</h5>
                <small class="text-body-secondary">Same event, two paths — deduplicated on event_id</small>
            </div>
            <div class="card-body">
                <div id="analytics-method-split" style="min-height:150px"></div>

                <div class="row text-center mt-2">
                    <div class="col-6">
                        <div class="text-primary fw-semibold fs-4">{{ number_format($attribution['browser']) }}</div>
                        <div class="small text-body-secondary">Browser events</div>
                    </div>
                    <div class="col-6">
                        <div class="text-success fw-semibold fs-4">{{ number_format($attribution['server']) }}</div>
                        <div class="small text-body-secondary">Server events</div>
                    </div>
                </div>

                <hr>

                <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Deduplication Health</h6>
                <div class="d-flex justify-content-between small">
                    <span>Matched on both paths</span>
                    <span class="fw-medium">{{ number_format($attribution['dedup']['primary']) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Browser only</span>
                    <span class="fw-medium">{{ number_format($attribution['dedup']['browser_only']) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Server only</span>
                    <span class="fw-medium">{{ number_format($attribution['dedup']['server_only']) }}</span>
                </div>

                <hr>

                <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Per-Event Split</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0 align-middle">
                        <thead>
                            <tr class="small text-body-secondary">
                                <th>Event</th>
                                <th class="text-end">Browser</th>
                                <th class="text-end">Server</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($attribution['per_event'] as $row)
                            <tr>
                                <td class="fw-medium">{{ $row['event'] }}</td>
                                <td class="text-end">{{ number_format($row['browser']) }}</td>
                                <td class="text-end">{{ number_format($row['server']) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary small">No events in this range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ 5. JOURNEY / FUNNEL ═══════════════ -->
<div class="card rounded-0 mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">User Journey &amp; Funnel Drop-off</h5>
            <small class="text-body-secondary">
                Built from {{ number_format($journey['sample_size']) }} observed visitor paths
            </small>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-xl-5">
                <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Conversion Funnel</h6>
                @foreach($journey['funnel'] as $step)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $step['label'] }}</span>
                            <span class="text-body-secondary">
                                {{ number_format($step['visitors']) }} · {{ $step['pct'] }}%
                                @if($step['drop_pct'] > 0)
                                    <span class="text-danger">(−{{ $step['drop_pct'] }}%)</span>
                                @endif
                            </span>
                        </div>
                        <div class="progress" style="height:10px">
                            <div class="progress-bar {{ $loop->last ? 'bg-success' : 'bg-primary' }}"
                                 style="width:{{ max($step['pct'], 0) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-12 col-xl-7">
                <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">
                    Observed Flow (Landing ➜ Engagement ➜ Drop-off)
                </h6>
                <div id="journey-flow" class="journey-flow" data-empty="{{ empty($journey['links']) ? '1' : '0' }}">
                    @if(empty($journey['links']))
                        <div class="text-body-secondary small py-4 text-center">
                            Not enough sequential events in this range to draw a flow yet.
                        </div>
                    @else
                        <div class="journey-columns">
                            @php
                                // Group nodes into ordered columns by their rank in the funnel.
                                $nodeRank = [];
                                foreach($journey['funnel'] as $i => $f) {
                                    $nodeRank[$f['label'] === 'Landed' ? 'PageView' : $f['event']] = $i;
                                }
                                $rankOf = function (string $name) use ($nodeRank) {
                                    if (str_starts_with($name, 'Scroll')) return 1;
                                    if (str_starts_with($name, 'Click')) return 2;
                                    if (str_starts_with($name, 'Outbound')) return 99;
                                    return $nodeRank[$name] ?? 50;
                                };

                                $columns = [];
                                foreach($journey['nodes'] as $n) {
                                    $columns[$rankOf($n['name'])][] = $n;
                                }
                                ksort($columns);
                            @endphp

                            @foreach($columns as $rank => $nodesInCol)
                                <div class="journey-column">
                                    @foreach($nodesInCol as $node)
                                        @php $width = max(12, min(100, (int) round(($node['total'] / max(1, $journey['nodes'][0]['total'])) * 100))); @endphp
                                        <div class="journey-node" style="width:{{ $width }}%" title="{{ $node['name'] }} — {{ number_format($node['total']) }}">
                                            <span class="journey-node-name">{{ $node['name'] }}</span>
                                            <span class="journey-node-val">{{ number_format($node['total']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if(!$loop->last)<div class="journey-arrow"><i class="ri ri-arrow-right-line"></i></div>@endif
                            @endforeach
                        </div>

                        <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2 mt-3">Strongest Paths</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                @foreach($journey['links'] as $link)
                                    <tr>
                                        <td class="small">
                                            <span class="badge bg-label-primary me-1">{{ $link['source'] }}</span>
                                            <i class="ri ri-arrow-right-line mx-1 text-body-secondary"></i>
                                            <span class="badge bg-label-success">{{ $link['target'] }}</span>
                                        </td>
                                        <td class="text-end fw-medium">{{ number_format($link['value']) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2 mt-3">Highest Drop-off Steps</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <thead>
                            <tr class="small text-body-secondary">
                                <th>Step</th>
                                <th class="text-end">Entered</th>
                                <th class="text-end">Exited</th>
                                <th class="text-end">Drop-off</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($journey['dropoff'] as $d)
                            <tr>
                                <td>{{ $d['step'] }}</td>
                                <td class="text-end">{{ number_format($d['entered']) }}</td>
                                <td class="text-end">{{ number_format($d['exited']) }}</td>
                                <td class="text-end">
                                    <span class="badge {{ $d['drop_pct'] >= 70 ? 'bg-danger' : ($d['drop_pct'] >= 40 ? 'bg-warning' : 'bg-label-secondary') }}">
                                        {{ $d['drop_pct'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary small">No journey steps recorded.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ 6. TOP PAGES ═══════════════ -->
<div class="card rounded-0 mb-3">
    <div class="card-header"><h5 class="mb-0">Top Tracked Pages</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-borderless mb-0 align-middle">
                <thead>
                    <tr class="small text-body-secondary">
                        <th>URL</th>
                        <th class="text-end">Hits</th>
                        <th class="text-end">Unique Visitors</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($attribution['top_urls'] as $u)
                    <tr>
                        <td class="text-truncate" style="max-width:600px">{{ $u['url'] }}</td>
                        <td class="text-end">{{ number_format($u['total']) }}</td>
                        <td class="text-end">{{ number_format($u['visitors']) }}</td>
                    </tr>
                @empty
                    <tr><td class="text-body-secondary small">No page events in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════ 7. LIVE VISITOR STREAM ═══════════════ -->
<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">
                Live Visitor Stream &amp; Event Telemetry
                <span class="badge bg-success ms-1" id="live-dot"><i class="ri ri-circle-fill"></i> live</span>
            </h5>
            <small class="text-body-secondary">
                <span id="live-count">{{ $kpi['live_visitors'] }}</span> visitors active in the last 5 minutes ·
                auto-refreshes every <span id="live-interval-label">20</span>s
            </small>
        </div>
        <div class="d-flex gap-2">
            <label class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                <input class="form-check-input" type="checkbox" id="live-auto-refresh" checked>
                <span class="form-check-label small">Auto</span>
            </label>
            <button class="btn btn-sm btn-outline-primary" id="live-refresh-btn" type="button">
                <i class="ri ri-refresh-line"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="live-table">
                <thead>
                    <tr class="small text-body-secondary">
                        <th>UUID</th>
                        <th>IP / ISP</th>
                        <th>Location</th>
                        <th>Device</th>
                        <th>Source</th>
                        <th>Event</th>
                        <th>Path</th>
                        <th>Method</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="live-table-body">
                    @include('backEnd.analytics.partials.live-rows', ['rows' => $live])
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Visitor drill-down -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="visitorDrawer" aria-labelledby="visitorDrawerLabel" style="width:min(560px, 92vw)">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="visitorDrawerLabel">Visitor Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="visitorDrawerBody">
        <div class="text-center py-5 text-body-secondary">Select a visitor row to load their journey.</div>
    </div>
</div>

<style>
    /* Bangladesh division heat grid — approximate relative geography, not boundaries. */
    .bd-heat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .4rem;
        max-width: 520px;
    }
    .bd-tile {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 62px;
        padding: .35rem;
        border-radius: .375rem;
        text-decoration: none;
        color: inherit;
        background: rgba(59, 130, 246, var(--heat, .05));
        border: 1px solid rgba(59, 130, 246, calc(var(--heat, .05) * .6));
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .bd-tile:hover { transform: translateY(-2px); box-shadow: 0 .25rem .75rem rgba(0,0,0,.12); }
    /* Approximate real-world placement on a 4x4 grid. */
    .bd-ra { grid-column: 1; grid-row: 1; }  /* Rajshahi      */
    .bd-my { grid-column: 2; grid-row: 1; }  /* Mymensingh    */
    .bd-sy { grid-column: 3; grid-row: 1; }  /* Sylhet        */
    .bd-ch { grid-column: 4; grid-row: 2; }  /* Chattogram    */
    .bd-dh { grid-column: 2; grid-row: 2; }  /* Dhaka         */
    .bd-ri { grid-column: 1; grid-row: 3; }  /* Rangpur       */
    .bd-kh { grid-column: 2; grid-row: 3; }  /* Khulna        */
    .bd-ba { grid-column: 3; grid-row: 3; }  /* Barishal      */
    .bd-tile-name { font-size: .7rem; font-weight: 600; text-align: center; line-height: 1.1; }
    .bd-tile-val  { font-size: .9rem; font-weight: 700; }
    .bd-tile-pct  { font-size: .65rem; opacity: .75; }

    /* Journey flow — dependency-free layered flow, no charting library needed. */
    .journey-columns {
        display: flex;
        align-items: center;
        gap: .25rem;
        overflow-x: auto;
        padding-bottom: .35rem;
    }
    .journey-column { display: flex; flex-direction: column; gap: .3rem; flex: 0 0 auto; min-width: 92px; }
    .journey-arrow { color: var(--bs-secondary-color, #adb5bd); flex: 0 0 auto; }
    .journey-node {
        display: flex; flex-direction: column; gap: 2px;
        padding: .3rem .45rem;
        border-radius: .3rem;
        background: var(--bs-tertiary-bg, #f6f7f9);
        border-left: 3px solid var(--bs-primary, #3b82f6);
        white-space: nowrap;
    }
    .journey-node-name { font-size: .7rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; }
    .journey-node-val  { font-size: .65rem; opacity: .7; }
</style>

@php
    // @json() is compiled by a regex that stops at the first newline, so a
    // multi-line argument emitted `json_encode([` with a stray `)` and the
    // whole page died with "Unclosed '[' does not match ')'" (a 500).
    // Build the array first, then hand @json a single simple expression.
    $analyticsMethodSplit = [
        ['label' => 'Browser Pixel', 'value' => (int) $attribution['browser']],
        ['label' => 'Server CAPI',   'value' => (int) $attribution['server']],
    ];
@endphp
<script>
    window.__ANALYTICS__ = {
        trend:      @json($trend),
        channels:   @json($attribution['channels']),
        method:     @json($analyticsMethodSplit),
        palette:    @json($channelPalette),
        liveUrl:    @json(route('admin.analytics.live') . '?' . http_build_query($query)),
        visitorBase:@json(url('admin/analytics/visitor')),
        interval:   20000
    };
</script>

@include('backEnd.analytics.partials.scripts')

@endsection