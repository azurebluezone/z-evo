@extends('backEnd.layouts.app')
@section('title','Change Password')
@section('content')
<div class="row">
    <div class="col-12">
        {{-- TOP HEADER CARD --}}
        <div class="card rounded-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Change Password</h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri-arrow-left-line me-1"></i> Dashboard
                    </a>
                    <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="changePasswordForm">
                        <i class="ri-lock-password-line me-1"></i> Change Password
                    </x-admin.button>
                </div>
            </div>
        </div>

        {{-- FORM CARD --}}
        <div class="app-ecommerce">
            <div class="card rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                    <h5 class="mb-0 text-uppercase fw-bold">Update Password</h5>
                </div>
                <div class="card-body mt-4">
                    <form action="{{ route('new_password') }}" method="POST" class="row g-4" data-parsley-validate="" id="changePasswordForm">
                        @csrf
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="old_password" class="form-label fw-semibold">Old Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control rounded-0 @error('old_password') is-invalid @enderror" name="old_password" id="old_password" placeholder="Enter old password" required="">
                                    <x-admin.button variant="outline-secondary" class="toggle-password rounded-0" type="button" data-target="#old_password">
                                        <i class="ri-eye-off-line"></i>
                                    </x-admin.button>
                                </div>
                                @error('old_password')
                                <span class="invalid-feedback d-block fw-semibold" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="new_password" class="form-label fw-semibold">New Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control rounded-0 @error('new_password') is-invalid @enderror" name="new_password" id="new_password" placeholder="Enter new password" required="">
                                    <x-admin.button variant="outline-secondary" class="toggle-password rounded-0" type="button" data-target="#new_password">
                                        <i class="ri-eye-off-line"></i>
                                    </x-admin.button>
                                </div>
                                @error('new_password')
                                <span class="invalid-feedback d-block fw-semibold" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control rounded-0 @error('confirm_password') is-invalid @enderror" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required="">
                                    <x-admin.button variant="outline-secondary" class="toggle-password rounded-0" type="button" data-target="#confirm_password">
                                        <i class="ri-eye-off-line"></i>
                                    </x-admin.button>
                                </div>
                                @error('confirm_password')
                                <span class="invalid-feedback d-block fw-semibold" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = document.querySelector(this.getAttribute('data-target'));
            if (target.type === 'password') {
                target.type = 'text';
                this.querySelector('i').className = 'ri-eye-line';
            } else {
                target.type = 'password';
                this.querySelector('i').className = 'ri-eye-off-line';
            }
        });
    });
</script>
@endsection