@extends('backEnd.layouts.app')
@section('title','Flash Sale Manage')

@section('content')

<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Flash Sales</h5>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('flash-sales.create') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri ri-add-line me-1"></i> Create Flash Sale
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">SL</th>
                        <th style="width:25%">Title</th>
                        <th style="width:15%">Start</th>
                        <th style="width:15%">End</th>
                        <th style="width:10%">Products</th>
                        <th style="width:10%">Status</th>
                        <th style="width:15%" class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($flash_sales as $sale)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td class="fw-medium">{{$sale->title ?? 'N/A'}}</td>
                        <td>{{ $sale->start_time ? $sale->start_time->format('Y-m-d H:i') : '' }}</td>
                        <td>{{ $sale->end_time ? $sale->end_time->format('Y-m-d H:i') : '' }}</td>
                        <td>{{ $sale->products_count ?? '' }}</td>
                        <td>
                            @if(($sale->status ?? 0) == 1)
                            <form method="post" action="{{route('flash-sales.inactive')}}" class="d-inline">
                            @csrf
                            <input type="hidden" value="{{$sale->id}}" name="hidden_id">
                            <div class="form-check form-switch custom-switch-sharp m-0">
                                <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active">
                            </div>
                            </form>
                            @else
                            <form method="post" action="{{route('flash-sales.active')}}" class="d-inline">
                                @csrf
                            <input type="hidden" value="{{$sale->id}}" name="hidden_id">
                            <div class="form-check form-switch custom-switch-sharp m-0">
                                <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" title="Inactive">
                            </div>
                            </form>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{route('flash-sales.edit',$sale->id)}}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit">
                                    <i class="icon-base ri ri-edit-line"></i>
                                </a>

                                <form method="post" action="{{route('flash-sales.destroy')}}" class="d-inline" id="deleteForm-{{ $sale->id }}">
                                    @csrf
                                    <input type="hidden" value="{{$sale->id}}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $sale->id }}')">
                                        <i class="icon-base ri ri-delete-bin-line"></i>
                                    </x-admin.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($flash_sales, 'links'))
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary">
                {{ $flash_sales->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
