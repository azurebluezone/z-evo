{{-- Hero Banner Section --}}
<div class="container banner-container ">
 
<div class="hero-section mb-60">
    <div class="left-site-category active">
        <div class="category-dropdown">
            @include('frontend.partials.category-menu')
        </div>
    </div>

    <div class="hero-slider">
        <div class="slider-wrapper overflow-hidden rounded--5">
            <div class="banner-slider owl-theme owl-carousel">
                @php
                    $activeBanners = $banners ?? collect();
                @endphp
                @if($activeBanners->count() > 0)
                    @foreach($activeBanners as $banner)
                        <div class="slide-item">
                            <a href="{{ $banner->link ?? '#' }}" class="d-block banner-image-container">
                                @php
                                    $mediaType = $banner->media_type ?? 'image';

                                    $bannerRaw = (string) ($banner->image ?? '');
                                    $bannerIsUrl = preg_match('~^https?://~i', $bannerRaw) === 1;
                                    $bannerPath = $bannerIsUrl ? $bannerRaw : ltrim(preg_replace('~^public/~', '', $bannerRaw), '/');
                                    $bannerSrc = !empty($bannerPath)
                                        ? ($bannerIsUrl ? $bannerPath : asset($bannerPath))
                                        : asset('assets/images/frontend/banner/6718b8ecbd6a11729673452.png');

                                    $videoRaw = (string) ($banner->video ?? '');
                                    $videoIsUrl = preg_match('~^https?://~i', $videoRaw) === 1;
                                    $videoPath = $videoIsUrl ? $videoRaw : ltrim(preg_replace('~^public/~', '', $videoRaw), '/');
                                    $hasVideo = !empty($videoRaw) && ($videoIsUrl || \Illuminate\Support\Facades\File::exists(public_path($videoPath)));
                                @endphp

                                @if($mediaType === 'video' && $hasVideo)
                                    <video class="w-100" autoplay muted loop playsinline preload="metadata" style="display:block;width:100%;height:400px;object-fit:cover;">
                                        <source src="{{ $videoIsUrl ? $videoRaw : asset($videoPath) }}" type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ $bannerSrc }}" alt="{{ $banner->title ?? 'slider-image' }}" 
                                         width="1200" height="400" loading="eager" decoding="async">
                                @endif
                            </a>
                        </div>
                    @endforeach
                @else
                    <div class="slide-item">
                        <a href="#" class="d-block banner-image-container">
                            <img src="{{ asset('assets/images/frontend/banner/6718b8ecbd6a11729673452.png') }}" alt="slider-image"
                                 width="1200" height="400" loading="eager" decoding="async">
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden">
            <div class="featured-category-slider owl-theme owl-carousel">
                @php
                    $featuredCats = $featured_categories ?? collect();
                @endphp
                @if($featuredCats->count() > 0)
                    @foreach($featuredCats as $category)
                        <div class="banner-categories p-2">
                            <a href="{{ route('products.category', $category->slug) }}" class="d-block text-center category-image-container">
                                @php
                                    $raw = (string) ($category->image ?? 'uploads/category/default.png');
                                    $isUrl = preg_match('~^https?://~i', $raw) === 1;
                                    $path = $isUrl ? $raw : ltrim(preg_replace('~^public/~', '', $raw), '/');
                                    $src = !empty($path)
                                        ? ($isUrl ? $path : asset($path))
                                        : asset('uploads/category/default.png');
                                @endphp
                                <img src="{{ $src }}"
                                     data-src="{{ $src }}"
                                     alt="category" class="w-100 lazyload owl-lazy"
                                     width="150" height="150" decoding="async">
                                <span class="title line-limitation-1">{{ $category->name }}</span>
                            </a>
                        </div>
                    @endforeach
                @else
                    {{-- Fallback categories if none in DB --}}
                    @foreach(['Skincare' => '6721e05a9ea1c1730273370.png', 'Makeup' => '6721e012cd1a51730273298.png', 'Fragrances' => '6721e022ddc0d1730273314.png'] as $name => $img)
                        <div class="banner-categories p-2">
                            <a href="#" class="d-block text-center category-image-container">
                                <img src="{{ asset('uploads/category/default.png') }}"
                                     data-src="{{ asset('uploads/category/default.png') }}"
                                     alt="category" class="w-100 lazyload owl-lazy"
                                     width="150" height="150" decoding="async">
                                <span class="title line-limitation-1">{{ $name }}</span>
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

   
</div>

 </div>
