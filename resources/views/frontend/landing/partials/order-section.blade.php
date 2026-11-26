@php
    // The design may render this form inside its own content; the shared
    // layout skips its own include once the form has been rendered.
    $tpl->markOrderRendered();

    $productName = $tpl->text('hero_title', $tpl->productName());
    $productId = $tpl->productId();
    $campaignId = $tpl->campaignId();
    $unitPrice = $tpl->priceCurrentNumber();
    $oldPrice = $tpl->priceOldNumber();
    $image = $tpl->text('hero_image');
    if ($image === '') {
        $gallery = $tpl->raw('gallery_image');
        if (is_array($gallery) && isset($gallery[0])) {
            $image = $tpl->imageUrl((string) $gallery[0]);
        }
    }
    $brandName = $tpl->text('brand_name', config('app.name', 'ZEvo'));
    $brandLogo = $tpl->text('brand_logo');
    $quantityOptions = $tpl->raw('quantity_options');
    if (!is_array($quantityOptions) || $quantityOptions === []) {
        $quantityOptions = [1, 2, 3];
    }
    $quantityOptions = array_values(array_filter(array_map('intval', $quantityOptions), fn ($qty) => $qty > 0));
    if ($quantityOptions === []) {
        $quantityOptions = [1, 2, 3];
    }
    // The same delivery areas and payment methods the storefront checkout
    // offers, so a landing order stores exactly what a normal order stores.
    $shippingAreas = \App\Models\ShippingCharge::where('status', 1)->orderBy('id')->get();
    $bkashEnabled = \App\Models\PaymentGateway::where(['status' => 1, 'type' => 'bkash'])->exists();
    $selectedArea = (int) old('area', $shippingAreas->first()?->id);
    $selectedPayment = (string) old('payment_method', 'Cash On Delivery');
    $selectedQty = (int) old('quantity', $quantityOptions[0]);
    if (!in_array($selectedQty, $quantityOptions, true)) {
        $selectedQty = $quantityOptions[0];
    }
    $inside = $tpl->text('delivery_charge_inside', 'ফ্রি');
    $outside = $tpl->text('delivery_charge_outside', 'ফ্রি');
    $submitLabel = $tpl->text('submit_label', 'অর্ডার কনফার্ম করুন');
    $orderError = session('error');
