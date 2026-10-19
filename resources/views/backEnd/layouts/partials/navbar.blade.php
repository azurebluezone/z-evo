<!-- Navbar -->
<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base ri ri-menu-line icon-22px"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                    <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
                </a>
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
            <!-- Language -->
            <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base ri ri-translate-2 icon-md"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-language="en" data-text-direction="ltr">
                            <span>English</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ Language -->

            <!-- Style Switcher -->
            <li class="nav-item dropdown me-sm-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill" id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base ri ri-sun-line icon-22px theme-icon-active"></i>
                    <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                    <li>
                        <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="false">
                            <span><i class="icon-base ri ri-sun-line icon-22px me-3" data-icon="sun-line"></i>Light</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="true">
                            <span><i class="icon-base ri ri-moon-clear-line icon-22px me-3" data-icon="moon-clear-line"></i>Dark</span>
                        </button>
                    </li>
                </ul>
            </li>
            <!-- / Style Switcher-->

            <!-- Notification -->
            @php
            $notifications = auth()->user()->notifications->take(5) ?? collect();
            $unreadCount = auth()->user()->unreadNotifications->count() ?? 0;
            @endphp
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-4 me-xl-1">
                <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="icon-base ri ri-notification-2-line icon-22px"></i>
                    @if($unreadCount > 0)
                    <span class="position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom py-50">
                        <div class="dropdown-header d-flex align-items-center py-2">
                            <h6 class="mb-0 me-auto">Notification</h6>
                            @if($unreadCount > 0)
                            <div class="badge rounded-pill bg-label-primary">{{ $unreadCount }} New</div>
                            @endif
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        <ul class="list-group list-group-flush">
                            @forelse($notifications as $notification)
                            <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? '' : 'list-group-item-warning' }}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar">
                                            <img src="{{ asset('admin/assets/img/avatars/1.png') }}" alt="avatar" class="rounded-circle" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ $notification->data['title'] ?? 'Notification' }}</h6>
                                        <small class="mb-1 d-block text-body">{{ $notification->data['message'] ?? '' }}</small>
                                        <small class="text-body-secondary">{{ $notification->created_at ? $notification->created_at->diffForHumans() : '' }}</small>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="list-group-item text-center text-muted py-4">
                                <i class="icon-base ri ri-notification-off-line icon-22px mb-1 d-block"></i>
                                <small>No notifications yet</small>
                            </li>
                            @endforelse
                        </ul>
                    </li>
                </ul>
            </li>
            <!--/ Notification -->

            <!-- User -->
            @php
            $currentUser = auth()->user();
            $userImage = ($currentUser->image && file_exists(public_path($currentUser->image))) ? asset($currentUser->image) : asset('uploads/users/default-avatar.png');
            @endphp
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ $userImage }}" alt="avatar" class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
                    <li>
                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar avatar-online">
                                        <img src="{{ $userImage }}" alt="alt" class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 small">{{ $currentUser->name ?? 'Admin' }}</h6>
                                    <small class="text-body-secondary">{{ $currentUser->email ?? '' }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('change_password') }}">
                            <i class="icon-base ri ri-settings-4-line icon-22px me-3"></i>
                            <span class="align-middle">Change Password</span>
                        </a>
                    </li>
                    <li>
                        {{--
                            Locking is a state change, so it is a POST form rather
                            than a link. As an <a href> any page that linked here
                            - an email, a prefetch - could lock the session of
                            whoever happened to be signed in.
                        --}}
                        <form method="POST" action="{{ route('locked') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="icon-base ri ri-lock-line icon-22px me-3"></i>
                                <span class="align-middle">Lock Screen</span>
                            </button>
                        </form>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <div class="d-grid px-4 pt-2 pb-1">
                            <form action="{{ route('admin.logout') }}" method="POST" id="navbar-logout-form" style="display:none;">@csrf</form>
                            <a class="btn btn-sm btn-danger d-flex" href="javascript:void(0);"
                                onclick="event.preventDefault(); document.getElementById('navbar-logout-form').submit();">
                                <small class="align-middle">Logout</small>
                                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
<!-- / Navbar -->