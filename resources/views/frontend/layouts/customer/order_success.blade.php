@extends('frontend.layouts.app')
@section('title','Order Success')
@section('content')
@push('css')
 <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/checkout-steps.css') }}">
 <style>
  .os-wrap{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:6px;padding:18px;color:#000}
  .os-msg{display:flex;gap:14px;align-items:flex-start}
  .os-msg-media{width:64px;height:64px;flex:0 0 auto;border:1px solid rgba(0,0,0,.08);border-radius:6px;display:flex;align-items:center;justify-content:center;background:#f8fafc}
  .os-msg-media img{max-width:46px;width:100%;height:auto}
  .os-msg-title{margin:0;font-weight:900;font-size:1.25rem;line-height:1.25;color:#000}
  .os-msg-sub{margin:6px 0 0 0;color:#000;opacity:.75;font-weight:600}
  .os-anim{animation:osFadeUp .55s ease-out both}
  .os-anim.delay{animation-delay:.12s}
  @keyframes osFadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

  .os-table{border:1px solid rgba(0,0,0,.08);border-radius:6px;overflow:hidden}
  .os-table .table{margin:0}
  .os-table td,.os-table th{color:#000}
 </style>
@endpush

@php
    $paymentMethod = optional($order->payment)->payment_method;
    $shippingName = optional($order->shipping)->name;
    $shippingPhone = optional($order->shipping)->phone;
    $shippingAddress = optional($order->shipping)->address;
    $shippingArea = optional($order->shipping)->area;
    $subtotal = 0;
    foreach (($order->orderdetails ?? []) as $d) {
        $subtotal += ((float) ($d->sale_price ?? 0)) * ((int) ($d->qty ?? 0));
    }
    $discount = (float) ($order->discount ?? 0);
    $shipping = (float) ($order->shipping_charge ?? 0);
    $grandTotal = (float) ($order->amount ?? ($subtotal + $shipping - $discount));
@endphp

<main>
    <div class="container py-60">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div id="order-confirmation-root">
                    <div class="os-wrap mb-4">
                        <div class="os-msg">
                            <div class="os-msg-media os-anim">
                                <img src="{{ asset('assets/templates/basic/images/order-completed.gif') }}" alt="Order Completed">
                            </div>
                            <div>
                                <h3 class="os-msg-title os-anim">আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে!</h3>
                                <p class="os-msg-sub os-anim delay">আমাদের প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবে। কেনাকাটার জন্য ধন্যবাদ।</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
                        <button type="button" class="btn btn-outline--light h-45" id="orderScreenshotBtn">Screenshot</button>
                        <button type="button" class="btn btn-outline--light h-45" id="orderPdfBtn">Download PDF</button>
                        <a href="{{ route('home') }}" class="btn btn-outline--light h-45">Go To Home</a>
                    </div>

                    <h5 class="my-3">অর্ডারের তথ্য</h5>
                    <div class="os-table mb-4">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <tbody>
                                <tr>
                                    <th style="width: 30%;">ইনভয়েস আইডি</th>
                                    <td><strong>{{ $order->invoice_id ?? $order->id }}</strong></td>
                                </tr>
                                <tr>
                                    <th>তারিখ</th>
                                    <td>{{ optional($order->created_at)->format('d-m-y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>ফোন</th>
                                    <td>{{ $shippingPhone ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>পেমেন্ট</th>
                                    <td>{{ $paymentMethod ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>ডেলিভারি এরিয়া</th>
                                    <td>{{ $shippingArea ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>ঠিকানা</th>
                                    <td>
                                        <div><strong>{{ $shippingName ?: 'N/A' }}</strong></div>
                                        <div>{{ $shippingAddress ?: '' }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>মোট</th>
                                    <td><strong>৳ {{ number_format($grandTotal, 0) }}</strong></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h5 class="my-3">Items</h5>
                    <div class="os-table mb-3">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($order->orderdetails as $value)
                                    @php($lineTotal = ((float) ($value->sale_price ?? 0)) * ((int) ($value->qty ?? 0)))
                                    <tr>
                                        <td>{{ $value->product_name }}</td>
                                        <td class="text-end">{{ (int) $value->qty }}</td>
                                        <td class="text-end"><strong>৳ {{ number_format($lineTotal, 0) }}</strong></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <th class="text-end">Net Total</th>
                                    <td></td>
                                    <td class="text-end"><strong id="net_total">৳{{ number_format($subtotal, 0) }}</strong></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Shipping</th>
                                    <td></td>
                                    <td class="text-end"><strong id="cart_shipping_cost">৳{{ number_format($shipping, 0) }}</strong></td>
                                </tr>
                                @if($discount > 0)
                                <tr>
                                    <th class="text-end">Discount</th>
                                    <td></td>
                                    <td class="text-end"><strong>- ৳{{ number_format($discount, 0) }}</strong></td>
                                </tr>
                                @endif
                                <tr>
                                    <th class="text-end">Grand Total</th>
                                    <td></td>
                                    <td class="text-end"><strong id="grand_total">৳{{ number_format($grandTotal, 0) }}</strong></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

<script>
    (function () {
        const root = document.getElementById('order-confirmation-root');
        const screenshotBtn = document.getElementById('orderScreenshotBtn');
        const pdfBtn = document.getElementById('orderPdfBtn');

        if (!root || !screenshotBtn || !pdfBtn) {
            return;
        }

        const safeFileName = (name) => {
            return (name || 'order')
                .toString()
                .replace(/[^a-z0-9\-_]+/gi, '-')
                .replace(/-+/g, '-')
                .replace(/(^-|-$)/g, '')
                .toLowerCase();
        };

        const fileBase = safeFileName(@json(($order->invoice_id ?? ('order-' . $order->id))));

        screenshotBtn.addEventListener('click', async () => {
            const canvas = await html2canvas(root, { scale: 2, useCORS: true });
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = `${fileBase}.png`;
            document.body.appendChild(link);
            link.click();
            link.remove();
        });

        pdfBtn.addEventListener('click', async () => {
            const canvas = await html2canvas(root, { scale: 2, useCORS: true });
            const imgData = canvas.toDataURL('image/png');

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');

            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();

            const imgProps = pdf.getImageProperties(imgData);
            const imgWidth = pageWidth;
            const imgHeight = (imgProps.height * imgWidth) / imgProps.width;

            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
            heightLeft -= pageHeight;

            while (heightLeft > 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
                heightLeft -= pageHeight;
            }

            pdf.save(`${fileBase}.pdf`);
        });
    })();
</script>
@endpush

{{-- 🎯 Purchase Event: Pixel + CAPI (Industry Standard)
     The id is minted by the server when the order row is committed and passed in
     as $purchaseEventId; browser and CAPI must send the same value or Meta counts
     the sale twice. The script only renders for the visit that follows checkout. --}}
@if(!empty($purchaseEventId))
@push('scripts')
<script>
(function() {
    'use strict';
    
    const purchaseData = {
        total: {{ $grandTotal }},
        currency: 'BDT',
        order_id: '{{ $order->id }}',
        num_items: {{ count($order->orderdetails ?? []) }},
        product_ids: [
            @foreach(($order->orderdetails ?? []) as $detail)
                {{ (int) ($detail->product_id ?? 0) }},
            @endforeach
        ]
    };

    function firePurchase() {
        // window.facebookTracker — already initialized singleton with all methods
        const tracker = window.facebookTracker;
        const purchaseEventId = '{{ $purchaseEventId }}';
        if (tracker && typeof tracker.trackPurchase === 'function') {
            tracker.trackPurchase(purchaseData, purchaseEventId);
        } else if (typeof fbq === 'function') {
            fbq('track', 'Purchase', {
                value: purchaseData.total,
                currency: purchaseData.currency,
                content_ids: purchaseData.product_ids,
                content_type: 'product',
                num_items: purchaseData.num_items,
                order_id: purchaseData.order_id
            }, { eventID: purchaseEventId });
        }

        try {
            if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.flush === 'function') {
                window.__EVENT_LOGGER__.flush();
            }
        } catch (e) {}
    }

    if (document.readyState === 'complete') firePurchase();
    else window.addEventListener('load', firePurchase);
})();
</script>
@endpush
@endif
