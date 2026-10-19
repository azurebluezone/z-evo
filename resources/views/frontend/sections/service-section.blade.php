{{-- Service Section --}}
@if(isset($services) && $services->count() > 0)
    <section class="py-60 service-section">
        <div class="container">
            @if(!empty($sections['service']['title'] ?? null))
                <div class="section-header left-style">
                    <h5 class="title">{{ $sections['service']['title'] }}</h5>
                </div>
            @endif
            <div class="services-wrapper">
                @foreach($services as $service)
                    <div class="services-card">
                        <div class="services-card__icon">
                            @php
                                $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
                                $fallbackImage = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                                $fallbackRaw = (string) $fallbackImage;
                                $fallbackIsUrl = preg_match('~^https?://~i', $fallbackRaw) === 1;
                                $fallbackPath = $fallbackIsUrl ? $fallbackRaw : ltrim(preg_replace('~^public/~', '', $fallbackRaw), '/');
                                $fallbackImageSrc = !empty($fallbackPath)
                                    ? ($fallbackIsUrl ? $fallbackPath : asset($fallbackPath))
                                    : asset('assets/images/empty.png');

                                $serviceRaw = (string) ($service->image ?? '');
                                $serviceIsUrl = preg_match('~^https?://~i', $serviceRaw) === 1;
                                $servicePath = $serviceIsUrl ? $serviceRaw : ltrim(preg_replace('~^public/~', '', $serviceRaw), '/');
                                $serviceImageSrc = !empty($servicePath)
                                    ? ($serviceIsUrl ? $servicePath : asset($servicePath))
                                    : $fallbackImageSrc;
                            @endphp
                            <img src="{{ $serviceImageSrc }}" alt="{{ $service->title }}">
                        </div>
                        <div class="services-card__content">
                            <h5 class="services-card__text mb-1">{{ $service->title }}</h5>
                            <span class="sub-title">{{ $service->subtitle }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@else
    {{-- Fallback static content if no services in database --}}
    <section class="py-60 service-section">
        <div class="container">
            @if(!empty($sections['service']['title'] ?? null))
                <div class="section-header left-style">
                    <h5 class="title">{{ $sections['service']['title'] }}</h5>
                </div>
            @endif
            <div class="services-wrapper">
                <div class="services-card">
                    <div class="services-card__icon">
                        <img src="{{ asset('assets/images/frontend/services/6739fca46ea611731853476.png') }}" alt="Secure Payment">
                    </div>
                    <div class="services-card__content">
                        <h5 class="services-card__text mb-1">Secure Payment</h5>
                        <span class="sub-title">All Cards Accepted</span>
                    </div>
                </div>

                <div class="services-card">
                    <div class="services-card__icon">
                        <img src="{{ asset('assets/images/frontend/services/6739fcbe2422c1731853502.png') }}" alt="Free Shipping">
                    </div>
                    <div class="services-card__content">
                        <h5 class="services-card__text mb-1">Free Shipping</h5>
                        <span class="sub-title">On All Order</span>
                    </div>
                </div>

                <div class="services-card">
                    <div class="services-card__icon">
                        <img src="{{ asset('assets/images/frontend/services/6739f654a40241731851860.png') }}" alt="Online Support">
                    </div>
                    <div class="services-card__content">
                        <h5 class="services-card__text mb-1">Online Support</h5>
                        <span class="sub-title">Technical 24/7</span>
                    </div>
                </div>

                <div class="services-card">
                    <div class="services-card__icon">
                        <img src="{{ asset('assets/images/frontend/services/6739f65c524471731851868.png') }}" alt="Big Savings">
                    </div>
                    <div class="services-card__content">
                        <h5 class="services-card__text mb-1">Big Savings</h5>
                        <span class="sub-title">Weekend Sales</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif