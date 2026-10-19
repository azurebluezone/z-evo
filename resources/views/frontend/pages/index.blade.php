@extends('frontend.layouts.master')
@section('title', 'Home')

@push('css')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Section title - industry standard (Maknoon-style) */
    .section-title {
        align-items: center;
        display: flex;
        flex-flow: row wrap;
        justify-content: space-between;
        position: relative;
        width: 100%;
        margin: 0 0 1.5rem 0;
    }
    .section-title i { margin-right: .5em; opacity: .6; }
    .section-title i.icon-angle-right { margin-left: 10px; margin-right: 0; }
    .section-title span { text-transform: uppercase; }
    .section-title small { font-size: 1em; font-weight: 400; opacity: .7; padding-left: 15px; text-transform: none; }
    .section-title b {
        background-color: currentColor;
        display: block;
        flex: 1;
        height: 2px;
        opacity: .1;
    }
    .section-title a { display: block; font-size: .8em; margin-left: auto; padding-left: 15px; }
    .section-title-center span { margin: 0 15px; }
    .section-title-bold-center span,
    .section-title-center span { text-align: center; }
    .section-title-bold-center small,
    .section-title-center small { display: block; padding: 0 15px; }
    .section-title-bold span,
    .section-title-bold-center span {
        border: 2px solid rgba(0,0,0,.1);
        padding: .3em .8em;
    }
    .section-title-bold b:first-of-type { display: none; }
    .section-title-main { font-size: 140% !important; }

    :root {
        --zombie-primary: #1ca3ac;
        --zombie-secondary: #f39c12;
        --zombie-light: #ffffff;
        --zombie-dark: #333333;
        --zombie-success: #27ae60;
    }

    /* Button Styles */
    .buy-now-btn {
        background-color: var(--zombie-secondary);
        color: white;
        border: none;
        padding: 10px 15px;
        width: 100%;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .buy-now-btn:hover {
        background-color: #e67e22;
    }

    .add_cart_btn_home {
        background-color: var(--zombie-primary);
        color: white;
        border: none;
        padding: 10px 15px;
        width: 100%;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .add_cart_btn_home:hover {
        background-color: #178f97;
    }

    /* Sidebar Styles */
    .filter_sidebar {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .accordion-button {
        background-color: var(--zombie-primary) !important;
        color: white !important;
        border: none;
        border-radius: 4px !important;
        font-family: 'Anek Bangla', sans-serif;
    }

    .accordion-button:not(.collapsed) {
        background-color: var(--zombie-primary) !important;
        color: white !important;
    }

    .accordion-button::after {
        filter: brightness(0) invert(1);
    }

    .cust_according_body {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 0 0 4px 4px;
    }

    #price-range .ui-slider-range {
        background: var(--zombie-primary);
    }

    #price-range .ui-slider-handle {
        border-color: var(--zombie-primary);
    }

    /* Zombie Product Card Styles */
    .zombie-product-card {
        border: none;
        overflow: hidden;
        transition: opacity 0.3s, transform 0.3s, background-color 0.3s;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: 100%;
        position: relative;
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
    }

    .zombie-product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .zombie-product-img-container {
        height: 300px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        position: relative;
    }

    .zombie-product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .zombie-product-img-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        background-color: #f8f9fa;
        color: #999;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        flex-direction: column;
    }

    .zombie-product-title {
        font-family: 'Poppins', 'Anek Bangla', sans-serif;
        font-size: 13px;
        font-weight: 500;
        color: #000;
        margin-bottom: 0.25em;
        margin-top: 0.35em;
        text-align: center;
        box-sizing: border-box;
        line-height: 1.35;
    }

    .zombie-product-price {
        color: #000;
        font-weight: 500;
        font-size: 13px;
        margin: 0.4rem 0;
        text-align: center;
    }

    .zombie-original-price {
        text-decoration: line-through;
        color: #ff0000;
        margin-right: 10px;
    }

    .zombie-discounted-price {
        color: #000;
        font-weight: 700;
    }

    .zombie-btn-group {
        display: flex;
        margin-top: 0.6rem;
        gap: 0;
        justify-content: center;
    }

    .zombie-btn-buy,
    .zombie-btn-cart {
        flex: 1;
        padding: 8px 6px;
        border: none;
        font-family: 'Poppins', 'Anek Bangla', sans-serif;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        white-space: nowrap;
    }

    .zombie-btn-buy {
        background-color: #f39c12;
        color: white;
    }

    .zombie-btn-buy:hover {
        background-color: #e67e22;
        color: white;
    }

    .zombie-btn-cart {
        background-color: #000;
        color: white;
    }

    .zombie-btn-cart:hover {
        background-color: #333;
        color: white;
    }

    .zombie-product-link {
        text-decoration: none;
        color: inherit;
        display: block;
        flex: 1;
    }
    
    .zombie-product-link:hover {
        text-decoration: none;
        color: inherit;
    }

    /* Row spacing between product grids */
    .col-6.col-md-4.col-lg-3 {
        margin-top: 13px;
    }

    .zombie-box-text {
        transition: opacity 0.3s, transform 0.3s, background-color 0.3s;
        font-size: 0.9em;
        padding-top: 0.7em;
        position: relative;
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }

    /* Responsive adjustments - Mobile & Tablet */
    @media (max-width: 768px) {
        .zombie-btn-group {
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            justify-content: center;
            align-items: stretch;
            margin-top: 0.5rem;
        }
        .zombie-btn-group form {
            flex: 1;
            min-width: 0;
            display: flex;
        }
        .zombie-btn-group .zombie-btn-buy,
        .zombie-btn-group .zombie-btn-cart {
            flex: 1;
            min-width: 0;
            font-size: 11px;
            padding: 6px 4px;
            min-height: 36px;
        }
        .zombie-product-card {
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .zombie-box-text {
            text-align: center;
            padding-top: 0.4em;
        }
        .zombie-product-title { font-size: 12px; margin-top: 0.2em; margin-bottom: 0.15em; }
        .zombie-product-price { margin: 0.25rem 0; font-size: 12px; }
    }

    @media (max-width: 549px) {
        .zombie-box-text {
            font-size: 85%;
            padding-left: 8px;
            padding-right: 8px;
            padding-top: 0.35em;
        }

        .zombie-product-img-container {
            height: 220px;
        }

        .zombie-btn-buy,
        .zombie-btn-cart {
            font-size: 10px;
            padding: 5px 3px;
            min-height: 32px;
        }

        .zombie-product-card {
            margin-bottom: 1rem;
        }
        .zombie-product-title { font-size: 11px; }
        .zombie-product-price { font-size: 11px; }
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<!-- Hero Section -->
<section class="sv-hero">
    <div class="sv-hero-content">
        @if(isset($sliders) && $sliders->count() > 0)
            @foreach($sliders as $key => $slider)
                @php
                    $mediaType = $slider->media_type ?? 'image';

                    $bannerImagePath = preg_replace('~^public/~', '', (string) ($slider->image ?? ''));
                    $bannerImagePath = ltrim($bannerImagePath, '/');

                    $videoRaw = (string) ($slider->video ?? '');
                    $videoIsUrl = preg_match('~^https?://~i', $videoRaw) === 1;
                    $videoPath = $videoIsUrl ? $videoRaw : ltrim(preg_replace('~^public/~', '', $videoRaw), '/');
                    $hasVideo = !empty($videoRaw) && ($videoIsUrl || \Illuminate\Support\Facades\File::exists(public_path($videoPath)));
                @endphp
                <div class="sv-hero-slide {{ $key === 0 ? 'active' : '' }}"
                     style="{{ ($mediaType === 'video' && $hasVideo) ? '' : "background-image: url('" . asset($bannerImagePath) . "');" }}">
                    @if($mediaType === 'video' && $hasVideo)
                        <video autoplay muted loop playsinline preload="metadata" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
                            <source src="{{ $videoIsUrl ? $videoRaw : asset($videoPath) }}" type="video/mp4">
                        </video>
                    @endif
                    <a href="{{ $slider->link ?? '#' }}" class="sv-hero-slide-link" aria-label="{{ $slider->title ?? 'View' }}"></a>
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="sv-hero-text-content">
                                    <div class="sv-hero-brand">{{ $slider->brand ?? 'Sunnah Heaven' }}</div>
                                    <!--<h1 class="sv-hero-title">{{ $slider->title }}</h1>-->
                                    <!--<p class="sv-hero-description">{{ $slider->description ?? 'Premium Islamic Fashion Collection' }}</p>-->
                                    <a href="{{ $slider->link ?? '#' }}" class="sv-hero-cta">SHOP NOW</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Fallback Hero -->
            <div class="sv-hero-slide active" style="background-image: url('{{ asset('uploads/banner/1728722342Slider-new-1.jpg') }}');">
                <a href="#products" class="sv-hero-slide-link" aria-label="View products"></a>
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="sv-hero-text-content">
                                <div class="sv-hero-brand">Sunnah Heaven</div>
                                <h1 class="sv-hero-title">Premium Islamic Fashion</h1>
                                <p class="sv-hero-description">Discover our exclusive collection of modest and elegant Islamic clothing</p>
                                <a href="#products" class="sv-hero-cta">SHOP NOW</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
<!-- Top Categories Section -->
@if(isset($generalsetting) && $generalsetting->home_page_top_categories_section == 1)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="col-12">
                    <h3 class="section-title section-title-bold-center"><b></b><span class="section-title-main" style="font-size:140%;">TOP CATEGORIES</span><b></b></h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="product_sliders">
                    @foreach ($homeproducts as $homecat)
                        @if(isset($homecat->home_page) && $homecat->home_page == 1)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="sv-boss-product-card">
                                <div class="sv-boss-product-image-container">
                                    <img src="{{ asset(preg_replace('~^public/~', '', (string) ($homecat->image ?? 'assets/images/default.png'))) }}" 
                                         alt="{{ $homecat->name }}" 
                                         class="sv-boss-product-image primary">
                                    <img src="{{ asset(preg_replace('~^public/~', '', (string) ($homecat->image ?? 'assets/images/default.png'))) }}" 
                                         alt="{{ $homecat->name }} Alternate" 
                                         class="sv-boss-product-image secondary">
                                    
                                    <div class="sv-boss-product-actions">
                                        <a href="{{ route('category', $homecat->slug) }}" class="sv-boss-action-btn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" class="sv-boss-action-btn">
                                            <i class="far fa-heart"></i>
                                </a>
                            </div>
                                </div>
                                <div class="sv-boss-product-info">
                                    <h3 class="sv-boss-product-title">
                                        <a href="{{ route('category', $homecat->slug) }}" style="color: inherit; text-decoration: none;">
                                            {{ $homecat->name }}
                                        </a>
                                    </h3>
                                    <div class="sv-boss-product-price">
                                        <span>{{ $homecat->products->count() }} Products</span>
                                    </div>
                                    <a href="{{ route('category', $homecat->slug) }}" class="btn sv-boss-add-to-cart">
                                        <i class="fas fa-eye me-2"></i>VIEW CATEGORY
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- Hot Deal Section -->
@if(isset($generalsetting) && $generalsetting->home_page_hot_deal_section == 1)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="col-12">
                    <h3 class="section-title section-title-bold-center"><b></b><span class="section-title-main" style="font-size:140%;">Hot Deal</span><b></b></h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="row">
                    @foreach ($hotdeal_top as $key => $value)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="{{ route('product', $value->slug) }}" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    @if($value->image && $value->image->image)
                                    @php $hotdealImgPath = preg_replace('#^public/#', '', $value->image->image); @endphp
                                    <img src="{{ asset($hotdealImgPath) }}" alt="{{ $value->name }}" class="zombie-product-img" loading="lazy">
                                    @else
                                    <div class="zombie-product-img-placeholder">
                                        {{ Str::limit($value->name, 30) }}
                                    </div>
                                    @endif
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">{{ Str::limit($value->name, 80) }}</p>
                                    <div class="zombie-product-price">
                                        @if ($value->old_price)
                                        <span class="zombie-original-price">৳ {{ $value->old_price }}</span>
                                        @endif
                                        <span class="zombie-discounted-price">৳ {{ $value->new_price }}</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                                <a href="{{ route('product', $value->slug) }}" class="zombie-btn-buy">BUY NOW</a>
                                <a href="{{ route('product', $value->slug) }}" class="zombie-btn-cart">ADD TO CART</a>
                                @else
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $value->id }}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $value->id }}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- Individual Category Sections -->
@foreach ($homeproducts as $homecat)
    @if(isset($homecat->home_page) && $homecat->home_page == 1)
    <section class="homeproduct">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="col-12">
                        <h3 class="section-title section-title-bold-center"><b></b><span class="section-title-main" style="font-size:140%;">{{ $homecat->name }}</span><b></b></h3>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="row">
                        @foreach ($homecat->products as $key => $value)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="zombie-product-card">
                                <a href="{{ route('product', $value->slug) }}" class="zombie-product-link">
                                    <div class="zombie-product-img-container">
                                        @if($value->image && $value->image->image)
                                        @php $catProdImgPath = preg_replace('#^public/#', '', $value->image->image); @endphp
                                        <img src="{{ asset($catProdImgPath) }}" alt="{{ $value->name }}" class="zombie-product-img" loading="lazy">
                                        @else
                                        <div class="zombie-product-img-placeholder">
                                            {{ Str::limit($value->name, 30) }}
                                        </div>
                                        @endif
                                    </div>
                                    <div class="zombie-box-text">
                                        <p class="zombie-product-title">{{ Str::limit($value->name, 80) }}</p>
                                        <div class="zombie-product-price">
                                            @if ($value->old_price)
                                            <span class="zombie-original-price">৳ {{ $value->old_price }}</span>
                                            @endif
                                            <span class="zombie-discounted-price">৳ {{ $value->new_price }}</span>
                                        </div>
                                    </div>
                                </a>
                                <div class="zombie-btn-group">
                                    @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                                    <a href="{{ route('product', $value->slug) }}" class="zombie-btn-buy">BUY NOW</a>
                                    <a href="{{ route('product', $value->slug) }}" class="zombie-btn-cart">ADD TO CART</a>
                                    @else
                                    <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                        <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                    </form>

                                    <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                        <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        </div>
                </div>
            </div>
        </div>
    </section>
    @endif
