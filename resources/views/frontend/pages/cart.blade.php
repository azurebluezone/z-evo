@extends('frontend.layouts.app')
@section('title','Shopping Cart')

@push('css')
 <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/checkout-steps.css') }}">
@endpush

@section('content')
@php
 $subtotal = Cart::instance('shopping')->subtotal();
 $subtotal=str_replace(',','',$subtotal);
 $subtotal=str_replace('.00', '',$subtotal);
 view()->share('subtotal',$subtotal);
 $shipping = Session::get('shipping')?Session::get('shipping'):0;
 $discount = Session::get('discount')?Session::get('discount'):0;
 $couponCode = (string) (Session::get('coupon_code') ?? '');
 $isCouponEnabled = \App\Models\Coupon::running()->exists();
@endphp

<main>
 <div class="container py-60">
  <div class="row justify-content-center">
   <div class="col-xl-10">
    <div class="pb-40">
     <div class="cart-step">
      <div class="row g-2 g-md-3">
       <div class="col">
        <div class="cart-step-box position-relative active">
         <a href="{{ route('cart.show') }}" class="cart-link"></a>
         <p class="cart-step-title mb-0">My cart</p>
        </div>
       </div>
       <div class="col">
        <div class="cart-step-box">
         <p class="cart-step-title mb-0">Shipping Info</p>
        </div>
       </div>
       <div class="col">
        <div class="cart-step-box">
         <p class="cart-step-title mb-0">Payment</p>
        </div>
       </div>
       <div class="col">
        <div class="cart-step-box">
         <p class="cart-step-title mb-0">Confirmation</p>
        </div>
       </div>
      </div>
     </div>
    </div>

    <h5 class="mb-3 d-block d-sm-none">My Cart</h5>

    <div class="cart-container" id="cartlist">
     <div class="row g-4">
      <div class="col-lg-8">
       <div class="cart">
        <div class="cart-body">
         @foreach($data as $value)
          <div class="single-product-item cartItem" data-id="{{ $value->rowId }}" data-price="{{ $value->price }}">
           <div class="thumb">
            <img src="{{ asset($value->options->image) }}" alt="product-image">
           </div>
           <div class="content">
            <div class="content-top">
             <div class="content-top-left">
              <div class="title">{{ $value->name }}</div>
              <div class="d-flex flex-column">
               <div class="text-muted cart-item-price">
                <span>Price:</span>
                <span class="text">৳{{ $value->price }}</span>
               </div>
              </div>
             </div>
             <div class="cart-item-action">
              <button class="removeCart text-muted cart_remove" data-id="{{ $value->rowId }}" type="button">
               <i class="las la-trash"></i>
              </button>
             </div>
            </div>

            <div class="content-bottom">
             <div class="product-quantity-input d-flex quantity">
              <button class="qtyButton minus cart_decrement" data-id="{{ $value->rowId }}" type="button"><i class="la la-minus"></i></button>
              <input type="number" min="1" name="quantity" step="1" value="{{ $value->qty }}" readonly autocomplete="off">
              <button class="qtyButton plus cart_increment" data-id="{{ $value->rowId }}" type="button"><i class="la la-plus"></i></button>
             </div>
             <div class="d-flex flex-column text-center">
              <span class="total-price fs-sm text-muted">Total Price</span>
              <span class="total-price-text">
               ৳<span class="totalPrice fw-600">{{ $value->price*$value->qty }}</span>
              </span>
             </div>
            </div>
           </div>
          </div>
         @endforeach
        </div>
       </div>

       @if($isCouponEnabled)
           <div class="coupon-form mt-4">
               <div class="applyCouponContainer">
                   <div class="apply-coupon-code">
                       <input type="text" class="form-control form--control coupon" name="coupon_code" placeholder="Enter Coupon Code" value="" />
                       <button id="applyCoupon" type="button">Apply</button>
                   </div>
               </div>
               <div class="couponContent {{ $discount > 0 ? '' : 'd-none' }}">
                   <div class="d-flex gap-3 justify-content-end align-items-center coupon-inner">
                       <div>
                           <button type="button" class="text-danger remove-coupon ps-0 removeCoupon"><i class="la la-times-circle"></i></button>
                           <small>Coupon (<span class="couponCode fw-bold">{{ $couponCode }}</span>):</small>
                       </div>
                       <strong class="amount fs-16 d-block">- ৳<span id="couponAmount">{{ $discount }}</span></strong>
                   </div>
               </div>
           </div>
       @else
           <span id="couponAmount" class="d-none">{{ $discount }}</span>
       @endif
      </div>

      <div class="col-lg-4">
       <div class="cart-summary">
        <h5>Cart Summary</h5>
        <table class="table">
         <tbody>
          <tr>
           <td>Items</td>
           <td>{{Cart::instance('shopping')->count()}} (qty)</td>
          </tr>
          <tr>
           <td>Subtotal</td>
           <td>৳<span class="cartSubtotal">{{$subtotal}}</span></td>
          </tr>
          <tr>
           <td>Shipping</td>
           <td>৳{{$shipping}}</td>
          </tr>
          <tr>
           <td>Discount</td>
           <td>৳<span id="couponAmount">{{$discount}}</span></td>
          </tr>
          <tr>
           <td>Total</td>
           <td>৳<span id="finalTotal">{{($subtotal+$shipping) - $discount}}</span></td>
          </tr>
         </tbody>
        </table>
        <a href="{{route('customer.checkout')}}" class="go_cart">PROCESS TO CHECKOUT</a>
       </div>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
