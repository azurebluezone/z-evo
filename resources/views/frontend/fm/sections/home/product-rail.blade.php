{{--
    FM:PARTIAL home/product-rail --------------------------------------------
    One titled row of v2 cards on the redesigned home page.

    Expects
        title     string            the rail heading
        eyebrow   string|null       the small badge above it
        text      string|null       one line under the heading
        products  Collection|Paginator  required
        link      string|null       "view all" target
        linkText  string|null
        limit     int  (default 8)
        variant   'home'|'catalog'  (default 'home') - card flavour

    The previous home page rendered its rails through sections/product-feeds,
    which is still the file the classic-flavoured pages use. This rail is the
    one that speaks the new design's grid and card.
    --------------------------------------------------------------------- --}}

@php
    $fmRailTitle    = $title ?? '';
    $fmRailEyebrow  = $eyebrow ?? null;
    $fmRailText     = $text ?? null;
    $fmRailLink     = $link ?? null;
    $fmRailLinkText = $linkText ?? 'View all';
    $fmRailLimit    = $limit ?? 8;
    $fmRailVariant  = $variant ?? 'home';
    $fmRailItems    = collect($products ?? collect())->take($fmRailLimit);
@endphp

@if($fmRailItems->isNotEmpty())
    <section class="fm-products-section" data-purpose="product-feed">
        <div class="fm-container">

            <div class="fm-section-header">
                <div class="fm-section-header-left">
                    @if(filled($fmRailEyebrow))
                        <span class="fm-eyebrow-badge">{{ $fmRailEyebrow }}</span>
                    @endif
                    <h2 class="fm-section-title">{{ $fmRailTitle }}</h2>
                    @if(filled($fmRailText))
                        <p class="fm-section-desc">{{ $fmRailText }}</p>
                    @endif
                </div>

                @if(filled($fmRailLink))
                    <a class="fm-view-all" href="{{ $fmRailLink }}">
                        {{ $fmRailLinkText }}
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif
            </div>

            <x-fm::product-grid
                :products="$fmRailItems"
                card="v2"
                variant="{{ $fmRailVariant }}" />

        </div>
    </section>
@endif