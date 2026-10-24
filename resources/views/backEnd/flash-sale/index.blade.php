@extends('backEnd.layouts.app')
@section('title','Flash Sale Manage')

@section('content')

{{--
    Headline numbers.
    The counts come from the same model scopes the storefront uses
    (FlashSale::active() / ::running()), so this row cannot claim a sale is
    live when the frontend disagrees. "Running now" means status is on AND
    now() is inside the start/end window.
--}}
<div class="row g-6 mb-6">
    <!-- Total -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-primary">
                            <i aria-hidden="true" class="icon-base ri ri-flashlight-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ $stats['total'] }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Total Flash Sales</h6>
                <p class="mb-0">
                    <small class="text-body-secondary">All campaigns</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Active (switch on) -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-success">
                            <i aria-hidden="true" class="icon-base ri ri-check-double-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ $stats['active'] }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Active</h6>
                <p class="mb-0">
                    <small class="text-body-secondary">Switch is on</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Running now (inside the time window) -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded-3 bg-label-info">
                            <i aria-hidden="true" class="icon-base ri ri-timer-flash-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ $stats['running'] }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Running Now</h6>
                <p class="mb-0">
                    <small class="text-body-secondary">
                        @if($stats['running'] > 0)
                            <span class="badge bg-label-success">Live on storefront</span>
                        @else
                            No live campaign
                        @endif
                    </small>
                </p>
            </div>
        </div>
    </div>

    <!-- Create -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 bg-label-primary border-primary">
            <div class="card-body">
                <h6 class="mb-2 fw-semibold text-primary">New Campaign</h6>
                <p class="mb-3 small">One time window, up to 10 products per sale.</p>
                <a href="{{ route('flash-sales.create') }}" class="btn btn-primary btn-square waves-effect w-100">
                    <i aria-hidden="true" class="ri ri-add-line me-1"></i> Create Flash Sale
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Flash Sales</h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-label-secondary">{{ $stats['products'] }} {{ \Illuminate\Support\Str::plural('product', $stats['products']) }} linked</span>
            <a href="{{ route('flash-sales.create') }}" class="btn btn-primary btn-square waves-effect">
                <i aria-hidden="true" class="ri ri-add-line me-1"></i> Create Flash Sale
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">SL</th>
                        <th style="width:25%">Title</th>
                        <th style="width:15%">Start</th>
                        <th style="width:15%">End</th>
                        <th style="width:10%">Products</th>
                        <th style="width:10%">Status</th>
                        <th style="width:15%" class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($flash_sales as $sale)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td class="fw-medium">
                            {{$sale->title ?? 'N/A'}}
                            @if($sale->isRunning())
                                <span class="badge bg-label-success ms-1">Live</span>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $sale->start_time ? $sale->start_time->format('Y-m-d H:i') : '' }}</td>
                        <td class="text-nowrap">{{ $sale->end_time ? $sale->end_time->format('Y-m-d H:i') : '' }}</td>
                        <td>{{ $sale->products_count ?? '' }}</td>
                        <td>
                            @if(($sale->status ?? 0) == 1)
                            <form method="post" action="{{route('flash-sales.inactive')}}" class="d-inline">
                            @csrf
                            <input type="hidden" value="{{$sale->id}}" name="hidden_id">
                            <div class="form-check form-switch custom-switch-sharp m-0">
                                <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active" aria-label="Deactivate {{ $sale->title }}">
                            </div>
                            </form>
                            @else
                            <form method="post" action="{{route('flash-sales.active')}}" class="d-inline">
                                @csrf
                            <input type="hidden" value="{{$sale->id}}" name="hidden_id">
                            <div class="form-check form-switch custom-switch-sharp m-0">
                                <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" title="Inactive" aria-label="Activate {{ $sale->title }}">
                            </div>
                            </form>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{route('flash-sales.edit',$sale->id)}}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit">
                                    <i aria-hidden="true" class="icon-base ri ri-edit-line"></i>
                                </a>

                                <form method="post" action="{{route('flash-sales.destroy')}}" class="d-inline" id="deleteForm-{{ $sale->id }}">
                                    @csrf
                                    <input type="hidden" value="{{$sale->id}}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $sale->id }}')">
                                        <i aria-hidden="true" class="icon-base ri ri-delete-bin-line"></i>
                                    </x-admin.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-6">
                            <i aria-hidden="true" class="icon-base ri ri-flashlight-line icon-24px text-body-secondary d-block mb-2"></i>
                            <h6 class="mb-1">No flash sale yet</h6>
                            <p class="text-body-secondary small mb-3">A campaign needs a title, a time window and at least one product.</p>
                            <a href="{{ route('flash-sales.create') }}" class="btn btn-sm btn-primary btn-square waves-effect">
                                <i aria-hidden="true" class="ri ri-add-line me-1"></i> Create the first one
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($flash_sales, 'links'))
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary">
                {{ $flash_sales->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
