{{--
    FM:COMPONENT shop-header
    =========================================================================
    Catalog banner above a listing: breadcrumb, page heading and one removable
    chip per filter that is currently narrowing the result.

    Each chip is a plain link to the same page with exactly one query parameter
    dropped, so removing a filter works with JavaScript off.

    WHY A COMPONENT
    -------------------------------------------------------------------------
    This used to be sections/shop-header.blade.php, included by the search page
    but written to read TEN variables out of the including view's scope
    ($fmTypeLabel, $fmActiveCategory, $fmActiveBrands, $fmBrandSlugs,
    $fmMinPrice, $fmMaxPrice, $fmRating, $fmInStock, $fmHasActiveFilter,
    $fmHeading - the last two plus three more only so the breadcrumb and the
    chips had something to read). The page it was included from needed a
    twenty-line PHP preamble to keep it from throwing.

    (That sentence used to spell the directive name out, and Blade parsed it
    inside this comment: compileComments turns a comment into a PHP comment
    block BEFORE the statement compilers run, so a bare directive token in a
    comment is real haystack. Measured result: the token matched the compiler's
    PHP-block pattern - which is non-greedy but has no closing-brace guard - up
    to the file's own closing tag, so the whole preamble and the <section>
    below it were swallowed and the page 500'd on an undefined variable while
    php -l stayed clean. Never write a directive name unescaped inside a Blade
    comment: use a phrase, or prefix the token with an extra @.
    work/Narayana Takbeer/blade_comment_compile_check.php proves the file is
    clean now.)

    Now it derives every chip from App\Support\ShopFilters - the same object the
    sidebar and toolbar use, so a chip can never disagree with the panel that
    set the filter - and a page only says what it knows better than the URL:

        <x-fm::shop-header />
        <x-fm::shop-header :heading="$fmSearchHeading" />

    Props (all optional)
        heading     string|null     replace the derived heading (the search page
                                    names the term; the URL cannot say that)
        filters     \App\Support\ShopFilters   default: the current request
        categoryList Collection     default: ShopFilterData::categories()
                                    (used to name the active category)
        brandList   Collection      default: ShopFilterData::brands()
                                    (used to name the active brands)

    The two list props are named that way, not `categories`/`brands`, because a
    component receives the view-SHARED data and AppServiceProvider shares
    `$sidecategories` and `$brands` with a different meaning (every top-level
    category, every brand, unordered). See components/shop-sidebar for the
    measured consequence of confusing the two.
    =========================================================================
--}}

@php
    /** @var \App\Support\ShopFilters $fmFilters */
    $fmFilters = $filters ?? \App\Support\ShopFilters::current();

    $sidecategories = $categoryList ?? \App\Support\ShopFilterData::categories();
    $brands         = $brandList ?? \App\Support\ShopFilterData::brands();

    $fmActiveCategory = $fmFilters->categorySlug !== ''
        ? $sidecategories->firstWhere('slug', $fmFilters->categorySlug)
        : null;

    $fmActiveBrands = $brands
        ->filter(static fn ($brand) => in_array($brand->slug, $fmFilters->brandSlugs, true))
        ->values();

    $fmHasActiveFilter = $fmFilters->hasActive();

    // The heading names the most specific thing that narrows the listing. A
    // caller that knows better - the search page, whose heading is the term -
    // passes it in as `heading`.
    $fmDerivedHeading = ($fmActiveCategory->name ?? null)
        ?? ($fmActiveBrands->isNotEmpty() ? $fmActiveBrands->pluck('name')->join(', ') : null)
        ?? $fmFilters->typeLabel()
        ?? 'All products & groceries';

    $fmPageHeading = isset($heading) && trim((string) $heading) !== ''
        ? (string) $heading
        : $fmDerivedHeading;

    // Remove one parameter (or one value of the brand array) from the current
    // URL; null makes Laravel drop the key entirely.
    $fmChipUrl = static function (string $param, mixed $value = null): string {
        return request()->fullUrlWithQuery([$param => $value]);
    };

    $fmChips = [];

    if ($fmFilters->typeLabel() !== null) {
        $fmChips[] = ['label' => $fmFilters->typeLabel(), 'url' => $fmChipUrl('type')];
    }

    if ($fmActiveCategory !== null) {
        $fmChips[] = ['label' => $fmActiveCategory->name, 'url' => $fmChipUrl('category')];
    }

    foreach ($fmActiveBrands as $fmChipBrand) {
        $fmRemainingBrands = array_values(array_diff($fmFilters->brandSlugs, [$fmChipBrand->slug]));
        $fmChips[] = [
            'label' => $fmChipBrand->name,
            'url'   => $fmChipUrl('brand', $fmRemainingBrands ?: null),
        ];
    }

    if ($fmFilters->minPrice > 0) {
        $fmChips[] = [
            'label' => 'Min Tk ' . number_format($fmFilters->minPrice),
            'url'   => $fmChipUrl('min_price'),
        ];
    }

    if ($fmFilters->maxPrice > 0) {
        $fmChips[] = [
            'label' => 'Max Tk ' . number_format($fmFilters->maxPrice),
            'url'   => $fmChipUrl('max_price'),
        ];
    }

    if ($fmFilters->rating > 0) {
        $fmChips[] = [
            'label' => $fmFilters->rating . ' stars & up',
            'url'   => $fmChipUrl('rating'),
        ];
    }

    if ($fmFilters->inStock) {
        $fmChips[] = [
            'label' => 'In stock only',
            'url'   => $fmChipUrl('in_stock'),
        ];
    }
@endphp

<section class="bg-[#f4f7f2] border-b border-gray-200/70 py-5 sm:py-6" data-section="shop-header">
    <div class="container mx-auto px-4">

        {{-- Breadcrumb + freshness badge --}}
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <nav aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-500 m-0 p-0 list-none">
                    <li>
                        <a class="hover:text-[#1b5e20] flex items-center gap-1" href="{{ route('home') }}">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li aria-hidden="true">/</li>

                    @if($fmHasActiveFilter)
                        <li>
                            <a class="hover:text-[#1b5e20]" href="{{ route('products.page') }}">All products</a>
                        </li>
                        <li aria-hidden="true">/</li>
                    @endif

                    <li class="text-[#1b5e20] font-bold" aria-current="page">{{ $fmPageHeading }}</li>
                </ol>
            </nav>

          
        </div>

        {{-- Title & subtitle --}}
        <div class="mb-4">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#132116] tracking-tight m-0">
                {{ $fmPageHeading }}
            </h1>
       
        </div>

        {{-- Active filter chips: one link per filter, each drops itself --}}
        @if(count($fmChips) > 0)
            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                <span class="text-gray-500 font-bold uppercase tracking-wider text-xs">Active filters:</span>

                @foreach($fmChips as $fmChip)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1b5e20] font-semibold border border-emerald-200/80 shadow-xs">
                        {{ $fmChip['label'] }}
                        <a class="text-gray-400 hover:text-red-600 flex items-center"
                           href="{{ $fmChip['url'] }}"
                           title="Remove this filter"
                           aria-label="Remove this filter">
                            <i class="bi bi-x-sm"></i>
                        </a>
                    </span>
                @endforeach

                <a class="text-red-600 hover:underline font-semibold ml-1" href="{{ route('products.page') }}">Clear all</a>
            </div>
        @endif

    </div>
</section>
