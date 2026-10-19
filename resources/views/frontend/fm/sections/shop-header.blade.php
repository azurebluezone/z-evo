{{--
    FM:SECTION shop-header --------------------------------------------------
    Catalog banner above the listing: breadcrumb, page heading and the chips
    that name every filter currently narrowing the result.

    Each chip is a plain link to the same page with exactly one query
    parameter dropped, so removing a filter works with JavaScript off. The
    variables it renders are computed once in pages/products.blade.php.
-------------------------------------------------------------------------- --}}

@php
    // Remove one parameter (or one value of the brand array) from the
    // current URL; null makes Laravel drop the key entirely.
    $fmChipUrl = static function (string $param, mixed $value = null): string {
        return request()->fullUrlWithQuery([$param => $value]);
    };

    $fmChips = [];

    if ($fmTypeLabel !== null) {
        $fmChips[] = ['label' => $fmTypeLabel, 'url' => $fmChipUrl('type')];
    }

    if ($fmActiveCategory !== null) {
        $fmChips[] = ['label' => $fmActiveCategory->name, 'url' => $fmChipUrl('category')];
    }

    foreach ($fmActiveBrands as $fmChipBrand) {
        $fmRemainingBrands = array_values(array_diff($fmBrandSlugs, [$fmChipBrand->slug]));
        $fmChips[] = [
            'label' => $fmChipBrand->name,
            'url'   => $fmChipUrl('brand', $fmRemainingBrands ?: null),
        ];
    }

    if ($fmMinPrice > 0) {
        $fmChips[] = [
            'label' => 'Min Tk ' . number_format($fmMinPrice),
            'url'   => $fmChipUrl('min_price'),
        ];
    }

    if ($fmMaxPrice > 0) {
        $fmChips[] = [
            'label' => 'Max Tk ' . number_format($fmMaxPrice),
            'url'   => $fmChipUrl('max_price'),
        ];
    }

    if ($fmRating > 0) {
        $fmChips[] = [
            'label' => $fmRating . ' stars & up',
            'url'   => $fmChipUrl('rating'),
        ];
    }

    if ($fmInStock) {
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

                    <li class="text-[#1b5e20] font-bold" aria-current="page">{{ $fmHeading }}</li>
                </ol>
            </nav>

          
        </div>

        {{-- Title & subtitle --}}
        <div class="mb-4">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#132116] tracking-tight m-0">
                {{-- A caller that knows better than the query string - the search
                     page, whose heading is the search term - passes
                     $fmHeading in; the query-derived value is the fallback. --}}
                {{ $fmHeadingOverride ?? $fmHeading }}
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
