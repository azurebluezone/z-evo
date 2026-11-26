@extends('backEnd.layouts.app')
@section('title', 'Courier Shipments')
@section('content')
<div class="row">
    <div class="col-12">

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        {{-- House layout: the page's filters live in the card header with the
             title, exactly like Manage Orders / Inventory, instead of a second
             card whose last column stretched the Filter button. --}}
        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                <h5 class="mb-0 text-uppercase fw-bold">
                    Courier shipments
                    <span class="badge bg-label-primary ms-1">{{ $shipments->total() }}</span>
                </h5>

                <form method="GET" action="{{ route('admin.courier.shipments') }}"
                      class="d-flex flex-wrap align-items-center gap-2 m-0">
                    <select class="form-select rounded-0" id="provider" name="provider"
                            style="min-width: 150px" aria-label="Courier">
                        <option value="">All couriers</option>
                        <option value="steadfast" @selected($provider === 'steadfast')>SteadFast</option>
                        <option value="pathao" @selected($provider === 'pathao')>Pathao</option>
                    </select>

                    <select class="form-select rounded-0" id="status" name="status"
                            style="min-width: 150px" aria-label="Status">
                        <option value="">Any status</option>
                        {{-- Built from the data, so the filter can never offer a
                             status that returns nothing. --}}
                        @foreach($statuses as $option)
                            <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                        @endforeach
                    </select>

                    <input type="text" class="form-control rounded-0" id="q" name="q"
                           value="{{ $search }}" placeholder="Consignment, tracking or invoice"
                           style="min-width: 220px" aria-label="Search shipments">

                    <x-admin.button variant="info" shape="square" type="submit">
                        <i class="ri ri-filter-3-line me-1"></i>Filter
                    </x-admin.button>
                    <a href="{{ route('admin.courier.shipments') }}"
                       class="btn btn-outline-secondary btn-square waves-effect">Reset</a>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order</th>
                                <th>Courier</th>
                                <th>Consignment</th>
                                <th>Tracking</th>
                                <th>Status</th>
                                <th>Last update</th>
                                <th class="text-end">View</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($shipments as $shipment)
                            @php
                                $statusKey = strtolower((string) $shipment->current_status);
                                $statusClass = match (true) {
                                    $statusKey === '' => 'bg-label-secondary',
                                    str_contains($statusKey, 'deliver') => 'bg-label-success',
                                    str_contains($statusKey, 'cancel'),
                                    str_contains($statusKey, 'return') => 'bg-label-danger',
                                    str_contains($statusKey, 'pending'),
                                    str_contains($statusKey, 'pickup'),
                                    str_contains($statusKey, 'pick') => 'bg-label-warning',
                                    default => 'bg-label-info',
                                };
                            @endphp
                            <tr>
                                <td>
                                    @if($shipment->order)
                                        {{ $shipment->order->invoice_id }}
                                    @else
                                        <span class="text-muted">order #{{ $shipment->order_id }} (missing)</span>
                                    @endif
                                    @if($shipment->merchant_order_id)
                                        <div class="text-muted small">{{ $shipment->merchant_order_id }}</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-label-info">{{ $shipment->provider }}</span></td>
                                <td><code class="small">{{ $shipment->consignment_id ?: '—' }}</code></td>
                                <td><code class="small">{{ $shipment->tracking_code ?: '—' }}</code></td>
                                <td>
                                    {{-- A shipment whose status is null has been booked but not
                                         yet reported on; saying so beats an empty cell. --}}
                                    @if($shipment->current_status)
                                        <span class="badge {{ $statusClass }}">{{ $shipment->current_status }}</span>
                                    @else
                                        <span class="badge bg-label-secondary">awaiting update</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $shipment->last_synced_at ? $shipment->last_synced_at->diffForHumans() : 'never' }}
                                </td>
                                <td class="text-end">
                                    <x-admin.button variant="outline-primary" size="sm"
                                                    href="{{ route('admin.courier.shipment', $shipment->id) }}">
                                        Details
                                    </x-admin.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No shipments yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($shipments->hasPages())
                <div class="card-footer">
                    {{ $shipments->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
