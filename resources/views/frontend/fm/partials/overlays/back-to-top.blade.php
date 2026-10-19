{{--
    FM:PARTIAL overlays/back-to-top
    =========================================================================
    The floating "scroll to top" button. It ships hidden (fm-sections.css owns
    #backToTopBtn.show) and index.js reveals it after the first scroll, so it
    never sits on top of content while the visitor is still at the top of a
    short page.

    Props
        (none)

    Usage
        @include('frontend.fm.partials.overlays.back-to-top')
--}}

<button class="btn btn-brand-primary rounded-full w-10 h-10 d-flex items-center justify-center shadow-lg" id="backToTopBtn" onclick="scrollToTop()" title="Back to top">
    <i class="bi bi-chevron-up"></i>
</button>