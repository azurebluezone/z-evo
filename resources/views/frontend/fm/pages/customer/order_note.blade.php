{{--
    FM:SECTION page-customer-order-note ----------------------------------------
    Classic source : resources/views/frontend/layouts/customer/order_note.blade.php
    Controller     : Frontend\CustomerController@order_note()
    Variables      : $order = Order::where(['id' => $request->id,
                                             'customer_id' => Auth::…->id])->firstOrFail()
                     No eager loads, so only orderdetail columns directly on
                     the orders table are available.
    Sections       : fm-acct-body, fm-acct-actions
    Consumes       : $order

    Only $order->invoice_id and $order->admin_note are read - the controller
    scopes the query to the signed-in customer, so a customer can never open
    somebody else's note by editing ?id=.

    STORED XSS FIX - THIS IS THE IMPORTANT LINE IN THIS FILE
    The classic rendered the note with
        {!! $order->admin_note !!}
    admin_note is free text typed into the admin order screen. Unescaped output
    means any admin (or anyone who compromises an admin session) can inject
    <script> into a page every customer of that order loads. This page uses
    {{ }} so the note is rendered as text. Combined with the existing
    white-space: pre-line rule the customer's line breaks are preserved, which
    is presumably why the note was written with multiple lines in the first
    place.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.account', [
    'fmAcctTitle'   => 'Order Note',
    'fmAcctSub'     => 'A note from our team about order '
        . ($order->invoice_id ?? ('#' . $order->id)) . '.',
    'fmAcctEyebrow' => 'Message',
])

@section('fm-acct-body')
    @php
        $note = trim((string) ($order->admin_note ?? ''));
    @endphp

    @if($note !== '')
        <div class="fm-acct__card fm-acct__card--muted">
            <div class="fm-acct__card-head">
                <h2 class="fm-acct__card-title">
                    <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                    Note from us
                </h2>
            </div>

            {{-- pre-line keeps the author's line breaks; {{ }} keeps it text. --}}
            <p class="mb-0" style="white-space: pre-line;">{{ $note }}</p>
        </div>
    @else
        <div class="fm-acct__empty">
            <i class="bi bi-chat-square-text" aria-hidden="true"></i>
            <p>There is no note on this order.</p>
        </div>
    @endif
@endsection

@section('fm-acct-actions')
    <a href="{{ route('customer.orders') }}" class="btn btn-brand-primary">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Back to my orders
    </a>
    <a href="{{ route('customer.invoice', ['id' => $order->id]) }}" class="btn btn-brand-outline">
        <i class="bi bi-receipt" aria-hidden="true"></i>
        View invoice
    </a>
@endsection
