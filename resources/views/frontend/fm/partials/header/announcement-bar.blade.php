{{--
    FM:PARTIAL header/announcement-bar
    =========================================================================
    The thin green strip above the header: helpline on the left, the language
    switcher and the WhatsApp shortcut on the right. The two ends stay two ends
    at every width, including phones.

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
    {{-- Two ends, on every width: helpline on the left, language (and the
         WhatsApp shortcut) on the right. The previous version kept both left
         and hid the helpline below md, so the strip had an empty right half and
         a phone visitor saw only the switcher. Neither control is optional
         chrome, so neither drops out now. --}}
    <div class="container mx-auto px-4 flex items-center justify-between gap-3">
        <a class="fm-topbar-contact flex items-center gap-1.5 min-w-0" href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $fmPhone) }}">
            <i class="bi bi-telephone-fill shrink-0"></i>
            <span class="hidden sm:inline shrink-0">Helpline:</span>
            <span class="truncate">{{ $fmPhone ?: 'Add your number' }}</span>
        </a>

        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
            @if(!empty($fmWhatsapp))
                <a aria-label="Chat on WhatsApp" class="fm-topbar-whatsapp inline-flex items-center rounded-lg px-1.5" target="_blank" rel="noopener" href="https://wa.me/{{ $fmWhatsapp }}">
                    <i class="bi bi-whatsapp"></i>
                    <span class="hidden md:inline ms-1">Chat on WhatsApp</span>
                </a>
            @endif

            {{-- Language: the switch route accepts en / bn / hi only. --}}
            <div class="dropdown">
                <button aria-expanded="false" aria-label="Change language"
                        class="fm-topbar-lang btn btn-sm dropdown-toggle inline-flex items-center"
                        data-bs-toggle="dropdown" type="button">
                    <i class="bi bi-translate me-1"></i>{{ $fmLocales[$fmLocale] }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach ($fmLocales as $fmCode => $fmLabel)
                        <li>
                            <a class="dropdown-item {{ $fmCode === $fmLocale ? 'active' : '' }}"
                               href="{{ route('language.change', $fmCode) }}">{{ $fmLabel }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</aside>
<!-- END: AnnouncementBar -->