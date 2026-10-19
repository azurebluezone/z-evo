@extends('backEnd.layouts.app')
@section('title','Contact Manage')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Contact Manage</h4>
    <p class="text-muted mb-0">Manage contact information</p>
  </div>
  <a href="{{route('contact.create')}}" class="btn btn-primary waves-effect">
    <i class="icon-base ri ri-add-line me-2"></i>Create Contact
  </a>
</div>

<div class="card">
            <div class="card-body">
                <div class="table-responsive">
                <table id="datatable-buttons" class="table table-hover">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                
                
                    <tbody>
                        @foreach($show_data as $key=>$value)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$value->phone}}</td>
                            <td>{{$value->email}}</td>
                            <td>@if($value->status==1)<span class="badge bg-label-success rounded-pill">Active</span> @else <span class="badge bg-label-danger rounded-pill">Inactive</span> @endif</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($value->status == 1)
                                    <form method="post" action="{{route('contact.inactive')}}" class="d-inline"> 
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">       
                                    <x-admin.button variant="text-warning" size="sm" shape="icon" ripple="true" class="waves-light change-confirm" type="button"><i class="icon-base ri ri-thumb-down-line"></i></x-admin.button></form>
                                    @else
                                    <form method="post" action="{{route('contact.active')}}" class="d-inline">
                                        @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                    <x-admin.button variant="text-success" size="sm" shape="icon" ripple="true" class="waves-light change-confirm" type="button"><i class="icon-base ri ri-thumb-up-line"></i></x-admin.button></form>
                                    @endif

                                    <a href="{{route('contact.edit',$value->id)}}" class="btn btn-sm btn-icon btn-text-primary waves-effect waves-light"><i class="icon-base ri ri-edit-line"></i></a>

                                    <form method="post" action="{{route('contact.destroy')}}" class="d-inline">        
                                        @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                    <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" class="waves-light delete-confirm" type="submit"><i class="icon-base ri ri-delete-bin-line"></i></x-admin.button></form>
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
@endsection
