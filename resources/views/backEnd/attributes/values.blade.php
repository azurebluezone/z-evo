@extends('backEnd.layouts.app')
@section('title','Attribute Values')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card rounded-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <div>
                    <h5 class="mb-0 text-uppercase fw-bold">Values: {{ $attribute->name ?? 'N/A' }}</h5>
                    <div class="text-muted small mt-1">{{ $attribute->slug ?? 'N/A' }}</div>
                </div>
                <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Attributes
                </a>
            </div>

            <div class="card-body mt-4">
                <form method="POST" action="{{ route('admin.attributes.values.store', $attribute->id) }}" class="row g-4">
                    @csrf
                    
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="value" id="value" class="form-control rounded-0 @error('value') is-invalid @enderror" value="{{ old('value') }}" placeholder="Value" />
                            <label for="value">Value *</label>
                        </div>
                        @error('value')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="code" id="code" class="form-control rounded-0 @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Code (Optional)" />
                            <label for="code">Code (Optional)</label>
                        </div>
                        @error('code')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="multiplier" id="multiplier" class="form-control rounded-0 @error('multiplier') is-invalid @enderror" value="{{ old('multiplier') }}" min="1" placeholder="Multiplier (Optional)" />
                            <label for="multiplier">Multiplier</label>
                        </div>
                        @error('multiplier')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="base_unit" id="base_unit" class="form-control rounded-0 @error('base_unit') is-invalid @enderror" value="{{ old('base_unit') }}" placeholder="Base Unit (Optional)" />
                            <label for="base_unit">Base Unit</label>
                        </div>
                        @error('base_unit')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="sort_order" id="sort_order" class="form-control rounded-0 @error('sort_order') is-invalid @enderror" value="{{ old('sort_order','0') }}" min="0" placeholder="Sort" />
                            <label for="sort_order">Sort</label>
                        </div>
                        @error('sort_order')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                            <i class="ri-save-line me-1"></i> Add Value
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>

        @if(($attribute->kind ?? 'custom') === 'system')
        <div class="alert alert-secondary rounded-0 alert-dismissible fade show" role="alert">
            This is a system attribute. Seeded values are locked for consistency, but you can add new values.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card rounded-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Value</th>
                                <th>Code</th>
                                <th>Multiplier</th>
                                <th>Base Unit</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($values as $v)
                            <tr>
                                <td>{{ $v->id }}</td>
                                <td class="fw-medium">{{ $v->value ?? 'N/A' }}</td>
                                <td>{{ $v->code ?? 'N/A' }}</td>
                                <td>{{ $v->multiplier ?? 'N/A' }}</td>
                                <td>{{ $v->base_unit ?? 'N/A' }}</td>
                                <td>
                                    @if(($v->kind ?? 'custom') === 'system')
                                        <span class="badge bg-secondary rounded-0">System</span>
                                    @else
                                        <span class="badge bg-info rounded-0">Custom</span>
                                    @endif
                                </td>
                                <td>
                                    @if((int)($v->status ?? 1) === 1)
                                        <span class="badge bg-success rounded-0">Active</span>
                                    @else
                                        <span class="badge bg-secondary rounded-0">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                        @if(($v->kind ?? 'custom') !== 'system')
                                            <form action="{{ route('admin.attributes.values.destroy', [$attribute->id, $v->id]) }}" method="POST" class="d-inline mb-0" onsubmit="return confirm('Are you sure you want to delete this value?');">
                                                @csrf
                                                <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="submit" title="Delete">
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
            </div>
        </div>


    </div>
</div>
@endsection
