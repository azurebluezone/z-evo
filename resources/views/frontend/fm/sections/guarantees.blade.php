{{--
    FM:COMPONENT guarantees
    =========================================================================
    The four-up trust strip.

    Markup is the reference block cut from work/2.md by
    tools/split_design_sections.py (data-section="shop-guarantees"), but the
    copy is NOT copied from it. The design mockup promises "Same-Day Express",
    "100% Fresh Guarantee", "Eco-Friendly Pack" and "24/7 Farm Concierge" - none
    of which this shop can actually honour. Rendering them would put four
    promises on the page that the business does not keep.

    Instead the copy comes from ShopPromises::all(), the same single source the
    cart summary and the product-detail tabs already render, so the four
    promises read identically everywhere and are changed in one place.
--}}

@php
    // ShopPromises::all() returns a plain array, not a Collection - calling
    // ->isNotEmpty() on it is a fatal, which is why the two existing callers
    // only ever @foreach the result.
    $fmPromises = \App\Support\ShopPromises::all();
@endphp

@if (!empty($fmPromises))
    <section class="fm-guarantees" data-section="shop-guarantees">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($fmPromises as $fmPromise)
                    <div class="fm-guarantee">
                        <div class="fm-guarantee__icon">
                            <i class="bi {{ $fmPromise->icon }} text-xl" aria-hidden="true"></i>
                        </div>
                        <div class="fm-guarantee__text">
                            <h4 class="fm-guarantee__title">{{ $fmPromise->title }}</h4>
                            <p class="fm-guarantee__sub">{{ $fmPromise->text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif