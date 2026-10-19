@extends('frontend.layouts.app')

@section('title', 'Customer Checkout')

@section('content')
@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
/* ---------- RESET & BASE (prefix: bd-ck) ---------- */
.bd-ck-container,
.bd-ck-container * {
  box-sizing: border-box;
}

.bd-ck-container {
  max-width: 1320px;
  margin: 24px auto;
  width: calc(100% - 24px);
  background: #ffffff;
  box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.2s;
  color: #000;
}

/* step indicator (modern) */
.bd-ck-steps {
  background: #ffffff;
  padding: 1.6rem 2rem;
  border-bottom: 1px solid #eef2f8;
}
.bd-ck-step-list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}
.bd-ck-step-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-weight: 500;
  font-size: 0.9rem;
  color: #7e8b9c;
}
.bd-ck-step-num {
  width: 34px;
  height: 34px;
  background: #eef2f8;
  border-radius: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  color: #5a6e7c;
}
.bd-ck-step-item.active .bd-ck-step-num {
  background: #2c6e49;
  color: white;
  box-shadow: 0 4px 10px rgba(44,110,73,0.2);
}
.bd-ck-step-item.active {
  color: #2c6e49;
  font-weight: 600;
}
.bd-ck-step-item.completed .bd-ck-step-num {
  background: #2c6e49;
  color: white;
}
.bd-ck-step-sep {
  flex: 1;
  height: 2px;
  background: #e2e8f0;
  min-width: 24px;
}

/* TWO COLUMN GRID with extra gap */
.bd-ck-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 3rem;
  padding: 3rem 3rem 2.2rem 3rem;
}
/* left side: shipping & payment */
.bd-ck-form-col {
  flex: 1.4;
  min-width: 280px;
}
/* right side: order summary (dynamic) */
.bd-ck-summary-col {
  flex: 1;
  min-width: 300px;
  background: #fafdff;
  border-radius: 0;
  border: 1px solid #eef3fc;
  padding: 1.6rem;
  box-shadow: 0 6px 14px rgba(0,0,0,0.02);
  align-self: start;
}
@media (min-width: 781px) {
  .bd-ck-summary-col {
    position: sticky;
    top: 110px;
  }
}

.bd-ck-summary-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 0.75rem;
}
.bd-ck-summary-mini {
  font-size: 0.85rem;
  color: #4a5b72;
  padding-left: 12px;
}
.bd-ck-summary-toggle-btn {
  display: none;
  border: 1px solid #d6e2ec;
  background: #ffffff;
  color: #1e2f3c;
  font-weight: 700;
  font-size: 0.85rem;
  padding: 0.55rem 0.9rem;
  border-radius: 0;
  cursor: pointer;
}
.bd-ck-summary-toggle-btn:active {
  transform: translateY(1px);
}
.bd-ck-summary-body {
  display: block;
}

/* section titles with left border */
.bd-ck-section-title {
  font-size: 1.35rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
  padding-left: 12px;
  border-left: 4px solid #2c6e49;
  color: #000;
}
.bd-ck-sub {
  font-size: 0.8rem;
  color: #6c7f96;
  margin-bottom: 1.5rem;
  padding-left: 12px;
}

/* form styles */
.bd-ck-form-group {
  margin-bottom: 1.2rem;
}
.bd-ck-label {
  display: block;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  color: #000;
  margin-bottom: 0.4rem;
}
.bd-ck-label span {
  color: #e03a3a;
  margin-left: 2px;
}
.bd-ck-input, .bd-ck-select {
  width: 100%;
  padding: 0.85rem 1rem;
  border: 1.5px solid #e2eaf1;
  border-radius: 0;
  font-size: 0.95rem;
  font-family: 'Inter', sans-serif;
  background: #ffffff;
  transition: 0.2s;
  outline: none;
  height: 48px;
}
.bd-ck-input:focus, .bd-ck-select:focus {
  border-color: #2c6e49;
  box-shadow: 0 0 0 3px rgba(44,110,73,0.1);
}
.bd-ck-row-2 {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.bd-ck-row-2 .bd-ck-form-group {
  flex: 1;
  min-width: 200px;
}

/* payment methods */
.bd-ck-payment-wrap {
  margin-top: 1.6rem;
}
.bd-ck-payment-title {
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 1rem;
}
.bd-ck-payment-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.bd-ck-radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  background: #f9fbfe;
  padding: 0.8rem;
  border-radius: 0;
  margin-bottom: 0.5rem;
  flex: 1 1 auto;
}
.bd-ck-radio-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  padding: 0.5rem 1.2rem;
  border-radius: 0;
  border: 1px solid #dee6ef;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 44px;
}
.bd-ck-radio-item.selected {
  border-color: #2c6e49;
  background: #f0f9ef;
}
.bd-ck-radio-item input {
  accent-color: #2c6e49;
  width: 18px;
  height: 18px;
  margin: 0;
  cursor: pointer;
}
.bd-ck-radio-item label {
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  margin: 0;
}
/* card details */
.bd-ck-card-details {
  background: #ffffff;
  padding: 1rem;
  border-radius: 0;
  border: 1px solid #eef2f8;
  margin-top: 0.8rem;
}

