@php
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
    $quantityOptions = $tpl->raw('quantity_options');
    if (!is_array($quantityOptions) || $quantityOptions === []) {
        $quantityOptions = [1, 2, 3];
    }
    $quantityOptions = array_values(array_filter(array_map('intval', $quantityOptions), fn ($qty) => $qty > 0));
    if ($quantityOptions === []) {
        $quantityOptions = [1, 2, 3];
    }
    $inside = $tpl->text('delivery_charge_inside', 'ফ্রি');
    $outside = $tpl->text('delivery_charge_outside', 'ফ্রি');
    $submitLabel = $tpl->text('submit_label', 'অর্ডার কনফার্ম করুন');
@endphp
<section class="zlt-order" id="landing-order" data-purpose="system-order-form">
    <div class="zlt-container zlt-order-grid">
        <div class="zlt-order-copy">
            <span class="zlt-eyebrow">ক্যাশ অন ডেলিভারি</span>
            <h2>অর্ডার করতে নিচের তথ্যগুলো পূরণ করুন</h2>
            <p>
                পরিমাণ বেছে নিয়ে অর্ডার নিশ্চিত করুন। নাম, মোবাইল নম্বর ও ঠিকানা পরের ধাপে
                আমাদের মূল চেকআউটে দিবেন।
            </p>
            <ul class="zlt-order-points">
                <li>পণ্য হাতে পেয়ে টাকা পরিশোধ করুন।</li>
                <li>ঢাকার ভিতরে: {{ $inside }} · ঢাকার বাইরে: {{ $outside }}</li>
                <li>অর্ডার নিশ্চিত হলে আমাদের প্রতিনিধি কল করবেন।</li>
            </ul>
        </div>

        <form class="zlt-order-card" method="POST" action="{{ route('landing.order') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $productId }}">
            @if($campaignId)
                <input type="hidden" name="campaign_id" value="{{ $campaignId }}">
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

            <div class="zlt-field">
                <label for="zlt-quantity">পরিমাণ</label>
                <select id="zlt-quantity" name="quantity" data-unit-price="{{ $unitPrice }}">
                    @foreach($quantityOptions as $quantity)
                        <option value="{{ $quantity }}">{{ $tpl->bnNumber($quantity) }} পিস</option>
                    @endforeach
                </select>
            </div>

            <div class="zlt-order-summary">
                <div>
                    <span>প্রোডাক্ট মূল্য</span>
                    <span>{{ $tpl->moneyBn($unitPrice) }}</span>
                </div>
                @if($oldPrice > $unitPrice)
                    <div>
                        <span>পূর্বের মূল্য</span>
                        <span style="text-decoration:line-through">{{ $tpl->moneyBn($oldPrice) }}</span>
                    </div>
                @endif
                <div class="zlt-total">
                    <span>সর্বমোট</span>
                    <span id="zlt-order-total">{{ $tpl->moneyBn($unitPrice) }}</span>
                </div>
            </div>

            <button type="submit" class="zlt-submit" @if(!$productId) disabled @endif>
                {{ $submitLabel }}
            </button>

            @if(!$productId)
                <p class="zlt-order-note">এই প্রিভিউতে কোনো প্রোডাক্ট নির্বাচন করা নেই।</p>
            @else
                <p class="zlt-order-note">
                    আপনার তথ্য সম্পূর্ণ নিরাপদ। পরের ধাপে চেকআউটে নাম, মোবাইল ও ঠিকানা দিবেন।
                </p>
            @endif
        </form>
    </div>
</section>

@push('scripts')
<script>
    (function () {
        var select = document.getElementById('zlt-quantity');
        var total = document.getElementById('zlt-order-total');

        if (!select || !total) {
            return;
        }

        var unit = parseFloat(select.getAttribute('data-unit-price')) || 0;
        var formatter = new Intl.NumberFormat('bn-BD', { maximumFractionDigits: 0 });

        select.addEventListener('change', function () {
            var quantity = parseInt(select.value, 10) || 1;
            total.textContent = '৳' + formatter.format(unit * quantity);
        });
    })();
</script>
@endpush
