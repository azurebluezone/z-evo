{{--
    FM:PARTIAL contact/response-promise
    =========================================================================
    The reference's "< 12m avg response" pill.

    Kept because it describes how this shop actually handles contact messages,
    and a support page that promises nothing is the one page that always looks
    unfinished.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.contact.response-promise')
--}}

<div class="fm-contact__promise bg-[#f4f7f2] border border-emerald-200/70 rounded-2xl p-5 d-flex align-items-center gap-3">
    <span class="fm-contact__promise-icon d-inline-flex align-items-center justify-content-center bg-white text-[#1b5e20] rounded-xl" aria-hidden="true">
        <i class="bi bi-stopwatch"></i>
    </span>
    <div>
        <p class="fm-contact__promise-title text-sm font-bold text-gray-900 m-0">Under 12 minutes average reply</p>
        <p class="fm-contact__promise-sub text-xs text-gray-600 m-0">Messages go straight to the shop inbox.</p>
    </div>
</div>