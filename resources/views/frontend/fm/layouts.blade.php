<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>

{{--
    CSRF token in the document head.

    The classic layouts (frontend.layouts.master:6, frontend.layouts.app) both
    emitted this meta tag, and page scripts inherited a habit of reading the
    token from it. The modern chrome instead ships it through
    window.FM_CONFIG (bottom of this file), which is what fm-app.js and
    fm-cart.js actually use.

    Both are kept on purpose: FM_CONFIG is the canonical source for this
    template set, but a ported page (or a third-party snippet) that reaches for
    the meta tag should not silently send an empty token and 419 on every POST.
    The tag is inert on its own, so keeping it costs nothing.
--}}
<meta name="csrf-token" content="{{ csrf_token() }}"/>

<title>@yield('title', $generalsetting->name ?? config('app.name'))</title>
@if($generalsetting->favicon ?? null)
    <link href="{{ \App\Support\MediaPath::url($generalsetting->favicon, 'settings') }}" rel="icon"/>
@endif

{{--
    SEO block. This is the SAME partial the classic layout emits, so the modern
    set publishes the same machine-readable data: the admin's SEO Meta record
    (meta title, keywords, robots, canonical, Open Graph and Twitter cards) and
    the per-page @section('meta_description') / @section('meta_keywords') /
    @section('canonical') a page yields.

    It is included rather than re-written because two copies of these tags is
    worse than one: a crawler reads whichever it sees first, and the classic
    half of the shop would then disagree with the modern half.

    $fallbackDescription is the one thing this layout adds - the modern set has
    a generic sentence to fall back on, while the classic layout historically
    emitted no description at all.
--}}
@include('frontend.layouts.partials.seo-meta', [
    'fallbackDescription' => \App\Support\MetaText::plain($generalsetting->description ?? '') ?: 'Shop online - fast delivery, secure payment, easy returns',
])

<!-- Google Fonts: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<!-- Material Symbols (local theme icons: star, eco, verified, ...) -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind CSS v3 with Plugins -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f0fdf4',
              100: '#dcfce7',
              200: '#bbf7d0',
              500: '#22c55e',
              600: '#16a34a',
              700: '#15803d',
              800: '#1b5e20',
              900: '#144618',
              accent: '#4caf50'
            },
            surface: {
              cream: '#fbfcf8',
              card: '#f4f6f0'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          }
        }
      }
    }
  </script>
<!-- BEGIN: Custom Styles -->
<style data-purpose="base-styling">
    :root {
      --primary-color: #1b5e20;
      --primary-light: #2e7d32;
      --accent-color: #4caf50;
      --bg-cream: #fbfcf8;
      --surface-light: #f4f6f0;
      --text-dark: #192a1e;
      --text-muted: #5e6d62;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--bg-cream);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    .btn-brand-primary {
      background-color: var(--primary-color);
      color: #ffffff;
      font-weight: 600;
      border: 1px solid var(--primary-color);
      transition: all 0.25s ease-in-out;
    }
    .btn-brand-primary:hover {
      background-color: #144618;
      border-color: #144618;
      color: #ffffff;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(27, 94, 32, 0.2);
    }

    .btn-brand-outline {
      background-color: transparent;
      color: var(--primary-color);
      border: 1.5px solid var(--primary-color);
      font-weight: 600;
      transition: all 0.25s ease;
    }
    .btn-brand-outline:hover {
      background-color: var(--primary-color);
      color: #ffffff;
    }

    .card-product {
      transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
      border: 1px solid rgba(0,0,0,0.06);
    }
    .card-product:hover {
      transform: translateY(-5px);
      box-shadow: 0 14px 28px rgba(0, 0, 0, 0.08);
      border-color: rgba(76, 175, 80, 0.35);
    }

    .heart-btn {
      transition: all 0.2s ease;
    }
    .heart-btn.active i {
      color: #dc2626 !important;
    }

    .badge-organic {
      background-color: #e8f5e9;
      color: #1b5e20;
      font-weight: 700;
      font-size: 0.75rem;
    }
    .badge-discount {
      background-color: #fee2e2;
      color: #b91c1c;
      font-weight: 700;
      font-size: 0.75rem;
    }
  </style>
