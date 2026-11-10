@extends('backEnd.layouts.app')
@section('title','Review Detail')
@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Review Detail</h4>
        <p class="text-muted mb-0">The full review, its product and the customer behind it.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
            <i class="ri ri-arrow-left-line me-1"></i> Manage
        </a>
        <a href="{{ route('reviews.edit', $review->id) }}" class="btn btn-primary btn-square waves-effect">
            <i class="ri ri-edit-box-line me-1"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card rounded-0 h-100">
            <div class="card-header border-bottom bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Review #{{ $review->id }}</h5>
                @if($review->status === 'active')
                    <span class="badge bg-label-success">Active</span>
                @else
                    <span class="badge bg-label-warning">Pending</span>
                @endif
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="d-inline-flex align-items-center gap-1" aria-label="Rating: {{ (int) $review->ratting }}">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="icon-base ri {{ $i <= (int) $review->ratting ? 'ri-star-fill text-warning' : 'ri-star-line text-muted' }}"></i>
                        @endfor
                    </span>
                    <span class="text-muted">({{ (int) $review->ratting }} / 5)</span>
                </div>

                <p class="mb-0 text-break">{!! nl2br(e($review->review)) !!}</p>
            </div>
            <div class="card-footer bg-white border-top text-muted small d-flex flex-wrap gap-3">
                <span>Created: {{ $review->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                <span>Updated: {{ $review->updated_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
        </div>

        <div class="card rounded-0 mt-4">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Moderation</h5>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                @if($review->status === 'active')
                    <form method="post" action="{{ route('reviews.inactive') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="hidden_id" value="{{ $review->id }}">
                        <x-admin.button variant="text-warning" shape="square" type="submit" class="change-confirm">
                            <i class="ri ri-thumb-down-line me-1"></i>Mark pending
                        </x-admin.button>
                    </form>
                @else
                    <form method="post" action="{{ route('reviews.active') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="hidden_id" value="{{ $review->id }}">
                        <x-admin.button variant="text-success" shape="square" type="submit" class="change-confirm">
                            <i class="ri ri-thumb-up-line me-1"></i>Approve
                        </x-admin.button>
                    </form>
                @endif

                <form method="post" action="{{ route('reviews.destroy') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="hidden_id" value="{{ $review->id }}">
                    <x-admin.button variant="text-danger" shape="square" type="submit" class="delete-confirm">
                        <i class="ri ri-delete-bin-7-line me-1"></i>Delete
                    </x-admin.button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Product</h5>
            </div>
            <div class="card-body">
                @if($review->product)
                    <h6 class="mb-1">
                        <a href="{{ route('products.edit', $review->product->id) }}" class="text-body">
                            {{ $review->product->name }}
                        </a>
                    </h6>
                    <div class="text-muted small mb-3">{{ $review->product->product_code }}</div>
                    <div class="d-flex justify-content-between border-top pt-2 mb-1">
                        <span class="text-muted">Price</span>
                        <span class="fw-medium">{{ number_format((float) $review->product->new_price, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Stock</span>
                        <span class="fw-medium">{{ number_format((float) $review->product->stock) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Status</span>
                        @if($review->product->status == 1)
                            <span class="badge bg-label-success">Published</span>
                        @else
                            <span class="badge bg-label-secondary">Unpublished</span>
                        @endif
                    </div>
                @else
                    <p class="text-muted mb-0">The product of this review no longer exists.</p>
                @endif
            </div>
        </div>

        <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Customer</h5>
            </div>
            <div class="card-body">
                @if($review->customer)
                    <h6 class="mb-1">{{ $review->customer->name }}</h6>
                    <div class="text-muted small mb-2">{{ $review->customer->email }}</div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Phone</span>
                        <span class="fw-medium">{{ $review->customer->phone ?: '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Account</span>
                        <span class="badge bg-label-success">{{ ucfirst((string) $review->customer->status) }}</span>
                    </div>
                @else
                    <h6 class="mb-1">{{ $review->name }}</h6>
                    <div class="text-muted small mb-2">{{ $review->email }}</div>
                    <p class="text-muted mb-0 small">Guest buyer — no customer account is linked to this review.</p>
                @endif
            </div>
        </div>

        <div class="card rounded-0">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Product rating</h5>
            </div>
            <div class="card-body">
                @php($activeTotal = (int) ($productRating->total ?? 0))
                @if($activeTotal > 0)
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="h3 mb-0">{{ number_format((float) ($productRating->average ?? 0), 1) }}</span>
                        <span class="text-muted">/ 5</span>
                    </div>
                    <p class="text-muted mb-0 small">Average of {{ number_format($activeTotal) }} active review(s) for this product.</p>
                @else
                    <p class="text-muted mb-0">No active review is visible on the storefront for this product yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
