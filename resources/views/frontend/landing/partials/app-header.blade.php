@php
    $brandName = $tpl->text('brand_name', config('app.name', 'ZEvo'));
    $brandLogo = $tpl->text('brand_logo');
    $hotline = $tpl->text('hotline_number');
    $headerCta = $tpl->isEnabled('header_cta')
        ? $tpl->text('header_cta', 'অর্ডার করুন')
        : 'অর্ডার করুন';
@endphp
<header class="zlt-header" data-purpose="app-name-header">
    <div class="zlt-container zlt-header-inner">
        {{-- The application logo is the brand mark now; the typed-out name
             and its letter tile are gone. The image carries the accessible
             name, so the link still announces the shop. --}}
        <a class="zlt-brand" href="{{ url('/') }}">
            @if($brandLogo !== '')
                <img class="zlt-brand-logo"
                     src="{{ $brandLogo }}"
                     alt="{{ $brandName }}"
                     width="866"
                     height="288"
                     loading="eager"
                     decoding="async">
            @else
                <span class="zlt-brand-name">{{ $brandName }}</span>
            @endif
        </a>

        <nav class="zlt-nav" aria-label="Landing navigation">
            <a href="{{ url('/') }}">হোম</a>
            <a href="#landing-order">অর্ডার</a>
            @if($hotline)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $hotline) }}">হটলাইন: {{ $hotline }}</a>
            @endif
        </nav>

        <a class="zlt-header-cta" href="#landing-order">{{ $headerCta }}</a>
    </div>
</header>
