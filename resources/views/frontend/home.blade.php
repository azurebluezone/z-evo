@extends('frontend.layouts.app')

@section('title', 'Home')
@section('meta_description', 'Build your dream e-commerce store with Z-Evo-Commerce, a feature-packed platform offering secure payments and customizable designs to scale your business effortlessly.')

@section('content')
{{-- Main Content --}}
        {{-- Hero Banner Section --}}
        @if(isset($sections) && ($sections['hero']['show'] ?? true))
            @include('frontend.sections.hero-banner')
        @endif
        
                  <!-- Service Section -->

        {{-- Promo Banners Section --}}
        @if(isset($sections) && ($sections['promo']['show'] ?? true))
            @include('frontend.sections.promo-banners')
        @endif
        

        {{-- Categories Section --}}
        @if(isset($sections) && ($sections['category']['show'] ?? true))
            @include('frontend.sections.categories')
        @endif


        {{-- Recommended Products Section --}}
        {{-- Flash Sale Section --}}
        @if(isset($sections) && ($sections['flash_sale']['show'] ?? true))
            @include('frontend.sections.flash-sale')
        @endif

        {{-- Recommended Products Section --}}
        @if(isset($sections) && ($sections['recommended']['show'] ?? true))
            @include('frontend.sections.recommended-products')
        @endif


        {{-- Featured Products Section --}}
        @if(isset($sections) && ($sections['featured']['show'] ?? true))
            @include('frontend.sections.featured-products')
        @endif

        {{-- Best Selling Products Section --}}
        @if(isset($sections) && ($sections['best_selling']['show'] ?? true))
            @include('frontend.sections.best-selling')
        @endif

        {{-- Discount Products Section --}}
        @if(isset($sections) && ($sections['discount_products']['show'] ?? true))
            @include('frontend.sections.discount-products')
        @endif


        {{-- Collections Section --}}
        @if(isset($sections) && ($sections['collections']['show'] ?? true))
            @include('frontend.sections.collections')
        @endif

        {{-- Top Brands Section --}}
        @if(isset($sections) && ($sections['brands']['show'] ?? true))
            @include('frontend.sections.top-brands')
        @endif

        {{-- Service Section --}}
        @if(isset($sections) && ($sections['service']['show'] ?? true))
            @include('frontend.sections.service-section')
        @endif

{{-- Scroll to Top Button --}}

{{-- Quick View Modal --}}
@include('frontend.partials.quick-view-modal')

{{-- Login Modal --}}
@include('frontend.partials.login-modal')

{{-- Cart Sidebar --}}
@include('frontend.partials.cart-sidebar')

{{-- Wishlist Sidebar --}}
@include('frontend.partials.wishlist-sidebar')
@endsection

@push('styles')
@endpush

