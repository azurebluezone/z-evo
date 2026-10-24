@extends('backEnd.layouts.app')
@section('title','All Review')
@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Reviews</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{route('reviews.create')}}" class="btn btn-primary btn-square waves-effect">Create</a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input checkall rounded-0" value="">
                            </div>
                        </th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Review</th>
                        <th>Product</th>
                        <th>Ratting</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($show_data as $key=>$value)
                    <tr>
                        <td>
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input checkbox rounded-0" value="{{ $value->id }}">
                            </div>
                        </td>
                        <td class="fw-medium">{{$value->name}}</td>
                        <td>{{$value->email}}</td>
                        <td>{{Str::limit($value->review,50)}}</td>
                        <td>
                            @if($value->product)
                                {{ Str::limit($value->product->name, 40) }}
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            @php($rating = (int) ($value->ratting ?? 0))
                            <span class="d-inline-flex align-items-center gap-1" aria-label="Rating: {{ $rating }}">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $rating)
                                        <i class="icon-base ri ri-star-fill text-warning"></i>
                                    @else
                                        <i class="icon-base ri ri-star-line text-muted"></i>
                                    @endif
                                @endfor
                                <span class="text-muted ms-1">({{ $rating }})</span>
                            </span>
                        </td>
                        <td>
                            @if($value->status == 'active')
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-warning">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                @if($value->status == 'active')
                                <form method="post" action="{{route('reviews.inactive')}}" class="d-inline"> 
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">       
                                    <x-admin.button variant="text-warning" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Mark Pending" data-bs-toggle="tooltip" data-bs-title="Mark Pending">
                                        <i class="icon-base ri ri-thumb-down-line icon-22px"></i>
                                    </x-admin.button>
                                </form>
                                @else
                                <form method="post" action="{{route('reviews.active')}}" class="d-inline">
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                    <x-admin.button variant="text-success" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Mark Active" data-bs-toggle="tooltip" data-bs-title="Mark Active">
                                        <i class="icon-base ri ri-thumb-up-line icon-22px"></i>
                                    </x-admin.button>
                                </form>
                                @endif

                                <a href="{{route('reviews.edit',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" data-bs-toggle="tooltip" data-bs-title="Edit">
                                    <i class="icon-base ri ri-edit-box-line icon-22px"></i>
                                </a>

                                <form method="post" action="{{route('reviews.destroy')}}" class="d-inline">        
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

        @if(method_exists($show_data, 'links'))
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary">
                {{ $show_data->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
