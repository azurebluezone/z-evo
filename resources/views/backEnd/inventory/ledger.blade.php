@extends('backEnd.layouts.app')
@section('title','Variant Ledger')

@section('content')
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h4 class="mb-0 text-uppercase fw-bold">Variant Ledger</h4>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.inventory.adjustment', ['variant_id' => $variant->id]) }}" class="btn btn-primary btn-square waves-effect">
        <i class="ri ri-settings-4-line me-1"></i> Adjust
      </a>
      <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
        <i class="ri ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card rounded-0">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3">
          <div>
            <div class="fw-semibold">Variant</div>
            <div class="text-muted">#{{ $variant->id }} — {{ $variant->sku ?? 'N/A' }} — {{ $variant->product_name ?? 'N/A' }}{{ $variant->title ? ' / ' . $variant->title : '' }}</div>
          </div>
          <div class="d-flex flex-wrap gap-3">
            <div>
              <div class="fw-semibold">On Hand</div>
              <div class="fw-bold">{{ (int) ($stock->on_hand ?? 0) }}</div>
            </div>
            <div>
              <div class="fw-semibold">Available</div>
              <div class="fw-bold">{{ (int) ($stock->available ?? 0) }}</div>
            </div>
            <div>
              <div class="fw-semibold">Damaged</div>
              <div class="fw-bold text-danger">{{ (int) ($stock->damaged ?? 0) }}</div>
            </div>
            <div>
              <div class="fw-semibold">Reserved (Active TTL)</div>
              <div class="fw-bold text-warning">{{ (int) ($activeReserved ?? 0) }}</div>
            </div>
          </div>
        </div>

        <form method="GET" action="{{ route('admin.inventory.ledger') }}" class="row g-3 mt-3">
          <input type="hidden" name="variant_id" value="{{ $variant->id }}" />

          <div class="col-md-3">
            <label class="form-label fw-semibold">From</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control rounded-0" />
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">To</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control rounded-0" />
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Type</label>
            <input type="text" name="type" value="{{ $filters['type'] ?? '' }}" class="form-control rounded-0" placeholder="e.g. RETURN" />
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Bucket</label>
            <select name="bucket" class="form-control rounded-0 form-select">
              <option value="" {{ ($filters['bucket'] ?? '') === '' ? 'selected' : '' }}>All</option>
              <option value="sellable" {{ ($filters['bucket'] ?? '') === 'sellable' ? 'selected' : '' }}>sellable</option>
              <option value="damaged" {{ ($filters['bucket'] ?? '') === 'damaged' ? 'selected' : '' }}>damaged</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Created By (User ID)</label>
            <input type="number" name="created_by" value="{{ $filters['created_by'] ?? '' }}" class="form-control rounded-0" placeholder="e.g. 1" />
          </div>
          <div class="col-12">
            <x-admin.button variant="success" shape="square" ripple="true" type="submit">
              <i class="ri ri-search-line me-1"></i> Filter
            </x-admin.button>
          </div>
        </form>

        @if(isset($recentSummary) && count($recentSummary) > 0)
        <div class="mt-4">
          <div class="fw-semibold mb-2">Recent Summary (Last 7 days)</div>
          <div class="d-flex flex-wrap gap-2">
            @foreach($recentSummary as $rs)
              <span class="badge bg-label-secondary">{{ $rs->type }}: {{ (int) $rs->qty_sum }} ({{ (int) $rs->rows_count }})</span>
            @endforeach
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="card rounded-0">
  <div class="card-body p-0">
    <div class="card-datatable table-responsive">
      <table class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>When</th>
            <th>Type</th>
            <th>Bucket</th>
            <th class="text-center">Qty Δ</th>
            <th>Ref</th>
            <th>Created By</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movements as $m)
            <tr>
              <td class="fw-medium">{{ $m->id }}</td>
              <td class="text-muted">{{ $m->created_at }}</td>
              <td><span class="badge bg-label-primary">{{ $m->type }}</span></td>
              <td class="text-muted">{{ $m->bucket ?? 'sellable' }}</td>
              <td class="text-center {{ (int) $m->qty_delta < 0 ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ (int) $m->qty_delta }}</td>
              @php
                $refText = $m->ref_type . ($m->ref_id ? ':' . $m->ref_id : '');
                $refUrl = null;
                if ($m->ref_type === 'order' && $m->ref_id) {
                  $refUrl = route('admin.order.invoice', ['invoice_id' => $m->ref_id]);
                }
                if ($m->ref_type === 'order_return' && $m->ref_id) {
                  $refUrl = route('admin.returns.index');
                }
              @endphp
              <td class="text-muted">
                @if($refUrl)
                  <a href="{{ $refUrl }}" class="text-decoration-none">{{ $refText }}</a>
                @else
                  {{ $refText }}
                @endif
              </td>
              <td class="text-muted">{{ $m->created_by_name ?? ($m->created_by ?? '') }}</td>
              <td class="text-muted">{{ $m->note }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">No movements found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($movements, 'links') && $movements->hasPages())
    <div class="card-footer border-top bg-white py-3">
      <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary mb-0">
        {{ $movements->links() }}
      </div>
    </div>
    @endif
  </div>
</div>
@endsection
