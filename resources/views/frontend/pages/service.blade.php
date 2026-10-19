@extends('frontend.layouts.app')

@section('title', 'Service')

@push('css')
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@100;200;300;400;500;600;700;800;900&family=Geist:wght@100;200;300;400;500;600;700;800;900&family=IBM+Plex+Mono:wght@100;200;300;400;500;600;700&family=IBM+Plex+Sans:wght@100;200;300;400;500;600;700&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:wght@200;300;400;500;600;700;800;900&family=PT+Serif:wght@400;700&family=Roboto+Slab:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&family=Shantell+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap"
  rel="stylesheet"
/>
<style id="base-styles">
  * {
    box-sizing: border-box;
  }
  :root {
    --primary: #1e5b99;
    --primary-foreground: #ffffff;
    --secondary: #f0f4f8;
    --secondary-foreground: #333333;
    --accent: #ff4a00;
    --accent-foreground: #ffffff;
    --background: #ffffff;
    --foreground: #333333;
    --muted: #f3f4f6;
    --border: #dddddd;
    --font-family-body:
      -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica,
      Arial, sans-serif;
  }
  body,
  html {
    margin: 0;
    padding: 0;
    font-family: var(--font-family-body);
    color: var(--foreground);
    background: var(--background);
    line-height: 1.5;
  }
  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    margin-top: 0;
    margin-bottom: 16px;
    font-weight: 600;
  }
  p {
    margin-top: 0;
    margin-bottom: 16px;
  }
  a {
    color: var(--primary);
    text-decoration: none;
  }
</style>

<style id="hero-styles">
  .hero {
    position: relative;
    background-color: #1a2a3a;
    color: #fff;
    padding: 60px 20px;
    display: flex;
    justify-content: center;
    overflow: hidden;
  }
  .hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.25;
    z-index: 0;
  }
  .hero-content {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 60px;
  }
  .hero-text {
    flex: 1 1 500px;
  }
  .hero-text h1 {
    font-size: 42px;
    line-height: 1.3;
    margin-bottom: 24px;
    color: #fffefe;
  }
  .hero-text p {
    font-size: 15px;
    color: #e0e0e0;
    margin-bottom: 20px;
    line-height: 1.6;
  }
  .btn-blue {
    background-color: var(--primary);
    color: var(--primary-foreground);
    border: none;
    padding: 14px 28px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .hero-form-wrapper {
    flex: 1 1 350px;
    max-width: 500px;
    background: transparent;
  }
  .hero-form-title {
    font-size: 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .hero-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .form-row {
    display: flex;
    gap: 12px;
  }
  .form-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    border: 1px solid transparent;
    border-radius: 2px;
    background: #fff;
    color: #333;
  }
  .btn-orange {
    background-color: var(--accent);
    color: var(--accent-foreground);
    border: none;
    padding: 14px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 2px;
    cursor: pointer;
    text-align: center;
  }
  .service-success {
    background: rgba(34, 197, 94, 0.18);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #ffffff;
    padding: 12px 14px;
    border-radius: 4px;
    font-size: 14px;
  }
  .service-error {
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #ffffff;
    padding: 10px 12px;
    border-radius: 4px;
    font-size: 13px;
  }
</style>

<style id="why-choose-styles">
  .why-choose {
    background-color: var(--primary);
    color: #fff;
    padding: 40px 20px;
    display: flex;
    justify-content: center;
  }
  .why-choose-inner {
    max-width: 1200px;
    width: 100%;
  }
  .why-choose-inner h2 {
    font-size: 20px;
    margin-bottom: 24px;
  }
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
  }
  .feature-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-style: italic;
  }
</style>

