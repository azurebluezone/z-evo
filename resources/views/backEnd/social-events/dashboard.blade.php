@extends('backEnd.layouts.app')
@section('title','Social Events Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title">Social Events Dashboard</h4>
                <form method="GET" action="{{ route('admin.social-events.dashboard') }}" class="d-flex gap-2">
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
                    <h6 class="mb-1">Events ({{ $days }} days)</h6>
                    <h3 class="mb-0">{{ $eventCount }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h6 class="mb-2">Tracking Status</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge {{ ($trackingStatus['facebook']['server'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">FB Server: {{ ($trackingStatus['facebook']['server'] ?? false) ? 'Active' : 'Inactive' }}</span>
                        <span class="badge {{ ($trackingStatus['google']['server'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">GTM Server: {{ ($trackingStatus['google']['server'] ?? false) ? 'Active' : 'Inactive' }}</span>
                        <span class="badge {{ ($trackingStatus['tiktok']['browser'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">TT Browser: {{ ($trackingStatus['tiktok']['browser'] ?? false) ? 'Active' : 'Inactive' }}</span>
                        <span class="badge {{ ($trackingStatus['tiktok']['server'] ?? false) ? 'bg-success' : 'bg-label-secondary' }}">TT Server: {{ ($trackingStatus['tiktok']['server'] ?? false) ? 'Active' : 'Inactive' }}</span>
                        <span class="badge {{ ($trackingStatus['tiktok']['test_mode'] ?? false) ? 'bg-warning' : 'bg-label-secondary' }}">TT Test: {{ ($trackingStatus['tiktok']['test_mode'] ?? false) ? 'ON' : 'OFF' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h6 class="mb-2">Event Activity</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(($allowedEvents ?? []) as $ev)
                            @php $c = (int) (($eventCounts[$ev] ?? 0)); @endphp
                            <span class="badge {{ $c > 0 ? 'bg-success' : 'bg-label-secondary' }}">
                                {{ $ev }}: {{ $c > 0 ? 'Active' : 'Inactive' }} ({{ $c }})
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Overview ({{ $days }} days)</h5>
                </div>
                <div class="card-body">
                    <div id="events-overview" style="min-height: 260px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-3">
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
                                    <td style="white-space: normal; word-break: break-word;">{{ $row->country }}</td>
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

        <div class="col-12 col-xl-3">
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
                                    <td style="white-space: normal; word-break: break-word;">{{ $row->city }}</td>
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
                    <h5 class="mb-0">Top Events</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topEvents as $row)
                                <tr>
                                    <td>{{ $row->event_name }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
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
        </div>

    </div>
</div>
@endsection

@section('script')
<script type="module" src="{{ asset('admin/build/apexcharts-DQTXqG1-.js') }}"></script>
<script>
(function() {
    var labels = @json(array_values($allowedEvents ?? []));
    var dataMap = @json($eventCounts ?? []);
    var series = labels.map(function (k) {
        return Number(dataMap && dataMap[k] ? dataMap[k] : 0);
    });

    if (typeof window.ApexCharts !== 'function') return;
    var el = document.querySelector('#events-overview');
    if (!el) return;

    var options = {
        chart: {
            type: 'bar',
            height: 260,
            toolbar: { show: false }
        },
        series: [{ name: 'Events', data: series }],
        xaxis: { categories: labels },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } }
    };

    try {
        (new ApexCharts(el, options)).render();
    } catch (e) {
    }
})();
</script>
@endsection
