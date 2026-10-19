@extends('frontend.layouts.app')
@section('title','Order Track')
@push('css')
<link rel="stylesheet" href="{{ asset('assets/templates/basic/css/order-track.css') }}">
@endpush
@section('content')
<div class="order-track-section py-60">
        <div class="container">
            <h5 class="title mb-3 text-center">Track Your Order</h5>
            <div class="row justify-content-center mb-5">
                <div class="col-lg-7 col-md-9 col-xl-6">
                    <form class="order-track-form" id="order-track" method="get" action="{{ route('customer.order_track_result') }}">
                        <div class="order-track-form-group">
                            <input type="text" name="invoice_id" placeholder="Enter Your Invoice Number (Optional)" value="{{ request('invoice_id') }}">
                            <input type="text" name="phone" placeholder="Enter Your Phone Number (Optional)" value="{{ request('phone') }}">
                            <button type="submit" class="track-btn">Track Now</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="order-track-wrapper d-flex flex-wrap justify-content-center">
                        <div class="confirm-state order-track-item active">
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
                </div>
            </div>
        </div>
    </div>
@endsection