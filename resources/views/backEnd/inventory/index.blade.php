@extends('backEnd.layouts.app')
@section('title','Inventory / Stock')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-uppercase fw-bold">Inventory / Stock</h4>
        <div></div>
    </div>
</div>

@php
  $kpiTotal = isset($variants) ? (int) $variants->total() : 0;
  $kpiLow = 0;
  $kpiOut = 0;
  if (isset($variants)) {
    foreach ($variants as $vv) {
      $av = (int) ($vv->available ?? 0);
      if ($av <= 0) { $kpiOut++; }
      if ($av > 0 && $av <= 2) { $kpiLow++; }
    }
  }
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-widget-separator-wrapper">
                <div class="card-body card-widget-separator">
                    <div class="row g-3">
                        <div class="col-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ $kpiTotal }}</h4>
                                    <p class="mb-0 text-muted">Total Variants</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial bg-label-primary rounded-0">
                                        <i class="ri ri-stack-line fs-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0 fw-bold text-warning">{{ $kpiLow }}</h4>
                                    <p class="mb-0 text-muted">Low Stock (1-2)</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial bg-label-warning rounded-0">
                                        <i class="ri ri-alert-line fs-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0 fw-bold text-danger">{{ $kpiOut }}</h4>
                                    <p class="mb-0 text-muted">Out of Stock</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial bg-label-danger rounded-0">
                                        <i class="ri ri-close-circle-line fs-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0 fw-bold text-warning">{{ (int) ($activeReservedTotal ?? 0) }}</h4>
                                    <p class="mb-0 text-muted">Active Reserved (TTL)</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial bg-label-warning rounded-0">
                                        <i class="ri ri-time-line fs-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0 fw-bold text-danger">{{ (int) ($damagedTotal ?? 0) }}</h4>
                                    <p class="mb-0 text-muted">Damaged (Total)</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial bg-label-danger rounded-0">
                                        <i class="ri ri-alert-fill fs-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Variants</h5>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="d-flex gap-2 align-items-center">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control rounded-0" style="min-width: 320px;" placeholder="Search product / sku / variant / id" />
                <a href="{{ route('admin.inventory.adjustment') }}" class="btn btn-primary btn-square waves-effect">
                    <i class="ri ri-settings-4-line me-1"></i> Adjustment
                </a>
                <x-admin.button variant="info" shape="square" ripple="true" type="submit">
                    <i class="ri ri-search-line me-1"></i> Search
                </x-admin.button>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-square waves-effect">Reset</a>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th class="d-none d-lg-table-cell">Variant ID</th>
                        <th class="d-none d-md-table-cell">SKU</th>
                        <th>Variant</th>
                        <th class="d-none d-sm-table-cell">Product</th>
                        <th class="text-center">On Hand</th>
                        <th class="text-center d-none d-md-table-cell">Reserved</th>
                        <th class="text-center">Available</th>
                        <th class="text-center d-none d-md-table-cell">Damaged</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $v)
                        @php $isLow = (int) ($v->available ?? 0) > 0 && (int) ($v->available ?? 0) <= 2; @endphp
                        @php $isOut = (int) ($v->available ?? 0) <= 0; @endphp
                        <tr>
                            <td class="d-none d-lg-table-cell">{{ $v->id }}</td>
                            <td class="d-none d-md-table-cell fw-medium">{{ $v->sku ?? 'N/A' }}</td>
                            <td>{{ $v->title ?? 'N/A' }}</td>
                            <td class="d-none d-sm-table-cell">{{ $v->product_name ?? 'N/A' }}</td>
                            <td class="text-center {{ $isLow || $isOut ? 'text-danger fw-bold' : '' }}">{{ $v->on_hand ?? 0 }}</td>
                            <td class="text-center d-none d-md-table-cell {{ $isLow || $isOut ? 'text-danger fw-bold' : '' }}">{{ $v->reserved ?? 0 }}</td>
                            <td class="text-center {{ $isLow || $isOut ? 'text-danger fw-bold' : '' }}">{{ $v->available ?? 0 }}</td>
                            <td class="text-center d-none d-md-table-cell text-danger fw-bold">{{ $v->damaged ?? 0 }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventory.ledger', ['variant_id' => $v->id]) }}" class="btn btn-sm btn-outline-primary waves-effect">Ledger</a>
                                <a href="{{ route('admin.inventory.adjustment', ['variant_id' => $v->id]) }}" class="btn btn-sm btn-primary waves-effect">Adjust</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No variants found in inventory</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($variants, 'links') && $variants->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary mb-0">
                {{ $variants->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
