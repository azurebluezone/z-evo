{{--
    FM:SECTION home/hero ----------------------------------------------------
    Full-bleed banner slider at the top of the redesigned home page.

    DATA (same banner fields the classic template reads)
    -------------------------------------------------------------------------
        $banners[]->image       string  path or full URL of the banner image
        $banners[]->video       string  path or full URL of the banner video
        $banners[]->media_type  string  'image' | 'video' (default 'image')
        $banners[]->link        string  slide target URL
        $banners[]->title       string  slide headline

    HOW A SLIDE PICKS ITS BACKGROUND
    -------------------------------------------------------------------------
    1. media_type = video and a usable video file  -> autoplay muted video
    2. otherwise a usable image                    -> cover image
    3. no image and no video                       -> the shop's fallback
       video (public/uploads/banner-video) when it exists on disk, else the
       classic fallback banner image. A slide is never an empty gradient.

    The image/video URL logic mirrors sections/hero-banner.blade.php exactly
    (full URLs pass through, relative paths are de-prefixed from "public/",
    the video is only claimed when the file really exists on disk).

    SLIDER HOOKS (do not rename - home.blade.php owns the behaviour)
    -------------------------------------------------------------------------
        [data-fm-hero]         slide rail
        [data-fm-hero-slide]   one slide, .is-active = visible
        [data-fm-hero-dot]     dot strip
        [data-fm-hero-prev]    arrow - previous
        [data-fm-hero-next]    arrow - next

    Every slide is painted into the DOM and only its opacity is toggled, so
    the first banner is on screen with JavaScript off.
    --------------------------------------------------------------------- --}}

@php
    // Public fallback media: a slide with neither image nor video still has
    // to show something real. The first .mp4 the shop has uploaded to
    // public/uploads/banner-video is picked automatically - no hard coded
    // file name to rot when the uploads are pruned - and the classic default
    // banner image is the last resort.
    $fmFallbackVideo = null;
    foreach (['sample-video.mp4', '1772653609-sample-video.mp4'] as $fmKnownVideo) {
        if (\Illuminate\Support\Facades\File::exists(public_path('uploads/banner-video/' . $fmKnownVideo))) {
            $fmFallbackVideo = asset('uploads/banner-video/' . $fmKnownVideo);
            break;
        }
    }
    if ($fmFallbackVideo === null
        && \Illuminate\Support\Facades\File::isDirectory(public_path('uploads/banner-video'))) {
        $fmFound = \Illuminate\Support\Facades\File::allFiles(public_path('uploads/banner-video'));
        foreach ($fmFound as $fmFile) {
            if (strtolower($fmFile->getExtension()) === 'mp4') {
                $fmFallbackVideo = asset('uploads/banner-video/' . $fmFile->getFilename());
                break;
            }
        }
    }
    $fmFallbackImage = asset('assets/images/frontend/banner/6718b8ecbd6a11729673452.png');

    $fmSlides = collect($banners ?? [])->map(static function ($banner) use ($fmFallbackVideo, $fmFallbackImage) {
        $mediaType = (string) ($banner->media_type ?? 'image');

        // -- video URL (same rules as the classic slider) ------------------
        $videoRaw = (string) ($banner->video ?? '');
        $videoIsUrl = preg_match('~^https?://~i', $videoRaw) === 1;
        $videoPath = $videoIsUrl ? $videoRaw : ltrim(preg_replace('~^public/~', '', $videoRaw), '/');
        $hasVideo = $videoRaw !== ''
            && ($videoIsUrl || \Illuminate\Support\Facades\File::exists(public_path($videoPath)));
        $videoUrl = $hasVideo ? ($videoIsUrl ? $videoRaw : asset($videoPath)) : null;

        // -- image URL (same rules as the classic slider) ------------------
        $imageRaw = (string) ($banner->image ?? '');
        $imageIsUrl = preg_match('~^https?://~i', $imageRaw) === 1;
        $imagePath = $imageIsUrl ? $imageRaw : ltrim(preg_replace('~^public/~', '', $imageRaw), '/');
        $hasImage = $imagePath !== '';
        $imageUrl = $hasImage ? ($imageIsUrl ? $imageRaw : asset($imagePath)) : null;

        // -- which medium paints the background ----------------------------
        // 1. A video banner plays its video even when a thumbnail exists.
        // 2. Otherwise the image wins.
        // 3. A banner with neither falls back to the shop's default video -
        //    "no picture -> the shiny video starts by itself" - and only
        //    then to the classic default image.
        if ($mediaType === 'video' && $hasVideo) {
            $kind = 'video';
            $src = $videoUrl;
        } elseif ($hasImage) {
            $kind = 'image';
            $src = $imageUrl;
        } elseif ($hasVideo) {
            $kind = 'video';
            $src = $videoUrl;
        } elseif ($fmFallbackVideo !== null) {
            $kind = 'video';
            $src = $fmFallbackVideo;
        } else {
            $kind = 'image';
            $src = $fmFallbackImage;
        }

        return (object) [
            'kind'  => $kind,
            'src'   => $src,
            'link'  => $banner->link ?: route('products.page'),
            'title' => filled($banner->title ?? null) ? (string) $banner->title : null,
        ];
    })->filter(static fn ($slide) => filled($slide->src))->values();

    $fmShopName = (string) ($generalsetting->name ?? config('app.name'));
