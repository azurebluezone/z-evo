<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $landingPage->title ?? 'Herbal Health Product' }}</title>
  @include('frontend.partials.event-layer-head')
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Swiper CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- AOS Animation -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <!-- Custom CSS -->
  <style>
    :root {
      --primary-color: #27ae60;
      --secondary-color: #2ecc71;
      --accent-color: #f39c12;
      --text-color: #2c3e50;
      --light-bg: #e8f5e8;
      --dark-bg: #1e3a2e;
    }

    body {
      font-family: 'Hind Siliguri', sans-serif;
      overflow-x: hidden;
      color: var(--text-color);
    }

    .hero-section {
      position: relative;
      @php
        $heroImage = $landingPage->image_url
          ? asset(preg_replace('/^public\//', '', $landingPage->image_url))
          : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
      @endphp
      background-image: linear-gradient(rgba(39, 174, 96, 0.8), rgba(46, 204, 113, 0.8)), url('{{ $heroImage }}');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 80px 0;
      color: white;
    }

    .hero-content {
      position: relative;
      z-index: 2;
    }

    .hero-title {
      font-weight: bold;
      font-size: 3rem;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .hero-subtitle {
      font-size: 1.3rem;
      margin-bottom: 30px;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }

    .cta-button {
      background: linear-gradient(135deg, var(--accent-color), #e67e22);
      color: white;
      padding: 15px 40px;
      border-radius: 50px;
      font-size: 1.1rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
      border: none;
    }

    .cta-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(243, 156, 18, 0.4);
      color: white;
    }

    .product-section {
      padding: 80px 0;
      background: var(--light-bg);
    }

    .product-card {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .product-card:hover {
      transform: translateY(-10px);
    }

    .product-image {
      width: 200px;
      height: 200px;
      object-fit: cover;
      border-radius: 50%;
      margin: 0 auto 30px;
      border: 5px solid var(--primary-color);
    }

    .price-tag {
      background: var(--primary-color);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      font-size: 1.2rem;
      font-weight: bold;
      display: inline-block;
      margin: 20px 0;
    }

    .old-price {
      text-decoration: line-through;
      color: #999;
      font-size: 1rem;
    }

    .benefits-section {
      padding: 80px 0;
      background: white;
    }

    .benefit-item {
      background: var(--light-bg);
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      border-left: 5px solid var(--primary-color);
    }

    .benefit-icon {
      font-size: 2.5rem;
      color: var(--primary-color);
      margin-bottom: 20px;
    }

    .testimonials-section {
      padding: 80px 0;
      background: var(--light-bg);
    }

    .testimonial-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      text-align: center;
      margin-bottom: 30px;
    }

    .testimonial-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin: 0 auto 20px;
      background: var(--primary-color);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2rem;
    }

    .order-section {
      padding: 80px 0;
      background: var(--dark-bg);
      color: white;
    }

    .order-form {
      background: rgba(255,255,255,0.1);
      border-radius: 20px;
      padding: 40px;
      backdrop-filter: blur(10px);
    }

    .form-control {
      background: rgba(255,255,255,0.9);
      border: none;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .form-control:focus {
      background: white;
      box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
    }

    .footer {
      background: var(--dark-bg);
      color: white;
      padding: 40px 0;
      text-align: center;
    }

    @media (max-width: 768px) {
      .hero-title {
        font-size: 2rem;
      }

      .hero-subtitle {
        font-size: 1rem;
      }

      .cta-button {
        padding: 12px 30px;
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>
  @include('frontend.partials.event-layer-body')
  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <div class="hero-content" data-aos="fade-right">
            <h1 class="hero-title">{{ $landingPage->title ?? 'প্রাকৃতিক আয়ুর্বেদিক সাপ্লিমেন্ট' }}</h1>
            <p class="hero-subtitle">{{ $landingPage->subtitle ?? 'স্বাস্থ্য ও সুস্থতার জন্য ১০০% প্রাকৃতিক উপাদান' }}</p>
            <a href="#order" class="cta-button">
              <i class="fas fa-shopping-cart me-2"></i>{{ $landingPage->cta_text ?? 'এখনই অর্ডার করুন' }}
            </a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="text-center" data-aos="fade-left">
            @if($landingPage->image_url)
              <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}" class="img-fluid rounded" style="max-height: 400px;">
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Product Section -->
  <section class="product-section" id="product">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="product-card" data-aos="zoom-in">
            @if($landingPage->image_url)
              <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}" class="product-image">
            @endif
            <h2 class="mb-3">{{ $landingPage->title ?? 'আয়ুর্বেদিক সাপ্লিমেন্ট' }}</h2>
            @if($landingPage->description)
              <div class="mb-4">{!! $landingPage->description !!}</div>
            @endif

            @php
              $oldPrice = $landingPage->extra_fields['old_price'] ?? null;
              $newPrice = $landingPage->extra_fields['new_price'] ?? null;
            @endphp
            @if($oldPrice || $newPrice)
            <div class="price-tag">
              @if($oldPrice)
                <span class="old-price">৳{{ number_format($oldPrice) }}</span>
              @endif
              @if($newPrice)
                <span class="ms-2">৳{{ number_format($newPrice) }}</span>
              @endif
            </div>
            @endif

            <a href="#order" class="cta-button">
              <i class="fas fa-heart me-2"></i>{{ $landingPage->cta_text ?? 'স্বাস্থ্য বাড়ান' }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Benefits Section -->
  @php $showBenefits = $landingPage->extra_fields['show_benefits'] ?? true; @endphp
  @if($showBenefits)
  <section class="benefits-section">
    <div class="container">
      <div class="row">
        <div class="col-12 text-center mb-5">
          <h2 class="fw-bold">কেন এই প্রোডাক্ট ব্যবহার করবেন?</h2>
        </div>
      </div>
      @php $benefits = $landingPage->extra_fields['benefits'] ?? [] @endphp
      @if(count($benefits) > 0)
      <div class="row">
        @foreach($benefits as $i => $benefit)
        <div class="col-lg-4 col-md-6">
          <div class="benefit-item" data-aos="fade-up" @if($i > 0) data-aos-delay="{{ $i * 100 }}" @endif>
            <div class="benefit-icon">
              <i class="{{ $benefit['icon'] ?? 'fas fa-leaf' }}"></i>
            </div>
            <h4>{{ $benefit['title'] ?? '' }}</h4>
            <p>{{ $benefit['text'] ?? '' }}</p>
          </div>
        </div>
        @endforeach
      </div>
      @else
      <div class="row">
        <div class="col-lg-4 col-md-6">
          <div class="benefit-item" data-aos="fade-up">
            <div class="benefit-icon"><i class="fas fa-leaf"></i></div>
            <h4>১০০% প্রাকৃতিক</h4>
            <p>কোন কেমিক্যাল নেই, শুধু প্রাকৃতিক উপাদান</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="benefit-item" data-aos="fade-up" data-aos-delay="100">
            <div class="benefit-icon"><i class="fas fa-shield-alt"></i></div>
            <h4>নিরাপদ ও কার্যকর</h4>
            <p>বিজ্ঞানসম্মতভাবে পরীক্ষিত এবং নিরাপদ</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="benefit-item" data-aos="fade-up" data-aos-delay="200">
            <div class="benefit-icon"><i class="fas fa-clock"></i></div>
            <h4>দ্রুত ফলাফল</h4>
            <p>কয়েক সপ্তাহের মধ্যে ফলাফল দেখতে পাবেন</p>
          </div>
        </div>
      </div>
      @endif
    </div>
  </section>
  @endif

  <!-- Testimonials Section -->
  @php
    $showTestimonials = $landingPage->extra_fields['show_testimonials'] ?? false;
    $testimonials = $landingPage->extra_fields['testimonials'] ?? [];
  @endphp
  @if($showTestimonials && count($testimonials) > 0)
  <section class="testimonials-section">
    <div class="container">
      <div class="row">
        <div class="col-12 text-center mb-5">
          <h2 class="fw-bold">গ্রাহকদের মতামত</h2>
        </div>
      </div>
      <div class="row">
        @foreach(array_slice($testimonials, 0, 3) as $testimonial)
        <div class="col-lg-4 col-md-6">
          <div class="testimonial-card" data-aos="fade-up">
            <div class="testimonial-avatar">
              <i class="fas fa-user"></i>
            </div>
            <p class="mb-3">{{ $testimonial['comment'] ?? 'অসাধারণ প্রোডাক্ট!' }}</p>
            <h6 class="fw-bold">{{ $testimonial['name'] ?? 'গ্রাহক' }}</h6>
            <div class="text-warning">
              @php $rating = (int)($testimonial['rating'] ?? 5); @endphp
              @for($i = 1; $i <= 5; $i++)
                <i class="fas fa-star{{ $i <= $rating ? '' : '-o' }}"></i>
              @endfor
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <!-- Order Section -->
  <section class="order-section" id="order">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="order-form" data-aos="zoom-in">
            <h3 class="text-center mb-4">এখনই অর্ডার করুন</h3>
            <form action="{{ $landingPage->cta_link ?? route('cart.add') }}" method="POST">
              @csrf
              <input type="hidden" name="product_id" value="{{ $landingPage->extra_fields['product_id'] ?? '' }}">
              <div class="row">
                <div class="col-md-6">
                  <input type="text" class="form-control" name="name" placeholder="আপনার নাম" required>
                </div>
                <div class="col-md-6">
                  <input type="tel" class="form-control" name="phone" placeholder="মোবাইল নম্বর" required>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <input type="email" class="form-control" name="email" placeholder="ইমেইল">
                </div>
                <div class="col-md-6">
                  <input type="number" class="form-control" name="quantity" value="1" min="1" placeholder="পরিমাণ">
                </div>
              </div>
              <textarea class="form-control" name="address" rows="3" placeholder="ঠিকানা" required></textarea>
              <div class="text-center">
                <button type="submit" class="cta-button">
                  <i class="fas fa-shopping-cart me-2"></i>অর্ডার কনফার্ম করুন
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <p>&copy; {{ date('Y') }} {{ $landingPage->title ?? 'Herbal Health' }}. All rights reserved.</p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Swiper JS -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
  <!-- AOS Animation -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <script>
    AOS.init({ duration: 1000, once: true });
  </script>
</body>
</html>
