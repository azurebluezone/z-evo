@extends('backEnd.layouts.app')
@section('title','Color Manage')

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Colors</h5>
        <a href="{{ route('colors.create') }}" class="btn btn-primary btn-square waves-effect">
            <i class="ri ri-add-line me-1"></i> Create Color
        </a>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">SL</th>
                        <th style="width:40%">Color Name</th>
                        <th style="width:20%">Status</th>
                        <th style="width:35%" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($show_data as $key=>$value)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>
                            @php
                                $colorVal = $value->color ?? '#000000';
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="border rounded-0" style="width: 24px; height: 24px; background-color: {{ $colorVal }};"></div>
                                <span class="fw-medium">{{$value->colorName ?? 'N/A'}}</span>
                            </div>
                        </td>
                        <td>
                            @if(($value->status ?? 0) == 1)
                            <form method="post" action="{{route('colors.inactive')}}" class="d-inline"> 
                                @csrf
                                <input type="hidden" value="{{$value->id}}" name="hidden_id">       
                                <div class="form-check form-switch custom-switch-sharp m-0">
                                    <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active">
                                </div>
                            </form>
                            @else
                            <form method="post" action="{{route('colors.active')}}" class="d-inline">
                                @csrf
                                <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                <div class="form-check form-switch custom-switch-sharp m-0">
                                    <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" title="Inactive">
                                </div>
                            </form>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{route('colors.edit',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
                                    <i class="icon-base ri ri-edit-box-line"></i>
                                </a>

                                <form method="post" action="{{route('colors.destroy')}}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" class="delete-confirm" type="submit" title="Delete">
                                        <i class="icon-base ri ri-delete-bin-7-line"></i>
                                    </x-admin.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if(method_exists($show_data, 'links') && $show_data->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary mb-0">
                {{ $show_data->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
