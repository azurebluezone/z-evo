@extends('backEnd.layouts.app')
@section('title', 'Abandoned Carts')

@section('content')
{{--
    Abandoned carts, Phase 3 item 5.

    The status printed here is the stored value from abandoned_carts, written by
    CartActivityRecorder when the cart changed and moved by the
    `carts:mark-abandoned` command. Nothing on this page infers abandonment from
    logs or from the absence of an order.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3" data-abandoned-carts>
    <div>
        <h4 class="mb-0">Abandoned Carts</h4>
        <p class="text-muted mb-0 small">Carts with no order, and the recovery state we actually recorded.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.marketing') }}" class="btn btn-sm btn-outline-secondary">Marketing</a>
        <a href="{{ route('admin.reports.dashboard') }}" class="btn btn-sm btn-outline-secondary">Report Dashboard</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Open</p>
                <h4 class="mb-1" data-abandoned-summary-open="{{ (int) $counts->get('open', 0) }}">{{ number_format((int) $counts->get('open', 0), 0) }}</h4>
                <span class="badge bg-label-primary">still shopping</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Abandoned</p>
                <h4 class="mb-1" data-abandoned-summary-abandoned="{{ (int) $counts->get('abandoned', 0) }}">{{ number_format((int) $counts->get('abandoned', 0), 0) }}</h4>
                <span class="badge bg-label-warning">idle past {{ $windowHours }}h</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Abandoned value</p>
                <h4 class="mb-1" data-abandoned-summary-value="{{ number_format($abandonedValue, 2, '.', '') }}">BDT {{ number_format($abandonedValue, 0) }}</h4>
                <span class="badge bg-label-secondary">stored subtotals</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Recovered / converted</p>
                <h4 class="mb-1">
                    <span data-abandoned-summary-recovered="{{ (int) $counts->get('recovered', 0) }}">{{ number_format((int) $counts->get('recovered', 0), 0) }}</span>
                    /
                    <span data-abandoned-summary-converted="{{ (int) $counts->get('converted', 0) }}">{{ number_format((int) $counts->get('converted', 0), 0) }}</span>
                </h4>
                <span class="badge bg-label-success">stored state</span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="get" action="{{ route('admin.reports.abandoned-carts') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label for="status" class="form-label small mb-1">Status</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('admin.reports.abandoned-carts') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">
            A cart becomes <strong>abandoned</strong> when it stays <code>open</code> with no activity for
            {{ $windowHours }} hour(s). The window comes from <code>config/reports.php</code> and the
            <code>carts:mark-abandoned</code> command writes the change.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Carts</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Cart</th>
                    <th>Customer</th>
                    <th class="text-end">Items</th>
                    <th class="text-end">Subtotal</th>
                    <th>Status</th>
                    <th>Last activity</th>
                    <th>Order</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($carts as $cart)
                    <tr data-abandoned-cart-row="{{ $cart->cart_key }}"
                        data-abandoned-cart-status="{{ $cart->status }}"
                        data-abandoned-cart-items="{{ (int) $cart->item_count }}"
                        data-abandoned-cart-subtotal="{{ number_format((float) $cart->subtotal, 2, '.', '') }}">
                        <td><code>{{ \Illuminate\Support\Str::limit($cart->cart_key, 18) }}</code></td>
                        <td>
                            @if ($cart->customer_id)
                                #{{ $cart->customer_id }}
                                <span class="text-muted small d-block">{{ $cart->phone ?: $cart->email ?: '-' }}</span>
                            @else
                                <span class="text-muted">guest</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((int) $cart->item_count, 0) }}</td>
                        <td class="text-end">BDT {{ number_format((float) $cart->subtotal, 2) }}</td>
                        <td>
                            @php
                                $badge = match ($cart->status) {
                                    \App\Models\AbandonedCart::STATUS_ABANDONED => 'bg-label-warning',
                                    \App\Models\AbandonedCart::STATUS_RECOVERED => 'bg-label-success',
                                    \App\Models\AbandonedCart::STATUS_CONVERTED => 'bg-label-info',
                                    default => 'bg-label-primary',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($cart->status) }}</span>
                        </td>
                        <td class="small">{{ $cart->last_activity_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="small">{{ $cart->order_id ? '#'.$cart->order_id : '-' }}</td>
                        <td class="text-end">
                            @if ($cart->isRecoverable())
                                <form method="post" action="{{ route('admin.reports.abandoned-carts.recovered', $cart->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">Mark recovered</button>
                                </form>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No cart matches this filter. A cart appears here as soon as items are added.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body border-top">
        <h6 class="mb-2">How this table is built</h6>
        <ul class="text-muted small mb-0">
            <li>Every cart mutation stores the snapshot and refreshes <code>last_activity_at</code>; the row is keyed by the session the cart lives in.</li>
            <li><code>carts:mark-abandoned</code> moves <code>open</code> rows idle past the window to <code>abandoned</code> and writes <code>abandoned_at</code>.</li>
            <li>Placing an order through <code>OrderPlacementService</code> moves the row to <code>converted</code> and stores the <code>order_id</code>.</li>
            <li>Marking recovered writes <code>recovered_at</code>; the state is never guessed from a log.</li>
        </ul>
    </div>
</div>
@endsection
