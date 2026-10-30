@extends('backEnd.layouts.app')
@section('title', 'Front-end Template')

@section('content')
<style>
    /* Scoped to this screen; only the selected-state highlight, the rest is
       Bootstrap utilities so the screen matches its sibling admin pages. */
    .fm-theme-active {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, .15);
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Front-end Template</h4>

                <div class="row g-3">
                    @foreach($catalog as $key => $tpl)
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100 bg-white {{ $stored === $key ? 'fm-theme-active' : '' }}">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="fw-semibold">{{ $tpl['name'] }}</span>
                                    @if($stored === $key)
                                        <span class="badge bg-success">Live</span>
                                    @elseif($preview === $key)
                                        <span class="badge bg-warning text-dark">Previewing</span>
                                    @endif
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="{{ $previewUrls[$key] }}" target="_blank" rel="noopener">
                                        Preview <i class="ri ri-external-link-line"></i>
                                    </a>

                                    <form action="{{ route('admin.frontend-theme.update') }}" method="POST"
                                          class="d-inline">
                                        @csrf
                                        <input type="hidden" name="frontend_theme" value="{{ $key }}">
                                        @if($stored === $key)
                                            <x-admin.button variant="primary" type="submit" size="sm" disabled>
                                                <i class="ri ri-checkbox-circle-line"></i> Use this template
                                            </x-admin.button>
                                        @else
                                            <x-admin.button variant="primary" type="submit" size="sm">
                                                <i class="ri ri-checkbox-circle-line"></i> Use this template
                                            </x-admin.button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($preview)
                    <div class="d-flex align-items-center gap-2 border-top pt-3 mt-3">
                        <a href="{{ $clearUrl }}" class="btn btn-outline-secondary btn-sm">
                            Stop preview
                        </a>
                        <span class="text-warning small">
                            Preview mode is on for your browser only — the public site is unchanged.
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
