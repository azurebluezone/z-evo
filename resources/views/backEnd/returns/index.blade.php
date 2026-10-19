@extends('backEnd.layouts.app')
@section('title','Returns')

@section('content')
<div class="card rounded-0">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
    <h5 class="mb-0 text-uppercase fw-bold">Returns</h5>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-square waves-effect">Orders</a>
      <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline-secondary btn-square waves-effect">Refunds</a>
    </div>
  </div>

  <div class="card-body p-0">
    <div class="sticky-top bg-white" style="top: 0; z-index: 9;">
      <div class="px-3 py-2">
        <div class="alert alert-info mb-0 rounded-0">Delivered orders list. Approve return will restock inventory (legacy + ledger/cache).</div>
      </div>
      <hr class="my-0">
    </div>

        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'returnsTable',
            'url'     => route('returns.datatable'),
            'title'   => 'Returns',
            'empty'   => 'No return requests yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[0, 'desc']],
            'columns' => [
                ['label' => 'Order'],
                ['label' => 'Invoice'],
                ['label' => 'Customer'],
                ['label' => 'Phone'],
                ['label' => 'Amount', 'class' => 'text-end'],
                ['label' => 'Return Status'],
                ['label' => 'Action', 'orderable' => false, 'class' => 'text-end'],
            ],
        ])
