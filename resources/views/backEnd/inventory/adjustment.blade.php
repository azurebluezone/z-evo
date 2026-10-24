@extends('backEnd.layouts.app')
@section('title','Inventory Adjustment')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Inventory Adjustment</h5>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('admin.inventory.adjustment') }}" class="d-flex gap-2 align-items-center">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control rounded-0" style="min-width: 320px;" placeholder="Search product / sku / variant" />
                        @if(!empty($filters['variant_id']))
                            <input type="hidden" name="variant_id" value="{{ $filters['variant_id'] }}" />
                        @endif
                        <x-admin.button variant="outline-primary" shape="square" ripple="true" type="submit">
                            <i class="ri ri-search-line me-1"></i> Filter
                        </x-admin.button>
                        <a href="{{ route('admin.inventory.adjustment') }}" class="btn btn-outline-secondary btn-square waves-effect">Reset</a>
                    </form>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri ri-arrow-left-line me-1"></i> Back to Stock
                    </a>
                </div>
            </div>

            <div class="card-body mt-4">
                <div class="card rounded-0 border-info shadow-none mb-4">
                    <div class="card-body text-info d-flex align-items-center">
                        <i class="ri ri-information-line fs-4 me-2"></i>
                        <span>
                            <strong>What this does:</strong> Writes an <b>ADJUSTMENT</b> row to <code>inventory_movements</code>, updates <code>variant_stocks</code>, and syncs legacy <code>products.stock</code>.
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.inventory.adjustment.store') }}" class="row g-4">
                    @csrf

                    <div class="col-12">
                        <label class="form-label fw-semibold">Variant *</label>
                        <select name="variant_id" class="form-control rounded-0 form-select @error('variant_id') is-invalid @enderror">
                            <option value="">Select variant</option>
                            @foreach($variants as $v)
                                <option value="{{ $v->id }}" {{ (string) old('variant_id', $filters['variant_id'] ?? '') === (string) $v->id ? 'selected' : '' }}>
                                    #{{ $v->id }} — {{ $v->sku ?? 'N/A' }} — {{ $v->product_name ?? 'N/A' }}{{ $v->title ? ' / ' . $v->title : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('variant_id')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Movement Type *</label>
                        <select name="type" class="form-control rounded-0 form-select @error('type') is-invalid @enderror">
                            <option value="ADJUSTMENT" {{ old('type', 'ADJUSTMENT') === 'ADJUSTMENT' ? 'selected' : '' }}>Adjustment</option>
                            <option value="RECEIVE" {{ old('type') === 'RECEIVE' ? 'selected' : '' }}>Receive</option>
                            <option value="DAMAGE" {{ old('type') === 'DAMAGE' ? 'selected' : '' }}>Damage</option>
                            <option value="LOST" {{ old('type') === 'LOST' ? 'selected' : '' }}>Lost</option>
                        </select>
                        @error('type')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline mt-md-4 mt-0">
                            <input type="number" name="qty_delta" id="qty_delta" class="form-control rounded-0 @error('qty_delta') is-invalid @enderror" value="{{ old('qty_delta') }}" placeholder="Qty Delta" />
                            <label for="qty_delta">Qty Delta *</label>
                        </div>
                        <div class="form-text text-muted mt-1"><i class="ri ri-information-line"></i> Use positive to add stock, negative to reduce.</div>
                        @error('qty_delta')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline mt-md-4 mt-0">
                            <input type="text" name="note" id="note" class="form-control rounded-0 @error('note') is-invalid @enderror" value="{{ old('note') }}" placeholder="Note (Optional)" />
                            <label for="note">Note (Optional)</label>
                        </div>
                        @error('note')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                            <i class="ri ri-check-line me-1"></i> Apply Adjustment
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
