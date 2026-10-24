@extends('backEnd.layouts.app')
@section('title', 'Wishlists')
@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Wishlists</h4>
    </div>
    <div class="d-flex gap-2">
        <form method="get" action="{{ route('admin.wishlist.index') }}" class="d-flex gap-2 align-items-center">
            <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Search product or customer...">
            <button type="submit" class="btn btn-primary btn-square waves-effect">
                <i class="icon-base ri ri-search-line me-1"></i>Search
            </button>
        </form>
    </div>
</div>

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">All Wishlists ({{ $show_data->total() }})</h5>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:64px;">Image</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Added At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($show_data as $key => $value)
                    <tr>
                        <td>
                            @php
                                $imgRaw = '';
                                try {
                                    $imgRaw = (string) ($value->product?->image?->image ?? '');
                                } catch (\Throwable $e) {
                                    $imgRaw = '';
                                }
                            @endphp
                            <img src="{{ !empty($imgRaw) ? asset($imgRaw) : asset('uploads/default/placeholder.png') }}" alt="" class="rounded" style="width:48px;height:48px;object-fit:cover;" />
                        </td>
                        <td class="fw-medium">{{ $value->product?->name ?? 'N/A' }}</td>
                        <td>
                            <div>{{ $value->customer?->name ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $value->customer?->email ?? '' }}</small>
                        </td>
                        <td>{{ $value->created_at ? $value->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <form method="post" action="{{ route('admin.wishlist.destroy', $value->id) }}" class="d-inline">
                                    @csrf
                                    <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" class="delete-confirm" type="submit" title="Delete" data-bs-toggle="tooltip" data-bs-title="Delete">
                                        <i class="icon-base ri ri-delete-bin-7-line icon-22px"></i>
                                    </x-admin.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No wishlist entries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($show_data, 'links'))
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary">
                {{ $show_data->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
