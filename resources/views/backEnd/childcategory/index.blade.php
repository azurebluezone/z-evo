@extends('backEnd.layouts.app')
@section('title','Childcategory Manage')

@section('content')

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Childcategories</h5>
        <div class="d-flex gap-2 align-items-center">
            
            <form id="childcategoriesBulkDeleteForm" method="post" action="{{ route('childcategories.bulk-destroy') }}" class="d-inline mb-0">
                @csrf
                <x-admin.button variant="outline-danger" shape="square" ripple="true" class="delete-confirm" type="submit" title="Delete selected childcategories">
                    <i class="ri-delete-bin-line me-1"></i> Delete Selected
                </x-admin.button>
            </form>

            <a href="{{ route('childcategories.create') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri-add-line me-1"></i> Create Childcategory
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input checkall rounded-0" value="">
                            </div>
                        </th>
                        <th style="width:35%">Subcategory</th>
                        <th style="width:35%">Name</th>
                        <th style="width:10%">Status</th>
                        <th style="width:12%" class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($data as $key=>$value)
                    <tr>
                        <td>
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input checkbox rounded-0" name="ids[]" value="{{ $value->id }}" form="childcategoriesBulkDeleteForm">
                            </div>
                        </td>
                        <td>{{ $value->subcategory->subcategoryName ?? 'N/A' }}</td>
                        <td class="fw-medium">{{ $value->childcategoryName ?? 'N/A' }}</td>
                        <td>
                            @if((int) ($value->status ?? 0) === 1)
                                <form method="post" action="{{ route('childcategories.inactive') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                    <div class="form-check form-switch custom-switch-sharp m-0">
                                        <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active">
                                    </div>
                                </form>
                            @else
                                <form method="post" action="{{ route('childcategories.active') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                    <div class="form-check form-switch custom-switch-sharp m-0">
                                        <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" title="Inactive">
                                    </div>
                                </form>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{ route('childcategories.edit', $value->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
                                    <i class="icon-base ri ri-edit-box-line"></i>
                                </a>
                                <form method="post" action="{{ route('childcategories.destroy') }}" class="d-inline" id="deleteForm-{{ $value->id }}">
                                    @csrf
                                    <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                                        <i class="icon-base ri ri-delete-bin-7-line"></i>
                                    </x-admin.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if(method_exists($data, 'links'))
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary">
                {{ $data->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
