@extends('backEnd.layouts.app')
@section('title','Customer Profile')

@section('content')
<!-- Customer Profile Card -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customer Profile</h5>
            </div>
            <div class="card-body text-center">
                @php
                    $imgPath = preg_replace('~^public/~', '', (string) ($profile->image ?? ''));
                    $imgPath = ltrim($imgPath, '/');
                    $fallbackImg = 'assets/images/empty.png';
                    $imgSrc = $imgPath ? asset($imgPath) : asset($fallbackImg);
                @endphp
                <img src="{{ $imgSrc }}" class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;" alt="profile-image" onerror="this.src='{{ asset($fallbackImg) }}'">

                <h5 class="mb-1">{{$profile->name}}</h5>
                <p class="text-muted mb-2">{{$profile->email}}</p>
                @if($profile->status == 'active')
                    <span class="badge bg-label-success mb-3">Active Customer</span>
                @else
                    <span class="badge bg-label-secondary mb-3">Inactive</span>
                @endif

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <a href="tel:{{$profile->phone}}" class="btn btn-success btn-sm"><i class="ri ri-phone-line me-1"></i>Call</a>
                    <a href="mailto:{{$profile->email}}" class="btn btn-info btn-sm"><i class="ri ri-mail-send-line me-1"></i>Email</a>
                </div>

                <div class="text-start mt-3 border-top pt-3">
                    <h6 class="mb-3">Contact Information</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Full Name:</span>
                        <span class="fw-medium">{{$profile->name}}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Mobile:</span>
                        <span class="fw-medium">{{$profile->phone}}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Email:</span>
                        <span class="fw-medium">{{$profile->email}}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Address:</span>
                        <span class="fw-medium text-end" style="max-width: 60%">{{$profile->address ?? 'N/A'}}</span>
                    </div>
                    @if($profile->district)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">District:</span>
                        <span class="fw-medium">{{$profile->district}}</span>
                    </div>
                    @endif
                    @if($profile->area)
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Upazila:</span>
                        <span class="fw-medium">{{$profile->area}}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{route('customers.index')}}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri ri-arrow-left-line me-1"></i> Back to List
                    </a>
                    <a href="{{route('customers.edit',$profile->id)}}" class="btn btn-outline-primary btn-sm">
                        <i class="ri ri-edit-line me-1"></i> Edit Profile
                    </a>
                    <form method="post" action="{{route('customers.adminlog')}}" target="_blank">
                        @csrf
                        <input type="hidden" value="{{$profile->id}}" name="hidden_id">
                        <x-admin.button variant="primary" size="sm" class="w-100" type="submit">
                            <i class="ri ri-login-box-line me-1"></i> Login As Customer
                        </x-admin.button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders & Stats Section -->
    <div class="col-lg-8">
        <!-- Order Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="avatar mx-auto mb-2">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="icon-base ri ri-shopping-bag-3-line icon-24px"></i>
                            </span>
                        </div>
                        <h4 class="mb-1">{{ $profile->orders->count() }}</h4>
                        <span class="text-muted">Total Orders</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="avatar mx-auto mb-2">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="icon-base ri ri-money-dollar-circle-line icon-24px"></i>
                            </span>
                        </div>
                        @php
                            $totalSpent = $profile->orders->sum('amount');
                        @endphp
                        <h4 class="mb-1">৳{{ number_format($totalSpent) }}</h4>
                        <span class="text-muted">Total Spent</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="avatar mx-auto mb-2">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="icon-base ri ri-calendar-line icon-24px"></i>
                            </span>
                        </div>
                        <h4 class="mb-1">{{ $profile->created_at->format('M Y') }}</h4>
                        <span class="text-muted">Member Since</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order History</h5>
                <span class="badge bg-label-primary">{{ $profile->orders->count() }} Orders</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Invoice</th>
                                <th>Shipping</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($profile->orders as $key=>$value)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>
                                    <a href="{{route('admin.order.invoice',['invoice_id'=>$value->invoice_id])}}" class="fw-medium">#{{$value->invoice_id}}</a>
                                </td>
                                <td>{{$value->shipping?$value->shipping->name:'N/A'}}</td>
                                <td>
                                    {{date('d-m-Y', strtotime($value->created_at))}}
                                    <small class="text-muted d-block">{{date('h:i a', strtotime($value->created_at))}}</small>
                                </td>
                                <td>৳{{$value->amount}}</td>
                                <td>
                                    @php
                                        $statusName = $value->status?$value->status->name:'';
                                        $bClass = 'bg-label-primary';
                                        if(stripos($statusName, 'complete') !== false || stripos($statusName, 'deliver') !== false) $bClass = 'bg-label-success';
                                        elseif(stripos($statusName, 'cancel') !== false || stripos($statusName, 'return') !== false) $bClass = 'bg-label-danger';
                                        elseif(stripos($statusName, 'process') !== false || stripos($statusName, 'pending') !== false) $bClass = 'bg-label-warning';
                                    @endphp
                                    <span class="badge {{$bClass}}">{{$statusName}}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ri ri-shopping-bag-line me-1"></i> No orders found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
