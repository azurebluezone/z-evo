@extends('backEnd.layouts.app')
@section('title','Brand Manage')

@section('content')

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Brands</h5>
        <div class="d-flex gap-2 align-items-center">
            
            @if(isset($generalSetting) && $generalSetting->home_page_brands_section == 1)
            <form method="post" action="{{route('settings.toggle-brands-section')}}" class="d-inline mb-0">
                @csrf
                <x-admin.button variant="outline-danger" shape="square" ripple="true" class="change-confirm" type="button" title="Brands section is visible on home page">
                    <i class="ri-eye-off-line me-1"></i> Hide from Home
                </x-admin.button>
            </form>
            @else
            <form method="post" action="{{route('settings.toggle-brands-section')}}" class="d-inline mb-0">
                @csrf
                <x-admin.button variant="outline-success" shape="square" ripple="true" class="change-confirm" type="button" title="Brands section is hidden from home page">
                    <i class="ri-eye-line me-1"></i> Show on Home
                </x-admin.button>
            </form>
            @endif

            <a href="{{ route('brands.create') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri-add-line me-1"></i> Create Brand
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'brandTable',
            'url'     => route('brand.datatable'),
            'title'   => 'Brands',
            'empty'   => 'No brands yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'asc']],
            'columns' => [
                ['label' => 'SL', 'orderable' => false, 'searchable' => false, 'width' => '5%'],
                ['label' => 'Name', 'width' => '25%'],
                ['label' => 'Image', 'orderable' => false, 'width' => '15%'],
                ['label' => 'Status', 'width' => '10%'],
                ['label' => 'Action', 'orderable' => false, 'class' => 'text-end', 'width' => '15%'],
            ],
        ])
    </div>
</div>
@endsection

