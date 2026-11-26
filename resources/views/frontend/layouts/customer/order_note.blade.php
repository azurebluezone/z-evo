@extends('frontend.layouts.customer.dashboard-layout')

@section('title', 'Order Note')
@section('dashboard-title', 'Order Note')

@section('dashboard-content')
    <div class="classic-account-page">
        <div class="classic-account-head">
            <h6 class="classic-account-head__title">
                Invoice #{{ $order->invoice_id }}
                <span class="classic-account-badge">Note</span>
            </h6>

            <a href="{{ route('customer.orders') }}" class="classic-notice__action">
                <i class="fa-solid fa-arrow-left"></i> Back to orders
            </a>
        </div>

        <div class="card custom--card">
            <div class="card-body">
                {!! $order->admin_note !!}
            </div>
        </div>
    </div>
@endsection
