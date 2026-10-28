@extends('backEnd.layouts.app')
@section('title', 'Coupon Usage - ' . $coupon->code)

@section('content')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Usage details</h4>
        <p class="text-muted mb-0 small">
            How often <code>{{ $coupon->code }}</code> has been used, from the coupon usage log (storefront + POS).
        </p>
    </div>
    <a href="{{ route('coupons.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
        <i class="ri ri-arrow-left-line me-1"></i> Manage Coupons
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <span class="text-muted small">Status</span>
                    @if(($coupon->status ?? 0) == 1)
                        <span class="badge bg-label-success">Active</span>
                    @else
                        <span class="badge bg-label-secondary">Inactive</span>
                    @endif
                </div>
                <h4 class="mt-2 mb-0">{{ strtoupper($coupon->type) }} · {{ $coupon->value }}</h4>
                <small class="text-muted">
                    @if($coupon->start_at || $coupon->end_at)
                        {{ $coupon->start_at ? $coupon->start_at->format('Y-m-d') : 'now' }}
                        →
                        {{ $coupon->end_at ? $coupon->end_at->format('Y-m-d') : 'no end' }}
                    @else
                        No date limit
                    @endif
                </small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small">Used today</span>
                <h4 class="mt-2 mb-0">{{ number_format($today) }}</h4>
                <small class="text-muted">Orders placed today with this code</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small">Used total</span>
                <h4 class="mt-2 mb-0">{{ number_format($total) }}</h4>
                <small class="text-muted">
                    @if($coupon->usage_limit)
                        Limit {{ number_format($coupon->usage_limit) }} ·
                        {{ $coupon->usage_limit - $total > 0 ? number_format($coupon->usage_limit - $total) . ' left' : 'reached' }}
                    @else
                        No usage limit
                    @endif
                </small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small">Coupons.used_count</span>
                <h4 class="mt-2 mb-0">{{ number_format((int) $coupon->used_count) }}</h4>
                <small class="text-muted">Counter column (kept in step with the log)</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
        <h5 class="mb-0">Recent uses</h5>
        <span class="badge bg-label-secondary">{{ number_format($recent->count()) }} shown</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>When</th>
                        <th>Order</th>
                        <th>Source</th>
                        <th class="text-end">Discount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $i => $usage)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $usage->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($usage->order_id)
                                    <span class="fw-medium">#{{ $usage->order_id }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($usage->source === 'pos')
                                    <span class="badge bg-label-info">POS</span>
                                @else
                                    <span class="badge bg-label-primary">Storefront</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($usage->discount_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="ri ri-bar-chart-line icon-32px text-muted d-block mb-2" aria-hidden="true"></i>
                                <span class="text-muted">No uses recorded yet. Each order placed with this code appears here.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
