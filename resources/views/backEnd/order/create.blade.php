@extends('backEnd.layouts.app') @section('title','Order Create')

@section('content')


<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Order Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="adminForm">
              <i class="ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-fluid">
 <!-- start page title -->
 <div class="row">
  <div class="col-12">
   <div class="page-title-box d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
    <h4 class="page-title mb-0">Create</h4>
    <div class="page-title-right">
     <form method="get" action="{{route('admin.order.cart_clear')}}" class="d-inline" id="adminForm">
      @csrf
      <x-admin.button variant="danger" class="rounded-pill delete-confirm" type="submit" title="Delete"><i class="fas fa-trash-alt"></i> Cart Clear</x-admin.button>

     </form>
    </div>
   </div>
  </div>
 </div>
 <!-- end page title -->
 <div class="row justify-content-center">
  <div class="col-lg-12">
   <div class="card">
    <div class="card-body">
     <form action="{{route('admin.order.store')}}" method="POST" class="row pos_form" data-parsley-validate="" enctype="multipart/form-data">
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
           <i class="ri-shopping-cart-line me-2"></i><span>Products</span>
          </a>
         </div>
         <div class="line"></div>
         <div class="step" data-target="#stepper-2">
          <a class="nav-link step-trigger" href="#stepper-2">
           <i class="ri-user-line me-2"></i><span>Customer</span>
          </a>
         </div>
         <div class="line"></div>
         <div class="step" data-target="#stepper-3">
          <a class="nav-link step-trigger" href="#stepper-3">
           <i class="ri-money-dollar-circle-line me-2"></i><span>Payment</span>
          </a>
         </div>
        </div>
        <div class="bs-stepper-content">
         <div class="content active" id="stepper-1">
      <div class="col-sm-12">
       <div class="form-group mb-3">
        <label for="product_id" class="form-label">Products *</label>
        <select id="cart_add" class="form-control @error('product_id') is-invalid @enderror" value="{{ old('product_id') }}">
         <option value="">Select..</option>
         @foreach($products as $value)
         <option value="{{$value->id}}">{{$value->name}}</option>
         @endforeach
        </select>
        @error('product_id')
        <span class="invalid-feedback" role="alert">
         <strong>{{ $message }}</strong>
        </span>
        @enderror
       </div>
      </div>
      <!-- col end -->
      <div class="col-sm-12">
       <div class="table-responsive">
        <table class="table table-bordered">
         <thead>
          <tr>
           <th style="width: 10%;">Image</th>
           <th style="width: 25%;">Name</th>
           <th style="width: 15%;">Quantity</th>
           <th style="width: 15%;">Sell Price</th>
           <th style="width: 15%;">Discount</th>
           <th style="width: 15%;">Sub Total</th>
           <th style="width: 15%;">Action</th>
          </tr>
         </thead>
         <tbody id="cartTable">
         @php $product_discount = 0; @endphp @foreach($cartinfo as $key=>$value)
         <tr>
          <td><img height="30" src="{{asset(preg_replace('/^public\//', '', $value->options->image))}}" /></td>
          <td>{{$value->name}}</td>
          <td>
           <div class="qty-cart vcart-qty">
            <div class="quantity">
             <button class="minus cart_decrement" value="{{$value->qty}}" data-id="{{$value->rowId}}">-</button>
             <input type="text" value="{{$value->qty}}" readonly />
             <button class="plus cart_increment" value="{{$value->qty}}" data-id="{{$value->rowId}}">+</button>
            </div>
           </div>
          </td>
          <td>{{$value->price}}</td>
          <td class="discount"><input type="number" class="product_discount" value="{{$value->options->product_discount}}" placeholder="0.00" data-id="{{$value->rowId}}" /></td>
          <td>{{($value->price - $value->options->product_discount)*$value->qty}}</td>
          <td>
           <x-admin.button variant="danger" size="sm" class="cart_remove" type="button" data-id="{{$value->rowId}}" aria-label="Remove">
            <span aria-hidden="true">×</span>
           </x-admin.button>
          </td>
         </tr>
         @php $product_discount += $value->options->product_discount*$value->qty; Session::put('product_discount',$product_discount); @endphp @endforeach
         </tbody>
        </table>
       </div>
      </div>
         </div>
         <div class="content" id="stepper-2">
      <!-- custome address -->
      <div class="col-sm-6">
       <div class="row">
        <div class="col-sm-12">
         <div class="form-group mb-2">
          <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Customer Name" name="name" value="" />
          @error('name')
          <span class="invalid-feedback" role="alert">
           <strong>{{ $message }}</strong>
          </span>
          @enderror
         </div>
        </div>
        <!-- col-end -->
        <div class="col-sm-12">
         <div class="form-group mb-2">
          <input type="number" id="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Customer Number" name="phone" value="" />
          @error('phone')
          <span class="invalid-feedback" role="alert">
           <strong>{{ $message }}</strong>
          </span>
          @enderror
         </div>
        </div>
        <!-- col-end -->
        <div class="col-sm-12">
         <div class="form-group mb-3">
          <input type="address" placeholder="Address" id="address" class="form-control @error('address') is-invalid @enderror" name="address" value="" />
          @error('email')
          <span class="invalid-feedback" role="alert">
           <strong>{{ $message }}</strong>
          </span>
          @enderror
         </div>
        </div>
        <div class="col-sm-12">
         <div class="form-group mb-3">
          <select type="area" id="area" class="form-control @error('area') is-invalid @enderror" name="area">
           <option value="">Select....</option>
           @foreach($shippingcharge as $value)
            <option value="{{$value->id}}">{{$value->name}}</option>
           @endforeach
          </select>
          @error('email')
          <span class="invalid-feedback" role="alert">
           <strong>{{ $message }}</strong>
          </span>
          @enderror
         </div>
        </div>
        <!-- col-end -->
       </div>
      </div>
         </div>
         <div class="content" id="stepper-3">
      <!-- cart total -->
      <div class="col-sm-6">
       <div class="row">
        <div class="col-12">
         <div class="form-group mb-2">
          <label class="form-label">Manual Discount (optional)</label>
          <input type="number" min="0" step="0.01" class="form-control" id="pos_manual_discount" value="{{ Session::get('pos_manual_discount') ?? '' }}" placeholder="0.00">
         </div>
        </div>
        <div class="col-12">
         <div class="form-group mb-3">
          <label class="form-label">Coupon Code (optional)</label>
          <div class="input-group">
           <input type="text" class="form-control" id="pos_coupon_code" value="{{ Session::get('pos_coupon_code') ?? '' }}" placeholder="Enter coupon">
           <x-admin.button variant="outline-primary" type="button" id="pos_apply_coupon">Apply</x-admin.button>
           <x-admin.button variant="outline-danger" type="button" id="pos_remove_coupon">Remove</x-admin.button>
          </div>
          <small class="text-muted d-block mt-1" id="pos_coupon_msg"></small>
         </div>
        </div>
        <div class="col-12">
         <table class="table table-bordered">
          <tbody id="cart_details">
           @php $subtotal = Cart::instance('pos_shopping')->subtotal(); $subtotal = str_replace(',','',$subtotal); $subtotal = str_replace('.00', '',$subtotal); $shipping = Session::get('pos_shipping'); $total_discount = (Session::get('pos_manual_discount') ?? 0) + (Session::get('pos_coupon_discount') ?? 0) + (Session::get('product_discount') ?? 0); @endphp
           <tr>
            <td>Sub Total</td>
            <td>{{$subtotal}}</td>
           </tr>
           <tr>
            <td>Shipping Fee</td>
            <td>{{$shipping}}</td>
           </tr>
           <tr>
            <td>Discount</td>
            <td>{{$total_discount}}</td>
           </tr>
           <tr>
            <td>Total</td>
            <td>{{($subtotal + $shipping)- $total_discount}}</td>
           </tr>
          </tbody>
         </table>
        </div>
       </div>
      </div>
      <div>
       <input type="submit" class="btn btn-success" value="Order Submit" />
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
        <i class="ri-arrow-left-line me-1"></i> Back
       </x-admin.button>
       <x-admin.button variant="primary" class="btn-next" type="button" data-bs-stepper-next>
        Next <i class="ri-arrow-right-line ms-1"></i>
       </x-admin.button>
      </div>

       </div>


     </form>
    </div>
    <!-- end card-body-->
   </div>
   <!-- end card-->
  </div>
  <!-- end col-->
 </div>
