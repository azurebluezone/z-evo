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
      --primary: #1ba228;
      --danger: #ff0000;
      --accent: #f05a2b;
      --bg: #ffffff;
      --text: #333333;
      --muted: #f8fafc;
      --border: #e2e8f0;
      --font-family-body: Inter, system-ui, -apple-system, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family-body); background-color: var(--bg); color: var(--text); line-height: 1.6; }
    .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
    .fw-bold { font-weight: 700; }
    .fw-semibold { font-weight: 600; }
    .fw-medium { font-weight: 500; }
    .text-primary { color: var(--primary); }
    .text-danger { color: var(--danger); }
    .text-center { text-align: center; }
    .bg-primary { background-color: var(--primary); color: white; }
    .section-title-banner { background-color: var(--primary); color: white; text-align: center; padding: 24px 20px; font-size: 28px; font-weight: 700; margin: 64px 0 48px; }

    /* Hero */
    .hero-wrapper { display: flex; flex-wrap: wrap; align-items: center; gap: 48px; padding: 64px 0 24px; }
    .hero-content { flex: 1; min-width: 320px; }
    .hero-image { flex: 1; min-width: 320px; }
    .hero-image img { width: 100%; border: 4px solid var(--primary); padding: 8px; border-radius: 12px; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.05); display: block; }
    .hero-title { font-size: 42px; line-height: 1.2; margin-bottom: 24px; }
    .price-row { font-size: 24px; margin-bottom: 32px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .price-strike { position: relative; display: inline-block; }
    .price-strike::after { content: ""; position: absolute; top: 50%; left: -5%; width: 110%; height: 3px; background-color: var(--danger); transform: rotate(-10deg); }
    .price-highlight { color: var(--danger); border: 2px solid var(--danger); border-radius: 50px; padding: 6px 20px; display: inline-block; }
    .countdown-group { display: flex; gap: 12px; margin-bottom: 40px; }
    .cd-box { background-color: var(--primary); color: white; width: 70px; height: 80px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 8px; }
    .cd-val { font-size: 32px; font-weight: 700; line-height: 1; }
    .cd-lbl { font-size: 13px; margin-top: 4px; opacity: 0.9; }
    .btn-main { background-color: var(--danger); color: white; font-size: 20px; border: none; padding: 16px 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(255,0,0,0.2); }

    /* Features */
    .intro-text { font-size: 18px; max-width: 900px; margin: 0 auto 48px; line-height: 1.6; text-align: center; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 56px; }
    .feature-card { background: var(--muted); border: 1px solid var(--border); padding: 24px; border-radius: 12px; display: flex; gap: 16px; align-items: flex-start; }
    .feature-icon-wrapper { background: white; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid var(--border); }
    .feature-text { font-size: 15px; color: var(--text); line-height: 1.6; }

    /* Why Us */
    .why-us-wrapper { display: flex; flex-wrap: wrap; align-items: center; gap: 64px; margin: 48px 0; }
    .why-us-image { flex: 1; min-width: 320px; }
    .why-us-image img { width: 100%; border-radius: 16px; box-shadow: 0 12px 32px rgba(0,0,0,0.08); display: block; }
    .why-us-list { flex: 1; min-width: 320px; list-style: none; }
    .why-us-list li { display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px dashed var(--border); font-size: 16px; }
    .why-us-list li:last-child { border-bottom: none; }
    .check-circle { background-color: var(--primary); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    /* Checkout */
    .checkout-section { background-color: var(--muted); padding: 80px 0; margin-top: 64px; border-top: 1px solid var(--border); }
    .form-container { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 48px; box-shadow: 0 8px 32px rgba(0,0,0,0.04); }
    .section-heading { font-size: 20px; margin-bottom: 24px; border-bottom: 2px solid var(--primary); padding-bottom: 12px; display: inline-block; }
    .input-group-custom { margin-bottom: 20px; }
    .input-label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #475569; }
    .form-input { width: 100%; height: 48px; border: 1px solid #cbd5e1; border-radius: 8px; background: white; padding: 0 16px; color: var(--text); font-size: 15px; }
    .form-input:focus { outline: none; border-color: var(--primary); }
    .order-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
    .order-table th, .order-table td { padding: 16px 0; border-bottom: 1px solid var(--border); text-align: left; }
    .order-table th:last-child, .order-table td:last-child { text-align: right; }
    .payment-methods { background: var(--muted); border-radius: 12px; padding: 24px; margin-bottom: 24px; border: 1px solid var(--border); }
    .pay-option { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; font-size: 15px; font-weight: 500; cursor: pointer; }
    .privacy-text { font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
    .btn-submit { width: 100%; background: var(--accent); color: white; border: none; padding: 18px; font-size: 18px; font-weight: 700; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer; }
    .site-footer { text-align: center; padding: 32px 20px; color: #64748b; font-size: 14px; background: white; }

    @media (max-width: 768px) {
      .hero-wrapper { flex-direction: column; padding: 32px 0 16px; }
      .hero-title { font-size: 28px; }
      .form-container { padding: 24px; }
      .features-grid { grid-template-columns: 1fr; }
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
        <div class="countdown-group">
          <div class="cd-box"><span class="cd-val" id="cd-days">00</span><span class="cd-lbl">Days</span></div>
          <div class="cd-box"><span class="cd-val" id="cd-hours">00</span><span class="cd-lbl">Hours</span></div>
          <div class="cd-box"><span class="cd-val" id="cd-minutes">00</span><span class="cd-lbl">Mins</span></div>
          <div class="cd-box"><span class="cd-val" id="cd-seconds">00</span><span class="cd-lbl">Secs</span></div>
        </div>

        <a href="#order" class="btn-main fw-bold">
          <iconify-icon icon="lucide:shopping-cart" style="font-size:24px;color:white"></iconify-icon>
          {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
        </a>
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

    @if(count($features) > 0)
    <div class="features-grid">
      @foreach($features as $i => $feature)
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <iconify-icon icon="{{ $featureIcons[$i % count($featureIcons)] }}" style="font-size:24px;color:var(--primary)"></iconify-icon>
        </div>
        <div class="feature-text fw-semibold">{{ $feature }}</div>
      </div>
      @endforeach
    </div>
    @endif

    @if($newPrice)
    <div class="text-center">
      <div class="fw-bold text-primary" style="font-size:36px;margin-bottom:32px">{{ $newPrice }} টাকা মাত্র</div>
      <a href="#order" class="btn-main fw-bold">
        <iconify-icon icon="lucide:shopping-cart" style="font-size:24px;color:white"></iconify-icon>
        {{ $landingPage->cta_text ?? 'অর্ডার করতে চাই' }}
      </a>
    </div>
    @endif
  </div>
  @endif

  <!-- Why Us Section -->
  @if(count($whyUs) > 0)
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
          <span>{{ $item }}</span>
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

  <script>
    @if($countdownDate)
    (function() {
      var target = new Date('{{ $countdownDate }}').getTime();
      function update() {
        var now = new Date().getTime();
        var diff = Math.max(0, target - now);
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
