@extends('backEnd.layouts.app')
@section('title','Users Manage')
@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Users</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-square waves-effect">Create</a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover w-100 align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">SL</th>
                        <th style="width:25%">Name</th>
                        <th style="width:30%">Email</th>
                        <th style="width:10%">Status</th>
                        <th style="width:12%" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $value)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-medium">{{ $value->name }}</td>
                        <td>{{ $value->email }}</td>
                        <td>
                                    @if((int) $value->status === 1)
                                        <form method="post" action="{{ route('users.inactive') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <label class="switch" title="Active">
                                                <input type="checkbox" class="switch-input js-auto-submit" checked>
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"></span>
                                                    <span class="switch-off"></span>
                                                </span>
                                            </label>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('users.active') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <label class="switch" title="Inactive">
                                                <input type="checkbox" class="switch-input js-auto-submit">
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"></span>
                                                    <span class="switch-off"></span>
                                                </span>
                                            </label>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                        <a href="{{ route('users.edit', $value->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" data-bs-toggle="tooltip" data-bs-title="Edit">
                                            <i class="icon-base ri ri-edit-box-line icon-22px"></i>
                                        </a>
                                        <form method="post" action="{{ route('users.destroy') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
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
</div>
@endsection
