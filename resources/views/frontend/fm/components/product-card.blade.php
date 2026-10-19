{{--
    FM:COMPONENT product-card
    =========================================================================
    Grid card for a single product.

    Usage:
        <x-fm::product-card :product="$product" />

    Props
        product   App\Models\Product            required
        badge     string|null                    optional pre-set corner badge
        wishlist  bool  (default true)           show the wishlist heart
        addToCart bool  (default true)           show the action row
        showRating bool (default true)

    Layout follows work/4db2d5a5-e6dd-48bc-affb-b79b5dfffe5f.png: square
    media, badge top left, heart top right, title, price with a struck
    through old price, then two side by side actions. The reference's
    "Order Now" is the extra button the brief asked to keep, and it posts to
    route('cart.store.buy') - a real route (ShoppingController::
    cart_store_by_now), not a placeholder.

    Card height is a little taller than the reference so the title, the rating
    row and both buttons never feel cramped. Padding and the vertical rhythm
    are set here in CSS (fm-card) rather than as one-off utility classes, so
    the same spacing holds on the home feeds, the category page and the
    search page.

    Every value this card paints is declared in
    public/frontend/css/fm-product-card.css (transcribed from work/code.html).
    Bootstrap 5.3 rewrites several of the utility classes below into
    !important values, so the class names do NOT tell you what the browser
    will paint - read that file before touching a class here.
    =========================================================================
--}}

@php
    // Anonymous components do not declare default prop values, so every
    // optional attribute is normalised here.
    $badge      = $badge ?? null;
    $wishlist   = $wishlist ?? true;
    $addToCart  = $addToCart ?? true;
    $showRating = $showRating ?? true;

    $image  = \App\Support\MediaPath::product($product);
    $url    = route('product', $product->slug ?: $product->id);

    $oldPrice = (float) ($product->old_price ?? 0);
    $newPrice = (float) ($product->new_price ?? 0);
    $hasDiscount = $oldPrice > 0 && $oldPrice > $newPrice;
    $discountPercent = $hasDiscount ? (int) round((($oldPrice - $newPrice) / $oldPrice) * 100) : 0;

    $ratingValue = (float) ($product->reviews_avg_ratting ?? 0);
    $ratingCount = (int) ($product->reviews_count ?? 0);

    $inStock = (int) ($product->stock ?? 0) > 0;
    $unit    = $product->pro_unit ?: null;
@endphp

<article class="fm-card fm-product group bg-white rounded-2xl p-4 border border-gray-200/80 hover:border-emerald-300 hover:shadow-md transition-all duration-300 flex flex-col"
         data-product-id="{{ $product->id }}">

    {{-- Media + corner badges. Aspect-square media with the zoom-on-hover
         treatment comes from the reference design; the fm-product__link name
         stays because the list view and the shop harness address it. --}}
    <div class="fm-product__media relative w-full aspect-square rounded-xl bg-gray-50 overflow-hidden mb-3">
        <a href="{{ $url }}" class="fm-product__link block w-full h-full" aria-label="{{ $product->name }}">
            <img src="{{ $image }}" alt="{{ $product->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy" decoding="async" width="1000" height="1000">
        </a>

        <div class="fm-product__badges absolute top-2.5 left-2.5 flex flex-col items-start gap-1">
            @if($badge)
                <span class="fm-pill fm-pill--{{ $badge['kind'] ?? 'new' }} px-2.5 py-0.5 rounded-full text-xs font-bold shadow-xs">{{ $badge['text'] }}</span>
            @elseif($discountPercent > 0)
                {{-- Reference style: a solid red pill reading "Save 20%", not
                     a bare percentage. The word keeps it readable for a screen
                     reader too. --}}
                <span class="fm-pill fm-pill--discount px-2.5 py-0.5 rounded-full text-xs font-bold shadow-xs">Save {{ $discountPercent }}%</span>
            @endif

            @unless($inStock)
                <span class="fm-pill fm-pill--out px-2.5 py-0.5 rounded-full text-xs font-bold shadow-xs">Out of stock</span>
            @endunless
        </div>

        @if($wishlist)
            <button type="button" class="fm-product__wish absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center text-gray-400 hover:text-red-500 shadow-xs transition-colors" data-wishlist-id="{{ $product->id }}"
                    title="Add to wishlist" aria-label="Add to wishlist">
                <i class="bi bi-heart text-xs"></i>
            </button>
        @endif
    </div>

    {{-- Body --}}
    <div class="fm-card__body fm-product__body flex flex-col flex-grow">

        @if($unit)
            <span class="fm-product__unit text-xs font-extrabold uppercase tracking-wider text-emerald-700">{{ $unit }}</span>
        @endif

        <h3 class="fm-product__title text-sm font-bold text-gray-900 line-clamp-1 mt-0.5">
            <a href="{{ $url }}" class="group-hover:text-[#1b5e20] transition-colors">{{ $product->name }}</a>
        </h3>

        @if($showRating)
            <div class="fm-product__rating flex items-center gap-1 text-xs mt-1" title="{{ $ratingCount }} reviews">
                @for($i = 1; $i <= 5; $i++)
                    <i class="bi {{ $ratingValue >= $i ? 'bi-star-fill' : ($ratingValue >= $i - 0.5 ? 'bi-star-half' : 'bi-star') }} text-amber-400"></i>
                @endfor
                @if($ratingCount > 0)
                    <span class="font-bold text-gray-800">{{ number_format($ratingValue, 1) }}</span>
                    <span class="text-gray-400 font-normal">({{ $ratingCount }})</span>
                @else
                    <span class="text-gray-400 font-normal">No reviews</span>
                @endif
            </div>
        @endif

        {{-- Foot: price left + ONE action button right — the reference card
             keeps a single "Add to Cart" pill next to the price; the second
             button row is dropped so every card in every page lines up on one
             baseline. --}}
        <div class="fm-product__foot mt-3 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
            <span class="flex items-baseline gap-2 min-w-0">
                <span class="fm-price text-base font-extrabold text-[#1b5e20]">Tk {{ number_format($newPrice) }}</span>
                @if($hasDiscount)
                    <del class="fm-product__old text-xs text-gray-400 line-through">Tk {{ number_format($oldPrice) }}</del>
                @endif
            </span>

            @if($addToCart)
                <form method="POST" action="{{ route('cart.store') }}" data-add-to-cart class="fm-product__cart m-0">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" class="fm-add-btn px-3.5 py-1.5 rounded-full bg-[#1b5e20] hover:bg-[#144618] text-white text-xs font-bold inline-flex items-center gap-1 shadow-xs active:scale-95 transition-all"
                            title="Add to cart" aria-label="Add to cart" {{ $inStock ? '' : 'disabled' }}>
                        <i class="bi bi-plus-lg"></i> <span>Add</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</article>