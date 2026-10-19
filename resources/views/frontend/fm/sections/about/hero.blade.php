{{--
    FM:PARTIAL about/hero
    =========================================================================
    The band that opens the About page: breadcrumb, eyebrow, headline, the
    sentence that states what the shop is, and the two calls to action.

    Props
        fmCategoryCount  int  number of live departments, quoted in the lede

    Usage
        @include('frontend.fm.sections.about.hero', ['fmCategoryCount' => $fmCategoryCount])
--}}

<section class="fm-about__hero">
    <div class="container mx-auto px-4">
        @include('frontend.fm.partials._page-breadcrumb', ['current' => 'About us'])

        <div class="fm-about__hero-inner">
            <span class="fm-about__eyebrow">About Adora</span>
            <h1 class="fm-about__title">
                Baby clothes that fit the year they are meant for
            </h1>
            <p class="fm-about__lede">
                Adora is a baby-fashion retailer in Dhaka. We stock newborn sizes through
                {{ $fmCategoryCount }} departments so a growing child can be dressed in one place, and we
                deliver nationwide with cash on delivery.
            </p>
            <div class="fm-about__cta">
                <a class="btn btn-brand-primary rounded-pill px-4 py-2" href="{{ route('products.page') }}">
                    <i class="bi bi-bag me-1"></i> Browse the catalogue
                </a>
                <a class="btn rounded-pill px-4 py-2 fm-about__cta-alt" href="{{ route('categories.page') }}">
                    See all departments
                </a>
            </div>
        </div>
    </div>
</section>