{{-- FM:SECTION promo-banner ------------------------------------------------
     Large seasonal callout. Two data sources, both already prepared by the
     controller:
       - $promo_banners  (admin managed banner rows)
       - $flash_sale     (campaign products with a countdown)
     --------------------------------------------------------------------- --}}

@php
    $fmPromo = collect($promo_banners ?? [])->first();

    // Build the offer line from the deepest discount actually in the catalogue.
    $fmBestDeal = collect($discount_products ?? [])->first(function ($product) {
        return (float) ($product->old_price ?? 0) > (float) ($product->new_price ?? 0);
    }) ?: collect($discount_products ?? [])->first();

    $fmPromoTitle    = $fmPromo->title ?? ($generalsetting->home_footer_title ?? null);
    $fmPromoImage    = $fmPromo ? \App\Support\MediaPath::url($fmPromo->image, 'promo-banner') : null;
    $fmPromoLink     = $fmPromo->link ?? route('hotdeals');
    $fmPromoOff      = $fmBestDeal
        ? (int) round((((float) $fmBestDeal->old_price) - (float) $fmBestDeal->new_price) / (float) $fmBestDeal->old_price * 100)
        : 0;
@endphp

@if($fmPromo || $fmBestDeal)
    <section class="fm-section" data-purpose="promo-banner">
        <div class="container mx-auto px-4">
            <div class="fm-promo">

                <div class="fm-promo__content">
                    @if($fmPromoTitle)
                        <span class="fm-promo__badge">Limited time offer</span>
                        <h2 class="fm-promo__title">{{ $fmPromoTitle }}</h2>
                    @endif

                    @if($fmPromoOff > 0)
                        <p class="fm-promo__text">
                            Get up to <strong>{{ $fmPromoOff }}% off</strong> on selected items.
                            Order before the offer ends.
                        </p>
                    @else
                        <p class="fm-promo__text">
                            Extra savings on selected items. Order before time runs out.
                        </p>
                    @endif

                    <div class="fm-promo__actions">
                        <a href="{{ $fmPromoLink }}" class="fm-btn fm-btn--light">
                            Buy now <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="{{ route('offers.index') }}" class="fm-btn fm-btn--ghost">All offers</a>
                    </div>
                </div>

                @if($fmPromoImage)
                    <div class="fm-promo__media">
                        <img src="{{ $fmPromoImage }}" alt="{{ $fmPromoTitle ?: 'Offer' }}"
                             loading="lazy" width="520" height="420">
                    </div>
                @elseif($fmBestDeal)
                    <div class="fm-promo__media">
                        <img src="{{ \App\Support\MediaPath::product($fmBestDeal) }}"
                             alt="{{ $fmBestDeal->name }}" loading="lazy" width="520" height="420">
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
