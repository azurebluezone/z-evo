@extends('backEnd.layouts.app')
@section('title','Homepage Collections')
@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Homepage Collections</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('admin.homepage.collections.create') }}" class="btn btn-primary btn-square waves-effect">Create</a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table id="datatable-buttons" class="table table-hover align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Banner</th>
                                <th>Limit</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collections as $collection)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $collection->title }}</td>
                                    <td>{{ $collection->category?->name }}</td>
                                    <td>
                                        @php
                                            $bannerPath = preg_replace('~^public/~', '', (string) ($collection->banner_image ?? ''));
                                            $bannerPath = ltrim($bannerPath, '/');
                                        @endphp
                                        @if($bannerPath)
                                            <img src="{{ asset($bannerPath) }}" class="backend-image" alt="" onerror="this.style.display='none'">
                                        @endif
                                    </td>
                                    <td>{{ $collection->product_limit }}</td>
                                    <td>{{ $collection->sort_order }}</td>
                                    <td>
                                        @if($collection->status==1)
                                            <span class="badge bg-label-success rounded-0">Active</span>
                                        @else
                                            <span class="badge bg-label-secondary rounded-0">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-1">
                                            @if($collection->status == 1)
                                                <form method="post" action="{{ route('admin.homepage.collections.inactive') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" value="{{ $collection->id }}" name="hidden_id">
                                                    <x-admin.button variant="text-warning" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Make Inactive" data-bs-toggle="tooltip" data-bs-title="Make Inactive"><i class="icon-base ri ri-thumb-down-line"></i></x-admin.button>
                                                </form>
                                            @else
                                                <form method="post" action="{{ route('admin.homepage.collections.active') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" value="{{ $collection->id }}" name="hidden_id">
                                                    <x-admin.button variant="text-success" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Make Active" data-bs-toggle="tooltip" data-bs-title="Make Active"><i class="icon-base ri ri-thumb-up-line"></i></x-admin.button>
                                                </form>
                                            @endif

                                            <a href="{{ route('admin.homepage.collections.edit', $collection->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="icon-base ri ri-edit-box-line"></i></a>

                                            <form method="post" action="{{ route('admin.homepage.collections.destroy') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" value="{{ $collection->id }}" name="hidden_id">
                                                <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" class="delete-confirm" type="submit" title="Delete" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="icon-base ri ri-delete-bin-7-line"></i></x-admin.button>
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
    </div>
</div>
@endsection
