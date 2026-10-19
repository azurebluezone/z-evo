{{--
    FM:PARTIAL detail/breadcrumbs
    =========================================================================
    The trail across the top of a product page: Home / department / ... /
    product, with a chevron between the steps and the current page printed as
    plain text (it has no link).

    The page builds $fmCrumbs because the trail is data, not markup - it walks
    the product's own category chain. This partial only renders it. The last
    crumb is truncated rather than wrapped so a long product name cannot push
    the department out of the row on a phone.

    Props
        crumbs  array  [['name' => string, 'url' => string|null], ...]

    Usage
        @include('frontend.fm.sections.detail.breadcrumbs', ['crumbs' => $fmCrumbs])
--}}

{{-- FM:SECTION breadcrumbs ============================================= --}}
<section class="w-full bg-surface-container-low border-b border-gray-100 py-3" data-section="breadcrumbs">
    <div class="container mx-auto px-4 max-w-7xl">
        <nav aria-label="Breadcrumb" class="flex items-center flex-wrap gap-2 text-xs md:text-sm text-gray-500 font-medium">
            @foreach($crumbs as $fmCrumbIdx => $fmCrumb)
                @if($fmCrumbIdx > 0)
                    <i class="bi bi-chevron-right text-[10px] text-gray-400"></i>
                @endif
                @if($fmCrumb['url'])
                    <a href="{{ $fmCrumb['url'] }}" class="hover:text-emerald-800 transition-colors flex items-center gap-1 max-w-[45vw] md:max-w-none truncate">
                        @if($fmCrumbIdx === 0)
                            <i class="bi bi-house-door text-xs"></i>
                        @endif
                        {{ $fmCrumb['name'] }}
                    </a>
                @else
                    <span class="text-emerald-950 font-bold truncate max-w-[45vw] md:max-w-none">{{ $fmCrumb['name'] }}</span>
                @endif
            @endforeach
        </nav>
    </div>
</section>
{{-- /FM:SECTION breadcrumbs --}}