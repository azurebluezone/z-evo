{{--
    FM:PARTIAL detail/related
    =========================================================================
    "You may also like": the related-products rail under the tabs.

    The heading is fixed, but the "see all" link is NOT: on a category page the
    link has to name the department the recommendations came from, so the page
    passes both the url and the label. The block renders nothing when there are
    no related products - an empty rail is worse than no rail.

    Props
        products  Collection  related products, may be empty
        linkUrl   string      where "see all" points
        linkLabel string      what that link says

    Usage
        @include('frontend.fm.sections.detail.related', [
            'products'  => $related_products,
            'linkUrl'   => $fmRelatedUrl,
            'linkLabel' => $fmRelatedLabel,
        ])
--}}

@if(!empty($products) && $products->isNotEmpty())
    {{-- FM:SECTION related-products ======================================= --}}
    <section class="w-full py-10 md:py-14 bg-white" data-section="related-products">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-3">
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-800">Related products</span>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">You may also like</h2>
                </div>
                <a class="text-sm font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1.5 transition-colors"
                   href="{{ $linkUrl }}">
                    <span>{{ $linkLabel }}</span>
                    <i class="bi bi-arrow-right text-xs"></i>
                </a>
            </div>

            <x-fm::product-grid
                :products="$products"
                :columns="4" />
        </div>
    </section>
    {{-- /FM:SECTION related-products --}}
@endif