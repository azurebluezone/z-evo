{{--
    FM:SECTION cart-items ===================================================
    Line items of the mini-cart offcanvas, served by the cart/mini-items
    endpoint and dropped straight into #cartItemsContainer by fm-app.js.

    The wrapper element carries the two numbers the client repaints from:
      data-fm-cart-meta-count    - total quantity, painted into the header badge
      data-fm-cart-meta-subtotal - cart subtotal, painted into every
                                   [data-fm-cart-total] element

    The meta prefix matters: the badge hook itself is data-fm-cart-count, and
    paintCartCount() writes textContent into every element carrying it - reusing
    that name here would wipe the line items it sits above.

    Keeping both server rendered means the header badge and the drawer
    totals can never disagree with the cart itself.
--}}
@php
    // The cart is read through the app wide Cart alias (hardevine/shoppingcart),
    // exactly like ShoppingController does. Only the read is guarded: a failed
    // read still has to render an empty drawer, but nothing else in the loop
    // is allowed to silently empty a cart that actually has items in it.
    $fmCartRows = [];
    try {
        $fmCartRows = Cart::instance('shopping')->content();
    } catch (\Throwable $e) {
        $fmCartRows = [];
    }

    $fmCartLines    = [];
    $fmCartQty      = 0;
    $fmCartSubtotal = 0.0;

    foreach ($fmCartRows as $fmCartRow) {
        $fmCartQty      += (int) ($fmCartRow->qty ?? 0);
        $fmCartSubtotal += (float) ($fmCartRow->price ?? 0) * (int) ($fmCartRow->qty ?? 0);

        $fmCartSlug = (string) ($fmCartRow->options->slug ?? '');

        $fmCartLines[] = [
            'row'   => (string) $fmCartRow->rowId,
            'name'  => (string) ($fmCartRow->name ?? ''),
            'qty'   => (int) ($fmCartRow->qty ?? 1),
            'price' => (float) ($fmCartRow->price ?? 0),
            'total' => (float) ($fmCartRow->price ?? 0) * (int) ($fmCartRow->qty ?? 1),
            'url'   => $fmCartSlug !== '' ? route('product', $fmCartSlug) : route('products.page'),
            'image' => \App\Support\MediaPath::url((string) ($fmCartRow->options->image ?? '')),
        ];
    }
@endphp

<div class="fm-cart-lines" data-fm-cart-meta-count="{{ $fmCartQty }}" data-fm-cart-meta-subtotal="{{ $fmCartSubtotal }}">
    @forelse($fmCartLines as $fmCartLine)
        <article class="fm-cart-line" data-fm-cart-row="{{ $fmCartLine['row'] }}">
            <a class="fm-cart-line__thumb" href="{{ $fmCartLine['url'] }}" tabindex="-1" aria-hidden="true">
                <img src="{{ $fmCartLine['image'] }}" alt="" loading="lazy" decoding="async" width="72" height="72">
            </a>

            <div class="fm-cart-line__body">
                <div class="fm-cart-line__head">
                    <a class="fm-cart-line__name" href="{{ $fmCartLine['url'] }}">{{ $fmCartLine['name'] }}</a>
                    <button class="fm-cart-line__remove"
                            type="button"
                            data-cart-action="remove"
                            data-cart-row="{{ $fmCartLine['row'] }}"
                            aria-label="Remove from cart"
                            title="Remove from cart">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="fm-cart-line__price">Tk {{ number_format($fmCartLine['price']) }}</div>

                <div class="fm-cart-line__foot">
                    <div class="fm-qty">
                        <button class="fm-qty__btn"
                                type="button"
                                data-cart-action="minus"
                                data-cart-row="{{ $fmCartLine['row'] }}"
                                aria-label="Decrease quantity"
                                @disabled($fmCartLine['qty'] <= 1)>
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                        <span class="fm-qty__value" aria-live="polite">{{ $fmCartLine['qty'] }}</span>
                        <button class="fm-qty__btn"
                                type="button"
                                data-cart-action="plus"
                                data-cart-row="{{ $fmCartLine['row'] }}"
                                aria-label="Increase quantity">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        </button>
                    </div>

                    <span class="fm-cart-line__total">Tk {{ number_format($fmCartLine['total']) }}</span>
                </div>
            </div>
        </article>
    @empty
        <div class="fm-cart-lines__empty">
            <i class="bi bi-basket2" aria-hidden="true"></i>
            <p>Your cart is empty</p>
            <a class="btn btn-brand-primary rounded-full px-4 py-2 text-xs font-bold"
               href="{{ route('products.page') }}">Start shopping</a>
        </div>
    @endforelse
</div>
