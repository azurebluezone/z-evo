{{--
    FM PAGE about-us
    =========================================================================
    About page.

    Structure and imagery come from the reference export - three variants exist
    under Downloads/01 and all three share one layout, so the largest (78 KB,
    11 responsive grid breakpoints) was measured as the source: hero, a
    who-we-are split, a stats band, a four-pillar grid, a journey timeline and
    a closing CTA.

    What was NOT taken from it is the wording. That file is written for a
    fictional organic-grocery co-operative and makes claims this shop cannot
    make: "FreshMart", "85+ grower partners", "proprietary optical AI",
    "patented hyperspectral sensors", "regenerative soil". Adora is a
    Bangladesh baby-fashion retailer with 12 categories and 1,240 products;
    printing borrowed claims on an About page is the one page where that is
    plainly a lie rather than a placeholder.

    So every figure below is counted from the database the page already reads,
    and the prose describes what the shop actually is. The numbers are not
    hard-coded: they are queried here, so they stay true as the catalogue grows.

    The classic page this replaces was worse - it described "Visermart", a USA
    storefront, while the shop is Adora in Dhaka.

    To bring the old design back: flip "Front-end Template" in the admin panel
    (Website Setup -> Front-end Template).
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@php
    /*
     * Counts are read from the catalogue rather than written down, so the page
     * cannot drift out of date the way the classic copy ("Visermart ... USA")
     * already had.
     */
    $fmProductCount = (int) \App\Models\Product::where('status', 1)->count();
    $fmCategoryCount = (int) \App\Models\Category::where('status', 1)->count();
    $fmBrandCount = (int) \App\Models\Brand::where('status', 1)->count();

    $fmTopCategories = \App\Models\Category::where('status', 1)
        ->withCount(['products' => fn ($q) => $q->where('status', 1)])
        ->orderByDesc('products_count')
        ->orderBy('name')
        ->limit(6)
        ->get();

    $fmStats = [
        ['value' => number_format($fmProductCount), 'label' => 'Products ready to order', 'icon' => 'bi-bag'],
        ['value' => number_format($fmCategoryCount),  'label' => 'Departments to shop',      'icon' => 'bi-grid'],
        ['value' => number_format($fmBrandCount),     'label' => 'Brands stocked',           'icon' => 'bi-tags'],
        ['value' => 'Dhaka',                            'label' => 'Delivering across Bangladesh', 'icon' => 'bi-truck'],
    ];

    /*
     * The reference's four pillars. Structure kept, subjects replaced: each one
     * is something the storefront actually does, verifiable in the product
     * catalogue or the checkout flow.
     */
    $fmPillars = [
        [
            'icon'  => 'bi-people',
            'title' => 'Every age, sized properly',
            'text'  => 'The catalogue runs from premature newborn sizes through 3-4 years and up, plus the accessories that finish the outfit.',
        ],
        [
            'icon'  => 'bi-box-seam',
            'title' => 'One shop for the whole outfit',
            'text'  => 'Rompers, frocks, T-shirts, dresses and everyday essentials sit under the same account, so a growing child is not split across shops.',
        ],
        [
            'icon'  => 'bi-shield-check',
            'title' => 'Pay the way you already do',
            'text'  => 'Cash on delivery alongside cards and mobile banking, which is how most families here prefer to pay.',
        ],
        [
            'icon'  => 'bi-arrow-counterclockwise',
            'title' => 'Returns that do not need a reason',
            'text'  => 'Anything can come back within seven days. Sizing runs small-to-large, so an exchange is often all it takes.',
        ],
    ];

    /*
     * The reference has a milestone timeline. Nothing on this shop records when
     * it opened or what happened in 2022, and inventing a company history on an
     * About page is not a design decision - it is a false statement about a
     * real business. The band is therefore built on what can be shown instead:
     * the departments, with their real counts.
     */
@endphp

@section('title', 'About us')
@section('meta_description', 'Adora is a Bangladesh baby-fashion retailer: newborn sizes through 3-4 years and up, delivered across the country with cash on delivery.')

@section('content')
    <main class="fm-about" data-purpose="about">

        @include('frontend.fm.sections.about.hero', [
            'fmCategoryCount' => $fmCategoryCount,
        ])

        @include('frontend.fm.sections.about.stats', ['stats' => $fmStats])

        @include('frontend.fm.sections.about.departments', ['categories' => $fmTopCategories])

        @include('frontend.fm.sections.about.pillars', ['pillars' => $fmPillars])

        @include('frontend.fm.sections.about.closing-cta')

    </main>

    @include('frontend.fm.sections.guarantees')
@endsection