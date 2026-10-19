@extends('backEnd.layouts.app')
@section('title','Coupon Manage')

@section('content')

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Coupons</h5>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('coupons.create') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri-add-line me-1"></i> Create Coupon
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'couponTable',
            'url'     => route('coupon.datatable'),
            'title'   => 'Coupons',
            'empty'   => 'No coupons yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'desc']],
            'columns' => [
                ['label' => 'SL', 'orderable' => false, 'searchable' => false, 'width' => '5%'],
                ['label' => 'Code', 'width' => '15%'],
                ['label' => 'Type', 'width' => '10%'],
                ['label' => 'Value', 'orderable' => false, 'width' => '10%'],
                ['label' => 'Min', 'orderable' => false, 'width' => '10%'],
                ['label' => 'Max', 'orderable' => false, 'width' => '10%'],
                ['label' => 'Start', 'orderable' => false, 'width' => '10%'],
                ['label' => 'End', 'orderable' => false, 'width' => '10%'],
                ['label' => 'Status', 'width' => '10%'],
                ['label' => 'Action', 'orderable' => false, 'class' => 'text-end', 'width' => '15%'],
            ],
        ])
    </div>
</div>
@endsection
