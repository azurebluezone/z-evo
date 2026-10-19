{{--
    FM:PARTIAL about/closing-cta
    =========================================================================
    The last band on the About page: a size question and the two answers - ask
    the shop, or check an order that is already on its way.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.about.closing-cta')
--}}

{{-- ----------------------------------------------------------------------
     Closing CTA.
     ---------------------------------------------------------------------- --}}
<section class="fm-about__section">
    <div class="container mx-auto px-4">
        <div class="fm-about__closing">
            <div>
                <h2 class="fm-about__closing-title">Not sure about the size?</h2>
                <p class="fm-about__closing-text">
                    Send us a message with your child's age and we will point you at the right department.
                </p>
            </div>
            <div class="fm-about__closing-actions">
                <a class="btn btn-brand-primary rounded-pill px-4 py-2" href="{{ route('contact') }}">
                    <i class="bi bi-chat-dots me-1"></i> Ask us
                </a>
                <a class="btn rounded-pill px-4 py-2 fm-about__cta-alt" href="{{ route('track.order') }}">
                    Track an order
                </a>
            </div>
        </div>
    </div>
</section>