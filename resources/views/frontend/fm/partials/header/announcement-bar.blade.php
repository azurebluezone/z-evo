{{--
    FM:PARTIAL header/announcement-bar
    =========================================================================
    The thin green strip above the header: helpline number and the language
    switcher on the left, the WhatsApp shortcut on the right.

    It is its own partial because it is the one chrome block that is rendered
    on EVERY page yet changes the least - both of its values come straight from
    the GeneralSetting record, so a page can never paint a stale number here
    while the rest of the site shows a new one.

    The language switcher sits in this strip for the same reason it sits in the
    classic header's top row: it is site-wide chrome, not page content, and it
    is the one control a visitor may want before they read a single word of the
    page. route('language.change') only puts the choice in the session and
    redirects back, so it needs no JavaScript beyond Bootstrap's dropdown.

    Props
        fmPhone     string  support phone / helpline (blank renders nothing)
        fmWhatsapp  string  digits only, already stripped of + and spaces

    Usage
        @include('frontend.fm.partials.header.announcement-bar')
--}}

@php
    /*
     * The three locales route('language.change') accepts. Anything else is
     * bounced back to English by the route itself, so the list and the route
     * validation are kept in step here.
     */
    $fmLocales = [
        'en' => 'English',
        'bn' => 'Bangla',
        'hi' => 'Hindi',
    ];
    $fmLocale = app()->getLocale();
    if (!isset($fmLocales[$fmLocale])) {
        $fmLocale = 'en';
    }
@endphp

<!-- BEGIN: AnnouncementBar -->
<aside class="bg-[#1b5e20] text-white py-2 text-xs border-b border-green-800" data-purpose="announcement-banner">
    {{-- Three groups, pinned to the two ends: helpline and language on the left,
         the WhatsApp shortcut on the right. They used to share one right-hand
         group, so the switcher and the chat link sat shoulder to shoulder and
         the strip had no left-hand content at all below md (the helpline is
         hidden there). --}}
    <div class="container mx-auto px-4 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
            <span class="hidden md:inline text-green-100">
                <i class="bi bi-telephone-fill me-1"></i> Helpline: {{ $fmPhone ?: 'Add your number' }}
            </span>

            {{-- Language: the switch route accepts en / bn / hi only. --}}
            <div class="dropdown">
                <button aria-expanded="false" class="btn btn-sm dropdown-toggle text-green-100 border-0 bg-transparent inline-flex items-center h-[44px] -my-2 px-3"
                        data-bs-toggle="dropdown" type="button">
                    <i class="bi bi-translate me-1"></i>{{ $fmLocales[$fmLocale] }}
                </button>
                <ul class="dropdown-menu">
                    @foreach ($fmLocales as $fmCode => $fmLabel)
                        <li>
                            <a class="dropdown-item {{ $fmCode === $fmLocale ? 'active' : '' }}"
                               href="{{ route('language.change', $fmCode) }}">{{ $fmLabel }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="flex items-center shrink-0">
            @if(!empty($fmWhatsapp))
                <a class="dropdown-item text-green-100 text-xs py-2" target="_blank" rel="noopener" href="https://wa.me/{{ $fmWhatsapp }}">
                    <i class="bi bi-whatsapp me-1"></i> Chat on WhatsApp
                </a>
            @endif
        </div>
    </div>
</aside>
<!-- END: AnnouncementBar -->