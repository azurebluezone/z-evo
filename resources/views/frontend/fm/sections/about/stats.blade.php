{{--
    FM:PARTIAL about/stats
    =========================================================================
    The four-figure band under the hero: catalogue size, departments, brands
    and the delivery area.

    Every value is counted by the page and handed in - this partial only lays
    them out, so the numbers can never be a copy-pasted figure that drifts away
    from the catalogue.

    Props
        stats  array  [['value' => string, 'label' => string, 'icon' => string], ...]

    Usage
        @include('frontend.fm.sections.about.stats', ['stats' => $fmStats])
--}}

{{-- ----------------------------------------------------------------------
     Stats band. Every value counted from the catalogue.
     ---------------------------------------------------------------------- --}}
<section class="fm-about__stats" aria-label="Shop at a glance">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($stats as $fmStat)
                <div class="fm-about__stat">
                    <i class="bi {{ $fmStat['icon'] }} fm-about__stat-icon" aria-hidden="true"></i>
                    <span class="fm-about__stat-value">{{ $fmStat['value'] }}</span>
                    <span class="fm-about__stat-label">{{ $fmStat['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>