{{--
    FM:COMPONENT ai/camera-lens
    =========================================================================
    Optical produce scan trigger. Same contract as ai/voice-search: the design
    markup is cut by tools/split_design_sections.py, the behaviour is ours.

    Props
        variant string  'inline' (default) or 'pill'
        label    string  accessible label

    fm-ai.js binds data-fm-open="camera" on the document, so this works from the
    header bar, the mobile search strip and any hero section alike.
--}}

@php
    $variant = $variant ?? 'inline';
    $label   = $label ?? 'Scan produce with camera';
@endphp

@if ($variant === 'pill')
    <button aria-label="{{ $label }}" class="ai-camera-pill" data-fm-open="camera" title="{{ $label }}" type="button">
        <span class="ai-pulse" aria-hidden="true"></span>
        <i class="bi bi-camera-fill" aria-hidden="true"></i>
        <span class="ai-pill-text">Scan with AI Lens</span>
    </button>
@else
    <button aria-label="{{ $label }}" class="fm-ai-icon-btn ai-inline-btn ai-inline-btn--accent" data-fm-open="camera" title="{{ $label }}" type="button">
        <i class="bi bi-camera-fill" aria-hidden="true"></i>
    </button>
@endif