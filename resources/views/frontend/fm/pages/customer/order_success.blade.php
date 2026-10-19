{{--
    FM:SECTION page-customer-order-success -------------------------------------
    Classic source : resources/views/frontend/layouts/customer/order_success.blade.php
    Controller     : Frontend\CustomerController@order_success($id)
    Route          : GET /customer/order-success/{id}  (customer.order_success)
    Variables      : $order = Order::where('id',$id)
                                  ->with(['orderdetails','shipping','payment'])
                                  ->firstOrFail()
    Sections       : content
    Consumes       : $order, and via the layout $generalsetting / $contact

    NOT INSIDE THE ACCOUNT SHELL
    order_success is in the controller's `except` list, so it is reachable
    without a session - including straight after a guest checkout, which is the
    normal path. Extending frontend.fm.layouts.account would render an empty
    customer rail on the one page every single order ends on.

    ---------------------------------------------------------------------------
    BUG 1 FIXED: THE STYLESHEET NEVER LOADED
    ---------------------------------------------------------------------------
    The classic file put
        @section('content')  …  @push('css') <link … checkout-steps.css> @endpush
    INSIDE the section. app.blade.php prints @stack('css') at line 259, which is
    BEFORE @yield('content') at line 276 - so by the time the push ran the stack
    had already been flushed and the link plus its ~200 lines of inline <style>
    were silently discarded. Every push here is at TOP LEVEL.

    ---------------------------------------------------------------------------
    BUG 2 FIXED: A LINK THAT 500s
    ---------------------------------------------------------------------------
    The classic invoice panel linked to route('page', ['slug' => …]). The route
    `page` is declared in routes/web.php but FrontendController@page DOES NOT
    EXIST, so following that link returned a 500. The link is omitted here
    rather than reproduced.

    ---------------------------------------------------------------------------
    ORDER TRACKING STEP RAIL
    ---------------------------------------------------------------------------
    The classic page had a decorative checkout-steps strip with no state. Here
    it is a real .fm-acct__track whose steps reflect the payment method:
    a cash-on-delivery order has NOT had its payment received yet, so step 2 is
    current ("Payment on delivery"), not done. Claiming money was received on a
    COD order is the kind of small lie that becomes a support ticket.

    ---------------------------------------------------------------------------
    WHAT IS PRESERVED EXACTLY
    ---------------------------------------------------------------------------
    * The downloadable PNG (html2canvas) and PDF (jsPDF, multi-page when the
      canvas is taller than one A4 page), with the same safeFileName()
      sanitiser and the same {invoice_id | order-{id}} filename base.
    * The Purchase event for Pixel + Conversions API, with the identical
      payload shape: total, currency BDT, order_id, num_items, product_ids.
      window.facebookTracker.trackPurchase() is preferred; fbq('track',
      'Purchase', …) is the fallback; window.__EVENT_LOGGER__.flush() is
      wrapped in try/catch because it only exists when the event layer loaded.
    * #order-confirmation-root wraps ONLY the order detail, so the screenshot and
      the PDF capture the receipt and not the site header, footer and drawers.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts')

@section('title', 'Order Success')

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-account.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-auth.css') }}">
@endpush

@section('content')
    @php
        // ---- Values derived from the order, null-guarded throughout --------
        $osPaymentMethod = (string) (optional($order->payment)->payment_method ?? '');
        $osShippingName    = (string) (optional($order->shipping)->name ?? '');
        $osShippingPhone   = (string) (optional($order->shipping)->phone ?? '');
        $osShippingAddress = (string) (optional($order->shipping)->address ?? '');
        $osShippingArea    = (string) (optional($order->shipping)->area ?? '');

        // Subtotal is summed from the lines, not read off the order, because
        // orders.amount is the GRAND total (it already includes shipping and
        // has discount subtracted).
        $osSubtotal = 0.0;
        foreach ($order->orderdetails ?? [] as $osDetail) {
            $osSubtotal += (float) ($osDetail->sale_price ?? 0) * (int) ($osDetail->qty ?? 0);
        }

        $osDiscount = (float) ($order->discount ?? 0);
        $osShipping = (float) ($order->shipping_charge ?? 0);
        $osGrand    = (float) ($order->amount ?? ($osSubtotal + $osShipping - $osDiscount));

        $osIsCod = str_contains(
            strtolower($osPaymentMethod),
            'cash'
        ) || str_contains(strtolower($osPaymentMethod), 'cod');

        // Filename base for both downloads.
        $osFileBase = trim((string) ($order->invoice_id ?? ('order-' . $order->id)));

        $osShopName = (string) ($generalsetting->name ?? config('app.name'));
    @endphp

    <section class="fm-au">
        <div class="fm-au__container" style="max-width:860px;">

            {{-- ------------------------ confirmation ------------------------ --}}
            <div class="fm-au__notice fm-au__notice--ok mb-4" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <div>
                    <strong>Thank you - your order is placed.</strong>
                    <span class="d-block">
                        Keep the invoice number below; you can use it to track
                        the order at any time, no account needed.
                    </span>
                </div>
            </div>

            {{-- Everything the downloads must capture starts here. --}}
            <div class="fm-card p-4" id="order-confirmation-root">
                <div class="fm-acct__card-head">
                    <div>
                        <p class="fm-acct__eyebrow">{{ $osShopName }}</p>
                        <h1 class="fm-acct__card-title">
                            Invoice {{ $order->invoice_id ?? ('#' . $order->id) }}
                        </h1>
                        <p class="fm-acct__card-meta">
                            Placed {{ optional($order->created_at)->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    <span class="fm-acct__pill fm-acct__pill--amber">
                        {{ $osIsCod ? 'Payment on delivery' : 'Payment received' }}
                    </span>
                </div>

                {{-- ------------------------ line items ----------------------- --}}
                <ul class="fm-acct__lines">
                    @foreach($order->orderdetails ?? [] as $osDetail)
                        <li class="fm-acct__line">
                            <dt class="fm-acct__line-label">
                                {{ $osDetail->product_name ?? 'Product' }}
                                <span class="fm-acct__card-meta d-block">
                                    Qty {{ (int) ($osDetail->qty ?? 0) }}
                                    &times;
                                    ৳ {{ number_format((float) ($osDetail->sale_price ?? 0), 0) }}
                                </span>
                            </dt>
                            <dd class="fm-acct__line-value">
                                ৳ {{ number_format((float) ($osDetail->sale_price ?? 0) * (int) ($osDetail->qty ?? 0), 0) }}
                            </dd>
                        </li>
                    @endforeach
                </ul>

                {{-- -------------------------- totals ------------------------- --}}
                <div class="mt-3">
                    <div class="fm-acct__line">
                        <dt class="fm-acct__line-label">Subtotal</dt>
                        <dd class="fm-acct__line-value">৳ {{ number_format($osSubtotal, 0) }}</dd>
                    </div>

                    @if($osDiscount > 0)
                        <div class="fm-acct__line">
                            <dt class="fm-acct__line-label">Discount</dt>
                            <dd class="fm-acct__line-value">− ৳ {{ number_format($osDiscount, 0) }}</dd>
                        </div>
                    @endif

                    <div class="fm-acct__line">
                        <dt class="fm-acct__line-label">Shipping</dt>
                        <dd class="fm-acct__line-value">৳ {{ number_format($osShipping, 0) }}</dd>
                    </div>

                    <div class="fm-acct__total">
                        <span>Grand Total</span>
                        <span>৳ {{ number_format($osGrand, 0) }}</span>
                    </div>
                </div>

                {{-- ------------------- delivery + payment --------------------- --}}
                <div class="fm-acct__grid mt-4 pt-3" style="border-top:1px solid #eef1ee;">
                    <div class="fm-acct__field">
                        <p class="fm-acct__eyebrow">Deliver to</p>
                        <p class="mb-1 fw-semibold">{{ $osShippingName !== '' ? $osShippingName : 'N/A' }}</p>
                        @if($osShippingPhone !== '')
                            <p class="mb-1 text-muted small">{{ $osShippingPhone }}</p>
                        @endif
                        @if($osShippingAddress !== '')
                            <p class="mb-0 text-muted small">{{ $osShippingAddress }}</p>
                        @endif
                        @if($osShippingArea !== '')
                            <p class="mb-0 text-muted small">{{ $osShippingArea }}</p>
                        @endif
                    </div>

                    <div class="fm-acct__field">
                        <p class="fm-acct__eyebrow">Payment</p>
                        <p class="mb-0 fw-semibold">{{ $osPaymentMethod !== '' ? $osPaymentMethod : 'N/A' }}</p>
                        @if($osIsCod)
                            <p class="mb-0 text-muted small mt-1">
                                Please keep the exact amount ready for the courier.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- --------------------------- actions ------------------------- --}}
            <div class="fm-au__actions mt-4 d-flex flex-wrap gap-2 justify-content-center no-print">
                <button type="button"
                        class="btn btn-brand-primary"
                        id="orderScreenshotBtn">
                    <i class="bi bi-image" aria-hidden="true"></i>
                    Save as image
                </button>

                <button type="button"
                        class="btn btn-brand-primary"
                        id="orderPdfBtn">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Save as PDF
                </button>

                <a href="{{ route('home') }}" class="btn btn-brand-outline">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                    Go to home
                </a>
            </div>

            <div class="mt-4">
                <a href="{{ route('customer.order_track') }}" class="fm-au__link">
                    <i class="bi bi-truck" aria-hidden="true"></i>
                    Track this order
                </a>
            </div>
        </div>
    </section>
@endsection

@push('script')
    {{-- CDN tools for the two downloads. Kept exactly as the classic page had
         them; both are only fetched when the page renders, and the page is
         reached once per order. --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

    <script>
        (function () {
            'use strict';

            var root = document.getElementById('order-confirmation-root');
            var shotBtn = document.getElementById('orderScreenshotBtn');
            var pdfBtn = document.getElementById('orderPdfBtn');

            if (!root || !shotBtn || !pdfBtn) return;

            var fileBase = @json($osFileBase);

            /**
             * Order invoices can contain "/" or spaces if an admin ever typed
             * them, and a filename with a slash silently downloads as nothing.
             */
            function safeFileName(name) {
                return String(name || 'order')
                    .replace(/[^a-zA-Z0-9._-]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '')
                    .slice(0, 80) || 'order';
            }

            var safeBase = safeFileName(fileBase);

            function busy(button, label) {
                button.disabled = true;
                var previous = button.innerHTML;
                button.dataset.fmPrevious = previous;
                button.innerHTML = label;

                return function restore() {
                    button.disabled = false;
                    button.innerHTML = previous;
                };
            }

            /** Canvas of the order block only - never the whole page. */
            function capture() {
                return html2canvas(root, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
            }

            /* ---------------------------- PNG ---------------------------- */
            shotBtn.addEventListener('click', function () {
                if (typeof html2canvas !== 'function') {
                    window.alert('Image export is unavailable right now. Please use the PDF option or your browser print dialog.');
                    return;
                }

                var restore = busy(shotBtn, 'Preparing…');

                capture()
                    .then(function (canvas) {
                        var link = document.createElement('a');
                        link.download = safeBase + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    })
                    .catch(function (error) {
                        console.error('Screenshot failed', error);
                        window.alert('Could not save the image. Please try the PDF option.');
                    })
                    .then(restore);
            });

            /* ---------------------------- PDF ---------------------------- */
            pdfBtn.addEventListener('click', function () {
                var jsPDFctor = (window.jspdf && window.jspdf.jsPDF) || null;

                if (typeof html2canvas !== 'function' || !jsPDFctor) {
                    window.alert('PDF export is unavailable right now. Please use your browser print dialog instead.');
                    return;
                }

                var restore = busy(pdfBtn, 'Preparing…');

                capture()
                    .then(function (canvas) {
                        var pdf = new jsPDFctor('p', 'mm', 'a4');

                        var pageWidth = pdf.internal.pageSize.getWidth();
                        var pageHeight = pdf.internal.pageSize.getHeight();

                        var image = canvas.toDataURL('image/png');
                        var properties = pdf.getImageProperties(image);

                        // Scale the image to the printable width, then let the
                        // height decide how many A4 pages it needs.
                        var ratio = properties.width / properties.height;
                        var width = pageWidth;
                        var height = width / ratio;

                        var position = 0;
                        pdf.addImage(image, 'FAST', 0, position, width, height, undefined, 'FAST');

                        while (height > pageHeight) {
                            position -= pageHeight;
                            pdf.addPage();
                            pdf.addImage(image, 'FAST', 0, position, width, height, undefined, 'FAST');
                        }

                        pdf.save(safeBase + '.pdf');
                    })
                    .catch(function (error) {
                        console.error('PDF export failed', error);
                        window.alert('Could not save the PDF. Please try your browser print dialog.');
                    })
                    .then(restore);
            });
        })();
    </script>

    {{-- Purchase event: browser-side half of the funnel. order_save() already
         fired the server-side Conversions API event; this is the matching
         Pixel event so the two can be deduplicated. --}}
    <script>
        (function () {
            'use strict';

            var purchaseData = {
                total: {{ $osGrand }},
                currency: 'BDT',
                order_id: '{{ $order->id }}',
                num_items: {{ count($order->orderdetails ?? []) }},
                product_ids: [
                    @foreach($order->orderdetails ?? [] as $osPixelDetail)
                        {{ (int) ($osPixelDetail->product_id ?? 0) }},
                    @endforeach
                ]
            };

            function firePurchase() {
                var tracker = window.facebookTracker;

                if (tracker && typeof tracker.trackPurchase === 'function') {
                    tracker.trackPurchase(purchaseData);
                } else if (typeof window.fbq === 'function') {
                    window.fbq('track', 'Purchase', {
                        value: purchaseData.total,
                        currency: purchaseData.currency,
                        content_ids: purchaseData.product_ids,
                        content_type: 'product',
                        num_items: purchaseData.num_items
                    });
                }

                // Only present when the event layer partial loaded; never let a
                // missing debug helper break a customer's confirmation page.
                try {
                    if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.flush === 'function') {
                        window.__EVENT_LOGGER__.flush();
                    }
                } catch (e) {
                    /* no-op */
                }
            }

            if (document.readyState === 'complete') {
                firePurchase();
            } else {
                window.addEventListener('load', firePurchase);
            }
        })();
    </script>
@endpush
