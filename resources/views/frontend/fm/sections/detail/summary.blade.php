{{--
    FM:SECTION detail-summary ------------------------------------------------
    Right column of the product detail page: brand/SKU header, stock pill,
    title, ratings row, price box, description teaser, variant selectors,
    quantity stepper with the two purchase forms, the value banner, the
    WhatsApp shortcut and the share row.

    Expects (computed once in pages/product-details.blade.php):
        $fmProduct        App\Models\Product       required
        $fmBrandChip      string|null              brand or category chip
        $fmInStock        bool
        $fmStockText      string                   stock pill wording
        $fmStockQty       int                      purchasable units
        $fmHasVariants    bool
        $fmRatingCount    int
        $fmRatingAvg      float
        $fmHasReviews     bool
        $fmDetailsOldPrice bool
        $fmOldPrice       float
        $fmSaveAmount     float
        $fmSavePct        int
        $fmNarrative      string|null              description teaser
        $fmVariantGroups  array                    prepared attribute pills
        $fmWhatsapp       string|null              WhatsApp order URL

    The stepper writes into both forms' hidden qty inputs and the pill clicks
    rebuild variant[] in both forms (pages/product-details.blade.php script),
    so the fm-app.js ajax cart and the classic buy-now POST always receive the
    same selection. No selection still means the DEFAULT variant, exactly
    like the classic design.
-------------------------------------------------------------------------- --}}

@php
    $fmProduct       = $fmProduct ?? $product;
    $fmBrandChip     = $fmBrandChip ?? null;
    $fmNarrative     = $fmNarrative ?? null;
    $fmVariantGroups = $fmVariantGroups ?? [];
    $fmWhatsapp      = $fmWhatsapp ?? null;
    $fmRatingCount   = $fmRatingCount ?? 0;
    $fmRatingAvg     = $fmRatingAvg ?? 0;
    $fmHasReviews    = $fmHasReviews ?? false;
    $fmOldPrice      = $fmOldPrice ?? 0;
    $fmSaveAmount    = $fmSaveAmount ?? 0;
    $fmSavePct       = $fmSavePct ?? 0;
    $fmStockQty      = $fmStockQty ?? 0;

    // Shared promises: the banner below shows the first three, the
    // description tab reuses the last one. One definition in ShopPromises.
    $fmServices = \App\Support\ShopPromises::all();
@endphp