</div>

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
 // Show validation errors using Toastr
 @if ($errors->any())
     $(document).ready(function() {
         @foreach ($errors->all() as $error)
             if(window.toastr) {
                 window.toastr.error('{{ $error }}', 'Validation Error!');
             }
         @endforeach
     });
 @endif

 // Show session success/error messages using Toastr
 @if(session('success'))
     $(document).ready(function() {
         if(window.toastr) {
             window.toastr.success('{{ session('success') }}', 'Success!');
         }
     });
 @endif

 @if(session('error'))
     $(document).ready(function() {
         if(window.toastr) {
             window.toastr.error('{{ session('error') }}', 'Error!');
         }
     });
 @endif

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
      $("#cart_add").val(null).trigger("change");
      cart_content();
      cart_details();
      if(window.toastr) {
       window.toastr.success(response.message, 'Success!');
      }
     } else {
      if(window.toastr) {
       window.toastr.error(response.message || 'Product not found', 'Error!');
      }
     }
    },
    error: function(xhr) {
     if(window.toastr) {
      window.toastr.error('Failed to add product to cart', 'Error!');
     }
    }
   });
  }
 });
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
 $(document).on("change", ".product_discount", function () {
  var id = $(this).data("id");
  var discount = $(this).val();
  $.ajax({
   cache: false,
   type: "GET",
   data: { id: id, discount: discount },
   url: "{{route('admin.order.product_discount')}}",
   dataType: "json",
   success: function (cartinfo) {
    cart_content(); cart_details();
   },
  });
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
 $(".cartclear").click(function (e) {
  e.preventDefault();
  $.ajax({
   cache: false,
   type: "GET",
   url: "{{route('admin.order.cart_clear')}}",
   dataType: "json",
   success: function (response) {
    if(response.status === 'success') {
     cart_content(); 
     cart_details();
     if(window.toastr) {
      window.toastr.success(response.message, 'Success!');
     }
    }
   },
   error: function(xhr) {
    if(window.toastr) {
     window.toastr.error('Failed to clear cart', 'Error!');
    }
   }
  });
 }); // pshippingfee from total
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
</script>
@endpush
