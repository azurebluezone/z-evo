{{--
    FM:SECTION page-customer-tracking-result -----------------------------------
    Classic source : resources/views/frontend/layouts/customer/tracking_result.blade.php
    Controller     : Frontend\CustomerController@order_track_result()
    Routes         : GET /customer/order-track/result (customer.order_track_result)
                     GET /track-order/result          (track.order.result)
    Variables      : $orders = Collection<Order>, each eager loaded with
                     shipping, status, orderdetails, PLUS a runtime attribute
                     $order->courier_shipment (CourierShipment|null) assigned
                     inside the controller, not an Eloquent relation.

    WHY THIS IS NOT INSIDE THE ACCOUNT SHELL
    order_track_result is in the controller's `except` list, so a guest can reach
    it. Extending frontend.fm.layouts.account would render an empty customer rail
    for them. This extends frontend.fm.layouts and lays its own container out.

    NOT PAGINATED
    The controller uses ->get() with latest(). A guest can legitimately have
    hundreds of orders on one phone number, but paging is a controller decision -
    ->paginate() cannot be introduced from the template - so it is left alone and
    flagged, not silently changed.

    $order->courier_shipment is the reason for the courier block: the controller
    attaches the first CourierShipment row for the order, and it is null until a
    consignment has actually been created. Printing "no tracking yet" instead of
    an empty courier box is the difference between "we don't know yet" and
    "something is broken".
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts')

@section('title', 'Order Tracking')

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-account.css') }}">
@endpush

@section('content')
    <section class="fm-acct">
        <div class="fm-au__container" style="max-width:900px;">

            <ol class="fm-au__crumbs">
                <li>
                    <a href="{{ route('home') }}">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li aria-hidden="true">/</li>
                <li>
                    <a href="{{ route('customer.order_track') }}">Track Order</a>
                </li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">Result</li>
            </ol>

            <div class="fm-acct__panel fm-card">
                <header class="fm-acct__head">
                    <p class="fm-acct__eyebrow">Tracking</p>
                    <h1 class="fm-acct__title">
                        {{ $orders->count() === 1 ? '1 order found' : $orders->count() . ' orders found' }}
                    </h1>
                    @if(request('invoice_id'))
                        <p class="fm-acct__sub">Invoice <strong>{{ request('invoice_id') }}</strong></p>
                    @elseif(request('phone'))
                        <p class="fm-acct__sub">Phone <strong>{{ request('phone') }}</strong></p>
                    @endif
                </header>

                <div class="fm-acct__body">
                    @forelse($orders as $order)
                        @php
                            $shipment   = $order->courier_shipment ?? null;
                            $orderTotal = (float) ($order->amount ?? 0);
                            $orderFreight = (float) ($order->shipping_charge ?? 0);
                        @endphp

                        <article class="fm-acct__card mb-4">

                            <div class="fm-acct__card-head">
                                <div>
                                    <h2 class="fm-acct__card-title">
                                        {{ $order->invoice_id ?? ('Order #' . $order->id) }}
                                    </h2>
                                    <p class="fm-acct__card-meta">
                                        Placed {{ optional($order->created_at)->format('d M Y, h:i A') }}
                                    </p>
                                </div>

                                <span class="fm-acct__pill fm-acct__pill--blue">
                                    {{ $order->status?->name ?? 'Unknown' }}
                                </span>
                            </div>

                            {{-- ------------------------- courier ------------------------- --}}
                            @if($shipment)
                                <div class="fm-acct__notice fm-au__notice--ok" style="margin-bottom:1rem;">
                                    <i class="bi bi-truck" aria-hidden="true"></i>
                                    <div>
                                        <strong>{{ $shipment->current_status ?: 'In transit' }}</strong>
                                        @if($shipment->provider)
                                            <span class="d-block fm-acct__card-meta">
                                                via {{ $shipment->provider }}
                                            </span>
                                        @endif
                                        @if($shipment->tracking_code)
                                            <span class="d-block mt-1">
                                                <span class="fm-au__code">{{ $shipment->tracking_code }}</span>
                                            </span>
                                        @endif
                                        @if($shipment->consignment_id)
                                            <span class="d-block fm-acct__card-meta">
                                                Consignment {{ $shipment->consignment_id }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="fm-au__notice fm-au__notice--info" style="margin-bottom:1rem;">
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    <span>No courier tracking available for this order yet.</span>
                                </div>
                            @endif

                            {{-- ------------------------ line items ----------------------- --}}
                            <ul class="fm-acct__lines">
                                @foreach($order->orderdetails ?? [] as $detail)
                                    <li class="fm-acct__line">
                                        <dt class="fm-acct__line-label">
                                            {{ $detail->product_name ?? 'Product' }}
                                            <span class="fm-acct__card-meta d-block">
                                                Qty {{ (int) ($detail->qty ?? 0) }}
                                            </span>
                                        </dt>
                                        <dd class="fm-acct__line-value">
                                            ৳ {{ number_format((float) ($detail->sale_price ?? 0) * (int) ($detail->qty ?? 0), 0) }}
                                        </dd>
                                    </li>
                                @endforeach
                            </ul>

                            @if($orderFreight > 0)
                                <div class="fm-acct__line">
                                    <dt class="fm-acct__line-label">Shipping</dt>
                                    <dd class="fm-acct__line-value">৳ {{ number_format($orderFreight, 0) }}</dd>
                                </div>
                            @endif

                            <div class="fm-acct__total">
                                <span>Total</span>
                                <span>৳ {{ number_format($orderTotal, 0) }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="fm-acct__empty">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <p>No orders matched that search.</p>
                            <a href="{{ route('customer.order_track') }}" class="btn btn-brand-primary btn-sm px-3">
                                Try another invoice number or phone
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
