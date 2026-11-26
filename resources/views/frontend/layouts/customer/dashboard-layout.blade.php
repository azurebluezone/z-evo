{{--
    Classic user-dashboard shell -------------------------------------------
    Markup converted from the ViserMart demo pages in work/Classic u db/:
        <main> .user-profile-section > .container > .dashboard-wrapper
               > aside.dashboard-menu + .dashboard-content > .breadcrumb

    Those demo pages are pages of THIS template, so the shell is
    frontend.layouts.app: the one that renders frontend.layouts.partials.header
    (<header class="header-area">) and loads assets/templates/basic/css/main.css.
    frontend.layouts.master belongs to the older screens and is NOT used here.

    Children provide:
        @section('title')             browser title
        @section('dashboard-title')   breadcrumb heading
        @section('dashboard-content') page body
--}}
@extends('frontend.layouts.app')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/user-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/classic-account-tables.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/classic-account-pages.css') }}">
@endpush

@section('content')
    <div class="user-profile-section py-60 classic-dashboard">
        <div class="container">
            <div class="dashboard-wrapper">
                @include('frontend.layouts.customer.partials.dashboard-nav')

                <div class="dashboard-content">
                    <div class="breadcrumb" aria-label="breadcrumb">
                        <h6>@yield('dashboard-title', 'Dashboard')</h6>
                    </div>

                    @yield('dashboard-content')
                </div>
            </div>
        </div>
    </div>
@endsection
