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

                    $footerAboutText = trim((string) ($generalsetting->footer_about_text ?? ''));
                @endphp
                <img src="{{ asset($footerLogoPath) }}" class="footer-logo" alt="footer-logo">
                {{-- Clamped to 6 lines: the admin cap is 400 characters, but a
                     long paragraph still stretched this column and broke the
                     homepage grid, so the CSS limit is the second fence. --}}
                <p class="mb-0 footer-about-text">{{ $footerAboutText ?: "Z-Evo-Commerce brings you tons of products in over 20+ different categories, including men's fashion, women's fashion, baby fashion, and more." }}</p>
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
            <div class="footer-widget widget-link">
                <h6 class="title">Terms & Policies</h6>
                <ul>
                    <li><a href="{{ route('cookie.policy') }}">Cookie Policy</a></li>
                    <li><a href="{{ route('return.policy') }}">Return Policy</a></li>
                    <li><a href="{{ route('privacy.policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('terms.conditions') }}">Terms and Conditions</a></li>
                </ul>
            </div>

            <div class="footer-widget widget-link">
                <h6 class="title">Site Links</h6>
                <ul>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                    <li><a href="{{ route('offers.index') }}">Offers</a></li>
                    <li><a href="{{ route('about.us') }}">About Us</a></li>
                </ul>
            </div>

            <div class="footer-widget widget-link">
                <h6 class="title">Useful Links</h6>
                <ul>
                    <li><a href="{{ route('register') }}">Sign Up</a></li>
                    <li><a href="{{ route('login') }}">Login</a></li>
                    <li><a href="{{ route('track.order') }}">Track Order</a></li>
                    <li><a href="{{ route('faq') }}">FAQ</a></li>
                </ul>
            </div>

            <div class="footer-widget widget-link">
                <h6 class="title">Other Links</h6>
                <ul>
                    <li><a href="{{ url('/products') }}">Products</a></li>
                    <li><a href="{{ url('/categories') }}">All Categories</a></li>
                    <li><a href="{{ url('/brands') }}">All Brands</a></li>
                    <li><a href="{{ route('wishlist') }}">Wishlist</a></li>
                </ul>
            </div>

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
                    @php
                        $paymentImagePath = preg_replace('~^public/~', '', (string) ($generalsetting->footer_payment_image ?? ''));
                        $paymentImagePath = ltrim($paymentImagePath, '/');
                        if (empty($paymentImagePath) || !\Illuminate\Support\Facades\File::exists(public_path($paymentImagePath))) {
                            $paymentImagePath = 'assets/images/frontend/footer/637cd4514c0c21669125201.png';
                        }
                    @endphp
                    <img src="{{ asset($paymentImagePath) }}" alt="payment-methods">
                </div>
            </div>
        </div>
    </div>
</footer>
