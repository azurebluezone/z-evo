@extends('backEnd.layouts.app')
@section('title','Invoices')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Invoices</h4>
        </div>

        <div class="card mb-3">
            <div class="card-widget-separator-wrapper">
                <div class="card-body card-widget-separator">
                    <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                                <div>
                                    <h4 class="mb-0">{{ number_format($invoice_kpis->clients ?? 0) }}</h4>
                                    <p class="mb-0">Clients</p>
                                </div>
                                <div class="avatar me-sm-6">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-user-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                                <div>
                                    <h4 class="mb-0">{{ number_format($invoice_kpis->invoices ?? 0) }}</h4>
                                    <p class="mb-0">Invoices</p>
                                </div>
                                <div class="avatar me-lg-6">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-pages-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                                <div>
                                    <h4 class="mb-0">৳{{ number_format((float) ($invoice_kpis->paid_amount ?? 0)) }}</h4>
                                    <p class="mb-0">Paid</p>
                                </div>
                                <div class="avatar me-sm-6">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-wallet-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-0">{{ number_format((int) ($invoice_kpis->unpaid_count ?? 0)) }}</h4>
                                    <p class="mb-0">Unpaid</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded-3 text-heading">
                                        <i class="icon-base ri ri-money-dollar-circle-line icon-26px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="sticky-top bg-white" style="top: 0; z-index: 9;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-sm">
                                Orders
                            </a>
                        </div>

                        <form class="d-flex align-items-center gap-2" method="get" action="{{ route('admin.invoices.index') }}">
                            <input type="text" name="keyword" class="form-control form-control-sm" placeholder="Search invoice/phone/name" value="{{ request('keyword') }}" style="min-width: 0; width: 100%; max-width: 320px;">
                            <x-admin.button variant="info" size="sm" type="submit">Search</x-admin.button>
                        </form>
                    </div>
                    <hr class="my-0">
                </div>

                <div class="card-datatable table-responsive">
                    <table class="table table-striped table-sm w-100 align-middle">
                        <thead>
                            <tr>
                                <th style="width:40px" class="text-center">
                                    <input class="form-check-input" type="checkbox" id="invoice-check-all" aria-label="Select all">
                                </th>
                                <th style="width:12%" class="text-nowrap">Invoice</th>
                                <th style="width:26%" class="text-nowrap">Client</th>
                                <th style="width:12%" class="d-none d-md-table-cell">Total</th>
                                <th style="width:12%" class="d-none d-md-table-cell text-nowrap">Payment</th>
                                <th style="width:14%" class="text-nowrap">Status</th>
                                <th style="width:56px" class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td class="text-center">
                                        <input class="form-check-input invoice-check" type="checkbox" name="ids[]" value="{{ $inv->id }}" aria-label="Select invoice">
                                    </td>
                                    <td>
                                        <div class="fw-medium">#{{ $inv->invoice_id }}</div>
                                        <div class="text-muted small d-md-none">৳{{ number_format((float) ($inv->amount ?? 0)) }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $inv->shipping ? $inv->shipping->name : '' }}</div>
                                        <div class="text-muted small">{{ $inv->shipping ? $inv->shipping->phone : '' }}</div>
                                    </td>
                                    <td class="d-none d-md-table-cell">৳{{ number_format((float) ($inv->amount ?? 0)) }}</td>
                                    <td class="d-none d-md-table-cell">
                                        @php($p = $inv->payment ? ($inv->payment->payment_status ?? '') : '')
                                        @if($p === 'paid')
                                            <span class="badge bg-soft-success text-success">Paid</span>
                                        @elseif($p === 'pending')
                                            <span class="badge bg-soft-warning text-warning">Pending</span>
                                        @else
                                            <span class="badge bg-soft-secondary text-secondary">{{ $p }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $inv->status ? $inv->status->name : '' }}</span>
                                        <div class="text-muted small d-md-none">
                                            @php($p = $inv->payment ? ($inv->payment->payment_status ?? '') : '')
                                            {{ $p ? ucfirst($p) : '' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <x-admin.button variant="text-secondary" size="sm" shape="icon" class="dropdown-toggle hide-arrow" type="button" style="padding: .25rem; width: 32px; height: 32px;" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="icon-base ri ri-more-2-fill"></i>
                                            </x-admin.button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="{{ route('admin.order.invoice', ['invoice_id' => $inv->invoice_id]) }}">
                                                    <i class="ri-eye-line me-2"></i>View Invoice
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.order.process', ['invoice_id' => $inv->invoice_id]) }}">
                                                    <i class="ri-settings-3-line me-2"></i>Process
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.order.edit', ['invoice_id' => $inv->invoice_id]) }}">
                                                    <i class="ri-edit-box-line me-2"></i>Edit
                                                </a>

                                                <div class="dropdown-divider"></div>

                                                <form method="post" action="{{ route('admin.order.destroy') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $inv->id }}">
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm">
                                                        <i class="ri-delete-bin-7-line me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="custom-paginate">
                    {{ $invoices->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  (function () {
    const checkAll = document.getElementById('invoice-check-all');
    if (!checkAll) return;
    const items = () => Array.from(document.querySelectorAll('.invoice-check'));
    checkAll.addEventListener('change', function () {
      items().forEach(cb => { cb.checked = checkAll.checked; });
    });
  })();
</script>
@endsection
