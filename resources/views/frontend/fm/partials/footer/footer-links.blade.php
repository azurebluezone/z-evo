{{--
    FM:PARTIAL footer/footer-links
    =========================================================================
    A same-to-same copy of the work/index.html "4-Column Footer Architecture"
    (Bootstrap), plus the bottom bar the shop asked for:

        MAIN ROW (the reference row, unchanged on a desktop)
            1. col-lg-4      Brand & story + the three social circles
            2. col-lg-2      Categories
            3. col-lg-2      Company
            4. col-lg-4      Customer Support + the SSL reassurance line

        BOTTOM BAR (below the row, its own bar)
            left    the footer menu (khaasfood-style, its own partial)
            centre  the accepted payment methods image
            right   the copyright line, with the policy links under it

    WHY THE COLUMNS NO LONGER USE footer-column.blade.php
    ------------------------------------------------------------------------
    They used to be five accordions (Menu / Categories / Company / Policies /
    Support). The reference is not an accordion footer: on a desktop it is four
    open columns, and the phone version is those SAME three link columns laid
    out two-up (not one stacked accordion list per column). So the three link
    columns are plain <h6> + <ul> again - the exact markup of the reference -
    and the accordion survives only where the shop wants it: the footer menu in
    the bottom bar (partials/footer/footer-menu, same data-fm-footer-toggle
    contract with fm-app.js).

    footer-column.blade.php is therefore no longer included from here. It is
    left on disk until the shop confirms nothing else needs it.

    PHONE LAYOUT (the shop's spec)
    ------------------------------------------------------------------------
        - the brand block is full width and CENTRED at the top: logo, story,
          social circles.
        - below lg the link columns go TWO-UP, exactly like the reference:
          Categories and Company share the first row (col-6 each) and
          Customer Support takes the next row at half width (col-6). Sums:
          12 + 6 + 6 + 6 wrap as brand / (Categories + Company) / (Support);
          at lg 4 + 2 + 2 + 4 = 12, so the reference's desktop grid is
          untouched.

    TEXT COLOUR
    ------------------------------------------------------------------------
    The story paragraph and the three support rows are WHITE, not the
    reference's muted slate - a shop request. The class that does it
    (.fm-footer-brand__text / .fm-footer-support__list) lives in
    fm-footer.css, so nothing here fights Bootstrap's utilities.

    DATA
    ------------------------------------------------------------------------
    Everything merchant-owned comes from the database first and falls back to
    the reference's own copy when the field is empty, so the footer never
    renders a blank row:

        - the logo      $generalsetting->dark_logo (the footer plate is dark,
                        but the logo sits on a near-white plate, so the DARK
                        ink mark is the correct one - same rule as the header)
        - the story     $generalsetting->footer_about_text
        - the phone     $generalsetting->support_phone / $contact->phone
        - address+email $contact
        - categories    $categories, else the first five published ones, else
                        the five labels of the reference
        - payment image $generalsetting->footer_payment_image, and if that
                        file is missing from /public the bundled placeholder
                        (the classic footer's own guard: never a broken <img>)

    Props
        fmShopName  string  shop name, used in the brand line and copyright
        fmPhone     string  support phone, blank falls back to the reference

    Usage
        @include('frontend.fm.partials.footer.footer-links')
--}}

<!-- BEGIN: MainFooter -->
<footer class="app-footer" data-purpose="footer-links" id="footer-contact">
    <div class="container">
        @php
            /*
                Categories. Merchant data first: the shop's own categories are
                what a customer is looking for. The five reference labels are
                the LAST resort, so a shop with no categories still gets the
                designed footer instead of an empty column.
            */
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
                    ['label' => 'Vegetables & Roots',  'url' => route('products.page')],
                    ['label' => 'Organic Fruits',      'url' => route('products.page')],
                    ['label' => 'Dairy & Farm Eggs',   'url' => route('products.page')],
                    ['label' => 'Artisan Sourdough',   'url' => route('products.page')],
                    ['label' => 'Grass-Fed Meats',     'url' => route('products.page')],
                ];
            }

            /*
                Company. The same five destinations as the reference, with the
                shop's own name in the first row. "Partner Farmers" and "Soil
                Regeneration" have no page of their own yet, so they point at
                the about page rather than at a "#" that goes nowhere.

                When the admin has saved links in the "Site Links" group on
                Website Setup -> Footer, those rows replace this default list.
                The "Terms & Policies" group is rendered in the bottom bar.
            */
            $fmCompanyLinks = [
                ['label' => 'About ' . $fmShopName, 'url' => route('about.us')],
                ['label' => 'Partner Farmers',      'url' => route('about.us')],
                ['label' => 'Soil Regeneration',    'url' => route('about.us')],
                ['label' => 'FAQ & Help Center',    'url' => route('faq')],
                ['label' => 'Contact Us',           'url' => route('contact')],
            ];

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

            if (! empty($fmManagedByGroup['site'])) {
                $fmCompanyLinks = $fmManagedByGroup['site'];
            }

            $fmPolicyLinks = $fmManagedByGroup['terms'] ?? [];

            /*
                Customer support. Merchant data first, reference copy as the
                fallback - a support block with an empty row is worse than one
                that shows the designed default.
            */
            $fmSupportAddress = $contact->address ?? null;
            $fmSupportEmail   = $contact->email ?? null;

            $fmSupportRows = array_values(array_filter([
                [
                    'icon'  => 'bi bi-geo-alt',
                    'text'  => $fmSupportAddress ?: '742 Evergreen Terrace, Brooklyn, NY 11201',
                    'class' => 'align-items-start',
                    'iconExtra' => 'mt-1',
                ],
                [
                    'icon'  => 'bi bi-telephone',
                    'text'  => $fmPhone ?: '+1 (800) 555-FOOD (24/7 Helpline)',
                    'class' => 'align-items-center',
                    'iconExtra' => '',
                ],
                [
                    'icon'  => 'bi bi-envelope',
                    'text'  => $fmSupportEmail ?: 'care@freshmart-groceries.com',
                    'class' => 'align-items-center',
                    'iconExtra' => '',
                ],
            ], fn ($fmRow) => trim((string) $fmRow['text']) !== ''));

            /*
                The FOOTER MENU of the bottom bar. Same destinations the old
                footer Menu column carried, because it IS that menu, moved
                down into the bar where the shop wants it. Log in / New
                account become My account / My orders when signed in - a
                footer that asks a signed-in customer to register again is
                drift, not a feature.
            */
            $fmMenuLinks = [
                ['label' => 'Home', 'url' => route('home'), 'active' => request()->routeIs('home')],
                [
                    'label'  => 'Shop Catalog',
                    'url'    => route('products.page'),
                    'active' => request()->routeIs('products.page', 'category', 'products.category', 'products.brand'),
                ],
                ['label' => 'Categories', 'url' => route('categories.page'), 'active' => request()->routeIs('categories.page')],
                ['label' => 'Special offers', 'url' => route('offers.index'), 'active' => request()->routeIs('offers.index')],
                ['label' => 'Hot deals', 'url' => route('hotdeals'), 'active' => request()->routeIs('hotdeals')],
                ['label' => 'Contact', 'url' => route('contact'), 'active' => request()->routeIs('contact')],
                ['label' => 'Wishlist', 'url' => route('wishlist'), 'active' => request()->routeIs('wishlist')],
            ];

            if (auth('customer')->check()) {
                $fmMenuLinks[] = ['label' => 'My account', 'url' => route('customer.account'), 'active' => request()->routeIs('customer.account')];
                $fmMenuLinks[] = ['label' => 'My orders', 'url' => route('customer.orders'), 'active' => request()->routeIs('customer.orders')];
            } else {
                $fmMenuLinks[] = ['label' => 'Log in', 'url' => route('customer.login'), 'active' => request()->routeIs('customer.login')];
                $fmMenuLinks[] = ['label' => 'New account', 'url' => route('customer.register'), 'active' => request()->routeIs('customer.register')];
            }

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
            $fmSocialRows = ($socials ?? \App\Models\SocialMedia::query()->where('status', 1)->get());

            /*
                Copyright. Merchant copy first (it may carry links / HTML, the
                way the classic footer allows), the reference line as fallback
                so the bar is never empty. The payment strip below is rendered
                by frontend.partials.payment-methods, the same partial the
                classic footer uses.
            */
            $fmCopyright = trim((string) ($generalsetting->copyright ?? ''));

            // Quill HTML, sanitized again at render for rows saved before the
            // editor existed (the controller already sanitizes on write).
            $fmAboutHtml = \App\Support\HtmlSanitizer::inline((string) ($generalsetting->footer_about_text ?? ''));
        @endphp

        {{--
            THE REFERENCE ROW. Column classes below lg are the phone layout:
            brand full width, then the three link columns two-up
            (Categories + Company, then Support). At lg the reference's own
            4 + 2 + 2 + 4 grid takes over and the row is the same-to-same
            copy of work/index.html.
        --}}
        <div class="row g-4 mb-4">
            {{-- Col 1: Brand & Story.

                 The logo keeps the .fm-footer-brand__ wrappers: the layout's
                 own #fm-audit-fix block declares
                 `img { max-width:100% !important; height:auto !important; }`,
                 which outranks any height utility on the bare <img>, and the
                 logo then rendered at the full column width. The rules that
                 put the size back are in fm-footer.css under BRAND COLUMN.

                 The name stays VISUALLY HIDDEN while a logo is present: this
                 shop's logo is already the wordmark, so printing it beside
                 itself duplicated it. With no logo the name becomes the brand. --}}
            <div class="col-12 col-lg-4">
                <div class="fm-footer-brand__row d-flex">
                    @if (!empty($generalsetting->dark_logo))
                        <span class="fm-footer-brand__logo">
                            <img alt="{{ $fmShopName }}" loading="lazy"
                                 class="h-9 w-auto object-contain"
                                 src="{{ \App\Support\MediaPath::url($generalsetting->dark_logo, 'settings') }}">
                        </span>
                        <div class="fm-footer-brand__name visually-hidden">{{ $fmShopName }}</div>
                    @else
                        <div class="fm-footer-brand__name">{{ $fmShopName }}</div>
                    @endif
                </div>

                {{-- A div: the editor returns paragraphs, and a <p> here would
                     nest them. --}}
                <div class="small m-0 fm-footer-brand__text">
                    {!! $fmAboutHtml !== '' ? $fmAboutHtml : 'FreshMart connects conscious households directly with regional regenerative farmers. Harvested at sunrise, inspected by AI, delivered to your door.' !!}
                </div>

                <div class="d-flex gap-2 mt-3 fm-footer-brand__social">
                    @forelse($fmSocialRows->take(3) as $fmSocial)
                        @php
                            $fmKey  = strtolower($fmSocial->title ?? ($fmSocial->icon ?? ''));
                            // Stored Font Awesome class -> Bootstrap Icons via the
                            // shared catalog; the old title map stays as fallback
                            // for rows saved before the catalog existed.
                            $fmIcon = \App\Support\SocialIcons::entryForFa($fmSocial->icon)['bi']
                                ?? $fmBiIcons[$fmKey]
                                ?? 'bi bi-globe2';
                            $fmLink = (string) ($fmSocial->link ?? '#');
                            $fmHref = str_contains($fmLink, '@') ? 'mailto:' . $fmLink : $fmLink;
                            // External links open in a new tab and never leak
                            // the opener; mailto: links stay in the same tab.
                            $fmExternal = str_starts_with($fmHref, 'http');
                        @endphp
                        <a class="btn btn-sm btn-outline-secondary rounded-circle text-white" href="{{ $fmHref }}"
                           @if($fmExternal) target="_blank" rel="noopener" @endif
                           aria-label="{{ $fmSocial->title ?? 'social' }}"><i class="{{ $fmIcon }}"></i></a>
                    @empty
                        <a class="btn btn-sm btn-outline-secondary rounded-circle text-white" href="#fb" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a class="btn btn-sm btn-outline-secondary rounded-circle text-white" href="#ig" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a class="btn btn-sm btn-outline-secondary rounded-circle text-white" href="#x" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                    @endforelse
                </div>
            </div>

            {{-- Col 2: Categories.
                 Two-up with Company below lg (col-6 each); at lg it is the
                 reference's col-lg-2. --}}
            <div class="col-6 col-lg-2 fm-footer-linkcol fm-footer-categories-col">
                <h6>Categories</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                    @foreach ($fmCategoryLinks as $fmCategoryLink)
                        <li><a href="{{ $fmCategoryLink['url'] }}">{!! $fmCategoryLink['label'] !!}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Col 3: Company --}}
            <div class="col-6 col-lg-2 fm-footer-linkcol fm-footer-company-col">
                <h6>Company</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                    @foreach ($fmCompanyLinks as $fmCompanyLink)
                        <li>
                            <a href="{{ $fmCompanyLink['url'] }}"
                               @if(!empty($fmCompanyLink['new_tab'])) target="_blank" rel="noopener" @endif>
                                {{ $fmCompanyLink['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Col 4: Customer Support & Security --}}
            <div class="col-6 col-lg-4 fm-footer-linkcol fm-footer-support">
                <h6>Customer Support</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small m-0 fm-footer-support__list">
                    @foreach ($fmSupportRows as $fmSupportRow)
                        <li class="d-flex gap-2 {{ $fmSupportRow['class'] }}">
                            <i class="bi {{ $fmSupportRow['icon'] }} text-success me-1 {{ $fmSupportRow['iconExtra'] }}" aria-hidden="true"></i>
                            <span>{{ $fmSupportRow['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
                </div>
            </div>
        </div>

        {{--
            THE BOTTOM BAR: copyright | accepted payment methods.
            One shared partial renders the payment strip so this footer and
            the classic one can never show different badges.
        --}}
        <div class="fm-footer-bottom d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
            <div class="fm-footer-bottom__legal">
                <p class="small m-0">
                    {!! $fmCopyright !== '' ? $fmCopyright : ('&copy; ' . date('Y') . ' ' . e($fmShopName) . '. All rights reserved.') !!}
                </p>

                {{-- Terms & Policies group from Website Setup -> Footer. The
                     classic footer renders the same rows as its own column. --}}
                @if(!empty($fmPolicyLinks))
                    <ul class="list-inline small mb-0 mt-1">
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
            <div class="fm-footer-payment">
                @include('frontend.partials.payment-methods')
            </div>
        </div>

    </div>
</footer>
<!-- END: MainFooter -->

{{-- Phone-only tab bar. It is a <nav> OUTSIDE the <footer> landmark: the
     footer is contentinfo, this bar is the primary phone navigation. --}}
@include('frontend.fm.partials.footer.mobile-bottom-nav', [
    'fmCartCount' => $fmCartCount ?? 0,
])
