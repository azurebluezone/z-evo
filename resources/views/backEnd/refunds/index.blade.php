@extends('backEnd.layouts.app')
@section('title','Refunds')

@section('content')
<div class="card rounded-0">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
    <h5 class="mb-0 text-uppercase fw-bold">Refunds</h5>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-square waves-effect">Orders</a>
      <a href="{{ route('admin.returns.index') }}" class="btn btn-outline-secondary btn-square waves-effect">Returns</a>
    </div>
  </div>

  <div class="card-body p-0">
    <div class="sticky-top bg-white" style="top: 0; z-index: 9;">
      <div class="px-3 py-2">
        <div class="alert alert-info mb-0 rounded-0">Approved returns list. Mark refunded after verifying payment.</div>
      </div>
      <hr class="my-0">
    </div>

    <div class="card-datatable table-responsive">
      <table class="table table-hover w-100 align-middle mb-0">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Invoice</th>
                  <th>Customer</th>
                  <th class="text-end">Refund Amount</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  @php
                    $o = $orders[$r->order_id] ?? null;
                  @endphp
                  <tr>
                    <td>#{{ $r->order_id }}</td>
                    <td>{{ optional($o)->invoice_id }}</td>
                    <td>{{ optional(optional($o)->customer)->name }}</td>
                    <td class="text-end">{{ $r->refund_amount }}</td>
                    <td>
                      <span class="badge bg-label-warning">{{ $r->status }}</span>
                      @if($r->refunded)
                        <span class="badge bg-label-success">Refunded</span>
                      @endif
                    </td>
                    <td class="text-end">
                      @if(!$r->refunded)
                        <form method="POST" action="{{ route('admin.refunds.mark', $r->order_id) }}" class="d-inline">
                          @csrf
                          <input type="hidden" name="refund_amount" value="{{ $r->refund_amount }}" />
                          <input type="text" name="refund_note" value="" class="form-control form-control-sm d-inline-block me-2" style="max-width: 180px;" placeholder="Refund note" />
                          <x-admin.button variant="primary" size="sm" ripple="true" type="submit">Mark Refunded</x-admin.button>
                        </form>
                      @else
                        <span class="badge bg-label-success">Done</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted">No approved returns found</td>
                  </tr>
                @endforelse
              </tbody>
          </table>
    </div>

    <div class="card-footer border-top bg-white py-3">
      <div class="d-flex justify-content-end">
        {{ $rows->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
