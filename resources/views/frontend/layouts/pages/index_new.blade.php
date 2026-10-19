@extends('frontend.layouts.master')
@section('title', 'Home')

@push('css')
@endpush

@section('content')
<!-- Hero Section -->
<section class="sv-hero">
    <div class="sv-hero-content">
        @if(isset($sliders) && $sliders->count() > 0)
            @foreach($sliders as $key => $slider)
                <div class="sv-hero-slide {{ $key === 0 ? 'active' : '' }}" 
                     style="background-image: url('{{ asset($slider->image) }}');">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="sv-hero-text-content">
                                    <div class="sv-hero-brand">{{ $slider->brand ?? 'Sunnah Heaven' }}</div>
                                    <h1 class="sv-hero-title">{{ $slider->title }}</h1>
                                    <p class="sv-hero-description">{{ $slider->description ?? 'Premium Islamic Fashion Collection' }}</p>
                                    <a href="{{ $slider->link ?? '#' }}" class="sv-hero-cta">SHOP NOW</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Fallback Hero -->
            <div class="sv-hero-slide active" style="background-image: url('{{ asset('public/uploads/banner/1755415132Web-Banner-(Sunnah-Heaven).jpg') }}');">
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
                <div class="col-12 text-center">
                    <h2 class="sv-section-title">TOP CATEGORIES</h2>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="product_sliders">
                    @foreach ($homeproducts as $homecat)
                        @if(isset($homecat->home_page) && $homecat->home_page == 1)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="sv-boss-product-card">
                                <div class="sv-boss-product-image-container">
                                    <img src="{{ asset($homecat->image ? $homecat->image->image : '') }}" 
                                         alt="{{ $homecat->name }}" 
                                         class="sv-boss-product-image primary">
                                    <img src="{{ asset($homecat->image ? $homecat->image->image : '') }}" 
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
                <div class="col-12 text-center">
                    <h2 class="sv-section-title">Hot Deal</h2>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="product_slider owl-carousel">
                    @foreach ($hotdeal_top as $key => $value)
                        <div class="sv-boss-product-card">
                            <div class="sv-boss-product-image-container">
                                <img src="{{ asset($value->image ? $value->image->image : '') }}" 
                                     alt="{{ $value->name }}" 
                                     class="sv-boss-product-image primary">
                                @if($value->images && $value->images->count() > 1)
                                    <img src="{{ asset($value->images[1]->image) }}" 
                                         alt="{{ $value->name }} Alternate" 
                                         class="sv-boss-product-image secondary">
                                @else
                                    <img src="{{ asset($value->image ? $value->image->image : '') }}" 
                                         alt="{{ $value->name }} Alternate" 
                                         class="sv-boss-product-image secondary">
                                @endif
                                
                                <div class="sv-boss-product-actions">
                                    <a href="#" class="sv-boss-action-btn">
                                        <i class="far fa-heart"></i>
                                    </a>
                                    <a href="#" class="sv-boss-action-btn">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="sv-boss-product-info">
                                <h3 class="sv-boss-product-title">
                                    <a href="{{ route('product', $value->slug) }}" style="color: inherit; text-decoration: none;">
                                        {{ Str::limit($value->name, 80) }}
                                    </a>
                                </h3>
                                <div class="sv-boss-product-price">
                                    @if ($value->old_price)
                                        <span class="sv-boss-product-original-price">৳ {{ $value->old_price }}</span>
                                    @endif
                                    <span>৳ {{ $value->new_price }}</span>
                                </div>
                                <button class="btn sv-boss-add-to-cart" data-product-id="{{ $value->id }}">
                                    <i class="fas fa-shopping-cart me-2"></i>ADD TO CART
                                </button>
                                <button class="btn sv-boss-buy-now" data-product-id="{{ $value->id }}">
                                    <i class="fas fa-bolt me-2"></i>BUY NOW
                                </button>
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
                    <div class="col-12 text-center">
                        <h2 class="sv-section-title">{{ $homecat->name }}</h2>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="product_sliders">
                        @foreach ($homecat->products as $key => $value)
                           <div class="sv-boss-product-card">
                                <div class="sv-boss-product-image-container">
                                    <img src="{{ asset($value->image ? $value->image->image : '') }}" 
                                         alt="{{ $value->name }}" 
                                         class="sv-boss-product-image primary">
                                    @if($value->images && $value->images->count() > 1)
                                        <img src="{{ asset($value->images[1]->image) }}" 
                                             alt="{{ $value->name }} Alternate" 
                                             class="sv-boss-product-image secondary">
                                    @else
                                        <img src="{{ asset($value->image ? $value->image->image : '') }}" 
                                             alt="{{ $value->name }} Alternate" 
                                             class="sv-boss-product-image secondary">
                                    @endif
                                    
                                    <div class="sv-boss-product-actions">
                                        <a href="#" class="sv-boss-action-btn">
                                            <i class="far fa-heart"></i>
                                        </a>
                                        <a href="#" class="sv-boss-action-btn">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="sv-boss-product-info">
                                    <h3 class="sv-boss-product-title">
                                        <a href="{{ route('product', $value->slug) }}" style="color: inherit; text-decoration: none;">
                                            {{ Str::limit($value->name, 80) }}
                                        </a>
                                    </h3>
                                    <div class="sv-boss-product-price">
                                        @if ($value->old_price)
                                            <span class="sv-boss-product-original-price">৳ {{ $value->old_price }}</span>
                                        @endif
                                        <span>৳ {{ $value->new_price }}</span>
                                    </div>
                                    <button class="btn sv-boss-add-to-cart" data-product-id="{{ $value->id }}">
                                        <i class="fas fa-shopping-cart me-2"></i>ADD TO CART
                                    </button>
                                    <button class="btn sv-boss-buy-now" data-product-id="{{ $value->id }}">
                                        <i class="fas fa-bolt me-2"></i>BUY NOW
                                    </button>
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
        <div class="col-12 text-center">
            <h2 class="sv-section-title">Hot Deal Brand</h2>
        </div>
        <div class="row">
            @foreach($brands as $brand)
                <div class="col-6 col-md-4 col-lg-2 mb-2">
                    <div class="card brand-card d-flex align-items-center justify-content-center">
                        <img src="{{ $brand->image }}" class="card-img-top brand-image" alt="{{ $brand->name }}">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

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
    });
</script>
@endpush
