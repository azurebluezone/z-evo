@extends('backEnd.layouts.app')
@section('title','Order Status Manage')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h4 class="fw-bold mb-1">Order Status Manage</h4>
    <a href="{{route('orderstatus.create')}}" class="btn btn-primary btn-sm waves-effect">
        <i class="icon-base ri ri-add-line me-2"></i>Create
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">SL</th>
                        <th>Name</th>
                        <th style="width:15%">Status</th>
                        <th style="width:20%" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $key=>$value)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td class="fw-medium">{{$value->name}}</td>
                        <td>
                            @if($value->status==1)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                @if($value->status == 1)
                                <form method="post" action="{{route('orderstatus.inactive')}}" class="d-inline"> 
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">      
                                    <x-admin.button variant="text-warning" size="sm" shape="icon" ripple="true" type="submit" title="Set Inactive" data-bs-toggle="tooltip">
                                        <i class="icon-base ri ri-eye-off-line icon-22px"></i>
                                    </x-admin.button>
                                </form>
                                @else
                                <form method="post" action="{{route('orderstatus.active')}}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                    <x-admin.button variant="text-success" size="sm" shape="icon" ripple="true" type="submit" title="Set Active" data-bs-toggle="tooltip">
                                        <i class="icon-base ri ri-eye-line icon-22px"></i>
                                    </x-admin.button>
                                </form>
                                @endif

                                <a href="{{route('orderstatus.edit',$value->id)}}" class="btn btn-sm btn-icon btn-text-secondary waves-effect" title="Edit" data-bs-toggle="tooltip">
                                    <i class="icon-base ri ri-edit-box-line icon-22px"></i>
                                </a>

                                <form method="post" action="{{route('orderstatus.destroy')}}" class="d-inline">        
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" class="delete-confirm" type="submit" title="Delete" data-bs-toggle="tooltip">
                                        <i class="icon-base ri ri-delete-bin-line icon-22px"></i>
                                    </x-admin.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
