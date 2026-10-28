@extends('backEnd.layouts.app')
@section('title','Product Manage')
@push('css')
<style>
    img.backend-image {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 0;
        background: #f3f4f6;
        display: inline-block;
        border: 1px solid #e0e0e0;
    }
    #productsTable td {
        vertical-align: middle;
    }
</style>
@endpush
@section('content')

<!-- Forms needed for external triggering -->
@if(isset($generalSetting) && $generalSetting->home_page_hot_deal_section == 1)
    <form id="hotDealToggleForm" method="post" action="{{route('settings.toggle-hot-deal-section')}}" class="d-none">
        @csrf
    </form>
@else
    <form id="hotDealToggleForm" method="post" action="{{route('settings.toggle-hot-deal-section')}}" class="d-none">
        @csrf
    </form>
@endif

{{-- One KPI band only: the shared stat-grid (backEnd/partials/stat-grid,
     included by the layout) already shows Total/Active/Inactive for this
     module. The page-local card-widget-separator band that repeated the
     same three numbers (plus Hot Deals) was removed 2026-10-28; the Hot
     Deals count stays visible in the card-header badge below. --}}

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 text-uppercase fw-bold">Manage Products</h5>
            <span class="badge bg-label-warning">{{ isset($product_kpis) ? $product_kpis->hot_deals : 0 }} hot deals</span>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            
            <x-admin.button variant="primary" shape="square" ripple="true" class="{{ (isset($generalSetting) && $generalSetting->home_page_hot_deal_section == 1) ? 'btn-outline-success' : 'btn-outline-warning' }} change-confirm" type="button" onclick="document.getElementById('hotDealToggleForm').submit();">
                <i class="ri ri-fire-line me-1"></i> {{ (isset($generalSetting) && $generalSetting->home_page_hot_deal_section == 1) ? 'Hide Hot Deals' : 'Show Hot Deals' }}
            </x-admin.button>

            <a href="{{route('products.create')}}" class="btn btn-primary btn-square waves-effect">
                <i class="ri ri-add-line me-1"></i> Add Product
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="bg-light px-3 py-2 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <x-admin.button variant="outline-secondary" shape="square" class="dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Bulk Actions
                    </x-admin.button>
                    <ul class="dropdown-menu rounded-0" aria-labelledby="bulkActionsDropdown">
                        {{--
                            Bulk actions are POSTs, so the value they set travels in
                            the request body as data-status rather than in the
                            URL of a link. The endpoints were GETs; a link was
                            enough to fire them.
                        --}}
                        <li><button type="button" class="dropdown-item hotdeal_update" data-url="{{route('products.update_deals')}}" data-status="1">Mark as Hot Deal</button></li>
                        <li><button type="button" class="dropdown-item hotdeal_update" data-url="{{route('products.update_deals')}}" data-status="0">Remove Hot Deal</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="button" class="dropdown-item update_status" data-url="{{route('products.update_status')}}" data-status="1">Set Active</button></li>
                        <li><button type="button" class="dropdown-item update_status" data-url="{{route('products.update_status')}}" data-status="0">Set Inactive</button></li>
                    </ul>
                </div>

                <x-admin.button variant="outline-secondary" shape="square" type="button" data-bs-toggle="offcanvas" data-bs-target="#productFilters" aria-controls="productFilters">
                    <i class="ri ri-filter-3-line me-1"></i> Filters
                </x-admin.button>
            </div>

            <form class="d-flex align-items-center gap-2" method="get" action="{{ route('products.index') }}">
                <input type="text" name="keyword" class="form-control rounded-0" placeholder="Search sku, name..." value="{{ request('keyword') }}" style="min-width: 220px;">
                <x-admin.button variant="info" shape="square" type="submit">Search</x-admin.button>
                <div class="dropdown">
                    <x-admin.button variant="outline-secondary" shape="square" class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Export
                    </x-admin.button>
                    <ul class="dropdown-menu dropdown-menu-end rounded-0">
                        <li><a class="dropdown-item" href="javascript:void(0)">CSV</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)">Excel</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)">PDF</a></li>
                    </ul>
                </div>
            </form>
        </div>

        {{-- Same shared grid the orders screen uses: columns and endpoint only. --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'productTable',
            'url'     => route('products.datatable'),
            'title'   => 'Products',
            'empty'   => 'No products yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'desc']],
            // Price and Stock cells are click-to-edit; this loads the handler
            // that swaps them for inputs and saves via products.quick_update.
            'inlineEdit' => true,
            'columns' => [
                ['label' => '', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '2%'],
                ['label' => 'Name', 'width' => '25%'],
                ['label' => 'Category', 'class' => 'd-none d-md-table-cell', 'width' => '10%'],
                ['label' => 'Image', 'class' => 'd-none d-md-table-cell', 'width' => '10%'],
                ['label' => 'Price', 'class' => 'd-none d-sm-table-cell', 'width' => '10%'],
                ['label' => 'Stock', 'class' => 'd-none d-sm-table-cell', 'width' => '8%'],
                ['label' => 'Feature', 'width' => '14%'],
                ['label' => 'Status', 'width' => '8%'],
                ['label' => 'Action', 'orderable' => false, 'width' => '10%'],
            ],
        ])
    </div>
