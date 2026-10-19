{{--
    FM:SECTION detail-gallery ------------------------------------------------
    Left column of the product detail page (hi design "PRODUCT DETAIL"
    section): main photo with the category and discount badges, the wishlist
    heart, the zoom hint and the thumbnail strip.

    Expects (computed once in pages/product-details.blade.php):
        $fmProduct     App\Models\Product            required
        $fmMainImage   string                        main photo URL
        $fmThumbs      array<string>                 photo URLs, first = main
        $fmThumbGrid   string                        grid-* utility for strip
        $fmBadge       array|null                    ['text' => ..., 'pct' => int]
        $fmCategoryChip string|null                  badge over the photo

    The heart talks to fm-app.js through data-wishlist-id, so the counter and
    the toast behave exactly like the cards on the home page.
-------------------------------------------------------------------------- --}}

@php
    $fmProduct     = $fmProduct ?? $product;
    $fmBadge       = $fmBadge ?? null;
    $fmCategoryChip = $fmCategoryChip ?? null;
@endphp

<div class="lg:col-span-6 flex flex-col gap-4 sticky top-24">
    <div class="relative w-full rounded-2xl overflow-hidden bg-surface-container-low border border-gray-100 shadow-sm group">
        {{-- Overlay badges --}}
        <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
            @if($fmCategoryChip)
                <span class="inline-flex items-center gap-1.5 bg-white/95 backdrop-blur text-emerald-800 font-semibold text-xs px-3 py-1.5 rounded-full shadow-sm border border-emerald-800/10">
                    <span class="material-symbols-outlined text-sm text-emerald-700">eco</span>
                    {{ $fmCategoryChip }}
                </span>
            @endif
            @if($fmBadge)
                <span class="inline-flex items-center gap-1 bg-amber-400 text-slate-900 font-bold text-xs px-2.5 py-1 rounded-full shadow-sm w-fit">
                    <i class="bi bi-lightning-charge-fill text-xs"></i> {{ $fmBadge['text'] }}
                </span>
            @endif
        </div>

        {{-- Wishlist heart: fm-app.js handles the click delegation --}}
        <button type="button"
                class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/90 backdrop-blur hover:bg-white text-gray-700 hover:text-red-500 flex items-center justify-center shadow-sm transition-all active:scale-95 border border-gray-200/60"
                data-wishlist-id="{{ $fmProduct->id }}"
                title="Add to wishlist" aria-label="Add to wishlist">
            <i class="bi bi-heart text-lg"></i>
        </button>

        {{-- Main photo canvas --}}
        <div class="aspect-square w-full flex items-center justify-center p-6 bg-white fm-detail-media" id="fmDetailMedia">
            <img src="{{ $fmMainImage }}"
                 alt="{{ $fmProduct->name }}"
                 id="fmDetailMainPhoto"
                 class="w-full h-full object-cover rounded-xl transition-all duration-300 transform group-hover:scale-105"
                 width="600" height="600"
                 onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';">
        </div>

        <div class="absolute bottom-3 right-3 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-gray-600 text-xs font-medium flex items-center gap-1 shadow-sm pointer-events-none border border-gray-200/50">
            <i class="bi bi-zoom-in text-xs text-gray-500"></i> Click a thumbnail to view
        </div>
    </div>

    {{-- Thumbnail strip. Rendered only when the shop has more than one photo
         for this product; the column count follows the number of photos. --}}
    @if(count($fmThumbs) > 1)
        <div class="{{ $fmThumbGrid }} gap-3">
            @foreach($fmThumbs as $fmIdx => $fmThumb)
                <button type="button"
                        class="fm-detail-thumb rounded-xl overflow-hidden bg-white border-2 border-gray-200 p-1 min-h-[44px] min-w-[44px] transition-all shadow-sm opacity-75 hover:opacity-100 hover:border-emerald-600 {{ $fmIdx === 0 ? 'is-active' : '' }}"
                        data-full="{{ $fmThumb }}"
                        title="Image {{ $fmIdx + 1 }}"
                        aria-label="{{ $fmProduct->name }} — Image {{ $fmIdx + 1 }}">
                    <img src="{{ $fmThumb }}"
                         alt="{{ $fmProduct->name }} — Image {{ $fmIdx + 1 }}"
                         class="w-full aspect-square object-cover rounded-lg"
                         loading="lazy" decoding="async">
                </button>
            @endforeach
        </div>
    @endif
</div>
