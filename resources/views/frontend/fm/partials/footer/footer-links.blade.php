{{--
    FM:PARTIAL footer/footer-links
    =========================================================================
    Green storefront footer from the supplied home.html reference.

    BOOTSTRAP 12-COLUMN SPLIT (lg and up)
        4  brand: logo, story, four social circles
        2  Quick Links
        2  Customer Care
        2  My Account
        2  Download App: the two store badges
        --  = 12, one row

    md (768-991): the reference's own split - brand half width, then the four
    link columns at one quarter each. phone (<768): brand centred above the
    four link columns, which stay visible two-up. The fixed phone tab bar
    carries Menu / Shop / Home / Cart / Account.

    DATA
    ------------------------------------------------------------------------
    Everything merchant-owned comes from the same globals as before:
    white_logo/dark_logo, footer_about_text, footer_links groups, categories,
    copyright, socials. The Download App badges use app URLs when the settings
    provide them (play_store_url / appstore_url are not columns today), and
    otherwise point at the two official store landings so nothing is a dead #.
    =========================================================================
--}}

<!-- BEGIN: MainFooter -->
<footer class="app-footer" data-purpose="footer-links" id="footer-contact">
    <div class="container">
        @php
            $fmShopName = $fmShopName ?? ($generalsetting->name ?? config('app.name'));
            $fmPhone = $fmPhone ?? ($generalsetting->support_phone ?? '');

            /* Catalog data, same source as the footer always used. */
            $fmFooterCats = ($categories ?? collect())->take(5);
            if ($fmFooterCats->isEmpty()) {
                $fmFooterCats = \App\Models\Category::query()->where('status', 1)->where('home_page', 1)->take(5)->get();
            }

            $fmCategoryLinks = $fmFooterCats->map(fn ($fmCat) => [
                'label' => $fmCat->name,
                'url'   => route('category', $fmCat->slug),
            ])->values()->all();

            if ($fmCategoryLinks === []) {
                $fmCategoryLinks = [
                    ['label' => 'Shop Catalog', 'url' => route('products.page')],
                    ['label' => 'Categories',   'url' => route('categories.page')],
                    ['label' => 'Hot Deals',    'url' => route('hotdeals')],
                    ['label' => 'Brands',       'url' => route('brands.page')],
                ];
            }

            /* Managed rows (Website Setup -> Footer); empty groups keep the
               route-driven defaults. */
            $fmManagedLinks = is_array($generalsetting->footer_links ?? null)
                ? $generalsetting->footer_links
                : [];
            $fmManagedByGroup = [];

            foreach ($fmManagedLinks as $fmManagedRow) {
                if (! is_array($fmManagedRow)) {
                    continue;
                }

                $fmManagedLabel = trim((string) ($fmManagedRow['label'] ?? ''));
                $fmManagedUrl = trim((string) ($fmManagedRow['url'] ?? ''));

                if ($fmManagedLabel === '' || $fmManagedUrl === '') {
                    continue;
                }

                $fmManagedGroup = (string) ($fmManagedRow['group'] ?? 'other');
                if (! in_array($fmManagedGroup, ['terms', 'site', 'useful', 'other'], true)) {
                    $fmManagedGroup = 'other';
                }

                $fmManagedByGroup[$fmManagedGroup][] = [
                    'label' => $fmManagedLabel,
                    'url' => $fmManagedUrl,
                    'new_tab' => (int) ($fmManagedRow['new_tab'] ?? 0) === 1,
                ];
            }

            $fmQuickLinks = $fmManagedByGroup['site'] ?? [
                ['label' => 'Home',     'url' => route('home'), 'new_tab' => false],
                ['label' => 'Shop',     'url' => route('products.page'), 'new_tab' => false],
                ['label' => 'About Us', 'url' => route('about.us'), 'new_tab' => false],
                ['label' => 'Contact',  'url' => route('contact'), 'new_tab' => false],
            ];

            $fmCareLinks = [
                ['label' => 'FAQ',      'url' => route('faq'), 'new_tab' => false],
                ['label' => 'Shipping', 'url' => route('faq'), 'new_tab' => false],
                ['label' => 'Returns',  'url' => route('return.policy'), 'new_tab' => false],
                ['label' => 'Support',  'url' => route('contact'), 'new_tab' => false],
            ];

            $fmAccountLinks = $fmManagedByGroup['useful'] ?? (auth('customer')->check()
                ? [
                    ['label' => 'Account',     'url' => route('customer.account'), 'new_tab' => false],
                    ['label' => 'Orders',      'url' => route('customer.orders'), 'new_tab' => false],
                    ['label' => 'Wishlist',    'url' => route('wishlist'), 'new_tab' => false],
                    ['label' => 'Track Order', 'url' => route('track.order'), 'new_tab' => false],
                ]
                : [
                    ['label' => 'Login',       'url' => route('customer.login'), 'new_tab' => false],
                    ['label' => 'Register',    'url' => route('customer.register'), 'new_tab' => false],
                    ['label' => 'Wishlist',    'url' => route('wishlist'), 'new_tab' => false],
                    ['label' => 'Track Order', 'url' => route('track.order'), 'new_tab' => false],
                ]);

            $fmPolicyLinks = $fmManagedByGroup['terms'] ?? [
                ['label' => 'Privacy Policy', 'url' => route('privacy.policy'), 'new_tab' => false],
                ['label' => 'Terms & Conditions', 'url' => route('terms.conditions'), 'new_tab' => false],
            ];

            /* Social circles. Real rows become links; with no configured row
               the four circles stay as decorative placeholders so the brand
               column keeps the reference's geometry. */
            $fmBiIcons = [
                'facebook'   => 'bi bi-facebook',
                'facebook-f' => 'bi bi-facebook',
                'instagram'  => 'bi bi-instagram',
                'twitter'    => 'bi bi-twitter-x',
                'twitter-x'  => 'bi bi-twitter-x',
                'google'     => 'bi bi-envelope',
                'youtube'    => 'bi bi-youtube',
                'linkedin'   => 'bi bi-linkedin',
            ];
            $fmSocialRows = ($socials ?? \App\Models\SocialMedia::query()->where('status', 1)->get())->take(4);
            $fmSocialFallback = [
                ['icon' => 'bi bi-facebook',  'label' => 'Facebook'],
                ['icon' => 'bi bi-youtube',   'label' => 'YouTube'],
                ['icon' => 'bi bi-linkedin',  'label' => 'LinkedIn'],
                ['icon' => 'bi bi-instagram', 'label' => 'Instagram'],
            ];

            $fmCopyright = trim((string) ($generalsetting->copyright ?? ''));
            $fmAboutHtml = \App\Support\HtmlSanitizer::inline((string) ($generalsetting->footer_about_text ?? ''));

            $fmFooterUsesWhiteLogo = filled($generalsetting->white_logo ?? null);
            $fmFooterLogo = $fmFooterUsesWhiteLogo
                ? $generalsetting->white_logo
                : ($generalsetting->dark_logo ?? null);

            $fmPlayStoreUrl = $generalsetting->play_store_url ?? 'https://play.google.com/store/apps';
            $fmAppStoreUrl = $generalsetting->appstore_url ?? 'https://apps.apple.com/';
        @endphp

        <div class="row g-4 fm-footer-grid">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="fm-footer-brand__row d-flex">
                    @if (filled($fmFooterLogo))
                        <span class="fm-footer-brand__logo {{ $fmFooterUsesWhiteLogo ? '' : 'fm-footer-brand__logo--chip' }}">
                            <img alt="{{ $fmShopName }}" loading="lazy"
                                 src="{{ \App\Support\MediaPath::url($fmFooterLogo, 'settings') }}">
                        </span>
                        <span class="fm-footer-brand__name visually-hidden">{{ $fmShopName }}</span>
                    @else
                        <span class="fm-footer-brand__name">{{ $fmShopName }}</span>
                    @endif
                </div>

                <div class="small m-0 fm-footer-brand__text">
                    {!! $fmAboutHtml !== '' ? $fmAboutHtml : e('Your trusted online store for quality products at the best price.') !!}
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3 fm-footer-brand__social">
                    @if($fmSocialRows->isNotEmpty())
                        @foreach($fmSocialRows as $fmSocial)
                            @php
                                $fmKey  = strtolower($fmSocial->title ?? ($fmSocial->icon ?? ''));
                                $fmIcon = \App\Support\SocialIcons::entryForFa($fmSocial->icon)['bi']
                                    ?? $fmBiIcons[$fmKey]
                                    ?? 'bi bi-globe2';
                                $fmLink = (string) ($fmSocial->link ?? '#');
                                $fmHref = str_contains($fmLink, '@') ? 'mailto:' . $fmLink : $fmLink;
                                $fmExternal = str_starts_with($fmHref, 'http');
                            @endphp
                            <a class="btn btn-sm btn-outline-secondary rounded-circle text-white" href="{{ $fmHref }}"
                               @if($fmExternal) target="_blank" rel="noopener" @endif
                               aria-label="{{ $fmSocial->title ?? 'social' }}"><i class="{{ $fmIcon }}" aria-hidden="true"></i></a>
                        @endforeach
                    @else
                        @foreach($fmSocialFallback as $fmSocial)
                            <span class="btn btn-sm btn-outline-secondary rounded-circle text-white fm-footer-social-placeholder" aria-hidden="true">
                                <i class="{{ $fmSocial['icon'] }}"></i>
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2 fm-footer-linkcol">
                <h2 class="fm-footer-heading">Quick Links</h2>
                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                    @foreach ($fmQuickLinks as $fmLink)
                        <li>
                            <a href="{{ $fmLink['url'] }}"
                               @if(!empty($fmLink['new_tab'])) target="_blank" rel="noopener" @endif>
                                {{ $fmLink['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2 fm-footer-linkcol">
                <h2 class="fm-footer-heading">Customer Care</h2>
                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                    @foreach ($fmCareLinks as $fmLink)
                        <li><a href="{{ $fmLink['url'] }}">{{ $fmLink['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2 fm-footer-linkcol">
                <h2 class="fm-footer-heading">My Account</h2>
                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                    @foreach ($fmAccountLinks as $fmLink)
                        <li>
                            <a href="{{ $fmLink['url'] }}"
                               @if(!empty($fmLink['new_tab'])) target="_blank" rel="noopener" @endif>
                                {{ $fmLink['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2 fm-footer-linkcol fm-footer-app">
                <h2 class="fm-footer-heading">Download App</h2>
                <p class="fm-footer-app__label">Get our app on</p>

                <div class="fm-footer-app__badges">
                    <a class="fm-store-badge" href="{{ $fmPlayStoreUrl }}" target="_blank" rel="noopener"
                       aria-label="Get it on Google Play">
                        <i class="bi bi-google-play" aria-hidden="true"></i>
                        <span class="fm-store-badge__text">
                            <small>GET IT ON</small>
                            <strong>Google Play</strong>
                        </span>
                    </a>

                    <a class="fm-store-badge" href="{{ $fmAppStoreUrl }}" target="_blank" rel="noopener"
                       aria-label="Download on the App Store">
                        <i class="bi bi-apple" aria-hidden="true"></i>
                        <span class="fm-store-badge__text">
                            <small>Download on the</small>
                            <strong>App Store</strong>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="fm-footer-bottom">
        <div class="container fm-footer-bottom__inner">
            <p class="fm-footer-bottom__copy small m-0">
                {!! $fmCopyright !== '' ? $fmCopyright : ('&copy; ' . date('Y') . ' ' . e($fmShopName) . '. All rights reserved.') !!}
            </p>

            @if($fmPolicyLinks !== [])
                <ul class="fm-footer-bottom__policy list-inline small m-0">
                    @foreach($fmPolicyLinks as $fmPolicyLink)
                        <li class="list-inline-item">
                            <a href="{{ $fmPolicyLink['url'] }}"
                               @if(!empty($fmPolicyLink['new_tab'])) target="_blank" rel="noopener" @endif>
                                {{ $fmPolicyLink['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</footer>
<!-- END: MainFooter -->

{{-- Phone-only tab bar. It is a <nav> OUTSIDE the <footer> landmark: the
     footer is contentinfo, this bar is the primary phone navigation. --}}
@include('frontend.fm.partials.footer.mobile-bottom-nav', [
    'fmCartCount' => $fmCartCount ?? 0,
])
