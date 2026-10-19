<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * ShopFilterData — the numbers behind the catalog sidebar.
 *
 * WHY THIS EXISTS
 * -------------------------------------------------------------------------
 * FrontendController::withListingSidebar() built categoryCounts, brandCounts,
 * priceCeiling, sidecategories, brands and hasReviewData and handed them to two
 * routes. The sidebar could therefore only ever be rendered by those two
 * routes: any other page that wanted the filter panel had no counters to show
 * and no way to get them.
 *
 * These accessors are what a component can call. They run the same queries
 * withListingSidebar() ran - that method now delegates here, so there is still
 * exactly one definition of each number - and they memoise per request, so a
 * page that renders the sidebar twice does not pay for the queries twice.
 *
 * Counting rule, kept from the controller: only published products count. A
 * filter that can only ever return "no results" is worse than no filter, so
 * the sidebar hides the blocks whose count is zero.
 */
final class ShopFilterData
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    /**
     * Published products per category id.
     */
    public static function categoryCounts(): Collection
    {
        return self::$cache['categoryCounts'] ??= Product::where('status', 1)
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');
    }

    /**
     * Published products per brand id.
     */
    public static function brandCounts(): Collection
    {
        return self::$cache['brandCounts'] ??= Product::where('status', 1)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as total')
            ->groupBy('brand_id')
            ->pluck('total', 'brand_id');
    }

    /**
     * Top of the price slider: the highest published price, rounded up to a
     * whole number. Zero on an empty catalogue, so callers floor it at 1.
     */
    public static function priceCeiling(): int
    {
        return self::$cache['priceCeiling'] ??= (int) ceil(
            (float) Product::where('status', 1)->max('new_price')
        );
    }

    /**
     * Whether any published product has a review yet. The rating filter is
     * hidden until this is true, because until then every value of it returns
     * nothing.
     */
    public static function hasReviewData(): bool
    {
        return self::$cache['hasReviewData'] ??= Product::where('status', 1)
            ->whereHas('reviews')
            ->exists();
    }

    /**
     * Categories that hold at least one published product, alphabetical.
     */
    public static function categories(): Collection
    {
        return self::$cache['categories'] ??= Category::where('status', 1)
            ->whereIn('id', self::categoryCounts()->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Brands that at least one published product uses, alphabetical.
     */
    public static function brands(): Collection
    {
        return self::$cache['brands'] ??= Brand::where('status', 1)
            ->whereIn('id', self::brandCounts()->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Every sidebar number, ready to merge into a controller's view data.
     *
     * maxPrice is the catalogue ceiling under the name the classic views use;
     * keeping both keys is what lets one array feed the FM page and the legacy
     * listing that shares the route.
     */
    public static function forListingSidebar(): array
    {
        $ceiling = self::priceCeiling();

        return [
            'categoryCounts' => self::categoryCounts(),
            'brandCounts'    => self::brandCounts(),
            'sidecategories' => self::categories(),
            'brands'         => self::brands(),
            'priceCeiling'   => $ceiling,
            'maxPrice'       => $ceiling,
            'hasReviewData'  => self::hasReviewData(),
        ];
    }

    /**
     * Forget the memoised numbers. Needed by a test that changes the catalogue
     * mid-request, and by nothing else.
     */
    public static function forget(): void
    {
        self::$cache = [];
    }
}
