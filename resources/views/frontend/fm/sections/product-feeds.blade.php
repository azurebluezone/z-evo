{{-- FM:SECTION featured-products -------------------------------------------
     Featured / best selling / discount grids all reuse the same partial, so
     the three rails below differ only by their data source.

     No page includes this partial any more (the home page renders
     home/product-rail instead, once per rail). It is kept alive and aligned
     with them: same one card, same home density - so if a page ever includes
     it again it cannot render a second card design.
     --------------------------------------------------------------------- --}}

@php
    $fmFeeds = array_values(array_filter([
        (object) ['key' => 'featured', 'label' => 'Featured', 'title' => 'Handpicked products',     'text' => 'Our best handpicked products',            'items' => $featured_products    ?? null],
        (object) ['key' => 'best',     'label' => 'Best selling', 'title' => 'Top sellers', 'text' => 'Products customers buy the most', 'items' => $best_selling_products ?? null],
        (object) ['key' => 'discount', 'label' => 'Discount', 'title' => 'Discounted products',          'text' => 'Available at a special limited-time discount',  'items' => $discount_products    ?? null],
    ]));
@endphp

@foreach($fmFeeds as $fmFeed)
    @if($fmFeed->items && $fmFeed->items->isNotEmpty())
        <section class="fm-section {{ $loop->first ? 'fm-section--tint' : '' }} fm-feed"
                 id="fm-feed-{{ $fmFeed->key }}" data-purpose="product-feed"
                 data-feed="{{ $fmFeed->key }}">
            <div class="container mx-auto px-4">
                @include('frontend.fm.sections.partials._section-head', [
                    'eyebrow'  => $fmFeed->label,
                    'heading'  => $fmFeed->title,
                    'text'     => $fmFeed->text,
                    'link'     => route('products.page') . '?sort_by=' . ($fmFeed->key === 'best' ? 'best_selling' : 'latest'),
                    'linkText' => 'View all',
                ])

                <x-fm::product-grid
                    card="v2"
                    variant="home"
                    :products="$fmFeed->items->take(8)"
                    :columns="4" />
            </div>
        </section>
    @endif
@endforeach
