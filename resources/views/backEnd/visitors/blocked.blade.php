@extends('backEnd.layouts.app')
@section('title','Blocked Visitors')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Blocked Visitors</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('admin.visitors.blocked') }}" class="row g-2 align-items-end">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Visitor ID</label>
                            <input type="text" name="visitor_id" class="form-control" value="{{ request()->get('visitor_id') }}" placeholder="UUID">
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">IP</label>
                            <input type="text" name="ip" class="form-control" value="{{ request()->get('ip') }}" placeholder="IP address">
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <x-admin.button variant="primary" class="w-100" type="submit">Search</x-admin.button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="post" action="" id="bulkActionForm">
                        @csrf

                        <div class="d-flex flex-wrap gap-2 justify-content-end mb-2">
                            <x-admin.button variant="primary" size="sm" type="button" id="bulkUnblockBtn" data-action="{{ route('admin.visitors.bulk-unblock') }}">Unblock Selected</x-admin.button>
                        </div>

                    <div class="card-datatable table-responsive">
                        <table class="table table-striped w-100 align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px;">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>SL</th>
                                    <th>Visitor ID</th>
                                    <th>IP</th>
                                    <th>Country</th>
                                    <th>City</th>
                                    <th>ISP</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visitors as $row)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="row-check" name="ids[]" value="{{ $row->id }}">
                                        </td>
                                        <td>{{ ($visitors->currentPage() - 1) * $visitors->perPage() + $loop->iteration }}</td>
                                        <td class="text-nowrap">
                                            <x-admin.button variant="link" class="p-0" type="button" data-bs-toggle="modal" data-bs-target="#visitorInfoModal" data-title="Visitor ID" data-value="{{ $row->visitor_id }}" title="View Visitor ID"><i class="icon-base ri ri-eye-line icon-22px"></i></x-admin.button>
                                        </td>
                                        <td class="text-nowrap">{{ $row->ip }}</td>
                                        <td>{{ $row->country ?? '' }}</td>
                                        <td>{{ $row->city ?? '' }}</td>
                                        <td>{{ $row->isp ?? '' }}</td>
                                        <td class="text-nowrap">
                                            <x-admin.button variant="link" class="p-0" type="button" data-bs-toggle="modal" data-bs-target="#visitorInfoModal" data-title="Created" data-value="{{ $row->created_at ? $row->created_at->format('d-m-Y h:i a') : '' }}" title="View Created"><i class="icon-base ri ri-eye-line icon-22px"></i></x-admin.button>
                                        </td>
                                        <td>
                                            <form method="post" action="{{ route('admin.visitors.unblock') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" value="{{ $row->id }}" name="hidden_id">
                                                <x-admin.button variant="primary" size="sm" ripple="true" class="waves-light" type="submit">Unblock</x-admin.button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    </form>

                    <div class="custom-paginate">
                        {{ $visitors->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="visitorInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visitorInfoModalTitle">Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-2 bg-light rounded" style="word-break: break-all;">
                    <span id="visitorInfoModalValue"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('admin/assets/js/admin-page-visitors-blocked.js') }}"></script>
@endsection
