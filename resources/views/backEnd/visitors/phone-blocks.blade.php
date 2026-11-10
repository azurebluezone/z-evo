@extends('backEnd.layouts.app')
@section('title', 'Phone Blocks')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-warning" role="alert">
        {{ $errors->first() }}
    </div>
@endif
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="icon-base ri ri-shield-user-line me-2"></i>Phone Blocks</h4>
        <p class="text-muted mb-0">
            Search a phone number, see the customers and orders behind it, and block it from every
            frontend order form.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.visitors.list') }}" class="btn btn-outline-secondary">
            <i class="icon-base ri ri-team-line me-1"></i>Visitor List
        </a>
        <a href="{{ route('admin.visitors.index') }}" class="btn btn-outline-primary">
            <i class="icon-base ri ri-bar-chart-box-line me-1"></i>Analytics
        </a>
    </div>
</div>

<div class="card rounded-0 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0">Search a phone number</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.visitors.phone-blocks') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label" for="phone-search">Phone</label>
                <input type="text" name="phone" id="phone-search" class="form-control"
                       value="{{ $search }}" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" autocomplete="off">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-base ri ri-search-line me-1"></i>Search
                </button>
                @if($search !== '')
                    <a href="{{ route('admin.visitors.phone-blocks') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($search !== '')
            <hr>
            @if($normalized === null)
                <div class="alert alert-warning mb-0">
                    <strong>{{ $search }}</strong> is not a valid phone number. Use 01XXXXXXXXX or +8801XXXXXXXXX.
                </div>
            @else
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge bg-label-secondary">Normalised: {{ $normalized }}</span>
                    <span class="badge bg-label-info">{{ $customers->count() }} customer match{{ $customers->count() === 1 ? '' : 'es' }}</span>
                    <span class="badge bg-label-warning">{{ $orders->count() }} order match{{ $orders->count() === 1 ? '' : 'es' }}</span>
                    @if($currentBlock && $currentBlock->is_active)
                        <span class="badge bg-label-danger">Currently blocked</span>
                    @elseif($currentBlock)
                        <span class="badge bg-label-success">Block inactive</span>
                    @else
                        <span class="badge bg-label-secondary">Not blocked</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3"><i class="icon-base ri ri-user-line me-1"></i>Customers</h6>
                            @forelse($customers as $customer)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <div class="fw-medium">{{ $customer->name }}</div>
                                        <small class="text-muted">#CUST-{{ $customer->id }} · {{ $customer->status }}</small>
                                    </div>
                                    <code>{{ $customer->phone }}</code>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No customer account uses this number.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3"><i class="icon-base ri ri-shopping-bag-line me-1"></i>Orders</h6>
                            @forelse($orders as $order)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <a href="{{ route('admin.order.invoice', ['invoice_id' => $order->invoice_id]) }}" class="fw-medium">
                                            #{{ $order->invoice_id }}
                                        </a>
                                        <small class="text-muted d-block">
                                            {{ $order->shipping?->name ?: ($order->customer?->name ?: 'Guest') }}
                                            · {{ $order->created_at?->format('d M Y') }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <code>{{ $order->shipping?->phone ?: '-' }}</code>
                                        <small class="text-muted d-block">Customer: {{ $order->customer?->phone ?: '-' }}</small>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No order uses this delivery number.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.visitors.phone-blocks.store') }}" class="row g-3 align-items-end mt-1">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $normalized }}">
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="block-reason">Reason (optional)</label>
                        <input type="text" name="reason" id="block-reason" class="form-control"
                               placeholder="Fake order / abusive / chargeback" maxlength="255"
                               value="{{ $currentBlock->reason ?? '' }}">
                    </div>
                    <div class="col-12 col-md-4">
                        @if($currentBlock && $currentBlock->is_active)
                            <button type="submit" class="btn btn-outline-danger" disabled>
                                <i class="icon-base ri ri-shield-cross-line me-1"></i>Already blocked
                            </button>
                        @else
                            <button type="submit" class="btn btn-danger">
                                <i class="icon-base ri ri-shield-cross-line me-1"></i>Block {{ $normalized }}
                            </button>
                        @endif
                    </div>
                </form>
            @endif
        @endif
    </div>
</div>

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0">Blocked phone numbers</h5>
        <span class="badge bg-label-primary">Total: {{ $blocks->total() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Phone</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Hits</th>
                        <th>Last blocked</th>
                        <th>Blocked by</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blocks as $block)
                        <tr>
                            <td>
                                <code>{{ $block->phone }}</code>
                                @if($block->original_phone && $block->original_phone !== $block->phone)
                                    <small class="text-muted d-block">entered as {{ $block->original_phone }}</small>
                                @endif
                            </td>
                            <td class="small">{{ $block->reason ?: '-' }}</td>
                            <td>
                                @if($block->is_active)
                                    <span class="badge bg-label-danger">Blocked</span>
                                @else
                                    <span class="badge bg-label-success">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $block->hits }}</td>
                            <td class="small">{{ $block->last_blocked_at?->format('d M Y, h:i A') ?: '-' }}</td>
                            <td class="small">{{ $block->blockedBy?->name ?: '-' }}</td>
                            <td class="text-end">
                                @if($block->is_active)
                                    <form method="POST" action="{{ route('admin.visitors.phone-blocks.destroy') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="phone" value="{{ $block->phone }}">
                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Unblock {{ $block->phone }}?')">
                                            <i class="icon-base ri ri-shield-check-line me-1"></i>Unblock
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.visitors.phone-blocks.store') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="phone" value="{{ $block->phone }}">
                                        <input type="hidden" name="reason" value="{{ $block->reason }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="icon-base ri ri-shield-cross-line me-1"></i>Re-block
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No phone number is blocked yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($blocks->hasPages())
        <div class="card-footer bg-white border-top py-3">
            {{ $blocks->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@endsection
