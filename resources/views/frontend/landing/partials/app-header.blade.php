@php
    $brandName = $tpl->text('brand_name', config('app.name', 'ZEvo'));
    $brandInitial = mb_substr($brandName, 0, 1);
    $brandSub = $tpl->text('logo_text', 'Official Store');
    if ($brandSub === $brandName) {
        $brandSub = 'Official Store';
    }
    $hotline = $tpl->text('hotline_number');
    $headerCta = $tpl->isEnabled('header_cta')
        ? $tpl->text('header_cta', 'অর্ডার করুন')
        : 'অর্ডার করুন';
@endphp
<header class="zlt-header" data-purpose="app-name-header">
    <div class="zlt-container zlt-header-inner">
        <a class="zlt-brand" href="{{ url('/') }}" aria-label="{{ $brandName }}">
            <span class="zlt-brand-mark" aria-hidden="true">{{ $brandInitial }}</span>
            <span>
                {{ $brandName }}
                <span class="zlt-brand-sub">{{ $tpl->text('logo_text', $brandName) }}</span>
            </span>
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
