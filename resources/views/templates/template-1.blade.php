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
  <link rel="stylesheet" href="{{ asset('frontend/landing/template-1.css') }}?v={{ filemtime(public_path('frontend/landing/template-1.css')) }}">
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
  @endphp

  <!-- Hero Section -->
  <div class="container text-center">
    <h1 class="page-title text-primary fw-bold">{{ $landingPage->title }}</h1>

    @if($landingPage->image_url)
    <div class="hero-img-wrapper">
      <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}">
    </div>
    @endif

    <!-- Countdown Timer -->
    <div class="countdown-container" id="countdown-container" data-countdown="{{ $countdownDate }}">
      <div class="countdown-box">
        <span class="countdown-val fw-bold" id="cd-days">00</span>
        <span class="countdown-label">Days</span>
      </div>
      <div class="countdown-box">
        <span class="countdown-val fw-bold" id="cd-hours">00</span>
        <span class="countdown-label">Hours</span>
      </div>
      <div class="countdown-box">
        <span class="countdown-val fw-bold" id="cd-minutes">00</span>
        <span class="countdown-label">Minutes</span>
      </div>
      <div class="countdown-box">
        <span class="countdown-val fw-bold" id="cd-seconds">00</span>
        <span class="countdown-label">Seconds</span>
      </div>
    </div>

    <!-- Pricing -->
    @if($oldPrice || $newPrice)
    <div class="pricing-row fw-bold">
      @if($oldPrice)
      <span>রেগুলার মূল্য
        <span class="price-strike-wrapper">{{ $oldPrice }}<span class="price-strike-line"></span></span>
      </span>
      @endif
      @if($newPrice)
      <span class="text-primary">বর্তমান ডিসকাউন্ট মূল্য
        <span class="price-circle-wrapper">{{ $newPrice }}</span> টাকা
      </span>
      @endif
    </div>
    @endif

    @if($showCta)
    <a href="#order" class="btn-cta fw-bold">
      <iconify-icon icon="lucide:shopping-cart" style="font-size: 24px; color: white"></iconify-icon>
      {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
    </a>
    @endif
  </div>

  <!-- Description / Features Banner -->
  @if($landingPage->subtitle)
  <div class="full-banner text-center fw-bold">{{ $landingPage->subtitle }}</div>
  @endif

  @if($landingPage->description || count($features) > 0)
  <div class="container text-center">
    @if($landingPage->description)
    <p class="desc-text text-danger fw-semibold">{!! $landingPage->description !!}</p>
    @endif

    @if($showFeatures && count($features) > 0)
    <div class="feature-list-wrapper">
      <ul class="feature-list fw-medium">
        @foreach($features as $feature)
        @php
          $featureTitle = is_array($feature) ? ($feature['title'] ?? ($feature['text'] ?? '')) : (string) $feature;
          $featureText = is_array($feature) ? ($feature['description'] ?? '') : '';
        @endphp
        <li>
          <div style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <iconify-icon icon="lucide:check-circle-2" style="font-size:20px;color:var(--primary)"></iconify-icon>
          </div>
          <span>
            {{ $featureTitle }}
            @if($featureText !== '')
            <small class="d-block text-muted">{{ $featureText }}</small>
            @endif
          </span>
        </li>
        @endforeach
      </ul>
    </div>
    @endif

    @if($newPrice)
    <div class="mid-price text-primary fw-bold">{{ $newPrice }} টাকা মাত্র</div>
    @endif

    @if($showCta)
    <a href="#order" class="btn-cta fw-bold">
      <iconify-icon icon="lucide:shopping-cart" style="font-size:24px;color:white"></iconify-icon>
      {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
    </a>
    @endif
  </div>
  @endif

  <!-- Why Us Section -->
  @if($showFeatures && count($whyUs) > 0)
  <div class="full-banner text-center fw-bold">আমাদের কাছে কেনো কিনবেন?</div>
  <div class="container">
    <div class="feature-list-wrapper">
      <ul class="feature-list fw-medium">
        @foreach($whyUs as $item)
        <li>
          <div style="background-color:var(--primary);border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <iconify-icon icon="lucide:chevron-right" style="color:white;font-size:14px;margin-left:2px"></iconify-icon>
          </div>
          <span>{{ is_array($item) ? ($item['text'] ?? ($item['title'] ?? '')) : $item }}</span>
        </li>
        @endforeach
      </ul>
    </div>

    @if($landingPage->image_url)
    <div class="hero-img-wrapper" style="margin-bottom:0">
      <img src="{{ asset(preg_replace('/^public\//', '', $landingPage->image_url)) }}" alt="{{ $landingPage->title }}">
    </div>
    @endif
  </div>
  @endif

  <!-- Contact Banner -->
  @if($contactPhone)
  <div class="full-banner text-center fw-bold" style="margin-top:32px">
    যে কোন প্রয়োজনে যোগাযোগ করুন {{ $contactPhone }}
  </div>
  @endif

  <!-- Order Form Section -->
  <div class="container" id="order">
    <h2 class="page-title text-center text-primary fw-bold" style="margin-bottom:32px">
      অর্ডার করতে নিচের ফর্মটি পূরণ করুন
    </h2>

    <div class="form-wrapper">
      <form method="POST" action="{{ $landingPage->cta_link ?? '#' }}">
        @csrf
        @if($productId)
          <input type="hidden" name="product_id" value="{{ $productId }}">
        @endif
        @if(!empty($extra['campaign_id']))
          <input type="hidden" name="campaign_id" value="{{ $extra['campaign_id'] }}">
        @endif

        <div class="form-grid">
          <!-- Left: Billing Details -->
          <div>
            <div class="form-title fw-bold">Billing details</div>

            <div class="form-group">
              <label class="form-label">আপনার নাম <span class="text-danger">*</span></label>
              <input type="text" class="form-input" name="name" placeholder="আপনার পূর্ণ নাম" required>
            </div>

            <div class="form-group">
              <label class="form-label">আপনার সম্পূর্ণ ঠিকানা <span class="text-danger">*</span></label>
              <input type="text" class="form-input" name="address" placeholder="House number and street name" required>
            </div>

            <div class="form-group">
              <label class="form-label">আপনার ফোন নাম্বারটি লেখুন <span class="text-danger">*</span></label>
              <input type="tel" class="form-input" name="phone" placeholder="01XXXXXXXXX" required>
            </div>

            <div class="form-group">
              <label class="form-label">পরিমাণ</label>
              <input type="number" class="form-input" name="quantity" value="1" min="1">
            </div>
          </div>

          <!-- Right: Order Summary -->
          <div>
            <div class="form-title fw-bold">Your order</div>

            <table class="order-summary-table">
              <thead>
                <tr>
                  <th class="fw-bold">Product</th>
                  <th class="fw-bold">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ $landingPage->title }}</td>
                  <td class="fw-medium">{{ $newPrice ? '৳ ' . $newPrice : '' }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold" style="padding:12px 0">Total</td>
                  <td class="fw-bold" style="padding:12px 0;border-bottom:none">{{ $newPrice ? '৳ ' . $newPrice : '' }}</td>
                </tr>
              </tbody>
            </table>

            <div class="payment-box">
              <div class="payment-option fw-medium">
                <input type="radio" name="payment_method" value="cod" checked> Cash on delivery
              </div>
            </div>

            <p class="privacy-text">
              Your personal data will be used to process your order, support your experience throughout this website.
            </p>

            <button type="submit" class="btn-submit">
              <iconify-icon icon="lucide:lock" style="font-size:18px"></iconify-icon>
              Place Order{{ $newPrice ? ' ৳ ' . $newPrice : '' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    Copyright &copy; {{ date('Y') }} {{ $landingPage->title }}. All rights reserved.
  </div>

  <script src="{{ asset('frontend/landing/template-1.js') }}?v={{ filemtime(public_path('frontend/landing/template-1.js')) }}"></script>
</body>
</html>