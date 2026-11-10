@extends('backEnd.layouts.app')
@section('title', 'Order Board')

@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                <h4 class="page-title mb-0">Order Board</h4>
                <div class="page-title-right d-flex gap-2">
                    <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri ri-list-check me-1"></i> List view
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    {{--
        The library builds the boards itself from the JSON below, so the markup
        here is an empty host. A card's column is its order_status in the
        database: after a drag the save endpoint answers with the column the
        order is really in, and the browser moves the card back if that is not
        where it was dropped. The DOM is never treated as the truth.
    --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="kanbanBoard" class="kanban-scroll"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-order-board.css') }}">
@endsection

@push('script')
<script src="{{ asset('admin/assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/jkanban/jkanban.js') }}"></script>
<div class="d-none" data-order-board="{{ json_encode(['moveUrl' => route('admin.order.board.move'),'processUrl' => route('admin.order.process', ['invoice_id' => 'x']),'boards' => $boardData]) }}"></div>
<script src="{{ asset('admin/assets/js/admin-page-order-board.js') }}"></script>
@endpush