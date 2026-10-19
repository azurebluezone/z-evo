{{--
    FM:PARTIAL _page-breadcrumb
    =========================================================================
    The "Home / <current>" trail that opens every inner page.

    One partial because about-us and contact carried byte-identical copies of
    it. A third page that needs a trail takes the same partial with a different
    $current rather than pasting the markup again.

    Props
        current  string  label of the page the visitor is on

    Usage
        @include('frontend.fm.partials._page-breadcrumb', ['current' => 'About us'])
--}}

<nav aria-label="Breadcrumb" class="mb-3">
    <ol class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-500 m-0 p-0 list-none">
        <li>
            <a class="hover:text-[#1b5e20] flex items-center gap-1" href="{{ route('home') }}">
                <i class="bi bi-house"></i> Home
            </a>
        </li>
        <li class="text-[#1b5e20] font-bold" aria-current="page">{{ $current }}</li>
    </ol>
</nav>