</div>

<!-- Offcanvas For Filters -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="productFilters" aria-labelledby="productFiltersLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="productFiltersLabel" class="offcanvas-title fw-bold">Filters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select rounded-0">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <x-admin.button variant="primary" shape="square" class="w-50" type="button" data-bs-dismiss="offcanvas">Apply</x-admin.button>
            <x-admin.button variant="outline-secondary" shape="square" class="w-50" type="button" data-bs-dismiss="offcanvas">Close</x-admin.button>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // DataTable disabled - using server-side pagination (10 items per page)

        function toastSuccess(message) {
            if (window.toastr && typeof window.toastr.success === 'function') {
                window.toastr.success(message);
            }
        }

        function toastError(message) {
            if (window.toastr && typeof window.toastr.error === 'function') {
                window.toastr.error(message);
            }
        }

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function getSelectedProductIds() {
            return Array.from(document.querySelectorAll('input.checkbox:checked')).map(function (el) {
                return el.value;
            });
        }

        var checkAll = document.querySelector('.checkall');
        if (checkAll) {
            checkAll.addEventListener('change', function (e) {
                var checked = !!e.target.checked;
                document.querySelectorAll('input.checkbox').forEach(function (cb) {
                    cb.checked = checked;
                });
            });
        }

        document.addEventListener('change', function (e) {
            var target = e.target;
            if (!(target instanceof HTMLElement)) return;

            if (target.classList.contains('js-auto-submit')) {
                var form = target.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });

        document.addEventListener('change', function (e) {
            var el = e.target;
            if (!(el instanceof HTMLInputElement)) return;
            if (!el.classList.contains('js-product-flag')) return;

            var isOn = el.checked;
            var url = isOn ? el.getAttribute('data-url-on') : el.getAttribute('data-url-off');
            var productId = el.getAttribute('data-product-id');
            if (!url || !productId) return;

            var body = new URLSearchParams();
            body.append('product_ids[]', productId);
            // The flag the switch is asking for. It used to ride in the query
            // string of a GET; the endpoint is a POST now, so it is body data.
            var src = isOn ? el.getAttribute('data-status-on') : el.getAttribute('data-status-off');
            if (src !== null && src !== '') body.append('status', src);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.status === 'success') {
                        toastSuccess(res.message || 'Updated');
                    } else {
                        toastError('Failed something wrong');
                        el.checked = !isOn;
                    }
                })
                .catch(function () {
                    toastError('Failed something wrong');
                    el.checked = !isOn;
                });
        });

        document.addEventListener('click', function (e) {
            var a = e.target && (e.target.closest ? e.target.closest('.hotdeal_update, .update_status') : null);
            if (!a) return;

            e.preventDefault();

            var url = a.getAttribute('data-url');
            var status = a.getAttribute('data-status');
            var productIds = getSelectedProductIds();
            if (!productIds.length) {
                toastError('Please Select A Product First !');
                return;
            }

            var body = new URLSearchParams();
            productIds.forEach(function (id) { body.append('product_ids[]', id); });
            body.append('status', status);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.status === 'success') {
                        toastSuccess(res.message || 'Updated');
                        window.location.reload();
                    } else {
                        toastError('Failed something wrong');
                    }
                })
                .catch(function () {
                    toastError('Failed something wrong');
                });
        });
    });
</script>
@endpush
