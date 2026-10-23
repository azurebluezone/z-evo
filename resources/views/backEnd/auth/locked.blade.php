<!DOCTYPE html>
<html lang="en" class="authentication-bg">
<head>
    <meta charset="utf-8" />
    <title>Locked | {{ config('app.name', 'Z Evo') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="icon" type="image/x-icon" href="{{ asset('admin/assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com/" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />

    <style>
        body.authentication-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: var(--bs-body-bg, #f8f9fa);
        }
        .auth-card { max-width: 420px; margin-inline: auto; }
    </style>
</head>

<body class="authentication-bg">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4">
                <div class="card auth-card rounded-0">
                    <div class="card-body p-4">

                        <div class="text-center mb-4">
                            <h4 class="mb-1">Screen Locked</h4>
                            <p class="text-muted mb-0">Enter your password to unlock the admin panel.</p>
                        </div>

                        <form method="POST" action="{{ route('unlocked') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           value="{{ old('password') }}" autocomplete="current-password"
                                           autofocus placeholder="Enter your password">
                                    <button type="button" class="input-group-text" id="toggle-password" aria-label="Show password">
                                        <i class="icon-base ri ri-eye-line"></i>
                                    </button>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="checkbox-signin" value="1" name="remember" checked>
                                    <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                </div>
                            </div>

                            <div class="text-center d-grid">
                                <x-admin.button variant="primary" type="submit">
                                    <i class="icon-base ri ri-lock-unlock-line me-1"></i>Unlock
                                </x-admin.button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script>
        // The eye button toggles the password field's type. The old view used
        // the previous theme's .password-eye span, which needs that theme's
        // app.min.js - a file this admin does not ship.
        (function () {
            var toggle = document.getElementById('toggle-password');
            var input = document.getElementById('password');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.innerHTML = '<i class="icon-base ri ' + (show ? 'ri-eye-off-line' : 'ri-eye-line') + '"></i>';
                input.focus();
            });
        })();
    </script>
</body>
</html>
