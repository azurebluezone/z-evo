@extends('frontend.layouts.app')

@section('title', 'About Us')
@section('meta_description', 'About Visermart')

@section('content')
<main>
    <div class="bg-white">
        <div class="banner" style="background-image: url('{{ asset('assets/images/frontend/about_us/6723853527b291730381109.png') }}');">
            <div class="banner__content">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-7 col-lg-6 col-xxl-5">
                            <div class="about-banner">
                                <h1 class="about-banner__title">We believe we can all make a stylish.</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="my-60">
            <div class="container">
                <div class="row g-4 justify-content-between align-items-center">
                    <div class="col-md-5 col-lg-6">
                        <div class="text-center">
                            <img src="{{ asset('assets/images/default.png') }}" data-src="{{ asset('assets/images/frontend/about_us/672607bcbd2991730545596.png') }}" alt="image" class="img-fluid lazyload">
                        </div>
                    </div>

                    <div class="col-md-7 col-lg-6">
                        <div class="about-description-wrapper">
                            <span class="section__subtitle">WHO WE ARE</span>
                            <h2 class="section__title mt-2">Happy Shopping With Visermart</h2>
                            <div class="description">
                                <p class="sm-text">Visermart is a web-based commercial center with online business stores in USA.</p>
                                <ul class="list--check align-items-start">
                                    <li class="sm-text">Various Kinds Of Product</li>
                                    <li class="sm-text">Large customer reach.</li>
                                    <li class="sm-text">Universal standard.</li>
                                    <li class="sm-text">Easy to use the checkout</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="testimonial">
            <div class="container justify-content-center">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="text-center d-none d-md-block">
                            <img src="{{ asset('assets/images/frontend/quote/6723b205530861730392581.png') }}" alt="image" class="img-fluid testimonial__img" />
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="testimonial__content py-5">
                            <p class="testimonial__text">Visermart is a popular shopping platform for you.</p>
                            <div class="testimonial__footer">
                                <h2 class="testimonial__title">Esther Howard</h2>
                                <span class="testimonial__cite">CEO, Visermart</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="my-60">
            <div class="section__head">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="text-center">
                                <span class="section__subtitle">FEATURES</span>
                                <h2 class="section__title my-3 text-center justify-content-center">Your Easy Shopping</h2>
                                <p class="sm-text text-center mb-0">Make your life easy by getting our exclusive products</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row gy-3 justify-content-center">
                    <div class="col-lg-4">
                        <ul class="list feature-card--list">
                            <li>
                                <div class="feature-card">
                                    <div class="feature-card__head">
                                        <div class="feature-card__icon">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <h4 class="feature-card__title">Safe Payment</h4>
                                    </div>
                                    <div class="feature-card__body">
                                        <p class="m-0 sm-text">Secure transactions with trusted payment methods for worry-free shopping.</p>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="feature-card">
                                    <div class="feature-card__head">
                                        <div class="feature-card__icon">
                                            <i class="fas fa-hands-helping"></i>
                                        </div>
                                        <h4 class="feature-card__title">Help Center</h4>
                                    </div>
                                    <div class="feature-card__body">
                                        <p class="m-0 sm-text">24/7 support to assist with orders, payments, and inquiries.</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-4 order-3 order-lg-2 d-none d-lg-block">
                        <div class="text-center">
                            <img src="{{ asset('assets/images/default.png') }}" data-src="{{ asset('assets/images/frontend/feature/6723b902c14761730394370.png') }}" alt="image" class="img-fluid lazyload">
                        </div>
                    </div>

                    <div class="col-lg-4 order-2 order-lg-3">
                        <ul class="list feature-card--list">
                            <li>
                                <div class="feature-card">
                                    <div class="feature-card__head">
                                        <div class="feature-card__icon">
                                            <i class="fab fa-product-hunt"></i>
                                        </div>
                                        <h4 class="feature-card__title">Return Products</h4>
                                    </div>
                                    <div class="feature-card__body">
                                        <p class="m-0 sm-text">Hassle-free returns with a simple and quick refund process.</p>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="feature-card">
                                    <div class="feature-card__head">
                                        <div class="feature-card__icon">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                        <h4 class="feature-card__title">Fast Delivery</h4>
                                    </div>
                                    <div class="feature-card__body">
                                        <p class="m-0 sm-text">Swift shipping ensures your orders arrive on time, every time.</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection
