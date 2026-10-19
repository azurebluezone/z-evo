{{--
    FM:PARTIAL search/filter-toggle
    =========================================================================
    The "Filters" button that appears only below the lg breakpoint, where the
    sidebar column collapses.

    It is a Bootstrap collapse around the sidebar, so it needs no JavaScript of
    its own: with scripts off the button still opens the panel.

    Props
        target     string  id of the collapsible element wrapping the sidebar
        hasFilters bool    print the "Active" badge

    Usage
        @include('frontend.fm.sections.search.filter-toggle', [
            'target'     => 'fmSearchFilters',
            'hasFilters' => $fmHasAnyFilter,
        ])
--}}

<button aria-controls="{{ $target }}" aria-expanded="false"
        class="fm-filter-toggle d-lg-none w-100" data-bs-toggle="collapse"
        data-bs-target="#{{ $target }}" type="button">
    <span class="d-flex align-items-center gap-2">
        <i class="bi bi-sliders2 text-[#1b5e20]"></i>
        <span class="fw-bold text-gray-900">Filters</span>
        @if ($hasFilters)
            <span class="fm-filter-toggle__badge">Active</span>
        @endif
    </span>
    <i class="bi bi-chevron-down fm-filter-toggle__chevron"></i>
</button>