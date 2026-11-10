@extends('backEnd.layouts.app')
@section('title','Landing Page Detail')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card rounded-0 mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Landing Page Detail</h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('landing-pages.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri ri-arrow-left-line me-1"></i> Manage
                    </a>
                    <a href="{{ route('landing-pages.edit', $landingPage->id) }}" class="btn btn-primary btn-square waves-effect">
                        <i class="ri ri-edit-box-line me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card rounded-0 h-100">
            <div class="card-header border-bottom bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">{{ $landingPage->title }}</h5>
                @if($landingPage->status)
                    <span class="badge bg-label-success rounded-0">Active</span>
                @else
                    <span class="badge bg-label-secondary rounded-0">Inactive</span>
                @endif
            </div>
            <div class="card-body">
                @if($landingPage->subtitle)
                    <p class="text-muted mb-3">{{ $landingPage->subtitle }}</p>
                @endif

                @if($landingPage->description)
                    <p class="mb-0 text-break">{!! nl2br(e($landingPage->description)) !!}</p>
                @else
                    <p class="text-muted mb-0">No description was written for this page.</p>
                @endif
            </div>
            <div class="card-footer bg-white border-top text-muted small d-flex flex-wrap gap-3">
                <span>Created: {{ $landingPage->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                <span>Updated: {{ $landingPage->updated_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
        </div>

        <div class="card rounded-0 mt-4">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Extra fields</h5>
            </div>
            <div class="card-body p-0">
                @php($extra = is_array($landingPage->extra_fields) ? $landingPage->extra_fields : [])
                @if(count($extra) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($extra as $key => $value)
                                    <tr>
                                        <td class="text-muted">{{ $key }}</td>
                                        <td class="text-break">
                                            @if(is_array($value))
                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                            @elseif(is_bool($value))
                                                {{ $value ? 'Yes' : 'No' }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0 p-3">This page has no template-specific extra fields saved.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Template</h5>
            </div>
            <div class="card-body">
                @if($landingPage->template)
                    <h6 class="mb-1">{{ $landingPage->template->name }}</h6>
                    <div class="text-muted small mb-2">{{ $landingPage->template->view_name }}</div>
                    @if($landingPage->template->description)
                        <p class="text-muted small mb-0">{{ $landingPage->template->description }}</p>
                    @endif
                @else
                    <p class="text-muted mb-0">The template of this page no longer exists.</p>
                @endif
            </div>
        </div>

        <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Page details</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Slug</span>
                    <span class="fw-medium text-break text-end">{{ $landingPage->slug ?: '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Sort order</span>
                    <span class="fw-medium">{{ $landingPage->sort_order }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">CTA text</span>
                    <span class="fw-medium">{{ $landingPage->cta_text ?: '—' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">CTA link</span>
                    @if($landingPage->cta_link)
                        <a href="{{ $landingPage->cta_link }}" class="fw-medium text-break text-end" target="_blank" rel="noopener">
                            {{ \Illuminate\Support\Str::limit($landingPage->cta_link, 40) }}
                        </a>
                    @else
                        <span class="fw-medium">—</span>
                    @endif
                </div>
            </div>
        </div>

        @if($landingPage->image_url)
            <div class="card rounded-0 mb-4">
                <div class="card-header border-bottom bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold">Hero image</h5>
                </div>
                <div class="card-body">
                    <img src="{{ asset($landingPage->image_url) }}" alt="{{ $landingPage->title }}" class="img-fluid rounded-0">
                </div>
            </div>
        @endif

        <div class="card rounded-0">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Actions</h5>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('landing.show', $landingPage->slug) }}" target="_blank" class="btn btn-outline-primary btn-square waves-effect">
                    <i class="ri ri-external-link-line me-1"></i> Preview live
                </a>
                <form action="{{ route('landing-pages.destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this landing page?')">
                    @csrf
                    <input type="hidden" name="id" value="{{ $landingPage->id }}">
                    <x-admin.button variant="text-danger" shape="square" type="submit">
                        <i class="ri ri-delete-bin-7-line me-1"></i> Delete
                    </x-admin.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
