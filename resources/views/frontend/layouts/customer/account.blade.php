@extends('frontend.layouts.customer.dashboard-layout')

@section('title', 'My Account')
@section('dashboard-title', 'My Account')

@section('dashboard-content')
    @php
        $customer = \App\Models\Customer::with('cust_area')->find(Auth::guard('customer')->user()->id);

        // Stored value is 'public/uploads/...'; asset() turned that into
        // '/public/public/...' and a broken <img>. MediaPath strips the
        // leading 'public/' and answers with the bundled placeholder when the
        // file is missing.
        $avatarUrl = \App\Support\MediaPath::url($customer->image ?? null, 'customer');
    @endphp

    <div class="classic-account-page">
        <div class="classic-table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Name</td>
                        <td>{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>{{ $customer->phone }}</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>{{ $customer->email }}</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>{{ $customer->address }}</td>
                    </tr>
                    <tr>
                        <td>District</td>
                        <td>{{ $customer->district }}</td>
                    </tr>
                    <tr>
                        <td>Area</td>
                        <td>{{ $customer->cust_area ? $customer->cust_area->area_name : '' }}</td>
                    </tr>
                    <tr>
                        <td>Image</td>
                        <td><img src="{{ $avatarUrl }}" alt="{{ $customer->name }}" class="classic-avatar classic-avatar--sm"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
