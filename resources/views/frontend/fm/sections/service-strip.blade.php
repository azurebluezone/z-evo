{{-- FM:SECTION service-strip ----------------------------------------------
     Four short promises shown directly under the hero. Keeps the hi design
     rhythm without pretending to be a data driven section.
     --------------------------------------------------------------------- --}}

@php
    $fmServices = \App\Support\ShopPromises::all();
@endphp

<section class="fm-service-strip" data-purpose="service-strip">
    <div class="container mx-auto px-4">
        <div class="row g-3">
            @foreach($fmServices as $fmService)
                <div class="col-6 col-lg-3">
                    <div class="fm-service">
                        <span class="fm-service__icon"><i class="bi {{ $fmService->icon }}"></i></span>
                        <div>
                            <h3 class="fm-service__title">{{ $fmService->title }}</h3>
                            <p class="fm-service__text">{{ $fmService->text }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
