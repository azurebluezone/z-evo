@extends('frontend.layouts.customer.dashboard-layout')

@section('title', 'Change Password')
@section('dashboard-title', 'Change Password')

@section('dashboard-content')
    <div class="classic-account-page">
        <div class="classic-form-note">
            <i class="la la-info-circle"></i>
            <p class="mb-0">
                For the security of your account it is important to update your password periodically.
                Regular updates help keep your account safe from unauthorized access.
            </p>
        </div>

        <div class="card custom--card">
            <div class="card-body">
                <form action="{{ route('customer.password_update') }}" method="POST" class="classic-form">
                    @csrf

                    <div class="form-group">
                        <label for="old_password">Current Password *</label>
                        <input type="password" id="old_password" name="old_password"
                               class="form--control @error('old_password') is-invalid @enderror"
                               value="{{ old('old_password') }}" autocomplete="current-password" required>
                        @error('old_password')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password"
                               class="form--control @error('new_password') is-invalid @enderror"
                               autocomplete="new-password" required>
                        @error('new_password')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form--control @error('confirm_password') is-invalid @enderror"
                               autocomplete="new-password" required>
                        @error('confirm_password')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn--base h-45 w-100">Update</button>
                </form>
            </div>
        </div>
    </div>
@endsection
