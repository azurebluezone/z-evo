{{--
    FM:PARTIAL section-head
    =========================================================================
    Shared heading block: small eyebrow, title, optional description and an
    optional "see all" link on the right.

    Expects:
        eyebrow  string|null
        heading  string
        text     string|null
        link     string|null
        linkText string|null
        centered bool      (default false)
    =========================================================================
--}}

@php
    $fmCentered = $centered ?? false;
@endphp

<div class="fm-section-head {{ $fmCentered ? 'fm-section-head--center' : '' }}">
    <div class="fm-section-head__text">
        @if(!empty($eyebrow))
            <span class="fm-section-head__eyebrow">{{ $eyebrow }}</span>
        @endif

        <h2>{{ $heading }}</h2>

        @if(!empty($text))
            <p>{{ $text }}</p>
        @endif
    </div>

    @if(!empty($link))
        <a href="{{ $link }}" class="fm-section-head__link">
            {{ $linkText ?? 'View all' }}
            <i class="bi bi-arrow-right"></i>
        </a>
    @endif
</div>
