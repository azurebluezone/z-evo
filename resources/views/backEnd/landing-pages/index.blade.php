@extends('backEnd.layouts.app')
@section('title')
    Landing Pages Management
@endsection

@section('content')
@if(isset($templates) && $templates->count() > 0)
    <div class="card rounded-0 mb-3">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 text-uppercase fw-bold">Templates</h5>
        </div>
        <div class="card-body">

                    <div class="row">
                        @foreach($templates as $template)
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold">{{ $template->name }}</div>
                                                @if($template->description)
                                                    <div class="text-muted small" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $template->description }}</div>
                                                @endif
                                            </div>
                                            <span class="badge bg-success">Active</span>
                                        </div>

                                        <div class="mt-3">
                                            <a href="{{ route('landing-pages.templates.preview', $template->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="icon-base ri ri-eye-line me-1"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
        </div>
    </div>
@endif

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Landing Pages</h5>
    </div>
    <div class="card-body">
        @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($landingPages->count() > 0)
                    <div class="card-datatable table-responsive mt-3">
                        <table class="table table-hover align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th style="width:70px;">ID</th>
                                    <th>Title</th>
                                    <th style="width:160px;">Template</th>
                                    <th style="width:110px;">Status</th>
                                    <th style="width:140px;">Created</th>
                                    <th style="width:150px;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($landingPages as $landingPage)
                                    <tr>
                                        <td>{{ $landingPage->id }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $landingPage->title }}</div>
                                            @if($landingPage->subtitle)
                                                <div class="text-muted small">{{ $landingPage->subtitle }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $landingPage->template->name }}</span>
                                        </td>
                                        <td>
                                            @if($landingPage->status)
                                                <span class="badge bg-label-success rounded-0">Active</span>
                                            @else
                                                <span class="badge bg-label-secondary rounded-0">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $landingPage->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                                <a href="{{ route('landing-pages.show', $landingPage->id) }}"
                                                   class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="View" data-bs-toggle="tooltip" data-bs-title="View">
                                                    <i class="icon-base ri ri-eye-line icon-22px"></i>
                                                </a>
                                                <a href="{{ route('landing-pages.edit', $landingPage->id) }}"
                                                   class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" data-bs-toggle="tooltip" data-bs-title="Edit">
                                                    <i class="icon-base ri ri-edit-box-line icon-22px"></i>
                                                </a>
                                                <a href="{{ route('landing.show', $landingPage->slug) }}" target="_blank"
                                                   class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Preview" data-bs-toggle="tooltip" data-bs-title="Preview">
                                                    <i class="icon-base ri ri-external-link-line icon-22px"></i>
                                                </a>
                                                <form action="{{ route('landing-pages.destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $landingPage->id }}">
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
                @else
                    <div class="text-center py-5">
                        <h5 class="text-muted mb-1">No Landing Pages Found</h5>
                        <p class="text-muted mb-3">Create your first landing page to get started.</p>
                    </div>
                @endif
    </div>
</div>
@endsection
