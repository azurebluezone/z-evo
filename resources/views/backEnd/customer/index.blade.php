@extends('backEnd.layouts.app')
@section('title','Customer Manage')

@section('content')
{{-- Customer totals live in the shared admin header
     (backEnd/partials/stat-grid). They used to be counted from the ten
     rows of the current paginator page, so "Active" changed when the
     admin pressed Next. The header queries the whole table once. --}}

<!-- Customer List -->
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Customers</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <form class="d-flex align-items-center gap-2 m-0" method="get" action="{{ route('customers.index') }}">
                <input type="text" name="keyword" class="form-control rounded-0" placeholder="Search phone/name" value="{{ request('keyword') }}" style="min-width: 220px;">
                <x-admin.button variant="info" shape="square" type="submit">Search</x-admin.button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        {{-- Shared grid: columns and endpoint only (see backEnd/partials/datatable). --}}
        @include('backEnd.partials.datatable', [
            'id'      => 'customerTable',
            'url'     => route('customers.datatable'),
            'title'   => 'Customers',
            'empty'   => 'No customers yet.',
            'params'  => ['keyword' => request('keyword')],
            'order'   => [[1, 'asc']],
            'columns' => [
                ['label' => 'SL', 'orderable' => false, 'searchable' => false],
                ['label' => 'Customer'],
                ['label' => 'Phone'],
                ['label' => 'Email'],
                ['label' => 'Status'],
                ['label' => 'Actions', 'orderable' => false, 'class' => 'text-end'],
            ],
        ])
    </div>{{-- /card-body --}}
</div>{{-- /card rounded-0 --}}

@endsection
