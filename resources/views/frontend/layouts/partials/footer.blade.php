{{--
    Footer Component.

    This partial owns the <footer> element itself, so app.blade.php must NOT
    wrap it in another one. It used to, which nested two contentinfo
    landmarks. role="contentinfo" lives here so the landmark survives the
    removal of the wrapper.
--}}
<footer class="footer-area footer-bg" role="contentinfo">
    <div class="container">
        {{-- Footer Top --}}
        <div class="footer-top">
            <div class="footer-widget widget-about">
                @php
                    $footerLogoPath = $generalsetting->white_logo
                        ?? $generalsetting->dark_logo
                        ?? 'assets/images/frontend/footer/67715a0cd06bf1735481868.png';
                    $footerLogoPath = preg_replace('~^public/~', '', (string) $footerLogoPath);

                    // Quill stores HTML now. Sanitizing again at render keeps
                    // rows written before the editor existed safe to print.
                    $footerAboutText = \App\Support\HtmlSanitizer::inline((string) ($generalsetting->footer_about_text ?? ''));

                    // Footer links managed on Website Setup -> Footer. A group
                    // with no managed rows keeps the designed defaults, so a
                    // fresh install still shows a complete footer.
                    $footerManagedLinks = is_array($generalsetting->footer_links ?? null)
                        ? $generalsetting->footer_links
                        : [];
                    $footerManagedByGroup = [];

                    foreach ($footerManagedLinks as $footerLinkRow) {
                        if (! is_array($footerLinkRow)) {
                            continue;
                        }

                        $footerLinkLabel = trim((string) ($footerLinkRow['label'] ?? ''));
                        $footerLinkUrl = trim((string) ($footerLinkRow['url'] ?? ''));

                        if ($footerLinkLabel === '' || $footerLinkUrl === '') {
                            continue;
                        }

                        $footerLinkGroup = (string) ($footerLinkRow['group'] ?? 'other');
                        if (! in_array($footerLinkGroup, ['terms', 'site', 'useful', 'other'], true)) {
                            $footerLinkGroup = 'other';
                        }

                        $footerManagedByGroup[$footerLinkGroup][] = [
                            'label' => $footerLinkLabel,
                            'url' => $footerLinkUrl,
                            'new_tab' => (int) ($footerLinkRow['new_tab'] ?? 0) === 1,
                        ];
                    }

                    $footerLinkGroups = [
                        'terms' => [
                            'title' => 'Terms & Policies',
                            'links' => $footerManagedByGroup['terms'] ?? [
                                ['label' => 'Cookie Policy', 'url' => route('cookie.policy')],
                                ['label' => 'Return Policy', 'url' => route('return.policy')],
                                ['label' => 'Privacy Policy', 'url' => route('privacy.policy')],
                                ['label' => 'Terms and Conditions', 'url' => route('terms.conditions')],
                            ],
                        ],
                        'site' => [
                            'title' => 'Site Links',
                            'links' => $footerManagedByGroup['site'] ?? [
                                ['label' => 'Home', 'url' => route('home')],
                                ['label' => 'Contact', 'url' => route('contact')],
                                ['label' => 'Offers', 'url' => route('offers.index')],
                                ['label' => 'About Us', 'url' => route('about.us')],
                            ],
                        ],
                        'useful' => [
                            'title' => 'Useful Links',
                            'links' => $footerManagedByGroup['useful'] ?? [
                                ['label' => 'Sign Up', 'url' => route('register')],
                                ['label' => 'Login', 'url' => route('login')],
                                ['label' => 'Track Order', 'url' => route('track.order')],
                                ['label' => 'FAQ', 'url' => route('faq')],
                            ],
                        ],
                        'other' => [
                            'title' => 'Other Links',
                            'links' => $footerManagedByGroup['other'] ?? [
                                ['label' => 'Products', 'url' => url('/products')],
                                ['label' => 'All Categories', 'url' => url('/categories')],
                                ['label' => 'All Brands', 'url' => url('/brands')],
                                ['label' => 'Wishlist', 'url' => route('wishlist')],
                            ],
                        ],
                    ];
                @endphp
                <img src="{{ asset($footerLogoPath) }}" class="footer-logo" alt="footer-logo">
                {{-- Clamped to 6 lines: the admin cap is 400 characters, but a
                     long paragraph still stretched this column and broke the
                     homepage grid, so the CSS limit is the second fence. --}}
                {{-- A block wrapper, not a <p>: Quill already returns paragraphs,
                     and nesting <p> inside <p> makes the browser split the
                     footer widget. --}}
                <div class="mb-0 footer-about-text">{!! $footerAboutText !== '' ? $footerAboutText : "Z-Evo-Commerce brings you tons of products in over 20+ different categories, including men's fashion, women's fashion, baby fashion, and more." !!}</div>
            </div>

            <div class="newsletter-widget text-center">
                <div class="newsletter-widget-header">
                    <h6 class="title">SIGN UP FOR OUR NEWSLETTER</h6>
                    <p class="fs-14">Receive our latest updates about our products and promotions.</p>
                </div>
                <form action="{{ route('newsletter.subscribe') }}" class="subscribe-form" method="POST">
                    @csrf
                    <input type="email" placeholder="Your Email Address..." name="email" required>
                    <button type="submit" class="subscribe-btn">
                        <span>Subscribe</span> 
                        <i class="las la-paper-plane"></i>
                    </button>
                </form>
            </div>

            <div class="widget-contact">
                <h6 class="title">Contact Us</h6>
                <ul>
                    <li><i class="las la-map-marker"></i> {{ $contact->address ?? 'Z-Evo-Commerce, House 666, Road #5555' }}</li>
                    <li>
                        <a href="tel:{{ $contact->phone ?? '+001001001' }}">
                            <i class="las la-phone"></i>{{ $contact->phone ?? '+ (001) 001 001' }}
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ $contact->email ?? 'info@z-evo-commerce.com' }}">
                            <i class="las la-envelope"></i>{{ $contact->email ?? 'info@z-evo-commerce.com' }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Footer Middle --}}
        <div class="footer-middle">
            {{-- The four managed columns. A group renders the admin's rows
                 when it has any, otherwise its designed defaults. --}}
            @foreach($footerLinkGroups as $footerLinkGroupKey => $footerLinkGroup)
                <div class="footer-widget widget-link">
                    <h6 class="title">{{ $footerLinkGroup['title'] }}</h6>
                    <ul>
                        @foreach($footerLinkGroup['links'] as $footerLink)
                            <li>
                                <a href="{{ $footerLink['url'] }}"
                                   @if(!empty($footerLink['new_tab'])) target="_blank" rel="noopener" @endif>
                                    {{ $footerLink['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="footer-widget widget-link">
                <h6 class="title">Top Categories</h6>
                <ul>
                    @if(isset($top_categories))
                        @foreach($top_categories->take(6) as $category)
                            <li><a href="{{ route('products.category', $category->slug) }}">{{ $category->name }}</a></li>
                        @endforeach
                    @endif
                </ul>
            </div>

            <div class="footer-widget widget-link">
                <h6 class="title">Trending</h6>
                <ul>
                    @if(isset($trending_categories))
                        @foreach($trending_categories->take(6) as $category)
                            <li><a href="{{ route('products.category', $category->slug) }}">{{ $category->name }}</a></li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

        {{-- Footer Copyright --}}
        <div class="footer-copyright">
            <div class="copyright-area d-flex flex-wrap align-items-center justify-content-between gap-4 flex-wrap-reverse">
                <div class="left">
                    <p>{!! $generalsetting->copyright ?? ('© ' . date('Y') . ' <a href="' . route('home') . '">' . e($generalsetting->name ?? 'Z-Evo-Commerce') . '</a> All Rights Reserved.') !!}</p>
                </div>

                <ul class="social-icons d-flex gap-2 flex-wrap mt-0">
                    @if(isset($socialicons) && $socialicons->count())
                        @foreach($socialicons as $value)
                            <li>
                                <a href="{{ $value->link ?? '#' }}" target="_blank">
                                    <i class="{{ $value->icon }}"></i>
                                </a>
                            </li>
                        @endforeach
                    @else
                        {{-- Default icons shown when no social icon is saved
                             yet. The links are placeholders ('#'): an older
                             copy pointed at another shop's profiles. --}}
                        <li>
                            <a href="#" aria-label="X (Twitter)">
                                <i class="fa-brands fa-x-twitter"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#" aria-label="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </li>
                    @endif
                </ul>

                <div class="right">
                    @include('frontend.partials.payment-methods')
                </div>
            </div>
        </div>
    </div>
</footer>
