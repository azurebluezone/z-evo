@extends('backEnd.layouts.app')
@section('title','Order Report')

@section('content')
{{--
    Order Report (Sales Report)

    Structure follows the Materialize/Materio admin template:
      - a KPI widget card   (app-invoice-list.html -> "Invoice List Widget")
      - a filter card       (app-ecommerce-product-list.html -> "Product List Table")
      - a datatable card    (card-datatable table-responsive)

    The page title uses the template's card-header pattern (card rounded-0 +
    card-header + text-uppercase fw-bold), which is the house style already
    used by order/index.blade.php and the other backEnd screens.

    Fixes carried over from the previous version of this file:
      - the extra </div> that pushed the layout footer out of .content-wrapper
      - the duplicate container (container-fluid inside the layout's container-xxl)
      - the dead @section('css') / @section('script') that the app layout did
        not yield, so Print and Export never had a script to run
      - the 404 asset paths (asset('backEnd')/... and asset('/public/backEnd/')/...)
      - $total_sales, which the footer referenced but the controller never passed
--}}

{{-- ============================ KPI WIDGET ============================ --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Total Sales</p>
                        <h4 class="mb-0">{{ number_format((float)($total_sales ?? 0), 2) }}</h4>
                        <small class="text-muted">Delivered orders</small>
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
                        <p class="text-muted mb-1">Total Purchase</p>
                        <h4 class="mb-0">{{ number_format((float)($total_purchase ?? 0), 2) }}</h4>
                        <small class="text-muted">Cost of goods</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-primary">
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
                        <p class="text-muted mb-1">Total Profit</p>
                        <h4 class="mb-0 {{ ((float)($total_sales ?? 0) - (float)($total_purchase ?? 0)) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float)($total_sales ?? 0) - (float)($total_purchase ?? 0), 2) }}
                        </h4>
                        <small class="text-muted">Sales minus purchase</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-info">
                            <i class="icon-base ri ri-percent-line icon-26px"></i>
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
                        <p class="text-muted mb-1">Items Sold</p>
                        <h4 class="mb-0">{{ number_format((float)($total_item ?? 0)) }}</h4>
                        <small class="text-muted">Total quantity</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded-3 bg-label-warning">
                            <i class="icon-base ri ri-box-3-line icon-26px"></i>
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
        <form method="get" action="{{ route('admin.order_report') }}">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="keyword" class="form-label">Keyword</label>
                    <input type="text" id="keyword" name="keyword"
                           value="{{ request('keyword') }}"
                           class="form-control" placeholder="Invoice, customer or product">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="user_id" class="form-label">Assign User</label>
                    <select id="user_id" name="user_id" class="form-select select2 @error('user_id') is-invalid @enderror">
                        <option value="">Select..</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @if((string) request('user_id') === (string) $user->id) selected @endif>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
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
                    <x-admin.button variant="outline-secondary" href="{{ route('admin.order_report') }}">
                        <i class="icon-base ri ri-refresh-line me-1"></i>Reset
                    </x-admin.button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ============================ REPORT TABLE ============================ --}}
<div class="card rounded-0" id="print-area">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 no-print">
        <h5 class="mb-0 text-uppercase fw-bold">Order Report</h5>
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
            <table class="table table-hover align-middle mb-0 w-100" id="order-report-table">
                <thead>
                    <tr>
                        <th style="width:5%">Invoice</th>
                        <th style="width:18%">Customer</th>
                        <th style="width:15%">Phone</th>
                        <th style="width:27%">Product</th>
                        <th style="width:10%" class="text-end">Purchase</th>
                        <th style="width:10%" class="text-end">Sale</th>
                        <th style="width:7%" class="text-end">Qty</th>
                        <th style="width:8%" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order?->invoice_id ?? '' }}</td>
                            <td>{{ $order->shipping?->name ?? '' }}</td>
                            <td>{{ $order->shipping?->phone ?? '' }}</td>
                            <td>{{ $order->product_name }}</td>
                            <td class="text-end">{{ number_format((float) $order->purchase_price, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $order->sale_price, 2) }}</td>
                            <td class="text-end">{{ $order->qty }}</td>
                            <td class="text-end fw-medium">{{ number_format((float) $order->qty * (float) $order->sale_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No delivered orders found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
        <div class="card-footer border-top bg-white py-3 no-print">
            <div class="d-flex justify-content-end">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

{{--
    Print stylesheet.

    Only the admin chrome is hidden - the report table stays in the normal
    document flow, so it prints at the right width and on the right page.
    Hiding everything with visibility:hidden and repositioning a single
    element is the other trick, and it loses the page background and the
    card border.
--}}
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

{{--
    Print + Export.

    The previous version loaded table2excel from a CDN and called a
    printFunction() that lived in a @section('script') the layout never
    yielded, so neither button did anything. Both are implemented here with
    no external dependency:

      - Print  : window.print(), with the stylesheet above scoping it.
      - Export : the table is serialised to CSV on the client and downloaded
                 as a Blob. No plugin, no network call.
--}}
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
            var table = document.getElementById('order-report-table');
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

            // BOM so Excel opens the UTF-8 file with the right encoding.
            var blob = new Blob(['﻿' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'order-report-' + new Date().toISOString().slice(0, 10) + '.csv';
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
