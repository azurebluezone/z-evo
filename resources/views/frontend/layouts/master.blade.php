<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', $generalsetting->name) - {{$generalsetting->name}}</title>
    <!-- App favicon -->

    @php
        $feFaviconPath = $generalsetting->favicon ?? 'assets/images/logo_icon/favicon.png';
        $feFaviconPath = preg_replace('~^public/~', '', (string) $feFaviconPath);
        if (!empty($feFaviconPath) && !\Illuminate\Support\Facades\File::exists(public_path($feFaviconPath))) {
            $feFaviconPath = 'assets/images/logo_icon/favicon.png';
        }
    @endphp
    <link rel="shortcut icon" href="{{ asset($feFaviconPath) }}" alt="Super Ecommerce Favicon" />
    <meta name="author" content="Super Ecommerce" />
    <link rel="canonical" href="" />
    @include('frontend.layouts.partials.seo-meta')
    @stack('seo')
    @stack('css')
    <link rel="stylesheet" href="{{asset('frontEnd/css/bootstrap.min.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/animate.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/all.min.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/owl.carousel.min.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/owl.theme.default.min.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/mobile-menu.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/select2.min.css')}}" />
    <!-- toastr css -->
    <link rel="stylesheet" href="{{asset('backEnd/assets/css/toastr.min.css')}}" />

    <link rel="stylesheet" href="{{asset('frontEnd/css/wsit-menu.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/style.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/responsive.css')}}" />

    <link rel="stylesheet" href="{{asset('frontEnd/css/main.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/modern-product-design.css')}}" />
    <link rel="stylesheet" href="{{asset('frontEnd/css/zombie-style.css')}}" />
    <link rel="stylesheet" href="{{asset('/frontEnd/css/sunnah-heaven-styles.css')}}" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Updated Header Styles for Better Responsiveness */
        .modern-header {
            background: #000;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .modern-logo {
            color: #d08501 !important;
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            font-style: italic;
            display: flex;
            align-items: center;
            height: 100%;
        }

        .modern-search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            align-items: center;
        }

        .modern-search-input {
            border-radius: 0 !important;
            padding: 12px 55px 12px 20px;
            border: 2px solid transparent;
            width: 100%;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.12) !important;
            color: white !important;
            transition: all 0.3s ease;
        }

        .modern-search-input:focus {
            outline: none;
            border-color: #d08501 !important;
            background: rgba(255, 255, 255, 0.2);
        }

        .modern-search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .modern-search-btn {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            background: #d08501 !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 20px;
            color: white !important;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modern-search-btn:hover {
            background: #e6b800;
            transform: scale(1.05);
        }

        /* Enhanced Header Icons - Icon Only */
        .modern-user-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 8px;
            position: relative;
        }

        .modern-user-actions:hover {
            color: white;
            background: rgba(244, 196, 48, 0.2);
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modern-user-actions i {
            font-size: 1.4rem;
            color: #d08501 !important;
            transition: all 0.3s ease;
        }

        .modern-user-actions:hover i {
            color: white;
            transform: scale(1.1);
        }

        .modern-cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            border: 2px solid #2c2c2c;
            z-index: 10;
        }

        /* Mobile Responsive Header */
        @media (max-width: 991px) {
            .modern-header {
                padding: 10px 0;
            }

            .modern-logo {
                font-size: 1.6rem;
                justify-content: center;
            }

            .modern-search-container {
                max-width: 100%;
                margin: 8px 0;
                order: 2;
            }

            .modern-search-input {
                padding: 10px 45px 10px 15px;
                font-size: 13px;
            }

            .modern-search-btn {
                padding: 0 15px;
            }

            .modern-user-actions {
                width: 40px;
                height: 40px;
                margin: 0 5px;
            }

            .modern-user-actions i {
                font-size: 1.2rem;
            }

            .modern-cart-count {
                width: 18px;
                height: 18px;
                font-size: 0.7rem;
                top: -4px;
                right: -4px;
            }
        }

        @media (max-width: 768px) {
            .modern-header {
                padding: 8px 0;
            }

            .modern-header .container-fluid {
                padding: 0 15px;
            }

            .modern-header .row {
                flex-direction: row;
                align-items: center;
            }

            .modern-header .col-md-2,
            .modern-header .col-md-6,
            .modern-header .col-md-4 {
                flex: 1;
                max-width: 100%;
                flex-basis: auto;
                margin-bottom: 5px;
            }

            .modern-header .col-md-2 {
                order: 1;
                text-align: left;
            }

            .modern-header .col-md-6 {
                order: 3;
                margin-top: 5px;
                margin-bottom: 5px;
            }

            .modern-header .col-md-4 {
                order: 2;
                text-align: right;
            }

            .modern-logo {
                font-size: 1.4rem;
                margin: 0 auto;
                justify-content: center;
            }

            .modern-search-container {
                order: 3;
                max-width: 100%;
                margin: 10px 0;
            }

            .modern-user-actions {
                width: 36px;
                height: 36px;
                margin: 0 3px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .modern-user-actions i {
                font-size: 1.1rem;
            }

            .modern-cart-count {
                width: 16px;
                height: 16px;
                font-size: 0.6rem;
                top: -3px;
                right: -3px;
            }
        }

        @media (max-width: 576px) {
            .modern-header {
                padding: 6px 0;
            }

            .modern-logo {
                font-size: 1.3rem;
            }

            .modern-user-actions {
                width: 34px;
                height: 34px;
                margin: 0 2px;
            }

            .modern-user-actions i {
                font-size: 1rem;
            }

            .modern-cart-count {
                width: 15px;
                height: 15px;
                font-size: 0.55rem;
            }

            .modern-search-input {
                padding: 8px 40px 8px 12px;
                font-size: 12px;
            }

            .modern-search-btn {
                padding: 0 12px;
            }

            .modern-header .col-md-2 {
                text-align: center;
            }

            .modern-header .col-md-4 {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .modern-header {
                padding: 5px 0;
            }

            .modern-logo img {
                max-height: 40px;
            }

            .modern-user-actions {
                width: 32px;
                height: 32px;
                margin: 0 1px;
            }

            .modern-user-actions i {
                font-size: 0.9rem;
            }

            .modern-cart-count {
                width: 14px;
                height: 14px;
                font-size: 0.5rem;
            }

            .modern-search-input {
                padding: 7px 35px 7px 10px;
                font-size: 11px;
            }

            .modern-search-btn {
                padding: 0 10px;
            }
        }

        /* Force sharp corners - override any other CSS */
        .modern-search-form,
        .modern-search-form .modern-search-input,
        .modern-search-form .modern-search-btn,
        .modern-search-container .modern-search-input,
        .modern-search-container .modern-search-btn {
            border-radius: 0 !important;
        }
    </style>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    @foreach($gtm_code as $gtm)
    <!-- Google tag (gtag.js) -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '{{ $gtm->code }}');
    </script>
    <!-- End Google Tag Manager -->
    @endforeach
</head>

<body class="gotop">
    @php $subtotal = Cart::instance('shopping')->subtotal(); @endphp
    <style>
        /* ===== MODERN RESPONSIVE STYLES ===== */
        /* Desktop: Logo Left | Search Right | Icons Right */
        @media (min-width: 992px) {
            .modern-header-search {
                justify-content: flex-end !important;
            }

            .modern-search-container {
                margin-left: auto;
            }
        }

        /* Tablet & Mobile: Menu Left | Logo Center | Cart Right (sv-hdr custom prefix) */
        @media (max-width: 991px) {
            .modern-header .modern-header-row {
                flex-wrap: nowrap;
            }

            .modern-header .col-md-2,
            .modern-header .col-md-4,
            .modern-header .col-md-6 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }

            /* sv-hdr: Hide search box & other buttons on mobile */
            .sv-hdr-search-row {
                display: none !important;
            }

            .sv-hdr-hide-mobile {
                display: none !important;
            }

            /* sv-hdr: Logo center on mobile */
            .sv-hdr-mobile-logo {
                display: flex !important;
                justify-content: center;
                align-items: center;
                margin: 0 auto;
                width: 100%;
            }

            .modern-header-search {
                justify-content: center !important;
            }

            /* sv-hdr: Cart button only on right */
            .sv-hdr-cart-btn {
                display: flex !important;
                margin-left: auto;
            }

            .modern-nav {
                display: none !important;
            }

            .modern-mobile-menu-toggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: #d08501 !important;
                color: white;
                border-radius: 5px;
                cursor: pointer;
                font-size: 20px;
                margin-right: 5px;
            }

            .modern-logo-mobile {
                display: flex !important;
                justify-content: center;
                margin: 0 auto;
            }

            .sv-footer-section {
                margin-bottom: 30px;
            }

            .mobile-menu {
                position: fixed;
                top: 0;
                left: -100%;
                width: 85%;
                max-width: 320px;
                height: 100vh;
                background: white;
                z-index: 9999;
                transition: left 0.3s ease;
                overflow-y: auto;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }

            .mobile-menu.active {
                left: 0;
            }

            .mobile-menu-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
            }

            .mobile-menu-overlay.active {
                display: block;
            }


            .modern-search-input {
                padding-right: 50px !important;
            }

            .modern-search-btn {
                right: 0 !important;
                background: #d08501 !important;
                color: white !important;
                width: 50px;
                border-radius: 0 !important;
            }
        }

        @media (max-width: 767px) {
            .modern-header {
                padding: 10px 0;
            }

            .modern-logo img {
                max-height: 40px !important;
            }

            .modern-user-actions {
                width: 36px !important;
                height: 36px !important;
            }

            .modern-cart-count {
                width: 16px !important;
                height: 16px !important;
                font-size: 0.6rem !important;
                top: -6px !important;
                right: -6px !important;
            }

            .footer_nav {
                display: flex !important;
            }

            .modern-mobile-header-left {
                display: flex;
                align-items: center;
            }
        }

        @media (max-width: 576px) {
            .modern-search-input {
                font-size: 14px;
                padding: 10px 50px 10px 15px !important;
            }

            .modern-search-btn {
                padding: 10px 15px !important;
            }

            .d-flex.justify-content-end {
                gap: 15px !important;
            }
        }

        @media (min-width: 992px) {
            .footer_nav {
                display: none !important;
            }

            .mobile-menu {
                display: none;
            }

            .modern-mobile-menu-toggle {
                display: none !important;
            }
        }

        /* Modern Search Box - Centered, blends with header */
        .modern-search-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            margin-right: -50px;
        }

        .modern-search-form {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.12) !important;
            border-radius: 0 !important;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
        }

        .modern-search-input {
            width: 100%;
            padding: 12px 55px 12px 20px;
            border: none;
            border-radius: 0 !important;
            background: transparent !important;
            font-size: 16px;
            color: white !important;
        }

        .modern-search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .modern-search-input:focus {
            outline: none;
            box-shadow: none;
        }

        .modern-search-form .modern-search-btn {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            background: #d08501 !important;
            border: none !important;
            border-radius: 0 !important;
            color: white !important;
            padding: 0 25px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modern-search-form .modern-search-btn:hover {
            background: #c8955a;
        }

        /* Mobile Menu Styles */
        .mobile-menu-logo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .logo-image img {
            max-height: 40px;
            width: auto;
        }

        .mobile-menu-close {
            font-size: 24px;
            color: #333;
            cursor: pointer;
        }

        .first-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .parent-category {
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .menu-category-name {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }

        .mobile-menu .side_cat_img {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 12px;
            display: inline-block !important;
            visibility: visible !important;
            flex-shrink: 0;
        }

        .mobile-menu-logo-text {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        .menu-category-toggle {
            position: absolute;
            left: 15px;
            right: auto;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .parent-category.has-children .menu-category-name {
            padding-left: 45px;
        }

        .second-nav,
        .third-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #f9f9f9;
            display: none;
        }

        .second-nav.active,
        .third-nav.active {
            display: block;
        }

        .parent-subcategory {
            position: relative;
            border-bottom: 1px solid #e0e0e0;
        }

        .menu-subcategory-name {
            display: block;
            padding: 12px 15px 12px 45px;
            color: #555;
            text-decoration: none;
        }

        .menu-subcategory-toggle {
            position: absolute;
            left: 15px;
            right: auto;
            top: 12px;
            cursor: pointer;
            color: #888;
        }

        .parent-subcategory.has-children .menu-subcategory-name {
            padding-left: 45px;
        }

        .childcategory a {
            display: block;
            padding: 10px 15px 10px 60px;
            color: #666;
            text-decoration: none;
            border-bottom: 1px solid #eee;
        }

        /* Mobile Navigation Toggle */
        .toggle {
            cursor: pointer;
        }

        /* Footer Navigation */
        .footer_nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #000;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .footer_nav ul {
            display: flex;
            justify-content: space-around;
            list-style: none;
            padding: 10px 0;
            margin: 0;
        }

        .footer_nav li {
            flex: 1;
            text-align: center;
        }

        .footer_nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #555;
            text-decoration: none;
            font-size: 12px;
        }

        .footer_nav i {
            font-size: 20px;
            margin-bottom: 4px;
            color: #d08501 !important;
        }

        /* Modern Header Responsive Fix */
        .modern-header .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
        }

        /* Mobile Menu Toggle Button */
        .modern-mobile-menu-toggle {
            display: none;
        }

        /* Modern Header Actions */
        .modern-user-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .modern-user-actions:hover {
            background: rgba(212, 165, 116, 0.2);
            transform: scale(1.1);
        }

        /* Modern Navigation Styles */
        .modern-nav {
            background: #2c2c2c;
            padding: 0;
            TEXT-ALIGN: CENTER;
        }

        .modern-nav-item {
            display: inline-block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }

        .modern-nav-item:hover {
            background: #d08501 !important;
            color: white;
        }

        .modern-nav-item.active {
            background: #d08501 !important;
            color: white;
        }

        @media (max-width: 991px) {
            .modern-search-form {
                border-radius: 25px;
            }

            .modern-search-form-input {
                padding: 12px 15px;
                font-size: 14px;
            }

            .modern-search-form-btn {
                padding: 0 20px;
                min-width: 45px;
            }
        }

        @media (max-width: 576px) {
            .modern-search-form {
                border-radius: 20px;
            }

            .modern-search-form-input {
                padding: 10px 15px;
                font-size: 13px;
            }

            .modern-search-form-btn {
                padding: 0 15px;
                min-width: 40px;
            }
        }

        .modern-submenu-link:hover,
        .modern-childmenu-link:hover {
            background: #d08501 !important;
            color: white !important;
        }

        /* Modern Navigation Dropdown */
        .modern-nav-dropdown:hover .modern-submenu {
            display: block;
        }

        .modern-submenu-item:hover .modern-childmenu {
            display: block;
        }
    </style>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay"></div>

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <div class="mobile-menu-logo">
            <div class="logo-image">
                @if($generalsetting->white_logo ?? $generalsetting->logo ?? null)
                <img src="{{ asset($generalsetting->white_logo ?? $generalsetting->logo) }}" alt="{{ $generalsetting->name ?? 'Sunnah Heaven' }}" />
                @else
                <span class="mobile-menu-logo-text">{{ $generalsetting->name ?? 'Sunnah Heaven' }}</span>
                @endif
            </div>
            <div class="mobile-menu-close">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        <ul class="first-nav">
            @foreach($menucategories as $scategory)
            <li class="parent-category {{ $scategory->subcategories->count() > 0 ? 'has-children' : '' }}">
                @if($scategory->subcategories->count() > 0)
                <span class="menu-category-toggle" role="button" aria-label="Toggle category" aria-expanded="false">
                    <i class="fa-solid fa-plus"></i>
                </span>
                @endif
                <a href="{{ route('products.category', $scategory->slug) }}" class="menu-category-name">
                    @if(isset($scategory->image) && $scategory->image)
                    <img src="{{ asset($scategory->image) }}" alt="{{ $scategory->name ?? '' }}" class="side_cat_img" />
                    @endif
                    {{$scategory->name}}
                </a>
                <ul class="second-nav">
                    @foreach($scategory->subcategories as $subcategory)
                    <li class="parent-subcategory {{ $subcategory->childcategories->count() > 0 ? 'has-children' : '' }}">
                        @if($subcategory->childcategories->count() > 0)
                        <span class="menu-subcategory-toggle" role="button" aria-label="Toggle subcategory" aria-expanded="false"><i class="fa-solid fa-plus"></i></span>
                        @endif
                        <a href="{{ route('products.category', $subcategory->slug) }}" class="menu-subcategory-name">{{$subcategory->subcategoryName}}</a>
                        <ul class="third-nav">
                            @foreach($subcategory->childcategories as $childcat)
                            <li class="childcategory"><a href="{{ route('products.category', $childcat->slug) }}" class="menu-childcategory-name">{{$childcat->childcategoryName}}</a></li>
                            @endforeach
                        </ul>
                    </li>
                    @endforeach
                </ul>
            </li>
            @endforeach

            <li class="parent-category">
                <a href="{{ route('home') }}" class="menu-category-name">
                    <i class="fa-solid fa-house" style="margin-right: 10px; color: #d08501 !important;"></i>
                    Home
                </a>
            </li>
            <li class="parent-category">
                <a href="{{ url('/products') }}" class="menu-category-name">
                    <i class="fa-solid fa-bag-shopping" style="margin-right: 10px; color: #d08501 !important;"></i>
                    Products
                </a>
            </li>
            <li class="parent-category">
                <a href="{{ url('/categories') }}" class="menu-category-name">
                    <i class="fa-solid fa-layer-group" style="margin-right: 10px; color: #d08501 !important;"></i>
                    All Categories
                </a>
            </li>
        </ul>
    </div>

    <!-- ===== MODERN HEADER SECTION ===== -->
    <header class="modern-header">
        <div class="container-fluid">
            <div class="row align-items-center modern-header-row">
                {{-- Desktop: Logo Left | Search Center-Right | Icons Right --}}
                {{-- Mobile: Menu Left | Logo Center | Icons Right --}}
                <div class="col-md-2 col-4 modern-header-logo order-1 order-md-1 d-flex align-items-center">
                    <div class="modern-mobile-menu-toggle d-md-none">
                        <i class="fa-solid fa-bars"></i>
                    </div>
                    <a href="{{route('home')}}" class="modern-logo modern-logo-desktop d-none d-md-flex">
                        @if($generalsetting->white_logo ?? null)
                        <img src="{{ asset($generalsetting->white_logo) }}" alt="{{ $generalsetting->name ?? 'Sunnah Heaven' }}" style="max-height: 50px; width: auto;">
                        @else
                        {{ $generalsetting->name ?? 'SUNNAH HEAVEN' }}
                        @endif
                    </a>
                </div>
                <div class="col-md-6 col-4 modern-header-search order-2 order-md-2 d-flex justify-content-md-end justify-content-center align-items-center">
                    <a href="{{route('home')}}" class="modern-logo modern-logo-mobile sv-hdr-mobile-logo d-md-none">
                        @if($generalsetting->white_logo ?? null)
                        <img src="{{ asset($generalsetting->white_logo) }}" alt="{{ $generalsetting->name ?? 'Sunnah Heaven' }}" style="max-height: 40px; width: auto;">
                        @else
                        <span>{{ $generalsetting->name ?? 'SUNNAH HEAVEN' }}</span>
                        @endif
                    </a>
                    <div class="modern-search-container modern-search-desktop d-none d-md-block">
                        <form action="{{route('search')}}" class="modern-search-form" method="get">
                            <input type="text" class="modern-search-input search_keyword" placeholder="Search for products..." name="keyword">
                            <button type="submit" class="modern-search-btn">
                                <i class="fa-solid fa-magnifying-glass" style="font-size: 1.1rem;"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-md-4 col-4 modern-header-actions order-3 order-md-3 d-flex justify-content-end align-items-center">
                    <div class="d-flex justify-content-end align-items-center modern-header-icons" style="gap: 20px;">
                        <a href="{{route('customer.order_track')}}" class="modern-user-actions sv-hdr-hide-mobile" title="Track Order">
                            <i class="fa-solid fa-truck-fast" style="font-size: 1.3rem; color: #d08501 !important;"></i>
                        </a>
                        @if(Auth::guard('customer')->user())
                        <a href="{{route('customer.account')}}" class="modern-user-actions sv-hdr-hide-mobile" title="{{Str::limit(Auth::guard('customer')->user()->name,14)}}">
                            <i class="fa-solid fa-user-circle" style="font-size: 1.3rem; color: #d08501 !important;"></i>
                        </a>
                        @php
                            $customerUnread = (int) Auth::guard('customer')->user()->unreadNotifications()->count();
                        @endphp
                        <a href="{{route('customer.notifications.index')}}" class="modern-user-actions sv-hdr-hide-mobile" title="Notifications" style="position: relative;">
                            <i class="fa-solid fa-bell" style="font-size: 1.3rem; color: #d08501 !important;"></i>
                            @if($customerUnread > 0)
                                <span style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; min-width: 18px; height: 18px; padding: 0 5px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold; line-height: 1;">{{ $customerUnread }}</span>
                            @endif
                        </a>
                        @else
                        <a href="{{route('customer.login')}}" class="modern-user-actions sv-hdr-hide-mobile" title="Login / Sign Up">
                            <i class="fa-solid fa-right-to-bracket" style="font-size: 1.3rem; color: #d08501 !important;"></i>
                        </a>
                        @endif
                        <a href="{{route('customer.checkout')}}" class="modern-user-actions sv-hdr-cart-btn header-cart-link is-small" title="Cart" style="position: relative;">
                            <i class="fa-solid fa-cart-shopping" style="font-size: 1.3rem; color: #d08501 !important;"></i>
                            <span class="modern-cart-count" id="cart_count" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">{{Cart::instance('shopping')->count()}}</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== MODERN NAVIGATION SECTION WITH SUBMENUS ===== -->
        <nav class="modern-nav" style="margin-bottom: 0; border-bottom: none;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="d-md-block d-none">
                            <a href="{{route('home')}}" class="modern-nav-item active">HOME</a>
                            @foreach ($menucategories as $scategory)
                            <div class="modern-nav-dropdown" style="display: inline-block; position: relative;">
                                <a href="{{ url('category/' . $scategory->slug) }}" class="modern-nav-item">{{ $scategory->name }}</a>
                                @if($scategory->subcategories->count() > 0)
                                <div class="modern-submenu" style="position: absolute; top: 100%; left: 0; background: #2c2c2c; min-width: 200px; z-index: 1000; display: none; border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                                    @foreach ($scategory->subcategories as $subcategory)
                                    <div class="modern-submenu-item" style="position: relative;">
                                        <a href="{{ url('subcategory/' . $subcategory->slug) }}" class="modern-submenu-link" style="color: white; padding: 12px 20px; display: block; text-decoration: none; border-bottom: 1px solid #444; transition: background-color 0.3s ease;">{{ $subcategory->subcategoryName }}</a>
                                        @if($subcategory->childcategories->count() > 0)
                                        <div class="modern-childmenu" style="position: absolute; left: 100%; top: 0; background: #2c2c2c; min-width: 200px; z-index: 1001; display: none; border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                                            @foreach ($subcategory->childcategories as $childcat)
                                            <a href="{{ url('products/' . $childcat->slug) }}" class="modern-childmenu-link" style="color: white; padding: 12px 20px; display: block; text-decoration: none; border-bottom: 1px solid #444; transition: background-color 0.3s ease;">{{ $childcat->childcategoryName }}</a>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div id="content">
            @yield('content')
        </div>
        <!-- content end -->
        <!-- ===== SUNNAH HEAVEN FOOTER - CONFLICT FREE VERSION ===== -->
        <footer class="sh-footer-v2">
            <div class="container">
                <div class="row">
                    <!-- About Section -->
                    <div class="col-lg-3 col-md-6 col-sm-6 sh-footer-col">
                        <h5 class="sh-footer-heading">About {{ $generalsetting->name ?? 'Sunnah Heaven' }}</h5>
                        <p class="sh-footer-desc">Your trusted destination for authentic modest fashion. We provide high-quality Islamic clothing that combines tradition with contemporary style.</p>
                        <div class="sh-social-wrapper">
                            @foreach($socialicons as $value)
                            <a href="{{$value->link}}" target="_blank" class="sh-social-btn" aria-label="Social Media"><i class="{{$value->icon}}"></i></a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="col-lg-3 col-md-6 col-sm-6 sh-footer-col">
                        <h5 class="sh-footer-heading sh-accordion-head" data-target="shQuickLinks">
                            Quick Links
                            <i class="fas fa-chevron-down sh-chevron-icon"></i>
                        </h5>
                        <div class="sh-link-wrapper" id="shQuickLinks">
                            <a href="{{route('home')}}" class="sh-footer-item"><i class="fas fa-home"></i> Home</a>
                            <a href="{{route('contact')}}" class="sh-footer-item"><i class="fas fa-info-circle"></i> About Us</a>
                            <a href="{{route('contact')}}" class="sh-footer-item"><i class="fas fa-envelope"></i> Contact</a>
                            <a href="#" class="sh-footer-item"><i class="fas fa-ruler"></i> Size Guide</a>
                            <a href="#" class="sh-footer-item"><i class="fas fa-question-circle"></i> FAQ</a>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="col-lg-3 col-md-6 col-sm-6 sh-footer-col">
                        <h5 class="sh-footer-heading sh-accordion-head" data-target="shCategories">
                            Categories
                            <i class="fas fa-chevron-down sh-chevron-icon"></i>
                        </h5>
                        <div class="sh-link-wrapper" id="shCategories">
                            @foreach($menucategories as $category)
                            <a href="{{ url('category/' . $category->slug) }}" class="sh-footer-item"><i class="fas fa-tag"></i> {{ $category->name }}</a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Customer Service -->
                    <div class="col-lg-3 col-md-6 col-sm-6 sh-footer-col">
                        <h5 class="sh-footer-heading sh-accordion-head" data-target="shCustomerService">
                            Customer Service
                            <i class="fas fa-chevron-down sh-chevron-icon"></i>
                        </h5>
                        <div class="sh-link-wrapper" id="shCustomerService">
                            <a href="#" class="sh-footer-item"><i class="fas fa-shipping-fast"></i> Shipping Info</a>
                            <a href="#" class="sh-footer-item"><i class="fas fa-exchange-alt"></i> Returns & Exchanges</a>
                            <a href="#" class="sh-footer-item"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                            <a href="#" class="sh-footer-item"><i class="fas fa-file-contract"></i> Terms of Service</a>
                            <a href="{{route('customer.order_track')}}" class="sh-footer-item"><i class="fas fa-map-marker-alt"></i> Track Your Order</a>
                        </div>
                    </div>
                </div>

                <!-- Footer Bottom -->
                <div class="sh-footer-bottom-wrapper">
                    <div class="sh-footer-bottom-content">
                        <p class="sh-copyright-text">
                            <i class="far fa-copyright"></i> {{ date('Y') }} {{ $generalsetting->name ?? 'Sunnah Heaven' }}. All rights reserved.
                            <span class="sh-heart-text">Designed with <i class="fas fa-heart sh-heart-icon"></i> for the Muslim community</span>
                        </p>
                        <p class="sh-developer-text">
                            <i class="fas fa-code"></i> Development By: <a href="#" class="sh-dev-link">Developer Zone</a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Mobile Bottom Navigation -->
        <div class="sh-mobile-nav-bar">
            <ul class="sh-nav-list">
                <li class="sh-nav-item">
                    <a class="toggle sh-nav-link">
                        <span class="sh-nav-icon-wrapper">
                            <i class="fa-solid fa-bars"></i>
                        </span>
                        <span class="sh-nav-label">Menu</span>
                    </a>
                </li>

                <li class="sh-nav-item">
                    <a href="https://wa.me/8801314883211" class="sh-nav-link" target="_blank">
                        <span class="sh-nav-icon-wrapper sh-whatsapp-icon">
                            <i class="fa-brands fa-whatsapp"></i>
                        </span>
                        <span class="sh-nav-label">WhatsApp</span>
                    </a>
                </li>
                <li class="sh-nav-item mobile_home">
                    <a href="{{route('home')}}" class="sh-nav-link sh-home-link">
                        <span class="sh-nav-icon-wrapper sh-home-icon">
                            <i class="fa-solid fa-house"></i>
                        </span>
                        <span class="sh-nav-label sh-home-label">Home</span>
                    </a>
                </li>
                <li class="sh-nav-item">
                    <a href="{{route('customer.checkout')}}" class="sh-nav-link">
                        <span class="sh-nav-icon-wrapper sh-cart-icon-wrapper">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="sh-cart-count-badge">{{Cart::instance('shopping')->count()}}</span>
                        </span>
                        <span class="sh-nav-label">Cart</span>
                    </a>
                </li>
                @if(Auth::guard('customer')->user())
                @php
                    $customerUnreadMobile = (int) Auth::guard('customer')->user()->unreadNotifications()->count();
                @endphp
                <li class="sh-nav-item">
                    <a href="{{route('customer.notifications.index')}}" class="sh-nav-link">
                        <span class="sh-nav-icon-wrapper sh-cart-icon-wrapper">
                            <i class="fa-solid fa-bell"></i>
                            @if($customerUnreadMobile > 0)
                                <span class="sh-cart-count-badge">{{ $customerUnreadMobile }}</span>
                            @endif
                        </span>
                        <span class="sh-nav-label">Notify</span>
                    </a>
                </li>
                <li class="sh-nav-item">
                    <a href="{{route('customer.account')}}" class="sh-nav-link">
                        <span class="sh-nav-icon-wrapper">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <span class="sh-nav-label">Account</span>
                    </a>
                </li>
                @else
                <li class="sh-nav-item">
                    <a href="{{route('customer.login')}}" class="sh-nav-link">
                        <span class="sh-nav-icon-wrapper">
                            <i class="fa-solid fa-right-to-bracket"></i>
                        </span>
                        <span class="sh-nav-label">Login</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>

        <style>
            /* ========================================
   SUNNAH HEAVEN FOOTER V2 - CONFLICT FREE
   ======================================== */

        /* Reset for Footer Only */
        .sh-footer-v2 * {
            box-sizing: border-box;
        }

        /* Main Footer Container */
        .sh-footer-v2 {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #ffffff;
            padding: 60px 0 20px;
            position: relative;
            overflow: hidden;
        }

        .sh-footer-v2::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #d08501, transparent) !important;
        }

        /* Footer Columns */
        .sh-footer-col {
            margin-bottom: 30px;
            animation: shFadeInUp 0.6s ease-out;
        }

        @keyframes shFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Footer Heading */
        .sh-footer-heading {
            color: #d08501 !important;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 600;
            position: relative;
            padding-bottom: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .sh-footer-heading::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, #d08501, transparent) !important;
            transition: width 0.3s ease;
        }

        .sh-footer-col:hover .sh-footer-heading::after {
            width: 80px;
        }

        /* Chevron Icon */
        .sh-chevron-icon {
            transition: transform 0.3s ease;
            display: none;
            font-size: 14px;
        }

        .sh-accordion-head.sh-active .sh-chevron-icon {
            transform: rotate(180deg);
        }

        /* Footer Description */
        .sh-footer-desc {
            color: #c5c5c5;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: justify;
        }

        /* Link Wrapper */
        .sh-link-wrapper {
            max-height: 500px;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out;
        }

        /* Footer Links */
        .sh-footer-item {
            display: flex;
            align-items: center;
            color: #b8b8b8;
            margin-bottom: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            padding: 8px 0;
            position: relative;
        }

        .sh-footer-item i {
            margin-right: 10px;
            color: #d08501 !important;
            font-size: 14px;
            width: 18px;
            transition: transform 0.3s ease;
        }

        .sh-footer-item:hover {
            color: #d08501 !important;
            padding-left: 10px;
            text-decoration: none;
        }

        .sh-footer-item:hover i {
            transform: scale(1.2);
        }

        /* Social Wrapper */
        .sh-social-wrapper {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .sh-social-btn {
            color: #d08501 !important;
            font-size: 18px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #d08501 !important;
            border-radius: 50%;
            transition: all 0.3s ease;
            background: transparent;
            text-decoration: none;
        }

        .sh-social-btn:hover {
            background: #d08501 !important;
            color: #1a1a2e;
            transform: translateY(-5px) rotate(360deg);
            box-shadow: 0 5px 15px rgba(212, 165, 116, 0.4);
        }

        /* Footer Bottom */
        .sh-footer-bottom-wrapper {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 50px;
            padding-top: 25px;
        }

        .sh-footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .sh-copyright-text {
            color: #9e9e9e;
            margin: 0;
            font-size: 14px;
            letter-spacing: 0.3px;
            line-height: 1.6;
        }

        .sh-heart-text {
            color: #b8b8b8;
            margin-left: 10px;
        }

        .sh-heart-icon {
            color: #ff4757;
            animation: shHeartbeat 1.5s infinite;
        }

        @keyframes shHeartbeat {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .sh-developer-text {
            color: #9e9e9e;
            margin: 0;
            font-size: 14px;
        }

        .sh-dev-link {
            color: #d08501 !important;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .sh-dev-link:hover {
            color: #ffffff;
            text-shadow: 0 0 10px rgba(212, 165, 116, 0.5);
            text-decoration: none;
        }

        /* ========================================
   MOBILE BOTTOM NAVIGATION - CONFLICT FREE
   ======================================== */

        .sh-mobile-nav-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, #1a1a2e 0%, #16213e 100%);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
            border-top: 2px solid #d08501 !important;
            backdrop-filter: blur(10px);
        }

        .sh-nav-list {
            display: flex;
            justify-content: space-around;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 10px 0;
            max-width: 100%;
        }

        .sh-nav-item {
            flex: 1;
            text-align: center;
        }

        .sh-nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #b8b8b8;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px 5px;
            position: relative;
            cursor: pointer;
            border-radius: 12px;
        }

        .sh-nav-icon-wrapper {
            font-size: 24px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sh-nav-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
        }

        /* Hover & Active States */
        .sh-nav-link:hover,
        .sh-nav-link:active {
            color: #d08501 !important;
            text-decoration: none;
        }

        .sh-nav-link:active {
            transform: scale(0.95);
        }

        .sh-nav-link:hover .sh-nav-icon-wrapper {
            transform: translateY(-3px);
        }

        /* WhatsApp Special Style */
        .sh-whatsapp-icon {
            color: #25D366 !important;
        }

        .sh-nav-link:hover .sh-whatsapp-icon {
            color: #128C7E !important;
            filter: drop-shadow(0 0 8px rgba(37, 211, 102, 0.6));
        }

        /* Home Icon Special Style */
        .sh-home-link {
            margin-top: -20px;
        }

        .sh-home-icon {
            background: linear-gradient(135deg, #d08501 0%, #f4d03f 100%) !important;
            color: #1a1a2e;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(212, 165, 116, 0.5);
            border: 3px solid #1a1a2e;
            font-size: 26px;
        }

        .sh-nav-link:hover .sh-home-icon {
            transform: translateY(-5px) rotate(360deg);
            box-shadow: 0 8px 25px rgba(212, 165, 116, 0.7);
        }

        .sh-home-label {
            color: #d08501 !important;
            font-weight: 600;
        }

        /* Cart Icon with Badge */
        .sh-cart-icon-wrapper {
            position: relative;
        }

        .sh-cart-count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
            color: #ffffff;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            border: 2px solid #1a1a2e;
            padding: 0 5px;
            animation: shPulse 2s infinite;
        }

        @keyframes shPulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 6px rgba(255, 71, 87, 0);
            }
        }

        /* Menu Icon Animation */
        .toggle .sh-nav-icon-wrapper i {
            transition: transform 0.3s ease;
        }

        .toggle:hover .sh-nav-icon-wrapper i {
            transform: rotate(90deg);
        }

        /* Ripple Effect */
        .sh-nav-link::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(212, 165, 116, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .sh-nav-link:active::before {
            width: 100px;
            height: 100px;
        }

        /* ========================================
   RESPONSIVE DESIGN
   ======================================== */

        /* Tablet & Mobile */
        @media (max-width: 991px) {
            .sh-footer-v2 {
                padding: 50px 0 90px;
            }

            .sh-mobile-nav-bar {
                display: block;
            }

            .sh-chevron-icon {
                display: block;
            }

            .sh-link-wrapper {
                max-height: 0;
            }

            .sh-accordion-head.sh-active+.sh-link-wrapper {
                max-height: 500px;
            }

            body {
                padding-bottom: 70px;
            }
        }

        /* Tablet Only */
        @media (max-width: 767px) {
            .sh-footer-v2 {
                padding: 40px 0 90px;
            }

            .sh-footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .sh-copyright-text,
            .sh-developer-text {
                width: 100%;
            }

            .sh-heart-text {
                display: block;
                margin: 5px 0 0 0;
            }
        }

        /* Mobile Only */
        @media (max-width: 575px) {
            .sh-footer-heading {
                font-size: 16px;
            }

            .sh-footer-desc,
            .sh-footer-item {
                font-size: 13px;
            }

            .sh-copyright-text,
            .sh-developer-text {
                font-size: 12px;
            }

            .sh-nav-icon-wrapper {
                font-size: 22px;
            }

            .sh-home-icon {
                width: 52px;
                height: 52px;
                font-size: 24px;
            }

            .sh-nav-label {
                font-size: 10px;
            }

            .sh-cart-count-badge {
                min-width: 18px;
                height: 18px;
                font-size: 10px;
            }
        }

        /* Extra Small Mobile */
        @media (max-width: 380px) {
            .sh-nav-list {
                padding: 8px 0;
            }

            .sh-nav-link {
                padding: 6px 3px;
            }

            .sh-nav-icon-wrapper {
                font-size: 20px;
            }

            .sh-home-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
            }

            .sh-nav-label {
                font-size: 9px;
            }
        }

        /* Desktop Only - Remove Mobile Nav Padding */
        @media (min-width: 992px) {
            body {
                padding-bottom: 0;
            }

            .sh-accordion-head.sh-active+.sh-link-wrapper {
                max-height: 500px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            'use strict';

            // ===== Mobile Menu Toggle =====
            const shMobileMenuToggle = document.querySelector('.modern-mobile-menu-toggle');
            const shToggleButton = document.querySelector('.toggle');
            const shMobileMenu = document.querySelector('.mobile-menu');
            const shMobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
            const shMobileMenuClose = document.querySelector('.mobile-menu-close');

            if (shMobileMenuToggle) {
                shMobileMenuToggle.addEventListener('click', function() {
                    if (shMobileMenu) shMobileMenu.classList.add('active');
                    if (shMobileMenuOverlay) shMobileMenuOverlay.classList.add('active');
                });
            }

            if (shToggleButton) {
                shToggleButton.addEventListener('click', function() {
                    if (shMobileMenu) shMobileMenu.classList.add('active');
                    if (shMobileMenuOverlay) shMobileMenuOverlay.classList.add('active');
                });
            }

            if (shMobileMenuClose) {
                shMobileMenuClose.addEventListener('click', function() {
                    if (shMobileMenu) shMobileMenu.classList.remove('active');
                    if (shMobileMenuOverlay) shMobileMenuOverlay.classList.remove('active');
                });
            }

            if (shMobileMenuOverlay) {
                shMobileMenuOverlay.addEventListener('click', function() {
                    if (shMobileMenu) shMobileMenu.classList.remove('active');
                    this.classList.remove('active');
                });
            }

            // ===== Footer Accordion for Mobile =====
            const shAccordionHeads = document.querySelectorAll('.sh-accordion-head');

            shAccordionHeads.forEach(function(head) {
                head.addEventListener('click', function() {
                    if (window.innerWidth <= 991) {
                        this.classList.toggle('sh-active');
                    }
                });
            });

            // ===== Auto-open accordions on desktop =====
            function shHandleAccordionResize() {
                if (window.innerWidth > 991) {
                    shAccordionHeads.forEach(function(head) {
                        head.classList.add('sh-active');
                    });
                } else {
                    shAccordionHeads.forEach(function(head) {
                        head.classList.remove('sh-active');
                    });
                }
            }

            shHandleAccordionResize();
            window.addEventListener('resize', shHandleAccordionResize);

            // ===== Mobile Menu Category Toggle =====
            const shCategoryToggles = document.querySelectorAll('.menu-category-toggle');
            shCategoryToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const submenu = this.parentElement.querySelector('.second-nav');
                    if (submenu) {
                        submenu.classList.toggle('active');
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('fa-plus');
                            icon.classList.toggle('fa-minus');
                        }
                        const expanded = submenu.classList.contains('active');
                        this.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    }
                });
            });

            // ===== Mobile Subcategory Toggle =====
            const shSubcategoryToggles = document.querySelectorAll('.menu-subcategory-toggle');
            shSubcategoryToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const childmenu = this.parentElement.querySelector('.third-nav');
                    if (childmenu) {
                        childmenu.classList.toggle('active');
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('fa-plus');
                            icon.classList.toggle('fa-minus');
                        }
                        const expanded = childmenu.classList.contains('active');
                        this.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    }
                });
            });

            // ===== Close mobile menu when clicking on links =====
            const shMenuLinks = document.querySelectorAll('.menu-category-name, .menu-subcategory-name, .menu-childcategory-name');
            shMenuLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (shMobileMenu) shMobileMenu.classList.remove('active');
                    if (shMobileMenuOverlay) shMobileMenuOverlay.classList.remove('active');
                });
            });

            // ===== Modern Navigation Hover Effects for Desktop =====
            if (window.innerWidth >= 992) {
                const shDropdowns = document.querySelectorAll('.modern-nav-dropdown');
                const shSubmenuItems = document.querySelectorAll('.modern-submenu-item');

                shDropdowns.forEach(function(dropdown) {
                    dropdown.addEventListener('mouseenter', function() {
                        const submenu = this.querySelector('.modern-submenu');
                        if (submenu) submenu.style.display = 'block';
                    });

                    dropdown.addEventListener('mouseleave', function() {
                        const submenu = this.querySelector('.modern-submenu');
                        if (submenu) submenu.style.display = 'none';
                    });
                });

                shSubmenuItems.forEach(function(item) {
                    item.addEventListener('mouseenter', function() {
                        const childmenu = this.querySelector('.modern-childmenu');
                        if (childmenu) childmenu.style.display = 'block';
                    });

                    item.addEventListener('mouseleave', function() {
                        const childmenu = this.querySelector('.modern-childmenu');
                        if (childmenu) childmenu.style.display = 'none';
                    });
                });
            }
        });
    </script>

    <!--Add TO cart-->
    <!-- <div class='header-list-items'>-->


    <!-- <ul>-->
    <!--    <li class="cart-dialog" id="cart-qty">-->
    <!--                                    <a href="{{route('customer.checkout')}}">-->
    <!--                                        <p class="margin-shopping">-->
    <!--                                            <i class="fa-solid fa-cart-shopping"></i>-->
    <!--                                            <span>{{Cart::instance('shopping')->count()}}</span>-->
    <!--                                        </p>-->
    <!--                                    </a>-->
    <!--                                    <div class="cshort-summary">-->
    <!--                                        <ul>-->
    <!--                                            @foreach(Cart::instance('shopping')->content() as $key=>$value)-->
    <!--                                            <li>-->
    <!--                                                <a href=""><img src="{{asset($value->options->image)}}" alt="" /></a>-->
    <!--                                            </li>-->
    <!--                                            <li><a href="">{{Str::limit($value->name, 30)}}</a></li>-->
    <!--                                            <li>Qty: {{$value->qty}}</li>-->
    <!--                                            <li>-->
    <!--                                                <p>৳{{$value->price}}</p>-->
    <!--                                                <button class="remove-cart cart_remove" data-id="{{$value->rowId}}"><i data-feather="x"></i></button>-->
    <!--                                            </li>-->
    <!--                                            @endforeach-->
    <!--                                        </ul>-->
    <!--                                        <p><strong>সর্বমোট : ৳{{$subtotal}}</strong></p>-->
    <!--                                        <a href="{{route('customer.checkout')}}" class="go_cart"> অর্ডার করুন </a>-->
    <!--                                    </div>-->
    <!--                                </li>-->
    <!--</ul>-->
    <!-- </div>-->


    <div class="addToCartModule d-none d-sm-none d-lg-block d-md-block" style="">
        <a href="{{route('customer.checkout')}}">
            <div class="cart-button-big-device">
                <div class='cart-button-uper'>
                    <svg version="1.1" id="Calque_1" x="0px" y="0px" style="fill:#FDD670;stroke:#FDD670;" width="16px" height="24px" viewBox="0 0 100 160.13"
                        data-reactid=".1wiukg4a76i.3.0.3.2.0.0">
                        <g data-reactid=".1wiukg4a76i.3.0.3.2.0.0.0">
                            <polygon points="11.052,154.666 21.987,143.115 35.409,154.666  "
                                data-reactid=".1wiukg4a76i.3.0.3.2.0.0.0.0">
                            </polygon>
                            <path d="M83.055,36.599c-0.323-7.997-1.229-15.362-2.72-19.555c-2.273-6.396-5.49-7.737-7.789-7.737  
                    c-6.796,0-13.674,11.599-16.489,25.689l-3.371-0.2l-0.19-0.012l-5.509,1.333c-0.058-9.911-1.01-17.577-2.849-22.747   
                    c-2.273-6.394-5.49-7.737-7.788-7.737c-8.618,0-17.367,18.625-17.788,37.361l-13.79,3.336l0.18,1.731h-0.18v106.605l17.466-17.762   
                    l18.592,17.762V48.06H9.886l42.845-10.764l2.862,0.171c-0.47,2.892-0.74,5.865-0.822,8.843l-8.954,1.75v106.605l48.777-10.655   
                    V38.532l0.073-1.244L83.055,36.599z M36.35,8.124c2.709,0,4.453,3.307,5.441,6.081c1.779,5.01,2.69,12.589,2.711,22.513  
                    l-23.429,5.667C21.663,23.304,30.499,8.124,36.35,8.124z M72.546,11.798c2.709,0,4.454,3.308,5.44,6.081  
                    c1.396,3.926,2.252,10.927,2.571,18.572l-22.035-1.308C61.289,21.508,67.87,11.798,72.546,11.798z M58.062,37.612l22.581,1.34  
                    c0.019,0.762,0.028,1.528,0.039,2.297l-23.404,4.571C57.375,42.986,57.637,40.234,58.062,37.612z M83.165,40.766   
                    c-0.007-0.557-0.01-1.112-0.021-1.665l6.549,0.39L83.165,40.766z" data-reactid=".1wiukg4a76i.3.0.3.2.0.0.0.1"></path>
                        </g>
                    </svg>
                    <p>{{Cart::instance('shopping')->count()}} ITEMS</p>
                </div>
                <div class='total-cart-tk'>
                    <p> ৳ {{$subtotal}} </p>
                </div>
            </div>
        </a>

    </div>


    <div class="scrolltop" style="">
        <div class="scroll">
            <i class="fa fa-angle-up"></i>
        </div>
    </div>

    <!-- /. fixed sidebar -->

    <div id="custom-modal"></div>
    <div id="page-overlay"></div>
    <div id="loading">
        <div class="custom-loader"></div>
    </div>

    <script src="{{asset('frontEnd/js/jquery-3.6.3.min.js')}}"></script>
    <script src="{{asset('frontEnd/js/bootstrap.min.js')}}"></script>
    <script src="{{asset('frontEnd/js/owl.carousel.min.js')}}"></script>
    <script src="{{asset('frontEnd/js/mobile-menu.js')}}"></script>
    <script src="{{asset('frontEnd/js/wsit-menu.js')}}"></script>
    <script src="{{asset('frontEnd/js/mobile-menu-init.js')}}"></script>
    <script src="{{asset('frontEnd/js/wow.min.js')}}"></script>
    <script>
        new WOW().init();
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- feather icon -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
    <script>
        feather.replace();
    </script>
    <script src="{{asset('backEnd/assets/js/toastr.min.js')}}"></script>
    {!! Toastr::message() !!} @stack('script')
    <script>
        $(".quick_view").on("click", function() {
            var id = $(this).data("id");
            $("#loading").show();
            if (id) {
                $.ajax({
                    type: "GET",
                    data: {
                        id: id
                    },
                    url: "{{route('quickview')}}",
                    success: function(data) {
                        if (data) {
                            $("#custom-modal").html(data);
                            $("#custom-modal").show();
                            $("#loading").hide();
                            $("#page-overlay").show();
                        }
                    },
                });
            }
        });
    </script>
    <script>
