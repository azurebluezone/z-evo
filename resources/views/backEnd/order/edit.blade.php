@extends('backEnd.layouts.app') 
@section('title','Edit Order') 

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Edit Order</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="adminForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Edit Order <span class="text-primary">#{{$order->invoice_id}}</span></h4>
    <p class="text-muted mb-0">Placed on {{date('d M Y, h:i A', strtotime($order->created_at))}}</p>
  </div>
  <div class="d-flex gap-2">
    <form method="get" action="{{route('admin.order.cart_clear')}}" class="d-inline" id="adminForm">
        @csrf
        <x-admin.button variant="danger" size="sm" ripple="true" type="submit" title="Cart Clear" data-bs-toggle="tooltip">
            <i class="icon-base ri ri-delete-bin-7-line me-2"></i>Cart Clear
        </x-admin.button>
    </form>
    <a href="{{ url('/admin/order/all') }}" class="btn btn-outline-secondary btn-sm waves-effect">
      <i class="icon-base ri ri-arrow-left-line me-2"></i>Back to Orders
    </a>
  </div>
</div>

<form action="{{route('admin.order.update')}}" method="POST" class="pos_form" enctype="multipart/form-data">
  @csrf

       <!--
         Three steps, one form. The <form> element above and every input inside
         it are unchanged - only the wrapping is new, so the same data is
         posted to the same route. See tools/verify_order_form_fields.py.
       -->
       <div class="bs-stepper" data-bs-stepper data-bs-stepper-linear="true">
        <div class="bs-stepper-header">
         <div class="step active" data-target="#stepper-1">
          <a class="nav-link step-trigger" href="#stepper-1">
           <i class="ri ri-shopping-cart-line me-2"></i><span>Products</span>
          </a>
         </div>
         <div class="line"></div>
         <div class="step" data-target="#stepper-2">
          <a class="nav-link step-trigger" href="#stepper-2">
           <i class="ri ri-user-line me-2"></i><span>Customer</span>
          </a>
         </div>
         <div class="line"></div>
         <div class="step" data-target="#stepper-3">
          <a class="nav-link step-trigger" href="#stepper-3">
           <i class="ri ri-money-dollar-circle-line me-2"></i><span>Payment</span>
          </a>
         </div>
        </div>
        <div class="bs-stepper-content">
         <div class="content active" id="stepper-1">
  <input type="hidden" value="{{$order->id}}" name="order_id">
  
  <div class="row g-6">
    <!-- Main Order Content -->
    <div class="col-lg-8">
         </div>
         <div class="content" id="stepper-2">
      
      <!-- Customer Information -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-user-line me-2"></i>Customer Information</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" name="name" id="customerName" value="{{$shippinginfo->name ?? ''}}">
                <label for="customerName">Customer Name</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="tel" class="form-control" name="phone" id="customerPhone" value="{{$shippinginfo->phone ?? ''}}">
                <label for="customerPhone">Phone Number</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating form-floating-outline">
                <textarea class="form-control" name="address" id="shippingAddress" style="height: 80px">{{$shippinginfo->address ?? ''}}</textarea>
                <label for="shippingAddress">Shipping Address</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <select id="area" class="form-select" name="area">
                    <option value="">Delivery Area</option>
                    @foreach($shippingcharge as $key=>$value)
                    <option value="{{$value->id}}" @if(isset($shippinginfo->area) && $shippinginfo->area == $value->name) selected @endif>{{$value->name}}</option>
                    @endforeach
                </select>
                <label for="area">Delivery Area</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <select id="cart_add" class="form-select">
                    <option value="">Search to add...</option>
                    @foreach($products as $value)
                        <option value="{{$value->id}}">{{$value->name}}</option> 
                    @endforeach
                </select>
                <label for="cart_add">Add Products</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Items POS Logic -->
      <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="icon-base ri ri-shopping-bag-line me-2"></i>Order Items</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th style="width:10%">Image</th>
                  <th style="width:30%">Name</th>
                  <th style="width:15%">Qty</th>
                  <th style="width:15%">Price</th>
                  <th style="width:15%">Total</th>
                  <th style="width:15%">Action</th>
                </tr>
              </thead>
              <tbody id="cartTable">
                @php
                  $product_discount = 0;
                @endphp
                @foreach($cartinfo as $key=>$value)
                <tr>
                  @php
                    $imgPath = preg_replace('~^public/~', '', (string) ($value->options->image ?? ''));
                    $imgPath = ltrim($imgPath, '/');
                    $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                    $fallbackImg = preg_replace('~^public/~', '', (string) $fallbackImg);
                    $fallbackImg = ltrim($fallbackImg, '/');
                    $imgSrc = $imgPath ? asset($imgPath) : asset($fallbackImg);
                  @endphp
                  <td><img height="40" src="{{ $imgSrc }}" class="rounded" onerror="this.src='{{ asset($fallbackImg) }}'"></td>
                  <td>
                      <a href="{{ route('product', $value->options->slug ?? '') }}" target="_blank" class="fw-medium">
                         {{$value->name}} 
                      </a>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      <x-admin.button variant="outline-secondary" size="sm" class="px-2 cart_decrement" value="{{$value->qty}}" data-id="{{$value->rowId}}">-</x-admin.button>
                      <input type="text" class="form-control form-control-sm text-center mx-1" value="{{$value->qty}}" style="width: 40px;" readonly />
                      <x-admin.button variant="outline-secondary" size="sm" class="px-2 cart_increment" value="{{$value->qty}}" data-id="{{$value->rowId}}">+</x-admin.button>
                    </div>
                  </td>
                  <td>৳{{$value->price}}</td>
                  <td>৳{{($value->price - ($value->options->product_discount ?? 0))*$value->qty}}</td>
                  <td>
                      <x-admin.button variant="text-danger" size="sm" shape="icon" class="cart_remove" type="button" data-id="{{$value->rowId}}">
                          <i class="ri ri-delete-bin-line"></i>
                      </x-admin.button>
                  </td>
                </tr>
                @php
                $product_discount += ($value->options->product_discount ?? 0) * $value->qty;
                Session::put('product_discount',$product_discount);
                @endphp
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar POS Actions -->
    <div class="col-lg-4">
      
      <!-- Order Settings -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Order Status</h5>
        </div>
        <div class="card-body">
          <div class="form-floating form-floating-outline mb-3">
             <select name="order_status" id="orderStatus" class="form-select">
                <option value="">Select..</option>
                @foreach($orderstatus as $st)
                    <option value="{{$st->id}}" @if(isset($order) && (int) $order->order_status === (int) $st->id) selected @endif>{{$st->name}}</option>
                @endforeach
            </select>
            <label for="orderStatus">Order Status</label>
          </div>
          
          <hr/>
          
          <div class="form-floating form-floating-outline mb-3">
             <input type="number" min="0" step="0.01" class="form-control" id="pos_manual_discount" value="{{ Session::get('pos_manual_discount') ?? '' }}" placeholder="0.00">
             <label for="pos_manual_discount">Manual Discount ৳ </label>
          </div>
          
          <label class="form-label d-block mb-1">Coupon Code</label>
          <div class="input-group mb-1">
              <input type="text" class="form-control" id="pos_coupon_code" value="{{ Session::get('pos_coupon_code') ?? '' }}" placeholder="Enter coupon">
              <x-admin.button variant="outline-primary" type="button" id="pos_apply_coupon">Apply</x-admin.button>
              <x-admin.button variant="outline-danger" type="button" id="pos_remove_coupon">Remove</x-admin.button>
          </div>
          <small class="text-muted d-block mb-3" id="pos_coupon_msg"></small>
        </div>
      </div>
         </div>
         <div class="content" id="stepper-3">

      <!-- Financial Summary -->
      <div class="card mb-6">
          <div class="card-header">
              <h5 class="mb-0">Summary</h5>
          </div>
          <div class="card-body p-0">
             <table class="table table-borderless table-sm mb-0 p-3">
                <tbody id="cart_details">
                    @php
                        $subtotal = Cart::instance('pos_shopping')->subtotal();
                        $subtotal = str_replace(',','',$subtotal);
                        $subtotal = str_replace('.00', '',$subtotal);
                        $shipping = Session::get('pos_shipping') ?? 0;
                        $total_discount = (Session::get('pos_manual_discount') ?? 0) + (Session::get('pos_coupon_discount') ?? 0) + (Session::get('product_discount') ?? 0);
                    @endphp
                    <tr>
                        <td class="ps-3">Sub Total</td>
                        <td class="text-end pe-3 fw-medium">৳{{$subtotal}}</td>
                    </tr>
                    <tr>
                        <td class="ps-3">Shipping Fee</td>
                        <td class="text-end pe-3 fw-medium">৳{{$shipping}}</td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-danger">Discount</td>
                        <td class="text-end pe-3 fw-medium text-danger">৳{{$total_discount}}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="ps-3 pt-2"><strong>Total</strong></td>
                        <td class="text-end pe-3 pt-2"><strong>৳{{($subtotal + $shipping) - $total_discount}}</strong></td>
                    </tr>
                </tbody>
             </table>
          </div>
      </div>

      <!-- Save Actions -->
      <div class="card">
        <div class="card-body">
          <x-admin.button variant="primary" ripple="true" class="w-100 waves-light mb-2" type="submit">
            <i class="icon-base ri ri-save-line me-2"></i>Save Order Changes
          </x-admin.button>

          {{--
            Send to courier.

            Placed OUTSIDE the Save form's button block but still outside the
            <form> itself, so it cannot submit the order form by accident - each
            is a plain link to the preview screen, which is what an admin wants
            before creating a billable consignment.

            One button per courier: the preview screen carries the payload and the
            confirm step, and it also lists every other courier, so an admin can
            still switch. Previously there was a single button hardcoded to
            SteadFast, which meant Pathao was unreachable from this page.
          --}}
          <a href="{{ route('admin.order.courier.show', ['provider' => 'steadfast', 'order' => $order->id ?? request('invoice_id')]) }}"
             class="btn btn-outline-primary w-100 mt-2">
            <i class="ri ri-truck-line me-1"></i> Send to SteadFast
          </a>
          <a href="{{ route('admin.order.courier.show', ['provider' => 'pathao', 'order' => $order->id ?? request('invoice_id')]) }}"
             class="btn btn-outline-success w-100 mt-2">
            <i class="ri ri-road-map-line me-1"></i> Send to Pathao
          </a>
        </div>
      </div>
      
    </div>
  </div>
         </div>
        </div>
      {{--
        Step navigation, INSIDE the stepper: a Next button outside it has no
        stepper to advance. The submit input in the last pane above is the same
        one this form has always had - nothing about the post changed.
      --}}
      <div class="d-flex justify-content-between mt-4">
       <x-admin.button variant="outline-secondary" class="btn-prev" type="button" data-bs-stepper-prev>
        <i class="ri ri-arrow-left-line me-1"></i> Back
       </x-admin.button>
       <x-admin.button variant="primary" class="btn-next" type="button" data-bs-stepper-next>
        Next <i class="ri ri-arrow-right-line ms-1"></i>
       </x-admin.button>
      </div>

       </div>



