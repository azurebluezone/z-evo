@php
    $product_discount = 0;
@endphp
@foreach($cartinfo as $key=>$value)
<tr>
    <td><img height="30" src="{{ asset(preg_replace('/^public\//', '', $value->options->image)) }}"></td>
    <td>{{$value->name}}</td>
    <td>
        <div class="qty-cart vcart-qty">
            <div class="quantity">
                <x-admin.button variant="outline-secondary" size="sm" class="minus cart_decrement" type="button" value="{{$value->qty}}" data-id="{{$value->rowId}}">-</x-admin.button>
                <input type="text" value="{{$value->qty}}" readonly style="width:40px;text-align:center;" />
                <x-admin.button variant="outline-secondary" size="sm" class="plus cart_increment" type="button" value="{{$value->qty}}" data-id="{{$value->rowId}}">+</x-admin.button>
            </div>
        </div>
    </td>
    <td>{{$value->price}}</td>
    <td class="discount">
        <input type="number" class="product_discount form-control form-control-sm" value="{{$value->options->product_discount}}" placeholder="0.00" data-id="{{$value->rowId}}" style="width:80px;">
    </td>
    <td>{{($value->price - $value->options->product_discount)*$value->qty}}</td>
    <td>
        <x-admin.button variant="danger" size="sm" class="cart_remove" type="button" data-id="{{$value->rowId}}" aria-label="Remove">
            <span aria-hidden="true">&times;</span>
        </x-admin.button>
    </td>
</tr>
@php
    $product_discount += $value->options->product_discount * $value->qty;
    Session::put('product_discount', $product_discount);
@endphp
@endforeach