/* buttons */
.bd-ck-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
    justify-content: space-evenly;
}
.bd-ck-actions.inline-actions {
    margin-top: 0;
    justify-content: flex-end;
}
.bd-ck-actions.inline-actions .bd-ck-btn {
  min-height: 44px;
  padding: 0.65rem 1.1rem;
  white-space: nowrap;
}
.bd-ck-btn {
  padding: 0.85rem 1.6rem;
  border-radius: 0;
  font-weight: 700;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  transition: 0.2s;
  font-family: inherit;
}
.bd-ck-btn-outline {
  background: transparent;
  border: 1.5px solid #cbdbe0;
  color: #2c3e50;
}
.bd-ck-btn-outline:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}
.bd-ck-btn-primary {
  background: #2c6e49;
  color: white;
  box-shadow: 0 4px 10px rgba(44,110,73,0.2);
}
.bd-ck-btn-primary:hover {
  background: #1f5a3b;
  transform: translateY(-2px);
}

/* ----- DYNAMIC ORDER SUMMARY (cart-like) ----- */
.bd-ck-summary-items {
  max-height: 320px;
  overflow-y: auto;
  margin-bottom: 1rem;
}
.bd-ck-cart-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  padding: 0.9rem 0;
  border-bottom: 1px solid #eef2f8;
  gap: 12px;
}
.bd-ck-cart-item.is-pending {
  opacity: 0.65;
}
.bd-ck-item-image {
  width: 50px;
  height: 50px;
  object-fit: contain;
  border: 1px solid #eef2f8;
  border-radius: 0;
}
.bd-ck-item-info {
  flex: 2;
  min-width: 180px;
}
.bd-ck-item-name {
  font-weight: 600;
  font-size: 0.9rem;
}
.bd-ck-item-meta {
  font-size: 0.7rem;
  color: #6f8faa;
}
.bd-ck-qty-control {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f3f6fc;
  padding: 4px 8px;
  border-radius: 0;
  flex: 0 0 auto;
}
.bd-ck-qty-btn {
  background: white;
  border: 1px solid #cbdde9;
  width: 28px;
  height: 28px;
  border-radius: 0;
  font-weight: bold;
  cursor: pointer;
  transition: 0.1s;
  font-size: 1.1rem;
}
.bd-ck-qty-num {
  width: 32px;
  text-align: center;
  font-weight: 600;
}
.bd-ck-item-price {
  font-weight: 700;
  min-width: 70px;
  text-align: right;
  flex: 0 0 auto;
}
.bd-ck-remove-item {
  background: none;
  border: none;
  color: #b91c1c;
  font-size: 1.1rem;
  cursor: pointer;
  opacity: 0.7;
  transition: 0.2s;
  flex: 0 0 auto;
}
.bd-ck-remove-item:hover {
  opacity: 1;
  transform: scale(1.1);
}

