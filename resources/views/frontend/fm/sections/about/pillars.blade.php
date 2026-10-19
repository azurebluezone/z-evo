{{--
    FM:PARTIAL about/pillars
    =========================================================================
    "How we shop": four promises in a tinted band.

    The reference's four pillars are kept as a structure, but the subjects are
    replaced - each one is something this storefront actually does and a visitor
    can verify in the catalogue or at checkout. The copy lives with the layout
    here; the page passes the array so the wording and the order are decided
    once, next to the other About page data.

    Props
        pillars  array  [['icon' => string, 'title' => string, 'text' => string], ...]

    Usage
        @include('frontend.fm.sections.about.pillars', ['pillars' => $fmPillars])
--}}

{{-- ----------------------------------------------------------------------
     Four pillars.
     ---------------------------------------------------------------------- --}}
<section class="fm-about__section fm-about__section--tint">
    <div class="container mx-auto px-4">
        <h2 class="fm-about__heading">How we shop</h2>
        <div class="row g-3 mt-1">
            @foreach ($pillars as $fmPillar)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="fm-about__pillar h-100">
                        <span class="fm-about__pillar-icon" aria-hidden="true">
                            <i class="bi {{ $fmPillar['icon'] }}"></i>
                        </span>
                        <h3 class="fm-about__pillar-title">{{ $fmPillar['title'] }}</h3>
                        <p class="fm-about__pillar-text">{{ $fmPillar['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>