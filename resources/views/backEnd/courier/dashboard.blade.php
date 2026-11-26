{{--
    backEnd/courier/dashboard - the courier account in business language.

    Three questions, in the order an admin asks them:
      1. how much money is at the courier, and how many parcels are out;
      2. which parcels are they, and what is the courier saying about them;
      3. how is this month going (delivered / cancelled / still moving).

    No API routes, no probe matrix, no webhook tokens. The parcel rows are the
    shipments this shop booked; the balance and courier counters are live from
    the courier's documented endpoints.
--}}
@extends('backEnd.layouts.app')
@section('title', 'Courier Dashboard')
@section('content')
@php
    $totalParcels = (int) ($totals['total'] ?? 0);
@endphp
<div class="row g-3">

    {{-- ==================== page header ==================== --}}
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                <div>
                    <h5 class="mb-0 text-uppercase fw-bold">
                        <i class="ri ri-truck-line me-2"></i>Courier Dashboard
                    </h5>
                    <small class="text-muted">Live courier balance and counters, plus every parcel booked from this panel.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <x-admin.button variant="outline-secondary" shape="square" ripple="true"
                                    href="{{ route('admin.courier.shipments') }}">
                        <i class="ri ri-list-check-2 me-1"></i>Shipments
                    </x-admin.button>
                    <x-admin.button variant="outline-secondary" shape="square" ripple="true"
                                    href="{{ route('admin.courier.index') }}">
                        <i class="ri ri-truck-line me-1"></i>Couriers
                    </x-admin.button>
                    <x-admin.button variant="outline-secondary" shape="square" ripple="true"
                                    href="{{ route('admin.courier.settings') }}">
                        <i class="ri ri-settings-3-line me-1"></i>Settings
                    </x-admin.button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== KPI row ==================== --}}
    <div class="col-12">
        <div class="row g-3">

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 rounded-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Parcels booked</p>
                                <h4 class="mb-0 text-primary">{{ number_format($totalParcels) }}</h4>
                                <small class="text-muted">
                                    today {{ number_format((int) ($totals['today'] ?? 0)) }} ·
                                    7 days {{ number_format((int) ($totals['week'] ?? 0)) }}
                                </small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ri ri-file-list-3-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 rounded-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Awaiting delivery</p>
                                <h4 class="mb-0 text-warning">{{ number_format((int) ($totals['pending'] ?? 0)) }}</h4>
                                <small class="text-warning"><i class="ri ri-time-line"></i> Still with the courier</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="icon-base ri ri-truck-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 rounded-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Delivered</p>
                                <h4 class="mb-0 text-success">{{ number_format((int) ($totals['delivered'] ?? 0)) }}</h4>
                                <small class="text-success"><i class="ri ri-checkbox-circle-line"></i> Confirmed by courier</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ri ri-checkbox-circle-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 rounded-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-muted">Cash to collect</p>
                                <h4 class="mb-0 text-info">৳ {{ number_format((float) ($totals['in_flight_value'] ?? 0)) }}</h4>
                                <small class="text-muted">On parcels still moving</small>
                            </div>
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ri ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ==================== courier account cards ==================== --}}
    @foreach($couriers as $courier)
        @php
            $snap    = $snapshots[$courier->type] ?? [];
            $stats   = $snap['stats'] ?? [];
            $balance = $snap['balance'] ?? [];
            $live    = $snap['live'] ?? [];
        @endphp

        <div class="col-12 col-xl-6">
            <div class="card h-100 rounded-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="icon-base ri ri-truck-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $courier->label() }}</h5>
                            <small class="text-muted">
                                {{ $courier->status ? 'Connected' : 'Switched off' }}
                            </small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @if($courier->isReady())
                            <span class="badge bg-label-success">Ready</span>
                        @elseif($courier->status)
                            <span class="badge bg-label-warning">Needs setup</span>
                        @else
                            <span class="badge bg-label-secondary">Disabled</span>
                        @endif
                        @if(! $courier->isConfigured())
                            <span class="badge bg-label-danger">Credentials missing</span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 text-center text-sm-start">
                        <div class="col-6 col-sm-3">
                            <div class="text-muted small">Parcels</div>
                            <div class="fs-5 fw-semibold">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="text-muted small">Pending</div>
                            <div class="fs-5 fw-semibold text-warning">{{ number_format((int) ($stats['pending'] ?? 0)) }}</div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="text-muted small">Delivered</div>
                            <div class="fs-5 fw-semibold text-success">{{ number_format((int) ($stats['delivered'] ?? 0)) }}</div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="text-muted small">COD to collect</div>
                            <div class="fs-5 fw-semibold text-info">৳ {{ number_format((float) ($stats['in_flight_value'] ?? 0)) }}</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    @if($courier->type === 'steadfast')
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <div class="text-muted small text-uppercase">Balance at courier</div>
                                @if(! empty($balance['available']))
                                    <div class="fs-4 fw-bold">৳ {{ number_format((float) $balance['amount'], 2) }}</div>
                                    <div class="small text-muted">
                                        Live from {{ $courier->label() }}
                                        @if(! empty($balance['checked_at']))
                                            · checked {{ \Illuminate\Support\Carbon::parse($balance['checked_at'])->diffForHumans() }}
                                        @endif
                                    </div>
                                @else
                                    <div class="fs-5 fw-semibold text-muted">—</div>
                                    <div class="small text-muted">{{ $balance['message'] ?? 'Balance is not available right now.' }}</div>
                                @endif
                            </div>
                            @if(! empty($live['ok']) && ! empty($live['stats']))
                                <div class="text-sm-end">
                                    <div class="text-muted small">Last 30 days</div>
                                    <div class="small">
                                        <span class="text-success fw-medium">{{ (int) $live['stats']['delivered'] }}</span> delivered ·
                                        <span class="text-danger fw-medium">{{ (int) $live['stats']['cancelled'] }}</span> cancelled
                                    </div>
                                    <div class="small text-muted">Success rate {{ number_format((float) $live['stats']['success_rate'], 0) }}%</div>
                                </div>
                            @endif
                        </div>

                        @if(! empty($live['ok']) && ! empty($live['states']))
                            <div class="row g-2">
                                @foreach($live['states'] as $state)
                                    <div class="col-6 col-lg-3">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">{{ $state['label'] }}</div>
                                            <div class="fs-6 fw-semibold">{{ number_format((int) $state['count']) }}</div>
                                            <div class="text-muted small">৳ {{ number_format((float) $state['cod_amount'], 0) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="small text-muted mt-2">
                                Live counts from {{ $courier->label() }}
                                @if(! empty($live['checked_at']))
                                    · checked {{ \Illuminate\Support\Carbon::parse($live['checked_at'])->diffForHumans() }}
                                @endif
                            </div>
                        @endif
                    @else
                        @if(! empty($live['ok']))
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="text-muted small text-uppercase">Pickup store</div>
                                    <div class="fw-semibold">{{ $live['preferred']['name'] ?? 'No default store' }}</div>
                                    @if(! empty($live['preferred']['address']))
                                        <div class="small text-muted">{{ $live['preferred']['address'] }}</div>
                                    @endif
                                </div>
                                <div class="text-sm-end">
                                    <span class="badge bg-label-info">{{ number_format((int) ($live['stores_count'] ?? 0)) }} store(s)</span>
                                    <div class="small text-muted mt-1">
                                        Parcels are collected from this store.
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">
                                Live from Pathao
                                @if(! empty($live['checked_at']))
                                    · checked {{ \Illuminate\Support\Carbon::parse($live['checked_at'])->diffForHumans() }}
                                @endif
                            </div>
                        @endif
                    @endif

                    @if(! empty($live['error']))
                        <div class="alert alert-warning py-2 small mt-3 mb-0" role="alert">
                            <i class="ri ri-error-warning-line me-1"></i>{{ $live['error'] }}
                        </div>
                    @endif

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-2 border-top">
                        <div class="small text-muted">
                            @if($stats['last_synced'] ?? null)
                                Parcel status last updated {{ \Illuminate\Support\Carbon::parse($stats['last_synced'])->diffForHumans() }}
                            @else
                                No courier status received yet
                            @endif
                        </div>
                        <a href="{{ route('admin.courier.shipments', ['provider' => $courier->type]) }}"
                           class="btn btn-sm btn-outline-primary btn-square waves-effect">
                            View parcels
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if($couriers->isEmpty())
        <div class="col-12">
            <div class="card rounded-0">
                <div class="card-body text-center text-muted py-5">
                    <i class="icon-base ri ri-truck-line icon-32px d-block mb-2"></i>
                    No courier account is set up yet.
                    <div class="mt-2">
                        <x-admin.button variant="primary" size="sm" shape="square"
                                        href="{{ route('admin.courier.settings') }}">
                            Open Courier Settings
                        </x-admin.button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== recent parcels + status split ==================== --}}
    <div class="col-12 col-xl-8">
        <div class="card rounded-0 h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                <h5 class="mb-0 text-uppercase fw-bold">Recent parcels</h5>
                <a href="{{ route('admin.courier.shipments') }}" class="btn btn-sm btn-outline-secondary btn-square waves-effect">
                    All shipments
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Courier</th>
                                <th>Consignment</th>
                                <th>Status</th>
                                <th class="text-end">COD</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($recent as $row)
                            @php
                                $shipment = $row['shipment'];
                                $status   = $row['status'];
                                $order    = $shipment->order;
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    @if($order)
                                        <a href="{{ route('admin.order.invoice', ['invoice_id' => $order->invoice_id]) }}" class="fw-medium">
                                            #{{ $order->invoice_id }}
                                        </a>
                                    @else
                                        <span class="text-muted">Order #{{ $shipment->order_id }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order && $order->shipping)
                                        <div class="fw-medium text-truncate" style="max-width: 180px;">{{ $order->shipping->name }}</div>
                                        <div class="small text-muted">{{ $order->shipping->phone }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-label-info">{{ $shipment->provider }}</span></td>
                                <td class="text-nowrap">
                                    <span class="font-monospace small">{{ $shipment->consignment_id ?: '—' }}</span>
                                    @if($shipment->tracking_code)
                                        <div class="small text-muted">{{ $shipment->tracking_code }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    ৳ {{ number_format((float) ($order->amount ?? 0)) }}
                                </td>
                                <td class="text-muted small text-nowrap">
                                    {{ $shipment->last_synced_at ? $shipment->last_synced_at->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="icon-base ri ri-inbox-line icon-32px d-block mb-2"></i>
                                    No parcels booked yet.
                                    <div class="small mt-1">Open an order and choose “Send to courier” to book the first parcel.</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card rounded-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Delivery status</h5>
            </div>
            <div class="card-body">
                @if($totalParcels > 0)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Delivered</span>
                        <span class="small fw-medium">
                            {{ number_format((int) ($totals['delivered'] ?? 0)) }} / {{ number_format($totalParcels) }}
                        </span>
                    </div>
                    <div class="progress mb-4" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width: {{ round(((int) ($totals['delivered'] ?? 0) / max(1, $totalParcels)) * 100) }}%"
                             aria-label="Delivered parcels"
                             aria-valuenow="{{ (int) ($totals['delivered'] ?? 0) }}"
                             aria-valuemin="0"
                             aria-valuemax="{{ $totalParcels }}"></div>
                    </div>
                @endif

                @forelse($statuses as $status)
                    @php
                        $percent = $totalParcels > 0 ? round(($status['count'] / $totalParcels) * 100) : 0;
                        $barClass = match ($status['meta']['group']) {
                            'delivered' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default => 'bg-warning',
                        };
                    @endphp
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge {{ $status['meta']['class'] }}">{{ $status['label'] }}</span>
                        <span class="small fw-medium">{{ number_format($status['count']) }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar {{ $barClass }}" role="progressbar"
                             style="width: {{ $percent }}%"
                             aria-label="{{ $status['label'] }} parcels"
                             aria-valuenow="{{ $status['count'] }}"
                             aria-valuemin="0"
                             aria-valuemax="{{ max(1, $totalParcels) }}"></div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Status appears here as soon as a parcel is booked.</p>
                @endforelse

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">COD delivered</span>
                    <span class="fw-semibold">৳ {{ number_format((float) ($totals['delivered_value'] ?? 0)) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small">COD still moving</span>
                    <span class="fw-semibold text-warning">৳ {{ number_format((float) ($totals['in_flight_value'] ?? 0)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <p class="small text-muted mb-2">
            Parcel rows are the shipments booked in this panel. Balance and courier counters are read live from the
            courier account; each card shows when it was last checked.
        </p>
    </div>

</div>
@endsection
