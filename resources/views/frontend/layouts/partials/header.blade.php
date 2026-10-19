{{-- Header Component --}}
<header class="header-area bg-white">
    {{-- Header Top --}}
    <div class="header-top d-none d-md-block bg-white">
        <div class="container">
            <div class="header-top-wrap d-flex flex-wrap justify-content-between align-items-center">
                <div class="nav--links">
                    <ul>
                        <li><a href="{{ route('track.order') }}">Track Order</a></li>
                    </ul>
                </div>
                <div class="predefined-widgets d-flex gap-2 align-items-center py-2">
                    {{-- Language Dropdown --}}
                    <div class="d-none d-lg-block">
                        <div class="dropdown dropdown--lang">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img class="dropdown-flag lazyload" src="{{ asset('assets/images/language/67076f5911ee81728540505.png') }}" alt="language" width="24" height="16" decoding="async">
                                <span>{{ app()->getLocale() == 'en' ? 'English' : (app()->getLocale() == 'bn' ? 'Bangla' : 'Hindi') }}</span>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('language.change', 'bn') }}">
                                    <img class="dropdown-flag lazyload" src="{{ asset('assets/images/language/67076f5911ee81728540505.png') }}" alt="Bangla" width="24" height="16" decoding="async">
                                    <span>Bangla</span>
                                </a>
                                <a class="dropdown-item" href="{{ route('language.change', 'hi') }}">
                                    <img class="dropdown-flag lazyload" src="{{ asset('assets/images/language/67076f5911ee81728540505.png') }}" alt="Hindi" width="24" height="16" decoding="async">
                                    <span>Hindi</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- User Account --}}
                    <div class="position-relative user-dropdown-wrapper">
                        @if(auth()->check())
                            <div class="dropdown">
                                <button class="user-account button dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <span class="icon"><i class="las la-user"></i></span>
                                    <span>{{ auth()->user()->name }}</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('customer.account') }}">Dashboard</a>
                                    <a class="dropdown-item" href="{{ route('customer.orders') }}">My Orders</a>
                                    <a class="dropdown-item" href="{{ route('customer.profile_edit') }}">Profile</a>
                                    <a class="dropdown-item" href="{{ route('wishlist') }}">Wishlist</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </div>
                        @else
                            <button type="button" class="user-account login-btn button" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <span class="icon"><i class="las la-user"></i></span>
                                <span>My Account</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Header Middle --}}
    <div class="header-middle">
        <div class="container">
            <div class="d-flex justify-content-between header-wrapper middle-menu-lsw">
                <div class="logo">
                    <a href="{{ route('home') }}" class="logo-container">
                        @php
                            $darkLogoPath = $generalsetting->dark_logo ?? 'assets/images/logo_icon/logo_dark.png';
                            $darkLogoPath = preg_replace('~^public/~', '', (string) $darkLogoPath);
                        @endphp
                        <img src="{{ asset($darkLogoPath) }}" alt="logo" width="150" height="40" decoding="async">
                    </a>
                </div>
                <div class="header-search-wrapper">
                    <form action="{{ route('products.index') }}" method="GET" class="header-search-form me-auto">
                        <div class="header-form-group">
                            <button type="button" class="search-close-btn"><i class="las la-arrow-up"></i></button>
                            <input type="text" class="form--control" name="search" value="{{ request('search') }}" placeholder="I am shopping for...">
                        </div>
                        <button class="icon" type="submit"><i class="las la-search"></i></button>
                    </form>
                </div>
                <button type="button" class="header-search-btn"><i class="las la-search"></i></button>

                <ul class="list list--row option-list-wrapper justify-content-center justify-content-md-end option-list d-flex align-items-center">
                    <li class="d-none d-lg-block">
                        <a href="{{ route('compare.products') }}" class="ecommerce">
                            <span class="ecommerce__icon">
                                <i class="las la-exchange-alt"></i>
                                <span class="ecommerce__is compare-count d-none">{{ session()->get('compare_count', 0) }}</span>
                            </span>
                            <span class="ecommerce__text">Compare</span>
                        </a>
                    </li>

                    <li class="d-none d-lg-block">
                        <a href="{{ route('wishlist') }}" class="ecommerce wish-button">
                            <span class="ecommerce__icon">
                                <i class="las la-heart"></i>
                                <span class="ecommerce__is wishlist-count d-none">{{ session()->get('wishlist_count', 0) }}</span>
                            </span>
                            <span class="ecommerce__text">Wishlist</span>
                        </a>
                    </li>

                    @if(Auth::guard('customer')->check())
                        @php
                            $customerUnread = (int) Auth::guard('customer')->user()->unreadNotifications()->count();
                        @endphp
                        <li class="d-none d-lg-block">
                            <a href="{{ route('customer.notifications.index') }}" class="ecommerce">
                                <span class="ecommerce__icon">
                                    <i class="las la-bell"></i>
                                    @if($customerUnread > 0)
                                        <span class="ecommerce__is">{{ $customerUnread }}</span>
                                    @endif
                                </span>
                                <span class="ecommerce__text">Notifications</span>
                            </a>
                        </li>
                    @endif

                    <li>
                        <a href="{{ route('cart.index') }}" class="ecommerce cart-button">
                            <span class="ecommerce__icon">
                                <i class="las la-shopping-cart"></i>
                                <span class="ecommerce__is cart-count">{{ session()->get('cart_count', 0) }}</span>
                            </span>
                            <span class="ecommerce__text">Cart</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Header Bottom --}}
    <!-- <div class="header-bottom">
        <div class="container">
            <div class="row g-0">
                <div class="header-bottom-wrapper primary-menu-clw">
                    <button class="primary-menu-button d-lg-none">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div class="d-none d-lg-block view-category-wrapper">
                        <div class="view-category">
                            <div class="menu-category-btn">
                                <i class="las la-bars"></i> Categories
                            </div>
                        </div>

                        <div class="category-dropdown-menu">
                            <div class="left-site-category active">
                                <div class="category-dropdown">
                                    @include('frontend.partials.category-menu')
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Main Navigation --}}
                    <nav class="main-navigation d-none d-lg-block">
                        <ul class="main-menu">
                            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                            <li><a href="{{ url('/products') }}" class="{{ request()->is('products*') ? 'active' : '' }}">Products</a></li>
                            <li><a href="{{ url('/categories') }}">Categories</a></li>
                            <li><a href="{{ url('/brands') }}">Brands</a></li>
                            <li><a href="{{ route('offers.index') }}">Offers</a></li>
                            <li><a href="{{ route('about.us') }}">About Us</a></li>
                            <li><a href="{{ route('contact') }}">Contact</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div> -->

    <div class="header-bottom ">
        <div class="container">
            <div class="row g-0">
                <div class="header-bottom-wrapper primary-menu-clw">

                    <button class="primary-menu-button d-lg-none">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div class="d-none d-lg-block view-category-wrapper">
                        <div class="view-category">
                            <div class="menu-category-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" x="0" y="0" viewBox="0 0 24 24" style="enable-background:new 0 0 512 512" xml:space="preserve">
                                    <g>
                                        <path fill="#ffffff" fill-rule="evenodd"
                                            d="m20.272 3.261.021.022.424.424.022.021c.378.378.693.693.93.972.246.29.45.59.568.95a2.75 2.75 0 0 1 0 1.7c-.117.36-.322.66-.569.95-.236.279-.551.594-.93.972l-.02.021-.425.424-.021.022c-.378.378-.693.693-.972.93-.29.246-.59.45-.95.568a2.75 2.75 0 0 1-1.7 0c-.36-.117-.66-.322-.95-.569-.279-.236-.594-.552-.972-.93l-.021-.02-.424-.425-.022-.021a17.822 17.822 0 0 1-.93-.972c-.246-.29-.45-.59-.568-.95a2.75 2.75 0 0 1 0-1.7c.117-.36.322-.66.569-.95.236.279.551.594.93-.972l.02-.021.425-.424.021-.022c.378-.378.693-.693.972-.93.29-.246.59-.45.95-.568a2.75 2.75 0 0 1 1.7 0c.36.117.66.322.95.569.279.236.594.551.972.93z"/>
                                    </g>
                                </svg>
                                Categories
                            </div>
                        </div>

                        <div class="category-dropdown-menu">
                            <div class="left-site-category active">
                                <div class="category-dropdown">
                                    @include('frontend.partials.category-menu')
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="menu d-none d-lg-flex">
                        <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                        <li><a href="{{ url('/products') }}" class="{{ request()->is('products*') ? 'active' : '' }}">Products</a></li>
                        @php
                            $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
                            $headerLinks = [];
                            if (!empty($generalsetting->header_links)) {
                                $headerLinks = json_decode($generalsetting->header_links, true) ?: [];
                            }
                            if (empty($headerLinks)) {
                                $aboutUrl = $generalsetting->about_url ?? null;
                                $contactUrl = $generalsetting->contact_url ?? null;
                                $headerLinks = [
                                    ['title' => 'About Us', 'url' => $aboutUrl ?: route('about.us')],
                                    ['title' => 'Contact', 'url' => $contactUrl ?: route('contact')],
                                ];
                            }
                        @endphp
                        @foreach($headerLinks as $link)
                            @php
                                $linkTitle = trim((string) ($link['title'] ?? ''));
                                $linkUrl = trim((string) ($link['url'] ?? ''));
                            @endphp
                            @if($linkTitle !== '' && $linkUrl !== '')
                                <li><a href="{{ $linkUrl }}">{{ $linkTitle }}</a></li>
                            @endif
                        @endforeach
                        <li><a href="{{ url('/categories') }}">All Categories</a></li>
                    </ul>
                    
                    <div class="action-btn-inner d-flex">
                        <div class="cart-icon-design">
                            <button class="button cart-button flex-shrink-0">
                                <i class="las la-shopping-bag"></i>
                                <span class="flex-shrink-0">
                                    <span class="amount d-block">৳<span class="cartSubtotal">0.00</span></span>
                                    <span class="items d-block">(<span class="cartItemCount">0</span>) items</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
