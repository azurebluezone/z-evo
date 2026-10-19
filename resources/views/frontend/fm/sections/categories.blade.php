{{-- FM:SECTION categories -------------------------------------------------
     Category tiles. Uses featured_categories when the controller supplied it,
     otherwise falls back to the shared sidecategories so the section never
     disappears on a page that does not prepare its own list.
     --------------------------------------------------------------------- --}}

@php
    $fmCategories = ($featured_categories ?? null);
    if (!$fmCategories || $fmCategories->isEmpty()) {
        $fmCategories = collect($sidecategories ?? [])->take(12);
    }
@endphp

@if($fmCategories->isNotEmpty())
    <section class="fm-section fm-section--tint fm-categories" id="categories-section" data-purpose="category-grid">
        <div class="container mx-auto px-4">
            @include('frontend.fm.sections.partials._section-head', [
                'eyebrow' => 'Browse categories',
                'heading' => 'Popular categories',
                'text'    => 'Pick any category you like.',
                'link'    => route('categories.page'),
                'linkText'=> 'All categories',
            ])

            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3">
                @foreach($fmCategories as $fmCategory)
                    @php
                        $fmCatImage = \App\Support\MediaPath::url($fmCategory->image, 'category');
                        $fmCatHasImage = filled($fmCategory->image ?? null);
                    @endphp

                    <div class="col">
                        <a href="{{ route('category', $fmCategory->slug) }}" class="fm-cat-tile">
                            @php
                                // FreshMart reference tints each category circle a
                                // different soft colour (emerald/red/amber/teal...).
                                $fmCatTints = [
                                    ['bg' => '#e6f2e6', 'fg' => '#1b5e20'],
                                    ['bg' => '#fdecec', 'fg' => '#c62828'],
                                    ['bg' => '#fff4de', 'fg' => '#ef6c00'],
                                    ['bg' => '#e8f3fa', 'fg' => '#0277bd'],
                                    ['bg' => '#f3e8fb', 'fg' => '#7b1fa2'],
                                    ['bg' => '#e0f2f1', 'fg' => '#00695c'],
                                ];
                                $fmCatTint = $fmCatTints[($loop->index ?? 0) % count($fmCatTints)];
                            @endphp
                            <span class="fm-cat-tile__icon" style="background-color: {{ $fmCatTint['bg'] }}; color: {{ $fmCatTint['fg'] }};">
                                @if($fmCatHasImage)
                                    <img src="{{ $fmCatImage }}" alt="{{ $fmCategory->name }}"
                                         loading="lazy" width="72" height="72">
                                @else
                                    <i class="bi bi-grid"></i>
                                @endif
                            </span>
                            <span class="fm-cat-tile__name">{{ $fmCategory->name }}</span>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- FM:SECTION brand-row =======================================
                 Brand strip is pinned to the bottom of the category block, so
                 the brand list stays visually tied to the category grid.
                 Renders nothing while the shop has no published brand image,
                 which keeps the section balanced instead of half empty.
                 ========================================================== --}}
            @php
                $fmBrands = collect($featured_brands ?? [])->filter(function ($brand) {
                    return filled($brand->slug ?? null) && filled($brand->name ?? null);
                });
            @endphp

            @if($fmBrands->isNotEmpty())
                <div class="fm-brand-row" data-purpose="brand-row">
                    @include('frontend.fm.sections.partials._section-head', [
                        'eyebrow'  => 'Trusted brands',
                        'heading'  => 'Shop by brand',
                        'text'     => 'Original products from genuine brands, straight from the source.',
                        'link'     => route('products.page'),
                        'linkText' => 'See all products',
                    ])

                    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3">
                        @foreach($fmBrands->take(6) as $brand)
                            @include('frontend.fm.sections.partials._brand-tile', ['brand' => $brand])
                        @endforeach
                    </div>
                </div>
            @endif
            {{-- /FM:SECTION brand-row --}}
        </div>
    </section>
@endif
