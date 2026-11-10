{{--
    FM:SECTION home/categories
    =========================================================================
    "Shop by Category" from the supplied home.html reference.

        DESKTOP  6 columns x 2 rows = 12 icon cards (11 categories + More)
        TABLET / PHONE
                 the grid is hidden and the same 12 cards become a Swiper
                 rail: 6 slides, 2 cards per slide, pagination dots.
                 slidesPerView 2.2 / 3.2 / 4.2 / 4.5 at 320 / 480 / 640 / 768.

    WHY THE ICON PALETTE IS RESOLVED HERE
    -------------------------------------------------------------------------
    The categories table has no icon column, so the reference's Fa icons are
    mapped from the category slug/name to Bootstrap Icons (the set this
    storefront already ships) plus a soft plate colour. Anything unmatched
    falls back to the green grid tile, so a newly added category never renders
    an empty box.

    The desktop and the rail render the same tile partial: one source for the
    icon, colour, URL and label, so the two breakpoints cannot drift.
    =========================================================================
--}}
@php
    $fmCategories = ($featured_categories ?? null);

    if (! $fmCategories || $fmCategories->isEmpty()) {
        $fmCategories = collect($sidecategories ?? []);
    }

    $fmTitle = trim((string) ($sections['category']['title'] ?? ''));
    $fmTitle = $fmTitle !== '' ? $fmTitle : 'Shop by Category';

    /*
     * Reference palettes, rotated onto grocery departments. The keys are
     * checked in order against "<slug> <name>" in lower case; the first hit
     * wins, so "Dairy & Eggs" takes the dairy plate before the generic egg
     * one and "Wild Raw Honey & Pantry" takes honey before pantry.
     */
    $fmIconStyles = [
        'grain'     => ['bi-basket2',      '#fef3c7', '#d97706'],
        'honey'     => ['bi-droplet',      '#fef9c3', '#ca8a04'],
        'bakery'    => ['bi-cup-hot',      '#ffedd5', '#ea580c'],
        'meat'      => ['bi-egg-fried',    '#fee2e2', '#dc2626'],
        'seafood'   => ['bi-water',        '#e0f2fe', '#0284c7'],
        'dairy'     => ['bi-cup',          '#f1f5f9', '#64748b'],
        'egg'       => ['bi-egg',          '#fef9c3', '#ca8a04'],
        'fruit'     => ['bi-apple',        '#dcfce7', '#16a34a'],
        'vegetable' => ['bi-flower2',      '#dcfce7', '#0b6b3a'],
        'sunnah'    => ['bi-flower1',      '#fce7f3', '#db2777'],
        'combo'     => ['bi-box-seam',     '#ede9fe', '#7c3aed'],
        'canvas'    => ['bi-brush',        '#e0f2fe', '#0369a1'],
        'fashion'   => ['bi-bag-heart',    '#dbeafe', '#2563eb'],
        'clock'     => ['bi-clock',        '#e0f2fe', '#0284c7'],
    ];

    $fmResolveIcon = static function (string $fmKey) use ($fmIconStyles): array {
        foreach ($fmIconStyles as $fmNeedle => $fmStyle) {
            if (str_contains($fmKey, $fmNeedle)) {
                return $fmStyle;
            }
        }

        return ['bi-grid-3x3-gap', '#dcfce7', '#0b6b3a'];
    };

    $fmFallbackUrl = asset(\App\Support\MediaPath::fallbackPath());

    // 11 departments + the reference's More tile = 12 (6 x 2).
    $fmTiles = $fmCategories->take(11)->map(static function ($fmCategory) use ($fmResolveIcon, $fmFallbackUrl): array {
        $fmImage = \App\Support\MediaPath::url($fmCategory->image ?? null, 'category');
        $fmStyle = $fmResolveIcon(strtolower(trim((string) $fmCategory->slug . ' ' . (string) $fmCategory->name)));

        return [
            'name'  => (string) $fmCategory->name,
            'url'   => route('category', $fmCategory->slug),
            'icon'  => $fmStyle[0],
            'bg'    => $fmStyle[1],
            'fg'    => $fmStyle[2],
            'image' => $fmImage !== $fmFallbackUrl ? $fmImage : null,
        ];
    })->values();

    $fmTiles->push([
        'name'  => 'More',
        'url'   => route('categories.page'),
        'icon'  => 'bi-grid-3x3-gap-fill',
        'bg'    => '#dcfce7',
        'fg'    => '#0b6b3a',
        'image' => null,
    ]);

    // Two cards per rail slide, exactly like the reference's columns.
    $fmSlides = $fmTiles->chunk(2);
@endphp

@if($fmTiles->isNotEmpty())
    <section class="fm-categories-section" data-purpose="popular-categories" aria-labelledby="fm-categories-title">
        <div class="fm-container">

            <div class="fm-cat-head">
                <h2 class="fm-cat-head__title" id="fm-categories-title">{{ $fmTitle }}</h2>

                <a href="{{ route('categories.page') }}" class="fm-cat-head__link">
                    View All
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            {{-- Desktop: 6 columns x 2 rows ---------------------------------- --}}
            <ul class="fm-cat-grid" role="list">
                @foreach($fmTiles as $fmTile)
                    <li class="fm-cat-item">
                        @include('frontend.fm.sections.home.partials.category-card', ['fmTile' => $fmTile])
                    </li>
                @endforeach
            </ul>

            {{-- Tablet / phone: Swiper rail, 2 cards per slide -------------- --}}
            <div class="fm-cat-swiper swiper"
                 data-fm-cat-swiper
                 role="group"
                 aria-roledescription="carousel"
                 aria-label="{{ $fmTitle }}">
                <div class="swiper-wrapper">
                    @foreach($fmSlides as $fmSlide)
                        <div class="swiper-slide">
                            <div class="fm-cat-slide">
                                @foreach($fmSlide as $fmTile)
                                    @include('frontend.fm.sections.home.partials.category-card', ['fmTile' => $fmTile])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="swiper-pagination"></div>
            </div>

        </div>
    </section>

    @push('css')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    @endpush

    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    @endpush
@endif