<style id="main-content-styles">
  .main-section {
    background-color: var(--muted);
    padding: 60px 20px;
    display: flex;
    justify-content: center;
  }
  .main-inner {
    max-width: 1200px;
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
  }
  .content-left {
    flex: 1 1 600px;
    min-width: 0;
  }
  .content-left h2,
  .content-left h3 {
    color: #111;
    margin-top: 36px;
    margin-bottom: 16px;
    font-size: 20px;
  }
  .content-left h2:first-child {
    margin-top: 0;
  }
  .content-left p {
    font-size: 14px;
    color: #444;
    line-height: 1.6;
    margin-bottom: 20px;
  }
  .list-check {
    list-style: none;
    padding: 0;
    margin: 0 0 32px 20px;
  }
  .list-check li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
    margin-bottom: 12px;
    font-weight: 600;
    font-style: italic;
    color: #111;
  }
  .list-check li .icon-wrapper {
    color: #000;
    margin-top: 2px;
  }
  .colored-heading {
    color: var(--accent) !important;
  }
  .bullet-list {
    padding-left: 20px;
    font-size: 14px;
    color: #444;
    line-height: 1.6;
    margin-bottom: 32px;
  }
  .bullet-list li {
    margin-bottom: 8px;
  }
  .accordion {
    border: 1px solid var(--border);
    border-radius: 4px;
    background: #fff;
    margin-top: 16px;
    overflow: hidden;
  }
  .accordion-item {
    border-bottom: 1px solid var(--border);
    padding: 16px;
    color: var(--accent);
    font-weight: 500;
    font-size: 14px;
    display: flex;
    justify-content: space-between;
    cursor: pointer;
  }
  .accordion-item:last-child {
    border-bottom: none;
  }

  .sidebar-right {
    flex: 0 1 340px;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
  .ad-banner {
    width: 100%;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
  }
  .ad-banner img {
    width: 100%;
    display: block;
  }
  .locations-box h3 {
    font-size: 18px;
    margin-bottom: 16px;
  }
  .locations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: var(--primary);
    padding: 16px;
    border-radius: 4px;
  }
  .location-item {
    background: #fff;
    padding: 12px;
    font-size: 13px;
    color: #333;
    text-align: center;
    border-radius: 2px;
    font-weight: 500;
    cursor: pointer;
  }
  .location-item strong {
    color: #000;
    font-weight: 700;
  }
</style>

<style id="products-styles">
  .products-section {
    padding: 60px 20px;
    display: flex;
    justify-content: center;
    background: #fff;
  }
  .products-inner {
    max-width: 1200px;
    width: 100%;
  }
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
  }
  .section-header h2 {
    font-size: 24px;
    margin: 0;
  }
  .btn-view-all {
    background: var(--accent);
    color: #fff;
    padding: 10px 20px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
  }
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 32px;
  }
  .product-card {
    position: relative;
    text-align: center;
    cursor: pointer;
  }
  .product-badge {
    position: absolute;
    top: -10px;
    right: 10px;
    background: var(--accent);
    color: #fff;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    z-index: 1;
  }
  .product-image {
    width: 100%;
    height: 200px;
    object-fit: contain;
    margin-bottom: 16px;
  }
  .product-category {
    font-size: 11px;
    color: #666;
    margin-bottom: 8px;
  }
  .product-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--accent);
    margin-bottom: 12px;
    min-height: 42px;
    line-height: 1.4;
  }
  .product-price {
    font-size: 14px;
  }
  .old-price {
    text-decoration: line-through;
    color: #999;
    font-size: 12px;
    margin-right: 8px;
  }
  .new-price {
    font-weight: 700;
    color: #000;
  }
</style>
@endpush

@push('scripts')
<script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var viewAll = document.querySelector('.btn-view-all');
    if (viewAll) {
      viewAll.addEventListener('click', function () {
        window.location.href = @json(route('products.page'));
      });
    }
  });
</script>
@endpush

