{{--
    FM:SECTION page-customer-invoice -------------------------------------------
    Classic source : resources/views/frontend/layouts/customer/invoice.blade.php
    Controller     : Frontend\CustomerController@invoice(Request $request)
    Route          : GET /customer/invoice?id=<order id>  (customer.invoice)
    Variables      : $order = Order::where(['id' => $request->id,
                        'customer_id' => Auth::guard('customer')->user()->id])
                        ->with('orderdetails','payment','shipping','customer')
                        ->firstOrFail()
    Sections       : content
    Consumes       : $order, plus the globally shared $generalsetting and $contact

    IS IT BEHIND THE AUTH MIDDLEWARE? YES. Unlike order_success this route sits
    in the authenticated group, and the controller additionally scopes the query
    to the signed-in customer's own orders - a customer editing ?id= gets a 404,
    not somebody else's invoice. It is still rendered without the account rail,
    because an invoice is a document to be printed, not a page to be browsed,
    and the rail would appear on every printed sheet.

    TWO CLASSIC BUGS FIXED
    1. $order->created_at->format(...) was called unguarded. created_at is
       nullable in the schema; a null there was a fatal on an invoice page.
    2. The classic linked route('page', ['slug' => 'terms-condition']). The
       route `page` is declared in routes/web.php but FrontendController@page
       DOES NOT EXIST, so that link returned a 500. Omitted here.

    The classic also carried its own inline <style> with @media print rules. This
    page relies on the @media print block already in fm-account.css instead -
    one copy of the print rules, not two.

    The print button uses type="button" explicitly. The classic shipped a bare
    <button>, which defaults to type=submit; harmless there because the button
    was outside any form, but it is the kind of default that becomes a bug the
    day someone wraps the panel in a form.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts')

@section('title', 'Invoice ' . ($order->invoice_id ?? ('#' . $order->id)))

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-account.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-auth.css') }}">
@endpush

@section('content')
    @php
        // $generalsetting and $contact are view()->share()d by AppServiceProvider
        // and both are nullable, so every read is guarded.
        $invShop   = (string) ($generalsetting->name ?? config('app.name'));
        $invPhone  = trim((string) ($contact->phone ?? ($generalsetting->support_phone ?? '')));
        $invEmail  = trim((string) ($contact->email ?? ''));
        $invAddr   = trim((string) ($contact->address ?? ''));

        $invLogo   = (string) ($generalsetting->dark_logo ?: ($generalsetting->white_logo ?? ''));
        $invLogoUrl = $invLogo !== '' ? \App\Support\MediaPath::url($invLogo, 'settings') : '';

        $invPayMethod = (string) (optional($order->payment)->payment_method ?? '');
        $invShipName  = (string) (optional($order->shipping)->name ?? '');
        $invShipPhone = (string) (optional($order->shipping)->phone ?? '');
        $invShipAddr  = (string) (optional($order->shipping)->address ?? '');
        $invShipArea  = (string) (optional($order->shipping)->area ?? '');

        // Subtotal from the lines; orders.amount is the grand total.
        $invSubtotal = 0.0;
        foreach ($order->orderdetails ?? [] as $invDetail) {
            $invSubtotal += (float) ($invDetail->sale_price ?? 0) * (int) ($invDetail->qty ?? 0);
        }

        $invDiscount = (float) ($order->discount ?? 0);
        $invShipping = (float) ($order->shipping_charge ?? 0);
        $invGrand    = (float) ($order->amount ?? ($invSubtotal + $invShipping - $invDiscount));
    @endphp

    <section class="fm-au">
        <div class="fm-au__container" style="max-width:860px;">

            {{-- Toolbar. Marked no-print so it never reaches the paper; the
                 @media print block in fm-account.css also drops the container
                 chrome. --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
                <a href="{{ route('customer.orders') }}" class="fm-au__link">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to my orders
                </a>

                <button type="button" class="btn btn-brand-primary" onclick="window.print()">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    Print / Save PDF
                </button>
            </div>

            {{-- ---------------------------- the document --------------------- --}}
            <div class="fm-card p-4" id="fm-invoice">

                <div class="fm-acct__card-head">
                    <div>
                        @if($invLogoUrl !== '')
                            <img src="{{ $invLogoUrl }}"
                                 alt="{{ $invShop }}"
                                 class="max-h-12 w-auto object-contain"
                                 style="max-height:48px;width:auto;object-fit:contain;margin-bottom:.75rem;">
                        @endif
                        <h1 class="fm-acct__card-title">{{ $invShop }}</h1>
                        <p class="fm-acct__card-meta">
                            @if($invAddr !== '')
                                {{ $invAddr }}<br>
                            @endif
                            @if($invPhone !== '')
                                {{ $invPhone }}
                            @endif
                            @if($invPhone !== '' && $invEmail !== '')
                                &middot;
                            @endif
                            @if($invEmail !== '')
                                {{ $invEmail }}
                            @endif
                        </p>
                    </div>

                    <div class="text-md-end">
                        <p class="fm-acct__eyebrow mb-1">Invoice</p>
                        <h2 class="fm-acct__card-title">
                            {{ $order->invoice_id ?? ('#' . $order->id) }}
                        </h2>
                        <p class="fm-acct__card-meta">
                            {{ optional($order->created_at)->format('d M Y') }}
                        </p>
                    </div>
                </div>

                {{-- ------------------------ bill to -------------------------- --}}
                <div class="fm-acct__grid mb-4 pt-3" style="border-top:1px solid #eef1ee;">
                    <div class="fm-acct__field">
                        <p class="fm-acct__eyebrow">Billed to</p>
                        <p class="mb-1 fw-semibold">{{ $invShipName !== '' ? $invShipName : 'N/A' }}</p>
                        @if($invShipPhone !== '')
                            <p class="mb-1 text-muted small">{{ $invShipPhone }}</p>
                        @endif
                        @if($invShipAddr !== '')
                            <p class="mb-0 text-muted small">{{ $invShipAddr }}</p>
                        @endif
                    </div>

                    <div class="fm-acct__field">
                        <p class="fm-acct__eyebrow">Payment method</p>
                        <p class="mb-0 fw-semibold">{{ $invPayMethod !== '' ? $invPayMethod : 'N/A' }}</p>
                    </div>
                </div>

                {{-- --------------------------- items ------------------------- --}}
                <ul class="fm-acct__lines">
                    @foreach($order->orderdetails ?? [] as $invDetail)
                        <li class="fm-acct__line">
                            <dt class="fm-acct__line-label">
                                {{ $invDetail->product_name ?? 'Product' }}
                                <span class="fm-acct__card-meta d-block">
                                    {{ (int) ($invDetail->qty ?? 0) }}
                                    &times;
                                    ৳ {{ number_format((float) ($invDetail->sale_price ?? 0), 0) }}
                                </span>
                            </dt>
                            <dd class="fm-acct__line-value">
                                ৳ {{ number_format((float) ($invDetail->sale_price ?? 0) * (int) ($invDetail->qty ?? 0), 0) }}
                            </dd>
                        </li>
                    @endforeach
                </ul>

                {{-- -------------------------- totals ------------------------- --}}
                <div class="mt-3 ms-auto" style="max-width:320px;">
                    <div class="fm-acct__line">
                        <dt class="fm-acct__line-label">Subtotal</dt>
                        <dd class="fm-acct__line-value">৳ {{ number_format($invSubtotal, 0) }}</dd>
                    </div>

                    @if($invDiscount > 0)
                        <div class="fm-acct__line">
                            <dt class="fm-acct__line-label">Discount</dt>
                            <dd class="fm-acct__line-value">− ৳ {{ number_format($invDiscount, 0) }}</dd>
                        </div>
                    @endif

                    <div class="fm-acct__line">
                        <dt class="fm-acct__line-label">Shipping</dt>
                        <dd class="fm-acct__line-value">৳ {{ number_format($invShipping, 0) }}</dd>
                    </div>

                    <div class="fm-acct__total">
                        <span>Total due</span>
                        <span>৳ {{ number_format($invGrand, 0) }}</span>
                    </div>
                </div>

                @if($invShipArea !== '')
                    <p class="fm-acct__card-meta mt-3 mb-0">
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        Delivery area: {{ $invShipArea }}
                    </p>
                @endif
            </div>

            <p class="fm-au__link mt-3 no-print" style="font-size:.75rem;">
                Keep this invoice for your records.
            </p>
        </div>
    </section>
@endsection