</form>


    </div>
  </div>
</div>
@endsection

@push('css')
{{-- Loaded on this page only; the stepper is three wrappers and a progress bar. --}}
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/bs-stepper/bs-stepper.css') }}">
@endpush

@push('script')
<script src="{{ asset('admin/assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
<script src="{{ asset('admin/assets/js/fm-order-stepper.js') }}"></script>

<script>
 function cart_content() {
  $.ajax({
   type: "GET",
   url: "{{route('admin.order.cart_content')}}",
   dataType: "html",
   success: function (cartinfo) {
    $("#cartTable").html(cartinfo);
   },
  });
 }
 function cart_details() {
  $.ajax({
   type: "GET",
   url: "{{route('admin.order.cart_details')}}",
   dataType: "html",
   success: function (cartinfo) {
    $("#cart_details").html(cartinfo);
   },
  });
 }

 $(document).on("click", ".cart_increment", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var qty = $(this).val();
  if (id) {
   $.ajax({
    cache: false,
    data: { id: id, qty: qty },
    type: "GET",
    url: "{{route('admin.order.cart_increment')}}",
    dataType: "json",
    success: function (cartinfo) {
     cart_content(); cart_details();
    },
   });
  }
 });

 $(document).on("click", ".cart_decrement", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var qty = $(this).val();
  if (id) {
   $.ajax({
    cache: false,
    type: "GET",
    data: { id: id, qty: qty },
    url: "{{route('admin.order.cart_decrement')}}",
    dataType: "json",
    success: function (cartinfo) {
     cart_content(); cart_details();
    },
   });
  }
 });

 $(document).on("click", ".cart_remove", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  if (id) {
   $.ajax({
    cache: false,
    type: "GET",
    data: { id: id },
    url: "{{route('admin.order.cart_remove')}}",
    dataType: "json",
    success: function (cartinfo) {
     cart_content(); cart_details();
    },
   });
  }
 });

 $("#area").on("change", function () {
  var id = $(this).val();
  $.ajax({
   type: "GET",
   data: { id: id },
   url: "{{route('admin.order.cart_shipping')}}",
   dataType: "json",
   success: function (cartinfo) {
    cart_content(); cart_details();
   },
  });
 });

 $("#cart_add").on("change", function (e) {
  var id = $(this).val();
  if (id) {
   $.ajax({
    cache: false,
    type: "GET",
    data: { id: id },
    url: "{{route('admin.order.cart_add')}}",
    dataType: "json",
    success: function (response) {
     if(response.status === 'success') {
      $("#cart_add").val('');
      cart_content();
      cart_details();
      if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', response.message);
     } else {
      if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', response.message || 'Product not found');
     }
    }
   });
  }
 });

 $('#pos_manual_discount').on('change', function () {
  var discount = $(this).val();
  $.ajax({
   cache: false,
   type: 'GET',
   data: { discount: discount },
   url: "{{route('admin.order.cart_extra_discount')}}",
   dataType: 'json',
   success: function () {
    cart_content(); cart_details();
   },
  });
 });

 $('#pos_apply_coupon').on('click', function () {
  var coupon_code = $('#pos_coupon_code').val();
  $.ajax({
   cache: false,
   type: 'GET',
   data: { coupon_code: coupon_code },
   url: "{{route('admin.order.cart_apply_coupon')}}",
   dataType: 'json',
   success: function (res) {
    $('#pos_coupon_msg').text(res.message || '');
    cart_content(); cart_details();
   },
  });
 });

 $('#pos_remove_coupon').on('click', function () {
  $.ajax({
   cache: false,
   type: 'GET',
   url: "{{route('admin.order.cart_remove_coupon')}}",
   dataType: 'json',
   success: function (res) {
    $('#pos_coupon_msg').text(res.message || '');
    cart_content(); cart_details();
   },
  });
 });
</script>
@endpush
