@extends('frontend.layouts.app')

@section('title', 'Landing Pages')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Offers</h1>
            <p class="text-muted mb-0">Hand-picked landing pages and limited-time offers.</p>
        </div>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Continue shopping</a>
    </div>

    @if($landingPages->isEmpty())
        <div class="text-center py-5">
            <h2 class="h5 mb-1">No offers right now</h2>
            <p class="text-muted mb-3">New landing pages will appear here as soon as they are published.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Continue shopping</a>
        </div>
    @else
        <div class="row g-4">
            @foreach($landingPages as $landingPage)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 mb-2">{{ $landingPage->title }}</h2>

                            @if($landingPage->subtitle)
                                <p class="text-muted mb-3">{{ $landingPage->subtitle }}</p>
                            @endif

                            <a href="{{ route('landing.show', $landingPage->slug) }}" class="btn btn-primary mt-auto">
                                View offer
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
