{{--
    FM:SECTION home/trust-row ----------------------------------------------
    The four-up trust strip (design: work/home.html, "TrustBadgesRow").

    The reference hard-codes four marketing lines. Those lines are already
    written once for the whole site in App\Support\ShopPromises and are the
    same four the header strip, the guarantees block and the about/contact
    pages show - so this section reads that class instead of repeating the
    copy, and re-wording one promise updates every placement at once.

    Only the LAYOUT differs from sections/guarantees.blade.php, which is kept
    untouched for the pages that already include it.
    --------------------------------------------------------------------- --}}

@php
    $fmPromises = \App\Support\ShopPromises::all();
@endphp

<section class="fm-trust-row-section" data-section="shop-guarantees">
    <div class="fm-container">
        <div class="fm-trust-grid">
            @foreach($fmPromises as $fmPromise)
                <div class="fm-trust-box">
                    <div class="fm-trust-box-icon">
                        <i class="bi {{ $fmPromise->icon }}" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4>{{ $fmPromise->title }}</h4>
                        <p>{{ $fmPromise->text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>