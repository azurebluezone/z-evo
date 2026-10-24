<!doctype html>
<html lang="{{ app()->getLocale() }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <!-- Accessibility Enhancements -->
    <meta name="theme-color" content="#f3532b">
    <meta name="msapplication-TileColor" content="#f3532b">
    <meta name="description" content="@yield('meta_description', 'GS Corporation BD - Your trusted online shopping destination with wide range of products')">
    <meta name="keywords" content="online shopping, ecommerce, products, fashion, electronics">
    <link rel="canonical" href="{{ url()->current() }}">
    
    {{-- Resource Hints for Performance --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="preload" as="font" href="{{ asset('assets/global/fonts/la-solid-900.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/global/fonts/la-regular-400.woff2') }}" type="font/woff2" crossorigin>
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//code.jquery.com">
    
    <!-- Skip Navigation Link -->
    <style>
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            z-index: 1000;
            text-decoration: none;
            border-radius: 4px;
            transition: top 0.2s;
        }
        .skip-link:focus {
            top: 6px;
        }
        /* Enhanced focus indicators */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid #2196f3;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.3);
        }
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            a, button, input, select, textarea {
                border: 2px solid #000;
            }
            .skip-link {
                background: #fff;
                color: #000;
                border: 2px solid #000;
            }
        }
        /* Focus visible enhancement for interactive elements */
        .btn:focus, .button:focus {
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.4);
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
        }
        /* Ensure focusable elements are keyboard accessible */
        [tabindex]:focus {
            outline: 2px solid #2196f3;
            outline-offset: 2px;
        }
        
        /* Color contrast improvements for WCAG compliance */
        .text-muted {
            color: #6c757d !important; /* Ensures 4.5:1 contrast on white */
        }
        .text-secondary {
            color: #6c757d !important; /* Ensures 4.5:1 contrast on white */
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        /* Ensure form placeholders have sufficient contrast */
        ::placeholder {
            color: #6c757d;
            opacity: 1;
        }
        :-ms-input-placeholder {
            color: #6c757d;
        }
        ::-ms-input-placeholder {
            color: #6c757d;
        }
        /* Dark mode support for better contrast */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212;
                color: #e0e0e0;
            }
            .bg-white {
                background-color: #1e1e1e !important;
            }
            .text-dark {
                color: #e0e0e0 !important;
            }
        }
    </style>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
        $appName = $generalsetting->name ?? 'GS Corporation BD';
        $pageTitleRaw = trim($__env->yieldContent('title', ''));
        $primaryColorHex = ltrim((string) ($generalsetting->primary_color ?? 'f3532b'), '#');
        $primaryColorHex = preg_match('~^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$~', $primaryColorHex) ? strtolower($primaryColorHex) : 'f3532b';
        $secondaryColorHex = ltrim((string) ($generalsetting->secondary_color ?? '111827'), '#');
        $secondaryColorHex = preg_match('~^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$~', $secondaryColorHex) ? strtolower($secondaryColorHex) : '111827';
        $faviconPath = $generalsetting->favicon ?? 'assets/images/logo_icon/favicon.png';
        $faviconPath = preg_replace('~^public/~', '', (string) $faviconPath);
        if (!empty($faviconPath) && !\Illuminate\Support\Facades\File::exists(public_path($faviconPath))) {
            $faviconPath = 'assets/images/logo_icon/favicon.png';
        }
    @endphp

    <title>{{ $pageTitleRaw !== '' ? ($pageTitleRaw . ' - ' . $appName) : $appName }}</title>
    <meta name="theme-color" content="#{{ $primaryColorHex }}">
    @include('frontend.layouts.partials.seo-meta')
    @stack('seo')
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo_icon/logo.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $appName }} - Home">
    <meta itemprop="name" content="{{ $appName }} - Home">
    <link type="image/x-icon" href="{{ asset('assets/images/logo_icon/favicon.png') }}" rel="shortcut icon">
    
    {{-- CSS Stylesheets --}}
    {{-- Critical above-the-fold CSS loaded synchronously --}}
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/color.php?color=' . $primaryColorHex) }}">
    
    {{-- Non-critical CSS loaded asynchronously --}}
    <link rel="preload" href="{{ asset('assets/templates/basic/css/banner.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/owl.carousel.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/product-details.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/xzoom/xzoom.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/xzoom/magnific-popup.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/global/css/select2.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/service-section.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/custom.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/templates/basic/css/order-track.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <noscript>
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/banner.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/owl.carousel.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/product-details.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/xzoom/xzoom.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/xzoom/magnific-popup.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/service-section.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/custom.css') }}">
    </noscript>
    
    {{-- Performance Optimizations --}}
     <style>
        :root {
            --sv-primary: #{{ $primaryColorHex }};
            --sv-secondary: #{{ $secondaryColorHex }};
        }
        .header-bottom {
            background-color: #{{ $primaryColorHex }}
        }

        .menu li a.active {
            background-color: #ffffff33;
        }
    </style>
    <style>
        .form--check .form-check-input {
            margin-top: 1px !important;
        }
    </style>
    <style>
        .ratings-area {
            display: none !important;
        }

        .product-details .ratings-area {
            display: block !important;
        }

        .product-card-order-now {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            text-align: center;
        }

        .product-card-qty {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }

        .product-card-qty.is-open {
            display: flex;
        }

        .product-card-qty button {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .product-card-qty .product-card-qty-value {
            min-width: 28px;
            text-align: center;
            font-weight: 700;
        }

        .product-card.is-cart-pending {
            opacity: 0.75;
        }

        .product-card.is-cart-pending .product-card-qty button {
            opacity: 0.6;
        }
    </style>
    <style>
        .form--check .form-check-input {
            margin-top: 1px !important;
        }
    </style>
    {{-- Preload critical resources --}}
    
    {{-- Lazy Load Script --}}
    <script src="{{ asset('assets/templates/basic/js/lazyload.js') }}"></script>
    
    @include('frontend.partials.event-layer-head')

    @stack('css')
    @stack('styles')
</head>

<body>
    <!-- Skip to main content link -->
    
    @include('frontend.partials.event-layer-body')
    <div class="body-overlay" id="body-overlay"></div>
    <div class="overlay"></div>

    <!-- Header with landmark region -->
    <header role="banner">
        @include('frontend.layouts.partials.header')
    </header>

    <main id="main-content" role="main">
        @yield('content')
    </main>

    @include('frontend.partials.login-modal')
    @include('frontend.partials.cart-sidebar')
    @include('frontend.partials.wishlist-sidebar')

    {{--
        The partial already opens its own <footer>, so wrapping it in a
        second one produced <footer><footer>...</footer></footer> - invalid
        markup, and a contentinfo landmark nested inside another one. The
        role moved onto the partial's own tag (see partials/footer.blade.php)
        and the wrapper is gone.
    --}}
    @include('frontend.layouts.partials.footer')

    @include('frontend.layouts.partials.mobile-menu')

    <div class="d-lg-none" style="position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030; background: #fff; border-top: 1px solid rgba(0,0,0,0.1); padding: 8px 6px;">
        <div class="d-flex justify-content-around align-items-center">
            <a href="{{ route('home') }}" class="text-decoration-none text-dark" style="min-width: 64px; text-align: center;">
                <div style="font-size: 20px; line-height: 1;"><i class="las la-home"></i></div>
                <div style="font-size: 11px; line-height: 1.2;">Home</div>
            </a>
            <a href="{{ route('cart.index') }}" class="text-decoration-none text-dark" style="min-width: 64px; text-align: center; position: relative;">
                <div style="font-size: 20px; line-height: 1; position: relative; display: inline-block;">
                    <i class="las la-shopping-cart"></i>
                    <span class="cart-count" style="position: absolute; top: -6px; right: -10px; background: #ff4757; color: #fff; border-radius: 999px; min-width: 18px; height: 18px; padding: 0 5px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">{{ session()->get('cart_count', 0) }}</span>
                </div>
                <div style="font-size: 11px; line-height: 1.2;">Cart</div>
            </a>

            @if(Auth::guard('customer')->check())
                @php
                    $customerUnreadBottom = (int) Auth::guard('customer')->user()->unreadNotifications()->count();
                @endphp
                <a href="{{ route('customer.notifications.index') }}" class="text-decoration-none text-dark" style="min-width: 64px; text-align: center; position: relative;">
                    <div style="font-size: 20px; line-height: 1; position: relative; display: inline-block;">
                        <i class="las la-bell"></i>
                        @if($customerUnreadBottom > 0)
                            <span style="position: absolute; top: -6px; right: -10px; background: #ff4757; color: #fff; border-radius: 999px; min-width: 18px; height: 18px; padding: 0 5px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">{{ $customerUnreadBottom }}</span>
                        @endif
                    </div>
                    <div style="font-size: 11px; line-height: 1.2;">Notify</div>
                </a>
                <a href="{{ route('customer.account') }}" class="text-decoration-none text-dark" style="min-width: 64px; text-align: center;">
                    <div style="font-size: 20px; line-height: 1;"><i class="las la-user"></i></div>
                    <div style="font-size: 11px; line-height: 1.2;">Account</div>
                </a>
            @else
                <a href="{{ route('customer.login') }}" class="text-decoration-none text-dark" style="min-width: 64px; text-align: center;">
                    <div style="font-size: 20px; line-height: 1;"><i class="las la-user"></i></div>
                    <div style="font-size: 11px; line-height: 1.2;">Login</div>
                </a>
            @endif
        </div>
    </div>

    {{-- Performance Monitoring --}}
    <script>
        @php
            $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
            $frontendEmptyImage = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
            $frontendEmptyImage = preg_replace('~^public/~', '', (string) $frontendEmptyImage);
            $frontendEmptyImage = ltrim($frontendEmptyImage, '/');
            if (empty($frontendEmptyImage) || !\Illuminate\Support\Facades\File::exists(public_path($frontendEmptyImage))) {
                $frontendEmptyImage = 'assets/images/empty.png';
            }
        @endphp
        const FRONTEND_EMPTY_IMAGE = @json(asset($frontendEmptyImage));

        (function () {
            function shouldForceFallback(src) {
                if (!src) return true;
                const s = String(src).toLowerCase();
                return s.includes('assets/images/empty.png') || s.includes('public/assets/images/empty.png') || s.includes('placeholder.png') || s.includes('no-image.png');
            }

            function applyFallback(img) {
                if (!img) return;
                if (img.dataset && img.dataset.fallbackApplied === '1') return;
                if (img.dataset) img.dataset.fallbackApplied = '1';
                img.src = FRONTEND_EMPTY_IMAGE;
                if (img.dataset && img.dataset.src) {
                    img.dataset.src = FRONTEND_EMPTY_IMAGE;
                }
            }

            function hasLazyLoadRealSource(img) {
                if (!img) return false;
                // If lazyload plugin is expected to swap data-src into src, do not clobber it.
                if (!img.classList || !img.classList.contains('lazyload')) return false;
                const ds = img.getAttribute('data-src') || (img.dataset ? img.dataset.src : '') || '';
                const v = String(ds || '').trim();
                if (!v) return false;
                // If data-src is already a placeholder/no-image, it's not a real source.
                const low = v.toLowerCase();
                if (low.includes('placeholder.png') || low.includes('no-image.png') || low.includes('assets/images/empty.png')) {
                    return false;
                }
                return true;
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('img').forEach(function (img) {
                    if (!img) return;
                    const currentSrc = img.getAttribute('src') || '';
                    if (shouldForceFallback(currentSrc) && !hasLazyLoadRealSource(img)) {
                        applyFallback(img);
                    }
                    img.addEventListener('error', function () {
                        if (!hasLazyLoadRealSource(img)) {
                            applyFallback(img);
                        }
                    });
                });
            });
        })();
        // Core Web Vitals measurement
        if ('PerformanceObserver' in window) {
            // Measure LCP
            const lcpObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
            });
            lcpObserver.observe({entryTypes: ['largest-contentful-paint']});
            
            // Measure FID
            const fidObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach(entry => {
                    if (entry.name === 'first-input') {
                    }
                });
            });
            fidObserver.observe({entryTypes: ['first-input']});
            
            // Measure CLS
            let clsValue = 0;
            const clsObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach(entry => {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                });
            });
            clsObserver.observe({entryTypes: ['layout-shift']});
        }
    </script>
    
    {{-- Critical JavaScript loaded first --}}
    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    
    {{-- Defer non-critical JavaScript --}}
    <script src="{{ asset('assets/templates/basic/js/jquery.validate.js') }}" defer></script>
    <script src="{{ asset('assets/templates/basic/js/owl.carousel.min.js') }}" defer></script>
    <script src="{{ asset('assets/templates/basic/js/owl-carousel.js') }}" defer></script>
    <script src="{{ asset('assets/templates/basic/js/xzoom/xzoom.min.js') }}" defer></script>
    <script src="{{ asset('assets/templates/basic/js/xzoom/magnific-popup.js') }}" defer></script>
    <script src="{{ asset('assets/templates/basic/js/xzoom/setup.js') }}" defer></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}" defer></script>
    <script src="{{ asset('assets/templates/basic/js/main.js') }}" defer></script>
    
    {{-- Deferred CSS for non-critical features --}}
    <link rel="preload" href="{{ asset('assets/global/css/iziToast.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('assets/global/css/iziToast_custom.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('assets/global/css/iziToast.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/global/css/iziToast_custom.css') }}">
    </noscript>
    <script src="{{ asset('assets/global/js/iziToast.min.js') }}"></script>
    {{-- Facebook Pixel is now loaded dynamically via event-layer-head.blade.php --}}
    <script>
        "use strict";
        
        // Accessibility Enhancement Script
        document.addEventListener('DOMContentLoaded', function() {
            // Keyboard navigation enhancements
            const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
            
            // Screen Reader Compatibility Enhancements
            function enhanceScreenReaderSupport() {
                // Add live regions for dynamic content updates
                if (!document.getElementById('sr-announcements')) {
                    const liveRegion = document.createElement('div');
                    liveRegion.id = 'sr-announcements';
                    liveRegion.setAttribute('aria-live', 'polite');
                    liveRegion.setAttribute('aria-atomic', 'true');
                    liveRegion.className = 'sr-only';
                    liveRegion.style.position = 'absolute';
                    liveRegion.style.left = '-10000px';
                    document.body.appendChild(liveRegion);
                }
                
                // Add descriptive text for icon-only buttons
                document.querySelectorAll('button i[class*="la-"]').forEach(icon => {
                    const button = icon.closest('button');
                    if (button && !button.getAttribute('aria-label')) {
                        const iconClasses = icon.className;
                        let description = '';
                        
                        if (iconClasses.includes('la-search')) description = 'Search';
                        else if (iconClasses.includes('la-user')) description = 'Account';
                        else if (iconClasses.includes('la-shopping-bag')) description = 'Cart';
                        else if (iconClasses.includes('la-heart')) description = 'Wishlist';
                        else if (iconClasses.includes('la-exchange-alt')) description = 'Compare';
                        else if (iconClasses.includes('la-times')) description = 'Close';
                        else if (iconClasses.includes('la-angle-right')) description = 'Expand menu';
                        
                        if (description) {
                            button.setAttribute('aria-label', description);
                        }
                    }
                });
                
                // Ensure form fields have proper labeling
                document.querySelectorAll('input, select, textarea').forEach(field => {
                    const formGroup = field.closest('.form-group');
                    const hasLabel = formGroup ? !!formGroup.querySelector('label') : false;
                    if (!field.getAttribute('aria-label') && !hasLabel) {
                        const placeholder = field.getAttribute('placeholder');
                        if (placeholder) {
                            field.setAttribute('aria-label', placeholder);
                        }
                    }
                });
                
                // Add table headers for better screen reader navigation
                document.querySelectorAll('table').forEach(table => {
                    if (!table.getAttribute('role')) {
                        table.setAttribute('role', 'table');
                    }
                    if (!table.querySelector('thead')) {
                        const firstRow = table.querySelector('tr');
                        if (firstRow) {
                            const thead = document.createElement('thead');
                            thead.appendChild(firstRow.cloneNode(true));
                            table.insertBefore(thead, table.firstChild);
                            firstRow.remove();
                        }
                    }
                });
            }
            
            // Add ARIA attributes to dynamic elements
            function enhanceAccessibility() {
                // Add role and aria-label to interactive elements without them
                document.querySelectorAll('button:not([aria-label]):not([title])').forEach(btn => {
                    if (!btn.getAttribute('aria-label') && btn.textContent.trim()) {
                        btn.setAttribute('aria-label', btn.textContent.trim());
                    }
                });
                
                // Ensure form controls have labels
                document.querySelectorAll('input:not([aria-label]):not([title])').forEach(input => {
                    if (input.placeholder && !input.getAttribute('aria-label')) {
                        input.setAttribute('aria-label', input.placeholder);
                    }
                });
                
                // Add landmarks to main sections
                if (!document.querySelector('header[role="banner"]')) {
                    const header = document.querySelector('.header-area');
                    if (header) header.setAttribute('role', 'banner');
                }
                
                if (!document.querySelector('nav[role="navigation"]')) {
                    const navs = document.querySelectorAll('.menu, .navbar');
                    navs.forEach(nav => nav.setAttribute('role', 'navigation'));
                }
                
                if (!document.querySelector('main[role="main"]')) {
                    const main = document.querySelector('main');
                    if (main) main.setAttribute('role', 'main');
                }
                
                if (!document.querySelector('footer[role="contentinfo"]')) {
                    const footer = document.querySelector('footer');
                    if (footer) footer.setAttribute('role', 'contentinfo');
                }
            }
            
            // Enhanced keyboard navigation
            document.addEventListener('keydown', function(e) {
                // Skip link activation with Enter key
                if (e.target.classList.contains('skip-link') && e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('main-content').focus();
                }
                
                // Close modals with Escape key
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(modal => {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    });
                }
            });
            
            // Initialize accessibility enhancements
            enhanceAccessibility();
            enhanceScreenReaderSupport();
            
            // Re-run when content changes
            const observer = new MutationObserver(function() {
                enhanceAccessibility();
                enhanceScreenReaderSupport();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });
        
        const colors = {
            success: '#28c76f',
            error: '#eb2222',
            warning: '#ff9f43',
            info: '#1e9ff2',
        }

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-exclamation-circle',
        }

        const notifications = [
            @if(session()->has('success'))
                ['success', @json(session('success'))],
            @endif
            @if(session()->has('error'))
                ['error', @json(session('error'))],
            @endif
        ];
        const errors = @json($errors->all());


        const triggerToaster = (status, message) => {
            if (typeof iziToast === 'undefined') {
                return;
            }
            iziToast[status]({
                title: status.charAt(0).toUpperCase() + status.slice(1),
                message: message,
                position: "topRight",
                backgroundColor: '#fff',
                icon: icons[status],
                iconColor: colors[status],
                progressBarColor: colors[status],
                titleSize: '1rem',
                messageSize: '1rem',
                titleColor: '#474747',
                messageColor: '#a2a2a2',
                transitionIn: 'bounceInLeft',
                transitionOut: 'fadeOutRight'
            });
        }

        const triggerCenteredToast = (status, message) => {
            if (typeof iziToast === 'undefined') {
                return;
            }
            const rootStyles = getComputedStyle(document.documentElement);
            const primary = (rootStyles.getPropertyValue('--sv-primary') || '').trim() || '#f3532b';
            const secondary = (rootStyles.getPropertyValue('--sv-secondary') || '').trim() || '#111827';

            iziToast[status]({
                title: '',
                message: message,
                position: "center",
                timeout: 1600,
                close: false,
                closeOnClick: true,
                displayMode: 1,
                backgroundColor: '#fff',
                progressBarColor: primary,
                messageSize: '1.05rem',
                messageColor: secondary,
                transitionIn: 'fadeInUp',
                transitionOut: 'fadeOutDown'
            });
        }

        if (notifications.length) {
            notifications.forEach(element => {
                triggerToaster(element[0], element[1]);
            });
        }

        if (errors.length) {
            errors.forEach(error => {
                triggerToaster('error', error);
            });
        }

        function notify(status, message) {
            if (typeof message == 'string') {
                triggerToaster(status, message);
            } else {
                $.each(message, (i, val) => triggerToaster(status, val));
            }
        }

        function notifyCart(message) {
            triggerCenteredToast('success', message);
        }
    </script>
    
    <script>
        (function($) {
            "use strict";
            $(window).on('load', function() {
                if (!$.fn || !$.fn.owlCarousel) {
                    return;
                }
                const $bannerSlider = $(".banner-slider");
                if (!$bannerSlider.length) {
                    return;
                }

                const owl = $bannerSlider.owlCarousel({
                    items: 1,
                    loop: true,
                    autoplay: true,
                    autoplayTimeout: 5000,
                    autoplayHoverPause: false,
                    nav: false,
                    dots: false,
                    animateOut: 'fadeOut'
                });

                const stopAutoplay = () => {
                    try {
                        owl.trigger('stop.owl.autoplay');
                    } catch (e) {}
                };

                const playAutoplay = () => {
                    try {
                        owl.trigger('play.owl.autoplay', [5000]);
                    } catch (e) {}
                };

                const handleSlideMedia = () => {
                    const $activeItem = $bannerSlider.find('.owl-item.active');
                    if (!$activeItem.length) {
                        playAutoplay();
                        return;
                    }

                    const $video = $activeItem.find('video').first();
                    $bannerSlider.find('video').each(function() {
                        try {
                            this.pause();
                        } catch (e) {}
                    });

                    if ($video.length) {
                        stopAutoplay();

                        const videoEl = $video.get(0);
                        try {
                            videoEl.currentTime = 0;
                        } catch (e) {}

                        $video.off('.banner');
                        $video.on('ended.banner', function() {
                            try {
                                owl.trigger('next.owl.carousel');
                            } catch (e) {}
                            playAutoplay();
                        });

                        try {
                            const playPromise = videoEl.play();
                            if (playPromise && typeof playPromise.catch === 'function') {
                                playPromise.catch(function() {
                                    playAutoplay();
                                });
                            }
                        } catch (e) {
                            playAutoplay();
                        }
                        return;
                    }

                    playAutoplay();
                };

                handleSlideMedia();
                $bannerSlider.on('changed.owl.carousel', function() {
                    handleSlideMedia();
                });
            });
        })(jQuery);
    </script>
    
    <script>
        (function($) {
            "use strict";
            const categoriesToShow = {
                "768": {
                    "items": 5
                },
                "992": {
                    "items": 5
                },
                "1199": {
                    "items": 5
                }
            };

            const viewItems = {
                0: {
                    items: 3,
                    margin: 12,
                },
                425: {
                    items: 4,
                    margin: 12,
                },
                575: {
                    items: 4,
                    margin: 12,
                },
                ...categoriesToShow
            };

            $(window).on('load', function() {
                if (!$.fn || !$.fn.owlCarousel) {
                    return;
                }
                $(".featured-category-slider").owlCarousel({
                    margin: 16,
                    responsiveClass: true,
                    items: 3,
                    nav: false,
                    dots: false,
                    autoplay: true,
                    autoplayTimeout: 4000,
                    loop: true,
                    lazyLoad: true,
                    responsive: viewItems,
                });
            });
        })(jQuery);
    </script>
    
    <script>
        (function($) {
            "use strict";
            $(document).on('click', '.login-trigger', function() {
                $(".sidebar-close-btn").trigger('click');
            });

            $('.policy').on('click', function() {
                $.get("cookie/accept", function(response) {
                    $('.cookies-card').addClass('d-none');
                });
            });
        })(jQuery);
    </script>

    <script>
        'use strict';
        const cartCountElement = $('.cartItemCount');
        const quickViewModal = $('#quickView');
        const checkoutUrl = `{{ route('customer.checkout') }}`;
        const buyNowUrl = `{{ route('cart.store.buy') }}`;
        const csrfToken = `{{ csrf_token() }}`;

        const getCompareWishlistCartData = () => $.get("/compare-wishlist-cart-date");
        const getCartCount = () => getCompareWishlistCartData().then((response) => response?.data?.cart_products ?? 0);
        const getCartSubtotal = () => getCompareWishlistCartData().then((response) => response?.data?.cart_subtotal ?? 0);
        const getCartData = () => $.get("/cart/sidebar-content");

        const absoluteValue = (amount) => {
            return Math.abs(parseFloat(amount).toFixed(2));
        }

        function setCartCountTargets(count = 0) {
            let safeCount = parseInt(count ?? 0, 10);
            if (!Number.isFinite(safeCount) || safeCount < 0) {
                safeCount = 0;
            }

            localStorage.setItem('cart_count', String(safeCount));

            const $targets = $('.cart-count, .cartItemCount, .modern-cart-count, .sh-cart-count-badge');
            $targets.text(safeCount);
            if (safeCount > 0) {
                $targets.removeClass('d-none');
            } else {
                $targets.addClass('d-none');
            }
        }

        const setCartCount = (count = null) => {

            if (count === null) {
                getCartCount().then((response) => setCartCount(response));
                return;
            }

            setCartCountTargets(count);
        }

        const setCartSubtotal = (amount = null) => {
            if (amount === null) {
                getCartSubtotal().then((response) => updateSubtotal(response));
            }
            amount = absoluteValue(amount ?? 0);
            updateSubtotal(amount);
        }

        const updateSubtotal = function(amount) {
            $('.cartSubtotal').text(amount);
        }

        const setPartialCart = (cartData) => {
            if (!cartData) {
                getCartData().then((response) => setPartialCart(response));
            }

            $('.cart--products').html(cartData);
            lazyload();
        }

        const getCompareData = () => {
            $.get("compare-products-count").done((response) => {
                if (response.total) {
                    $('.compare-count').text(response.total);
                    $('.compare-count').removeClass('d-none');
                } else {
                    $('.compare-count').addClass('d-none');
                }
            });
        }

        const getWishlistData = () => {
            $.get("wishlist-short").done((response) => {

                $('.wish--products').html(response);
                lazyload();
            });
        }

        const getWishlistTotal = () => {
            $.get("wishlist-count").done((response) => {
                if (response > 0) {
                    $('.wishlist-count').text(response).removeClass('d-none');
                } else {
                    $('.wishlist-count').addClass('d-none');
                }
            });
        }

        const removeFromCartHandler = function() {
            const couponAmount = Number($('#couponAmount').text());

            if (couponAmount > 0) {
                notify('info', 'Applied coupon has been removed');
                $('.couponContent').hide();
                $('#couponAmount').text(0);
            }

            const parent = $(this).parents('.cartItem');
            const subTotal = Number($('.cartSubtotal').first().text());
            const thisPrice = Number(parent.find('.totalPrice').text());

            const sidebarCart = $(this).parents('.cart--products');

            const data = {
                _token: `{{ csrf_token() }}`
            };

            const action = `/remove-from-cart/${$(this).data('id')}`;

            $.post(action, data,
                function(response) {
                    if (response.status == 'success') {
                        parent.remove();

                        if (thisPrice) {
                            $('.cartSubtotal').text((subTotal - thisPrice).toFixed(2));
                            $('#finalTotal').text((subTotal - thisPrice).toFixed(2));
                        }

                        if ($(".cartItem").length == 0) {
                            if (sidebarCart.length == 0) {
                                $(".cart-container").html(`
                            <div class="single-product-item no_data empty-cart__page">
                                <div class="no_data-thumb text-center mb-4">
                                    <img src="public/assets/images/empty_cart.png" alt="Empty Cart">
                                </div>
                                <h6>Your cart is empty</h6>

                                <a href="public/home" class="btn btn-outline--light">Browse Products</a>
                            </div>
                            `);

                                $('.cart-next-step').remove();
                            } else {
                                sidebarCart.html(`
                            <div class="single-product-item no_data">
                                <div class="no_data-thumb">
                                    <img src="public/assets/images/empty_cart.png" alt="Empty Cart">
                                </div>
                                <h6>Your cart is empty</h6>
                            </div>
                            `);
                            }
                        }

                        setCartCount(response.cartItemCount);
                    } else {
                        notify(response.status, response.message);
                    }
                }
            );
        }


        const updateCart = (quantity, cartItem) => {
            let couponAmount = Number($('#couponAmount').text());
            if (couponAmount > 0) {
                notify('info', 'Applied coupon has been removed');
                $('#couponAmount').text(0)
                $('.couponContent').hide();
            }


            let unitPrice = Number(cartItem.data().price);

            const data = {
                _token: `{{ csrf_token() }}`,
                quantity: quantity
            }

            $.post(`/cart-update/${cartItem.data().id}`, data,
                function(response) {
                    if (response.status === 'error') {
                        cartItem.find('[name=quantity]').val(response.quantity);
                        notify('error', response.message);
                    } else {

                        cartItem.find('.totalPrice').text(absoluteValue(quantity * unitPrice));

                        let priceArray = $('.cartItem .totalPrice').map((i, element) => $(element).text()).get();
                        var subtotal = priceArray.reduce((acc, element) => acc + Number(element), 0);


                        $('.cartSubtotal').text(absoluteValue(subtotal));

                        var finalAmount = subtotal - absoluteValue($('#couponAmount').text());

                        $('#finalTotal').text(absoluteValue(finalAmount));

                        if (quantity > 1) {
                            cartItem.find('.qtyButton.minus').removeAttr('disabled');
                        } else {
                            cartItem.find('.qtyButton.minus').attr('disabled', true);
                        }
                    }
                }
            );
        }

        const quantityButtonClickHandler = function() {
            const parent = $(this).parent();
            let qty = parent.find("input").val() * 1;
            qty = $(this).hasClass('plus') ? ++qty : --qty;
            qty = qty < 1 ? 1 : qty;

            parent.find("input").val(qty);

            if ($(this).parent().data('update') != 'no') {
                updateCart(qty, $(this).parents('.cartItem'));
            }

            if (qty > 1) {
                parent.find(".minus").removeAttr('disabled');
            } else {
                parent.find(".minus").attr('disabled', true);
            }
        }

        const applyCouponClickHandler = () => {
            var code = $('input[name=coupon_code]').val();
            var subtotal = absoluteValue($('.cartSubtotal').first().text());

            if (!code) {
                notify('error', 'Coupon field is required');
                return false;
            }

            const data = {
                _token: `{{ csrf_token() }}`,
                code: code,
                subtotal: subtotal,
                categories: null,
            }

            $.post("/apply-coupon", data).done((response) => {
                notify(response.status, response.message);
                if (response.status == 'success') {
                    let couponAmount = response.amount;
                    if (couponAmount > subtotal) {
                        couponAmount = subtotal;
                    }

                    $('#couponAmount').text(couponAmount);
                    $('.couponCode').text(response.coupon_code);
                    $('#finalTotal').text(absoluteValue((subtotal - couponAmount)));
                    $('[name=coupon_code]').val('');
                    $('.couponContent').removeClass('d-none').hide().show('300');
                    $('#applyCoupon').attr('disabled', true);
                }
            });
        }

        const couponCodeKeyHandler = (event) => {
            if (event.keyCode == 13) {
                applyCouponClickHandler();
            }
        }

        const removeCouponClickHandler = () => {
            $.post(`/remove-coupon`, {
                _token: `{{ csrf_token() }}`
            }).done((response) => {
                if (response.status == 'success') {
                    notify('success', response.success);
                    $('#finalTotal').text(absoluteValue($('.cartSubtotal').first().text()));
                    $('.couponContent').hide('slow');
                    $('input[name=coupon_code]').val('');
                    $('#applyCoupon').removeAttr('disabled');
                }
            });
        }

        const addToWishlistClickHandler = function() {
            const button = $(this);
            const productId = $(this).data('id');

            let products = $(`.addToWishlist[data-id="${productId}"]`);

            const data = {
                _token: `{{ csrf_token() }}`
            }

            $.post(`add-to-wishlist/${productId}`, data).done((response) => {
                if (response.status === 'success') {
                    getWishlistData();
                    getWishlistTotal();
                    $.each(products, function(i, product) {
                        $(product).toggleClass('active');
                    });
                }
                notify(response.status, response.message);
            });

        }

        const removeFromWishlistClickHandler = function(e) {

            const id = $(this).data('id');
            const productId = $(this).data('pid');
            let page = $(this).data('page');
            let parent = $(this).closest('.wishlistItem');

            const data = {
                _token: `{{ csrf_token() }}`
            }

            $.post(`remove-from-wishlist/${id}`, data).done((response) => {
                if (response.status === 'success') {
                    getWishlistData();
                    getWishlistTotal();


                } else {
                    notify(response.status, response.message);
                }

                if (productId) {
                    let products = $(`.addToWishlist[data-id="${productId}"]`);
                    $.each(products, function(i, v) {

                        if ($(v).parents('.product-wishlist').find('.wishlist-label').length) {
                            $(v).parents('.product-wishlist').find('.wishlist-label').text("Add to Wishlist")
                        }

                        if ($(v).hasClass('active')) {
                            $(v).removeClass('active');
                        }
                    });
                }
                if (page == 1) {
                    if (id == 0) {
                        $('.wishlist-row').html(`<div class="empty-message text-center">
        <img src="${FRONTEND_EMPTY_IMAGE}" class="lazyload" alt="">
                    <h6 class="message mt-2">Data not found</h6>
            </div>
`);
                        $('.removeAllBtn').remove();
                        lazyload();
                    } else {
                        parent.hide(300);
                    }
                }
            });
        }

        const addToCompareClickHandler = function() {
            let productId = $(this).data('id');
            let products = $(`.addToCompare[data-id="${productId}"]`);

            $.ajax({
                url: "add-to-compare",
                method: "POST",
                data: {
                    product_id: productId,
                    _token: `{{ csrf_token() }}`
                },
                success: function(response) {
                    if (response.success) {
                        getCompareData();


                        $.each(products, function(i, product) {
                            $(product).toggleClass('active');
                        });

                        notify('success', response.success);
                    } else {
                        notify('error', response.error);
                    }
                }
            });
        }

        const quickViewClickHandler = function() {
            $.get(`product/${$(this).data('product')}`).done((response) => {
                quickViewModal.find('.modal-body').html(response);
                console.log(response);

                setBackgroundImage();
                initImgSlider();
                initializeXZOOM();
            });
            quickViewModal.modal('show');
        }

        const loadReviews = (url) => {

            $.get(url).done((response) => {
                $('#loadMoreBtn').remove();
                $('.review-area').append(response);
                lazyload();
            });
        }

        const loadMoreClickHandler = function() {
            $('#loadMoreBtn').html(`<i class="fa fa-spinner fa-spin"></i> Loading Reviews`);
            loadReviews($(this).data('url'));
        }


        const quantityChangeHandler = function() {
            const parent = $(this).parent();
            let quantity = $(this).val();

            if (quantity == '' || quantity <= 0) {
                quantity = 1;
                $(this).val(quantity);
                parent.find('.minus').attr('disabled', true);
            } else if (quantity == 1) {
                parent.find('.minus').attr('disabled', true);
            } else {
                parent.find('.minus').removeAttr('disabled');
            }

            if ($(this).parent().data('update') != 'no') {
                updateCart(quantity, $(this).parents('.cartItem'));
            }
        }

        // âœ… TRACKING QUEUE SYSTEM - Batched tracking for better performance
        let trackingQueue = [];
        let trackingFlushTimer = null;
        const TRACKING_FLUSH_DELAY = 2000; // 2 seconds

        const queueTrackingEvent = (eventName, eventData) => {
            // Add to queue
            trackingQueue.push({
                event_name: eventName,
                event_data: eventData,
                timestamp: Date.now(),
                url: window.location.href
            });
            
            // Clear existing timer
            if (trackingFlushTimer) {
                clearTimeout(trackingFlushTimer);
            }
            
            // Schedule flush
            trackingFlushTimer = setTimeout(flushTrackingEvents, TRACKING_FLUSH_DELAY);
        };

        const flushTrackingEvents = () => {
            if (trackingQueue.length === 0) return;
            
            // Process all queued events
            trackingQueue.forEach(item => {
                // Facebook/TikTok Event Layer
                if (window.__EVENT_LAYER__ && typeof window.__EVENT_LAYER__.push === 'function') {
                    window.__EVENT_LAYER__.push(item.event_name.toLowerCase(), item.event_data);
                }
                
                // Event Logger (localStorage + sendBeacon)
                if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.queue === 'function') {
                    window.__EVENT_LOGGER__.queue(item.event_name, { url: item.url });
                }
            });

            try {
                if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.flush === 'function') {
                    window.__EVENT_LOGGER__.flush();
                }
            } catch (e) {
            }
            
            // Clear queue
            trackingQueue = [];
        };

        // Flush on page unload (backup)
        window.addEventListener('beforeunload', () => {
            if (trackingQueue.length > 0) {
                flushTrackingEvents();
            }
        });

        // âœ… LIGHTWEIGHT CART COUNT BADGE UPDATER
        const setCartCountBadge = (count = 0) => setCartCountTargets(count);

        const updateCartCountBadge = (delta = 0, absolute = null) => {
            if (absolute !== null && absolute !== undefined) {
                setCartCountBadge(absolute);
                return;
            }

            let currentCount = parseInt(localStorage.getItem('cart_count') || '0', 10);
            if (!Number.isFinite(currentCount) || currentCount < 0) currentCount = 0;
            currentCount += (Number.isFinite(delta) ? delta : 0);
            if (currentCount < 0) currentCount = 0;
            setCartCountBadge(currentCount);
        };

        const reconcileCartCountBadgeFromServer = () => {
            return $.get("{{ url('compare-wishlist-cart-date') }}")
                .then((response) => {
                    const absolute = response?.data?.cart_products ?? null;
                    if (absolute !== null && absolute !== undefined) {
                        updateCartCountBadge(0, absolute);
                    }
                }, () => {
                });
        };

        // Initialize cart count in localStorage on page load
        $(document).ready(function() {
            const bootstrapCount =
                parseInt($('.cart-count').first().text() || '0', 10) ||
                parseInt($('.cartItemCount').first().text() || '0', 10) ||
                parseInt(localStorage.getItem('cart_count') || '0', 10) ||
                0;
            setCartCountTargets(bootstrapCount);
        });

        // add to cart handler
        function addToCartClickHandler() {
            const $btn = $(this);
            let productType = $btn.data('product_type');
            let productId = $btn.data('id');
            const $productCard = $btn.closest('.product-card');
            const isCardContext = $productCard.length > 0 && $btn.closest('.product-details').length === 0;
            let attributeValues = $btn.parents('.product-details').find('.attributeBtn.active').map((i, element) => $(element).data('attribute').id).toArray();

            if (isCardContext) {
                const inStockAttr = $productCard.attr('data-in-stock');
                const isOutOfStock = String(inStockAttr) === '0';
                if (isOutOfStock) {
                    notify('error', 'Out of stock');
                    return;
                }
                ensureProductCardControls($productCard, productId);
                // Add 1 instantly (optimistic), then reveal qty controls.
                incrementProductInCart($productCard);
                openProductCardQty($productCard, productId);
                return;
            }


            const $productDetails = $btn.parents('.product-details');
            const hasVariantUi = $productDetails.find('.attributeBtn').length > 0;
            if (hasVariantUi) {
                const groups = $productDetails.find('.attributeValueArea').length;
                const selected = attributeValues.length;
                if (selected > 0 && groups > 0 && selected < groups) {
                    notify('error', 'Please select all variants');
                    return false;
                }
                // If selected === 0, we allow add-to-cart and the backend will fallback to DEFAULT variant.
            }

            let quantity = $btn.parent().find('[name=quantity]').val();
            if (quantity == null || quantity === '' || Number(quantity) <= 0) {
                quantity = 1;
            }

            let addToCartUrl = `{{ url('/add-to-cart') }}/${productId}/${quantity}`;
            if (Array.isArray(attributeValues) && attributeValues.length) {
                const qs = attributeValues.map(v => `variant[]=${encodeURIComponent(v)}`).join('&');
                addToCartUrl = `${addToCartUrl}?${qs}`;
            }

            $.get(addToCartUrl).done((response) => {
                if (typeof notifyCart === 'function') {
                    notifyCart('Added to cart');
                } else {
                    notify('success', 'Added to cart');
                }

                if (response && response.cart_count !== undefined && response.cart_count !== null) {
                    updateCartCountBadge(0, response.cart_count);
                }

                // âœ… Use queued tracking (non-blocking)
                queueTrackingEvent('AddToCart', { product_id: productId, quantity: quantity });

                if (typeof setCompareWishlistAndCartData === 'function') {
                    setCompareWishlistAndCartData();
                }

                if (!isCardContext && typeof cartBtnClickHandler === 'function') {
                    cartBtnClickHandler();
                }

                
            }).fail((xhr) => {
                const msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add to cart';
                notify('error', msg);
            });
        }

        const ensureProductCardControls = ($productCard, productId) => {
            if (!$productCard || !$productCard.length) return;
            const $content = $productCard.find('.product-content').first();
            if (!$content.length) return;

            const inStockAttr = $productCard.attr('data-in-stock');
            const isOutOfStock = String(inStockAttr) === '0';
            if (isOutOfStock) {
                return;
            }

            if (productId) {
                $productCard.attr('data-product-id', String(productId));
            }

            const $addBtn = $content.find('.addToCart').first();
            if (!$content.find('.product-card-order-now').length) {
                const $orderNow = $(`<a class="add-to-cart-btn product-card-order-now" href="${checkoutUrl}">Order Now</a>`);
                if ($addBtn.length) {
                    $orderNow.insertBefore($addBtn);
                } else {
                    $content.append($orderNow);
                }
            }

            if (!$content.find('.product-card-qty').length) {
                const $qtyWrap = $(
                    `<div class="product-card-qty" data-update="yes">
                        <button type="button" class="product-card-qty-minus" aria-label="Decrease quantity">-</button>
                        <span class="product-card-qty-value">0</span>
                        <button type="button" class="product-card-qty-plus" aria-label="Increase quantity">+</button>
                    </div>`
                );
                $content.append($qtyWrap);
            }
        }

        const hydrateProductCardCartState = ($productCard, productId) => {
            if (!$productCard || !$productCard.length || !productId) return Promise.resolve();

            return getCartData().then((html) => {
                const $tmp = $('<div></div>').html(html);
                const $removeBtn = $tmp.find(`.removeCart[data-pid="${productId}"]`).first();
                const rowId = $removeBtn.data('id');
                const qty = parseInt($removeBtn.closest('.cartItem').find('input[name="quantity"]').val() || '0', 10);
                if (rowId) {
                    $productCard.attr('data-cart-row-id', rowId);
                } else {
                    $productCard.removeAttr('data-cart-row-id');
                }
                $productCard.attr('data-cart-qty', String(Number.isFinite(qty) && qty > 0 ? qty : 0));
            });
        }

        const openProductCardQty = ($productCard, productId) => {
            ensureProductCardControls($productCard, productId);
            const $content = $productCard.find('.product-content').first();
            const $qtyWrap = $content.find('.product-card-qty').first();
            const $addBtn = $content.find('.addToCart').first();
            const $orderNow = $content.find('.product-card-order-now').first();

            const qty = parseInt($productCard.attr('data-cart-qty') || '0', 10);
            $qtyWrap.find('.product-card-qty-value').text(String(Number.isFinite(qty) ? qty : 0));
            $qtyWrap.addClass('is-open');
            $addBtn.hide();
            $orderNow.hide();

            return Promise.resolve();
        }

        const closeAllProductCardQty = () => {
            $('.product-card').each(function() {
                const $card = $(this);
                const $content = $card.find('.product-content').first();
                const $qtyWrap = $content.find('.product-card-qty').first();
                const $addBtn = $content.find('.addToCart').first();
                const $orderNow = $content.find('.product-card-order-now').first();

                if ($qtyWrap.length) {
                    $qtyWrap.removeClass('is-open');
                }

                if ($addBtn.length) {
                    $addBtn.show();
                }

                if ($orderNow.length) {
                    $orderNow.show();
                }
            });
        }

        const setProductCardQtyUi = ($productCard, qty) => {
            const $content = $productCard.find('.product-content').first();
            const $qtyWrap = $content.find('.product-card-qty').first();
            $productCard.attr('data-cart-qty', String(qty));
            $qtyWrap.find('.product-card-qty-value').text(String(qty));
        }

        const setProductCardPending = ($productCard, pending) => {
            if (!$productCard || !$productCard.length) return;
            if (pending) {
                $productCard.addClass('is-cart-pending');
            } else {
                $productCard.removeClass('is-cart-pending');
            }
        }

        const markProductOutOfStock = (productId) => {
            if (!productId) return;
            let $cards = $(`.product-card[data-product-id="${productId}"]`);
            if (!$cards.length) {
                $cards = $(`.product-card`).filter(function () {
                    const $card = $(this);
                    const pid = $card.attr('data-product-id') || $card.data('product-id') || $card.find('.addToCart').first().data('id');
                    return String(pid) === String(productId);
                });
            }
            $cards.attr('data-in-stock', '0');
            $cards.find('.addToCart').each(function () {
                $(this).attr('disabled', true);
            });
            $cards.find('.product-card-qty-plus, .product-card-qty-minus').each(function () {
                $(this).attr('disabled', true);
            });
        }

        $(document).on('click', '.product-card-order-now', function (e) {
            e.preventDefault();
            const $card = $(this).closest('.product-card');
            const productId = $card.attr('data-product-id') || $card.data('product-id');

            if (!productId) {
                window.location.href = checkoutUrl;
                return;
            }

            const payload = {
                _token: csrfToken,
                id: productId,
                qty: 1,
            };

            $.post(buyNowUrl, payload)
                .done(() => {
                    window.location.href = checkoutUrl;
                })
                .fail(() => {
                    if (typeof notify === 'function') {
                        notify('error', 'Failed to process order');
                    }
                });
        });

        const productCardSyncState = new Map();

        const ensureProductCardSyncState = ($productCard) => {
            if (!$productCard || !$productCard.length) return null;

            let key = $productCard.attr('data-cart-sync-key');
            if (!key) {
                key = `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
                $productCard.attr('data-cart-sync-key', key);
            }

            if (!productCardSyncState.has(key)) {
                const qty = parseInt($productCard.attr('data-cart-qty') || '0', 10);
                const safeQty = Number.isFinite(qty) && qty >= 0 ? qty : 0;
                productCardSyncState.set(key, {
                    key,
                    inFlight: false,
                    confirmedQty: safeQty,
                    targetQty: safeQty,
                });
            }

            return productCardSyncState.get(key);
        };

        const queueProductCardQtyTarget = ($productCard, targetQty) => {
            const state = ensureProductCardSyncState($productCard);
            if (!state) return;
            const previousTarget = Math.max(0, parseInt(state.targetQty || '0', 10) || 0);
            const safeTarget = Number.isFinite(targetQty) ? Math.max(0, targetQty) : 0;
            state.targetQty = safeTarget;
            const optimisticDelta = safeTarget - previousTarget;
            if (optimisticDelta !== 0) {
                updateCartCountBadge(optimisticDelta);
            }
            setProductCardQtyUi($productCard, safeTarget);
            processProductCardQtySync($productCard);
        };

        const removeProductFromCart = ($productCard, state, prevConfirmedQty) => {
            const rowId = $productCard.attr('data-cart-row-id');
            if (!rowId) {
                state.confirmedQty = 0;
                return Promise.resolve(true);
            }

            return $.post(`/remove-from-cart/${rowId}`, { _token: csrfToken })
                .then((response) => {
                    if (response && response.status === 'success') {
                        $productCard.removeAttr('data-cart-row-id');
                        state.confirmedQty = 0;
                        setProductCardQtyUi($productCard, 0);
                        if (response.cartItemCount !== undefined && response.cartItemCount !== null) {
                            updateCartCountBadge(0, response.cartItemCount);
                        } else if (response.cart_count !== undefined && response.cart_count !== null) {
                            updateCartCountBadge(0, response.cart_count);
                        } else {
                            updateCartCountBadge(-Math.max(1, prevConfirmedQty));
                        }
                        return true;
                    }
                    notify('error', response?.message || 'Failed to remove from cart');
                    return false;
                }, () => {
                    notify('error', 'Failed to remove from cart');
                    return false;
                });
        };

        const queueAddToCartEventsByDelta = (productId, fromQty, toQty) => {
            const before = Math.max(0, parseInt(fromQty || '0', 10) || 0);
            const after = Math.max(0, parseInt(toQty || '0', 10) || 0);
            const delta = after - before;
            if (!productId || delta <= 0) return;

            for (let i = 0; i < delta; i++) {
                queueTrackingEvent('AddToCart', {
                    product_id: productId,
                    quantity: 1,
                    cart_quantity: before + i + 1,
                });
            }
        };

        const processProductCardQtySync = ($productCard) => {
            const state = ensureProductCardSyncState($productCard);
            if (!state || state.inFlight) return;

            const desiredQty = Math.max(0, parseInt(state.targetQty || '0', 10) || 0);
            const prevConfirmedQty = Math.max(0, parseInt(state.confirmedQty || '0', 10) || 0);
            if (desiredQty === prevConfirmedQty) {
                setProductCardPending($productCard, false);
                return;
            }

            const productId = $productCard.attr('data-product-id');

            state.inFlight = true;
            setProductCardPending($productCard, true);

            let requestPromise;
            if (desiredQty === 0) {
                requestPromise = removeProductFromCart($productCard, state, prevConfirmedQty);
            } else {
                const existingRowId = $productCard.attr('data-cart-row-id');
                if (!existingRowId) {
                    const addUrl = `{{ url('/add-to-cart') }}/${productId}/${desiredQty}`;
                    requestPromise = $.get(addUrl)
                        .then((response) => {
                            let cartCount = null;
                            let safeQty = 1;
                            try {
                                const parsed = (typeof response === 'string') ? (JSON.parse(response) || {}) : response;
                                const rowId = parsed && typeof parsed === 'object' ? parsed.rowId : null;
                                const qty = parsed && typeof parsed === 'object' ? parseInt(parsed.qty || '1', 10) : 1;
                                cartCount = parsed && typeof parsed === 'object' ? (parsed.cart_count ?? null) : null;
                                const parsedVariantId = parsed && typeof parsed === 'object' && parsed.options ? (parsed.options.variant_id ?? null) : null;
                                if (rowId) {
                                    $productCard.attr('data-cart-row-id', String(rowId));
                                }
                                if (parsedVariantId !== null && parsedVariantId !== undefined && parsedVariantId !== '') {
                                    const vid = String(parsedVariantId);
                                    $productCard.attr('data-cart-variant-id', vid);
                                    $(`.product-card`).filter(function() {
                                        const $card = $(this);
                                        const pid = $card.attr('data-product-id') || $card.data('product-id') || $card.find('.addToCart').first().data('id');
                                        return String(pid) === String(productId);
                                    }).attr('data-cart-variant-id', vid);
                                } else {
                                    $productCard.removeAttr('data-cart-variant-id');
                                }
                                safeQty = Number.isFinite(qty) && qty > 0 ? qty : 1;
                            } catch (e) {
                                safeQty = 1;
                            }

                            state.confirmedQty = safeQty;
                            const pendingTarget = Math.max(0, parseInt(state.targetQty || safeQty, 10) || safeQty);
                            if (pendingTarget < safeQty) {
                                state.targetQty = safeQty;
                            }
                            setProductCardQtyUi($productCard, state.targetQty);
                            if (cartCount !== null && cartCount !== undefined && cartCount !== '') {
                                updateCartCountBadge(0, cartCount);
                            } else {
                                updateCartCountBadge(safeQty - prevConfirmedQty);
                            }
                            queueAddToCartEventsByDelta(productId, prevConfirmedQty, safeQty);
                            return true;
                        }, (xhr) => {
                            const failureCount = xhr?.responseJSON?.cart_count ?? null;
                            if (failureCount !== null && failureCount !== undefined) {
                                updateCartCountBadge(0, failureCount);
                            }
                            notify('error', 'Failed to add to cart');
                            return false;
                        });
                } else {
                    requestPromise = $.post(`/cart-update/${existingRowId}`, {
                            _token: csrfToken,
                            quantity: desiredQty,
                        })
                        .then((response) => {
                            if (response && response.status === 'error') {
                                if (response.cart_count !== undefined && response.cart_count !== null) {
                                    updateCartCountBadge(0, response.cart_count);
                                }
                                if (response.max_qty !== undefined && response.max_qty !== null) {
                                    const mq = parseInt(response.max_qty, 10);
                                    if (Number.isFinite(mq) && mq <= 0 && productId) {
                                        markProductOutOfStock(productId);
                                    }
                                }
                                notify('error', response.message || 'Failed to update cart');
                                return false;
                            }

                            const serverQty = parseInt(response?.quantity ?? desiredQty, 10);
                            const safeServerQty = Number.isFinite(serverQty) && serverQty >= 0 ? serverQty : desiredQty;
                            state.confirmedQty = safeServerQty;
                            const pendingTarget = Math.max(0, parseInt(state.targetQty || safeServerQty, 10) || safeServerQty);
                            if (pendingTarget < safeServerQty) {
                                state.targetQty = safeServerQty;
                            }
                            setProductCardQtyUi($productCard, state.targetQty);

                            if (response && response.remaining !== undefined && response.remaining !== null) {
                                const remaining = parseInt(response.remaining, 10);
                                if (Number.isFinite(remaining) && remaining <= 0 && productId) {
                                    markProductOutOfStock(productId);
                                }
                            }

                            if (response && response.cart_count !== undefined && response.cart_count !== null) {
                                updateCartCountBadge(0, response.cart_count);
                            } else {
                                updateCartCountBadge(safeServerQty - prevConfirmedQty);
                            }
                            queueAddToCartEventsByDelta(productId, prevConfirmedQty, safeServerQty);
                            return true;
                        }, (xhr) => {
                            const failureCount = xhr?.responseJSON?.cart_count ?? null;
                            if (failureCount !== null && failureCount !== undefined) {
                                updateCartCountBadge(0, failureCount);
                            }
                            notify('error', 'Failed to update cart');
                            return false;
                        });
                }
            }

            Promise.resolve(requestPromise).then((ok) => {
                if (!ok) {
                    state.targetQty = state.confirmedQty;
                    setProductCardQtyUi($productCard, state.confirmedQty);
                    reconcileCartCountBadgeFromServer();
                    if (state.confirmedQty <= 0) {
                        closeAllProductCardQty();
                    }
                }
            }).finally(() => {
                state.inFlight = false;
                if (state.targetQty !== state.confirmedQty) {
                    processProductCardQtySync($productCard);
                    return;
                }
                setProductCardPending($productCard, false);
            });
        };

        const incrementProductInCart = ($productCard) => {
            const productId = $productCard.attr('data-product-id');
            if (!productId) return;
            const currentQty = parseInt($productCard.attr('data-cart-qty') || '0', 10);
            const safeQty = Number.isFinite(currentQty) && currentQty >= 0 ? currentQty : 0;
            if (typeof notifyCart === 'function') {
                notifyCart(`Added to cart (${safeQty + 1})`);
            }
            queueProductCardQtyTarget($productCard, safeQty + 1);
        };

        const decrementProductInCart = ($productCard) => {
            const currentQty = parseInt($productCard.attr('data-cart-qty') || '0', 10);
            const safeQty = Number.isFinite(currentQty) && currentQty >= 0 ? currentQty : 0;
            if (safeQty <= 0) return;
            queueProductCardQtyTarget($productCard, safeQty - 1);
        };

        const setCompareWishlistAndCartData = () => {
            $.ajax({
                type: "GET",
                url: "{{ url('compare-wishlist-cart-date') }}",
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        let data = response.data;

                        if (data.compare_products > 0) {
                            $('.compare-count').text(data.compare_products);
                            $('.compare-count').removeClass('d-none');
                        } else {
                            $('.compare-count').addClass('d-none');
                        }

                        if (data.wishlist_products > 0) {
                            $('.wishlist-count').text(data.wishlist_products);
                            $('.wishlist-count').removeClass('d-none');
                        } else {
                            $('.wishlist-count').addClass('d-none');
                        }

                        $('.cartSubtotal').text(data.cart_subtotal.toFixed(2));

                        setCartCountTargets(data.cart_products);

                    } else {
                        console.warn(response.message);
                    }
                }
            });
        }

        let isLoadedWishlistData = false;
        const wishBtnClickHandler = () => {
            if (isLoadedWishlistData) return false;
            $.ajax({
                type: "GET",
                url: "wishlist-short",
                success: function(response) {
                    $('.wish--products').html(response);
                    lazyload();
                    isLoadedWishlistData = true;
                }
            });
        }

        let isLoadedCartData = false;
        const cartBtnClickHandler = (e) => {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }

            const $cartSidebar = $('#cart-sidebar-area');
            if ($cartSidebar.length) {
                $('body').addClass('scroll-hide-sm');
                $cartSidebar.addClass('active');
                $('.body-overlay').addClass('active');
            }

            if (isLoadedCartData) return false;
            $.ajax({
                type: "GET",
                url: "/cart/sidebar-content",
                success: function(response) {
                    $('.cart--products').html(response);
                    lazyload();
                    isLoadedCartData = true;
                }
            });
        }

        $(document).on('click', '#loadMoreBtn', loadMoreClickHandler);
        $(document).on('click', '.quickViewBtn', quickViewClickHandler);
        $(document).on("click", ".qtyButton", quantityButtonClickHandler);
        $(document).on('click', ' #applyCoupon', applyCouponClickHandler);
        $(document).on('click', '.removeCart', removeFromCartHandler);
        $(document).on('click', '.removeCoupon', removeCouponClickHandler);
        $(document).on('click', '.addToWishlist', addToWishlistClickHandler);
        $(document).on('click', '.removeWishlist', removeFromWishlistClickHandler);
        $(document).on('click', '.addToCompare', addToCompareClickHandler);
        $(document).on('change, input', '[name=quantity]', quantityChangeHandler);
        $(document).on('keyup', '[name=coupon_code]', couponCodeKeyHandler);
        $(document).off('click', '.addToCart').on('click', '.addToCart', addToCartClickHandler);
        $(document).off('click', '.product-card-qty-plus').on('click', '.product-card-qty-plus', function() {
            const $productCard = $(this).closest('.product-card');
            incrementProductInCart($productCard);
        });
        $(document).off('click', '.product-card-qty-minus').on('click', '.product-card-qty-minus', function() {
            const $productCard = $(this).closest('.product-card');
            decrementProductInCart($productCard);
        });
        $(document).on('click', '.wish-button', wishBtnClickHandler);
        $(document).on('click', '.cart-button', cartBtnClickHandler);

        $(document).on('click', function(e) {
            if ($(e.target).closest('.product-card').length) return;
            closeAllProductCardQty();
        });

        $('.product-card').each(function() {
            const $card = $(this);
            const $addBtn = $card.find('.addToCart').first();
            const productId = $addBtn.data('id');
            if (productId) {
                ensureProductCardControls($card, productId);
            }
        });

        lazyload();
        setCompareWishlistAndCartData();
    </script>
    
    <script>
        "use strict";

        function setCountdownProgress(element, gradientAngle) {
            var gradient = "conic-gradient(hsl(var(--base)) " + gradientAngle + "deg, hsl(var(--black) / .05) 0deg)";
            element.style.background = gradient;
        }

        function initializeCountdown(offerElement) {

            var startAt = parseInt(offerElement.getAttribute('data-starts-at'));
            var endsAt = parseInt(offerElement.getAttribute('data-ends-at'));
            var totalDuration = endsAt - startAt;

            if (isNaN(startAt) || isNaN(endsAt) || !isFinite(totalDuration) || totalDuration <= 0) {
                return;
            }

            var daysEl = offerElement.querySelector(".box__days");
            var hrsEl = offerElement.querySelector(".remaining-time__hrs");
            var minEl = offerElement.querySelector(".remaining-time__min");
            var secEl = offerElement.querySelector(".remaining-time__sec");
            var contentEl = offerElement.querySelector(".remaining-time__content");

            if (!daysEl || !hrsEl || !minEl || !secEl || !contentEl) {
                return;
            }

            var countdownInterval = setInterval(function() {
                var now = Date.now();
                var distance = endsAt - now;

                if (now < startAt) {
                    contentEl.innerHTML = "<p>Offer hasn't started yet</p>";
                    return;
                }

                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                daysEl.textContent = days;
                hrsEl.textContent = hours;
                minEl.textContent = minutes;
                secEl.textContent = seconds;

                var elapsed = now - startAt;
                var progress = Math.min(elapsed / totalDuration, 1);

                if ($(offerElement).find('.offer-countdown').length) {
                    setCountdownProgress(daysEl, 360 * (1 - progress));
                    setCountdownProgress(hrsEl, (360 / 24) * hours);
                    setCountdownProgress(minEl, (360 / 60) * minutes);
                    setCountdownProgress(secEl, (360 / 60) * seconds);
                }

                if (distance < 0) {
                    clearInterval(countdownInterval);
                    contentEl.innerHTML = "<p class='expired'>Expired</p>";
                }
            }, 1000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var offers = document.querySelectorAll('.flash-sell-section');
            offers.forEach(function(offer) {
                if (offer.hasAttribute('data-starts-at') && offer.hasAttribute('data-ends-at')) {
                    initializeCountdown(offer);
                }
            });
        });
    </script>
    
    <script>
        'use strict';
        (function($) {
            $(document).on('submit', '.subscribe-form', function(e) {
                e.preventDefault();

                const $form = $(this);
                const actionUrl = $form.attr('action') || 'newsletter/subscribe';
                const email = $form.find('input[name="email"]').val();
                const token = $form.find('input[name="_token"]').val() || `{{ csrf_token() }}`;

                let data = {
                    email: email,
                    _token: token,
                };

                if (!data.email) {
                    notify('error', `The email field is required`);
                    return false;
                }

                $form.find('.subscribe-btn').attr('disabled', true);

                $.post(actionUrl, data)
                    .done(function(response) {
                        $form.find('.subscribe-btn').removeAttr('disabled');
                        if (response && typeof response === 'object' && 'status' in response) {
                            if (response.status) {
                                notify('success', response.message || 'Subscribed');
                            } else {
                                notify('error', response.message || 'Failed to subscribe');
                            }
                        } else {
                            notify('success', 'Subscribed');
                        }
                        $form.find('[name=email]').val('');
                    })
                    .fail(function() {
                        $form.find('.subscribe-btn').removeAttr('disabled');
                        notify('error', 'Failed to subscribe');
                    });
            });
        })
        (jQuery)
    </script>
    
    <script>
        (function($) {
            "use strict"
            $('.verify-gcaptcha').on('submit', function() {
                var response = grecaptcha.getResponse();
                if (response.length == 0) {
                    document.getElementById('g-recaptcha-error').innerHTML = '<span class="text--danger">Captcha field is required.</span>';
                    return false;
                }
                return true;
            });

            window.verifyCaptcha = () => {
                document.getElementById('g-recaptcha-error').innerHTML = '';
            }
        })(jQuery);
    </script>
    
    @stack('script')
    @stack('scripts')

    @php
        $settingsCta = $generalsetting ?? \App\Models\GeneralSetting::first();
        $waRaw = trim((string) ($settingsCta->whatsapp_number ?? ''));
        $msRaw = trim((string) ($settingsCta->messenger_link ?? ''));
        $phoneRaw = trim((string) ($settingsCta->support_phone ?? ''));

        $waLink = '';
        if (!empty($waRaw)) {
            if (preg_match('~^https?://~i', $waRaw)) {
                $waLink = $waRaw;
            } else {
                $waDigits = preg_replace('~\D+~', '', $waRaw);
                $waDigits = ltrim($waDigits, '0');
                if (!empty($waDigits)) {
                    $waLink = 'https://wa.me/' . $waDigits;
                }
            }
        }

        $msLink = '';
        if (!empty($msRaw)) {
            $msLink = preg_match('~^https?://~i', $msRaw) ? $msRaw : ('https://' . ltrim($msRaw, '/'));
        }

        $phoneLink = '';
        if (!empty($phoneRaw)) {
            $phoneDigits = preg_replace('~\s+~', '', $phoneRaw);
            $phoneLink = 'tel:' . $phoneDigits;
        }
    @endphp

    @if(!empty($waLink) || !empty($msLink) || !empty($phoneLink))
        <style>
            .sv-floating-cta {
                position: fixed;
                right: 16px;
                bottom: 16px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .sv-floating-cta a {
                width: 46px;
                height: 46px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                box-shadow: 0 8px 20px rgba(0,0,0,0.18);
                text-decoration: none;
            }
            .sv-floating-cta a:hover { color: #fff; }
            .sv-floating-cta .sv-wa { background: #25D366; }
            .sv-floating-cta .sv-ms { background: #0084FF; }
            .sv-floating-cta .sv-ph { background: #111827; }
        </style>

        <div class="sv-floating-cta" aria-label="Contact quick actions">
            @if(!empty($waLink))
                <a class="sv-wa" href="{{ $waLink }}" target="_blank" rel="noopener" aria-label="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            @endif

            @if(!empty($msLink))
                <a class="sv-ms" href="{{ $msLink }}" target="_blank" rel="noopener" aria-label="Messenger">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
            @endif

            @if(!empty($phoneLink))
                <a class="sv-ph" href="{{ $phoneLink }}" aria-label="Call">
                    <i class="las la-phone"></i>
                </a>
            @endif
        </div>
    @endif
</body>

</html>