</main>
@endsection

@push('scripts')
<script>
    (function () {
        function request(method, url, data) {
            return $.ajax({
                type: method,
                url: url,
                data: data || {},
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        }

        function getCurrentQty($btn) {
            const raw = parseInt(
                $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty').find('input').first().val() || '1',
                10
            );
            return Number.isFinite(raw) && raw > 0 ? raw : 1;
        }

        function postCartUpdate(id, quantity) {
            return request('POST', `{{ url('/cart-update') }}/${encodeURIComponent(id)}`, {
                _token: '{{ csrf_token() }}',
                quantity: quantity
            });
        }

        function setQty($btn, qty) {
            $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty').find('input').first().val(String(qty));
        }

        function setPending($btn, flag) {
            const $scope = $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty');
            $scope.find('.cart_increment, .cart_decrement').prop('disabled', !!flag);
            if (flag) {
                $scope.addClass('is-cart-pending');
            } else {
                $scope.removeClass('is-cart-pending');
            }
        }

        $(document).on('click', '.cart_remove', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            if (!id) return;
            request('GET', "{{ route('cart.remove') }}", { id }).always(function () {
                window.location.reload();
            });
        });

        $(document).on('click', '.cart_increment', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const id = $(this).data('id');
            if (!id) return;
            if ($btn.prop('disabled')) return;
            const prevQty = getCurrentQty($btn);
            const nextQty = getCurrentQty($btn) + 1;
            setQty($btn, nextQty); // optimistic update
            setPending($btn, true);
            postCartUpdate(id, nextQty).done(function (response) {
                if (response && response.status === 'error') {
                    setQty($btn, prevQty); // rollback
                }
            }).fail(function () {
                setQty($btn, prevQty); // rollback
            }).always(function () {
                setPending($btn, false);
                window.location.reload();
            });
        });

        $(document).on('click', '.cart_decrement', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const id = $(this).data('id');
            if (!id) return;
            if ($btn.prop('disabled')) return;
            const currentQty = getCurrentQty($btn);
            if (currentQty <= 1) {
                request('GET', "{{ route('cart.remove') }}", { id }).always(function () {
                    window.location.reload();
                });
                return;
            }
            const prevQty = currentQty;
            const nextQty = currentQty - 1;
            setQty($btn, nextQty); // optimistic update
            setPending($btn, true);
            postCartUpdate(id, nextQty).done(function (response) {
                if (response && response.status === 'error') {
                    setQty($btn, prevQty); // rollback
                }
            }).fail(function () {
                setQty($btn, prevQty); // rollback
            }).always(function () {
                setPending($btn, false);
                window.location.reload();
            });
        });
    })();
</script>
@endpush
