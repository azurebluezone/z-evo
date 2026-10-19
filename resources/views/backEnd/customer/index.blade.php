@extends('backEnd.layouts.app')
@section('title','Customer Manage')

@section('content')
<!-- Customer Statistics -->
<div class="row g-4 mb-4">
    <!-- Total Customers -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded-3 bg-label-primary">
                            <i class="icon-base ri ri-user-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ number_format($show_data->total()) }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Total Customers</h6>
            </div>
        </div>
    </div>

    <!-- Active Customers -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded-3 bg-label-success">
                            <i class="icon-base ri ri-user-follow-line icon-24px"></i>
                        </span>
                    </div>
                    @php
                        $activeCount = $show_data->getCollection()->where('status', 'active')->count();
                    @endphp
                    <h4 class="mb-0">{{ number_format($activeCount) }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Active Customers</h6>
            </div>
        </div>
    </div>

    <!-- Inactive Customers -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded-3 bg-label-warning">
                            <i class="icon-base ri ri-user-unfollow-line icon-24px"></i>
                        </span>
                    </div>
                    @php
                        $inactiveCount = $show_data->getCollection()->where('status', '!=', 'active')->count();
                    @endphp
                    <h4 class="mb-0">{{ number_format($inactiveCount) }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Inactive Customers</h6>
            </div>
        </div>
    </div>

    <!-- This Page -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded-3 bg-label-info">
                            <i class="icon-base ri ri-file-list-line icon-24px"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ $show_data->count() }}</h4>
                </div>
                <h6 class="mb-0 fw-normal">Showing This Page</h6>
            </div>
        </div>
    </div>
</div>

<!-- Customer List -->
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Customers</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <form class="d-flex align-items-center gap-2 m-0" method="get" action="{{ route('customers.index') }}">
                <input type="text" name="keyword" class="form-control rounded-0" placeholder="Search phone/name" value="{{ request('keyword') }}" style="min-width: 220px;">
                <x-admin.button variant="info" shape="square" type="submit">Search</x-admin.button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'customerTable',
            'url'     => route('customers.datatable'),
            'title'   => 'Customers',
            'empty'   => 'No customers yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'asc']],
            'columns' => [
                ['label' => 'SL', 'orderable' => false, 'searchable' => false],
                ['label' => 'Customer'],
                ['label' => 'Phone'],
                ['label' => 'Email'],
                ['label' => 'Status'],
                ['label' => 'Actions', 'orderable' => false, 'class' => 'text-end'],
            ],
        ])
</div>

@endsection
