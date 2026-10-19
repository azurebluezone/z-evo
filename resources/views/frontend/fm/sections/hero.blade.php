{{-- FM:SECTION hero --------------------------------------------------------
     Top of page. Left column carries the copy and CTAs, right column shows the
     first active banner image. Falls back to a branded gradient panel when no
     banner row exists so the hero never renders empty.
     --------------------------------------------------------------------- --}}

@php
    $fmHeroBanner = collect($banners ?? [])->filter(function ($banner) {
        return (string) ($banner->image ?? '') !== '';
    })->first();

    $fmHeroImage = $fmHeroBanner ? \App\Support\MediaPath::url($fmHeroBanner->image, 'banner') : null;
    $fmHeroLink  = $fmHeroBanner->link ?? route('products.page');
    $fmHeroTitle = $fmHeroBanner->title ?? null;
@endphp

<section class="fm-hero" data-purpose="hero-banner">
    <div class="container mx-auto px-4">
        <div class="row align-items-center g-4 g-lg-5">

            <div class="col-12 col-lg-6">


                <h1 class="fm-hero__title">
                    Your favorite products,<br>
                    <span class="fm-hero__title-accent">at the best price</span> to your doorstep
                </h1>

                <p class="fm-hero__text">
                    {{ $generalsetting->description
                        ?: 'Trusted online shopping platform of Bangladesh. Thousands of products in one place, fast delivery and easy payment.' }}
                </p>

                <div class="fm-hero__cta">
                    <a href="{{ route('products.page') }}" class="fm-btn fm-btn--primary">
                        <i class="bi bi-bag-check"></i>
                        Buy now
                    </a>
                    <a href="{{ route('hotdeals') }}" class="fm-btn fm-btn--outline">
                        <i class="bi bi-fire"></i>
                        See hot deals
                    </a>
                </div>

                {{-- Trust badges --}}
                <ul class="fm-hero__trust">
                    <li><i class="bi bi-truck"></i><span>Fast delivery</span></li>
                    <li><i class="bi bi-shield-check"></i><span>Secure payment</span></li>
                    <li><i class="bi bi-arrow-counterclockwise"></i><span>Easy returns</span></li>
                </ul>
            </div>

            <div class="col-12 col-lg-6">
                <div class="fm-hero__visual">
                    @if($fmHeroImage)
                        <a href="{{ $fmHeroLink }}" class="fm-hero__figure">
                            <img src="{{ $fmHeroImage }}"
                                 alt="{{ $fmHeroTitle ?: ($generalsetting->name ?? config('app.name')) }}"
                                 width="720" height="520" fetchpriority="high" decoding="async">
                        </a>
                    @else
                        <div class="fm-hero__placeholder">
                            <i class="bi bi-bag-heart"></i>
                            <span>{{ $generalsetting->name ?? config('app.name') }}</span>
                        </div>
                    @endif

                    {{-- Floating price tag --}}
                    @php $fmHeroDeal = collect($discount_products ?? [])->first(); @endphp
                    @if($fmHeroBanner && $fmHeroDeal)
                        <div class="fm-hero__float">
                            <span class="fm-hero__float-label">Today's offer</span>
                            <strong class="fm-hero__float-value">Tk {{ number_format((float) ($fmHeroDeal->new_price ?? 0)) }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
