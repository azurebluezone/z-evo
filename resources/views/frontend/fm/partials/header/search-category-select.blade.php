{{--
    FM:COMPONENT header/search-category-select
    =========================================================================
    The category picker that sits at the left edge of the search field.

    Kept separate from the search form for two reasons: the same list is
    wanted by the mobile strip, and the desktop copy is a native <select>
    while the mobile copy is not shown at all - so the two must not share a DOM
    id. fm-sections.css drops the UA arrow and draws the reference's hairline
    divider instead.

    Props
        id        string  element id, default "searchCategoryBtn"
        class     string  extra classes for the <select>
        includeAll bool    (default true) offer the "All categories" reset

    Usage
        @include('frontend.fm.partials.header.search-category-select')
--}}

@php
    $selectId   = $id ?? 'searchCategoryBtn';
    $extraClass = $class ?? '';
    $withAll    = $includeAll ?? true;
@endphp

<div class="dropdown shrink-0">
    {{-- name="category" only when the page is NOT already scoped. A scoped page
         (search-bar.blade.php) renders its own hidden category input, and two
         `category` parameters in one GET means PHP takes the last one - the
         empty "All categories" default would then silently drop the scope the
         visitor was browsing. With no scope, this picker is the only source, so
         it is what the form submits.

         The option VALUE is the slug, not the name: FrontendController::search()
         resolves category= through `categories.slug`, so the name this picker
         used to render could never match a row. It was a decorative control. --}}
    <select aria-label="Search category" class="fm-search-cat {{ $extraClass }}"
            @if (($fmScopedCategory ?? '') === '') name="category" @endif
            id="{{ $selectId }}">
        @if ($withAll)
            <option selected value="">All categories</option>
        @endif
        @foreach ($sidecategories ?? [] as $fmCat)
            <option value="{{ $fmCat->slug }}">{{ $fmCat->name }}</option>
        @endforeach
    </select>
</div>