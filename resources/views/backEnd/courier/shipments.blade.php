@extends('backEnd.layouts.app')
@section('title', 'Courier Shipments')
@section('content')
<div class="row">
    <div class="col-12">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-0">Courier shipments</h4>
                <p class="text-muted mb-0 small">
                    Every parcel this shop has handed to a courier, with the status the webhook last wrote.
                </p>
            </div>
            <a href="{{ route('admin.courier.index') }}" class="btn btn-secondary btn-sm">
                <i class="ri ri-arrow-left-line me-1"></i> Couriers
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.courier.shipments') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" for="provider">Courier</label>
                        <select class="form-select" id="provider" name="provider">
                            <option value="">All couriers</option>
                            <option value="steadfast" @selected($provider === 'steadfast')>SteadFast</option>
                            <option value="pathao" @selected($provider === 'pathao')>Pathao</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Any status</option>
                            {{-- Built from the data, so the filter can never offer a
                                 status that returns nothing. --}}
                            @foreach($statuses as $option)
                                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="q">Search</label>
                        <input type="text" class="form-control" id="q" name="q" value="{{ $search }}"
                               placeholder="consignment, tracking code or invoice">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <a href="{{ route('admin.courier.shipments') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
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
                                    <span class="badge bg-label-primary">{{ $shipment->current_status }}</span>
                                @else
                                    <span class="badge bg-label-secondary">awaiting update</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $shipment->last_synced_at ? $shipment->last_synced_at->diffForHumans() : 'never' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.courier.shipment', $shipment->id) }}"
                                   class="btn btn-sm btn-outline-primary">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No shipments recorded yet. A shipment appears here once an order is sent to a
                                courier, or once the courier's webhook reports a status for one.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($shipments->hasPages())
                <div class="card-footer">
                    {{ $shipments->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        <p class="text-muted small mt-2 mb-0">
            Showing {{ $shipments->count() }} of {{ $shipments->total() }}.
        </p>

    </div>
</div>
@endsection
