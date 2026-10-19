{{--
    FM:PARTIAL contact/intro
    =========================================================================
    Breadcrumb plus the page's own heading and lede.

    The trail itself is the shared partials/_page-breadcrumb, the same one the
    About page opens with - two pages that share a breadcrumb should not each
    carry their own copy of the markup.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.contact.intro')
--}}

@include('frontend.fm.partials._page-breadcrumb', ['current' => 'Contact'])

<div class="fm-contact__intro mb-6">
    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#132116] tracking-tight m-0">Contact us</h1>
    <p class="text-sm text-gray-600 mt-2 mb-0 max-w-2xl">
        Questions about an order, a product or delivery? Send a message and the team replies within a few hours.
    </p>
</div>