<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pages that must not render the shared stat header
    |--------------------------------------------------------------------------
    |
    | The dashboard already renders its own KPI band (revenue, orders,
    | visitors, customers). Printing the same numbers a second time on one
    | screen is the duplication this header exists to remove.
    |
    */

    'exclude' => [
        'dashboard',
        'admin.login',
        'admin.login.submit',

        /*
         * Website setup and homepage settings are pure configuration
         * screens. The shop-overview fallback (Products/Orders/Customers)
         * measured the wrong thing there and confused the admin, so these
         * pages render no stat band at all (reported 2026-10-28).
         */
        'admin.website-setup.index',
        'admin.website-setup.header',
        'admin.website-setup.footer',
        'admin.website-setup.appearance',
        'admin.website-setup.seo',
        'admin.homepage.settings.edit',

        /*
         * Same class of mistake found by the 2026-10-28 full-admin scan
         * (140 GET pages): these configuration/account screens were falling
         * back to the shop overview and printing "Total Products" on pages
         * that have nothing to do with products.
         */
        'settings.index',
        'settings.create',
        'admin.frontend-theme.edit',
        'facebook-tracking.index',
        'admin.tiktok-test.index',
        'admin.prompt',
        'admin.ui-demo',
        'change_password',

        /*
         * Screens that already render their OWN KPI band. The rule is one stat
         * band per page, never two: these pages measure something the shared
         * four cards cannot (month-over-month growth, stock value, running
         * flash sales), so their band wins and the shared one stays off.
         *
         * Do not add a page here merely to silence a duplicate it caused -
         * remove the duplicate band instead.
         */
        'visitors.index',
        'flash-sales.index',
        'admin.analytics.index',
        'admin.analytics.live',
        'admin.stock_report',
    ],

    /*
    |--------------------------------------------------------------------------
    | One definition per module: /admin/{segment}
    |--------------------------------------------------------------------------
    |
    | The four cards are the same TEMPLATE on every page, but the numbers are
    | about the page the admin is looking at:
    |
    |   card 1  Total <label>
    |   card 2  Active <label>          (status_column present)
    |           Added today             (no status column)
    |   card 3  Inactive <label>        (status_column present)
    |           Added this month        (no status column)
    |   card 4  Showing this page       (rows the grid is displaying now)
    |
    | A module may override cards 2 and 3 with `metrics` when "active" is not
    | the honest word (orders are pending/delivered, not active/inactive).
    |
    | active_values exist because `status` is a varchar in this schema and
    | older rows say 'active' while newer forms write 1.
    |
    */

    'modules' => [
        'coupon' => ['label' => 'Coupons', 'icon' => 'ri-coupon-3-line', 'table' => 'coupons'],
        'courier' => ['label' => 'Couriers', 'icon' => 'ri-truck-line', 'table' => 'courier_apis'],
        'courierapi' => ['label' => 'Courier APIs', 'icon' => 'ri-truck-line', 'table' => 'courierapis'],
        'bulk-courier' => ['label' => 'Shipments', 'icon' => 'ri-truck-line', 'table' => 'courier_shipments', 'no_status' => true],
        'products' => ['label' => 'Products', 'icon' => 'ri-shopping-bag-3-line', 'table' => 'products'],
        'variants' => ['label' => 'Variants', 'icon' => 'ri-layout-grid-line', 'table' => 'product_variants'],
        'inventory' => ['label' => 'Inventory Movements', 'icon' => 'ri-archive-line', 'table' => 'inventory_movements', 'no_status' => true],
        'categories' => ['label' => 'Categories', 'icon' => 'ri-price-tag-3-line', 'table' => 'categories'],
        'subcategories' => ['label' => 'Subcategories', 'icon' => 'ri-price-tag-2-line', 'table' => 'subcategories'],
        'childcategories' => ['label' => 'Child Categories', 'icon' => 'ri-price-tag-line', 'table' => 'childcategories'],
        'brands' => ['label' => 'Brands', 'icon' => 'ri-store-2-line', 'table' => 'brands'],
        'attributes' => ['label' => 'Attributes', 'icon' => 'ri-list-check-2', 'table' => 'attributes'],
        'color' => ['label' => 'Colors', 'icon' => 'ri-palette-line', 'table' => 'colors'],
        'size' => ['label' => 'Sizes', 'icon' => 'ri-ruler-line', 'table' => 'sizes'],
        'order' => [
            'label' => 'Orders',
            'icon' => 'ri-file-list-3-line',
            'table' => 'orders',
            'metrics' => [
                [
                    'label' => 'Pending Orders',
                    'icon' => 'ri-time-line',
                    'table' => 'orders',
                    'where' => ['order_status', 'pending'],
                ],
                [
                    'label' => 'Delivered Orders',
                    'icon' => 'ri-checkbox-circle-line',
                    'table' => 'orders',
                    'where_in' => ['order_status', ['delivered', 'completed', 'complete']],
                ],
            ],
        ],
        'invoices' => ['label' => 'Invoices', 'icon' => 'ri-file-list-3-line', 'table' => 'orders'],
        'customer' => ['label' => 'Customers', 'icon' => 'ri-group-line', 'table' => 'customers'],
        'returns' => ['label' => 'Returns', 'icon' => 'ri-arrow-go-back-line', 'table' => 'order_returns', 'no_status' => true],
        'refunds' => [
            'label' => 'Refunds',
            'icon' => 'ri-refund-2-line',
            'table' => 'order_returns',
            'where_all' => [['restocked', 1], ['refund_required', 1]],
            'metrics' => [
                [
                    'label' => 'Refunded',
                    'icon' => 'ri-checkbox-circle-line',
                    'table' => 'order_returns',
                    'where' => ['refunded', 1],
                ],
                [
                    'label' => 'Awaiting Refund',
                    'icon' => 'ri-time-line',
                    'table' => 'order_returns',
                    'where' => ['refunded', 0],
                ],
            ],
        ],
        'review' => [
            'label' => 'Reviews',
            'icon' => 'ri-star-line',
            'table' => 'reviews',
            'metrics' => [
                [
                    'label' => 'Pending Reviews',
                    'icon' => 'ri-time-line',
                    'table' => 'reviews',
                    'where' => ['status', 'pending'],
                ],
                [
                    'label' => 'Active Reviews',
                    'icon' => 'ri-checkbox-circle-line',
                    'table' => 'reviews',
                    'where' => ['status', 'active'],
                ],
            ],
        ],
        'reviews' => [
            'label' => 'Reviews',
            'icon' => 'ri-star-line',
            'table' => 'reviews',
            'metrics' => [
                [
                    'label' => 'Pending Reviews',
                    'icon' => 'ri-time-line',
                    'table' => 'reviews',
                    'where' => ['status', 'pending'],
                ],
                [
                    'label' => 'Active Reviews',
                    'icon' => 'ri-checkbox-circle-line',
                    'table' => 'reviews',
                    'where' => ['status', 'active'],
                ],
            ],
        ],
        'campaign' => ['label' => 'Campaigns', 'icon' => 'ri-megaphone-line', 'table' => 'campaigns'],
        'flash-sale' => ['label' => 'Flash Sales', 'icon' => 'ri-flashlight-line', 'table' => 'flash_sales'],
        'landing-pages' => ['label' => 'Landing Pages', 'icon' => 'ri-pages-line', 'table' => 'landing_pages'],
        'page' => ['label' => 'Pages', 'icon' => 'ri-file-text-line', 'table' => 'create_pages'],
        'banner' => ['label' => 'Banners', 'icon' => 'ri-image-line', 'table' => 'banners'],
        'banner-category' => ['label' => 'Banner Categories', 'icon' => 'ri-image-2-line', 'table' => 'banner_categories'],
        'promo-banner' => ['label' => 'Promo Banners', 'icon' => 'ri-gallery-line', 'table' => 'promo_banners'],
        'shipping' => ['label' => 'Shipping Zones', 'icon' => 'ri-map-pin-line', 'table' => 'shippings', 'no_status' => true],
        'shippingcharges' => ['label' => 'Shipping Charges', 'icon' => 'ri-map-pin-line', 'table' => 'shipping_charges'],
        'orderstatus' => ['label' => 'Order Statuses', 'icon' => 'ri-flow-chart', 'table' => 'order_statuses', 'no_status' => true],
        'paymentgeteway' => ['label' => 'Payment Gateways', 'icon' => 'ri-wallet-3-line', 'table' => 'payment_gateways'],
        'smsgeteway' => ['label' => 'SMS Gateways', 'icon' => 'ri-message-3-line', 'table' => 'sms_gateways'],
        'notification-templates' => ['label' => 'Notification Templates', 'icon' => 'ri-notification-3-line', 'table' => 'notification_templates', 'status_column' => 'is_active'],
        'pixels' => ['label' => 'Pixels', 'icon' => 'ri-code-s-slash-line', 'table' => 'ecom_pixels'],
        'tag-manager' => ['label' => 'Tag Manager', 'icon' => 'ri-code-box-line', 'table' => 'google_tag_managers'],
        'social-media' => ['label' => 'Social Media', 'icon' => 'ri-share-line', 'table' => 'social_media'],
        'social-events' => ['label' => 'Social Events', 'icon' => 'ri-radar-line', 'table' => 'event_logs', 'no_status' => true],
        'contact' => ['label' => 'Messages', 'icon' => 'ri-mail-line', 'table' => 'contact_messages', 'no_status' => true],
        'visitors' => ['label' => 'Visitors', 'icon' => 'ri-line-chart-line', 'table' => 'visitors_master'],
        'analytics' => ['label' => 'Visitors', 'icon' => 'ri-line-chart-line', 'table' => 'visitors_master'],
        'wishlist' => ['label' => 'Wishlists', 'icon' => 'ri-heart-3-line', 'table' => 'wishlists', 'no_status' => true],
        'users' => ['label' => 'Users', 'icon' => 'ri-user-settings-line', 'table' => 'users'],
        'roles' => ['label' => 'Roles', 'icon' => 'ri-shield-user-line', 'table' => 'roles', 'no_status' => true],
        'permissions' => ['label' => 'Permissions', 'icon' => 'ri-lock-2-line', 'table' => 'permissions', 'no_status' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exact-route overrides
    |--------------------------------------------------------------------------
    */

    'routes' => [
        'admin.invoices.index' => [
            'label' => 'Invoices',
            'icon' => 'ri-file-list-3-line',
            'table' => 'orders',
            'metrics' => [
                [
                    'label' => 'Awaiting Payment',
                    'icon' => 'ri-time-line',
                    'table' => 'payments',
                    'where' => ['payment_status', 'pending'],
                ],
                [
                    'label' => 'Paid Payments',
                    'icon' => 'ri-checkbox-circle-line',
                    'table' => 'payments',
                    'where' => ['payment_status', 'paid'],
                ],
            ],
        ],
        'admin.courier.shipments' => ['label' => 'Shipments', 'icon' => 'ri-truck-line', 'table' => 'courier_shipments', 'no_status' => true],
        'admin.homepage.collections.index' => ['label' => 'Collections', 'icon' => 'ri-stack-line', 'table' => 'homepage_collections'],
        'admin.homepage.collections.create' => ['label' => 'Collections', 'icon' => 'ri-stack-line', 'table' => 'homepage_collections'],
        'admin.courier.settings' => ['label' => 'Couriers', 'icon' => 'ri-truck-line', 'table' => 'courier_apis'],
        'admin.courier.fraud-checker' => ['label' => 'Fraud Checks', 'icon' => 'ri-shield-check-line', 'table' => 'courier_fraud_checks', 'no_status' => true],
        'admin.analytics.live' => ['label' => 'Visitor Events', 'icon' => 'ri-radar-line', 'table' => 'event_logs', 'no_status' => true],
        'colors.index' => ['label' => 'Colors', 'icon' => 'ri-palette-line', 'table' => 'colors'],
        'reviews.pending' => [
            'label' => 'Pending Reviews',
            'icon' => 'ri-star-line',
            'table' => 'reviews',
            'where' => ['status', 'pending'],
            'no_status' => true,
        ],
        'shippingcharges.index' => ['label' => 'Shipping Charges', 'icon' => 'ri-map-pin-line', 'table' => 'shipping_charges'],
        'admin.shipping.verification' => [
            'label' => 'Shipping Zones',
            'icon' => 'ri-map-pin-line',
            'table' => 'shippings',
            'no_status' => true,
            'has_list' => false,
        ],
        'admin.recommendations.index' => [
            'label' => 'Recommended Products',
            'icon' => 'ri-thumb-up-line',
            'table' => 'products',
            'where' => ['is_recommended', 1],
        ],
        'admin.user-submissions.newsletter' => [
            'label' => 'Subscribers',
            'icon' => 'ri-mail-send-line',
            'table' => 'newsletter_subscribers',
            'no_status' => true,
        ],
        'admin.user-submissions.contacts' => [
            'label' => 'Messages',
            'icon' => 'ri-mail-line',
            'table' => 'contact_messages',
            'no_status' => true,
        ],
        'admin.user-submissions.service-requests.index' => [
            'label' => 'Service Requests',
            'icon' => 'ri-customer-service-2-line',
            'table' => 'service_requests',
            'no_status' => true,
        ],
        'admin.order_report' => [
            'label' => 'Orders',
            'icon' => 'ri-file-list-3-line',
            'table' => 'orders',
            'metrics' => [
                ['label' => 'Pending Orders', 'icon' => 'ri-time-line', 'table' => 'orders', 'where' => ['order_status', 'pending']],
                ['label' => 'Delivered Orders', 'icon' => 'ri-checkbox-circle-line', 'table' => 'orders', 'where_in' => ['order_status', ['delivered', 'completed', 'complete']]],
            ],
        ],
        'admin.stock_report' => [
            'label' => 'Stocked Products',
            'icon' => 'ri-archive-2-line',
            'table' => 'products',
            'where' => ['status', 1],
            'metrics' => [
                ['label' => 'Out of Stock', 'icon' => 'ri-close-circle-line', 'table' => 'products', 'where_op' => ['stock', '<=', 0]],
                ['label' => 'In Stock', 'icon' => 'ri-checkbox-circle-line', 'table' => 'products', 'where_op' => ['stock', '>', 0]],
            ],
        ],
        'admin.visitors.blocked' => [
            'label' => 'Blocked Visitors',
            'icon' => 'ri-forbid-2-line',
            'table' => 'visitors_master',
            'where' => ['status', 0],
            'no_status' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages with no countable module (settings, wizards, multi-type inboxes)
    |--------------------------------------------------------------------------
    |
    | The header still appears, but it describes the shop the settings belong
    | to rather than inventing a number for the page itself. The fourth card
    | stays an em dash: there is no list on the page to count.
    |
    */

    'overview' => [
        ['label' => 'Products', 'icon' => 'ri-shopping-bag-3-line', 'table' => 'products'],
        ['label' => 'Orders', 'icon' => 'ri-file-list-3-line', 'table' => 'orders'],
        ['label' => 'Customers', 'icon' => 'ri-group-line', 'table' => 'customers'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status vocabulary
    |--------------------------------------------------------------------------
    |
    | `customers.status` (and a few siblings) is a varchar written as both 1/0
    | and 'active'. Both spellings mean active; the decision lives here once.
    |
    */

    'active_values' => ['1', 1, 'active'],

    /*
    |--------------------------------------------------------------------------
    | Which controller methods display a list
    |--------------------------------------------------------------------------
    |
    | "Showing This Page" describes rows the grid is displaying. A create or
    | edit form has no grid, so the fourth card stays an em dash there instead
    | of borrowing the module total. A page can override this with has_list.
    |
    */

    'non_list_methods' => ['create', 'store', 'edit', 'update', 'destroy', 'show', 'print'],

    /*
    | Routes that are configuration screens but reuse a module table. They get
    | the module totals (useful context) and an em dash for the page count,
    | because the page is not displaying that module's list.
    */
    'no_list_routes' => [
        'admin.courier.fraud-checker',
        'admin.courier.settings',
    ],
];
