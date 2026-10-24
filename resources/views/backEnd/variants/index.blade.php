@extends('backEnd.layouts.app')
@section('title','Variants')

@section('content')

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Variants</h5>
        <a href="{{ route('admin.variants.create') }}" class="btn btn-primary btn-square waves-effect">
            <i class="ri ri-add-line me-1"></i> Create Variant
        </a>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">ID</th>
                        <th style="width:20%">SKU</th>
                        <th style="width:25%">Title</th>
                        <th style="width:20%">Product</th>
                        <th style="width:15%">Status</th>
                        <th style="width:15%" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($variants as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td class="fw-medium">{{ $v->sku ?? 'N/A' }}</td>
                        <td>{{ $v->title ?? 'N/A' }}</td>
                        <td>{{ $v->product_name ?? 'N/A' }}</td>
                        <td>
                            @if((int)$v->status === 1)
                                <span class="badge bg-success rounded-0">Active</span>
                            @else
                                <span class="badge bg-secondary rounded-0">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{ route('admin.variants.edit', $v->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
                                    <i class="icon-base ri ri-edit-box-line"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if(method_exists($variants, 'links') && $variants->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary mb-0">
                {{ $variants->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
