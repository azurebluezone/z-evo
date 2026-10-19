@extends('frontend.layouts.app')

@section('title', 'Contact Us')
@section('meta_description', 'Contact Visermart')

@section('content')
<main>
    <div class="contact-area pt-60 pb-60">
        <div class="container">
            <div class="row g-4 gy-4 justify-content-xl-between flex-wrap-reverse">

                <div class="col-lg-6 col-xxl-4 col-xl-5">
                    <div class="mb-4">
                        <h5 class="mb-3">We'd Love to Hear From You</h5>
                        <p>We appreciate feedback and interaction of any sort so please feel free to get in touch.</p>
                    </div>

                    <ul class="list">
                        <li>
                            <div class="contact align-items-center">
                                <div class="contact__icon"><i class="las la-map-marker"></i></div>
                                <div class="contact__content">
                                    <h6 class="contact__title">Office Address</h6>
                                    <p class="mb-0 sm-text">4517 Washington Ave. Manchester, Kentucky 39495 Main town USA.</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="contact align-items-center">
                                <div class="contact__icon"><i class="las la-mobile-alt"></i></div>
                                <div class="contact__content">
                                    <h6 class="contact__title">Mobile</h6>
                                    <p class="mb-0 sm-text">0182646854 +9574651651</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="contact align-items-center">
                                <div class="contact__icon"><i class="las la-envelope"></i></div>
                                <div class="contact__content">
                                    <h6 class="contact__title">Email</h6>
                                    <p class="mb-0 sm-text">info@visermart.com</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-6 col-xxl-8 col-xl-7 ps-xxl-5">
                    <h5 class="mb-4">Leave Us a Message</h5>
                    <div class="contact-form">
                        @if(session()->has('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session()->has('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form action="{{ url('/contact-submit') }}" method="post">
                            @csrf

                            <div class="mb-3">
                                <label class="form--label" for="fullname">Full Name</label>
                                <input type="text" class="form-control form--control" id="fullname" name="name" value="{{ old('name') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form--label" for="email">Email</label>
                                <input type="email" class="form-control form--control" id="email" name="email" value="{{ old('email') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form--label" for="subject">Subject</label>
                                <input type="text" class="form-control form--control" id="subject" name="subject" value="{{ old('subject') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form--label" for="message">Message</label>
                                <textarea class="form-control form--control" name="message" rows="3" id="message" required>{{ old('message') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <button class="btn btn--base h-45" type="submit">Send Your Message</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection
