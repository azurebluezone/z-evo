@extends('backEnd.layouts.app')
@section('title','Category Manage')

@section('content')
<!-- Forms needed for external triggering -->
<form id="categoriesHomePageSetOnlyForm" method="post" action="{{ route('categories.home-page-set-only') }}" class="d-none">
    @csrf
</form>
<form id="categoriesBulkDeleteForm" method="post" action="{{ route('categories.bulk-destroy') }}" class="d-none">
    @csrf
</form>

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Categories</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <form method="post" action="{{ route('settings.toggle-top-categories-section') }}" class="m-0">
                @csrf
                <div class="form-check form-switch custom-switch-sharp m-0">
                    <input type="checkbox" class="form-check-input js-auto-submit" {{ (isset($generalSetting) && (int) $generalSetting->home_page_top_categories_section === 1) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold">Home Section</label>
                </div>
            </form>

            <x-admin.button variant="outline-warning" shape="square" ripple="true" type="submit" form="categoriesHomePageSetOnlyForm">
                <i class="ri-home-gear-line"></i> Set Home (2)
            </x-admin.button>

            <x-admin.button variant="outline-danger" shape="square" ripple="true" type="button" onclick="triggerDeleteModal('categoriesBulkDeleteForm')">
                <i class="ri-delete-bin-line"></i> Bulk Delete
            </x-admin.button>

            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri-add-line"></i> Create New
            </a>
        </div>
    </div>
  
  <div class="card-body p-0">
        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'categoryTable',
            'url'     => route('category.datatable'),
            'title'   => 'Departments',
            'empty'   => 'No departments yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'asc']],
            'columns' => [
                ['label' => '', 'orderable' => false, 'searchable' => false],
                ['label' => 'Name'],
                ['label' => 'Image', 'orderable' => false],
                ['label' => 'Home'],
                ['label' => 'Status'],
                ['label' => 'Action', 'orderable' => false, 'class' => 'text-end'],
            ],
        ])

</div>

@endsection
