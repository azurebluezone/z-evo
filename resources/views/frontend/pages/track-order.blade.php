@extends('frontend.layouts.app')

@section('title', 'Track Order')
@section('meta_description', 'Track your order status')

@section('content')
<main>
    <div class="order-track-section py-60">
        <div class="container">
            <h5 class="title mb-3 text-center">Track Your Order</h5>

            <div class="row justify-content-center mb-5">
                <div class="col-lg-7 col-md-9 col-xl-6">
                    <form class="order-track-form" id="order-track" method="GET" action="{{ url('/track-order') }}">
                        <div class="order-track-form-group">
                            <input type="text" name="order_number" placeholder="Enter Your Order ID" value="{{ request('order_number') }}">
                            <button type="submit" class="track-btn">Track Now</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="order-track-wrapper d-flex flex-wrap justify-content-center">
                        <div class="confirm-state order-track-item">
                            <div class="thumb">
                                <i class="las la-check-square"></i>
                            </div>
                            <div class="content">
                                <h6 class="title">Pending</h6>
                            </div>
                        </div>

                        <div class="order-track-item processing-state">
                            <div class="thumb">
                                <i class="las la-sync-alt"></i>
                            </div>
                            <div class="content">
                                <h6 class="title">Processing</h6>
                            </div>
                        </div>

                        <div class="order-track-item dispatched-state">
                            <div class="thumb">
                                <i class="las la-truck-pickup"></i>
                            </div>
                            <div class="content">
                                <h6 class="title">Dispatched</h6>
                            </div>
                        </div>

                        <div class="order-track-item delivered-state">
                            <div class="thumb">
                                <i class="las la-map-signs"></i>
                            </div>
                            <div class="content">
                                <h6 class="title">Delivered</h6>
                            </div>
                        </div>
                    </div>

                    @if(!empty($order_status))
                        <div class="text-center mt-4">
                            <div class="alert alert-info mb-0">Current Status: {{ $order_status }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