@push('scripts')
<script>
    // Initialize homepage functionality
    document.addEventListener('DOMContentLoaded', function() {
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            // Add hide class for smooth transition
            preloader.classList.add('hide');
            // Remove from DOM after transition
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 300);
        }

        // Initialize sliders
        if (typeof initializeSliders === 'function') {
            initializeSliders();
        }
        
        // Initialize cart functionality
        if (typeof initializeCart === 'function') {
            initializeCart();
        }
        
        // Initialize wishlist functionality
        if (typeof initializeWishlist === 'function') {
            initializeWishlist();
        }
        
        // Initialize quick view
        if (typeof initializeQuickView === 'function') {
            initializeQuickView();
        }
        
        // Initialize search functionality
        if (typeof initializeSearch === 'function') {
            initializeSearch();
        }
        
        // Initialize mobile menu
        if (typeof initializeMobileMenu === 'function') {
            initializeMobileMenu();
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sectionEndpoint = "{{ url('/home/section-products') }}";

        const setEnabled = (btn, enabled) => {
            if (!btn) return;
            btn.style.opacity = enabled ? '' : '.4';
            btn.style.pointerEvents = enabled ? '' : 'none';
        };

        const toast = () => {};

        const setLoadingState = (nav, wrapper, loading) => {
            if (!nav || !wrapper) return;

            if (loading) {
                if (!wrapper.dataset.fixedMinHeight) {
                    wrapper.dataset.fixedMinHeight = String(wrapper.offsetHeight || 0);
                }
                const fixed = parseInt(wrapper.dataset.fixedMinHeight || '0', 10);
                if (fixed > 0) {
                    wrapper.style.minHeight = `${fixed}px`;
                }
                wrapper.style.opacity = '0.35';
                wrapper.style.transition = 'opacity 160ms ease';
                nav.setAttribute('aria-busy', 'true');
                nav.dataset.loading = '1';
            } else {
                wrapper.style.opacity = '';
                wrapper.style.transition = '';
                wrapper.style.minHeight = '';
                nav.removeAttribute('aria-busy');
                delete nav.dataset.loading;
            }
        };

        const fadeReplace = async (wrapper, fnReplace) => {
            if (!wrapper) return;

            wrapper.style.transition = 'opacity 160ms ease';
            wrapper.style.opacity = '0';
            await new Promise((r) => setTimeout(r, 170));
            fnReplace();
            wrapper.style.opacity = '1';
            await new Promise((r) => setTimeout(r, 170));
            wrapper.style.transition = '';
        };

        const initRecommendedCarousel = (el) => {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.owlCarousel) return;
            const $el = window.jQuery(el);
            if (!$el.length) return;
            if ($el.hasClass('owl-loaded')) return;
            $el.owlCarousel({
                loop: false,
                margin: 16,
                responsiveClass: true,
                items: 4,
                dots: false,
                nav: true,
                autoplay: false,
                responsive: {
                    320: { items: 1 },
                    400: { items: 2 },
                    576: { items: 2 },
                    768: { items: 3 },
                    992: { items: 4 },
                    1200: { items: 4 },
                    1400: { items: 5 }
                }
            });
        };

        const replaceRecommendedItems = (wrapper, html) => {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.owlCarousel) {
                wrapper.innerHTML = html;
                return;
            }
            const $el = window.jQuery(wrapper);
            if ($el.hasClass('owl-loaded')) {
                $el.trigger('destroy.owl.carousel');
                $el.removeClass('owl-loaded');
                $el.find('.owl-stage-outer').children().unwrap();
                $el.removeData();
            }
            wrapper.innerHTML = html;
            initRecommendedCarousel(wrapper);
        };

        document.addEventListener('click', async function (e) {
            const btn = e.target && e.target.closest ? e.target.closest('button[data-home-section][data-direction]') : null;
            if (!btn) return;
            if (btn.style.pointerEvents === 'none') return;
            e.preventDefault();

            const section = btn.getAttribute('data-home-section');
            const direction = btn.getAttribute('data-direction');
            const nav = document.querySelector(`[data-home-section-nav="${section}"]`);
            const wrapper = document.querySelector(`[data-home-section-wrapper="${section}"]`);
            if (!nav || !wrapper) return;
            if (nav.dataset.loading === '1') return;

            const currentPage = Math.max(1, parseInt(nav.getAttribute('data-page') || '1', 10));
            const nextPage = direction === 'next' ? currentPage + 1 : currentPage - 1;
            if (nextPage < 1) return;

            const prevBtn = nav.querySelector('button[data-direction="prev"]');
            const nextBtn = nav.querySelector('button[data-direction="next"]');

            setEnabled(prevBtn, false);
            setEnabled(nextBtn, false);
            setLoadingState(nav, wrapper, true);

            try {
                const url = `${sectionEndpoint}?section=${encodeURIComponent(section)}&page=${encodeURIComponent(String(nextPage))}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                if (!data || typeof data.html !== 'string') {
                    return;
                }

                await fadeReplace(wrapper, () => {
                    if (section === 'recommended') {
                        replaceRecommendedItems(wrapper, data.html);
                    } else {
                        wrapper.innerHTML = data.html;
                    }
                });

                nav.setAttribute('data-page', String(data.page || nextPage));
                setEnabled(prevBtn, !!data.hasPrev);
                setEnabled(nextBtn, !!data.hasNext);
            } finally {
                setLoadingState(nav, wrapper, false);
                const pageNow = Math.max(1, parseInt(nav.getAttribute('data-page') || '1', 10));
                if (pageNow === currentPage) {
                    setEnabled(prevBtn, prevBtn && prevBtn.style.opacity !== '.4');
                    setEnabled(nextBtn, nextBtn && nextBtn.style.opacity !== '.4');
                }
            }
        });

        const initNavStates = () => {
            document.querySelectorAll('[data-home-section-nav]').forEach(async (nav) => {
                const section = nav.getAttribute('data-home-section-nav');
                if (!section) return;

                const prevBtn = nav.querySelector('button[data-direction="prev"]');
                const nextBtn = nav.querySelector('button[data-direction="next"]');

                // Start with whatever server rendered
                if (prevBtn) setEnabled(prevBtn, prevBtn.style.pointerEvents !== 'none');
                if (nextBtn) setEnabled(nextBtn, nextBtn.style.pointerEvents !== 'none');

                // Then sync with backend truth (fix cases like Recommended buttons being disabled incorrectly)
                try {
                    const currentPage = Math.max(1, parseInt(nav.getAttribute('data-page') || '1', 10));
                    const url = `${sectionEndpoint}?section=${encodeURIComponent(section)}&page=${encodeURIComponent(String(currentPage))}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data) return;
                    if (prevBtn) setEnabled(prevBtn, !!data.hasPrev);
                    if (nextBtn) setEnabled(nextBtn, !!data.hasNext);
                } catch (e) {
                    // silent
                }
            });
        };

        initNavStates();
    });
</script>
@endpush