@endphp
<section class="zlt-order" id="landing-order" data-purpose="system-order-form">
    <div class="zlt-container">

        {{-- Order header, taken from the order section of the landing designs:
             the cash-on-delivery badge, the heading and one short line. --}}
        <div class="zlt-order-head">
            <span class="zlt-eyebrow">
                <i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i>
                ক্যাশ অন ডেলিভারি (পণ্য পেয়ে টাকা দিন)
            </span>
            <h2>অর্ডার করতে নিচের তথ্যগুলো পূরণ করুন</h2>
            <p>আপনার নাম, মোবাইল নম্বর এবং ঠিকানা দিন। আমাদের প্রতিনিধি দ্রুত কল করে নিশ্চিত করবেন।</p>
        </div>

        {{-- One form: the product line, the quantity, the delivery details and
             the payment method - the same fields the storefront checkout
             saves, under the same field names, so the order is stored like a
             normal product order. --}}
        <form class="zlt-order-card zlt-order-form" method="POST" action="{{ route('landing.order') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $productId }}">
            @if($campaignId)
                <input type="hidden" name="campaign_id" value="{{ $campaignId }}">
            @endif

            @if($orderError)
                <p class="zlt-alert zlt-alert-error" role="alert">{{ $orderError }}</p>
            @endif

            @if($errors->any())
                <div class="zlt-alert zlt-alert-error" role="alert">
                    <ul class="zlt-alert-list">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($brandLogo !== '')
                <div class="zlt-order-brand">
                    <img class="zlt-order-logo"
                         src="{{ $brandLogo }}"
                         alt="{{ $brandName }}"
                         width="866"
                         height="288"
                         loading="lazy"
                         decoding="async">
                </div>
            @endif

            <div class="zlt-order-product">
                @if($image)
                    <img src="{{ $image }}" alt="{{ $tpl->text('hero_image_alt', $productName) }}" loading="lazy">
                @endif
                <div>
                    <strong>{{ $productName }}</strong>
                    <span class="zlt-price">{{ $tpl->moneyBn($unitPrice) }}</span>
                </div>
            </div>

            {{-- Step 1: quantity --}}
            <section class="zlt-step">
                <h3 class="zlt-step-title">
                    <span class="zlt-step-number" aria-hidden="true">১</span>
                    কত পিস নিতে চান?
                </h3>
                <div class="zlt-option-grid">
                    @foreach($quantityOptions as $quantity)
                        <label class="zlt-option @if($selectedQty === $quantity) is-active @endif">
                            <input type="radio"
                                   name="quantity"
                                   value="{{ $quantity }}"
                                   data-unit-price="{{ $unitPrice }}"
                                   @checked($selectedQty === $quantity)>
                            <span class="zlt-option-title">{{ $tpl->bnNumber($quantity) }} পিস</span>
                            <span class="zlt-option-price">{{ $tpl->moneyBn($unitPrice * $quantity) }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Step 2: delivery details, the fields a normal order saves --}}
            <section class="zlt-step">
                <h3 class="zlt-step-title">
                    <span class="zlt-step-number" aria-hidden="true">২</span>
                    আপনার ডেলিভারি তথ্য দিন
                </h3>

                <div class="zlt-field">
                    <label for="zlt-name">আপনার পূর্ণ নাম <span class="zlt-req">*</span></label>
                    <div class="zlt-input-wrap">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input type="text"
                               id="zlt-name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="যেমন: মোঃ কামরুল হাসান"
                               autocomplete="name"
                               required>
                    </div>
                    @error('name')<p class="zlt-error">{{ $message }}</p>@enderror
                </div>

                <div class="zlt-field">
                    <label for="zlt-phone">মোবাইল ফোন নম্বর (১১ ডিজিট) <span class="zlt-req">*</span></label>
                    <div class="zlt-input-wrap">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                        <input type="tel"
                               id="zlt-phone"
                               name="phone"
                               value="{{ old('phone') }}"
                               placeholder="01700000000"
                               inputmode="numeric"
                               pattern="0[0-9]{10}"
                               maxlength="11"
                               autocomplete="tel"
                               required>
                    </div>
                    @error('phone')<p class="zlt-error">{{ $message }}</p>@enderror
                </div>

                <div class="zlt-field">
                    <label for="zlt-address">সম্পূর্ণ ডেলিভারি ঠিকানা (বাসা/রোড, থানা, জেলা) <span class="zlt-req">*</span></label>
                    <div class="zlt-input-wrap zlt-input-wrap-textarea">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <textarea id="zlt-address"
                                  name="address"
                                  rows="3"
                                  placeholder="যেমন: বাসা নং ১২, রোড নং ৫, সেক্টর ৩, উত্তরা, ঢাকা"
                                  autocomplete="street-address"
                                  required>{{ old('address') }}</textarea>
                    </div>
                    @error('address')<p class="zlt-error">{{ $message }}</p>@enderror
                </div>

                <div class="zlt-field">
                    <label for="zlt-area">ডেলিভারি এলাকা <span class="zlt-req">*</span></label>
                    <select id="zlt-area" name="area" required>
                        @foreach($shippingAreas as $area)
                            <option value="{{ $area->id }}"
                                    data-amount="{{ (float) $area->amount }}"
                                    @selected($selectedArea === (int) $area->id)>
                                {{ $area->name }} — {{ $tpl->moneyBn($area->amount) }}
                            </option>
                        @endforeach
                    </select>
                    @error('area')<p class="zlt-error">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- Step 3: payment method, the same options the checkout offers --}}
            <section class="zlt-step">
                <h3 class="zlt-step-title">
                    <span class="zlt-step-number" aria-hidden="true">৩</span>
                    পেমেন্ট পদ্ধতি
                </h3>
                <div class="zlt-option-grid">
                    <label class="zlt-option @if($selectedPayment === 'Cash On Delivery') is-active @endif">
                        <input type="radio"
                               name="payment_method"
                               value="Cash On Delivery"
                               @checked($selectedPayment === 'Cash On Delivery')>
                        <span class="zlt-option-title">💵 ক্যাশ অন ডেলিভারি</span>
                        <span class="zlt-option-text">পণ্য হাতে পেয়ে টাকা পরিশোধ করুন</span>
                    </label>

                    @if($bkashEnabled)
                        <label class="zlt-option @if($selectedPayment === 'bkash') is-active @endif">
                            <input type="radio"
                                   name="payment_method"
                                   value="bkash"
                                   @checked($selectedPayment === 'bkash')>
                            <span class="zlt-option-title">📱 bKash</span>
                            <span class="zlt-option-text">অর্ডারের পর bKash পেজ থেকে পরিশোধ করুন</span>
                        </label>
                    @endif
                </div>
                @error('payment_method')<p class="zlt-error">{{ $message }}</p>@enderror
            </section>

            {{-- Order summary --}}
            <div class="zlt-order-summary">
                <div>
                    <span>প্রোডাক্ট মূল্য</span>
                    <span id="zlt-line-total">{{ $tpl->moneyBn($unitPrice * $selectedQty) }}</span>
                </div>
                @if($oldPrice > $unitPrice)
                    <div>
                        <span>পূর্বের মূল্য</span>
                        <span class="zlt-strike">{{ $tpl->moneyBn($oldPrice) }}</span>
                    </div>
                @endif
                <div>
                    <span>ডেলিভারি চার্জ</span>
                    <span id="zlt-delivery-charge">{{ $tpl->moneyBn($shippingAreas->first()?->amount ?? 0) }}</span>
                </div>
                <div class="zlt-total">
                    <span>সর্বমোট</span>
                    <span id="zlt-order-total" role="status" aria-live="polite">{{ $tpl->moneyBn($unitPrice * $selectedQty + ($shippingAreas->first()?->amount ?? 0)) }}</span>
                </div>
            </div>

            <button type="submit" class="zlt-submit" @if(!$productId) disabled @endif>
                {{ $submitLabel }}
            </button>

            @if(!$productId)
                <p class="zlt-order-note">এই প্রিভিউতে কোনো প্রোডাক্ট নির্বাচন করা নেই।</p>
            @endif

            <ul class="zlt-order-points">
                <li>পণ্য হাতে পেয়ে টাকা পরিশোধ করুন।</li>
                <li>ঢাকার ভিতরে: {{ $inside }} · ঢাকার বাইরে: {{ $outside }}</li>
                <li>অর্ডার নিশ্চিত হলে আমাদের প্রতিনিধি কল করবেন।</li>
            </ul>
        </form>
    </div>
