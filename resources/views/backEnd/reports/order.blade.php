@extends('backEnd.layouts.app')
@section('title','Order Report')

@section('content')
{{--
    Order Report (Sales Report)

    Structure follows the Materialize/Materio admin template:
      - one shared stat header (rendered by the layout, per-page data)
      - a filter card       (app-ecommerce-product-list.html -> "Product List Table")
      - a datatable card    (card-datatable table-responsive)
      - a totals strip under the table (filter totals, not per-page totals)

    Money totals were previously printed in a second band of four KPI cards
    directly under the shared stat header, so every visit showed two sets of
    overview cards. They now live beside the table they describe.

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

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Order Report</h4>
    </div>
</div>

{{-- Summary cards are ORDER-grain over the current filter (docs/report-metrics.md,
     "Sales Report summary rule"): distinct delivered orders, then orders.discount /
     orders.shipping_charge / orders.amount over that set. data-report-summary pins
     each value for the report tests; data-report-grain records which grain is on
     screen. Written out one card at a time (no @foreach) so the four keys stay
     greppable. Icons stay out - the shared stat header owns the page's four. --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Filtered Delivered Orders</div>
                <div class="fs-5 fw-semibold mb-0" data-report-grain="{{ $reportView }}"
                     data-report-summary="orders">{{ (string) (int) ($summary['orders'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Order Discount</div>
                <div class="fs-5 fw-semibold mb-0" data-report-grain="{{ $reportView }}"
                     data-report-summary="order_discount">{{ number_format((float) ($summary['order_discount'] ?? 0), 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Shipping Charged</div>
                <div class="fs-5 fw-semibold mb-0" data-report-grain="{{ $reportView }}"
                     data-report-summary="shipping">{{ number_format((float) ($summary['shipping'] ?? 0), 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card rounded-0 h-100">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Order Total</div>
                <div class="fs-5 fw-semibold mb-0" data-report-grain="{{ $reportView }}"
                     data-report-summary="order_total">{{ number_format((float) ($summary['order_total'] ?? 0), 2) }}</div>
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
                           class="form-control rounded-0" placeholder="Invoice, customer or product">
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
                <div class="col-12 d-flex flex-wrap gap-2 text-nowrap">
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
        <div class="d-flex flex-wrap gap-2 text-nowrap">
            {{-- Grain toggle: same filters, same cards, one row per LINE or one
                 row per ORDER. The active grain is the filled button; both are
                 plain GET links so the filter form and the toggle cannot drift
                 apart. No icon here - the stat band test counts page icons. --}}
            <div class="btn-group" role="group" aria-label="Report view">
                <x-admin.button size="sm"
                                variant="{{ $reportView === 'line' ? 'secondary' : 'outline-secondary' }}"
                                href="{{ route('admin.order_report', array_merge(request()->query(), ['view' => 'line'])) }}">Line view</x-admin.button>
                <x-admin.button size="sm"
                                variant="{{ $reportView === 'order' ? 'secondary' : 'outline-secondary' }}"
                                href="{{ route('admin.order_report', array_merge(request()->query(), ['view' => 'order'])) }}">Order view</x-admin.button>
            </div>
            <x-admin.button variant="outline-secondary" size="sm" type="button" id="print-report"
                            title="Print the filtered table; the browser print dialog can save it as PDF">
                <i class="icon-base ri ri-printer-line me-1"></i>Print / PDF
            </x-admin.button>
            <x-admin.button variant="outline-primary" size="sm"
                            href="{{ route('admin.report_export', array_merge(request()->except('page'), ['report' => 'order', 'format' => 'xlsx'])) }}"
                            title="Download every filtered row as a real .xlsx file">
                <i class="icon-base ri ri-file-excel-line me-1"></i>Excel
            </x-admin.button>
            <x-admin.button variant="outline-secondary" size="sm"
                            href="{{ route('admin.report_export', array_merge(request()->except('page'), ['report' => 'order', 'format' => 'csv'])) }}"
                            title="Download every filtered row as CSV">
                CSV
            </x-admin.button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            @if ($reportView === 'line')
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
                        <tr data-report-view="line">
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
            @else
            {{-- Order-level view: one row per DISTINCT filtered delivered order.
                 Money columns come from `orders` (amount already is
                 (subtotal + shipping) - discount, never re-summed from lines),
                 Lines/Qty/Sales describe the lines this filter kept. Same DOM id
                 as the line table so Print/Export keep working - only one of the
                 two tables exists in a render. --}}
            <table class="table table-hover align-middle mb-0 w-100" id="order-report-table">
                <thead>
                    <tr>
                        <th style="width:7%">Invoice</th>
                        <th style="width:8%">Date</th>
                        <th style="width:12%">Customer</th>
                        <th style="width:5%" class="text-end">Lines</th>
                        <th style="width:6%" class="text-end">Qty</th>
                        <th style="width:9%" class="text-end">Sales</th>
                        <th style="width:8%" class="text-end">Discount</th>
                        <th style="width:8%" class="text-end">Shipping</th>
                        <th style="width:9%" class="text-end">Order Total</th>
                        <th style="width:9%">Status</th>
                        <th style="width:9%">Payment</th>
                        <th style="width:10%">Courier</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orderViewRows as $entry)
                        @php($orderRow = $entry->row)
                        <tr data-report-view="order"
                            data-order-id="{{ $orderRow->id }}"
                            data-order-total="{{ number_format((float) $orderRow->amount, 2) }}"
                            data-order-discount="{{ number_format((float) $orderRow->discount, 2) }}"
                            data-order-shipping="{{ number_format((float) $orderRow->shipping_charge, 2) }}"
                            data-order-lines="{{ $entry->line_count }}"
                            data-order-qty="{{ $entry->qty }}">
                            <td>{{ $orderRow->invoice_id }}</td>
                            <td>{{ \Carbon\Carbon::parse($orderRow->created_at)->format('d M Y') }}</td>
                            <td>{{ $entry->shipping->name ?? '-' }}</td>
                            <td class="text-end">{{ $entry->line_count }}</td>
                            <td class="text-end">{{ $entry->qty }}</td>
                            <td class="text-end">{{ number_format($entry->sales, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $orderRow->discount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $orderRow->shipping_charge, 2) }}</td>
                            <td class="text-end fw-medium">{{ number_format((float) $orderRow->amount, 2) }}</td>
                            <td>{{ $entry->status }}</td>
                            <td>{{ $entry->payment }}</td>
                            <td>{{ $entry->courier }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No delivered orders found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- Totals belong with the table they describe, not in a second band of
         cards above it. Values cover the FILTER, not just this page. --}}
    <div class="border-top px-3 py-3 d-flex flex-wrap align-items-center gap-4 small">
        <span class="text-muted">
            Filtered totals · {{ number_format($orders->total()) }} lines
        </span>
        <span><span class="text-muted">Purchase:</span> <strong>{{ number_format((float) ($total_purchase ?? 0), 2) }}</strong></span>
        <span><span class="text-muted">Sales:</span> <strong>{{ number_format((float) ($total_sales ?? 0), 2) }}</strong></span>
        <span><span class="text-muted">Profit:</span> <strong class="{{ ((float) ($total_sales ?? 0) - (float) ($total_purchase ?? 0)) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) ($total_sales ?? 0) - (float) ($total_purchase ?? 0), 2) }}</strong></span>
        <span><span class="text-muted">Items:</span> <strong>{{ number_format((float) ($total_item ?? 0)) }}</strong></span>
    </div>

    @if($pagination->hasPages())
        <div class="card-footer border-top bg-white py-3 no-print">
            <div class="d-flex justify-content-end">
                {{ $pagination->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

{{--
    Print + Export live in shared external assets (no inline blocks):
      - stylesheet : public/admin/assets/css/admin-report.css
      - script     : public/admin/assets/js/admin-report-table.js
                     (the previous version loaded table2excel from a CDN and
                     called a printFunction() that lived in a @section('script')
                     the layout never yielded)
--}}
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-report.css') }}">
@endpush

@push('script')
<script src="{{ asset('admin/assets/js/admin-report-table.js') }}"
        data-report-table="order-report-table"></script>
@endpush
@endsection
