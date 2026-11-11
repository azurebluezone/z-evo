@extends('backEnd.layouts.app')

@section('title')
    Landing Page Designs
@endsection

@section('content')
<div class="card rounded-0">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 text-uppercase fw-bold">Landing Page Designs</h5>
            <span class="badge bg-dark">{{ $templates->count() }}</span>
        </div>
        <div class="text-muted small">One card per working design · View opens the live preview · Edit manages its variables</div>
    </div>
    <div class="card-body">
        @if($templates->isEmpty())
            <div class="text-center py-5">
                <h5 class="text-muted mb-1">No Designs Found</h5>
                <p class="text-muted mb-0">Run <code>php artisan db:seed --class=TemplateSeeder</code> to install the nine designs.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach($templates as $template)
                    @php
                        $registryDesign = config('landing_templates.templates.' . $template->view_name);
                        $designTitle = $registryDesign['name'] ?? $template->name;
                        $campaign = $campaignsByTemplate[$template->id] ?? null;
                        $image = \App\Support\LandingCardImage::forCampaign($campaign, $template);
                    @endphp
                    <div class="col-xl-4 col-lg-6" data-design-card>
                        <div class="card h-100 rounded-0">
                            <img src="{{ \App\Support\LandingCardImage::url($image) }}" alt="{{ $designTitle }}" class="card-img-top" style="height:140px;object-fit:cover;">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <h6 class="fw-bold mb-0">{{ $designTitle }}</h6>
                                    @if($template->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <a href="{{ route('landing-pages.templates.preview', $template->id) }}" target="_blank"
                                       class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="ri ri-eye-line me-1"></i> View
                                    </a>
                                    <a href="{{ route('landing-pages.template-variables', $template->id) }}"
                                       class="btn btn-sm btn-dark flex-fill">
                                        <i class="ri ri-edit-box-line me-1"></i> Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
