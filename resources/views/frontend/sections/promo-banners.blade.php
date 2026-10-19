{{-- Promo Banners Section --}}
@php
    $promoBanners = $promo_banners ?? collect();
    $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
    $fallbackImage = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
    $fallbackRaw = (string) $fallbackImage;
    $fallbackIsUrl = preg_match('~^https?://~i', $fallbackRaw) === 1;
    $fallbackPath = $fallbackIsUrl ? $fallbackRaw : ltrim(preg_replace('~^public/~', '', $fallbackRaw), '/');
    $fallbackImageSrc = !empty($fallbackPath)
        ? ($fallbackIsUrl ? $fallbackPath : asset($fallbackPath))
        : asset('assets/images/empty.png');
@endphp
@if($promoBanners->count() > 0)
<div class="site-section my-60">
    <div class="container">
        @if(!empty($sections['promo']['title'] ?? null))
            <div class="section-header left-style">
                <h5 class="title">{{ $sections['promo']['title'] }}</h5>
            </div>
        @endif
        <div class="row gy-3 gx-3 justify-content-center">
            @foreach($promoBanners as $banner)
                <div class="col-12 col-md-4">
                    <a href="{{ $banner->link ?? 'javascript:void(0)' }}" class="d-block overlay-effects rounded--5">
                        @php
                            $bannerRaw = (string) ($banner->image ?? '');
                            $bannerIsUrl = preg_match('~^https?://~i', $bannerRaw) === 1;
                            $bannerPath = $bannerIsUrl ? $bannerRaw : ltrim(preg_replace('~^public/~', '', $bannerRaw), '/');
                            $bannerSrc = !empty($bannerPath)
                                ? ($bannerIsUrl ? $bannerPath : asset($bannerPath))
                                : $fallbackImageSrc;
                        @endphp
                        <img src="{{ $fallbackImageSrc }}" 
                             data-src="{{ $bannerSrc }}" 
                             class="w-100 lazyload" 
                             alt="{{ $banner->title ?? '' }}">
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
