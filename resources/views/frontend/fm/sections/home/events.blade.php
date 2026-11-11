{{--
    FM:SECTION home/events --------------------------------------------------
    "Events" - up to nine live campaigns, not invented grocery days.

    The old version hardcoded "Farmers Market Day", "Organic Harvest Fest" and
    "Smart Grocery Demo Day" with relative dates. Those were design-mockup
    copy: a shop that does not run a farm was advertising one, and the dates
    moved every day. Campaigns are the CMS entity that already owns a title,
    image, dates, short description and public URL, so the section reads from
    there and disappears when nothing is live.
    --------------------------------------------------------------------- --}}

@php
    $fmEvents = collect($homeEvents ?? [])->map(function ($campaign) {
        $date = $campaign->start_date ?: $campaign->date;
        $timestamp = $date ? strtotime((string) $date) : null;

        return [
            'img' => filled($campaign->image_one) ? asset($campaign->image_one) : '',
            'fallback' => 'linear-gradient(135deg, #1e3a8a 0%, #172554 100%)',
            'plate' => (string) ($campaign->campaign_type ?: 'Event'),
            'day' => $timestamp ? date('d', $timestamp) : '',
            'mon' => $timestamp ? date('M', $timestamp) : '',
            'title' => (string) $campaign->name,
            'text' => (string) ($campaign->short_description ?? ''),
            'link' => filled($campaign->slug) ? route('campaign', $campaign->slug) : route('campaigns'),
            'cta' => (string) ($campaign->hero_button_text ?: 'Learn more'),
        ];
    });
@endphp

@if($fmEvents->isNotEmpty())
    <section class="fm-events-section" data-purpose="social-events">
        <div class="fm-container">

            <div class="fm-section-header">
                <div class="fm-section-header-left">
                    <span class="fm-eyebrow-badge">📅 Events</span>
                    <h2 class="fm-section-title">What's happening</h2>
                    <p class="fm-section-desc">Live campaigns and events from the shop.</p>
                </div>
            </div>

            <div class="fm-events-grid">
                @foreach($fmEvents as $fmEvent)
                    <article class="fm-event-card">
                        {{-- The inline gradient is both the reference treatment
                             and the artwork fallback: a card without an image
                             still reads as designed instead of broken. --}}
                        <div class="fm-event-media" style="background: {{ $fmEvent['fallback'] }};">
                            @if($fmEvent['img'] !== '')
                                <img src="{{ $fmEvent['img'] }}" alt="{{ $fmEvent['title'] }}"
                                     loading="lazy" decoding="async" width="600" height="280"
                                     onerror="this.style.display='none'">
                            @endif
                            @if($fmEvent['day'] !== '')
                                <span class="fm-event-date-pill">{{ $fmEvent['day'] }} {{ $fmEvent['mon'] }}</span>
                            @endif
                            <div class="fm-event-title-plate">{{ $fmEvent['plate'] }}</div>
                        </div>

                        <div class="fm-event-body">
                            <div>
                                <h3>{{ $fmEvent['title'] }}</h3>
                                @if($fmEvent['text'] !== '')
                                    <p>{{ $fmEvent['text'] }}</p>
                                @endif
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
@endif
