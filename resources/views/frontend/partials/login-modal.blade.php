<div class="modal custom--modal fade" id="loginModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>

                <h5 class="modal-title mt-3"></h5>
                <div class="login-wrapper">
                    <form method="POST" action="{{ route('customer.signin') }}" class="sign-in-form">
                        @csrf

                        <div class="form-group">
                            <label class="form--label" for="login-username">Phone</label>
                            <input type="text" class="form--control" name="phone" id="login-username" value="">
                        </div>

                        <div class="form-group">
                            <label class="form--label" for="login-password">Password</label>
                            <input type="password" class="form--control" name="password" id="login-password">
                        </div>

                        <div class="form-group">
                            <div class="d-flex gap-1 flex-wrap justify-content-between">
                                <div class="form-check form--check d-flex gap-1 align-items-center mb-0">
                                    <label class="form-check-label m-0 lh-1 d-flex align-items-center">
                                        <input class="form-check-input me-1" type="checkbox" name="remember" id="remember-me">Remember Me
                                    </label>
                                </div>

                                <a href="{{ route('customer.forgot.password') }}" class="t-link d-block text-end text--base heading-clr sm-text fw-md">
                                    Forgot Password?
                                </a>
                            </div>
                        </div>

                        <button type="submit" class="btn btn--base w-100 h-45">Login</button>

                        <p class="create-accounts mb-0 mt-2">
                            <span class="text-dark">
                                Don't have an account?
                                <a href="{{ route('customer.register') }}" class="text--base">Create An Account</a>
                            </span>
                        </p>
                    </form>

                    <div class="auth-divide col-12">
                        <span class="auth-divide-text">Or</span>
                    </div>

                    <ul class="social-auth-list col-12">
                        <li class="social-auth-item">
                            <a href="{{ url('/user/social-login/google') }}" class="social-auth-link">
                                <span class="icon">
                                    <img src="{{ asset('assets/templates/basic/images/google.svg') }}" alt="Google">
                                </span>
                                <span class="text">Login Google Account</span>
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>
