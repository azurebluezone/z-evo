{{--
    FM:SECTION events (Social Events)
    =========================================================================
    Community / social event cards for the grocery storefront. Uses images
    shipped in public/uploads/events/ (from work/pic/). Mobile-first: one
    column on phones, three from the md breakpoint. No empty state, so the
    section simply renders the three current social events.
    =========================================================================
--}}
<section class="fm-events my-5" data-purpose="social-events">
<div class="container">
@include('frontend.fm.sections.partials._section-head', [
    'eyebrow'   => 'Our Community',
    'heading'   => 'Social Events',
    'text'      => 'Join us at the farm, the market and the kitchen.',
])
<div class="row g-3 g-lg-4 mt-1">
@php
    $fmEvents = [
        [
            'img'   => asset('uploads/events/farmers-market.jpg'),
            'day'   => date('d', strtotime('+5 days')), 'mon' => date('M', strtotime('+5 days')),
            'title' => 'Farmers Market Day',
            'text'  => 'Meet the growers behind your basket — tastings, live demos and seasonal harvests every month.',
            'link'  => route('contact'),
            'cta'   => 'Reserve a stall',
        ],
        [
            'img'   => asset('uploads/events/harvest-fest.jpg'),
            'day'   => date('d', strtotime('+12 days')), 'mon' => date('M', strtotime('+12 days')),
            'title' => 'Organic Harvest Fest',
            'text'  => 'A day of fresh-picked produce, farm tours and zero-waste workshops for the whole family.',
            'link'  => route('contact'),
            'cta'   => 'Get tickets',
        ],
        [
            'img'   => asset('uploads/events/ai-demo-day.jpg'),
            'day'   => date('d', strtotime('+19 days')), 'mon' => date('M', strtotime('+19 days')),
            'title' => 'Smart Grocery Demo Day',
            'text'  => 'See the AI-powered store in action — smart search, instant pricing and home delivery magic.',
            'link'  => route('products.page'),
            'cta'   => 'Explore shop',
        ],
    ];
@endphp
@foreach($fmEvents as $fmEvent)
<div class="col-12 col-md-6 col-lg-4">
<article class="fm-event-card">
<div class="fm-event-media">
<img alt="{{ $fmEvent['title'] }}" class="fm-event-img" loading="lazy" src="{{ $fmEvent['img'] }}"/>
<div class="fm-event-date"><span>{{ $fmEvent['day'] }}</span><small>{{ $fmEvent['mon'] }}</small></div>
</div>
<div class="fm-event-body">
<h3 class="fm-event-title">{{ $fmEvent['title'] }}</h3>
<p class="fm-event-text">{{ $fmEvent['text'] }}</p>
<a class="fm-event-link" href="{{ $fmEvent['link'] }}">{{ $fmEvent['cta'] }} <i class="bi bi-arrow-right"></i></a>
</div>
</article>
</div>
@endforeach
</div>
</div>
</section>