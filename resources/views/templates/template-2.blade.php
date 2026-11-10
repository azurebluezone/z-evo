<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $landingPage->title ?? 'Product Landing Page' }}</title>
  @include('frontend.partials.event-layer-head')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="{{ asset('frontend/landing/template-2.css') }}?v={{ filemtime(public_path('frontend/landing/template-2.css')) }}">
    <link rel="stylesheet" href="{{ asset('frontend/landing/responsive.css') }}?v={{ filemtime(public_path('frontend/landing/responsive.css')) }}">
</head>
<body>
  @include('frontend.partials.event-layer-body')
  @php
    $extra = $landingPage->extra_fields ?? [];
    $oldPrice = $extra['old_price'] ?? null;
    $newPrice = $extra['new_price'] ?? null;
    $countdownDate = $extra['countdown_date'] ?? null;
    $features = $extra['features'] ?? [];
    $whyUs = $extra['why_us'] ?? [];
    $contactPhone = $extra['contact_phone'] ?? null;
    $productId = $extra['product_id'] ?? null;
    $showFeatures = $extra['show_features'] ?? true;
    $showCta = $extra['show_cta'] ?? true;
    $featureIcons = ['lucide:heart-pulse','lucide:droplet','lucide:shield-check','lucide:activity','lucide:zap','lucide:gauge','lucide:glass-water','lucide:smile','lucide:bone'];
  @endphp

  <!-- Hero Section -->
  <div class="container">
    <div class="hero-wrapper">
      <div class="hero-content">
        <h1 class="hero-title text-primary fw-bold">{{ $landingPage->title }}</h1>

        @if($oldPrice || $newPrice)
        <div class="price-row fw-bold">
          @if($oldPrice)
          <span style="color:#64748b">রেগুলার মূল্য <span class="price-strike">{{ $oldPrice }}</span></span>
          @endif
          @if($newPrice)
          <span class="text-primary">বর্তমান মূল্য <span class="price-highlight">{{ $newPrice }}</span> টাকা</span>
          @endif
        </div>
        @endif

        <!-- Countdown Timer -->
        <div class="countdown-group" id="countdown-group" data-countdown="{{ $countdownDate }}">
          <div class="cd-box"><span class="cd-val" id="cd-days">00</span><span class="cd-lbl">Days</span></div>
          <div class="cd-box"><span class="cd-val" id="cd-hours">00</span><span class="cd-lbl">Hours</span></div>
          <div class="cd-box"><span class="cd-val" id="cd-minutes">00</span><span class="cd-lbl">Mins</span></div>
          <div class="cd-box"><span class="cd-val" id="cd-seconds">00</span><span class="cd-lbl">Secs</span></div>
        </div>

        @if($showCta)
        <a href="#order" class="btn-main fw-bold">
          <iconify-icon icon="lucide:shopping-cart" style="font-size:24px;color:white"></iconify-icon>
          {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
        </a>
        @endif
      </div>

      @if($landingPage->image_url)
      <div class="hero-image">
        <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}">
      </div>
      @endif
    </div>
  </div>

  <!-- Features Section -->
  @if($landingPage->subtitle || $landingPage->description || count($features) > 0)
  <div class="section-title-banner">{{ $landingPage->subtitle ?? 'কেন এই পণ্য ব্যবহার করবেন?' }}</div>

  <div class="container">
    @if($landingPage->description)
    <p class="intro-text text-danger fw-bold">{!! $landingPage->description !!}</p>
    @endif

    @if($showFeatures && count($features) > 0)
    <div class="features-grid">
      @foreach($features as $i => $feature)
      @php
        $featureTitle = is_array($feature) ? ($feature['title'] ?? ($feature['text'] ?? '')) : (string) $feature;
        $featureText = is_array($feature) ? ($feature['description'] ?? '') : '';
      @endphp
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <iconify-icon icon="{{ $featureIcons[$i % count($featureIcons)] }}" style="font-size:24px;color:var(--primary)"></iconify-icon>
        </div>
        <div class="feature-text fw-semibold">{{ $featureTitle }}</div>
        @if($featureText !== '')
        <small class="text-muted d-block mt-1">{{ $featureText }}</small>
        @endif
      </div>
      @endforeach
    </div>
    @endif

    @if($newPrice)
    <div class="text-center">
      <div class="fw-bold text-primary" style="font-size:36px;margin-bottom:32px">{{ $newPrice }} টাকা মাত্র</div>
      @if($showCta)
      <a href="#order" class="btn-main fw-bold">
        <iconify-icon icon="lucide:shopping-cart" style="font-size:24px;color:white"></iconify-icon>
        {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
      </a>
      @endif
    </div>
    @endif
  </div>
  @endif

  <!-- Why Us Section -->
  @if($showFeatures && count($whyUs) > 0)
  <div class="section-title-banner" style="margin-top:80px">আমাদের কাছে কেনো কিনবেন?</div>
  <div class="container">
    <div class="why-us-wrapper">
      @if($landingPage->image_url)
      <div class="why-us-image">
        <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}">
      </div>
      @endif
      <ul class="why-us-list fw-bold">
        @foreach($whyUs as $item)
        <li>
          <div class="check-circle">
            <iconify-icon icon="lucide:check" style="font-size:16px;color:white"></iconify-icon>
          </div>
          <span>{{ is_array($item) ? ($item['text'] ?? ($item['title'] ?? '')) : $item }}</span>
        </li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <!-- Contact Banner -->
  @if($contactPhone)
  <div class="section-title-banner" style="margin-bottom:0">যে কোন প্রয়োজনে যোগাযোগ করুন {{ $contactPhone }}</div>
  @endif

  <!-- Checkout Section -->
  <div class="checkout-section" id="order">
    <div class="container">
      <div class="text-center" style="margin-bottom:48px">
        <h2 class="text-primary fw-bold" style="font-size:32px">অর্ডার করতে নিচের ফর্মটি পূরণ করুন</h2>
      </div>

      <div class="form-container">
        <form method="POST" action="{{ $landingPage->cta_link ?? '#' }}" style="display:flex;gap:64px;flex-wrap:wrap">
          @csrf
          @if($productId)
            <input type="hidden" name="product_id" value="{{ $productId }}">
          @endif
          @if(!empty($extra['campaign_id']))
            <input type="hidden" name="campaign_id" value="{{ $extra['campaign_id'] }}">
          @endif

          <!-- Billing Details -->
          <div style="flex:1;min-width:320px">
            <div class="section-heading fw-bold">Billing details</div>

            <div class="input-group-custom">
              <label class="input-label">আপনার নাম <span class="text-danger">*</span></label>
              <input type="text" class="form-input" name="name" placeholder="আপনার পূর্ণ নাম" required>
            </div>

            <div class="input-group-custom">
              <label class="input-label">আপনার সম্পূর্ণ ঠিকানা <span class="text-danger">*</span></label>
              <input type="text" class="form-input" name="address" placeholder="House number and street name" required>
            </div>

            <div class="input-group-custom">
              <label class="input-label">আপনার ফোন নাম্বারটি লেখুন <span class="text-danger">*</span></label>
              <input type="tel" class="form-input" name="phone" placeholder="01XXXXXXXXX" required>
            </div>

            <div class="input-group-custom">
              <label class="input-label">পরিমাণ</label>
              <input type="number" class="form-input" name="quantity" value="1" min="1">
            </div>
          </div>

          <!-- Order Summary -->
          <div style="flex:1;min-width:320px">
            <div class="section-heading fw-bold">Your order</div>

            <table class="order-table">
              <thead>
                <tr>
                  <th class="fw-bold">Product</th>
                  <th class="fw-bold">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ $landingPage->title }}</td>
                  <td class="fw-semibold">{{ $newPrice ? '৳ ' . $newPrice : '' }}</td>
                </tr>
                <tr>
                  <td class="fw-bold text-primary" style="font-size:20px;border-bottom:none">Total</td>
                  <td class="fw-bold text-primary" style="font-size:20px;border-bottom:none">{{ $newPrice ? '৳ ' . $newPrice : '' }}</td>
                </tr>
              </tbody>
            </table>

            <div class="payment-methods">
              <div class="pay-option">
                <input type="radio" name="payment_method" value="cod" checked>
                <span class="fw-semibold">Cash on delivery</span>
              </div>
            </div>

            <p class="privacy-text">
              Your personal data will be used to process your order, support your experience throughout this website.
            </p>

            <button type="submit" class="btn-submit">
              <iconify-icon icon="lucide:lock" style="font-size:20px"></iconify-icon>
              Place Order{{ $newPrice ? ' ৳ ' . $newPrice : '' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="site-footer">
    Copyright &copy; {{ date('Y') }} {{ $landingPage->title }}. All rights reserved.
  </div>

  <script src="{{ asset('frontend/landing/template-2.js') }}?v={{ filemtime(public_path('frontend/landing/template-2.js')) }}"></script>
</body>
</html>