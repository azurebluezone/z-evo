@php
$subtotal = Cart::instance('shopping')->subtotal();
$subtotal=str_replace(',','',$subtotal);
$subtotal=str_replace('.00', '',$subtotal);
$shipping = Session::get('shipping')?Session::get('shipping'):0;
$discount = Session::get('discount')?Session::get('discount'):0;
@endphp
<table class="cart_table table table-bordered table-striped text-center mb-0">
    <thead>
        <tr>
            <th style="width: 20%;">ডিলিট</th>
            <th style="width: 40%;">প্রোডাক্ট</th>
            <th style="width: 20%;">পরিমাণ</th>
            <th style="width: 20%;">মূল্য</th>
        </tr>
    </thead>

    <tbody>
        @foreach(Cart::instance('shopping')->content() as $value)
        <tr>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger cart_remove" data-id="{{$value->rowId}}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            <td class="text-left">
                <a href="{{route('product',$value->options->slug)}}"> <img src="{{asset($value->options->image)}}" style="height:30px;width:30px" /> {{Str::limit($value->name,20)}}</a>
                @if($value->options->product_size)
                <p>Size: {{$value->options->product_size}}</p>
                @endif
                @if($value->options->product_color)
                <p>Color: {{ $value->options->product_color }}</p>
                @endif
            </td>
                      <td class="cart_qty">
           <div class="qty-cart vcart-qty">
            <div class="quantity">
             <button type="button" class="minus cart_decrement" data-id="{{$value->rowId}}">
                 <i class="fas fa-minus"></i>
             </button>
             <input type="text" value="{{$value->qty}}" readonly />
             <button type="button" class="plus cart_increment" data-id="{{$value->rowId}}">
                 <i class="fas fa-plus"></i>
             </button>
            </div>
           </div>
          </td>
            <td><span class="alinur">৳ </span><strong>{{$value->price}}</strong></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-end px-4">মোট</th>
            <td>
                <span id="net_total"><span class="alinur">৳ </span><strong>{{$subtotal}}</strong></span>
            </td>
        </tr>
        <tr>
            <th colspan="3" class="text-end px-4">ডেলিভারি চার্জ</th>
            <td>
                <span id="cart_shipping_cost"><span class="alinur">৳ </span><strong>{{$shipping}}</strong></span>
            </td>
        </tr>
        <tr>
            <th colspan="3" class="text-end px-4">সর্বমোট</th>
            <td>
                <span id="grand_total"><span class="alinur">৳ </span><strong>{{$subtotal+$shipping}}</strong></span>
            </td>
        </tr>
    </tfoot>
</table>

<script src="{{asset('frontEnd/js/jquery-3.6.3.min.js')}}"></script>
<!-- cart js start -->
<script>
    function cart_update(id, quantity) {
        return $.ajax({
            type: "POST",
            url: `{{ url('/cart-update') }}/${encodeURIComponent(id)}`,
            dataType: "json",
            data: {
                _token: "{{ csrf_token() }}",
                quantity: quantity
            }
        });
    }

    function sidebar_refresh() {
        return $.ajax({
            type: "GET",
            url: "{{ route('sidebar.content') }}",
            dataType: "html",
            success: function(html) {
                if (html) {
                    $(".cartlist").html(html);
                }
            }
        });
    }

    function get_qty($btn) {
        var raw = parseInt($btn.closest('.vcart-qty, .qty-cart').find('input').first().val() || '1', 10);
        return Number.isFinite(raw) && raw > 0 ? raw : 1;
    }

    function set_qty($btn, qty) {
        $btn.closest('.vcart-qty, .qty-cart').find('input').first().val(String(qty));
    }

    function set_pending($btn, flag) {
        var $scope = $btn.closest('.vcart-qty, .qty-cart');
        $scope.find('.cart_increment, .cart_decrement').prop('disabled', !!flag);
        if (flag) {
            $scope.addClass('is-cart-pending');
        } else {
            $scope.removeClass('is-cart-pending');
        }
    }

    $('.cart_store').on('click', function() {
        var id = $(this).data('id');
        var qty = $(this).parent().find('input').val();
        if (id) {
            $.ajax({
                type: "GET",
                data: {
                    'id': id,
                    'qty': qty ? qty : 1
                },
                url: "{{route('cart.store')}}",
                success: function(data) {
                    if (data) {
                        return cart_count();
                    }
                }
            });
        }
    });

    $('.cart_remove').on('click', function() {
        var id = $(this).data('id');
        if (id) {
            $.ajax({
                type: "GET",
                data: {
                    'id': id
                },
                url: "{{route('cart.remove')}}",
                success: function(data) {
                    if (data) {
                        sidebar_refresh();
                        return cart_count();
                    }
                }
            });
        }
    });

    $('.cart_increment').on('click', function() {
        var $btn = $(this);
        var id = $(this).data('id');
        if (!id || $btn.prop('disabled')) return;
        var prevQty = get_qty($btn);
        var nextQty = prevQty + 1;
        set_qty($btn, nextQty); // optimistic update
        set_pending($btn, true);
        cart_update(id, nextQty).done(function(response) {
            if (response && response.status === 'error') {
                set_qty($btn, prevQty); // rollback
            }
        }).fail(function() {
            set_qty($btn, prevQty); // rollback
        }).always(function() {
            set_pending($btn, false);
            sidebar_refresh();
            cart_count();
        });
    });

    $('.cart_decrement').on('click', function() {
        var $btn = $(this);
        var id = $(this).data('id');
        if (!id || $btn.prop('disabled')) return;
        var currentQty = get_qty($btn);
        if (currentQty <= 1) {
            $.ajax({
                type: "GET",
                data: { 'id': id },
                url: "{{route('cart.remove')}}"
            }).always(function() {
                sidebar_refresh();
                cart_count();
            });
            return;
        }
        var prevQty = currentQty;
        var nextQty = currentQty - 1;
        set_qty($btn, nextQty); // optimistic update
        set_pending($btn, true);
        cart_update(id, nextQty).done(function(response) {
            if (response && response.status === 'error') {
                set_qty($btn, prevQty); // rollback
            }
        }).fail(function() {
            set_qty($btn, prevQty); // rollback
        }).always(function() {
            set_pending($btn, false);
            sidebar_refresh();
            cart_count();
        });
    });

    function cart_count() {
        $.ajax({
            type: "GET",
            url: "{{route('cart.count')}}",
            success: function(data) {
                var count = (data && typeof data === 'object' && data.cart_count !== undefined) ? parseInt(data.cart_count, 10) : parseInt(data, 10);
                if (Number.isFinite(count) && count >= 0) {
                    $("#cart-qty").html(count);
                } else {
                    $("#cart-qty").empty();
                }
            }
        });
    };
</script>
<!-- cart js end -->