<style data-purpose="micro-interactions">
    .category-chip {
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .category-chip:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.05);
    }
    .category-chip.active {
      background-color: #1b5e20 !important;
      color: #ffffff !important;
    }
    .category-chip.active p, .category-chip.active span {
      color: #ffffff !important;
    }

    #backToTopBtn {
      position: fixed;
      bottom: 25px;
      right: 25px;
      z-index: 1040;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    #backToTopBtn.show {
      opacity: 1;
      visibility: visible;
    }
  </style>
<!-- END: Custom Styles -->
<style id="fm-audit-fix">
/* ===== FreshMart responsive audit fix (auto-injected) ===== */
*,*::before,*::after{box-sizing:border-box}
html,body{overflow-x:hidden;max-width:100%;width:100%}
img,svg,video,canvas,iframe{max-width:100%;height:auto}
img{max-width:100%!important;height:auto!important}
input,select,textarea,button{max-width:100%}
pre{white-space:pre-wrap;word-break:break-word}
table{max-width:100%;overflow-x:auto;display:block}
/* fixed pixel widths -> fluid */
[style*="width: 1"][style*="px"],[style*="width:9"][style*="px"]{width:100%!important;max-width:100%!important}
[style*="min-width"][style*="px"]{min-width:0!important}
[style*="grid-template-columns: repeat"][style*="px"]{grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr))!important}
/* flex wrap protection */
[class*="flex"][style*="display: flex"]{flex-wrap:wrap}
/* mobile */
@media(max-width:576px){
  [class*="gap-"]{gap:.5rem!important}
  [class*="px-"],[class*="p-"]{padding-left:.75rem!important;padding-right:.75rem!important}
  /* Both guards above match on a SUBSTRING, not on a utility name: "gap-2",
     "py-2.5", "group-hover:scale-102" and "p-2.5" all contain "p-", and every
     "-gap-" class contains "gap-". On the header's left cluster that stacked
     12px on four nested elements and pushed the hamburger glyph to x=60 on a
     390px screen - 60px of dead space before the first control, measured with
     work/Narayana Takbeer/header_boxmodel.mjs. The bar is a row of 44x44
     controls, not a padded block, so the chain is reset through the
     fm-header-brand hook. Scoped to that chain only: the section blocks this
     guard was written for keep their padding. */
  [data-purpose="primary-header"] .container>div,
  [data-purpose="primary-header"] .fm-header-brand,
  [data-purpose="primary-header"] .fm-header-brand>button,
  [data-purpose="primary-header"] .fm-header-brand>a,
  [data-purpose="primary-header"] .fm-header-brand>a>img{padding-left:0!important;padding-right:0!important}
  h1{font-size:1.5rem!important;line-height:1.25!important}
  h2{font-size:1.25rem!important}
  .fm-hide-mobile{display:none!important}
}
/* tablet */
@media(min-width:577px) and (max-width:992px){
  [class*="px-8"],[class*="px-12"]{padding-left:1.5rem!important;padding-right:1.5rem!important}
}
/* tap targets on mobile */
@media(max-width:768px){
  button,a.btn,[role="button"],input[type="submit"]{min-height:42px}
}
/* desktop guard */
@media(min-width:993px){.fm-only-desktop{display:block!important}}
</style>
  {{--
    Stylesheet load order - do not reorder without re-testing at 320/768/1280 px.

        bootstrap / icons / fonts   CDN, look & feel of the approved design
        index.css                   original design tokens (.card-product, .btn-brand-*)
        fm-sections.css             generic primitives (.fm-card, .fm-section-head)
        fm-sections-home.css        page blocks built on those primitives
        fm-tap-target.css           WCAG 24/44 px hit areas
        fm-runtime-fix.css          never-scroll-sideways + wrapping hardening
        fm-interaction.css          search suggest, toast, mini-cart line items
        fm-ai.css                   AI search drawer
        ...page sheets on the 'css' stack (fm-products.css, ...)
        fm-product-card.css         LAST: product grid + product card, transcribed
                                    from work/code.html (the approved catalog
                                    design). It is the final word on
                                    .fm-card.fm-product / the result grid because
                                    Bootstrap's own .p-4/.mb-3/.mt-* utilities are
                                    !important and silently rewrote the reference
                                    spacing (see the header of that file).
--}}

  <link rel="stylesheet" href="{{ asset('frontend/css/index.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-sections.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-sections-home.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-tap-target.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-runtime-fix.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-interaction.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-ai.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-events.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-footer.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-slider.css') }}">

  {{-- Tracking layer (GTM / Meta / TikTok + tracker helpers). Same include the
       classic layout uses, so ViewContent and friends behave identically on
       both designs. --}}
  @include('frontend.partials.event-layer-head')

  @stack('css')
  @stack('styles')

  {{-- Product grid + product card. Linked after every other sheet, including
       the page stack, so work/code.html's values always win. --}}
  <link rel="stylesheet" href="{{ asset('frontend/css/fm-product-card.css') }}">
</head>
<body class="antialiased">

{{--
    GTM's <noscript> fallback. event-layer-head already put the container's
    scripts in the <head>; this half only fires when scripting is blocked, and
    the classic layout includes it in the same place.
--}}
@include('frontend.partials.event-layer-body')

{{--
    FM:CHROME ============================================================
    Everything the header and the footer need, resolved once here on the
    server so both paint correct on the first byte. The partials below are
    given these values explicitly (Blade passes data downwards, never upwards,
    so a partial cannot read a variable its parent created unless the parent
    hands it over) and fm-app.js keeps the live numbers in sync afterwards.
--}}
@php
    // Server resolved values so the header is correct on first paint.
    // fm-app.js keeps them in sync afterwards.
    $fmCartCount = 0;
    $fmCartTotal = 0;
    try {
        // Bare Cart:: resolves to the package alias, exactly like
        // partials/cart-items.blade.php below. The previous fully qualified
        // Illuminate\Support\Facades\Cart does not exist, so it threw and the
        // catch below swallowed it - which made the badge and the header
        // total paint 0 on every modern page until JS corrected them.
        $fmCart = \Cart::instance('shopping');
        $fmCartCount = $fmCart->count();
        // subtotal() answers a formatted string ("1,050.00"), so casting it
        // straight to float stops at the separator and yields 1. Strip the
        // thousands separator first - the same trick the classic cart does.
        $fmCartTotal = (float) str_replace(',', '', $fmCart->subtotal());
    } catch (\Throwable $e) {
        // Cart is optional on some pages; fall back to an empty header badge.
    }

    $fmWishlist = session()->get('wishlist', []);
    $fmWishlistCount = is_array($fmWishlist) ? count(array_unique(array_map('intval', $fmWishlist))) : 0;

    $fmShopName = $generalsetting->name ?? config('app.name');
    $fmPhone = $generalsetting->support_phone ?: ($contact->phone ?? '');
    $fmWhatsapp = preg_replace('/\D/', '', (string) ($generalsetting->whatsapp_number ?: ($contact->hotline ?? '')));
    // Header logo. The bar behind it is white, so the DARK ink logo is the
    // correct one here; picking white_logo rendered a white wordmark on a white
    // bar and it disappeared.
    $fmLogo = \App\Support\MediaPath::url($generalsetting->dark_logo ?: $generalsetting->white_logo, 'settings');
@endphp

<!-- FM:SECTION announcement-bar ======================================= -->
@include('frontend.fm.partials.header.announcement-bar', [
    'fmPhone'    => $fmPhone,
    'fmWhatsapp' => $fmWhatsapp,
])
<!-- /FM:SECTION announcement-bar -->

<!-- FM:SECTION primary-header ========================================== -->
@include('frontend.fm.partials.header.primary-header', [
    'fmShopName'      => $fmShopName,
    'fmLogo'          => $fmLogo,
    'fmWishlistCount' => $fmWishlistCount,
    'fmCartCount'     => $fmCartCount,
    'fmCartTotal'     => $fmCartTotal,
])
<!-- /FM:SECTION primary-header -->

<!-- FM:SECTION primary-navigation ====================================== -->
@include('frontend.fm.partials.header.primary-navigation')
<!-- /FM:SECTION primary-navigation -->

{{-- One drawer serves both the phone hamburger and the Categories pill: the
     categories first, because that is what a phone visitor is reaching for,
     then the same destinations as the desktop strip. --}}
<!-- FM:SECTION mobile-nav-drawer ======================================== -->
@include('frontend.fm.partials.header.mobile-nav-drawer')
<!-- /FM:SECTION mobile-nav-drawer -->

{{-- The phone search drawer: the whole desktop search component behind the
     magnifier in the action cluster. It has to live here and not inside the
     header - see the note in partials/header/search-drawer.blade.php. --}}
<!-- FM:SECTION search-drawer =========================================== -->
@include('frontend.fm.partials.header.search-drawer')
<!-- /FM:SECTION search-drawer -->

<!-- FM:SECTION page-content ============================================ -->
<main>
@if(session('success') || session('error'))
<div class="container mx-auto px-4 mt-3">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
</div>
@endif

@yield('content')
</main>
<!-- /FM:SECTION page-content -->

<!-- FM:SECTION footer ================================================= -->
@include('frontend.fm.partials.footer.footer-links', [
    'fmShopName' => $fmShopName,
    'fmPhone'    => $fmPhone,
])
<!-- /FM:SECTION footer -->

<!-- FM:SECTION minicart-drawer ======================================== -->
@include('frontend.fm.partials.overlays.mini-cart-drawer', ['fmCartTotal' => $fmCartTotal])
<!-- /FM:SECTION minicart-drawer -->

<!-- FM:SECTION toast-notifications ==================================== -->
@include('frontend.fm.partials.overlays.toast-notifications')
<!-- /FM:SECTION toast-notifications -->

<!-- FM:SECTION back-to-top ============================================= -->
@include('frontend.fm.partials.overlays.back-to-top')
<!-- /FM:SECTION back-to-top -->

<script>
    // URLs the front-end scripts need, resolved server side so no page has to
    // hardcode a path.
    window.FM_CONFIG = {
        cartStoreUrl:      @json(route('cart.store')),
        cartRemoveUrl:     @json(route('cart.remove')),
        cartIncrementUrl:  @json(route('cart.increment')),
        cartDecrementUrl:  @json(route('cart.decrement')),
        cartCountUrl:      @json(route('cart.count')),
        cartItemsUrl:      @json(route('cart.mini-items')),
        cartUpdateUrl:     @json(url('/cart-update')),
        couponApplyUrl:    @json(route('coupon.apply')),
        couponRemoveUrl:   @json(route('coupon.remove')),
        checkoutUrl:       @json(route('customer.checkout')),
        wishlistAddUrl:    @json(url('add-to-wishlist')),
        wishlistRemoveUrl: @json(url('remove-from-wishlist')),
        wishlistCountUrl:  @json(url('wishlist-count')),
        livesearchUrl:     @json(route('livesearch')),
        csrfToken:         @json(csrf_token()),
        currency:          'Tk ',
        messages: {
            added:   'Added to cart',
            removed: 'Removed from cart',
            saved:   'Saved to wishlist',
            unsaved: 'Removed from wishlist',
            error:   'Something went wrong',
            stock:   'Not enough stock for this product'
        }
    };
</script>

<!-- Bootstrap 5.3 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('frontend/js/index.js') }}"></script>
<script src="{{ asset('frontend/js/fm-app.js') }}"></script>
<script src="{{ asset('frontend/js/fm-ai.js') }}"></script>

@stack('script')
@stack('scripts')
</body>
</html>