// Updated search functionality to prevent interference with form submission
$(document).ready(function() {
    // Handle live search with delay to avoid too many requests
    let searchTimeout;
    
    // Desktop search input
    $(".search_keyword").on("input", function() {
        clearTimeout(searchTimeout);
        var keyword = $(this).val();
        
        // Only perform live search if keyword is at least 2 characters
        if(keyword.length >= 2) {
            searchTimeout = setTimeout(function() {
                $.ajax({
                    type: "GET",
                    data: { keyword: keyword },
                    url: "{{route('livesearch')}}",
                    success: function(products) {
                        if (products) {
                            $(".search_result").html(products);
                        } else {
                            $(".search_result").empty();
                        }
                    },
                });
            }, 500); // Delay by 500ms to avoid too many requests
        } else {
            $(".search_result").empty();
        }
    });
    
    // Mobile search input
    $(".msearch_keyword").on("input", function() {
        clearTimeout(searchTimeout);
        var keyword = $(this).val();
        
        if(keyword.length >= 2) {
            searchTimeout = setTimeout(function() {
                $.ajax({
                    type: "GET",
                    data: { keyword: keyword },
                    url: "{{route('livesearch')}}",
                    success: function(products) {
                        if (products) {
                            $("#loading").hide();
                            $(".search_result").html(products);
                        } else {
                            $(".search_result").empty();
                        }
                    },
                });
            }, 500);
        } else {
            $(".search_result").empty();
        }
    });
});
    </script>
    <!-- quick view end -->
    <!-- cart js start -->
    <script>
        $(".addcartbutton").on("click", function() {
            var id = $(this).data("id");
            var qty = 1;
            if (id) {
                $.ajax({
                    cache: "false",
                    type: "GET",
                    url: "{{url('add-to-cart')}}/" + id + "/" + qty,
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            toastr.success('Success', 'Product add to cart successfully');
                            cart_count();
                            mobile_cart();
                        }
                    },
                });
            }
        });
        $(".cart_store").on("click", function() {
            var id = $(this).data("id");
            var qty = $(this).parent().find("input").val();
            if (id) {
                $.ajax({
                    type: "GET",
                    data: {
                        id: id,
                        qty: qty ? qty : 1
                    },
                    url: "{{route('cart.store')}}",
                    success: function(data) {
                        if (data) {
                            toastr.success('Success', 'Product add to cart succfully');
                            cart_count();
                            mobile_cart();
                        }
                    },
                });
            }
        });

        $(".cart_remove").on("click", function() {
            var id = $(this).data("id");
            if (id) {
                $.ajax({
                    type: "GET",
                    data: {
                        id: id
                    },
                    url: "{{route('cart.remove')}}",
                    success: function(data) {
                        if (data) {
                            $(".cartlist").html(data);
                            cart_count();
                            mobile_cart();
                            cart_summary();
                        }
                    },
                });
            }
        });

        function cart_update_row(id, quantity) {
            return $.ajax({
                type: "POST",
                url: `{{ url('/cart-update') }}/${encodeURIComponent(id)}`,
                dataType: "json",
                data: {
                    _token: "{{ csrf_token() }}",
                    quantity: quantity
                }
            });
        }

        function get_cart_btn_qty($btn) {
            var raw = parseInt($btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty').find('input').first().val() || '1', 10);
            return Number.isFinite(raw) && raw > 0 ? raw : 1;
        }

        function set_cart_btn_qty($btn, qty) {
            $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty').find('input').first().val(String(qty));
        }

        function set_cart_btn_pending($btn, flag) {
            var $scope = $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty');
            $scope.find('.cart_increment, .cart_decrement').prop('disabled', !!flag);
            if (flag) {
                $scope.addClass('is-cart-pending');
            } else {
                $scope.removeClass('is-cart-pending');
            }
        }

        $(".cart_increment").on("click", function() {
            var $btn = $(this);
            var id = $(this).data("id");
            if (!id || $btn.prop('disabled')) return;
            var prevQty = get_cart_btn_qty($btn);
            var nextQty = prevQty + 1;
            set_cart_btn_qty($btn, nextQty); // optimistic update
            set_cart_btn_pending($btn, true);
            cart_update_row(id, nextQty).done(function(response) {
                if (response && response.status === 'error') {
                    set_cart_btn_qty($btn, prevQty); // rollback
                }
            }).fail(function() {
                set_cart_btn_qty($btn, prevQty); // rollback
            }).always(function() {
                set_cart_btn_pending($btn, false);
                cart_count();
                mobile_cart();
            });
        });

        $(".cart_decrement").on("click", function() {
            var $btn = $(this);
            var id = $(this).data("id");
            if (!id || $btn.prop('disabled')) return;
            var currentQty = get_cart_btn_qty($btn);
            if (currentQty <= 1) {
                $.ajax({
                    type: "GET",
                    data: { id: id },
                    url: "{{route('cart.remove')}}"
                }).always(function() {
                    cart_count();
                    mobile_cart();
                });
                return;
            }
            var prevQty = currentQty;
            var nextQty = currentQty - 1;
            set_cart_btn_qty($btn, nextQty); // optimistic update
            set_cart_btn_pending($btn, true);
            cart_update_row(id, nextQty).done(function(response) {
                if (response && response.status === 'error') {
                    set_cart_btn_qty($btn, prevQty); // rollback
                }
            }).fail(function() {
                set_cart_btn_qty($btn, prevQty); // rollback
            }).always(function() {
                set_cart_btn_pending($btn, false);
                cart_count();
                mobile_cart();
            });
        });

        function cart_count() {
            $.ajax({
                type: "GET",
                url: "{{route('cart.count')}}",
                success: function(data) {
                    var count = (data && typeof data === 'object' && data.cart_count !== undefined) ? parseInt(data.cart_count, 10) : parseInt(data, 10);
                    if (Number.isFinite(count) && count >= 0) {
                        $("#cart-qty").html(count);
                        $("#cart_count").text(count);
                        $(".cart-button-big-device p").text(count + " ITEMS");
                        $(".mobilecart-qty").html(count);
                    } else {
                        $("#cart-qty").empty();
                        $("#cart_count").text(0);
                        $(".cart-button-big-device p").text("0 ITEMS");
                        $(".mobilecart-qty").html(0);
                    }
                },
            });
        }

        function mobile_cart() {
            $.ajax({
                type: "GET",
                url: "{{route('mobile.cart.count')}}",
                success: function(data) {
                    var count = (data && typeof data === 'object' && data.cart_count !== undefined) ? parseInt(data.cart_count, 10) : parseInt(data, 10);
                    if (Number.isFinite(count) && count >= 0) {
                        $(".mobilecart-qty").html(count);
                        $("#cart_count").text(count);
                        $("#cart-qty").html(count);
                        $(".cart-button-big-device p").text(count + " ITEMS");
                    } else {
                        $(".mobilecart-qty").empty();
                        $("#cart_count").text(0);
                        $("#cart-qty").html(0);
                        $(".cart-button-big-device p").text("0 ITEMS");
                    }
                },
            });
        }

        function cart_summary() {
            $.ajax({
                type: "GET",
                url: "{{route('shipping.charge')}}",
                dataType: "html",
                success: function(response) {
                    $(".cart-summary").html(response);
                },
            });
        }
    </script>
    <!-- cart js end -->

    <!-- search js start -->
    <script></script>
    <script></script>
    <script>
        $(".district").on("change", function() {
            var id = $(this).val();
            $.ajax({
                type: "GET",
                data: {
                    id: id
                },
                url: "{{route('districts')}}",
                success: function(res) {
                    if (res) {
                        $(".area").empty();
                        $(".area").append('<option value="">Select..</option>');
                        $.each(res, function(key, value) {
                            $(".area").append('<option value="' + key + '" >' + value + "</option>");
                        });
                    } else {
                        $(".area").empty();
                    }
                },
            });
        });
    </script>
    <script>
        $(".toggle").on("click", function() {
            $("#page-overlay").show();
            $(".mobile-menu").addClass("active");
        });

        $("#page-overlay").on("click", function() {
            $("#page-overlay").hide();
            $(".mobile-menu").removeClass("active");
            $(".feature-products").removeClass("active");
        });

        $(".mobile-menu-close").on("click", function() {
            $("#page-overlay").hide();
            $(".mobile-menu").removeClass("active");
        });

        $(".mobile-filter-toggle").on("click", function() {
            $("#page-overlay").show();
            $(".feature-products").addClass("active");
        });
    </script>
    <script>
        $(document).ready(function() {
            $(".parent-category").each(function() {
                const menuCatToggle = $(this).find(".menu-category-toggle");
                const secondNav = $(this).find(".second-nav");

                menuCatToggle.on("click", function() {
                    menuCatToggle.toggleClass("active");
                    secondNav.slideToggle("fast");
                    $(this).closest(".parent-category").toggleClass("active");
                });
            });
            $(".parent-subcategory").each(function() {
                const menuSubcatToggle = $(this).find(".menu-subcategory-toggle");
                const thirdNav = $(this).find(".third-nav");

                menuSubcatToggle.on("click", function() {
                    menuSubcatToggle.toggleClass("active");
                    thirdNav.slideToggle("fast");
                    $(this).closest(".parent-subcategory").toggleClass("active");
                });
            });
        });
    </script>

    <script>
        var menuNode = document.querySelector("#menu");
        if (menuNode && window.MmenuLight) {
            var menu = new MmenuLight(menuNode, "all");

            var navigator = menu.navigation({
                selectedClass: "Selected",
                slidingSubmenus: true,
                // theme: 'dark',
                title: "ক্যাটাগরি",
            });

            var drawer = menu.offcanvas({
                // position: 'left'
            });

            var menuOpenLink = document.querySelector('a[href="#menu"]');
            if (menuOpenLink) {
                menuOpenLink.addEventListener("click", (evnt) => {
                    evnt.preventDefault();
                    drawer.open();
                });
            }
        }
    </script>

    <script>
        // document.addEventListener("DOMContentLoaded", function () {
        //     window.addEventListener("scroll", function () {
        //         if (window.scrollY > 200) {
        //             document.getElementById("navbar_top").classList.add("fixed-top");
        //         } else {
        //             document.getElementById("navbar_top").classList.remove("fixed-top");
        //             document.body.style.paddingTop = "0";
        //         }
        //     });
        // });
        /*=== Main Menu Fixed === */
        // document.addEventListener("DOMContentLoaded", function () {
        //     window.addEventListener("scroll", function () {
        //         if (window.scrollY > 0) {
        //             document.getElementById("m_navbar_top").classList.add("fixed-top");
        //             // add padding top to show content behind navbar
        //             navbar_height = document.querySelector(".navbar").offsetHeight;
        //             document.body.style.paddingTop = navbar_height + "px";
        //         } else {
        //             document.getElementById("m_navbar_top").classList.remove("fixed-top");
        //             // remove padding top from body
        //             document.body.style.paddingTop = "0";
        //         }
        //     });
        // });
        /*=== Main Menu Fixed === */

        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
                $(".scrolltop:hidden").stop(true, true).fadeIn();
            } else {
                $(".scrolltop").stop(true, true).fadeOut();
            }
        });
        $(function() {
            $(".scroll").click(function() {
                $("html,body").animate({
                    scrollTop: $(".gotop").offset().top
                }, "1000");
                return false;
            });
        });
    </script>
    <script>
        $(".filter_btn").click(function() {
            $(".filter_sidebar").addClass('active');
            $("body").css("overflow-y", "hidden");
        })
        $(".filter_close").click(function() {
            $(".filter_sidebar").removeClass('active');
            $("body").css("overflow-y", "auto");
        })
    </script>

    <!-- Submenu JavaScript -->
    <script>
        $(document).ready(function() {
            // Show submenu on hover
            $('.modern-nav-dropdown').hover(
                function() {
                    $(this).find('.modern-submenu').fadeIn(200);
                },
                function() {
                    $(this).find('.modern-submenu').fadeOut(200);
                }
            );

            // Show child menu on hover
            $('.modern-submenu-item').hover(
                function() {
                    $(this).find('.modern-childmenu').fadeIn(200);
                },
                function() {
                    $(this).find('.modern-childmenu').fadeOut(200);
                }
            );

            // Add hover effects to menu items
            $('.modern-submenu-link, .modern-childmenu-link').hover(
                function() {
                    $(this).css('background-color', '#f4c430');
                    $(this).css('color', '#2c2c2c');
                },
                function() {
                    $(this).css('background-color', 'transparent');
                    $(this).css('color', 'white');
                }
            );

            // Add hover effects to header icons
            $('.modern-user-actions').hover(
                function() {
                    $(this).css('transform', 'translateY(-2px)');
                    $(this).css('color', '#d08501 !important');
                    $(this).find('i').css('color', '#c19660');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                    $(this).css('color', '#333');
                    $(this).find('i').css('color', '#d08501 !important');
                }
            );
        });
    </script>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-K29C9BKJ"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Category filter functionality
        document.querySelectorAll('.btn-group .btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                // Here you would typically filter products based on category
                console.log('Filter by:', this.textContent);
            });
        });

        // Search functionality
        const svSearchBtn = document.querySelector('.sv-search-btn');
        if (svSearchBtn) {
            svSearchBtn.addEventListener('click', function() {
                const svSearchInput = document.querySelector('.sv-search-input');
                const searchTerm = svSearchInput ? svSearchInput.value : '';
                console.log('Search for:', searchTerm);
                // Implement search logic here
            });
        }

        // Add to cart functionality
        document.querySelectorAll('.sv-order-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Add smooth animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);

                console.log('Product added to cart');
                // Implement add to cart logic here
            });
        });

        // Newsletter subscription
        const svCtaBtn = document.querySelector('.sv-cta-btn');
        if (svCtaBtn) {
            svCtaBtn.addEventListener('click', function() {
                const emailInput = document.querySelector('input[type="email"]');
                const email = emailInput ? emailInput.value : '';
                if (email) {
                    alert('Thank you for subscribing!');
                } else {
                    alert('Please enter your email address');
                }
            });
        }

        // Ensure search forms submit properly
        document.querySelectorAll('.modern-search-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const searchInput = this.querySelector('input[name="keyword"]');
                if (searchInput && searchInput.value.trim() === '') {
                    e.preventDefault();
                    return false;
                }
                // Allow form to submit normally
                return true;
            });
        });
    </script>
</body>

</html>