/* totals area */
.bd-ck-total-row {
  display: flex;
  justify-content: space-between;
  padding: 0.7rem 0;
  font-size: 0.9rem;
  border-top: 1px solid #ecf3fa;
}
.bd-ck-total-row.bd-ck-grand {
  font-weight: 800;
  font-size: 1.2rem;
  border-top: 2px dashed #cbdde9;
  margin-top: 0.3rem;
  padding-top: 1rem;
}
.bd-ck-coupon-area {
  margin: 1rem 0;
  display: flex;
  gap: 10px;
}
.bd-ck-coupon-input {
  flex: 2;
  padding: 0.6rem 1rem;
  border-radius: 0;
  border: 1px solid #cbdde9;
  font-size: 0.8rem;
  height: 44px;
}
.bd-ck-apply-coupon {
  background: #2c6e49;
  border: none;
  padding: 0 1rem;
  border-radius: 0;
  color: white;
  font-weight: 600;
  cursor: pointer;
  height: 44px;
}
.bd-ck-coupon-status {
  font-size: 0.75rem;
  margin-top: 6px;
  display: flex;
  justify-content: space-between;
  background: #e9f3e6;
  padding: 8px 12px;
  border-radius: 0;
}

/* toast */
.bd-ck-toast {
  position: fixed;
  bottom: 25px;
  left: 50%;
  transform: translateX(-50%);
  background: #1e2a36;
  color: white;
  padding: 12px 28px;
  border-radius: 0;
  font-size: 0.85rem;
  z-index: 1200;
  opacity: 0;
  transition: 0.2s;
  pointer-events: none;
}
.bd-ck-toast.show {
  opacity: 1;
}

@media (max-width: 780px) {
  .bd-ck-container { margin: 14px auto; width: calc(100% - 16px); }
  .bd-ck-grid { flex-direction: column; gap: 1.5rem; padding: 1.25rem; }
  .bd-ck-step-item span:last-child { font-size: 0.7rem; }
  .bd-ck-summary-col { padding: 1rem; }
  .bd-ck-summary-toggle-btn { display: inline-flex; align-items: center; gap: 8px; }
  .bd-ck-summary-body { display: none; }
  .bd-ck-summary-col.is-open .bd-ck-summary-body { display: block; }

  /* Payment buttons: COD + bKash same size side-by-side */
  .bd-ck-payment-bar { align-items: stretch; }
  .bd-ck-radio-group { gap: 10px; }
  .bd-ck-radio-item { flex: 1 1 0; padding: 0.55rem 0.8rem; justify-content: center; }
  .bd-ck-actions.inline-actions { width: 100%; justify-content: center; }
  .bd-ck-actions.inline-actions .bd-ck-btn { width: 100%; }

  /* Order summary items: prevent squish */
  .bd-ck-cart-item { align-items: flex-start; }
  .bd-ck-item-info { flex: 1 1 100%; min-width: 0; }
  .bd-ck-qty-control { order: 3; }
  .bd-ck-item-price { order: 4; margin-left: auto; }
  .bd-ck-remove-item { order: 5; }
}

@media (min-width: 992px) {
  /* Desktop: payment radios + Place Order in one row */
  .bd-ck-payment-bar { flex-wrap: nowrap; }
  .bd-ck-radio-group { flex-wrap: nowrap; gap: 12px; }
  .bd-ck-radio-item { flex: 0 0 auto; }
}

/* Laravel specific fixes */
.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}
.is-invalid {
  border-color: #dc3545 !important;
}
</style>
@endpush

@php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal = str_replace(',', '', $subtotal);
    $subtotal = str_replace('.00', '', $subtotal);
    $shipping = Session::get('shipping') ? Session::get('shipping') : 0;
    $shippingAreaId = (int) (Session::get('shipping_area_id') ?? 0);
    $discount = Session::get('discount') ? Session::get('discount') : 0;
    $couponCode = (string) (Session::get('coupon_code') ?? '');
    $isCouponEnabled = \App\Models\Coupon::running()->exists();
@endphp

