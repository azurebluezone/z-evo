{{--
    FM:PARTIAL overlays/mini-cart-drawer
    =========================================================================
    The right-hand offcanvas the cart button opens.

    The line items are NOT rendered here: #cartItemsContainer is an empty shell
    that fm-app.js fills from /cart/mini-items (see
    partials/cart-items.blade.php). Only the totals are server rendered, so the
    drawer shows the right number on first paint and JS keeps it in sync after
    every add/remove.

    Props
        fmCartTotal  float  cart subtotal, server rendered into both totals

    Usage
        @include('frontend.fm.partials.overlays.mini-cart-drawer')
--}}

<!-- BEGIN: MiniCartOffcanvas -->
<div aria-labelledby="cartDrawerLabel" class="offcanvas offcanvas-end" data-fm-cart-drawer id="cartDrawer" style="--bs-offcanvas-width: min(380px, 100%); max-width: 100%;" tabindex="-1">
    <div class="offcanvas-header border-b border-gray-100 bg-emerald-50/50">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-basket text-xl text-emerald-800"></i>
            <h5 class="offcanvas-title font-bold text-emerald-950 text-base" id="cartDrawerLabel">My shopping basket</h5>
        </div>
        <button aria-label="Close" class="btn-close text-reset text-xs" data-bs-dismiss="offcanvas" type="button"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-between p-4">
        {{-- Cart Items List Container --}}
        <div class="space-y-4 overflow-y-auto" data-fm-cart-items id="cartItemsContainer">
            {{-- Line items render here from /cart/mini-items (see cart-items partial) --}}
        </div>

        {{-- Cart Summary & Checkout --}}
        <div class="pt-4 border-t border-gray-100 mt-auto">
            <div class="d-flex justify-between text-xs text-gray-500 mb-1">
                <span>Total</span>
                <span class="font-bold text-gray-900" data-fm-cart-total id="drawerSubtotal">Tk {{ number_format($fmCartTotal ?? 0) }}</span>
            </div>
            <div class="d-flex justify-between text-xs text-gray-500 mb-3">
                <span>Delivery charge</span>
                <span class="text-emerald-700 font-bold">Calculated at checkout</span>
            </div>
            <div class="d-flex justify-between text-sm font-extrabold text-gray-900 pt-2 border-t border-dashed border-gray-200 mb-4">
                <span>Total</span>
                <span class="text-emerald-800 text-lg" data-fm-cart-total id="drawerTotal">Tk {{ number_format($fmCartTotal ?? 0) }}</span>
            </div>
            <a class="btn btn-outline-success w-100 py-2 rounded-xl font-bold text-xs" href="{{ route('cart.show') }}">
                View full cart
            </a>
            <a class="btn btn-brand-primary w-100 py-3 rounded-xl font-bold text-sm shadow-sm mt-2" href="{{ route('customer.checkout') }}">
                Proceed to checkout &rarr;
            </a>
            <button class="btn btn-link text-xs text-gray-500 w-100 mt-2 text-decoration-none" data-bs-dismiss="offcanvas">
                Continue shopping
            </button>
        </div>
    </div>
</div>
<!-- END: MiniCartOffcanvas -->