@section('content')
<div class="hero">
  <img
    class="hero-bg"
    data-aspect-ratio="21:9"
    data-query="Industrial water purification system, blue tone, dark overlay"
    src="https://storage.googleapis.com/banani-generated-images/generated-images/42f64f66-77b3-47eb-8c2f-7084b0166f70.jpg"
  />
  <div class="hero-content">
    <div class="hero-text">
      <h1>Best Water Purifier<br />Service In Dhaka</h1>
      <p>
        Through GS Corporation Bd, you can connect with trusted water purifier
        services in Dhaka. We provide users with a broad network of knowledgeable
        and experienced water purifier specialists in Dhaka. Get the best drinking
        water possible by resolving your water purifier problems.
      </p>
      <p>
        We understand the importance of timely and efficient service, which is why
        we offer 24-72 hour service and 12/7 emergency support. Our technicians will arrive
        at your location fully equipped with the tools and parts needed to get your water
        purifier running smoothly again.
      </p>
      <div style="margin-top: 32px">
        <a class="btn-blue" href="tel:+8801616701701">Book Your Service Over the Phone</a>
      </div>
    </div>

    <div class="hero-form-wrapper">
      <div class="hero-form-title">
        <div
          style="
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
          "
        >
          <iconify-icon
            icon="lucide:settings-2"
            style="font-size: 20px; color: white"
          ></iconify-icon>
        </div>
        Book your online Service !!
      </div>

      <form class="hero-form" action="{{ route('service.store') }}" method="POST">
        @csrf

        @if(session('service_request_submitted'))
          <div class="service-success">{{ session('service_request_submitted') }}</div>
        @endif

        @if($errors->any())
          <div class="service-error">Please check the form and try again.</div>
        @endif

        <div class="form-row">
          <div style="flex: 1">
            <input class="form-input" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First Name*" required>
            @error('first_name')<div class="service-error" style="margin-top:8px;">{{ $message }}</div>@enderror
          </div>
          <div style="flex: 1">
            <input class="form-input" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last Name*" required>
            @error('last_name')<div class="service-error" style="margin-top:8px;">{{ $message }}</div>@enderror
          </div>
        </div>

        <div>
          <input class="form-input" type="text" name="phone" value="{{ old('phone') }}" placeholder="Number*" required>
          @error('phone')<div class="service-error" style="margin-top:8px;">{{ $message }}</div>@enderror
        </div>

        <div>
          <select class="form-input" name="service_type" required>
            <option value="">Please Select your service</option>
            <option value="Servicing" {{ old('service_type') === 'Servicing' ? 'selected' : '' }}>Servicing</option>
            <option value="Want to buy water purifier" {{ old('service_type') === 'Want to buy water purifier' ? 'selected' : '' }}>Want to buy water purifier</option>
            <option value="Installation" {{ old('service_type') === 'Installation' ? 'selected' : '' }}>Installation</option>
            <option value="Re-installation" {{ old('service_type') === 'Re-installation' ? 'selected' : '' }}>Re-installation</option>
            <option value="Filter Change" {{ old('service_type') === 'Filter Change' ? 'selected' : '' }}>Filter Change</option>
          </select>
          @error('service_type')<div class="service-error" style="margin-top:8px;">{{ $message }}</div>@enderror
        </div>

        <div>
          <textarea class="form-input" name="message" placeholder="Message" rows="4" style="height:100px;" required>{{ old('message') }}</textarea>
          @error('message')<div class="service-error" style="margin-top:8px;">{{ $message }}</div>@enderror
        </div>

        <button class="btn-orange" style="width: 100%" type="submit">Book Online</button>
      </form>
    </div>
  </div>
</div>

<div class="products-section">
  <div class="products-inner">
    <div class="section-header">
      <h2>Our Product</h2>
      <div class="btn-view-all" data-media-type="banani-button">View All</div>
    </div>

    <div class="products-grid">
      @forelse($products as $product)
        @php
          $imgPath = preg_replace('~^public/~', '', (string) optional($product->image)->image);
          $imgPath = ltrim($imgPath, '/');
          $imgUrl = !empty($imgPath) ? asset($imgPath) : asset('uploads/default/placeholder.png');
          $oldPrice = $product->old_price ?? null;
          $newPrice = $product->new_price ?? $product->price ?? null;
        @endphp
        <a class="product-card" href="{{ route('product', $product->slug) }}" data-media-type="banani-button">
          @if($oldPrice)
            <div class="product-badge">Sale!</div>
          @endif
          <img class="product-image" src="{{ $imgUrl }}" alt="{{ $product->name }}" />
          <div class="product-category">{{ optional($product->category)->name }}</div>
          <div class="product-title">{{ $product->name }}</div>
          <div class="product-price">
            @if($oldPrice)
              <span class="old-price">৳ {{ $oldPrice }}</span>
            @endif
            <span class="new-price">৳ {{ $newPrice }}</span>
          </div>
        </a>
      @empty
        <div>No products found.</div>
      @endforelse
    </div>
  </div>
