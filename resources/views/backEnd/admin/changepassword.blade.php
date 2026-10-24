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
                                    <x-admin.button variant="outline-secondary" class="toggle-password rounded-0" type="button"
                                        data-target="#old_password" data-label="old password"
                                        aria-controls="old_password" aria-label="Show old password" aria-pressed="false">
                                        <i aria-hidden="true" class="ri ri-eye-off-line"></i>
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
                                    <x-admin.button variant="outline-secondary" class="toggle-password rounded-0" type="button"
                                        data-target="#new_password" data-label="new password"
                                        aria-controls="new_password" aria-label="Show new password" aria-pressed="false">
                                        <i aria-hidden="true" class="ri ri-eye-off-line"></i>
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
                                    <x-admin.button variant="outline-secondary" class="toggle-password rounded-0" type="button"
                                        data-target="#confirm_password" data-label="confirm password"
                                        aria-controls="confirm_password" aria-label="Show confirm password" aria-pressed="false">
                                        <i aria-hidden="true" class="ri ri-eye-off-line"></i>
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
    /*
     * Password visibility toggle.
     *
     * The icon classes are the MASK-based Remix build, so the base `ri` class is
     * load-bearing: `className = 'ri-eye-line'` alone drops it and the glyph
     * collapses to 0x0 (the empty button this control used to be).
     *
     * aria-pressed + aria-label are updated too, because a screen reader cannot
     * see an eye icon change: the button must say what it is about to do.
     */
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        var target = document.querySelector(btn.getAttribute('data-target'));
        if (!target) return;

        var icon = btn.querySelector('i');
        var label = btn.getAttribute('data-label') || 'password';

        btn.addEventListener('click', function () {
            var reveal = target.type === 'password';

            target.type = reveal ? 'text' : 'password';
            icon.className = reveal ? 'ri ri-eye-line' : 'ri ri-eye-off-line';
            btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            btn.setAttribute('aria-label', (reveal ? 'Hide ' : 'Show ') + label);

            // Keep the caret where the user was typing.
            target.focus({ preventScroll: true });
        });
    });
</script>
@endsection