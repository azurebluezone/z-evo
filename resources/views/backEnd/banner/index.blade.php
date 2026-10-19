@extends('backEnd.layouts.app')
@section('title','Banner Manage')

@push('css')
<style>
    img.backend-image {
        width: 140px;
        height: 60px;
        object-fit: cover;
        border-radius: 0;
        background: #f3f4f6;
        display: inline-block;
        border: 1px solid #e0e0e0;
    }
</style>
@endpush

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Banners</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('banners.create') }}" class="btn btn-primary btn-square waves-effect">Create</a>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'bannerTable',
            'url'     => route('banner.datatable'),
            'title'   => 'Banners',
            'empty'   => 'No banners yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'desc']],
            'columns' => [
                ['label' => 'SL', 'orderable' => false, 'searchable' => false, 'width' => '5%'],
                ['label' => 'Category', 'width' => '25%'],
                ['label' => 'Media', 'orderable' => false, 'width' => '20%'],
                ['label' => 'Status', 'width' => '10%'],
                ['label' => 'Action', 'orderable' => false, 'class' => 'text-end', 'width' => '12%'],
            ],
        ])
</div>
@endsection
