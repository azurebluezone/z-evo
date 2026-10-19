<aside id="layout-menu" class="layout-menu menu-vertical menu">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <span class="text-primary">
                    <svg width="32" height="18" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z" fill="currentColor" />
                        <path d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z" fill="url(#paint0_linear_2989_100980)" fill-opacity="0.4" />
                        <path d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z" fill="currentColor" />
                        <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="currentColor" />
                        <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="url(#paint1_linear_2989_100980)" fill-opacity="0.4" />
                        <path d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z" fill="currentColor" />
                        <defs>
                            <linearGradient id="paint0_linear_2989_100980" x1="5.36642" y1="0.849138" x2="10.532" y2="24.104" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-opacity="1" />
                                <stop offset="1" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="paint1_linear_2989_100980" x1="5.19475" y1="0.849139" x2="10.3357" y2="24.1155" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-opacity="1" />
                                <stop offset="1" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                    </svg>
                </span>
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">{{ $generalsetting->name ?? config('app.name', 'Adora Style') }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z" fill-opacity="0.9" />
                <path d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z" fill-opacity="0.4" />
            </svg>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @php
            $comingSoon = function (string $label) {
                return '<span class="text-muted">' . e($label) . '</span><span class="badge badge-center text-bg-secondary rounded-pill ms-auto">Soon</span>';
            };
        @endphp

        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-home-4-line"></i>
                <div>Dashboard</div>
            </a>
        </li>

        <!-- Customers -->
        <li class="menu-item {{ request()->is('admin/customers*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-user-3-line"></i>
                <div>Customers</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('customers.index') ? 'active' : '' }}">
                    <a href="{{ route('customers.index') }}" class="menu-link">
                        <div>All Customers</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Wishlists') !!}</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Order Management -->
        <li class="menu-item {{ (request()->is('admin/order*') || request()->is('admin/invoices*')) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-shopping-cart-line"></i>
                <div>Order</div>
                <div class="badge badge-center text-bg-danger rounded-pill ms-auto">{{ $neworder ?? 0 }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.invoices.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.invoices.index') }}" class="menu-link">
                        <div>Invoice List</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.orders') && request()->get('slug') == 'all' ? 'active' : '' }}">
                    <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="menu-link">
                        <div>All Orders</div>
                    </a>
                </li>
                @foreach($orderstatus ?? [] as $status)
                    <li class="menu-item {{ request()->routeIs('admin.orders') && request()->get('slug') == $status->slug ? 'active' : '' }}">
                        <a href="{{ route('admin.orders', ['slug' => $status->slug]) }}" class="menu-link">
                            <div>{{ $status->name }}</div>
                        </a>
                    </li>
                @endforeach
                <li class="menu-item {{ request()->routeIs('admin.returns.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.returns.index') }}" class="menu-link">
                        <div>Returns</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.refunds.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.refunds.index') }}" class="menu-link">
                        <div>Refunds</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Products -->
        <li class="menu-item {{ request()->is('admin/products*') || request()->is('admin/categories*') || request()->is('admin/subcategories*') || request()->is('admin/childcategories*') || request()->is('admin/brands*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-database-line"></i>
                <div>Products</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('products.create') ? 'active' : '' }}">
                    <a href="{{ route('products.create') }}" class="menu-link">
                        <div>Create Product</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.inventory.index') }}" class="menu-link">
                        <div>Inventory / Stock</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('products.index') ? 'active' : '' }}">
                    <a href="{{ route('products.index') }}" class="menu-link">
                        <div>All Products</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('categories.index') ? 'active' : '' }}">
                    <a href="{{ route('categories.index') }}" class="menu-link">
                        <div>Main Category</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('subcategories.index') ? 'active' : '' }}">
                    <a href="{{ route('subcategories.index') }}" class="menu-link">
                        <div>Sub Category</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('childcategories.index') ? 'active' : '' }}">
                    <a href="{{ route('childcategories.index') }}" class="menu-link">
                        <div>Child Category</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('brands.index') ? 'active' : '' }}">
                    <a href="{{ route('brands.index') }}" class="menu-link">
                        <div>Brands</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.attributes.soon') ? 'active' : '' }}">
                    <a href="{{ route('admin.attributes.soon') }}" class="menu-link">
                        <div>Attributes</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Flash Sales -->
        <li class="menu-item {{ request()->routeIs('flash-sales.*') ? 'active' : '' }}">
            <a href="{{ route('flash-sales.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-flashlight-line"></i>
                <div>Flash Sales</div>
            </a>
        </li>

        <!-- Product Recommendations -->
        <li class="menu-item {{ request()->routeIs('admin.recommendations.*') ? 'active' : '' }}">
            <a href="{{ route('admin.recommendations.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-thumb-up-line"></i>
                <div>Recommendation</div>
            </a>
        </li>

        <!-- Landing Pages -->
        <li class="menu-item {{ request()->routeIs('landing-pages.*') || request()->routeIs('campaign.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-pages-line"></i>
                <div>Landing Page</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('landing-pages.index') ? 'active' : '' }}">
                    <a href="{{ route('landing-pages.index') }}" class="menu-link">
                        <div>Landing Pages</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('campaign.create') ? 'active' : '' }}">
                    <a href="{{ route('campaign.create') }}" class="menu-link">
                        <div>Create Campaign</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('campaign.index') ? 'active' : '' }}">
                    <a href="{{ route('campaign.index') }}" class="menu-link">
                        <div>Campaign</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Marketing -->
        <li class="menu-item {{ request()->routeIs('coupons.*') ? 'active' : '' }}">
            <a href="{{ route('coupons.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-megaphone-line"></i>
                <div>Coupon</div>
            </a>
        </li>

        <!-- Banners -->
        <li class="menu-item {{ request()->is('admin/banner*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-image-line"></i>
                <div>Banners</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('banners.index') ? 'active' : '' }}">
                    <a href="{{ route('banners.index') }}" class="menu-link">
                        <div>Home Slider</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('promo_banners.index') ? 'active' : '' }}">
                    <a href="{{ route('promo_banners.index') }}" class="menu-link">
                        <div>Slider Bottom Banners</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Payment -->
        <li class="menu-item {{ request()->is('admin/payment*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-bank-card-line"></i>
                <div>Payment</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('paymentgeteway.manage') ? 'active' : '' }}">
                    <a href="{{ route('paymentgeteway.manage') }}" class="menu-link">
                        <div>Settings</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Methods') !!}</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Courier -->
        <li class="menu-item {{ request()->is('admin/courier*') || request()->is('admin/courierapi*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-truck-line"></i>
                <div>Courier</div>
            </a>
            <ul class="menu-sub">
                {{-- Overview first: it is the screen that answers "is my
                     courier working?", which is the question this menu is for. --}}
                <li class="menu-item {{ request()->routeIs('admin.courier.index') || request()->routeIs('admin.courier.edit') ? 'active' : '' }}">
                    <a href="{{ route('admin.courier.index') }}" class="menu-link">
                        <div>Couriers</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.courier.shipments') || request()->routeIs('admin.courier.shipment') ? 'active' : '' }}">
                    <a href="{{ route('admin.courier.shipments') }}" class="menu-link">
                        <div>Shipments</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('courierapi.manage') ? 'active' : '' }}">
                    <a href="{{ route('courierapi.manage') }}" class="menu-link">
                        <div>Settings</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.courier.fraud-checker') ? 'active' : '' }}">
                    <a href="{{ route('admin.courier.fraud-checker') }}" class="menu-link">
                        <div>Fraud Checker</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.courier.settings') ? 'active' : '' }}">
                    <a href="{{ route('admin.courier.settings') }}" class="menu-link">
                        <div>API Settings</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Status Fast Courier') !!}</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Shipping -->
        <li class="menu-item {{ request()->is('admin/shipping*') || request()->is('admin/shippingcharges*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-send-plane-line"></i>
                <div>Shipping</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('shippingcharges.index') ? 'active' : '' }}">
                    <a href="{{ route('shippingcharges.index') }}" class="menu-link">
                        <div>Settings</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.shipping.verification') ? 'active' : '' }}">
                    <a href="{{ route('admin.shipping.verification') }}" class="menu-link">
                        <div>Verification / Weight Checker</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Visitors -->
        <li class="menu-item {{ request()->routeIs('admin.visitors.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-user-search-line"></i>
                <div>Visitors</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.visitors.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.visitors.index') }}" class="menu-link">
                        <div>Analytics</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.visitors.list') ? 'active' : '' }}">
                    <a href="{{ route('admin.visitors.list') }}" class="menu-link">
                        <div>Visitor List</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.visitors.blocked') ? 'active' : '' }}">
                    <a href="{{ route('admin.visitors.blocked') }}" class="menu-link">
                        <div>Blocked Visitors</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Real-time Analytics & Attribution -->
        <li class="menu-item {{ request()->routeIs('admin.analytics.*') ? 'active open' : '' }}">
            <a href="{{ route('admin.analytics.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-dashboard-3-line"></i>
                <div>Real-time Analytics</div>
            </a>
        </li>

        <!-- Social Event Manager -->
        <li class="menu-item {{ request()->routeIs('admin.social-events.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-share-line"></i>
                <div>Social Events</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.social-events.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.social-events.index') }}" class="menu-link">
                        <div>Settings</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.analytics.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.analytics.index') }}" class="menu-link">
                        <div>Attribution Dashboard</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.social-events.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.social-events.dashboard') }}" class="menu-link">
                        <div>Dashboard</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.social-events.facebook') ? 'active' : '' }}">
                    <a href="{{ route('admin.social-events.facebook') }}" class="menu-link">
                        <div>Facebook</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.social-events.google') ? 'active' : '' }}">
                    <a href="{{ route('admin.social-events.google') }}" class="menu-link">
                        <div>Google (GTM)</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.social-events.tiktok') ? 'active' : '' }}">
                    <a href="{{ route('admin.social-events.tiktok') }}" class="menu-link">
                        <div>TikTok</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- User Submissions -->
        <li class="menu-item {{ request()->routeIs('admin.user-submissions.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-mail-settings-line"></i>
                <div>User Submissions</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.user-submissions.newsletter') ? 'active' : '' }}">
                    <a href="{{ route('admin.user-submissions.newsletter') }}" class="menu-link">
                        <div>Newsletter Subscribers</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.user-submissions.contacts') ? 'active' : '' }}">
                    <a href="{{ route('admin.user-submissions.contacts') }}" class="menu-link">
                        <div>Contact Messages</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.user-submissions.service-requests.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.user-submissions.service-requests.index') }}" class="menu-link">
                        <div>Service Requests</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Website Setup -->
        <li class="menu-item {{ request()->is('admin/settings*') || request()->routeIs('admin.website-setup.*') || request()->routeIs('admin.frontend-theme.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-settings-4-line"></i>
                <div>Website Setup</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.website-setup.header') ? 'active' : '' }}">
                    <a href="{{ route('admin.website-setup.header') }}" class="menu-link">
                        <div>Header</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.website-setup.footer') ? 'active' : '' }}">
                    <a href="{{ route('admin.website-setup.footer') }}" class="menu-link">
                        <div>Footer</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.website-setup.appearance') ? 'active' : '' }}">
                    <a href="{{ route('admin.website-setup.appearance') }}" class="menu-link">
                        <div>Appearance</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.website-setup.seo') ? 'active' : '' }}">
                    <a href="{{ route('admin.website-setup.seo') }}" class="menu-link">
                        <div>Seo</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.homepage.settings.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.homepage.settings.edit') }}" class="menu-link">
                        <div>Home Settings</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.frontend-theme.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.frontend-theme.edit') }}" class="menu-link">
                        <div>Front-end Template</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Appearances -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                <i class="menu-icon icon-base ri ri-palette-line"></i>
                <div>{!! $comingSoon('Backup') !!}</div>
            </a>
        </li>

        <!-- Role & Permission -->
        <li class="menu-item {{ request()->is('admin/roles*') || request()->is('admin/permissions*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-shield-user-line"></i>
                <div>Role &amp; Permission</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('roles.index') ? 'active' : '' }}">
                    <a href="{{ route('roles.index') }}" class="menu-link">
                        <div>Roles</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('permissions.index') ? 'active' : '' }}">
                    <a href="{{ route('permissions.index') }}" class="menu-link">
                        <div>Permissions</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- User Management -->
        <li class="menu-item {{ request()->is('admin/users*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-user-3-line"></i>
                <div>User Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}" class="menu-link">
                        <div>All Users</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Reviews -->
        @php
            $pending_reviews = \App\Models\Review::where('status', 'pending')->count();
        @endphp
        <li class="menu-item {{ request()->is('admin/reviews*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-star-line"></i>
                <div>Reviews</div>
                @if($pending_reviews > 0)
                    <div class="badge badge-center text-bg-warning rounded-pill ms-auto">{{ $pending_reviews }}</div>
                @endif
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('reviews.index') ? 'active' : '' }}">
                    <a href="{{ route('reviews.index') }}" class="menu-link">
                        <div>All Reviews</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('reviews.pending') ? 'active' : '' }}">
                    <a href="{{ route('reviews.pending') }}" class="menu-link">
                        <div>Pending Reviews ({{ $pending_reviews }})</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Approved Reviews') !!}</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Rejected Reviews') !!}</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Settings') !!}</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Reports & Analytics -->
        <li class="menu-item {{ request()->is('admin/reports*') || request()->is('admin/stock*') || request()->is('admin/order_report*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-pie-chart-line"></i>
                <div>Reports &amp; Analytics</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.order_report') ? 'active' : '' }}">
                    <a href="{{ route('admin.order_report') }}" class="menu-link">
                        <div>Sales Report</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.stock_report') ? 'active' : '' }}">
                    <a href="{{ route('admin.stock_report') }}" class="menu-link">
                        <div>Product Report</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                        <div>{!! $comingSoon('Customer Report') !!}</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Template Manager -->
        <li class="menu-item {{ request()->routeIs('admin.notification-templates.*') ? 'active' : '' }}">
            <a href="{{ route('admin.notification-templates.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-mail-open-line"></i>
                <div>Template Manager</div>
            </a>
        </li>

        <!-- Communications -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link disabled" tabindex="-1" aria-disabled="true">
                <i class="menu-icon icon-base ri ri-discuss-line"></i>
                <div>{!! $comingSoon('Communications') !!}</div>
            </a>
        </li>

        <!-- UI Demo -->
        <li class="menu-item {{ request()->routeIs('admin.ui-demo') ? 'active' : '' }}">
            <a href="{{ route('admin.ui-demo') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-layout-2-line"></i>
                <div>UI Demo</div>
            </a>
        </li>

        <!-- AI Prompt Generator -->
        <li class="menu-item {{ request()->routeIs('admin.prompt') ? 'active' : '' }}">
            <a href="{{ route('admin.prompt') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-palette-line"></i>
                <div>AI Prompt Generator</div>
            </a>
        </li>
    </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="ri ri-menu-line icon-base"></i>
        <i class="ri ri-arrow-right-s-line icon-base"></i>
    </a>
</div>
