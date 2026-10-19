@extends('backEnd.layouts.app')
@section('title','Pending Review')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
                <h4 class="mb-0">Pending Review</h4>
                <a href="{{route('reviews.create')}}" class="btn btn-primary">Create</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable-buttons" class="table table-striped w-100 align-middle">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Review</th>
                                    <th>Ratting</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $key=>$value)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$value->name}}</td>
                                    <td>{{$value->email}}</td>
                                    <td>{{Str::limit($value->review,50)}}</td>
                                    <td>{{$value->ratting}}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            @if($value->status == 'active')
                                            <form method="post" action="{{route('reviews.inactive')}}" class="d-inline"> 
                                                @csrf
                                                <input type="hidden" value="{{$value->id}}" name="hidden_id">       
                                                <x-admin.button variant="text-secondary" size="sm" shape="icon" class="change-confirm" type="button" title="Mark Pending" data-bs-toggle="tooltip" data-bs-title="Mark Pending">
                                                    <i class="icon-base ri ri-thumb-down-line icon-22px"></i>
                                                </x-admin.button>
                                            </form>
                                            @else
                                            <form method="post" action="{{route('reviews.active')}}" class="d-inline">
                                                @csrf
                                                <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                                <x-admin.button variant="text-secondary" size="sm" shape="icon" class="change-confirm" type="button" title="Mark Active" data-bs-toggle="tooltip" data-bs-title="Mark Active">
                                                    <i class="icon-base ri ri-thumb-up-line icon-22px"></i>
                                                </x-admin.button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
