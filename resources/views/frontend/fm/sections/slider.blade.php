{{-- FM:SECTION slider -------------------------------------------------------
     Full-width banner slider sitting at the very top of the page, directly
     under the header (design brief: "page start, full width"). Fed by the
     banners table: an image slide renders image + title pill, a video slide
     renders autoplay muted video only - no overlay. Bootstrap 5 carousel
     (the FM set ships Bootstrap, not owl-carousel).
     --------------------------------------------------------------------- --}}

@php
    $fmSlides = collect($banners ?? [])->filter(function ($banner) {
        $isVideo = (string) ($banner->media_type ?? 'image') === 'video'
            && filled($banner->video ?? null);
        return filled($banner->image ?? null) || $isVideo;
    })->values();

    $fmSliderImage = static function ($banner) {
        $raw = (string) ($banner->image ?? '');
        if (preg_match('~^https?://~i', $raw) === 1) {
            return $raw;
        }
        return \App\Support\MediaPath::url($banner->image, 'banner');
    };

    $fmSliderVideo = static function ($banner) {
        $raw = (string) ($banner->video ?? '');
        if (preg_match('~^https?://~i', $raw) === 1) {
            return $raw;
        }
        return asset(ltrim(preg_replace('~^public/~', '', $raw), '/'));
    };
@endphp

@if($fmSlides->isNotEmpty())
    <section class="fm-slider" data-purpose="banner-slider">
        <div id="fmFullSlider" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4500">
            <div class="carousel-inner">
                @foreach($fmSlides as $fmSlideIndex => $fmSlide)
                    <div class="carousel-item {{ $fmSlideIndex === 0 ? 'active' : '' }}">
                        @php
                            $fmSlideIsVideo = (string) ($fmSlide->media_type ?? 'image') === 'video'
                                && filled($fmSlide->video ?? null);
                        @endphp

                        @if($fmSlideIsVideo)
                            {{-- Video slide: video only, no title overlay. --}}
                            <a href="{{ $fmSlide->link ?? route('products.page') }}" class="fm-slider__media d-block">
                                <video class="fm-slider__video" autoplay muted loop playsinline preload="metadata">
                                    <source src="{{ $fmSliderVideo($fmSlide) }}" type="video/mp4">
                                </video>
                            </a>
                        @else
                            {{-- Image slide: image with the title pill. --}}
                            <a href="{{ $fmSlide->link ?? route('products.page') }}" class="fm-slider__media d-block">
                                <img src="{{ $fmSliderImage($fmSlide) }}"
                                     alt="{{ $fmSlide->title ?: ($generalsetting->name ?? config('app.name')) }}"
                                     width="1376" height="560"
                                     fetchpriority="{{ $fmSlideIndex === 0 ? 'high' : 'low' }}" decoding="async">
                                @if(filled($fmSlide->title ?? null))
                                    <span class="fm-slider__title">{{ $fmSlide->title }}</span>
                                @endif
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($fmSlides->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#fmFullSlider" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#fmFullSlider" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                <div class="carousel-indicators">
                    @foreach($fmSlides as $fmSlideIndex => $fmSlideDot)
                        <button type="button" data-bs-target="#fmFullSlider" data-bs-slide-to="{{ $fmSlideIndex }}"
                                class="{{ $fmSlideIndex === 0 ? 'active' : '' }}"
                                aria-label="Slide {{ $fmSlideIndex + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
