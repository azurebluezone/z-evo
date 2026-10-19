{{--
    FM:SECTION home/categories
    =========================================================================
    Popular Categories - Card style (inspired by Ekomart "Featured Categories")
    Uses existing category data: $featured_categories or fallback $sidecategories.
    =========================================================================
--}}
@php
    $fmCategories = ($featured_categories ?? null);

    if (! $fmCategories || $fmCategories->isEmpty()) {
        $fmCategories = collect($sidecategories ?? []);
    }

    $fmTitle = (string) ($sections['category']['title'] ?? 'Popular categories');
    $fmEyebrow = (string) ($sections['category']['subtitle'] ?? '');
    $fmDesc = (string) ($sections['category']['description'] ?? '');
@endphp

@if($fmCategories->isNotEmpty())
    <section class="fm-categories-section" data-purpose="popular-categories">
        <div class="fm-container">

            @include('frontend.fm.sections.partials._section-head', [
                'eyebrow'   => $fmEyebrow,
                'heading'   => $fmTitle,
                'text'      => $fmDesc,
                'link'      => route('categories.page'),
                'linkText'  => 'All categories',
            ])

            <div class="fm-categories-grid-v2">
                @foreach($fmCategories->take(12) as $fmCategory)
                    @php
                        $fmHasImage = filled($fmCategory->image ?? null);
                        $fmImage = \App\Support\MediaPath::url($fmCategory->image, 'category');
                        $fmProductsCount = $fmCategory->products_count ?? null;
                    @endphp

                    <article class="fm-category-card">
                        <a href="{{ route('category', $fmCategory->slug) }}" class="fm-category-card-link">
                            <div class="fm-category-media">
                                @if($fmHasImage)
                                    <img src="{{ $fmImage }}"
                                         alt="{{ $fmCategory->name }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="300"
                                         height="240">
                                @else
                                    <div class="fm-category-media-fallback">
                                        <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="fm-category-body">
                                <h3 class="fm-category-title">{{ $fmCategory->name }}</h3>
                                @if(!is_null($fmProductsCount))
                                    <p class="fm-category-count">{{ $fmProductsCount }} {{ $fmProductsCount == 1 ? 'Item' : 'Items' }}</p>
                                @else
                                    <p class="fm-category-count">Browse Collection</p>
                                @endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

        </div>
    </section>
@endif