@extends('backEnd.layouts.app')
@section('title', 'Couriers')
@section('content')
<div class="row">
    <div class="col-12">

        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-0">Couriers</h4>
                <p class="text-muted mb-0 small">
                    Every courier account and whether it is ready to take parcels. Credentials are edited in one place.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-admin.button variant="outline-secondary" size="sm" href="{{ route('admin.courier.shipments') }}">
                    <i class="ri ri-list-check-2 me-1"></i>Shipments
                </x-admin.button>
                <x-admin.button variant="primary" size="sm" href="{{ route('admin.courier.settings') }}">
                    <i class="ri ri-settings-3-line me-1"></i>API settings
                </x-admin.button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-truck-line me-2"></i>Courier accounts</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Courier</th>
                            <th>Base URL</th>
                            <th>Credentials</th>
                            <th>Status</th>
                            <th>Shipments</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($couriers as $courier)
                        @php($stat = $shipmentStats->get($courier->type))
                        <tr>
                            <td>
                                <strong>{{ $courier->label() }}</strong>
                                <div class="text-muted small">{{ $courier->type }}</div>
                            </td>
                            <td><code class="small">{{ $courier->url ?: '—' }}</code></td>
                            <td>
                                @if($courier->isConfigured())
                                    <span class="badge bg-label-success">Complete</span>
                                @else
                                    <span class="badge bg-label-danger">No API Connect</span>
                                @endif
                            </td>
                            <td>
                                @if($courier->status)
                                    <span class="badge bg-label-primary">Enabled</span>
                                @else
                                    <span class="badge bg-label-secondary">Disabled</span>
                                    @if(! $courier->isConfigured())
                                        <div class="text-muted small">Set API credentials to connect</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.courier.shipments', ['provider' => $courier->type]) }}">
                                    {{ (int) ($stat->total ?? 0) }}
                                </a>
                                @if($stat && $stat->last_synced_at)
                                    <div class="text-muted small">last {{ \Illuminate\Support\Carbon::parse($stat->last_synced_at)->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <x-admin.button variant="outline-primary" size="sm"
                                                    href="{{ route('admin.courier.settings') }}#courier-{{ $courier->type }}">
                                        API settings
                                    </x-admin.button>

                                    <form method="POST" action="{{ route('admin.courier.toggle', $courier->type) }}">
                                        @csrf
                                        <x-admin.button :variant="$courier->status ? 'outline-danger' : 'outline-success'"
                                                        size="sm" type="submit">
                                            {{ $courier->status ? 'Disable' : 'Enable' }}
                                        </x-admin.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No courier accounts exist yet. Run the courier migration, then open API settings.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
