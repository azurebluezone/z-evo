{{--
    FM:SECTION home/newsletter ----------------------------------------------
    The subscribe card at the foot of the redesigned home page
    (design: work/home.html, "NewsletterSection").

    Same endpoint and same wording as sections/newsletter.blade.php, which is
    still included by product-details.blade.php and therefore stays exactly
    as it is. Only the card layout differs.

    The reference blocks the submit with onsubmit="event.preventDefault()",
    which would silently throw the address away. This form posts for real to
    route('newsletter.subscribe'), so it works with JavaScript off and the
    confirmation/error flash the controller already sets still arrives.
    --------------------------------------------------------------------- --}}

<section class="fm-newsletter-section" data-purpose="newsletter-subscription">
    <div class="fm-container">
        <div class="fm-newsletter-card">

            <div class="fm-newsletter-texts">
                <span class="fm-badge fm-badge-organic">Discounts at checkout</span>
                <h2>Know about new offers first</h2>
                <p>Share your email and get new deals and product news first.</p>
            </div>

            <form class="fm-newsletter-form" method="POST" action="{{ route('newsletter.subscribe') }}"
                  data-fm-newsletter>
                @csrf
                <label class="visually-hidden" for="fmHomeNewsletterEmail">Email address</label>
                <input class="fm-newsletter-input" type="email" name="email"
                       id="fmHomeNewsletterEmail" placeholder="Your email address"
                       required maxlength="255" autocomplete="email"
                       aria-describedby="fmHomeNewsletterStatus">
                <button class="fm-newsletter-btn" type="submit">Subscribe</button>

                {{-- fm-app.js writes the ajax result in here. role=status keeps
                     the confirmation beside the field the shopper just used -
                     which is where they are looking. The layout's flash alert
                     renders at the top of <main>, 7113px above this card on
                     the home page, so as the only feedback it was invisible.

                     Empty by default: :empty in fm-home-v2.css hides it, so
                     nothing shifts while the form is at rest. --}}
                <p class="fm-newsletter-status" id="fmHomeNewsletterStatus"
                   data-fm-newsletter-status role="status" aria-live="polite"></p>
            </form>

        </div>
    </div>
</section>