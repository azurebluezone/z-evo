{{--
    FM:SECTION cart-list ----------------------------------------------------
    Line items of /shop/cart, one card per cart row, plus the empty state.

    Expects (computed once in pages/cart.blade.php):
        $fmLines   array<int, array{row:string,name:string,qty:int,price:float,
                                    old:float,total:float,url:string,image:string,
                                    variant:string,saving:float}>
        $fmQty     int   total quantity across every row

    Every control is wired through data-fm-line-action so the page level
    script can drive it. The attribute is deliberately NOT data-cart-action:
    that one belongs to fm-app.js and only repaints the drawer, so reusing it
    here would let two handlers fight over the same click.

    Quantity rules match the classic cart exactly: "+" adds one, "-" removes
    one, and pressing "-" at 1 removes the row (the package drops any row
    whose quantity falls to zero).

    The image goes through MediaPath::url() because the cart stores whatever
    shape the product table held - "public/uploads/...", "uploads/..." or a
    bare filename.
-------------------------------------------------------------------------- --}}

<div class="fm-basket-list" data-section="cart-list">
        @if($fmLines === [])
            {{-- Empty basket: the approved design's empty state, pointed at a
                 real route instead of the prototype's dead #featured-products
                 anchor. The summary column stays beside it so the checkout
                 link the classic page always had is never taken away. --}}
            <div class="fm-basket-empty">
                <span class="fm-basket-empty__icon"><i class="bi bi-basket2" aria-hidden="true"></i></span>
                <h2 class="fm-basket-empty__title">Your Basket is Currently Empty</h2>
                <p class="fm-basket-empty__text">
                    Looks like you have not added anything yet. Browse the catalog and
                    add the products you need.
                </p>
                <a class="btn btn-brand-primary rounded-full px-5 py-3 text-sm font-bold"
                   href="{{ route('products.page') }}">
                    <i class="bi bi-shop me-2" aria-hidden="true"></i>Start shopping
                </a>
            </div>
        @else
            <div class="fm-basket-card">
                <div class="fm-basket-card__head">
                    <h1 class="fm-basket-card__title">Shopping Basket</h1>
                    <span class="fm-basket-card__count">
                        {{ $fmQty }} {{ $fmQty === 1 ? 'item' : 'items' }}
                    </span>
                </div>

                <div class="fm-basket-card__body">
                    @foreach($fmLines as $fmLine)
                        <article class="fm-basket-row"
                                 data-fm-line="{{ $fmLine['row'] }}"
                                 data-fm-line-qty="{{ $fmLine['qty'] }}"
                                 data-fm-line-price="{{ $fmLine['price'] }}"
                                 data-fm-line-old="{{ $fmLine['old'] }}">
                            <a class="fm-basket-row__thumb" href="{{ $fmLine['url'] }}" tabindex="-1" aria-hidden="true">
                                <img src="{{ $fmLine['image'] }}"
                                     alt="{{ $fmLine['name'] }}"
                                     width="96" height="96"
                                     loading="lazy" decoding="async">
                            </a>

                            <div class="fm-basket-row__body">
                                <div class="fm-basket-row__head">
                                    <div class="fm-basket-row__id">
                                        @if($fmLine['variant'] !== '')
                                            <span class="fm-basket-row__meta">{{ $fmLine['variant'] }}</span>
                                        @endif
                                        <h2 class="fm-basket-row__name">
                                            <a href="{{ $fmLine['url'] }}">{{ $fmLine['name'] }}</a>
                                        </h2>
                                        <p class="fm-basket-row__unit">Tk {{ number_format($fmLine['price']) }} each</p>
                                    </div>

                                    <div class="fm-basket-row__price">
                                        <span class="fm-basket-row__total" data-fm-line-total>
                                            Tk {{ number_format($fmLine['total']) }}
                                        </span>
                                        @if($fmLine['old'] > $fmLine['price'])
                                            <s class="fm-basket-row__old">Tk {{ number_format($fmLine['old']) }}</s>
                                        @endif
                                    </div>
                                </div>

                                <div class="fm-basket-row__foot">
                                    <div class="fm-qty">
                                        <button class="fm-qty__btn"
                                                type="button"
                                                data-fm-line-action="dec"
                                                data-fm-line-target="{{ $fmLine['row'] }}"
                                                aria-label="Decrease quantity">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <span class="fm-qty__value" data-fm-line-qty-text aria-live="polite">{{ $fmLine['qty'] }}</span>
                                        <button class="fm-qty__btn"
                                                type="button"
                                                data-fm-line-action="inc"
                                                data-fm-line-target="{{ $fmLine['row'] }}"
                                                aria-label="Increase quantity">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                    <button class="fm-basket-row__remove"
                                            type="button"
                                            data-fm-line-action="remove"
                                            data-fm-line-target="{{ $fmLine['row'] }}">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                        <span>Remove</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
</div>
