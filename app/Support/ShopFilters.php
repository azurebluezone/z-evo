<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * ShopFilters — the catalog filter state of the CURRENT request, parsed once.
 *
 * WHY THIS EXISTS
 * -------------------------------------------------------------------------
 * The same query string was parsed in three views. pages/products.blade.php
 * and pages/search.blade.php each declared ~16 variables
 * ($fmCategorySlug, $fmBrandSlugs, $fmMinPrice, $fmMaxPrice, $fmRating,
 * $fmInStock, $fmType, $fmRouteName, ...) purely so the sidebar and toolbar
 * partials could read them out of the including view's scope. A partial that
 * reads its caller's locals is not a component: every new caller has to
 * re-declare the whole set, and a caller that forgets one gets a silently
 * wrong panel (or a Blade error) instead of a default.
 *
 * So the parse lives here once. The sidebar and toolbar components call
 * current(); a page calls the same method when it needs the same value for a
 * heading. Two readers, one definition, no drift.
 *
 * WHAT IT READS
 * -------------------------------------------------------------------------
 * FrontendController applies the filters the query string names
 * (category, brand[], min_price, max_price, rating, in_stock, type,
 * sort_by, per_page). This class parses exactly those names and nothing else,
 * and it keeps the one piece of routing knowledge the views used to carry:
 *
 *   /category/{slug}, /products/category/{slug} and /products/brand/{slug}
 *   scope the listing through the PATH. FrontendController::category() and
 *   brand() read only their own route parameter and ignore ?category=, so on
 *   those routes the path is the source of truth. Letting the query win there
 *   let a stale ?category= rename the page over the wrong list.
 *
 * NOT A CONTAINER
 * -------------------------------------------------------------------------
 * The counters and the price ceiling that the sidebar also needs come from the
 * database, not from the URL. Those are in ShopFilterData, so this class stays
 * a pure function of the request.
 */
final class ShopFilters
{
    /**
     * One label map for the whole storefront.
     *
     * pages/products.blade.php and pages/search.blade.php each had their own
     * copy and they had already drifted ("Best selling products" vs "Best
     * selling"), which meant the same URL printed two different headings
     * depending on which page rendered it.
     */
    public const TYPE_LABELS = [
        'featured'     => 'Featured products',
        'best-selling' => 'Best selling products',
        'discount'     => 'Discounted products',
        'recommended'  => 'Recommended products',
    ];

    /**
     * Routes where the PATH picks the category and ?category= must be ignored.
     * products.brand is in the list because a brand page has no category at
     * all - the sidebar links departments there instead of posting a radio.
     */
    public const PATH_SCOPED_ROUTES = ['category', 'products.category', 'products.brand'];

    private static ?self $current = null;

    private function __construct(
        public readonly string $routeName,
        public readonly string $categorySlug,
        public readonly array $brandSlugs,
        public readonly float $minPrice,
        public readonly float $maxPrice,
        public readonly int $rating,
        public readonly bool $inStock,
        public readonly string $type,
        public readonly string $sortBy,
        public readonly int $perPage,
    ) {
    }

    /**
     * Parse one request. The only place the query names are spelled out.
     */
    public static function fromRequest(Request $request): self
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        $categorySlug = match ($routeName) {
            'category'          => (string) ($request->route('category') ?? ''),
            'products.category' => (string) ($request->route('slug') ?? ''),
            // A brand page carries a brand, never a category.
            'products.brand'    => '',
            default             => trim((string) $request->query('category', '')),
        };

        $brandSlugs = array_values(array_filter(
            (array) $request->query('brand', []),
            static fn ($slug) => trim((string) $slug) !== ''
        ));

        return new self(
            routeName: $routeName,
            categorySlug: $categorySlug,
            brandSlugs: $brandSlugs,
            minPrice: (float) $request->query('min_price', 0),
            maxPrice: (float) $request->query('max_price', 0),
            rating: (int) $request->query('rating', 0),
            inStock: $request->query('in_stock') === '1',
            type: trim((string) $request->query('type', '')),
            sortBy: (string) $request->query('sort_by', ''),
            perPage: (int) $request->query('per_page', 24),
        );
    }

    /**
     * The current request's filters, parsed at most once per request.
     */
    public static function current(): self
    {
        return self::$current ??= self::fromRequest(request());
    }

    /**
     * Drop the memoised parse. Only the tests and a re-render in the same
     * request need this; a normal request parses one query string once.
     */
    public static function forget(): void
    {
        self::$current = null;
    }

    public function typeLabel(): ?string
    {
        return self::TYPE_LABELS[$this->type] ?? null;
    }

    /**
     * True when the path - not the query - decides the category, so the
     * sidebar has to link to department pages instead of posting a radio.
     */
    public function pathScopedCategories(): bool
    {
        return in_array($this->routeName, self::PATH_SCOPED_ROUTES, true);
    }

    /**
     * "Is the visitor looking at something narrower than the whole shop?"
     * Drives the toolbar's reset button and the drawer trigger's "!" badge.
     *
     * An unknown ?type= deliberately does NOT count: the listing ignores it, so
     * announcing an active filter would be a lie.
     */
    public function hasActive(): bool
    {
        return $this->categorySlug !== ''
            || $this->brandSlugs !== []
            || $this->typeLabel() !== null
            || $this->minPrice > 0
            || $this->maxPrice > 0
            || $this->rating > 0
            || $this->inStock;
    }
}