</div>

<div class="clients-section">
  <div class="clients-inner">
    <h2>Our Client</h2>
    <div class="clients-grid">
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract with red color" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract with blue text" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract with pink icon" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract with teal colors" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract blue gradient" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract green element" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract with circle icon" src="https://app.banani.co/image-fallback.png" />
      <img class="client-logo" data-aspect-ratio="3:1" data-query="Corporate logo abstract solid blue text" src="https://app.banani.co/image-fallback.png" />
    </div>
  </div>
</div>

<div class="stats-banner">
  <div class="stats-inner">
    <div class="stat-item">CLIENT</div>
    <div class="stat-item">PROJECTS</div>
    <div class="stat-item">REPAIRING</div>
    <div class="stat-item">AWARDS</div>
  </div>
</div>

<div class="reviews-section">
  <div class="reviews-inner">
    <h2 class="reviews-title">What Our Customer Says.</h2>
    <p class="reviews-subtitle">
      See the outcomes of our most recent Client Satisfaction Survey, as
      well as valuable customer testimonies, to see how we create great
      working partnerships by knowing individual needs and having market
      knowledge
    </p>

    <div class="reviews-grid-wrapper">
      <div class="nav-btn" data-media-type="banani-button">
        <iconify-icon icon="lucide:chevron-left" style="font-size: 20px"></iconify-icon>
      </div>
      <div class="reviews-grid">
        <div class="review-card">
          <div class="google-icon">G</div>
          <div class="review-header">
            <img src="https://storage.googleapis.com/banani-avatars/avatar%2Ffemale%2F25-35%2FSouth%20Asian%2F1" class="review-avatar" />
            <div class="review-meta">
              <h4 class="review-name">Tonny Tuj</h4>
              <p class="review-date">12:32 03 Jan 22</p>
            </div>
          </div>
          <div class="stars">★★★★★</div>
          <p class="review-text">Been a constant user of their service for over two years. Excellent</p>
        </div>

        <div class="review-card">
          <div class="google-icon">G</div>
          <div class="review-header">
            <div class="review-avatar placeholder bg-purple">S</div>
            <div class="review-meta">
              <h4 class="review-name">Saiful Anowar</h4>
              <p class="review-date">09:33 19 Mar 21</p>
            </div>
          </div>
          <div class="stars">★★★★★</div>
          <p class="review-text">Bangladesh no 1 water purifier. Good service, excellent quality product</p>
        </div>

        <div class="review-card">
          <div class="google-icon">G</div>
          <div class="review-header">
            <img src="https://storage.googleapis.com/banani-avatars/avatar%2Fmale%2F25-35%2FSouth%20Asian%2F1" class="review-avatar" />
            <div class="review-meta">
              <h4 class="review-name">Abu Taleb</h4>
              <p class="review-date">10:02 01 Mar 21</p>
            </div>
          </div>
          <div class="stars">★★★★★</div>
          <p class="review-text">Best water Purifier in bd, After sels service so good</p>
        </div>

        <div class="review-card">
          <div class="google-icon">G</div>
          <div class="review-header">
            <div class="review-avatar placeholder bg-blue">A</div>
            <div class="review-meta">
              <h4 class="review-name">Arafat Rahman</h4>
              <p class="review-date">12:13 24 Feb 21</p>
            </div>
          </div>
          <div class="stars">★★★★★</div>
          <p class="review-text">GS Corporation Bd is one of the best purifier company ...they provide</p>
        </div>
      </div>
      <div class="nav-btn" data-media-type="banani-button">
        <iconify-icon icon="lucide:chevron-right" style="font-size: 20px"></iconify-icon>
      </div>
    </div>
  </div>
</div>

@endsection
