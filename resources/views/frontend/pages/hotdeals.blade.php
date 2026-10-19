@extends('frontend.layouts.master')
@section('title','Hot Deals')
@push('css')
<link rel="stylesheet" href="{{asset('frontEnd/css/jquery-ui.css')}}" />
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.carousel.min.css') }}" />
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.theme.default.min.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

<style>
        :root {
            --zombie-primary: #555555;
            --zombie-secondary: #000000;
            --zombie-light: #ffffff;
            --zombie-dark: #333333;
            --zombie-success: #27ae60;
        }
        
        body {
            font-family: 'Anek Bangla', sans-serif;
            background-color: #ffffff;
            color: var(--zombie-primary);
            margin: 0;
            padding: 0;
        }
        
        .container {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }
        
        @media (min-width: 576px) {
            .container {
                max-width: 540px;
            }
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 720px;
            }
        }
        
        @media (min-width: 992px) {
            .container {
                max-width: 960px;
            }
        }
        
        @media (min-width: 1200px) {
            .container {
                max-width: 1140px;
            }
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-6, .col-lg-3, .col-sm-12, .col-md-4, .col-lg-2 {
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
        
        .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        
        @media (min-width: 768px) {
            .col-md-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }
        
        @media (min-width: 992px) {
            .col-lg-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }
            
            .col-lg-2 {
                flex: 0 0 16.666667%;
                max-width: 16.666667%;
            }
        }
        
        /* Row spacing between product grids */
        .col-6.col-lg-3 {
            margin-top: 13px;
        }
        
        .mb-2, .mb-4 {
            margin-bottom: 0.5rem;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .mt-5 {
            margin-top: 3rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .zombie-header {
            background-color: #f5f5f5;
            color: var(--zombie-primary);
            padding: 2rem 0;
            margin-bottom: -10rem;
            text-align: center;
        }
        
        .zombie-header h1 {
            font-family: 'Lato', sans-serif;
            font-weight: 700;
            font-size: 1.25em;
            color: rgb(85, 85, 85);
            margin-bottom: 0.5em;
            margin-top: 0px;
        }
        
        .zombie-product-card {
            border: none;
            overflow: hidden;
            transition: opacity 0.3s, transform 0.3s, background-color 0.3s;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: 100%;
            position: relative;
            margin-bottom: 0; /* no gap between rows */
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
            cursor: pointer;
        }
        
        .zombie-product-img-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(244, 196, 48, 0.1), rgba(44, 44, 44, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }
        
        .zombie-product-img-container:hover::before {
            opacity: 1;
        }

        .zombie-product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 0;
        }

        .zombie-product-card:hover .zombie-product-img {
            transform: scale(1.05);
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
        }

        .zombie-product-title {
            font-family: 'Anek Bangla', sans-serif;
            font-size: 14px;
            font-weight: 400;
            color: var(--zombie-primary);
            margin-bottom: 0.2em;
            margin-top: 0.2em;
            text-align: center;
            box-sizing: border-box;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 40px;
            cursor: pointer;
        }

        .zombie-product-price {
            color: var(--zombie-primary);
            font-weight: 400;
            font-size: 14px;
            margin: 0.15rem 0;
            text-align: center;
        }

        .zombie-original-price {
            text-decoration: line-through;
            color: #999;
            margin-right: 10px;
        }

        .zombie-discounted-price {
            color: var(--zombie-primary);
            font-weight: 600;
        }

        .zombie-btn-group {
            display: flex;
            margin-top: 0.35rem;
            gap: 0;
            justify-content: center;
            margin-bottom: 0;
            padding-bottom: 0;
            border-top: 1px solid #eeeeee; /* visual separation between title/price and buttons */
            padding-top: 4px;
        }
        
        /* Section Spacing */
        section {
            margin-bottom: 40px;
        }
        
        section:last-child {
            margin-bottom: 0;
        }

        .zombie-btn-buy,
        .zombie-btn-cart {
            flex: 1;
            padding: 12px 8px;
            border: none;
            font-family: 'Anek Bangla', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .zombie-btn-buy::before,
        .zombie-btn-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .zombie-btn-buy:hover::before,
        .zombie-btn-cart:hover::before {
            left: 100%;
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
            background-color: var(--zombie-secondary);
            color: white;
        }

        .zombie-btn-cart:hover {
            background-color: #333;
            color: white;
        }
        

        .zombie-box-text {
            transition: opacity 0.3s, transform 0.3s, background-color 0.3s;
            font-size: 0.9em;
            padding-top: 0.4em;
            position: relative;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
            padding: 8px 10px;
        }

        .zombie-cart-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--zombie-success);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1000;
            animation: zombie-fadeIn 0.5s ease;
        }

        @keyframes zombie-fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .zombie-cart-count {
            background-color: var(--zombie-secondary);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            position: absolute;
            top: -8px;
            right: -8px;
        }

        .zombie-cart-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: white;
            color: var(--zombie-primary);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 999;
            cursor: pointer;
        }

        .zombiecoder-section-title {
            background-color: #2c2c2c;
            color: #f4c430;
            padding: 15px 40px;
            font-size: 1.8rem;
            font-weight: bold;
            letter-spacing: 2px;
            position: relative;
            display: inline-block;
            margin-bottom: 50px;
            clip-path: polygon(0 0, calc(100% - 30px) 0, 100% 100%, 30px 100%);
        }
        
        .zombiecoder-section-title::before,
        .zombiecoder-section-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 80px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #f4c430, transparent);
        }
        
        .zombiecoder-section-title::before {
            left: -100px;
            transform: translateY(-50%);
        }
        
        .zombiecoder-section-title::after {
            right: -100px;
            transform: translateY(-50%);
        }
        
        .view_more_btn {
            display: inline-block;
            margin: 20px 0;
            padding: 12px 30px;
            background-color: #f4c430;
            color: #2c2c2c;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .view_more_btn:hover {
            background-color: #2c2c2c;
            color: #f4c430;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Product Link Styling */
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
        
        .brand-card {
            height: 120px;
            padding: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .brand-card:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .brand-image {
            max-height: 90px;
            object-fit: contain;
            width: 100%;
        }
        
        .section-title-header {
            text-align: center;
            margin: 30px 0;
        }
        
        .section-title-name {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title-name:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: #f4c430;
        }
        
        .slider-section {
            margin-bottom: 30px;
            position: relative;
            background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .home-slider-container {
            overflow: hidden;
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .slider-item img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            object-position: center;
        }
        
        .slider-section .container {
            position: relative;
            z-index: 2;
        }
        
        /* Owl Carousel Navigation */
        .slider-section .owl-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            pointer-events: none;
            z-index: 3;
        }
        
        .slider-section .owl-nav button {
            background: rgba(244, 196, 48, 0.8) !important;
            color: #2c2c2c !important;
            border: none !important;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 18px;
            pointer-events: all;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        
        .slider-section .owl-nav button:hover {
            background: rgba(244, 196, 48, 1) !important;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .slider-section .owl-nav .owl-prev {
            margin-left: 20px;
        }
        
        .slider-section .owl-nav .owl-next {
            margin-right: 20px;
        }
        
        /* Owl Carousel Dots */
        .slider-section .owl-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
        }
        
        .slider-section .owl-dots .owl-dot {
            display: inline-block;
            margin: 0 5px;
        }
        
        .slider-section .owl-dots .owl-dot span {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            display: block;
            transition: all 0.3s ease;
        }
        
        .slider-section .owl-dots .owl-dot.active span {
            background: #f4c430;
            transform: scale(1.2);
        }
        
        .homeproduct {
            margin: 30px 0;
        }
        
        .sec_title {
            margin-bottom: 20px;
        }
        
        .topcategory {
            display: flex;
            overflow-x: auto;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .cat_item {
            flex: 0 0 auto;
            margin-right: 15px;
            text-align: center;
            width: 120px;
        }
        
        .cat_img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 10px;
        }
        
        .cat_img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cat_name a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991px) {
            .zombie-product-img-container {
                height: 250px;
            }
            
            .zombie-btn-buy,
            .zombie-btn-cart {
                font-size: 12px;
                padding: 10px 5px;
            }
            
            .zombiecoder-section-title {
                font-size: 1.5rem;
                padding: 12px 30px;
                margin-bottom: 40px;
            }
            
            .zombiecoder-section-title::before,
            .zombiecoder-section-title::after {
                width: 50px;
            }
            
            .zombiecoder-section-title::before {
                left: -70px;
            }
            
            .zombiecoder-section-title::after {
                right: -70px;
            }
        }
        
        @media (max-width: 767px) {
            .zombie-product-img-container {
                height: 220px;
            }
            
            .zombie-product-title {
                font-size: 13px;
                height: 36px;
            }
            
            .zombie-btn-buy,
            .zombie-btn-cart {
                font-size: 11px;
                padding: 8px 3px;
            }
            .zombie-product-card { margin-bottom: 2px; }
            
            .zombiecoder-section-title {
                font-size: 1.3rem;
                padding: 10px 25px;
                margin-bottom: 30px;
            }
            
            .section-title-name {
                font-size: 1.7rem;
            }
        }
        
        @media (max-width: 575px) {
            .zombie-product-img-container {
                height: 200px;
            }
            
            .zombie-product-title {
                font-size: 12px;
                height: 32px;
            }
            
            .zombie-btn-buy,
            .zombie-btn-cart {
                font-size: 10px;
                padding: 8px 2px;
            }
            .zombie-product-card { margin-bottom: 2px; }
            
            .zombie-box-text {
                padding: 10px;
            }
            
            .zombiecoder-section-title {
                font-size: 1.1rem;
                padding: 8px 20px;
                margin-bottom: 25px;
            }
            
            .section-title-name {
                font-size: 1.5rem;
            }
            
            .zombie-cart-icon {
                width: 40px;
                height: 40px;
                top: 15px;
                right: 15px;
            }
            
            .zombie-cart-count {
                width: 20px;
                height: 20px;
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 400px) {
            .zombie-product-img-container {
                height: 180px;
            }
            
            .zombie-product-title {
                font-size: 11px;
            }
            
            .zombie-btn-buy,
            .zombie-btn-cart {
                font-size: 9px;
                padding: 6px 1px;
            }
            .zombie-product-card { margin-bottom: 2px; }
            
            .zombie-product-price {
                font-size: 12px;
            }
        }
    </style>

@endpush
@section('content')
<!-- Slider Section -->
<section class="slider-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="home-slider-container">
                    <div class="main_slider owl-carousel">
                        @if(isset($sliders) && count($sliders) > 0)
                        @foreach ($sliders as $key => $value)
                        <div class="slider-item">
                            <img src="{{ asset($value->image) }}" alt="" class="w-100" />
                        </div>
                        @endforeach
                        @else
                        <div class="slider-item">
                            <img src="{{ asset('uploads/default/hotdeals-banner.jpg') }}" alt="Hot Deals" class="w-100" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                            <div style="height: 400px; background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%); display: none; align-items: center; justify-content: center; color: #f4c430; font-size: 2rem; font-weight: bold;">
                                Hot Deals
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shopping Cart Icon -->
<div class="zombie-cart-icon">
    <i class="fas fa-shopping-cart"></i>
    <span class="zombie-cart-count">0</span>
</div>

<!-- Cart Notification -->
<div class="zombie-cart-notification">
    <i class="fas fa-check-circle me-2"></i> <span class="zombie-notification-text">Product added to cart!</span>
</div>

<section class="container">
    <div class="text-center">
        <h2 class="zombiecoder-section-title">Hot Deals</h2>
    </div>
</section>


<section class="product-section">
    <div class="container">
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">Home</a>
                        <span>/</span>
                        <strong>Hot Deals</strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="showing-data">
                                <span>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} Results</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mobile-filter-toggle">
                                <i class="fa fa-list-ul"></i><span>filter</span>
                            </div>
                            <div class="page-sort">
                                <form action="" class="sort-form">
                                    <select name="sort" class="form-control form-select sort">
                                        <option value="1" @if(request()->get('sort')==1)selected @endif>Product: Latest</option>
                                        <option value="2" @if(request()->get('sort')==2)selected @endif>Product: Oldest</option>
                                        <option value="3" @if(request()->get('sort')==3)selected @endif>Price: High To Low</option>
                                        <option value="4" @if(request()->get('sort')==4)selected @endif>Price: Low To High</option>
                                        <option value="5" @if(request()->get('sort')==5)selected @endif>Name: A-Z</option>
                                        <option value="6" @if(request()->get('sort')==6)selected @endif>Name: Z-A</option>
                                    </select>
                                    <input type="hidden" name="min_price" value="{{request()->get('min_price')}}" />
                                    <input type="hidden" name="max_price" value="{{request()->get('max_price')}}" />
                                </form>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="offer_timer" id="simple_timer"></div>
            </div>
        </div>

        <div class="row">
            @foreach($products as $key => $value)
            <div class="col-6 col-lg-3">
                <div class="zombie-product-card">
                    <a href="{{ route('product', $value->slug) }}" class="zombie-product-link">
                        <div class="zombie-product-img-container">
                            @if($value->image && $value->image->image)
                            <img src="{{ asset($value->image->image) }}" alt="{{ $value->name }}" class="zombie-product-img">
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
        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate">
                    {{$products->links('pagination::bootstrap-4')}}

                </div>
            </div>
        </div>
    </div>
</section>


@endsection
@push('script')
<script src="{{ asset('frontEnd/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontEnd/js/jquery.syotimer.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(".main_slider").owlCarousel({
            items: 1,
            loop: true,
            dots: true,
            autoplay: true,
            nav: true,
            autoplayHoverPause: true,
            margin: 0,
            mouseDrag: true,
            smartSpeed: 1000,
            autoplayTimeout: 5000,
            animateOut: "fadeOut",
            animateIn: "fadeIn",

            navText: ["<i class='fa-solid fa-angle-left'></i>",
                "<i class='fa-solid fa-angle-right'></i>"
            ],
            responsive: {
                0: {
                    items: 1,
                    dots: true,
                    nav: true
                },
                600: {
                    items: 1,
                    dots: true,
                    nav: true
                },
                1000: {
                    items: 1,
                    dots: true,
                    nav: true
                }
            }
        });
    });