@endforeach

<!--brand section-->
@if(isset($generalsetting) && $generalsetting->home_page_brands_section == 1)
<div class="homeproduct">
    <div class="container mt-2">
        <div class="col-12">
            <h3 class="section-title section-title-bold-center"><b></b><span class="section-title-main" style="font-size:140%;">Hot Deal Brand</span><b></b></h3>
        </div>
        <div class="row">
        @foreach($brands as $brand)
            <div class="col-6 col-md-4 col-lg-2 mb-2">
                <div class="card brand-card d-flex align-items-center justify-content-center">
                    <img src="{{ asset(preg_replace('~^public/~', '', (string) ($brand->image ?? 'assets/images/default.png'))) }}" class="card-img-top brand-image" alt="{{ $brand->name }}">
                </div>
            </div>
        @endforeach
    </div>
    </div>
</div>
@endif

<!-- Zombie Product Section -->
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="col-12">
                    <h3 class="section-title section-title-bold-center"><b></b><span class="section-title-main" style="font-size:140%;">Recommended Products</span><b></b></h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="row">
                    <!-- Example zombie product cards - you can loop through actual products here -->
                    @if(isset($zombie_products) && $zombie_products->count() > 0)
                        @foreach($zombie_products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="{{ route('product', $product->slug) }}" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    @if($product->image && $product->image->image)
                                    @php $featImgPath = preg_replace('#^public/#', '', $product->image->image); @endphp
                                    <img src="{{ asset($featImgPath) }}" alt="{{ $product->name }}" class="zombie-product-img" loading="lazy">
                                    @else
                                    <img src="{{ asset('uploads/default/placeholder.png') }}" alt="{{ $product->name }}" class="zombie-product-img" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';">
                                    @endif
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">{{ Str::limit($product->name, 80) }}</p>
                                    <div class="zombie-product-price">
                                        @if ($product->old_price)
                                        <span class="zombie-original-price">৳ {{ $product->old_price }}</span>
                                        @endif
                                        <span class="zombie-discounted-price">৳ {{ $product->new_price }}</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                @if (!$product->prosizes->isEmpty() || !$product->procolors->isEmpty())
                                <a href="{{ route('product', $product->slug) }}" class="zombie-btn-buy">BUY NOW</a>
                                <a href="{{ route('product', $product->slug) }}" class="zombie-btn-cart">ADD TO CART</a>
                                @else
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $product->id }}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $product->id }}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                        @endforeach
                    @else
                    <!-- Sample products for demonstration -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="#" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    <img src="{{ asset('uploads/default/placeholder.png') }}" alt="Sample Product" class="zombie-product-img" onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';">
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">Premium Floor Touch Zilbab</p>
                                    <div class="zombie-product-price">
                                        <span class="zombie-original-price">৳ 2500</span>
                                        <span class="zombie-discounted-price">৳ 1190</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="1" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="1" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="#" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    <img src="{{ asset('uploads/default/placeholder.png') }}" alt="Sample Product 2" class="zombie-product-img" onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';">
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">Premium Islamic Abaya</p>
                                    <div class="zombie-product-price">
                                        <span class="zombie-original-price">৳ 3200</span>
                                        <span class="zombie-discounted-price">৳ 1890</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="2" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="2" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="#" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    <img src="{{ asset('uploads/product/placeholder.jpg') }}" alt="Sample Product 3" class="zombie-product-img">
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">Elegant Hijab Set</p>
                                    <div class="zombie-product-price">
                                        <span class="zombie-original-price">৳ 1500</span>
                                        <span class="zombie-discounted-price">৳ 890</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="3" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="3" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="#" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    <img src="{{ asset('uploads/product/placeholder.jpg') }}" alt="Sample Product 4" class="zombie-product-img">
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">Premium Modest Dress</p>
                                    <div class="zombie-product-price">
                                        <span class="zombie-original-price">৳ 2800</span>
                                        <span class="zombie-discounted-price">৳ 1490</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="4" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="4" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('script')