<div class="lg:col-span-6 flex flex-col gap-6">

    {{-- Meta header --}}
    <div class="flex flex-col gap-2">
        {{-- Brand + SKU are secondary identity: on a phone they competed with
             the stock pill for a 257px row, so the chip broke across two lines
             and "SKU: FS-7103" split after the colon. Below 640px the pair is
             hidden and the stock pill gets the line to itself; from 640px up
             both fit, and whitespace-nowrap keeps each figure in one piece. --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="hidden sm:flex items-center gap-2 min-w-0">
                @if($fmBrandChip)
                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-100 whitespace-nowrap">{{ $fmBrandChip }}</span>
                    <span class="text-gray-300">&bull;</span>
                @endif
                <span class="text-xs text-gray-500 font-medium whitespace-nowrap">SKU: <span id="fmDetailSku">{{ $fmProduct->sku ?: 'N/A' }}</span></span>
            </div>
            <span id="fmDetailStockPill" class="fm-stock-pill inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full {{ $fmInStock ? '' : 'is-out' }}">
                <span class="fm-stock-dot w-2 h-2 rounded-full animate-pulse"></span>
                <span id="fmDetailStockText">{{ $fmStockText }}</span>
            </span>
        </div>

        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 leading-tight tracking-tight">
            {{ $fmProduct->name }}
        </h1>

        {{-- Ratings & review link --}}
        <div class="flex flex-wrap items-center gap-3 pt-1 text-sm">
            @if($fmHasReviews)
                <div class="flex items-center text-amber-500" title="{{ $fmRatingAvg }} / 5">
                    @include('frontend.fm.partials._stars', ['fmStarValue' => $fmRatingAvg, 'fmStarSize' => 'text-sm'])
                    <span class="text-gray-900 font-bold ml-1.5">{{ $fmRatingAvg }}</span>
                </div>
                <span class="text-gray-300">&bull;</span>
            @endif
            <a class="text-emerald-800 underline font-semibold hover:text-emerald-900 transition-colors"
               href="#customer-reviews" data-fm-open-tab="tab-reviews">
                {{ $fmRatingCount }} reviews
            </a>
        </div>
    </div>

    {{-- Pricing box --}}
    <div class="p-4 md:p-5 rounded-2xl bg-gray-50 border border-gray-200/80 flex flex-wrap items-center justify-between gap-4 shadow-sm fm-detail-pricebox" id="fmDetailPriceBox">
        {{-- gap-y + wrap: at 390px the old price and the save badge used to be
             squeezed onto the price's line and both broke mid-number
             ("Tk" / "350"). Wrapping the group and pinning each figure to one
             line keeps every amount readable; nothing is hidden. --}}
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-900 tracking-tight whitespace-nowrap" id="fmDetailPrice">Tk {{ $fmProduct->new_price }}</span>
            <span class="text-base sm:text-lg text-gray-500 line-through whitespace-nowrap {{ $fmDetailsOldPrice ? '' : 'hidden' }}" id="fmDetailOldPrice">Tk {{ $fmOldPrice }}</span>
            <span class="text-xs font-bold bg-amber-400 text-slate-900 px-2.5 py-1 rounded-md shadow-sm whitespace-nowrap {{ $fmDetailsOldPrice ? '' : 'hidden' }}" id="fmDetailSave">
                Save Tk <span id="fmDetailSaveAmount">{{ $fmSaveAmount }}</span> (<span id="fmDetailSavePct">{{ $fmSavePct }}</span>%)
            </span>
        </div>
    </div>

    {{-- Description teaser --}}
    @if($fmNarrative)
        <p class="text-sm md:text-base text-gray-600 leading-relaxed">{{ $fmNarrative }}</p>
    @endif

    {{-- Variant selectors --}}
    @foreach($fmVariantGroups as $fmGroupIndex => $fmGroup)
        <div class="flex flex-col gap-2.5">
            <label class="text-xs md:text-sm font-bold text-gray-900 flex justify-between items-center">
                <span>Select {{ $fmGroup['name'] }}:</span>
                <span class="text-emerald-800 font-extrabold text-xs md:text-sm" data-attr-selected>Select options</span>
            </label>

            <div class="fm-pill-group {{ $fmGroupIndex > 0 ? 'fm-pill-group--row' : '' }}"
                 data-attr-slug="{{ $fmGroup['slug'] }}">
                @foreach($fmGroup['values'] as $fmVal)
                    @php
                        // A swatch is drawn only when the admin stored a real
                        // hex colour for this value. Without one the pill keeps
                        // its plain text label, so the page never invents a
                        // shade the shop does not actually sell.
                        $fmHasColour = !empty($fmVal['colour']);
                        $fmPillClass = 'fm-attr-pill' . ($fmHasColour ? ' fm-attr-pill--colour' : '');
                    @endphp
                    <button type="button"
                            class="{{ $fmPillClass }}"
                            data-attr-id="{{ $fmVal['id'] }}"
                            data-label="{{ $fmVal['label'] }}"
                            @if($fmHasColour) data-colour="{{ $fmVal['colour'] }}" @endif
                            @if($fmVal['price'] !== null) data-price="{{ $fmVal['price'] }}" @endif>
                        @if($fmHasColour)
                            <span class="fm-colour-chip" style="--fm-chip: {{ $fmVal['colour'] }};" aria-hidden="true"></span>
                        @endif
                        <span class="flex justify-between items-center w-full gap-2">
                            <span class="text-xs font-bold">{{ $fmVal['label'] }}</span>
                            <i class="bi bi-check-circle-fill text-xs fm-pill-check"></i>
                        </span>
                        @if($fmVal['price'] !== null)
                            <span class="fm-pill-price text-sm font-bold text-emerald-800 mt-2">Tk {{ $fmVal['price'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- One note for the whole selection: partial picks, invalid combinations
         and offline stock checks all report here. --}}
    <p class="fm-variant-note text-xs text-red-600 font-semibold hidden m-0" id="fmVariantNote"></p>

    @if($fmInStock)
        {{-- Quantity + purchase CTAs --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
            {{-- Stepper --}}
            <div class="inline-flex items-center justify-between bg-gray-50 border border-gray-200 rounded-xl p-1 w-full sm:w-36">
                <button class="w-9 h-9 rounded-lg bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 flex items-center justify-center transition-colors" data-fm-qty="-1" title="Decrease" aria-label="Decrease quantity">
                    <i class="bi bi-dash font-bold"></i>
                </button>
                <input class="w-12 text-center bg-transparent text-base font-extrabold text-gray-900 focus:outline-none"
                       id="fmDetailQty" readonly type="text" value="1" aria-label="Quantity">
                <button class="w-9 h-9 rounded-lg bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 flex items-center justify-center transition-colors" data-fm-qty="1" title="Increase" aria-label="Increase quantity">
                    <i class="bi bi-plus font-bold"></i>
                </button>
            </div>

            {{-- Add to cart: fm-app.js intercepts data-add-to-cart --}}
            <form method="POST" action="{{ route('cart.store') }}" data-add-to-cart class="flex-1">
                @csrf
                <input type="hidden" name="id" value="{{ $fmProduct->id }}">
                <input type="hidden" name="qty" value="1" data-fm-qty-source>
                <div data-fm-variant-slot></div>
                <button type="submit" data-fm-cta
                        class="w-full py-3 px-6 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.98]">
                    <i class="bi bi-basket2-fill text-base"></i>
                    <span>Add to cart</span>
                </button>
            </form>

            {{-- Instant buy: plain POST, same payload as classic Order Now --}}
            <form method="POST" action="{{ route('cart.store.buy') }}">
                @csrf
                <input type="hidden" name="id" value="{{ $fmProduct->id }}">
                <input type="hidden" name="qty" value="1" data-fm-qty-source>
                <div data-fm-variant-slot></div>
                <button type="submit" data-fm-cta
                        class="w-full py-3 px-6 rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-900 font-bold text-sm shadow-sm transition-all whitespace-nowrap">
                    Buy now
                </button>
            </form>
        </div>

        @if($fmWhatsapp)
            <a class="inline-flex items-center justify-center gap-2 py-2.5 px-6 rounded-xl border-2 border-emerald-800 text-emerald-800 hover:bg-emerald-50 font-bold text-sm transition-all"
               href="{{ $fmWhatsapp }}" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Order on WhatsApp
            </a>
        @endif
    @else
        {{-- Out of stock: the classic design hides the buttons, keep that --}}
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm font-semibold flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i> This product is out of stock right now
        </div>
    @endif

    {{-- Value propositions banner --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-gray-50 border border-gray-200/80 mt-1">
        @foreach(array_slice($fmServices, 0, 3) as $fmService)
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center">
                    <i class="bi {{ $fmService->icon }} text-sm"></i>
                </span>
                <div>
                    <p class="text-xs font-bold text-gray-900 m-0">{{ $fmService->title }}</p>
                    <p class="text-xs text-gray-500 m-0">{{ $fmService->text }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Share row: carried over from the classic design --}}
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <b class="text-gray-900">Share:</b>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" title="Share on Facebook" class="text-gray-500 hover:text-emerald-800 transition-colors">
            <i class="bi bi-facebook text-base"></i>
        </a>
        <a href="https://pinterest.com/pin/create/button/?url={{ urlencode(url()->current()) }}&description={{ urlencode($fmProduct->name) }}" target="_blank" rel="noopener" title="Share on Pinterest" class="text-gray-500 hover:text-emerald-800 transition-colors">
            <i class="bi bi-pinterest text-base"></i>
        </a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($fmProduct->name) }}" target="_blank" rel="noopener" title="Share on LinkedIn" class="text-gray-500 hover:text-emerald-800 transition-colors">
            <i class="bi bi-linkedin text-base"></i>
        </a>
        <a href="https://twitter.com/intent/tweet?text={{ urlencode($fmProduct->name . ' ' . url()->current()) }}" target="_blank" rel="noopener" title="Share on X" class="text-gray-500 hover:text-emerald-800 transition-colors">
            <i class="bi bi-twitter-x text-base"></i>
        </a>
    </div>
</div>
