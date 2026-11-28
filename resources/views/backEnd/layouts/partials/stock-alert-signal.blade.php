@php
    $stockSignal = app(\App\Services\Admin\StockAlertSignal::class)->state();
@endphp
@if ($stockSignal['active'])
<li class="nav-item dropdown-notifications navbar-dropdown dropdown me-4 me-xl-1" data-stock-signal="navbar">
    <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-danger rounded-pill" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Low stock">
        <i class="icon-base ri ri-error-warning-line icon-22px"></i>
        <span class="position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end py-0" style="min-width:320px;">
        <li class="dropdown-menu-header border-bottom py-50">
            <div class="dropdown-header d-flex align-items-center py-2">
                <h6 class="mb-0 me-auto">Low stock</h6>
                <div class="badge rounded-pill bg-label-danger">{{ number_format($stockSignal['count'], 0) }} products</div>
            </div>
        </li>
        <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush">
                @foreach ($stockSignal['items'] as $item)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <h6 class="small mb-0">{{ $item['product_name'] !== '' ? $item['product_name'] : 'missing product' }}</h6>
                                <small class="text-muted">{{ $item['sku'] !== '' ? $item['sku'] : 'missing variant' }}</small>
                            </div>
                            <span class="badge bg-label-danger align-self-center">{{ number_format($item['available'], 0) }} pcs</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </li>
        <li class="border-top">
            <div class="d-flex gap-2 p-2">
                <a href="{{ route('admin.reports.stock-alerts') }}" class="btn btn-sm btn-outline-primary flex-grow-1">View batch</a>
                <form method="post" action="{{ route('admin.reports.stock-alerts.dismiss') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                </form>
            </div>
        </li>
    </ul>
</li>
@endif
