{{-- Top Brands Section --}}
@if(isset($featured_brands) && count($featured_brands) > 0)
    <section class="my-60">
        <div class="container">
            <div class="section-header">
                <h5 class="title">{{ $sections['brands']['title'] ?? 'Top Brands' }}</h5>
            </div>

            <div class="small-card">
                @foreach($featured_brands as $brand)
                    <div class="small-card-item text-center">
                        <a href="{{ route('products.brand', $brand->slug) }}">
                            @php
                                $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
                                $fallbackImage = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                                $fallbackRaw = (string) $fallbackImage;
                                $fallbackIsUrl = preg_match('~^https?://~i', $fallbackRaw) === 1;
                                $fallbackPath = $fallbackIsUrl ? $fallbackRaw : ltrim(preg_replace('~^public/~', '', $fallbackRaw), '/');
                                $fallbackImageSrc = !empty($fallbackPath)
                                    ? ($fallbackIsUrl ? $fallbackPath : asset($fallbackPath))
                                    : asset('assets/images/empty.png');

                                $brandImagePath = $brand->logo ?? $brand->image ?? '';
                                $brandRaw = (string) $brandImagePath;
                                $brandIsUrl = preg_match('~^https?://~i', $brandRaw) === 1;
                                $brandPath = $brandIsUrl ? $brandRaw : ltrim(preg_replace('~^public/~', '', $brandRaw), '/');
                                $brandImageSrc = !empty($brandPath)
                                    ? ($brandIsUrl ? $brandPath : asset($brandPath))
                                    : $fallbackImageSrc;
                            @endphp
                            <img src="{{ $brandImageSrc }}"
                                data-src="{{ $brandImageSrc }}" class="w-100 lazyload"
                                alt="brand">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
