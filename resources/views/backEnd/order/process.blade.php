@extends('backEnd.layouts.app')
@section('title','Order Process')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
                <div class="d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center mb-1 flex-wrap">
                        <h5 class="mb-0">Order #{{ $data->invoice_id }}</h5>
                        <span class="badge bg-soft-primary text-primary ms-2">{{ $data->payment ? ($data->payment->payment_status ?? '') : '' }}</span>
                        <span class="badge bg-soft-info text-info ms-2">{{ $data->status ? $data->status->name : '' }}</span>
                    </div>
                    <p class="mb-0">{{ $data->created_at ? $data->created_at->format('d M, Y, h:i a') : '' }}</p>
                </div>
                <div class="d-flex align-content-center flex-wrap gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.orders', ['slug' => 'all']) }}">Back</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.order.invoice', ['invoice_id' => $data->invoice_id]) }}">Invoice</a>
                </div>
            </div>
        </div>
    </div>

    @php
        $subtotal = 0;
        foreach (($data->orderdetails ?? []) as $d) {
            $subtotal += ((float) ($d->sale_price ?? 0)) * ((int) ($d->qty ?? 0));
        }
        $discount = (float) ($data->discount ?? 0);
        $shipping = (float) ($data->shipping_charge ?? 0);
        $grandTotal = (float) ($data->amount ?? ($subtotal + $shipping - $discount));
    @endphp

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 56px;"></th>
                                    <th>Products</th>
                                    <th style="width: 120px;">Price</th>
                                    <th style="width: 90px;">Qty</th>
                                    <th style="width: 140px;" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data->orderdetails as $product)
                                    @php
                                        $lineTotal = ((float) ($product->sale_price ?? 0)) * ((int) ($product->qty ?? 0));
                                        $prodImg = $product->image && $product->image->image ? $product->image->image : null;
                                        $prodImgPath = $prodImg ? preg_replace('~^public/~', '', $prodImg) : null;
                                        $prodImgPath = $prodImgPath ? ltrim($prodImgPath, '/') : null;
                                        $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                                        $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
                                        $fallbackImg = ltrim($fallbackImg, '/');
                                        $imgPath = $prodImgPath && file_exists(public_path($prodImgPath)) ? asset($prodImgPath) : asset($fallbackImg);
                                    @endphp
                                    <tr>
                                        <td>
                                            <img src="{{ $imgPath }}" alt="" class="backend-image" style="height: 44px; width: 44px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/empty.png') }}'">
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $product->product_name }}</div>
                                            <div class="text-muted small">
                                                @if($product->product_size)
                                                    <span class="me-2">Size: {{ $product->product_size }}</span>
                                                @endif
                                                @if($product->product_color)
                                                    <span>Color: {{ $product->product_color }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>৳{{ number_format((float) ($product->sale_price ?? 0)) }}</td>
                                        <td>{{ (int) ($product->qty ?? 0) }}</td>
                                        <td class="text-end">৳{{ number_format($lineTotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end align-items-center p-3 pt-0">
                        <div style="min-width: 260px;">
                            <div class="d-flex justify-content-between">
                                <span class="text-heading">Subtotal:</span>
                                <span class="fw-semibold">৳{{ number_format($subtotal) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-heading">Discount:</span>
                                <span class="fw-semibold">৳{{ number_format($discount) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-heading">Shipping:</span>
                                <span class="fw-semibold">৳{{ number_format($shipping) }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                <span class="text-heading fw-semibold">Total:</span>
                                <span class="fw-semibold">৳{{ number_format($grandTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Order</h5>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.order_change')}}" method="POST" class="row g-3" name="editForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="{{$data->id}}">

                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label">Customer name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{$data->shipping?$data->shipping->name:''}}" placeholder="Name">
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Customer phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone" value="{{$data->shipping?$data->shipping->phone:''}}" placeholder="Phone Number">
                            @error('phone')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Customer address</label>
                            <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{$data->shipping?$data->shipping->address:''}}</textarea>
                            @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="area" class="form-label">Delivery area</label>
                            <select id="cart_add" class="form-select @error('product_id') is-invalid @enderror" value="{{ old('product_id') }}">
                                @foreach($shippingcharge as $value)
                                    <option @if($data->shipping?$data->shipping->area:'' == $value->name) selected @endif value="{{$value->id}}">{{$value->name}}</option>
                                @endforeach
                            </select>
                            @error('area')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="order_status" class="form-label">Order status</label>
                            <select class="form-select" name="status">
                                <option value="">Select..</option>
                                @foreach($orderstatus as $value)
                                    <option value="{{$value->id}}" @if($data->order_status==$value->id) selected @endif>{{$value->name}}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                            <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Customer details</h5>
                    <div class="d-flex justify-content-start align-items-center mb-3">
                        <div class="avatar me-3">
                            @php
                                $custFallback = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                                $custFallback = preg_replace('~^public/~', '', $custFallback);
                                $custFallback = ltrim($custFallback, '/');
                            @endphp
                            <img src="{{ asset($custFallback) }}" alt="" class="rounded-circle" style="height: 40px; width: 40px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/empty.png') }}'" />
                        </div>
                        <div class="d-flex flex-column">
                            <h6 class="mb-0">{{ $data->shipping ? $data->shipping->name : '' }}</h6>
                            <span class="text-muted">Customer ID: #{{ $data->customer ? $data->customer->id : '' }}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-start align-items-center mb-3">
                        <span class="avatar rounded-circle bg-soft-success text-success me-3 d-flex align-items-center justify-content-center">
                            <i class="icon-base ri ri-shopping-cart-line icon-24px"></i>
                        </span>
                        <h6 class="text-nowrap mb-0">{{ $data->customer ? ($data->customer->orders_count ?? '') : '' }} Orders</h6>
                    </div>
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1">Contact info</h6>
                    </div>
                    <p class="mb-1">Email: {{ $data->customer ? ($data->customer->email ?? '') : '' }}</p>
                    <p class="mb-0">Mobile: {{ $data->shipping ? $data->shipping->phone : '' }}</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-1">Courier history</h5>
                    @if(!empty($hasCourierHistory) && $hasCourierHistory)
                        <span class="badge bg-soft-success text-success">Found</span>
                    @else
                        <span class="badge bg-soft-secondary text-secondary">None</span>
                    @endif
                </div>
                <div class="card-body">
                    @if(!empty($hasCourierHistory) && $hasCourierHistory)
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($previousCourierShipments ?? []) as $ship)
                                        <tr>
                                            <td>{{ optional($ship->order)->invoice_id }}</td>
                                            <td>{{ $ship->provider }}</td>
                                            <td>{{ $ship->current_status }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted">No previous courier shipments for this customer.</div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-1">Shipping address</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        {{ $data->shipping ? $data->shipping->address : '' }}
                        <br>
                        {{ $data->shipping ? $data->shipping->area : '' }}
                    </p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between pb-0">
                    <h5 class="card-title mb-1">Payment</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">Method: {{ $data->payment ? ($data->payment->payment_method ?? '') : '' }}</p>
                    <p class="mb-0">Status: {{ $data->payment ? ($data->payment->payment_status ?? '') : '' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
