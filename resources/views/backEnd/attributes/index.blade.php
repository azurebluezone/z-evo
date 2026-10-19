@extends('backEnd.layouts.app')
@section('title','Attributes')

@section('content')

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Attributes</h5>
        <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary btn-square waves-effect">
            <i class="ri-add-line me-1"></i> Create Attribute
        </a>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">ID</th>
                        <th style="width:25%">Name</th>
                        <th style="width:20%">Slug</th>
                        <th style="width:15%">Type</th>
                        <th style="width:15%">Status</th>
                        <th style="width:20%" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attributes as $a)
                    <tr>
                        <td>{{ $a->id }}</td>
                        <td class="fw-medium">{{ $a->name ?? 'N/A' }}</td>
                        <td>{{ $a->slug ?? 'N/A' }}</td>
                        <td>
                            @if(($a->kind ?? 'custom') === 'system')
                                <span class="badge bg-secondary rounded-0">System</span>
                            @else
                                <span class="badge bg-info rounded-0">Custom</span>
                            @endif
                        </td>
                        <td>
                            @if((int)($a->status ?? 1) === 1)
                                <span class="badge bg-success rounded-0">Active</span>
                            @else
                                <span class="badge bg-secondary rounded-0">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{ route('admin.attributes.values', $a->id) }}" class="btn btn-sm btn-outline-info btn-square waves-effect" title="Values">
                                    <i class="ri-list-check"></i> Values
                                </a>
                                @if(($a->kind ?? 'custom') !== 'system')
                                    <a href="{{ route('admin.attributes.edit', $a->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
                                        <i class="icon-base ri ri-edit-box-line"></i>
                                    </a>
                                    <form action="{{ route('admin.attributes.destroy', $a->id) }}" method="POST" class="d-inline mb-0" id="deleteForm-{{ $a->id }}">
                                        @csrf
                                        <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $a->id }}')">
                                            <i class="icon-base ri ri-delete-bin-7-line"></i>
                                        </x-admin.button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($attributes, 'links') && $attributes->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary mb-0">
                {{ $attributes->links() }}
            </div>
        </div>
        @endif
    </div>
</div>



@endsection
