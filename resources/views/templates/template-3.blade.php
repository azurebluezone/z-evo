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
  <style>
    :root {
      --primary-color: #ff0000;
      --secondary-color: #008000;
      --accent-color: #FFD700;
      --text-color: #333333;
      --light-bg: #f8f9fa;
      --dark-bg: #343a40;
    }
    body { font-family: 'Hind Siliguri', sans-serif; overflow-x: hidden; color: var(--text-color); }
    
    /* Hero Section */
    .hero-section {
      position: relative;
      background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('{{ $landingPage->image_url ? asset(preg_replace("/^public\//", "", $landingPage->image_url)) : "https://via.placeholder.com/1200x800" }}');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 80px 0;
      color: white;
      text-align: center;
    }
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.4);
      z-index: 1;
    }
    .hero-content { position: relative; z-index: 2; }
    .logo { max-height: 80px; margin-bottom: 30px; filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.5)); }
    .hero-title { font-weight: bold; font-size: 2.5rem; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); }
    .hero-subtitle { font-size: 1.2rem; margin-bottom: 30px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5); }
    .price-text { font-size: 1.5rem; font-weight: bold; color: var(--accent-color); }
    .original-price { color: var(--primary-color); font-size: 1.5rem; text-decoration: line-through; }
    .current-price { color: var(--secondary-color); font-size: 2rem; font-weight: bold; }
    .cta-button {
      background-color: var(--primary-color);
      color: white;
      font-weight: bold;
      padding: 12px 30px;
      border-radius: 5px;
      border: none;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      display: inline-block;
      margin: 20px auto 0;
      text-decoration: none;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .cta-button:hover {
      background-color: #cc0000;
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
      color: white;
    }
    .wave-divider {
      position: absolute; bottom: 0; left: 0;
      width: 100%; overflow: hidden; line-height: 0; z-index: 2;
    }
    .wave-divider svg { position: relative; display: block; width: calc(100% + 1.3px); height: 65px; }
    .wave-divider .shape-fill { fill: #FFFFFF; }
    
    /* Price Section */
    .price-section { background: white; padding: 40px 0; text-align: center; }
    .free-delivery { font-size: 1.8rem; font-weight: bold; margin-bottom: 20px; position: relative; display: inline-block; }
    .free-delivery::after {
      content: ""; position: absolute; bottom: -5px; left: 0;
      width: 100%; height: 2px;
      background-color: var(--primary-color);
      background-image: url("data:image/svg+xml,%3Csvg width='40' height='12' viewBox='0 0 40 12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 6c2-2 5-2 7 0s5 2 7 0 5-2 7 0 5 2 7 0 5 2 7 0' fill='%23ff0000' fill-opacity='1' fill-rule='evenodd'/%3E%3C/svg%3E");
      background-size: 40px 12px;
    }
    
    /* Product Slider */
    .product-slider {
      padding: 60px 0;
      position: relative;
      background: linear-gradient(45deg, #343a40, #1a1e21);
    }
    .swiper { width: 100%; padding-top: 50px; padding-bottom: 50px; }
    .swiper-slide {
      background-position: center; background-size: cover;
      width: 300px; height: 300px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 15px 50px rgba(0,0,0,0.2);
      overflow: hidden;
    }
    .swiper-slide img { display: block; width: 100%; height: 100%; object-fit: cover; }
    
    /* Features Section */
    .feature-section { padding: 60px 0; background-color: white; }
    .section-title { text-align: center; font-weight: bold; font-size: 2rem; margin-bottom: 50px; position: relative; }
    .section-title::after {
      content: ""; position: absolute; bottom: -15px; left: 50%;
      transform: translateX(-50%);
      width: 80px; height: 3px;
      background-color: var(--primary-color);
    }
    .feature-box {
      border: 2px solid var(--primary-color);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
      background: white;
      transition: all 0.3s ease;
      height: 100%;
    }
    .feature-box:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
    .feature-box img { width: 100%; border-radius: 8px; margin-bottom: 15px; }
    .feature-box h3 { color: var(--primary-color); font-weight: bold; font-size: 1.5rem; margin-bottom: 15px; text-align: center; }
    
    /* Benefits Section */
    .benefits-section { padding: 60px 0; background-color: var(--light-bg); position: relative; }
    .benefits-list { list-style: none; padding: 0; position: relative; z-index: 1; }
    .benefits-list li {
      display: flex; align-items: flex-start;
      margin-bottom: 20px;
      background: white;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: transform 0.3s ease;
    }
    .benefits-list li:hover { transform: translateX(10px); }
    .benefits-list li i { color: var(--primary-color); margin-right: 15px; font-size: 24px; flex-shrink: 0; }
    
    /* Gallery Section */
    .gallery-section { padding: 60px 0; background-color: white; }
    .gallery-main { margin-bottom: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .gallery-main .swiper-slide { height: 400px; width: 100%; }
    .gallery-thumbs { height: 100px; }
    .gallery-thumbs .swiper-slide { height: 100%; width: 100px; opacity: 0.4; cursor: pointer; }
    .gallery-thumbs .swiper-slide-thumb-active { opacity: 1; }
    
    /* Order Section */
    .order-section { padding: 60px 0; background-color: var(--light-bg); }
    .order-form { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .package-option {
      display: flex; align-items: center;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 10px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
    }
    .package-option:hover { border-color: var(--primary-color); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .package-option img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; margin-right: 15px; }
    .submit-btn {
      background-color: var(--primary-color);
      color: white;
      font-weight: bold;
      padding: 15px 30px;
      border: none;
      border-radius: 5px;
      font-size: 1.2rem;
      width: 100%;
      transition: all 0.3s ease;
    }
    .submit-btn:hover { background-color: #cc0000; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,0,0,0.2); }
    
    /* Footer */
    footer { background-color: var(--dark-bg); color: white; padding: 40px 0 20px; }
    footer h5 { font-weight: bold; margin-bottom: 20px; }
    .social-icons { display: flex; gap: 15px; }
    .social-icons a {
      display: inline-flex; align-items: center; justify-content: center;
      width: 40px; height: 40px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      transition: all 0.3s ease;
    }
    .social-icons a:hover { background: var(--primary-color); transform: translateY(-3px); }
    .copyright { text-align: center; padding-top: 20px; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); }
    
    @media (max-width: 768px) {
      .hero-title { font-size: 1.5rem; }
      .hero-subtitle { font-size: 1rem; }
      .free-delivery { font-size: 1.5rem; }
      .original-price { font-size: 1.2rem; }
      .current-price { font-size: 1.5rem; }
      .section-title { font-size: 1.5rem; }
      .gallery-main .swiper-slide { height: 300px; }
      .order-form { padding: 20px; }
    }
  </style>
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
  @endphp

  <!-- Hero Section -->
  <section class="hero-section">
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
      
      <a href="#order-now" class="cta-button" data-aos="fade-up">{{ $landingPage->cta_text ?? 'অর্ডার করুন' }}</a>
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
      <a href="#order-now" class="btn btn-danger btn-lg mt-3" data-aos="fade-up" data-aos-delay="300">{{ $landingPage->cta_text ?? 'অর্ডার করতে ক্লিক করুন' }}</a>
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
  @if(count($features) > 0)
  <section class="feature-section" id="features">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">পণ্যের বৈশিষ্ট্য</h2>
      <div class="row">
        @foreach($features as $i => $feature)
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="{{ $i * 100 }}">
          <div class="feature-box">
            @if(isset($feature['image']))
            <img src="{{ asset(preg_replace('/^public\//', '', $feature['image'])) }}" alt="Feature">
            @endif
            <h3>{{ $feature['title'] ?? 'Feature ' . ($i + 1) }}</h3>
            <p>{{ $feature['description'] ?? '' }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <!-- Benefits Section -->
  @if(count($benefits) > 0)
  <section class="benefits-section" id="benefits">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">আমাদের কাছ থেকে কেন কিনবেন</h2>
      <div class="row">
        <div class="col-lg-12">
          <ul class="benefits-list">
            @foreach($benefits as $benefit)
            <li data-aos="fade-right">
              <i class="fas fa-thumbs-up"></i>
              <span>{{ $benefit }}</span>
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
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
      
      // Product slider
      new Swiper('.product-swiper', {
        effect: 'coverflow',
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: 'auto',
        coverflowEffect: {
          rotate: 50,
          stretch: 0,
          depth: 100,
          modifier: 1,
          slideShadows: true
        },
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
      });
      
      // Gallery
      if(document.querySelector('.gallery-thumbs')) {
        const galleryThumbs = new Swiper('.gallery-thumbs', {
          spaceBetween: 10,
          slidesPerView: 4,
          freeMode: true,
          watchSlidesProgress: true
        });
        new Swiper('.gallery-main', {
          spaceBetween: 10,
          navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
          thumbs: { swiper: galleryThumbs }
        });
      }
    });
  </script>
</body>
</html>
