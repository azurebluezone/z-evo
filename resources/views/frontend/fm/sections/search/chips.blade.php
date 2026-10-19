{{--
    FM:PARTIAL search/chips
    =========================================================================
    The removable chip strip above the result grid: the searched word, the
    category the search was scoped to, and a way to clear the filters.

    This is the one thing a result page has that the shop page does not, which
    is why it lives in its own partial rather than in the shared shop listing.
    The scope gets its own chip so a visitor can drop the department without
    losing the word they typed.

    The page decides whether to print the strip at all - it is only rendered
    when there is a term or an active filter.

    Props
        term         string  the searched word
        scope        string  category slug the search was scoped to
        scopeName    string  name of that category, when it resolved
        hasFilters   bool    offer the "Clear filters" link

    Usage
        @include('frontend.fm.sections.search.chips', [
            'term'       => $fmTerm,
            'scope'      => $fmSearchScope,
            'scopeName'  => $fmActiveCategory->name ?? null,
            'hasFilters' => $fmHasAnyFilter,
        ])
--}}

<div class="fm-search-chips" data-fm-search-chips>
    @if ($term !== '')
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1b5e20] font-semibold border border-emerald-200/80 shadow-xs">
            &ldquo;{{ $term }}&rdquo;
            <a aria-label="Clear search term" class="text-gray-400 hover:text-red-600 flex items-center"
               href="{{ request()->fullUrlWithoutQuery(['q', 'search', 'page']) }}">
                <i class="bi bi-x text-sm"></i>
            </a>
        </span>
    @endif

    @if ($scope !== '')
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1b5e20] font-semibold border border-emerald-200/80 shadow-xs">
            {{ $scopeName ?: $scope }}
            <a aria-label="Clear category" class="text-gray-400 hover:text-red-600 flex items-center"
               href="{{ request()->fullUrlWithoutQuery(['q', 'search', 'category', 'page']) }}">
                <i class="bi bi-x text-sm"></i>
            </a>
        </span>
    @endif

    @if ($hasFilters)
        <a class="text-red-600 hover:underline font-semibold ml-2 text-xs"
           href="{{ route('search', ['q' => $term]) }}">
            Clear filters
        </a>
    @endif
</div>