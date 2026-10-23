@extends('backEnd.layouts.app')
@section('title','IP Block Manage')

@section('content')
{{--
    IP Block Manage

    The same three components as the other report screens: a filter/form card
    and a datatable card.

    Fixed from the previous version:
      - 12 asset references to /public/backEnd/assets/libs/datatables.net-*/
        which do not exist. Every one of them 404'd, and the page does not
        use DataTables at all - the table is a plain Bootstrap table. The
        references were removed instead of being repointed.
      - `data-parsley-validate` and `enctype="multipart/form-data"` on a
        form with no file input and no parsley
      - `fe-edit-1` and `mdi mdi-close` icons, neither of which is loaded in
        the admin (it uses iconify / ri-*)
      - the legacy page-title-box header, the nested container-fluid and the
        form-group class (Bootstrap 4, not defined in this theme)
--}}

<div class="card rounded-0 mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">IP Block Manage</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('customers.ipblock.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-12 col-md-6">
                <label for="ip_no" class="form-label">IP No *</label>
                <input type="text" class="form-control @error('ip_no') is-invalid @enderror"
                       name="ip_no" value="{{ old('ip_no') }}" id="ip_no" required>
                @error('ip_no')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
            <div class="col-12 col-md-6">
                <label for="reason" class="form-label">Reason *</label>
                <textarea class="form-control @error('reason') is-invalid @enderror"
                          name="reason" id="reason" rows="3" required>{{ old('reason') }}</textarea>
                @error('reason')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
            <div class="col-12">
                <x-admin.button variant="primary" type="submit">
                    <i class="icon-base ri ri-add-line me-1"></i>Add IP Block
                </x-admin.button>
            </div>
        </form>
    </div>
</div>

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Blocked IPs</h5>
        <span class="text-muted small">{{ count($data) }} {{ count($data) === 1 ? 'entry' : 'entries' }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:8%">SL</th>
                        <th style="width:32%">IP</th>
                        <th>Reason</th>
                        <th style="width:120px" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="fw-medium">{{ $row->ip_no }}</span></td>
                            <td>{{ $row->reason }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <x-admin.button variant="text-secondary" size="sm" shape="icon" type="button"
                                                   data-bs-toggle="modal" data-bs-target="#ipEdit{{ $row->id }}" title="Edit">
                                        <i class="icon-base ri ri-edit-box-line"></i>
                                    </x-admin.button>
                                    <form method="post" action="{{ route('customers.ipblock.destroy') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" value="{{ $row->id }}" name="id">
                                        <x-admin.button variant="text-danger" size="sm" shape="icon" type="submit" title="Delete">
                                            <i class="icon-base ri ri-delete-bin-7-line"></i>
                                        </x-admin.button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- The modal stays inside the cell: a <div> directly
                             under <tbody> is invalid and the browser lifts
                             it out of the table. --}}
                        <div class="modal fade" id="ipEdit{{ $row->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit IP Address</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="{{ route('customers.ipblock.update') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $row->id }}">
                                            <div class="mb-3">
                                                <label for="ip_no_edit_{{ $row->id }}" class="form-label">IP No *</label>
                                                <input type="text" class="form-control @error('ip_no') is-invalid @enderror"
                                                       name="ip_no" id="ip_no_edit_{{ $row->id }}" value="{{ $row->ip_no }}" required>
                                                @error('ip_no')
                                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label for="reason_edit_{{ $row->id }}" class="form-label">Reason *</label>
                                                <textarea class="form-control @error('reason') is-invalid @enderror"
                                                          name="reason" id="reason_edit_{{ $row->id }}" rows="3" required>{{ $row->reason }}</textarea>
                                                @error('reason')
                                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                            <x-admin.button variant="primary" type="submit">
                                                <i class="icon-base ri ri-save-line me-1"></i>Update
                                            </x-admin.button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No IP addresses blocked</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