<div class="bd-ck-container">
  <div class="bd-ck-steps">
  </div>

  <div class="bd-ck-grid">
    <!-- LEFT COLUMN: Shipping + Payment dynamic -->
    <div class="bd-ck-form-col" id="bdFormCol">
      <div class="bd-ck-section-title">📦 Recipient's Info</div>
      <div class="bd-ck-sub">Complete form to place your order. Call 01836879690 for help.</div>

      <form action="{{ route('customer.ordersave') }}" method="POST" data-parsley-validate="">
        @csrf
        
        <div class="bd-ck-form-group">
          <label class="bd-ck-label">Full Name <span>*</span></label>
          <input type="text" id="bdFullName" name="name" class="bd-ck-input @error('name') is-invalid @enderror" placeholder="MD. Rahim Uddin" value="{{ old('name') }}" required>
          @error('name')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
          @enderror
        </div>
        <div class="bd-ck-form-group">
          <label class="bd-ck-label">Address <span>*</span></label>
          <input type="text" id="bdAddress" name="address" class="bd-ck-input @error('address') is-invalid @enderror" placeholder="House 12, Road 5, Block-C" value="{{ old('address') }}" required>
          @error('address')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
          @enderror
        </div>
        
        <div class="bd-ck-form-group">
          <label class="bd-ck-label">Phone <span>*</span></label>
          <input type="tel" id="bdPhone" name="phone" class="bd-ck-input @error('phone') is-invalid @enderror" placeholder="01XXXXXXXXX" value="{{ old('phone') }}" minlength="11" maxlength="11" pattern="0[0-9]+" required>
          @error('phone')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
          @enderror
        </div>
        
        <div class="bd-ck-form-group">
          <label class="bd-ck-label">Shipping Area <span>*</span></label>
          <select id="bdAreaSelect" name="area" class="bd-ck-select @error('area') is-invalid @enderror" required>
            <option value="" disabled {{ $shippingAreaId === 0 ? 'selected' : '' }}>Select Delivery Area</option>
            @foreach ($shippingcharge as $key => $value)
              <option value="{{ $value->id }}" {{ $shippingAreaId === (int) $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
            @endforeach
          </select>
          @error('area')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
          @enderror
        </div>

        <!-- PAYMENT SECTION -->
        <div id="bdPaymentSection" style="margin-top: 20px;">
          <div class="bd-ck-section-title" style="margin-top: 0.5rem;">💳 Payment Method</div>
          <div class="bd-ck-payment-wrap">
            <div class="bd-ck-payment-bar">
              <div class="bd-ck-radio-group" id="bdPaymentRadios">
                <div class="bd-ck-radio-item selected" data-method="cod">
                  <input type="radio" name="payment_method" value="Cash On Delivery" id="codRadio" checked> 
                  <label for="codRadio">💵 Cash On Delivery</label>
                </div>
                @if($bkash_gateway)
                <div class="bd-ck-radio-item" data-method="bkash">
                  <input type="radio" name="payment_method" value="bkash" id="bkashRadio"> 
                  <label for="bkashRadio">📱 bKash</label>
                </div>
                @endif
                @if($shurjopay_gateway)
                <div class="bd-ck-radio-item" data-method="shurjopay">
                  <input type="radio" name="payment_method" value="shurjopay" id="shurjoRadio"> 
                  <label for="shurjoRadio">💳 Shurjopay</label>
                </div>
                @endif
              </div>

              <div class="bd-ck-actions inline-actions">
                <button class="bd-ck-btn bd-ck-btn-primary" type="submit" id="bdContinueBtn">✅ Place Order</button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- RIGHT COLUMN: DYNAMIC ORDER SUMMARY -->
    <div class="bd-ck-summary-col">
      <div class="bd-ck-summary-head">
        <div>
          <div class="bd-ck-section-title" style="border-left-color: #2c6e49;">🛒 Order Summary</div>
          <div class="bd-ck-summary-mini d-block d-md-none">Grand Total: <strong id="bdGrandTotalMini">৳ {{ ($subtotal + $shipping) - $discount }}</strong></div>
        </div>
        <button type="button" class="bd-ck-summary-toggle-btn" id="bdSummaryToggleBtn" aria-expanded="false">View</button>
      </div>

      <div class="bd-ck-summary-body" id="bdSummaryBody">
        <div id="bdCartItemsContainer" class="bd-ck-summary-items">
          @forelse (Cart::instance('shopping')->content() as $value)
            <div class="bd-ck-cart-item" data-rowid="{{ $value->rowId }}" data-unit-price="{{ (float) $value->price }}" data-qty="{{ (int) $value->qty }}">
              <img src="{{ asset($value->options->image) }}" alt="{{ $value->name }}" class="bd-ck-item-image" />
              <div class="bd-ck-item-info">
                <div class="bd-ck-item-name">{{ Str::limit($value->name, 25) }} <span class="bd-ck-item-mult" style="font-size:0.7rem;">× {{ (int) $value->qty }}</span></div>
                <div class="bd-ck-item-meta">
                  @if ($value->options->product_size) Size: {{ $value->options->product_size }} @endif
                  @if ($value->options->product_color) | Color: {{ $value->options->product_color }} @endif
                </div>
              </div>
              <div class="bd-ck-qty-control">
                <button class="bd-ck-qty-btn cart_decrement" data-id="{{ $value->rowId }}" type="button">-</button>
                <span class="bd-ck-qty-num">{{ (int) $value->qty }}</span>
                <button class="bd-ck-qty-btn cart_increment" data-id="{{ $value->rowId }}" type="button">+</button>
              </div>
              <div class="bd-ck-item-price">৳ {{ (float) $value->price * (int) $value->qty }}</div>
              <button class="bd-ck-remove-item remove-cart-item" data-id="{{ $value->rowId }}" type="button" aria-label="Remove"><i class="fas fa-trash-alt"></i></button>
            </div>
          @empty
            <div style="padding: 20px; text-align:center;">Your cart is empty.<br><a href="{{ url('/products') }}" style="color:#2c6e49;">Shop now</a></div>
          @endforelse
        </div>

        <!-- COUPON SECTION -->
        @if($isCouponEnabled)
        <div class="bd-ck-coupon-area">
          <input type="text" id="bdCouponInput" class="bd-ck-coupon-input" name="coupon_code" placeholder="Coupon code (SAVE10 / WELCOME5)" value="{{ $couponCode }}">
          <button id="bdApplyCouponBtn" class="bd-ck-apply-coupon" type="button">Apply</button>
        </div>
        <div id="bdCouponStatus" class="bd-ck-coupon-status" style="display: none;"></div>
        @endif

        <!-- TOTALS -->
        <div id="bdTotalsPanel">
          <div class="bd-ck-total-row"><span>Subtotal</span><span id="bdSubtotal">৳ {{ $subtotal }}</span></div>
          <div class="bd-ck-total-row"><span>Shipping</span><span id="bdShippingFee">৳ {{ $shipping }}</span></div>
          <div class="bd-ck-total-row"><span>Discount (coupon)</span><span id="bdDiscountAmount">- ৳ {{ $discount }}</span></div>
          <div class="bd-ck-total-row bd-ck-grand"><span>Grand Total</span><span id="bdGrandTotal">৳ {{ ($subtotal + $shipping) - $discount }}</span></div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="bdToastMsg" class="bd-ck-toast"></div>
@endsection

@push('scripts')
<script>
  // Toast function
  function showToast(msg, isErr = false) {
    const toast = document.getElementById('bdToastMsg');
    toast.innerText = msg;
    toast.style.background = isErr ? '#b91c1c' : '#2c6e49';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
  }

  // Payment method selection
  document.querySelectorAll('.bd-ck-radio-item').forEach(item => {
    item.addEventListener('click', function() {
      const radio = this.querySelector('input[type="radio"]');
      radio.checked = true;
      document.querySelectorAll('.bd-ck-radio-item').forEach(i => i.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  // Initial selection
  const checkedRadio = document.querySelector('.bd-ck-radio-item input:checked');
  if (checkedRadio) {
    checkedRadio.closest('.bd-ck-radio-item').classList.add('selected');
  }

  // Shipping area change
  (function () {
    const areaSelect = document.getElementById('bdAreaSelect');
    if (!areaSelect) return;

    const shippingFeeEl = document.getElementById('bdShippingFee');

    areaSelect.addEventListener('change', async function () {
      const id = this.value;
      if (!id) return;

      try {
        const res = await fetch(@json(route('shipping.charge')) + '?id=' + encodeURIComponent(id), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!res.ok || !data || data.status !== true) {
          return;
        }
        const amount = Number(data.amount) || 0;
        
        if (shippingFeeEl) {
          shippingFeeEl.textContent = '৳ ' + amount;
        }
        renderTotals();
        showToast('Shipping updated: ৳ ' + amount, false);
      } catch (e) {
        console.error('Shipping update failed', e);
      }
    });
  })();

  // Order summary mobile toggle
  (function () {
    const summaryCol = document.querySelector('.bd-ck-summary-col');
    const toggleBtn = document.getElementById('bdSummaryToggleBtn');
    if (!summaryCol || !toggleBtn) return;

    // Collapsed by default on small screens
    if (window.matchMedia && window.matchMedia('(max-width: 780px)').matches) {
      summaryCol.classList.remove('is-open');
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.textContent = 'View';
    } else {
      summaryCol.classList.add('is-open');
      toggleBtn.setAttribute('aria-expanded', 'true');
      toggleBtn.textContent = 'Hide';
    }

    toggleBtn.addEventListener('click', function () {
      const isOpen = summaryCol.classList.toggle('is-open');
      toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggleBtn.textContent = isOpen ? 'Hide' : 'View';
    });
  })();

  // Money helpers
  const parseMoney = (value) => {
    const raw = (value ?? '').toString();
    const num = Number(raw.replace(/[^\d.-]/g, ''));
    return Number.isFinite(num) ? num : 0;
  };
  const formatMoney = (amount) => {
    const n = Math.round(Number(amount) || 0);
    return '৳ ' + n;
  };

  const getSubtotal = () => {
    let subtotal = 0;
    document.querySelectorAll('.bd-ck-cart-item').forEach((item) => {
      const unit = parseMoney(item.getAttribute('data-unit-price') || '0');
      const qty = parseInt(item.getAttribute('data-qty') || '0', 10) || 0;
      subtotal += unit * qty;
    });
    return subtotal;
  };
  const getShipping = () => parseMoney(document.getElementById('bdShippingFee')?.textContent || '0');
  const getDiscount = () => parseMoney(document.getElementById('bdDiscountAmount')?.textContent || '0');

  function renderTotals() {
    const subtotal = getSubtotal();
    const shipping = getShipping();
    const discount = getDiscount();
    const grand = subtotal + shipping - discount;

    const subtotalEl = document.getElementById('bdSubtotal');
    const grandEl = document.getElementById('bdGrandTotal');
    const grandMiniEl = document.getElementById('bdGrandTotalMini');

    if (subtotalEl) subtotalEl.textContent = formatMoney(subtotal);
    if (grandEl) grandEl.textContent = formatMoney(grand);
    if (grandMiniEl) grandMiniEl.textContent = formatMoney(grand);
  }

  const setCartItemUi = (rowId, qty) => {
    const item = document.querySelector(`.bd-ck-cart-item[data-rowid="${rowId}"]`);
    if (!item) return;
    const qtyNum = item.querySelector('.bd-ck-qty-num');
    const multEl = item.querySelector('.bd-ck-item-mult');
    const priceEl = item.querySelector('.bd-ck-item-price');
    const unit = parseMoney(item.getAttribute('data-unit-price') || '0');

    const safeQty = Math.max(1, parseInt(qty || '1', 10) || 1);
    item.setAttribute('data-qty', String(safeQty));
    if (qtyNum) qtyNum.textContent = String(safeQty);
    if (multEl) multEl.textContent = '× ' + String(safeQty);
    if (priceEl) priceEl.textContent = formatMoney(unit * safeQty);
  };

  const setCartItemPending = (rowId, pending) => {
    const item = document.querySelector(`.bd-ck-cart-item[data-rowid="${rowId}"]`);
    if (!item) return;
    item.classList.toggle('is-pending', !!pending);
    item.querySelectorAll('button').forEach((btn) => {
      btn.disabled = !!pending;
    });
  };

  async function updateCartQty(rowId, delta) {
    const item = document.querySelector(`.bd-ck-cart-item[data-rowid="${rowId}"]`);
    if (!item) return;

    const prevQty = parseInt(item.getAttribute('data-qty') || '1', 10) || 1;
    let nextQty = prevQty + (parseInt(delta || '0', 10) || 0);
    if (nextQty < 1) nextQty = 1;

    // Optimistic UI
    setCartItemPending(rowId, true);
    setCartItemUi(rowId, nextQty);
    renderTotals();

    try {
      const res = await fetch('{{ url('/cart-update') }}/' + encodeURIComponent(rowId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ quantity: nextQty })
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data || data.status !== 'success') {
        const fallbackQty = (data && data.quantity) ? parseInt(data.quantity, 10) : prevQty;
        setCartItemUi(rowId, fallbackQty);
        renderTotals();
        showToast((data && data.message) ? data.message : 'Failed to update quantity', true);
        return;
      }

      // Optional: sync header cart badges (count = total quantity)
      if (typeof setCompareWishlistAndCartData === 'function') {
        setCompareWishlistAndCartData();
      }
    } catch (e) {
      setCartItemUi(rowId, prevQty);
      renderTotals();
      showToast('Failed to update quantity', true);
    } finally {
      setCartItemPending(rowId, false);
    }
  }

  async function removeCartItem(rowId) {
    if (!confirm('Remove this item from cart?')) return;

    setCartItemPending(rowId, true);
    try {
      const res = await fetch('{{ url('/remove-from-cart') }}/' + encodeURIComponent(rowId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data || data.status !== 'success') {
        showToast((data && data.message) ? data.message : 'Failed to remove item', true);
        return;
      }

      document.querySelector(`.bd-ck-cart-item[data-rowid="${rowId}"]`)?.remove();
      renderTotals();

      if (typeof updateCartCountBadge === 'function' && data.cartItemCount !== undefined && data.cartItemCount !== null) {
        updateCartCountBadge(0, data.cartItemCount);
      } else if (typeof setCompareWishlistAndCartData === 'function') {
        setCompareWishlistAndCartData();
      }
      showToast('Removed from cart', false);
    } catch (e) {
      showToast('Failed to remove item', true);
    } finally {
      setCartItemPending(rowId, false);
    }
  }

  // Coupon functionality
  @if($isCouponEnabled)
  document.getElementById('bdApplyCouponBtn')?.addEventListener('click', async function() {
    const code = document.getElementById('bdCouponInput').value.trim();
    if (!code) {
      showToast('Please enter coupon code', true);
      return;
    }
    
    const statusEl = document.getElementById('bdCouponStatus');
    const discountEl = document.getElementById('bdDiscountAmount');

    try {
      const res = await fetch(@json(route('coupon.apply')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ coupon_code: code, subtotal: getSubtotal() })
      });
      const data = await res.json().catch(() => ({}));
      if (!data || data.status !== 'success') {
        showToast(data.message || 'Invalid coupon code', true);
        if (statusEl) {
          statusEl.style.display = 'flex';
          statusEl.textContent = data.message || 'Invalid coupon code';
        }
        return;
      }

      if (discountEl) {
        discountEl.textContent = '- ' + formatMoney(data.amount || 0);
      }
      if (statusEl) {
        statusEl.style.display = 'flex';
        statusEl.textContent = data.message || 'Coupon applied successfully';
      }
      renderTotals();
      showToast('Coupon applied successfully!', false);
    } catch (e) {
      showToast('Failed to apply coupon', true);
    }
  });
  @endif

  // Delegated click handlers (works after DOM updates)
  document.addEventListener('click', function (e) {
    const inc = e.target.closest('.cart_increment');
    if (inc) {
      e.preventDefault();
      const rowId = inc.getAttribute('data-id');
      if (rowId) updateCartQty(rowId, 1);
      return;
    }
    const dec = e.target.closest('.cart_decrement');
    if (dec) {
      e.preventDefault();
      const rowId = dec.getAttribute('data-id');
      if (rowId) updateCartQty(rowId, -1);
      return;
    }
    const rem = e.target.closest('.remove-cart-item');
    if (rem) {
      e.preventDefault();
      const rowId = rem.getAttribute('data-id');
      if (rowId) removeCartItem(rowId);
      return;
    }
  });

  // Initial totals
  renderTotals();

  // 🎯 InitiateCheckout Event: Pixel + CAPI (fires when checkout page loads)
  (function() {
    const checkoutData = {
      total: {{ ($subtotal + $shipping) - $discount }},
      currency: 'BDT',
      num_items: {{ Cart::instance('shopping')->count() }},
      product_ids: [
        @foreach(Cart::instance('shopping')->content() as $item)
          {{ (int) ($item->id ?? 0) }},
        @endforeach
      ]
    };

    function fireInitiateCheckout() {
      // window.facebookTracker — already initialized singleton
      const tracker = window.facebookTracker;
      if (tracker && typeof tracker.trackInitiateCheckout === 'function') {
        tracker.trackInitiateCheckout(checkoutData);
      } else if (typeof fbq === 'function') {
        fbq('track', 'InitiateCheckout', {
          value: checkoutData.total,
          currency: checkoutData.currency,
          content_ids: checkoutData.product_ids,
          content_type: 'product',
          num_items: checkoutData.num_items
        });
      }
    }

    if (document.readyState === 'complete') fireInitiateCheckout();
    else window.addEventListener('load', fireInitiateCheckout);
  })();
</script>
@endpush
