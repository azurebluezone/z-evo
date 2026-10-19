@extends('backEnd.layouts.app')
@section('title','Service Request Details')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="mb-0">Service Request Details</h4>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.user-submissions.service-requests.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                    <a href="{{ route('admin.user-submissions.service-requests.edit', $requestItem->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                    <div class="col-md-6">
                        <div class="fw-medium">Name</div>
                        <div class="text-muted">{{ $requestItem->first_name }} {{ $requestItem->last_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-medium">Phone</div>
                        <div class="text-muted">{{ $requestItem->phone }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-medium">Service Type</div>
                        <div class="text-muted">{{ $requestItem->service_type }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-medium">Status</div>
                        <div>
                            @if($requestItem->status === 'approved')
                                <span class="badge text-bg-success">Approved</span>
                            @else
                                <span class="badge text-bg-warning">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="fw-medium">Message</div>
                        <div class="text-muted" style="white-space: pre-wrap;">{{ $requestItem->message }}</div>
                    </div>
                    <div class="col-12">
                        <div class="fw-medium">Submitted At</div>
                        <div class="text-muted">{{ $requestItem->created_at ? $requestItem->created_at->format('Y-m-d H:i') : '' }}</div>
                    </div>
                </div>

                <hr>

                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ route('admin.user-submissions.service-requests.status', $requestItem->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="{{ $requestItem->status === 'approved' ? 'pending' : 'approved' }}">
                            <x-admin.button variant="outline-success" size="sm" type="submit">
                                {{ $requestItem->status === 'approved' ? 'Mark Pending' : 'Mark Approved' }}
                            </x-admin.button>
                        </form>

                        <form action="{{ route('admin.user-submissions.service-requests.destroy', $requestItem->id) }}" method="POST" class="d-inline">
                            @csrf
                            <x-admin.button variant="outline-danger" size="sm" class="delete-confirm" type="submit">Delete</x-admin.button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
