@extends('backEnd.layouts.app')
@section('title','Stock Report')

@section('content')
{{--
    Stock Report (Product Report)

    Same component set as the Order Report - a KPI widget card, a filter
    card and a datatable card - because the two screens show the same kind
    of thing: a filtered list of products with a summary on top.

    Fixed from the previous version:
      - the extra </div> that pushed the layout footer out of .content-wrapper
      - the duplicate container (container-fluid inside the layout's container-xxl)
      - the dead @section('css') / @section('script') the layout never yielded
      - the 404 asset paths (asset('backEnd')/... and asset('/public/backEnd/')/...)
      - the footer referenced $total_purchase / $total_stock / $total_price,
        which the controller passes for the whole filter but the footer also
        recomputed per page, so the two numbers disagreed
--}}

{{-- ============================ KPI WIDGET ============================ --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Total Stock</p>
                        <h4 class="mb-0">{{ number_format((float)($total_stock ?? 0)) }} <small class="fs-6 text-muted">Pcs</small></h4>
                        <small class="text-muted">Units in the catalogue</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-primary">
                            <i class="icon-base ri ri-box-3-line icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Stock Value</p>
                        <h4 class="mb-0">{{ number_format((float)($total_price ?? 0), 2) }}</h4>
                        <small class="text-muted">Stock x sale price</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-success">
                            <i class="icon-base ri ri-money-dollar-circle-line icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Stock Cost</p>
                        <h4 class="mb-0">{{ number_format((float)($total_purchase ?? 0), 2) }}</h4>
                        <small class="text-muted">Stock x purchase price</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-info">
                            <i class="icon-base ri ri-database-2-line icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Products</p>
                        <h4 class="mb-0">{{ $products->total() }}</h4>
                        <small class="text-muted">Matching the filter</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-warning">
                            <i class="icon-base ri ri-archive-line icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
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
                           class="form-control" placeholder="Product name">
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
                           value="{{ request('start_date') }}" class="form-control">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date"
                           value="{{ request('end_date') }}" class="form-control">
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

    @if($products->hasPages())
        <div class="card-footer border-top bg-white py-3 no-print">
            <div class="d-flex justify-content-end">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@push('css')
<style>
    @media print {
        .layout-menu,
        .layout-navbar,
        .content-footer,
        .content-backdrop,
        .no-print {
            display: none !important;
        }
        .layout-page { padding-top: 0 !important; }
        .content-wrapper { padding: 0 !important; }
        .container-xxl { padding: 0 !important; max-width: 100% !important; }
        .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
        .table th, .table td { font-size: 12px; }
    }
</style>
@endpush

@push('script')
<script>
(function () {
    'use strict';

    var printBtn = document.getElementById('print-report');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    var exportBtn = document.getElementById('export-report');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var table = document.getElementById('stock-report-table');
            if (!table) return;

            var rows = [];
            var trs = table.querySelectorAll('tr');

            for (var i = 0; i < trs.length; i++) {
                var cells = trs[i].querySelectorAll('th, td');
                if (!cells.length) continue;

                var row = [];
                for (var j = 0; j < cells.length; j++) {
                    var text = (cells[j].innerText || '').replace(/\s+/g, ' ').trim();
                    row.push('"' + text.replace(/"/g, '""') + '"');
                }
                rows.push(row.join(','));
            }

            if (!rows.length) return;

            var blob = new Blob(['﻿' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'stock-report-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });
    }
})();
</script>
@endpush
@endsection
