@extends('backEnd.layouts.app')
@section('title','Stock Report')

@section('content')
{{--
    Stock Report (Product Report)

    Same component set as the Order Report - a page title, a filter card and
    a datatable card with a totals strip under the table - because the two
    screens show the same kind of thing: a filtered list of products with a
    summary beside the table they describe. The money cards used to sit as a
    second KPI band above the table; the layout already draws the shared
    stat-grid header on every page, so this page draws its numbers once,
    under the table (same treatment as the Order Report).

    Also moved here:
      - the print stylesheet   -> public/admin/assets/css/admin-report.css
      - the print/export script-> public/admin/assets/js/admin-report-table.js
    (the blade keeps the @push with a <link>/<script src>, no inline blocks)

    Fixed from the previous version:
      - the extra </div> that pushed the layout footer out of .content-wrapper
      - the duplicate container (container-fluid inside the layout's container-xxl)
      - the dead @section('css') / @section('script') the layout never yielded
      - the 404 asset paths (asset('backEnd')/... and asset('/public/backEnd/')/...)
      - the footer referenced $total_purchase / $total_stock / $total_price,
        which the controller passes for the whole filter but the footer also
        recomputed per page, so the two numbers disagreed
--}}

{{-- ============================ PAGE TITLE ============================ --}}
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Stock Report</h4>
    </div>
</div>

{{-- ============================ FILTER CARD ============================ --}}
<div class="card rounded-0 mb-4 no-print">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.stock_report') }}">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="keyword" class="form-label">Keyword</label>
                    <input type="text" id="keyword" name="keyword"
                           value="{{ request('keyword') }}"
                           class="form-control rounded-0" placeholder="Product name">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select id="category_id" name="category_id" class="form-select select2 @error('category_id') is-invalid @enderror">
                        <option value="">Select..</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @if((string) request('category_id') === (string) $category->id) selected @endif>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date"
                           value="{{ request('start_date') }}" class="form-control rounded-0">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date"
                           value="{{ request('end_date') }}" class="form-control rounded-0">
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <x-admin.button variant="primary" type="submit">
                        <i class="icon-base ri ri-filter-3-line me-1"></i>Apply Filters
                    </x-admin.button>
                    <x-admin.button variant="outline-secondary" href="{{ route('admin.stock_report') }}">
                        <i class="icon-base ri ri-refresh-line me-1"></i>Reset
                    </x-admin.button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ============================ REPORT TABLE ============================ --}}
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 no-print">
        <h5 class="mb-0 text-uppercase fw-bold">Stock Report</h5>
        <div class="d-flex flex-wrap gap-2">
            <x-admin.button variant="outline-secondary" size="sm" type="button" id="print-report">
                <i class="icon-base ri ri-printer-line me-1"></i>Print
            </x-admin.button>
            <x-admin.button variant="outline-primary" size="sm" type="button" id="export-report">
                <i class="icon-base ri ri-file-excel-line me-1"></i>Export
            </x-admin.button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="stock-report-table">
                <thead>
                    <tr>
                        <th style="width:6%">SL</th>
                        <th style="width:44%">Product Name</th>
                        <th style="width:18%" class="text-end">Price</th>
                        <th style="width:16%" class="text-end">Stock</th>
                        <th style="width:16%" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $product->name }}</td>
                            <td class="text-end">{{ number_format((float) $product->new_price, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $product->stock) }}</td>
                            <td class="text-end fw-medium">{{ number_format((float) $product->stock * (float) $product->new_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No products found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Totals belong with the table they describe, not in a second band of
         cards above it - same treatment as the Order Report. Values cover
         the FILTER, not just this page. --}}
    <div class="border-top px-3 py-3 d-flex flex-wrap align-items-center gap-4 small">
        <span class="text-muted">
            Filtered totals · {{ number_format($products->total()) }} products
        </span>
        <span><span class="text-muted">Units:</span> <strong>{{ number_format((float) ($total_stock ?? 0)) }}</strong></span>
        <span><span class="text-muted">Stock value:</span> <strong>{{ number_format((float) ($total_price ?? 0), 2) }}</strong></span>
        <span><span class="text-muted">Stock cost:</span> <strong>{{ number_format((float) ($total_purchase ?? 0), 2) }}</strong></span>
    </div>

    @if($products->hasPages())
        <div class="card-footer border-top bg-white py-3 no-print">
            <div class="d-flex justify-content-end">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-report.css') }}">
@endpush

@push('script')
<script src="{{ asset('admin/assets/js/admin-report-table.js') }}"
        data-report-table="stock-report-table"
        data-report-export="stock-report"></script>
@endpush
@endsection
