{{--
    FM:PARTIAL detail/product-main
    =========================================================================
    The white band that carries the gallery and the buy box side by side.

    It owns nothing: the gallery (detail/gallery) and the summary
    (detail/summary) are the two sections, and this partial is only the grid
    that puts them next to each other. It stays a partial because the gallery /
    summary pair is the one combination every product page renders, and the
    column rule (12 tracks, 8/4 split) belongs with it rather than in the page.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.detail.product-main')
--}}

{{-- FM:SECTION product-detail ========================================= --}}
<section class="w-full py-8 md:py-12 bg-white" data-section="product-detail">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            @include('frontend.fm.sections.detail.gallery')
            @include('frontend.fm.sections.detail.summary')
        </div>
    </div>
</section>
{{-- /FM:SECTION product-detail --}}