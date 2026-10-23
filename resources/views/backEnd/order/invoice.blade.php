@extends('backEnd.layouts.app')
@section('title','Order Invoice')

{{-- The admin layout has no print rules, so printing this page would otherwise
     include the sidebar, navbar and action row. Hide the chrome and let the
     invoice card fill the sheet. --}}
@section('css')
<style>
    @media print {
        @page { margin: 12mm; }

        html,
        body { background: #fff !important; }

        .layout-menu,
        .layout-navbar,
        .content-footer,
        .layout-overlay,
        .drag-target,
        .no-print { display: none !important; }

        .layout-wrapper,
        .layout-container,
        .layout-page,
        .content-wrapper,
        .container-xxl,
        .container-p-y,
        .row,
        .col-12 {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: 0 !important;
        }

        .card { border: 0 !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; }
        .table-responsive { overflow: visible !important; }
        a[href]::after { content: none !important; }
    }
</style>
@endsection

@section('content')
@php
    $logoImg = $generalsetting && $generalsetting->white_logo ? $generalsetting->white_logo : null;
    $logoImgPath = $logoImg ? preg_replace('~^public/~', '', $logoImg) : null;
    $logoImgPath = $logoImgPath ? ltrim($logoImgPath, '/') : null;
    $fallbackLogo = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
    $fallbackLogo = preg_replace('~^public/~', '', $fallbackLogo);
    $fallbackLogo = ltrim($fallbackLogo, '/');
    $logoPath = $logoImgPath && file_exists(public_path($logoImgPath)) ? asset($logoImgPath) : asset($fallbackLogo);

    $subtotal = 0;
    foreach (($order->orderdetails ?? []) as $d) {
        $subtotal += ((float) ($d->sale_price ?? 0)) * ((int) ($d->qty ?? 0));
    }
    $discount = (float) ($order->discount ?? 0);
    $shipping = (float) ($order->shipping_charge ?? 0);
    $grandTotal = (float) ($order->amount ?? ($subtotal + $shipping - $discount));
@endphp

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary btn-sm">Invoice List</a>
                <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-sm">Orders</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <x-admin.button variant="primary" size="sm" type="button" onclick="window.print()">
                    <i class="icon-base ri ri-printer-line icon-18px me-1"></i>
                    Print
                </x-admin.button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <img src="{{ $logoPath }}" alt="" style="height: 46px;" class="backend-image">
                        <div class="mt-2">
                            <div class="fw-semibold">{{ $generalsetting->name ?? '' }}</div>
                            <div class="text-muted small">{{ $contact->phone ?? '' }}</div>
                            <div class="text-muted small">{{ $contact->email ?? '' }}</div>
                            <div class="text-muted small">{{ $contact->address ?? '' }}</div>
                        </div>
                    </div>

                    <div class="text-end">
                        <div class="h4 mb-1">Invoice</div>
                        <div class="text-muted">Invoice ID: <span class="fw-semibold">#{{ $order->invoice_id }}</span></div>
                        <div class="text-muted">Invoice Date: <span class="fw-semibold">{{ $order->created_at ? $order->created_at->format('d-m-Y') : '' }}</span></div>
                        <div class="text-muted">Payment Method: <span class="fw-semibold text-uppercase">{{ $order->payment ? ($order->payment->payment_method ?? '') : '' }}</span></div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-2">Invoice To</h6>
                        <div class="text-muted">{{ $order->shipping ? $order->shipping->name : '' }}</div>
                        <div class="text-muted">{{ $order->shipping ? $order->shipping->phone : '' }}</div>
                        <div class="text-muted">{{ $order->shipping ? $order->shipping->address : '' }}</div>
                        <div class="text-muted">{{ $order->shipping ? $order->shipping->area : '' }}</div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="mb-2">Summary</h6>
                        <div class="d-flex justify-content-md-end justify-content-between">
                            <div class="text-muted me-3">Subtotal</div>
                            <div class="fw-semibold">৳{{ number_format($subtotal) }}</div>
                        </div>
                        <div class="d-flex justify-content-md-end justify-content-between">
                            <div class="text-muted me-3">Discount</div>
                            <div class="fw-semibold">৳{{ number_format($discount) }}</div>
                        </div>
                        <div class="d-flex justify-content-md-end justify-content-between">
                            <div class="text-muted me-3">Shipping</div>
                            <div class="fw-semibold">৳{{ number_format($shipping) }}</div>
                        </div>
                        <div class="d-flex justify-content-md-end justify-content-between border-top pt-2 mt-2">
                            <div class="text-muted me-3">Total</div>
                            <div class="fw-semibold">৳{{ number_format($grandTotal) }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped w-100 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;">SL</th>
                                <th>Product</th>
                                <th style="width: 120px;">Price</th>
                                <th style="width: 80px;">Qty</th>
                                <th style="width: 140px;" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderdetails as $value)
                                @php($lineTotal = ((float) ($value->sale_price ?? 0)) * ((int) ($value->qty ?? 0)))
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $value->product_name }}</div>
                                        <div class="text-muted small">
                                            @if($value->product_size)
                                                <span class="me-2">Size: {{ $value->product_size }}</span>
                                            @endif
                                            @if($value->product_color)
                                                <span>Color: {{ $value->product_color }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>৳{{ number_format((float) ($value->sale_price ?? 0)) }}</td>
                                    <td>{{ (int) ($value->qty ?? 0) }}</td>
                                    <td class="text-end">৳{{ number_format($lineTotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-center text-muted mt-4">
                    <div class="fw-semibold">Terms & Conditions</div>
                    <div class="small">This is a computer generated invoice, does not require any signature.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
