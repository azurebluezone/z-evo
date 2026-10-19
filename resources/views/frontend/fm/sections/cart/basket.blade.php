{{--
    FM:PARTIAL cart/basket
    =========================================================================
    The basket itself: the line-item list beside the summary.

    Both columns are the existing sections - cart-list and cart-summary - and
    both read the same pre-computed $fmLines / $fmSubtotal / $fmSavings the
    page assembled in ONE pass over the cart, so the list, the summary and the
    JS repaint can never quote three different totals.

    The cart-progress band above this block is a separate include: it is the one
    part of the page that also appears on the checkout, so it is not owned by
    the basket.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.cart.basket')
--}}

{{-- FM:SECTION basket --------------------------------------------------- --}}
<section class="fm-basket" data-section="cart">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-8">
                @include('frontend.fm.sections.cart-list')
            </div>
            <div class="col-12 col-lg-4">
                @include('frontend.fm.sections.cart-summary')
            </div>
        </div>
    </div>
</section>