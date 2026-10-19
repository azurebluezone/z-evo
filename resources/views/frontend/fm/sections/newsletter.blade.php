{{-- FM:SECTION newsletter --------------------------------------------------
     Opt-in form wired to the existing newsletter.subscribe endpoint.
     --------------------------------------------------------------------- --}}

<section class="fm-section" data-purpose="newsletter">
    <div class="container mx-auto px-4">
        <div class="fm-newsletter">
            <div class="fm-newsletter__copy">
                <span class="fm-newsletter__badge">Discounts at checkout</span>
                <h2 class="fm-newsletter__title">Know about new offers first</h2>
                <p class="fm-newsletter__text">
                    Share your email and get new deals and product news first.
                </p>
            </div>

            <form class="fm-newsletter__form" method="POST" action="{{ route('newsletter.subscribe') }}">
                @csrf
                <label class="visually-hidden" for="fmNewsletterEmail">Email address</label>
                <input class="form-control" type="email" name="email" id="fmNewsletterEmail"
                       placeholder="Your email address" required maxlength="255">
                <button class="fm-btn fm-btn--light" type="submit">Subscribe</button>
            </form>
        </div>
    </div>
</section>
