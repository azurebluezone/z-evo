{{--
    FM:SECTION home/flash-sale ---------------------------------------------
    The flash-sale banner with a LIVE countdown and the four deal cards under
    it (design: work/home.html, "FlashSaleSection").

    DATA
        $flash_sale          App\Models\FlashSale|null  already resolved by
                                                    FrontendController to a
                                                    running sale, else the
                                                    next upcoming one
        $flash_sale_products Collection                   $flash_sale->products

    THE CLOCK IS REAL, NOT THE STATIC 08:42:19 OF THE MOCKUP
    -------------------------------------------------------------------------
    The reference hard-codes the digits. Here the countdown is rendered
    server-side from end_time and then kept live by home.blade.php, so the
    first paint is already correct and JS only takes over the ticking.

    An upcoming sale counts DOWN to start_time and says so; a running one
    counts down to end_time. Both are announced once, not re-rendered, so the
    numbers stay readable.

    The block self-hides when there is no sale at all, and when a sale exists
    but carries no products the banner still shows - the promise is real even
    if the grid behind it is empty.
    --------------------------------------------------------------------- --}}

@php
    $fmFlash = $flash_sale ?? null;
    $fmFlashProducts = collect($flash_sale_products ?? collect());

    $fmSectionTitle = (string) ($sections['flash_sale']['title'] ?? 'FLASH SALE');

    $fmEndsAt = null;
    $fmStartsAt = null;
    $fmIsLive = false;

    if ($fmFlash) {
        $fmStartsAt = $fmFlash->start_time;
        $fmEndsAt = $fmFlash->end_time;
        $fmIsLive = (bool) $fmFlash->isRunning();
    }

    // The countdown always points at the boundary that is still ahead.
    $fmTarget = null;
    if ($fmEndsAt && $fmEndsAt->isFuture()) {
        $fmTarget = $fmEndsAt;
    } elseif ($fmStartsAt && $fmStartsAt->isFuture()) {
        $fmTarget = $fmStartsAt;
    }

    $fmSecondsLeft = $fmTarget ? max(0, (int) now()->diffInSeconds($fmTarget, false)) : 0;

    $fmHeadline = $fmFlash?->title ?: ($fmSectionTitle ?: 'Flash Sale');
@endphp

@if($fmFlash && $fmTarget)
    <section class="fm-flash-section" data-purpose="flash-sale-section">
        <div class="fm-container">

            {{-- Banner + clock ------------------------------------------------ --}}
            <div class="fm-flash-banner-card">
                <div class="fm-flash-banner-info">
                    <span class="fm-flash-pill">⚡ {{ $fmIsLive ? 'Live now · limited time deals' : 'Starting soon' }}</span>
                    <h2>{{ $fmHeadline }}</h2>
                    <p>
                        {{ $fmIsLive
                            ? 'Hand-picked markdowns while the clock runs out. Grab them before stock does.'
                            : 'These markdowns go live soon. Set your alarm and come back.' }}
                    </p>
                </div>

                {{-- data-ends-at is the contract with the page script; it ticks
                     the digits and flips data-ended when the target passes. --}}
                <div class="fm-countdown-group"
                     data-fm-countdown
                     data-target="{{ $fmTarget->toIso8601String() }}"
                     data-label-live="{{ $fmIsLive ? 'Ends in' : 'Starts in' }}"
                     data-label-done="{{ $fmIsLive ? 'Sale ended' : 'Sale started' }}"
                     role="timer"
                     aria-live="off">

                    <div class="fm-countdown-unit">
                        <span class="fm-countdown-val" data-fm-cd="h">{{ str_pad((string) intdiv($fmSecondsLeft, 3600), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="fm-countdown-lbl">Hours</span>
                    </div>
                    <span class="fm-countdown-sep">:</span>
                    <div class="fm-countdown-unit">
                        <span class="fm-countdown-val" data-fm-cd="m">{{ str_pad((string) intdiv($fmSecondsLeft % 3600, 60), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="fm-countdown-lbl">Mins</span>
                    </div>
                    <span class="fm-countdown-sep">:</span>
                    <div class="fm-countdown-unit">
                        <span class="fm-countdown-val" data-fm-cd="s">{{ str_pad((string) ($fmSecondsLeft % 60), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="fm-countdown-lbl">Secs</span>
                    </div>

                    <span class="visually-hidden">{{ $fmIsLive ? 'Sale ends' : 'Sale starts' }} at {{ $fmTarget->toDayDateTimeString() }}</span>
                </div>
            </div>

            {{-- Deal cards ------------------------------------------------------ --}}
            @if($fmFlashProducts->isNotEmpty())
                <div class="fm-section-header">
                    <div class="fm-section-header-left">
                        <span class="fm-eyebrow-badge fm-eyebrow-badge-red">⚡ Flash deals</span>
                        <h2 class="fm-section-title">Flash Sale Products</h2>
                        <p class="fm-section-desc">Exclusive mark-downed essentials, refreshed with the campaign.</p>
                    </div>
                    <a class="fm-view-all" href="{{ route('products.page', ['type' => 'discount']) }}">
                        View all flash deals
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div class="fm-product-grid">
                    @foreach($fmFlashProducts->take(4) as $fmDeal)
                        <x-fm::product-card-v2 :product="$fmDeal" variant="home" />
                    @endforeach
                </div>
            @endif

        </div>
    </section>
@endif