{{--
    FM:SECTION page-customer-order-track ---------------------------------------
    Classic source : resources/views/frontend/layouts/customer/order_track.blade.php
    Controller     : Frontend\CustomerController@order_track()  (no variables)
    Routes         : GET /customer/order-track  (customer.order_track)
                     GET /track-order           (track.order) - same method
    Sections       : fm-acct-body
    Consumes       : request('invoice_id'), request('phone')

    WHY THIS IS NOT INSIDE THE ACCOUNT SHELL
    order_track is in the controller's `except` list, so it is NOT behind the
    `customer` auth middleware - a guest can search for an order. It therefore
    extends frontend.fm.layouts directly. frontend.fm.layouts.account renders
    the customer rail, which reads Auth::guard('customer') and would be empty
    for exactly the visitor this page exists for.

    FORM
    GET to customer.order_track_result. A GET form needs no @csrf - adding one
    would be meaningless (the token would travel in the query string). This is
    also why the fields read request() rather than old(): the search state lives
    in the URL, which means the result page can be bookmarked and shared.

    The classic page linked an external stylesheet
    (assets/templates/basic/css/order-track.css) for a two-field form; that file
    is not loaded and the styling now comes from fm-auth.css.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts')

@section('title', 'Track Order')

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-auth.css') }}">
@endpush

@section('content')
    @php
        // These are the shared Contact / GeneralSetting rows. Both are nullable
        // and the support numbers are the whole point of the aside panel, so
        // each is guarded before it is printed.
        $trackPhone  = trim((string) ($generalsetting->support_phone ?? ($contact->phone ?? '')));
        $trackEmail  = trim((string) ($contact->email ?? ''));
        $trackShop   = (string) ($generalsetting->name ?? config('app.name'));
    @endphp

    <section class="fm-au">
        <div class="fm-au__container">

            <ol class="fm-au__crumbs">
                <li>
                    <a href="{{ route('home') }}">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">Track Order</li>
            </ol>

            <div class="fm-au__panel">
                <div class="fm-au__main">
                    <p class="fm-au__eyebrow">Order status</p>
                    <h1 class="fm-au__title">Track your order</h1>
                    <p class="fm-au__sub">
                        Enter your invoice number, or the phone number you ordered
                        with. Either one on its own is enough.
                    </p>

                    {{-- order_track_result() redirects back with a flash error
                         when both fields are blank, and again when nothing
                         matches. Saying so up front saves a pointless round trip
                         and tells the customer which half of the pair to retry. --}}
                    <div class="fm-au__notice fm-au__notice--info" role="note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>
                            Your invoice number is printed at the top of the order
                            confirmation and on every invoice we email you.
                        </span>
                    </div>

                    <form action="{{ route('customer.order_track_result') }}"
                          method="GET"
                          class="fm-au__form">

                        <div class="fm-au__field">
                            <label class="fm-au__label" for="trackInvoice">
                                Invoice number
                            </label>
                            <input type="text"
                                   id="trackInvoice"
                                   name="invoice_id"
                                   class="fm-au__input"
                                   value="{{ request('invoice_id') }}"
                                   placeholder="INV-000123"
                                   autocomplete="off">
                        </div>

                        <div class="fm-au__field">
                            <label class="fm-au__label" for="trackPhone">
                                Phone number
                            </label>
                            <input type="tel"
                                   id="trackPhone"
                                   name="phone"
                                   class="fm-au__input"
                                   value="{{ request('phone') }}"
                                   placeholder="01XXXXXXXXX"
                                   inputmode="numeric"
                                   autocomplete="tel">
                        </div>

                        <button type="submit" class="fm-au__btn">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            Track Now
                        </button>
                    </form>

                    <p class="fm-au__foot">
                        Have an account?
                        <a href="{{ route('customer.login') }}">Sign in</a> to see every order in one place.
                    </p>
                </div>

                {{-- Brand / reassurance half. Hidden on phones by fm-auth.css. --}}
                <aside class="fm-au__aside">
                    <h2>{{ $trackShop }}</h2>
                    <p>Track without an account - just the invoice number or the
                       phone number you gave us.</p>

                    <ul class="fm-au__aside-list">
                        <li>
                            <i class="bi bi-patch-check" aria-hidden="true"></i>
                            Live courier status when we have it
                        </li>
                        <li>
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                            Every item in the order, with quantities
                        </li>
                        <li>
                            <i class="bi bi-cash-coin" aria-hidden="true"></i>
                            What you paid, including shipping and discount
                        </li>
                    </ul>

                    @if($trackPhone !== '' || $trackEmail !== '')
                        <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,.2);">
                            @if($trackPhone !== '')
                                <p class="mb-1">
                                    <i class="bi bi-telephone" aria-hidden="true"></i>
                                    <a href="tel:{{ preg_replace('/\D/', '', $trackPhone) }}"
                                       class="text-white text-decoration-none fw-semibold">{{ $trackPhone }}</a>
                                </p>
                            @endif
                            @if($trackEmail !== '')
                                <p class="mb-0">
                                    <i class="bi bi-envelope" aria-hidden="true"></i>
                                    <a href="mailto:{{ $trackEmail }}"
                                       class="text-white text-decoration-none fw-semibold">{{ $trackEmail }}</a>
                                </p>
                            @endif
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