</script>

<script>
    $(".sort").change(function() {
        $('#loading').show();
        $(".sort-form").submit();
    })
</script>

<script>
    $("#simple_timer").syotimer({
        date: new Date(2015, 0, 1),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",

        periodUnit: "d",
        periodic: true,
        periodInterval: 1,
    });
</script>

<script>
    function add_to_cart(button, event) {
        event.preventDefault();
        var form = $(button).closest('form');
        var url = form.attr('action');
        var submit_button = $(button);

        submit_button.val(`Please Wait...`);
        submit_button.prop('disabled', true);
        
        $.ajax({
            url: url,
            method: form.attr('method'),
            data: form.serialize(),
            success: function(response) {
                if (response.success == true) {
                    toastr.success(response.message);
                    var cart_count = response.cart_count;
                    $('#cart_count').text(cart_count);

                    // Update zombie cart count
                    $('.zombie-cart-count').text(cart_count);

                    // Show notification
                    $('.zombie-notification-text').text("Product added to cart!");
                    $('.zombie-cart-notification').fadeIn();

                    // Hide notification after 3 seconds
                    setTimeout(function() {
                        $('.zombie-cart-notification').fadeOut();
                    }, 3000);

                    // Button animation
                    const originalHtml = submit_button.val();
                    submit_button.val('ADDED');
                    submit_button.css('background-color', '#27ae60');

                    // Reset button after 2 seconds
                    setTimeout(function() {
                        submit_button.val(originalHtml);
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
                submit_button.val(`Add To Cart`);
                submit_button.prop('disabled', false);
            }
        });
    }

    // Cart icon click functionality
    $(document).ready(function() {
        $('.zombie-cart-icon').click(function() {
            var cartCount = $('.zombie-cart-count').text();
            alert('You have ' + cartCount + ' items in your cart!');
        });
    });
</script>
@endpush