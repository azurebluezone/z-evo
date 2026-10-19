{{--
    FM:PARTIAL contact/faster-answers
    =========================================================================
    "Faster answers": the two places that answer without waiting for a reply.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.contact.faster-answers')
--}}

<div class="fm-contact__card bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5">
    <h2 class="fm-contact__card-title text-base font-bold text-gray-900 m-0">Faster answers</h2>
    <p class="text-xs text-gray-600 mt-2 mb-3">
        An order already in progress is best handled from its own page - it shows the live status without waiting for a reply.
    </p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm rounded-pill px-3" href="{{ route('track.order') }}">
            <i class="bi bi-search me-1"></i> Track an order
        </a>
        <a class="btn btn-sm rounded-pill px-3" href="{{ route('products.page') }}">
            <i class="bi bi-bag me-1"></i> Browse products
        </a>
    </div>
</div>