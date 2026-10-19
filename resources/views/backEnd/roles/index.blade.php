@extends('backEnd.layouts.app')
@section('title','Roles Manage')

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Roles</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{route('roles.create')}}" class="btn btn-primary btn-square waves-effect">
                <i class="icon-base ri ri-add-line me-1"></i> Create
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:8%">SL</th>
                        <th>Name</th>
                        <th style="width:18%" class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($show_data as $key=>$value)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td class="fw-medium">{{$value->name}}</td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                <a href="{{route('roles.show',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="View" data-bs-toggle="tooltip" data-bs-title="View">
                                    <i class="icon-base ri ri-eye-line icon-22px"></i>
                                </a>
                                <a href="{{route('roles.edit',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" data-bs-toggle="tooltip" data-bs-title="Edit">
                                    <i class="icon-base ri ri-edit-box-line icon-22px"></i>
                                </a>

                                <form method="post" action="{{route('roles.destroy')}}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" class="delete-confirm" type="submit" title="Delete" data-bs-toggle="tooltip" data-bs-title="Delete">
                                        <i class="icon-base ri ri-delete-bin-7-line icon-22px"></i>
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

    @if(method_exists($show_data, 'links'))
    <div class="card-footer border-top bg-white py-3">
        <div class="d-flex justify-content-end">
            {{ $show_data->links('pagination::bootstrap-4') }}
        </div>
    </div>
    @endif
</div>
@endsection