<script>
    // Add to cart functionality for boss style buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        document.querySelectorAll('.sv-boss-add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                
                // Animation effect
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-2"></i>ADDED TO CART';
                this.style.backgroundColor = '#4caf50';
                
                // Update cart count if exists
                const cartCount = document.querySelector('.cart-count, .sv-cart-count, .modern-cart-count');
                if (cartCount) {
                    cartCount.textContent = parseInt(cartCount.textContent) + 1;
                }
                
                // Reset button after delay
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.backgroundColor = '';
                }, 2000);
                
                // Add to cart logic here
                console.log('Adding product to cart:', productId);
            });
        });

        // Buy now functionality
        document.querySelectorAll('.sv-boss-buy-now').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                
                // Animation effect
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-2"></i>PROCEEDING TO CHECKOUT';
                this.style.backgroundColor = '#2e5e3f';
                
                // Update cart count if exists
                const cartCount = document.querySelector('.cart-count, .sv-cart-count, .modern-cart-count');
                if (cartCount) {
                    cartCount.textContent = parseInt(cartCount.textContent) + 1;
                }
                
                // Redirect after delay
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.backgroundColor = '';
                    // Redirect to checkout
                    window.location.href = '/checkout';
                }, 2000);
    });
});

        // Action buttons functionality
        document.querySelectorAll('.sv-boss-action-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const icon = this.querySelector('i');
                
                if (icon && icon.classList.contains('far')) {
                    // Wishlist functionality
                    if (icon.classList.contains('fa-heart')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.style.color = '#e74c3c';
                        
                        setTimeout(() => {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            this.style.color = '';
                        }, 2000);
                    }
                } else if (icon && icon.classList.contains('fa-shopping-cart')) {
                    // Quick add to cart
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.style.backgroundColor = '#4caf50';
                    this.style.color = 'white';
                    
                    const cartCount = document.querySelector('.cart-count, .sv-cart-count, .modern-cart-count');
                    if (cartCount) {
                        cartCount.textContent = parseInt(cartCount.textContent) + 1;
                    }
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.backgroundColor = '';
                        this.style.color = '';
                    }, 2000);
                }
            });
        });
        
        // Zombie product add to cart functionality
        window.add_to_cart = function(button, event) {
            event.preventDefault();
            var form = $(button).closest('form');
            var url = form.attr('action');
            var submit_button = $(button);

            submit_button.prop('disabled', true);

            $.ajax({
                url: url,
                method: form.attr('method'),
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success == true) {
                        toastr.success(response.message);
                        var cart_count = response.cart_count;
                        
                        // Update all cart count elements
                        $('#cart_count').text(cart_count);
                        $('.cart_count').text(cart_count);
                        $('.cart-count').text(cart_count);
                        $('.sv-cart-count').text(cart_count);
                        $('.modern-cart-count').text(cart_count);
                        $('.mobilecart-qty').text(cart_count);
                        $('.zombie-cart-count').text(cart_count);

                        // Show notification
                        $('.zombie-notification-text').text("Product added to cart!");
                        $('.zombie-cart-notification').fadeIn();

                        // Hide notification after 3 seconds
                        setTimeout(function() {
                            $('.zombie-cart-notification').fadeOut();
                        }, 3000);

                        // Button animation
                        submit_button.html('ADDED');
                        submit_button.css('background-color', '#27ae60');

                        // Reset button after 2 seconds
                        setTimeout(function() {
                            submit_button.html('ADD TO CART');
                            submit_button.css('background-color', '#000000');
                        }, 2000);
                    }
                    if (response.success == false) {
                        toastr.error(response.message);
                    }
                },
                error: function(response) {
                    toastr.error('Something went wrong!');
                },
                complete: function() {
                    submit_button.prop('disabled', false);
                }
            });
        };
    });
</script>
@endpush