@endphp

@if($fmSlides->isNotEmpty())
    <section class="fm-hero-wrapper" data-purpose="hero-slider-section">
        <div class="fm-hero-banner">

            <div class="fm-hero-slides" data-fm-hero>
                @foreach($fmSlides as $fmSlideIndex => $fmSlide)
                    <div class="fm-hero-slide {{ $fmSlideIndex === 0 ? 'is-active' : '' }}"
                         data-fm-hero-slide
                         data-href="{{ $fmSlide->link }}"
                         aria-hidden="{{ $fmSlideIndex === 0 ? 'false' : 'true' }}">

                        {{-- The whole media is the click target, same as the
                             classic banner slider. --}}
                        <a class="fm-hero-hit" href="{{ $fmSlide->link }}" tabindex="-1"
                           aria-hidden="{{ $fmSlideIndex === 0 ? 'false' : 'true' }}">
                            @if($fmSlide->kind === 'video')
                                <video class="fm-hero-media" autoplay muted loop playsinline
                                       preload="{{ $fmSlideIndex === 0 ? 'auto' : 'metadata' }}"
                                       aria-hidden="true">
                                    <source src="{{ $fmSlide->src }}" type="video/mp4">
                                </video>
                            @else
                                <img class="fm-hero-media"
                                     src="{{ $fmSlide->src }}"
                                     alt="{{ $fmSlide->title ?: $fmShopName }}"
                                     width="1920" height="560"
                                     fetchpriority="{{ $fmSlideIndex === 0 ? 'high' : 'low' }}"
                                     decoding="async">
                            @endif
                        </a>

                        {{-- Dark scrim so white text stays readable over any
                             banner photo or video frame. --}}
                        <span class="fm-hero-scrim" aria-hidden="true"></span>

                        <div class="fm-hero-overlay">
                            <span class="fm-hero-pill-badge">
                                <svg fill="none" height="16" stroke="currentColor" stroke-linecap="round"
                                     stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24" width="16"
                                     aria-hidden="true">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                                Fresh Picks of the Day
                            </span>

                            <div class="fm-hero-text-block">
                                <h1>{{ $fmSlide->title ?: $fmShopName }}</h1>
                                <span class="fm-hero-subtext">{{ $fmShopName }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Dots and arrows only earn their space with 2+ slides; they
                 float over the media in the bottom-right corner. --}}
            @if($fmSlides->count() > 1)
                <div class="fm-hero-bottom-controls">
                    <div class="fm-slider-dots" data-fm-hero-dots role="tablist" aria-label="Banner slides">
                        @foreach($fmSlides as $fmDotIndex => $fmDot)
                            <button type="button"
                                    class="fm-slider-dot {{ $fmDotIndex === 0 ? 'fm-active' : '' }}"
                                    data-fm-hero-dot="{{ $fmDotIndex }}"
                                    role="tab"
                                    aria-selected="{{ $fmDotIndex === 0 ? 'true' : 'false' }}"
                                    aria-label="Slide {{ $fmDotIndex + 1 }}"></button>
                        @endforeach
                    </div>

                    <div class="fm-slider-arrows">
                        <button type="button" class="fm-slider-btn" data-fm-hero-prev aria-label="Previous slide">
                            <svg fill="none" height="18" stroke="currentColor" stroke-width="2.5"
                                 viewBox="0 0 24 24" width="18" aria-hidden="true">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </button>
                        <button type="button" class="fm-slider-btn" data-fm-hero-next aria-label="Next slide">
                            <svg fill="none" height="18" stroke="currentColor" stroke-width="2.5"
                                 viewBox="0 0 24 24" width="18" aria-hidden="true">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </section>
@endif
