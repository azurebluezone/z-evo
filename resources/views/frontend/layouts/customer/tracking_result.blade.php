@extends('frontend.layouts.app')
@section('title','Order Track Result')
@section('content')
<section class="auth-section py-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @foreach($orders as $key=>$value)
                <div class="form-content mb-4">
                    <p class="auth-title">Order Track Result</p>
                    <div class="track_info">
                        <ul>
                            <li><span>Invoice ID:</span> {{$value->invoice_id}} </li>
                            <li><span>Date:</span> {{ optional($value->created_at)->format('d M Y, h:i A') }} </li>
                            <li><span>Order Status:</span> {{ optional($value->status)->name }} </li>
                            @if(isset($value->courier_shipment) && $value->courier_shipment)
                                <li><span>Courier:</span> {{ strtoupper($value->courier_shipment->provider) }}</li>
                                @if($value->courier_shipment->consignment_id)
                                    <li><span>Consignment:</span> {{$value->courier_shipment->consignment_id}}</li>
                                @endif
                                @if($value->courier_shipment->tracking_code)
                                    <li><span>Tracking Code:</span> {{$value->courier_shipment->tracking_code}}</li>
                                @endif
                                @if($value->courier_shipment->current_status)
                                    <li><span>Courier Status:</span> {{$value->courier_shipment->current_status}}</li>
                                @endif
                            @endif
                        </ul>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered tracktable">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($value->orderdetails as $key=>$product)
                                <tr>
                                    <td>{{$product->product_name}}</td>
                                    <td>{{$product->qty}}</td>
                                    <td class="text-end">{{ number_format((float) $product->sale_price * (int) $product->qty, 0) }} TK</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2"></td>
                                    <td class="tfoot_bg"><span>Delivery Charge:</span> {{ number_format((float) $value->shipping_charge, 0) }} Tk.</td>
                                </tr>
                                <tr>
                                    <td colspan="2"></td>
                                    <td class="tfoot_bg"><span>Total:</span> {{ number_format((float) $value->amount, 0) }} Tk.</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
@push('script')
<script src="{{asset('frontEnd/')}}/js/parsley.min.js"></script>
<script src="{{asset('frontEnd/')}}/js/form-validation.init.js"></script>
@endpush