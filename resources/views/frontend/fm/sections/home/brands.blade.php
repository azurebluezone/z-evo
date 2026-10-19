{{--
    FM:SECTION home/brands --------------------------------------------------
    "Shop by brand" - the four-up logo row
    (design: work/home.html, "ShopByBrand").

    DATA
        $featured_brands  Brand[]  latest 12 active brands
        $brands           Brand[]  shared listing data, the fallback

    In the previous FM home page the brand strip lived at the bottom of the
    category grid (sections/categories.blade.php). The new design gives brands
    their own band, so this section is separate and the old one keeps its
    copy for any page that still includes it.

    Each tile links to the brand's own catalogue page - the same route the
    classic design uses - so a brand card is never a dead tile.
    --------------------------------------------------------------------- --}}

@php
    $fmBrands = ($featured_brands ?? null);

    if (! $fmBrands || $fmBrands->isEmpty()) {
        $fmBrands = collect($brands ?? []);
    }

    $fmTitle = (string) ($sections['brands']['title'] ?? 'Shop by brand');
@endphp

@if($fmBrands->isNotEmpty())
    <section class="fm-brands-section" data-purpose="trusted-brands">
        <div class="fm-container">

            {{--
                Task: three stacked lines (badge / title / description) felt
                heavy here. Only the section title stays, carrying a small
                emerald accent bar - defined in fm-home-v2.css as
                .fm-section-title--accent.
            --}}
            <div class="fm-section-header">
                <h2 class="fm-section-title fm-section-title--accent">{{ $fmTitle }}</h2>
                <a class="fm-view-all" href="{{ route('products.page') }}">
                    See all brands
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="fm-brands-grid">
                @foreach($fmBrands->take(8) as $fmBrand)
                    @php
                        $fmHasLogo = filled($fmBrand->image ?? null);
                        $fmLogo = \App\Support\MediaPath::url($fmBrand->image, 'brand');
                    @endphp

                    <a class="fm-brand-card" href="{{ route('products.brand', $fmBrand->slug) }}">
                        <span class="fm-brand-logo-icon">
                            @if($fmHasLogo)
                                <img src="{{ $fmLogo }}" alt="" loading="lazy" decoding="async"
                                     width="64" height="64">
                            @else
                                {{-- The reference draws an abstract mark when a
                                     brand has no logo; this is the same idea in
                                     the site's own icon set. --}}
                                <i class="bi bi-patch-check" aria-hidden="true"></i>
                            @endif
                        </span>
                        <span>{{ $fmBrand->name }}</span>
                    </a>
                @endforeach
            </div>

        </div>
    </section>
@endif