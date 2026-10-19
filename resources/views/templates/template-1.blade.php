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
  <style>
    :root {
      --background: #ffffff;
      --foreground: #111111;
      --primary: #01a31c;
      --primary-foreground: #ffffff;
      --danger: #ff0000;
      --danger-foreground: #ffffff;
      --accent: #f05a28;
      --accent-foreground: #ffffff;
      --muted: #f8fafc;
      --muted-foreground: #64748b;
      --border: #e2e8f0;
      --input-border: #d1d5db;
      --font-family-body: Inter, system-ui, -apple-system, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family-body); background-color: var(--background); color: var(--foreground); line-height: 1.5; }
    .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .text-center { text-align: center; }
    .fw-bold { font-weight: 700; }
    .fw-semibold { font-weight: 600; }
    .fw-medium { font-weight: 500; }
    .text-primary { color: var(--primary); }
    .text-danger { color: var(--danger); }

    .page-title { font-size: 36px; margin: 32px 0 20px; }
    .hero-img-wrapper { border: 3px solid var(--primary); padding: 4px; border-radius: 4px; margin-bottom: 24px; background: var(--background); }
    .hero-img-wrapper img { width: 100%; height: auto; display: block; }
    .countdown-container { display: flex; justify-content: center; gap: 8px; margin-bottom: 32px; }
    .countdown-box { background-color: var(--primary); color: var(--primary-foreground); width: 80px; height: 90px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 4px; }
    .countdown-val { font-size: 40px; line-height: 1; }
    .countdown-label { font-size: 14px; margin-top: 4px; }
    .pricing-row { display: flex; justify-content: center; align-items: center; gap: 16px; font-size: 24px; margin-bottom: 32px; flex-wrap: wrap; }
    .price-strike-wrapper { position: relative; color: var(--foreground); display: inline-block; }
    .price-strike-line { position: absolute; top: 50%; left: -5%; width: 110%; height: 3px; background-color: var(--danger); transform: rotate(-10deg); }
    .price-circle-wrapper { color: var(--danger); border: 2px solid var(--danger); border-radius: 50px; padding: 4px 16px; display: inline-block; margin: 0 4px; }
    .btn-cta { background-color: var(--danger); color: var(--danger-foreground); font-size: 20px; border: none; padding: 14px 28px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer; margin-bottom: 40px; text-decoration: none; }

    .full-banner { background-color: var(--primary); color: var(--primary-foreground); padding: 16px 20px; font-size: 28px; margin-bottom: 32px; }
    .desc-text { font-size: 16px; max-width: 800px; margin: 0 auto 32px; line-height: 1.6; }
    .feature-list-wrapper { max-width: 760px; margin: 0 auto 32px; }
    .feature-list { list-style: none; }
    .feature-list li { display: flex; align-items: flex-start; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--border); font-size: 15px; color: var(--foreground); text-align: left; }
    .feature-list li:last-child { border-bottom: none; }
    .mid-price { font-size: 32px; margin-bottom: 24px; }

    .form-wrapper { border: 2px solid var(--primary); border-radius: 12px; padding: 40px; margin-bottom: 60px; background-color: var(--background); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
    .form-title { font-size: 18px; margin-bottom: 24px; color: var(--foreground); }
    .form-group { margin-bottom: 16px; text-align: left; }
    .form-label { display: block; font-size: 13px; margin-bottom: 6px; color: #333; font-weight: 600; }
    .form-input { width: 100%; border: 1px solid var(--input-border); padding: 12px 14px; border-radius: 4px; background-color: var(--background); font-size: 14px; }
    .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(1,163,28,0.15); }
    .form-select { width: 100%; border: 1px solid var(--input-border); padding: 12px 14px; border-radius: 4px; background-color: var(--background); font-size: 14px; }
    .form-textarea { width: 100%; border: 1px solid var(--input-border); padding: 12px 14px; border-radius: 4px; background-color: var(--background); font-size: 14px; min-height: 80px; }

    .order-summary-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; text-align: left; }
    .order-summary-table th { font-size: 14px; padding-bottom: 12px; border-bottom: 1px dashed var(--border); }
    .order-summary-table td { padding: 16px 0; font-size: 14px; border-bottom: 1px solid var(--border); }
    .order-summary-table th:last-child, .order-summary-table td:last-child { text-align: right; }

    .payment-box { background-color: var(--muted); padding: 20px; border-radius: 4px; margin-bottom: 24px; }
    .payment-option { display: flex; align-items: center; gap: 10px; font-size: 14px; margin-bottom: 12px; cursor: pointer; }
    .privacy-text { font-size: 11px; color: #64748b; margin-bottom: 20px; line-height: 1.5; }
    .btn-submit { background-color: var(--accent); color: var(--accent-foreground); width: 100%; border: none; padding: 16px; font-size: 16px; border-radius: 4px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; font-weight: 700; }

    .footer { text-align: center; padding: 24px; font-size: 13px; color: var(--muted-foreground); border-top: 1px solid var(--border); }

    @media (max-width: 768px) {
      .form-grid { grid-template-columns: 1fr; gap: 24px; }
      .countdown-box { width: 60px; height: 70px; }
      .countdown-val { font-size: 28px; }
    }
  </style>
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
    <div class="countdown-container" id="countdown-container">
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

    <a href="#order" class="btn-cta fw-bold">
      <iconify-icon icon="lucide:shopping-cart" style="font-size: 24px; color: white"></iconify-icon>
      {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
    </a>
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

    @if(count($features) > 0)
    <div class="feature-list-wrapper">
      <ul class="feature-list fw-medium">
        @foreach($features as $feature)
        <li>
          <div style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <iconify-icon icon="lucide:check-circle-2" style="font-size:20px;color:var(--primary)"></iconify-icon>
          </div>
          <span>{{ $feature }}</span>
        </li>
        @endforeach
      </ul>
    </div>
    @endif

    @if($newPrice)
    <div class="mid-price text-primary fw-bold">{{ $newPrice }} টাকা মাত্র</div>
    @endif

    <a href="#order" class="btn-cta fw-bold">
      <iconify-icon icon="lucide:shopping-cart" style="font-size:24px;color:white"></iconify-icon>
      {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
    </a>
  </div>
  @endif

  <!-- Why Us Section -->
  @if(count($whyUs) > 0)
  <div class="full-banner text-center fw-bold">আমাদের কাছে কেনো কিনবেন?</div>
  <div class="container">
    <div class="feature-list-wrapper">
      <ul class="feature-list fw-medium">
        @foreach($whyUs as $item)
        <li>
          <div style="background-color:var(--primary);border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <iconify-icon icon="lucide:chevron-right" style="color:white;font-size:14px;margin-left:2px"></iconify-icon>
          </div>
          <span>{{ $item }}</span>
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

  <script>
    // Countdown timer
    @if($countdownDate)
    (function() {
      var target = new Date('{{ $countdownDate }}').getTime();
      function update() {
        var now = new Date().getTime();
        var diff = target - now;
        if (diff < 0) diff = 0;
        var d = Math.floor(diff / (1000*60*60*24));
        var h = Math.floor((diff % (1000*60*60*24)) / (1000*60*60));
        var m = Math.floor((diff % (1000*60*60)) / (1000*60));
        var s = Math.floor((diff % (1000*60)) / 1000);
        document.getElementById('cd-days').textContent = String(d).padStart(2,'0');
        document.getElementById('cd-hours').textContent = String(h).padStart(2,'0');
        document.getElementById('cd-minutes').textContent = String(m).padStart(2,'0');
        document.getElementById('cd-seconds').textContent = String(s).padStart(2,'0');
      }
      update();
      setInterval(update, 1000);
    })();
    @endif
  </script>
</body>
</html>
