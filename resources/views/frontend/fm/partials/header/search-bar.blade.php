{{--
    FM:PARTIAL header/search-bar
    =========================================================================
    The omni-search field: category picker, text input, the two AI triggers and
    the submit button - each of the AI triggers being its own component.

    Structure follows the reference header (work/2.md, #global-search-input and
    the "Quick AI Action Icons inside search bar" group); behaviour is ours:

      - it is a real GET form to route('search'), so searching works with
        JavaScript off;
      - the input keeps data-fm-search-input, which is the hook fm-app.js binds
        for the live suggest list, so that panel is not lost by extracting the
        markup into a component;
      - the design's inline onclick="... openAiDrawer()" is gone: the AI
        triggers carry data-fm-open instead and fm-ai.js owns opening.

    Props
        id          string  input id, default "fmSearchInput"
        placeholder string
        variant     string  'desktop' (default) or 'mobile'. Nothing renders the
                            mobile variant any more: the phone search surface is
                            the #fmSearchDrawer offcanvas, and it takes the
                            desktop variant on purpose because that is the one
                            that carries the category picker, the suggest panel
                            and both AI triggers.

    Usage
        @include('frontend.fm.partials.header.search-bar')
--}}

@php
    $variant     = $variant ?? 'desktop';
    $inputId     = $id ?? 'fmSearchInput';
    $placeholder = $placeholder ?? 'Search products by name or code';
    $fieldClass  = $variant === 'mobile'
        ? 'w-full bg-transparent border-0 px-3 py-1.5 min-h-[44px] text-xs text-gray-800 placeholder-gray-400 focus:ring-0'
        : 'w-full bg-transparent border-0 px-3 py-1.5 min-h-[44px] text-xs lg:text-sm text-gray-800 placeholder-gray-400 focus:ring-0 focus:outline-none';
    $shellClass  = $variant === 'mobile'
        ? 'flex items-center rounded-full border border-emerald-800/20 bg-gray-50 overflow-hidden p-1 shadow-inner'
        : 'flex w-full items-center rounded-full border border-emerald-800/25 bg-gray-50/80 hover:bg-white focus-within:bg-white focus-within:border-[#1b5e20] focus-within:ring-2 focus-within:ring-emerald-100 transition-all p-1 shadow-inner';

    /*
     * Searching from inside a category keeps that category.
     *
     * The form used to post only q=, so a visitor browsing "0-3 Month" who
     * typed in the header bar was thrown onto the global result page and lost
     * the department they were in. The category the page was showing is carried
     * in a hidden field, but ONLY while this page can actually narrow by it -
     * on a path-scoped route such as /category/{slug} the controller ignores
     * ?category= entirely, so sending one there would claim a scope the server
     * does not apply. $fmCategorySlug is resolved by the calling page and is
     * already the route slug on those pages, which is exactly why it is used
     * here instead of the raw request value.
     */
    $fmScopedCategory = trim((string) ($fmCategorySlug ?? ''));
@endphp

<form class="{{ $variant === 'mobile' ? 'w-full' : 'flex items-center w-full' }}" method="GET" action="{{ route('search') }}" role="search">
    @if ($fmScopedCategory !== '')
        <input name="category" type="hidden" value="{{ $fmScopedCategory }}">
    @endif
    <div class="{{ $shellClass }}">
        @if ($variant === 'desktop')
            @include('frontend.fm.partials.header.search-category-select')
        @endif

        <div class="relative flex-1 flex items-center">
            <input aria-label="Search" class="{{ $fieldClass }}" data-fm-search-input
                   id="{{ $inputId }}" name="q" placeholder="{{ $placeholder }}" type="search">

            {{-- The two AI controls, one component each. --}}
            <span class="fm-ai-inline flex items-center space-x-1.5 pr-2 text-gray-400" data-fm-inline-icons>
                @include('frontend.fm.sections.ai.voice-search')
                @include('frontend.fm.sections.ai.camera-lens')
            </span>
        </div>

        <button aria-label="Search" class="bg-[#1b5e20] hover:bg-[#144618] text-white px-4 py-2 min-h-[44px] min-w-[44px] rounded-full flex items-center justify-center transition-colors flex-shrink-0 shadow-xs border-0" type="submit">
            <i class="bi bi-search text-xs"></i>
        </button>
    </div>
</form>

@if ($variant === 'desktop')
    {{-- data-fm-for pairs this panel with ITS field. fm-app.js binds every
         [data-fm-search-input] on the page (the bar and the search drawer both
         render one), and pairing by the field id is what keeps the two panels
         from writing into each other. --}}
    <div class="fm-suggest" data-fm-search-suggest data-fm-for="{{ $inputId }}" hidden></div>
@endif