{{--
    FM:SECTION home/events --------------------------------------------------
    "Social Events" - the three community cards
    (design: work/home.html, "SocialEventsSection").

    Same three events and the same copy as sections/events.blade.php, which
    stays in place for any page still calling it; only the layout follows the
    new design. The reference paints a coloured gradient plate behind the
    title, which is kept as the fallback so a card never shows a broken image
    when its artwork is missing from public/uploads/events/.
    --------------------------------------------------------------------- --}}

@php
    // Same event list and wording as sections/events.blade.php. Dates are
    // relative so the strip is never stale.
    $fmEvents = [
        [
            'img'      => asset('uploads/events/farmers-market.jpg'),
            'fallback' => 'linear-gradient(135deg, #1e3a8a 0%, #172554 100%)',
            'plate'    => 'Farm Experience',
            'day'      => date('d', strtotime('+5 days')),
            'mon'      => date('M', strtotime('+5 days')),
            'title'    => 'Farmers Market Day',
            'text'     => 'Meet the growers behind your basket — tastings, live demos and seasonal harvests every month.',
            'link'     => route('contact'),
            'cta'      => 'Reserve a stall',
        ],
        [
            'img'      => asset('uploads/events/harvest-fest.jpg'),
            'fallback' => 'linear-gradient(135deg, #ea580c 0%, #7c2d12 100%)',
            'plate'    => 'Harvest Fair',
            'day'      => date('d', strtotime('+12 days')),
            'mon'      => date('M', strtotime('+12 days')),
            'title'    => 'Organic Harvest Fest',
            'text'     => 'A day of fresh-picked produce, farm tours and zero-waste workshops for the whole family.',
            'link'     => route('contact'),
            'cta'      => 'Get tickets',
        ],
        [
            'img'      => asset('uploads/events/ai-demo-day.jpg'),
            'fallback' => 'linear-gradient(135deg, #0f766e 0%, #134e4a 100%)',
            'plate'    => 'Innovation Day',
            'day'      => date('d', strtotime('+19 days')),
            'mon'      => date('M', strtotime('+19 days')),
            'title'    => 'Smart Grocery Demo Day',
            'text'     => 'See the AI-powered store in action — smart search, instant pricing and home delivery magic.',
            'link'     => route('products.page'),
            'cta'      => 'Explore shop',
        ],
    ];
@endphp

<section class="fm-events-section" data-purpose="social-events">
    <div class="fm-container">

        <div class="fm-section-header">
            <div class="fm-section-header-left">
                <span class="fm-eyebrow-badge">👥 Community</span>
                <h2 class="fm-section-title">Social Events</h2>
                <p class="fm-section-desc">Join us at the farm, the market and the kitchen.</p>
            </div>
        </div>

        <div class="fm-events-grid">
            @foreach($fmEvents as $fmEvent)
                <article class="fm-event-card">
                    {{-- The inline gradient is the reference's own treatment
                         AND the artwork fallback: if the jpg is missing the
                         plate still reads as a designed card. --}}
                    <div class="fm-event-media" style="background: {{ $fmEvent['fallback'] }};">
                        <img src="{{ $fmEvent['img'] }}" alt="{{ $fmEvent['title'] }}"
                             loading="lazy" decoding="async" width="600" height="280"
                             onerror="this.style.display='none'">
                        <span class="fm-event-date-pill">{{ $fmEvent['day'] }} {{ $fmEvent['mon'] }}</span>
                        <div class="fm-event-title-plate">{{ $fmEvent['plate'] }}</div>
                    </div>

                    <div class="fm-event-body">
                        <div>
                            <h3>{{ $fmEvent['title'] }}</h3>
                            <p>{{ $fmEvent['text'] }}</p>
                        </div>
                        <a class="fm-event-cta" href="{{ $fmEvent['link'] }}">
                            {{ $fmEvent['cta'] }}
                            <svg fill="none" height="12" stroke="currentColor" stroke-width="2.5"
                                 viewBox="0 0 24 24" width="12" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

    </div>
</section>