</section>

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('landing-order');
        if (!form) {
            return;
        }

        var quantityInputs = form.querySelectorAll('input[name="quantity"]');
        var areaSelect = document.getElementById('zlt-area');
        var lineTotal = document.getElementById('zlt-line-total');
        var deliveryCharge = document.getElementById('zlt-delivery-charge');
        var total = document.getElementById('zlt-order-total');
        var formatter = new Intl.NumberFormat('bn-BD', { maximumFractionDigits: 0 });

        function money(value) {
            return '৳' + formatter.format(value);
        }

        function unitPrice() {
            var checked = form.querySelector('input[name="quantity"]:checked');
            if (!checked) {
                return 0;
            }

            return parseFloat(checked.getAttribute('data-unit-price')) || 0;
        }

        function quantity() {
            var checked = form.querySelector('input[name="quantity"]:checked');
            return checked ? (parseInt(checked.value, 10) || 1) : 1;
        }

        function deliveryFee() {
            if (!areaSelect || !areaSelect.value) {
                return 0;
            }

            var option = areaSelect.options[areaSelect.selectedIndex];
            return option ? (parseFloat(option.getAttribute('data-amount')) || 0) : 0;
        }

        function refresh() {
            if (!total) {
                return;
            }

            var line = unitPrice() * quantity();
            var delivery = deliveryFee();

            if (lineTotal) {
                lineTotal.textContent = money(line);
            }
            if (deliveryCharge) {
                deliveryCharge.textContent = money(delivery);
            }

            total.textContent = money(line + delivery);
        }

        Array.prototype.forEach.call(quantityInputs, function (input) {
            input.addEventListener('change', refresh);
        });

        if (areaSelect) {
            areaSelect.addEventListener('change', refresh);
        }

        refresh();
    })();
</script>
@endpush
