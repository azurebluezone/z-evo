<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $landingPage->title ?? 'Premium Product' }}</title>
  @include('frontend.partials.event-layer-head')
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Swiper CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- AOS Animation -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('frontend/landing/template-3.css') }}?v={{ filemtime(public_path('frontend/landing/template-3.css')) }}">
    <link rel="stylesheet" href="{{ asset('frontend/landing/responsive.css') }}?v={{ filemtime(public_path('frontend/landing/responsive.css')) }}">
</head>
<body>
  @include('frontend.partials.event-layer-body')
  @php
    $extra = $landingPage->extra_fields ?? [];
    $oldPrice = $extra['old_price'] ?? null;
    $newPrice = $extra['new_price'] ?? null;
    $features = $extra['features'] ?? [];
    $benefits = $extra['benefits'] ?? [];
    $galleryImages = $extra['gallery_images'] ?? [];
    $contactPhone = $extra['contact_phone'] ?? null;
    $productId = $extra['product_id'] ?? null;
    $showFeatures = $extra['show_features'] ?? true;
    $showBenefits = $extra['show_benefits'] ?? true;
    $showCta = $extra['show_cta'] ?? true;
  @endphp

  <!-- Hero Section -->
  <section class="hero-section" style="--hero-image: url('{{ $landingPage->image_url ? asset(preg_replace('/^public\//', '', $landingPage->image_url)) : 'https://via.placeholder.com/1200x800' }}');">
    <div class="hero-content container">
      @if($landingPage->image_url)
        <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}" class="logo mb-4">
      @endif
      
      @if($extra['video_url'] ?? null)
      <div class="hero-video-container mb-4" data-aos="fade-up">
        <div class="ratio ratio-16x9 shadow-lg rounded overflow-hidden">
          <iframe src="{{ $extra['video_url'] }}" title="{{ $landingPage->title }}" allowfullscreen></iframe>
        </div>
      </div>
      @endif
      
      <h1 class="hero-title" data-aos="fade-up">{{ $landingPage->title }}</h1>
      <p class="hero-subtitle" data-aos="fade-up">{{ $landingPage->subtitle ?? 'আসল চামড়ার তৈরি, দীর্ঘস্থায়ী এবং স্টাইলিশ' }}</p>
      
      @if($oldPrice || $newPrice)
      <div class="price-container" data-aos="fade-up">
        @if($newPrice)
        <p class="price-text">স্পেশাল অফার মূল্য</p>
        @endif
        @if($oldPrice)
        <p class="original-price">রেগুলার প্রাইসঃ {{ $oldPrice }} টাকা</p>
        @endif
        @if($newPrice)
        <p class="current-price">অফার প্রাইসঃ {{ $newPrice }} টাকা!</p>
        @endif
      </div>
      @endif
      
      @if($showCta)
      <a href="#order-now" class="cta-button" data-aos="fade-up">{{ $landingPage->cta_text ?? 'অর্ডার করুন' }}</a>
      @endif
    </div>
    <div class="wave-divider">
      <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
        <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
      </svg>
    </div>
  </section>

  <!-- Price Section -->
  @if($oldPrice || $newPrice)
  <section class="price-section" id="price">
    <div class="container">
      @if($newPrice)
      <h2 class="free-delivery" data-aos="fade-up">সীমিত সময়ের জন্য থাকছে ফ্রি ডেলিভারি!!</h2>
      @endif
      @if($oldPrice)
      <div class="original-price" data-aos="fade-up" data-aos-delay="100">রেগুলার প্রাইসঃ {{ $oldPrice }} টাকা</div>
      @endif
      @if($newPrice)
      <div class="offer-price" data-aos="fade-up" data-aos-delay="200">অফার প্রাইসঃ {{ $newPrice }} টাকা!</div>
      @if($showCta)
      <a href="#order-now" class="btn btn-danger btn-lg mt-3" data-aos="fade-up" data-aos-delay="300">{{ $landingPage->cta_text ?? 'অর্ডার করতে ক্লিক করুন' }}</a>
      @endif
      @endif
    </div>
  </section>
  @endif

  <!-- Product Slider -->
  @if(count($features) > 0 || $landingPage->description)
  <section class="product-slider" id="product">
    <div class="container">
      <h2 class="section-title text-white" data-aos="fade-up">{{ $landingPage->subtitle ?? 'প্রিমিয়াম কোয়ালিটি' }}</h2>
      <div class="swiper product-swiper" data-aos="fade-up">
        <div class="swiper-wrapper">
          @if($landingPage->image_url)
            <div class="swiper-slide"><img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}"></div>
          @endif
          @foreach(array_slice($galleryImages, 0, 5) as $img)
            <div class="swiper-slide"><img src="{{ asset(preg_replace('/^public\//', '', $img)) }}" alt="Gallery"></div>
          @endforeach
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
      </div>
    </div>
  </section>
  @endif

  <!-- Features Section -->
  @if($showFeatures && count($features) > 0)
  <section class="feature-section" id="features">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">পণ্যের বৈশিষ্ট্য</h2>
      <div class="row">
        @foreach($features as $i => $feature)
        @php
          $featureTitle = is_array($feature) ? ($feature['title'] ?? 'Feature ' . ($i + 1)) : (string) $feature;
          $featureText = is_array($feature) ? ($feature['description'] ?? '') : '';
          $featureImage = is_array($feature) ? ($feature['image'] ?? null) : null;
        @endphp
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="{{ $i * 100 }}">
          <div class="feature-box">
            @if($featureImage)
            <img src="{{ asset(preg_replace('/^public\//', '', $featureImage)) }}" alt="Feature">
            @endif
            <h3>{{ $featureTitle }}</h3>
            @if($featureText !== '')
            <p>{{ $featureText }}</p>
            @endif
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <!-- Benefits Section -->
  @if($showBenefits && count($benefits) > 0)
  <section class="benefits-section" id="benefits">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">আমাদের কাছ থেকে কেন কিনবেন</h2>
      <div class="row">
        <div class="col-lg-12">
          <ul class="benefits-list">
            @foreach($benefits as $benefit)
            @php
              $benefitTitle = is_array($benefit) ? ($benefit['title'] ?? '') : (string) $benefit;
              $benefitText = is_array($benefit) ? ($benefit['text'] ?? ($benefit['description'] ?? '')) : '';
            @endphp
            <li data-aos="fade-right">
              <i class="fas fa-thumbs-up"></i>
              <span>
                {{ $benefitTitle }}
                @if($benefitText !== '')
                <small class="d-block">{{ $benefitText }}</small>
                @endif
              </span>
            </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </section>
  @endif

  <!-- Gallery Section -->
  @if(count($galleryImages) > 0)
  <section class="gallery-section" id="gallery">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">প্রোডাক্ট গ্যালারি</h2>
      <div class="swiper gallery-main" data-aos="fade-up">
        <div class="swiper-wrapper">
          @foreach($galleryImages as $img)
            <div class="swiper-slide"><img src="{{ asset(preg_replace('/^public\//', '', $img)) }}" alt="Gallery"></div>
          @endforeach
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
      </div>
      <div class="swiper gallery-thumbs" data-aos="fade-up">
        <div class="swiper-wrapper">
          @foreach($galleryImages as $img)
            <div class="swiper-slide"><img src="{{ asset(preg_replace('/^public\//', '', $img)) }}" alt="Thumb"></div>
          @endforeach
        </div>
      </div>
    </div>
  </section>
  @endif

  <!-- Order Section -->
  <section class="order-section" id="order-now">
    <div class="container">
      <div class="order-form" data-aos="fade-up">
        <h2 class="mb-4">অর্ডার করতে নিচের ফরমটি পূরণ করে "Place Order" ট্যাপ করুন।</h2>
        
        <form method="POST" action="{{ $landingPage->cta_link ?? '#' }}">
          @csrf
          @if($productId)
            <input type="hidden" name="product_id" value="{{ $productId }}">
          @endif
          @if(!empty($extra['campaign_id']))
            <input type="hidden" name="campaign_id" value="{{ $extra['campaign_id'] }}">
          @endif
          
          <div class="row mb-4">
            <div class="col-md-12">
              <h3 class="mb-3">প্যাকেজ নির্বাচন করুন</h3>
              @php $packages = $extra['packages'] ?? [['name' => $landingPage->title, 'price' => $newPrice]]; @endphp
              @foreach($packages as $i => $pkg)
              <div class="package-option">
                <input type="radio" name="package" id="package{{ $i }}" value="{{ $i }}" @if($i === 0) checked @endif class="me-2">
                @if(isset($pkg['image']))
                <img src="{{ asset(preg_replace('/^public\//', '', $pkg['image'])) }}" alt="{{ $pkg['name'] }}">
                @endif
                <div class="package-details"><h5>{{ $pkg['name'] ?? 'Package ' . ($i + 1) }} × 1</h5></div>
                <div class="package-price">{{ isset($pkg['price']) ? number_format($pkg['price'], 2) : '' }}৳</div>
              </div>
              @endforeach
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-4">
              <h4 class="mb-3">Billing details</h4>
              <div class="mb-3">
                <label class="form-label">নাম *</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">ফোন নাম্বার *</label>
                <input type="tel" class="form-control" name="phone" required>
              </div>
              <div class="mb-3">
                <label class="form-label">ঠিকানা *</label>
                <textarea class="form-control" name="address" rows="3" required></textarea>
              </div>
            </div>
            <div class="col-md-6 mb-4">
              <h4 class="mb-3">Order Summary</h4>
              <div class="item mb-2"><span class="fw-bold">Product:</span> {{ $landingPage->title }}</div>
              <div class="item mb-2"><span class="fw-bold">Total:</span> <span class="text-danger fw-bold">{{ $newPrice ? $newPrice . '৳' : '' }}</span></div>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="cash-delivery" checked>
                <label class="form-check-label" for="cash-delivery">ক্যাশ-অন ডেলিভারি</label>
              </div>
            </div>
          </div>
          
          <button type="submit" class="submit-btn">Place Order{{ $newPrice ? ' ' . $newPrice . '৳' : '' }}</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-4">
          @if($landingPage->image_url)
          <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}" class="mb-3" style="height: 50px;">
          @endif
          <p>{{ $landingPage->description ?? '' }}</p>
        </div>
        <div class="col-md-4 mb-4">
          <h5>যোগাযোগ করুন</h5>
          @if($contactPhone)
          <p><i class="fas fa-phone me-2"></i>{{ $contactPhone }}</p>
          @endif
          <p><i class="fas fa-envelope me-2"></i>{{ $extra['email'] ?? 'info@example.com' }}</p>
        </div>
        <div class="col-md-4 mb-4">
          <h5>আমাদের অনুসরণ করুন</h5>
          <div class="social-icons">
            @if($extra['facebook'] ?? null)
            <a href="{{ $extra['facebook'] }}"><i class="fab fa-facebook-f"></i></a>
            @endif
            @if($extra['instagram'] ?? null)
            <a href="{{ $extra['instagram'] }}"><i class="fab fa-instagram"></i></a>
            @endif
            @if($extra['youtube'] ?? null)
            <a href="{{ $extra['youtube'] }}"><i class="fab fa-youtube"></i></a>
            @endif
          </div>
        </div>
      </div>
      <div class="copyright"><p>&copy; {{ date('Y') }} {{ $landingPage->title }}. সর্বস্বত্ব সংরক্ষিত।</p></div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  
  <script src="{{ asset('frontend/landing/template-3.js') }}?v={{ filemtime(public_path('frontend/landing/template-3.js')) }}"></script>
</body>
</html>