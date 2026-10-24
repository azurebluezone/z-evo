<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\ShoppingController;
use App\Http\Controllers\Frontend\CustomerController;
use App\Http\Controllers\Frontend\BkashController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderCourierController;
use App\Http\Controllers\Admin\OrderBoardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\ChildcategoryController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\PixelsController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductRecommendationController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ReturnsRefundsController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\ApiIntegrationController;
use App\Http\Controllers\Admin\CourierFraudCheckerController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CourierSettingsController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\BannerCategoryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\PromoBannerController;
use App\Http\Controllers\Admin\CreatePageController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\CustomerManageController;
use App\Http\Controllers\Admin\ShippingChargeController;
use App\Http\Controllers\Admin\ShippingVerificationController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\TagManagerController;
use App\Http\Controllers\Admin\FacebookTrackingController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\HomepageCollectionController;
use App\Http\Controllers\Admin\SocialEventController;
use App\Http\Controllers\Admin\SocialEventDashboardController;
use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\SocialEventPlatformController;
use App\Http\Controllers\Admin\UserSubmissionsController;
use App\Http\Controllers\Admin\ServiceRequestsController;
use App\Http\Controllers\Admin\VisitorManageController;
use App\Http\Controllers\Admin\UiDemoController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\FrontendThemeController;
use App\Http\Controllers\Frontend\EventCollectController;
use App\Http\Controllers\Frontend\ServiceRequestController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Frontend\CouponController as FrontendCouponController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\FlashSaleController as AdminFlashSaleController;
use App\Http\Controllers\Admin\WebsiteSetupController;
use App\Http\Controllers\Admin\HomepageSettingsController;
use App\Http\Controllers\Admin\TikTokTestController;
use App\Http\Controllers\Admin\WishlistController;

// -----------------Landing Page------------------

Route::get('/landing-pages', [\App\Http\Controllers\Frontend\LandingPageController::class, 'index'])->name('landing.index');
Route::get('/landing-pages/{slug}', [\App\Http\Controllers\Frontend\LandingPageController::class, 'show'])->name('landing.show');

// -----------------Landing Page------------------

Auth::routes();

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.submit');

// ---------------------------------------------------------------------------
// NOTE: 15 development/debug closures used to live here (/cc, /controller,
// /1, /h, /kookvai, /product/create, /order/{id}/edit, ...). They had no
// auth middleware and two of them ran Artisan::call() - /cc cleared every
// cache and /controller wrote a file into app/. Removed on 2026-10-18.
// The block is preserved in work/zevo/_removed_dev_routes.txt.
// ---------------------------------------------------------------------------

Route::group(['namespace' => 'Frontend', 'middleware' => ['ipcheck', 'check_refer', 'visitor_identify']], function () {
    Route::get('/', [FrontendController::class, 'frontendHome'])->name('home');
    Route::get('/legacy-home', [FrontendController::class, 'index'])->name('legacy.home');
    Route::get('/frontend-home', [FrontendController::class, 'frontendHome'])->name('frontend.home.preview');

    // Temporary design preview. Session scoped: it changes nothing for anyone
    // else, and it keeps the query string free for real filters such as brand=.
    Route::get('/site/theme/{theme}/preview', function (string $theme, \Illuminate\Http\Request $request) {
        if (! \App\Support\FrontendTheme::startPreview($theme)) {
            return redirect()->route('home');
        }

        $back = (string) $request->query('back', '/');

        if (! str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }

        return redirect()->to($back);
    })->name('site.theme.preview');

    Route::get('/site/theme/clear', function () {
        \App\Support\FrontendTheme::clearPreview();

        $back = request()->query('back', '/');

        if (! is_string($back) || ! str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }

        return redirect()->to($back);
    })->name('site.theme.clear');
    Route::get('/home', [FrontendController::class, 'campaigns'])->name('campaigns');

    Route::post('/landing-order', [ShoppingController::class, 'landingOrder'])->name('landing.order');

    Route::get('/service', [ServiceRequestController::class, 'index'])->name('service.index');
    Route::post('/service', [ServiceRequestController::class, 'store'])->name('service.store');

    Route::get('/home/section-products', [FrontendController::class, 'homeSectionProducts'])->name('home.section-products');

    // Compatibility route names used by migrated frontend blades
    Route::get('products/category/{slug}', [FrontendController::class, 'category'])->name('products.category');
    Route::get('products/show/{slug}', [FrontendController::class, 'details'])->name('products.show');
    Route::get('products/brand/{slug}', [FrontendController::class, 'brand'])->name('products.brand');
    Route::get('category/{category}', [FrontendController::class, 'category'])->name('category');

    Route::get('track-order', [CustomerController::class, 'order_track'])->name('track.order');
    Route::get('track-order/result', [CustomerController::class, 'order_track_result'])->name('track.order.result');
    Route::get('language/{locale}', function ($locale) {
        if (!in_array($locale, ['en', 'bn', 'hi'], true)) {
            $locale = 'en';
        }

        session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()->back();
    })->name('language.change');

    Route::get('products', function () {
        $sortBy = (string) request('sort_by', '');
        $type = (string) request('type', '');

        $query = \App\Models\Product::where('status', 1)
            ->with('image')
            ->withCount('reviews')
            ->withAvg('reviews', 'ratting');

        // Filter by product type (for "See More" buttons from homepage sections)
        if ($type === 'featured') {
            $query->where('feature_product', 1);
        } elseif ($type === 'best-selling') {
            $query->where('topsale', 1);
        } elseif ($type === 'discount') {
            $query->whereNotNull('old_price')
                ->whereRaw('old_price > new_price');
        } elseif ($type === 'recommended') {
            $query->where('is_recommended', 1);
        }

        // ------------------------------------------------------------------
        // Real query-string filters.
        //
        // The classic filter form always sent min_price / max_price / rating,
        // but this route never read them, so the controls were decorative.
        // Both template sets now share the same filtering rules, which keeps
        // the query string free for genuine filters (category=, brand=, ...)
        // instead of design flags.
        // ------------------------------------------------------------------
        $minPrice = (float) request('min_price', 0);
        if ($minPrice > 0) {
            $query->where('new_price', '>=', $minPrice);
        }

        $maxPrice = (float) request('max_price', 0);
        if ($maxPrice > 0) {
            $query->where('new_price', '<=', $maxPrice);
        }

        // A star filter is a floor on the product's AVERAGE rating: "4.5 and
        // up" means the mean of all its reviews is >= 4.5, not "has at least
        // one 4.5 review". Cast to float as well - the sidebar offers 4.5, and
        // the old (int) cast silently collapsed it into the 4.0 bucket.
        $minRating = (float) request('rating', 0);
        if ($minRating > 0 && $minRating <= 5) {
            $query->whereRaw(
                '(SELECT AVG(r.ratting) FROM reviews r WHERE r.product_id = products.id) >= ?',
                [$minRating]
            );
        }

        if (request('in_stock') === '1') {
            $query->where('stock', '>', 0);
        }

        // Category and brand narrow the listing in place; the dedicated
        // /category/{slug} and /products/brand/{slug} pages stay untouched.
        $categorySlug = trim((string) request('category', ''));
        if ($categorySlug !== '') {
            $query->whereIn(
                'category_id',
                \App\Models\Category::where('slug', $categorySlug)->select('id')
            );
        }

        $brandSlugs = array_values(array_filter((array) request('brand')));
        if ($brandSlugs !== []) {
            $query->whereIn(
                'brand_id',
                \App\Models\Brand::whereIn('slug', $brandSlugs)->select('id')
            );
        }

        // Sorting on "price" used to throw SQLSTATE 42S22: the products table
        // has no price column (new_price is the selling price), so every
        // price sort link answered 500. Same options, working column.
        if ($sortBy === 'price_htl') {
            $query->orderBy('new_price', 'desc');
        } elseif ($sortBy === 'price_lth') {
            $query->orderBy('new_price', 'asc');
        } elseif ($sortBy === 'latest') {
            $query->orderBy('id', 'desc');
        } elseif ($sortBy === 'oldest') {
            $query->orderBy('id', 'asc');
        } else {
            $query->latest();
        }

        $perPage = (int) request('per_page', 24);

        // If per_page is very high (like 9999 from "View All Products" button), show all products
        if ($perPage >= 9999) {
            $allProducts = $query->get();
            // Create a paginator-like object for consistency
            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $allProducts,
                count($allProducts),
                $perPage,
                null,
                ['path' => request()->url()]
            );
        } else {
            $products = $query->paginate($perPage)->appends(request()->query());
        }

        // Sidebar counters for the modern listing page: how many active
        // products each category and brand holds. Unfiltered totals, so the
        // numbers stay stable while the visitor narrows the result down.
        $categoryCounts = \App\Models\Product::where('status', 1)
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $brandCounts = \App\Models\Product::where('status', 1)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as total')
            ->groupBy('brand_id')
            ->pluck('total', 'brand_id');

        // Upper bound of the price slider in the sidebar filter.
        $priceCeiling = (int) ceil((float) \App\Models\Product::where('status', 1)->max('new_price'));

        // The rating block of the sidebar only renders while at least one
        // published product carries a review, so a control that could only
        // ever return "no results" is never shown to a visitor.
        $hasReviewData = \App\Models\Product::where('status', 1)->whereHas('reviews')->exists();

        // Departments and brands the sidebar actually loops over. They are
        // derived from the counts above rather than listed wholesale: an
        // earlier version omitted them, and because shop-sidebar falls back
        // to `?? collect()` the department list rendered as nothing at all.
        // Filtering to the categories and brands that own a published product
        // also keeps the twelve empty baby departments out of a grocery shop.
        $sidecategories = \App\Models\Category::where('status', 1)
            ->whereIn('id', $categoryCounts->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $filterBrands = \App\Models\Brand::where('status', 1)
            ->whereIn('id', $brandCounts->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        // The sidebar reads $fmMaxPrice for the slider bound and falls back to
        // $priceCeiling, so both names are supplied with the same value.
        $maxPrice = $priceCeiling;

        return view('frontend.pages.products', compact(
            'products',
            'categoryCounts',
            'brandCounts',
            'priceCeiling',
            'hasReviewData',
            'sidecategories',
            'filterBrands',
            'maxPrice'
        ));
    })->name('products.page');

    Route::get('products/search', [FrontendController::class, 'search'])->name('products.search');

    Route::get('compare-wishlist-cart-date', function () {
        $cartCount = 0;
        $cartSubtotal = 0;
        $wishlistCount = 0;
        try {
            $cart = \Cart::instance('shopping');
            $cartCount = (int) $cart->content()->sum(function ($item) {
                return (int) ($item->qty ?? 0);
            });
            $subtotalRaw = (string) \Cart::instance('shopping')->subtotal();
            $cartSubtotal = (float) str_replace([','], [''], $subtotalRaw);
        } catch (\Throwable $e) {
            $cartCount = 0;
            $cartSubtotal = 0;
        }

        try {
            $wishlist = session()->get('wishlist', []);
            if (! is_array($wishlist)) {
                $wishlist = [];
            }
            $customer = auth('customer')->user();
            if ($customer) {
                $wishlist = array_merge(
                    $wishlist,
                    \App\Models\Wishlist::where('customer_id', $customer->id)->pluck('product_id')->all()
                );
            }
            $wishlistCount = count(array_unique(array_map('intval', $wishlist)));
        } catch (\Throwable $e) {
            $wishlistCount = 0;
        }

        return response()->json([
            'status' => true,
            'data' => [
                'compare_products' => 0,
                'wishlist_products' => $wishlistCount,
                'cart_products' => $cartCount,
                'cart_subtotal' => $cartSubtotal,
            ],
        ]);
    });

    Route::get('wishlist-count', function () {
        $wishlistCount = 0;
        try {
            $wishlist = session()->get('wishlist', []);
            if (! is_array($wishlist)) {
                $wishlist = [];
            }
            $customer = auth('customer')->user();
            if ($customer) {
                $wishlist = array_merge(
                    $wishlist,
                    \App\Models\Wishlist::where('customer_id', $customer->id)->pluck('product_id')->all()
                );
            }
            $wishlistCount = count(array_unique(array_map('intval', $wishlist)));
        } catch (\Throwable $e) {
            $wishlistCount = 0;
        }
        session()->put('wishlist_count', $wishlistCount);
        return response()->json($wishlistCount);
    });

    Route::get('wishlist-short', function () {
        $wishlistIds = [];
        try {
            $wishlist = session()->get('wishlist', []);
            if (! is_array($wishlist)) {
                $wishlist = [];
            }
            $customer = auth('customer')->user();
            if ($customer) {
                $wishlist = array_merge(
                    $wishlist,
                    \App\Models\Wishlist::where('customer_id', $customer->id)->pluck('product_id')->all()
                );
            }
            $wishlistIds = array_values(array_unique(array_map('intval', $wishlist)));
        } catch (\Throwable $e) {
            $wishlistIds = [];
        }

        $products = collect();
        if (!empty($wishlistIds)) {
            try {
                $products = \App\Models\Product::whereIn('id', $wishlistIds)->with('image')->get();
            } catch (\Throwable $e) {
                $products = collect();
            }
        }

        $html = '';
        foreach ($products as $p) {
            $slug = isset($p->slug) ? (string) $p->slug : '';
            $name = isset($p->name) ? (string) $p->name : 'Product';
            $imgRaw = '';
            try {
                $imgRaw = (string) ($p->image?->image ?? '');
            } catch (\Throwable $e) {
                $imgRaw = '';
            }
            $img = !empty($imgRaw) ? asset($imgRaw) : asset('uploads/default/placeholder.png');
            $url = !empty($slug) ? route('product', $slug) : 'javascript:void(0)';
            $pid = (int) ($p->id ?? 0);

            $html .= '<div class="wishlistItem" style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);">';
            $html .= '<a href="' . e($url) . '" style="display:block;width:56px;height:56px;flex:0 0 56px;overflow:hidden;border-radius:6px;background:#fff;">';
            $html .= '<img src="' . e($img) . '" alt="" style="width:100%;height:100%;object-fit:contain;" />';
            $html .= '</a>';
            $html .= '<div style="flex:1;min-width:0;">';
            $html .= '<a href="' . e($url) . '" style="display:block;color:inherit;text-decoration:none;font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . e($name) . '</a>';
            $html .= '</div>';
            $html .= '<button type="button" class="removeWishlist" data-id="' . e((string) $pid) . '" data-pid="' . e((string) $pid) . '" data-page="1" style="border:0;background:transparent;color:#dc3545;font-size:14px;">Remove</button>';
            $html .= '</div>';
        }

        return response($html);
    });

    Route::post('add-to-wishlist/{productId}', function (\Illuminate\Http\Request $request, $productId) {
        $pid = (int) $productId;
        if ($pid < 1) {
            return response()->json(['status' => 'error', 'message' => 'Invalid product']);
        }

        $wishlist = session()->get('wishlist', []);
        if (!is_array($wishlist)) {
            $wishlist = [];
        }

        if (!in_array($pid, $wishlist, true)) {
            $wishlist[] = $pid;
        }

        $wishlist = array_values(array_unique(array_map('intval', $wishlist)));
        session()->put('wishlist', $wishlist);
        session()->put('wishlist_count', count($wishlist));

        // Logged-in customers get a durable row so the admin panel can see
        // it; guests stay session-only (this block is skipped for them).
        $count = count($wishlist);
        $customer = auth('customer')->user();
        if ($customer) {
            \App\Models\Wishlist::firstOrCreate([
                'customer_id' => $customer->id,
                'product_id' => $pid,
            ]);
            $count = count(array_unique(array_map('intval', array_merge(
                $wishlist,
                \App\Models\Wishlist::where('customer_id', $customer->id)->pluck('product_id')->all()
            ))));
            session()->put('wishlist_count', $count);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Added to wishlist',
            'count' => $count,
        ]);
    });

    Route::post('remove-from-wishlist/{productId}', function (\Illuminate\Http\Request $request, $productId) {
        $pid = (int) $productId;
        $wishlist = session()->get('wishlist', []);
        if (!is_array($wishlist)) {
            $wishlist = [];
        }

        $wishlist = array_values(array_filter(array_map('intval', $wishlist), function ($id) use ($pid) {
            return (int) $id !== (int) $pid;
        }));

        // Mirror the removal into the durable row for logged-in customers.
        $customer = auth('customer')->user();
        if ($customer) {
            \App\Models\Wishlist::where('customer_id', $customer->id)
                ->where('product_id', $pid)
                ->delete();
        }

        session()->put('wishlist', $wishlist);
        session()->put('wishlist_count', count($wishlist));

        $count = count($wishlist);
        if ($customer) {
            $count = count(array_unique(array_map('intval', array_merge(
                $wishlist,
                \App\Models\Wishlist::where('customer_id', $customer->id)->pluck('product_id')->all()
            ))));
            session()->put('wishlist_count', $count);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Removed from wishlist',
            'count' => $count,
        ]);
    });

    Route::post('cart-update/{rowId}', function (\Illuminate\Http\Request $request, $rowId) {
        $quantity = (int) $request->input('quantity', 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        try {
            if (!class_exists('Cart')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cart is not available',
                    'quantity' => $quantity,
                ], 400);
            }

            $cart = \Cart::instance('shopping');
            $sumQty = function ($cartInstance) {
                return (int) $cartInstance->content()->sum(function ($item) {
                    return (int) ($item->qty ?? 0);
                });
            };
            $existing = $cart->get($rowId);
            if (!$existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cart item not found',
                    'quantity' => $quantity,
                ], 404);
            }

            $productId = isset($existing->id) ? (int) $existing->id : 0;
            $variantId = null;
            try {
                $variantId = isset($existing->options) && isset($existing->options->variant_id) ? (int) $existing->options->variant_id : null;
                if (!$variantId && isset($existing->options) && isset($existing->options->product_size)) {
                    $ps = (string) $existing->options->product_size;
                    if (preg_match('/variant_id\s*:\s*(\d+)/i', $ps, $m)) {
                        $variantId = (int) $m[1];
                    }
                }
            } catch (\Throwable $e) {
                $variantId = null;
            }

            // Stock validation
            $available = null;
            try {
                if (!empty($variantId)) {
                    $available = \Illuminate\Support\Facades\DB::table('variant_stocks')
                        ->where('variant_id', (int) $variantId)
                        ->value('available');
                    if ($available !== null) {
                        $available = (int) $available;
                    }
                }

                if ($available === null && $productId > 0) {
                    $productStock = \Illuminate\Support\Facades\DB::table('products')
                        ->where('id', (int) $productId)
                        ->value('stock');
                    if ($productStock !== null) {
                        $available = (int) $productStock;
                    }
                }
            } catch (\Throwable $e) {
                $available = null;
            }

            if ($available !== null) {
                if ($available <= 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Out of stock',
                        'quantity' => (int) ($existing->qty ?? 1),
                        'available' => 0,
                        'max_qty' => 0,
                        'cart_count' => $sumQty($cart),
                    ], 422);
                }
                if ($quantity > $available) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Not enough stock',
                        'quantity' => (int) ($existing->qty ?? 1),
                        'available' => $available,
                        'max_qty' => $available,
                        'cart_count' => $sumQty($cart),
                    ], 422);
                }
            }

            $cart->update($rowId, $quantity);

            // If stock known and now exhausted, return hint so frontend can mark as out of stock
            $remaining = null;
            if ($available !== null) {
                $remaining = max(0, $available - $quantity);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cart updated',
                'quantity' => $quantity,
                'remaining' => $remaining,
                'cart_count' => $sumQty($cart),
            ]);
        } catch (\Throwable $e) {
            $fallbackQty = $quantity;
            $fallbackCount = 0;
            try {
                $existing = class_exists('Cart') ? \Cart::instance('shopping')->get($rowId) : null;
                if ($existing && isset($existing->qty)) {
                    $fallbackQty = (int) $existing->qty;
                }
                if (class_exists('Cart')) {
                    $cart = \Cart::instance('shopping');
                    $fallbackCount = (int) $cart->content()->sum(function ($item) {
                        return (int) ($item->qty ?? 0);
                    });
                }
            } catch (\Throwable $ignored) {
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update cart',
                'quantity' => $fallbackQty,
                'cart_count' => $fallbackCount,
            ], 500);
        }
    })->name('cart.update.compat');

    Route::post('remove-from-cart/{rowId}', function (\Illuminate\Http\Request $request, $rowId) {
        try {
            if (!class_exists('Cart')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cart is not available',
                    'cartItemCount' => 0,
                ], 400);
            }

            $cart = \Cart::instance('shopping');
            $sumQty = function ($cartInstance) {
                return (int) $cartInstance->content()->sum(function ($item) {
                    return (int) ($item->qty ?? 0);
                });
            };
            $existing = $cart->get($rowId);
            if (!$existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cart item not found',
                    'cartItemCount' => $sumQty($cart),
                ], 404);
            }

            $cart->remove($rowId);

            return response()->json([
                'status' => 'success',
                'message' => 'Removed from cart',
                'cartItemCount' => $sumQty($cart),
            ]);
        } catch (\Throwable $e) {
            $count = 0;
            try {
                if (class_exists('Cart')) {
                    $cart = \Cart::instance('shopping');
                    $count = (int) $cart->content()->sum(function ($item) {
                        return (int) ($item->qty ?? 0);
                    });
                }
            } catch (\Throwable $ignored) {
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to remove from cart',
                'cartItemCount' => $count,
            ], 500);
        }
    })->name('cart.remove.compat');

    Route::get('categories', function () {
        $categories = \App\Models\Category::where('status', 1)
            ->where('parent_id', 0)
            ->with(['categories' => function ($q) {
                $q->where('status', 1)->with(['categories' => function ($qq) {
                    $qq->where('status', 1);
                }]);
            }])
            ->latest()
            ->get();

        // Map demo view property names: children / child->children
        $categories->each(function ($cat) {
            $cat->setRelation('children', $cat->categories);
            $cat->categories->each(function ($child) {
                $child->setRelation('children', $child->categories);
            });
        });

        // The FM (modern) layout owns this page now: frontend/fm/pages/
        // categories.blade.php, in the design of the product listing. The
        // classic view (frontend/pages/categories.blade.php) is still on disk
        // and is the fallback - change the name below to switch back.
        return view('frontend.fm.pages.categories', compact('categories'));
    })->name('categories.page');

    Route::get('brands', function () {
        return redirect()->route('home');
    })->name('brands.page');

    Route::get('offers', function () {
        return redirect()->route('offers');
    })->name('offers.index');

    Route::get('about-us', function () {
        return view('frontend.pages.about-us');
    })->name('about.us');

    Route::get('cart', function () {
        return redirect()->route('cart.show');
    })->name('cart.index');

    Route::get('wishlist', function () {
        return redirect()->route('home');
    })->name('wishlist');

    Route::get('compare', function () {
        return redirect()->route('home');
    })->name('compare.products');

    Route::post('newsletter/subscribe', [\App\Http\Controllers\Frontend\FormController::class, 'newsletterSubscribe'])
        ->name('newsletter.subscribe');

    Route::match(['get', 'post'], 'subscribe', [\App\Http\Controllers\Frontend\FormController::class, 'newsletterSubscribe'])
        ->name('newsletter.subscribe.compat');

    Route::get('policy/cookie', function () {
        return redirect()->route('home');
    })->name('cookie.policy');

    Route::get('policy/return', function () {
        return redirect()->route('home');
    })->name('return.policy');

    Route::get('policy/privacy', function () {
        return redirect()->route('home');
    })->name('privacy.policy');

    Route::get('terms-and-conditions', function () {
        return redirect()->route('home');
    })->name('terms.conditions');

    Route::get('faq', function () {
        return redirect()->route('home');
    })->name('faq');

    Route::get('subcategory/{subcategory}', [FrontendController::class, 'subcategory'])->name('subcategory');

    // Variant endpoints (must be defined before the catch-all products/{slug} route)
    Route::get('products/{productId}/variant-detail', [FrontendController::class, 'variantDetail'])->name('products.variant-detail');
    Route::get('products/{productId}/variant-images', [FrontendController::class, 'variantImages'])->name('products.variant-images');
    Route::get('products/check-stock', [FrontendController::class, 'checkStock'])->name('products.check-stock');

    Route::get('products/{slug}', [FrontendController::class, 'products'])->name('products');

    Route::get('hot-deals', [FrontendController::class, 'hotdeals'])->name('hotdeals');
    Route::get('test-zombie-cards', function () {
        $products = \App\Models\Product::where('status', 1)->with('image', 'prosizes', 'procolors')->limit(8)->get();
        return view('frontend.layouts.pages.details_new', compact('products'));
    })->name('test.zombie.cards');
    Route::get('livesearch', [FrontendController::class, 'livesearch'])->name('livesearch');
    Route::get('search', [FrontendController::class, 'search'])->name('search');
    Route::get('product/{id}', [FrontendController::class, 'details'])->name('product');
    Route::get('quick-view', [FrontendController::class, 'quickview'])->name('quickview');
    Route::get('/shipping-charge', [FrontendController::class, 'shipping_charge'])->name('shipping.charge');
    Route::get('site/contact-us', [FrontendController::class, 'contact'])->name('contact');
    Route::post('contact-submit', [\App\Http\Controllers\Frontend\FormController::class, 'contactSubmit'])
        ->name('contact.submit');
    Route::get('/page/{slug}', [FrontendController::class, 'page'])->name('page');
    Route::get('districts', [FrontendController::class, 'districts'])->name('districts');
    Route::get('/campaign/{slug}', [FrontendController::class, 'campaign'])->name('campaign');
    Route::get('/offer', [FrontendController::class, 'offers'])->name('offers');

    // cart route
    Route::post('cart/store', [ShoppingController::class, 'cart_store'])->name('cart.store');
    Route::post('cart/store/buy', [ShoppingController::class, 'cart_store_by_now'])->name('cart.store.buy');

    Route::post('apply-coupon', [FrontendCouponController::class, 'apply'])->name('coupon.apply');
    Route::post('remove-coupon', [FrontendCouponController::class, 'remove'])->name('coupon.remove');

    Route::get('cart/sidebar-content', function () {
        return view('frontend.partials.cart-sidebar-popup');
    })->name('cart.sidebar.content');

    Route::get('sidebar-content', function () {
        return view('frontend.partials.cart-sidebar-popup');
    })->name('sidebar.content');

    // Compatibility endpoint for legacy frontend scripts
    Route::get('cart-shortlist', function () {
        return view('frontend.partials.cart-sidebar-popup');
    })->name('cart.shortlist');

    // Line items for the redesigned mini-cart offcanvas. Separate from
    // cart-shortlist so the classic sidebar popup keeps answering the legacy
    // pages completely untouched.
    Route::get('cart/mini-items', function () {
        return view('frontend.fm.partials.cart-items');
    })->name('cart.mini-items');

    Route::get('/add-to-cart/{id}/{qty}', [ShoppingController::class, 'addTocartGet']);

    Route::get('shop/cart', [ShoppingController::class, 'cart_show'])->name('cart.show');
    Route::get('cart/remove', [ShoppingController::class, 'cart_remove'])->name('cart.remove');
    Route::get('cart/count', [ShoppingController::class, 'cart_count'])->name('cart.count');
    Route::get('mobilecart/count', [ShoppingController::class, 'mobilecart_qty'])->name('mobile.cart.count');
    Route::get('cart/decrement', [ShoppingController::class, 'cart_decrement'])->name('cart.decrement');

    Route::get('cart/increment', [ShoppingController::class, 'cart_increment'])->name('cart.increment');
});

Route::post('/events/collect', [EventCollectController::class, 'collect'])->name('events.collect');

Route::group(['prefix' => 'customer', 'namespace' => 'Frontend', 'middleware' => ['ipcheck', 'check_refer', 'visitor_identify']], function () {
    Route::get('/login', [CustomerController::class, 'login'])->name('customer.login');
    Route::post('/signin', [CustomerController::class, 'signin'])->name('customer.signin');
    Route::get('/register', [CustomerController::class, 'register'])->name('customer.register');
    Route::post('/store', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/verify', [CustomerController::class, 'verify'])->name('customer.verify');
    Route::post('/verify-account', [CustomerController::class, 'account_verify'])->name('customer.account.verify');
    Route::post('/resend-otp', [CustomerController::class, 'resendotp'])->name('customer.resendotp');
    Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');
    Route::post('/post/review', [CustomerController::class, 'review'])->name('customer.review');
    Route::get('/forgot-password', [CustomerController::class, 'forgot_password'])->name('customer.forgot.password');
    Route::post('/forgot-verify', [CustomerController::class, 'forgot_verify'])->name('customer.forgot.verify');
    Route::get('/forgot-password/reset', [CustomerController::class, 'forgot_reset'])->name('customer.forgot.reset');
    Route::post('/forgot-password/store', [CustomerController::class, 'forgot_store'])->name('customer.forgot.store');
    Route::post('/forgot-password/resendotp', [CustomerController::class, 'forgot_resend'])->name('customer.forgot.resendotp');
    Route::get('/checkout', [CustomerController::class, 'checkout'])->name('customer.checkout');
    Route::post('/order-save', [CustomerController::class, 'order_save'])->name('customer.ordersave');
    Route::get('/order-success/{id}', [CustomerController::class, 'order_success'])->name('customer.order_success');

    Route::get('/order-track', [CustomerController::class, 'order_track'])->name('customer.order_track');
    Route::get('/order-track/result', [CustomerController::class, 'order_track_result'])->name('customer.order_track_result');
});

// customer auth
Route::group(['prefix' => 'customer', 'namespace' => 'Frontend', 'middleware' => ['customer', 'ipcheck', 'check_refer', 'visitor_identify']], function () {

    Route::get('/account', [CustomerController::class, 'account'])->name('customer.account');

    Route::get('/notifications', [\App\Http\Controllers\Frontend\CustomerNotificationController::class, 'index'])->name('customer.notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Frontend\CustomerNotificationController::class, 'readAll'])->name('customer.notifications.readAll');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Frontend\CustomerNotificationController::class, 'read'])->name('customer.notifications.read');

    Route::get('/orders', [CustomerController::class, 'orders'])->name('customer.orders');
    Route::get('/invoice', [CustomerController::class, 'invoice'])->name('customer.invoice');
    Route::get('/invoice/order-note', [CustomerController::class, 'order_note'])->name('customer.order_note');
    Route::get('/profile-edit', [CustomerController::class, 'profile_edit'])->name('customer.profile_edit');
    Route::post('/profile-update', [CustomerController::class, 'profile_update'])->name('customer.profile_update');
    Route::get('/change-password', [CustomerController::class, 'change_pass'])->name('customer.change_pass');
    Route::post('/password-update', [CustomerController::class, 'password_update'])->name('customer.password_update');
});

Route::group(['namespace' => 'Frontend', 'middleware' => ['ipcheck', 'check_refer', 'visitor_identify']], function () {

    // `bkash/checkout-url/pay` (route name url-pay) was removed on 2026-10-18:
    // BkashController never had a pay() method and nothing referenced the URL, so
    // hitting it returned a 500. The live flow goes straight to url-create
    // (see Frontend\CustomerController, the 'bkash' branch of order_save()).
    Route::any('bkash/checkout-url/create', [BkashController::class, 'create'])->name('url-create');
    Route::get('bkash/checkout-url/callback', [BkashController::class, 'callback'])->name('url-callback');
});

// ajax route
Route::get('/ajax-product-subcategory', [ProductController::class, 'getSubcategory']);
Route::get('/ajax-product-childcategory', [ProductController::class, 'getChildcategory']);

// auth route
Route::group(['namespace' => 'Admin', 'middleware' => ['auth', 'lock', 'check_refer'], 'prefix' => 'admin'], function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    /*
     * The lock screen has to be reachable WHILE the account is locked, so it
     * opts out of LockAccount - otherwise that middleware redirects the lock
     * screen to itself, forever.
     *
     * It also lives inside this group rather than the unauthenticated one:
     * as a public route, any visitor could open the screen and, because it was
     * a GET, a link or an <img> in an email was enough to lock a signed-in
     * admin's own session. Locking is a state change, so it is a POST and
     * carries a CSRF token like every other one.
     */
    Route::post('locked', [DashboardController::class, 'locked'])
        ->name('locked')
        ->withoutMiddleware([\App\Http\Middleware\LockAccount::class]);

    Route::post('unlocked', [DashboardController::class, 'unlocked'])
        ->name('unlocked')
        ->withoutMiddleware([\App\Http\Middleware\LockAccount::class]);

    // Admin logout (separate from customer logout)
    Route::post('logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::get('ui-demo', [UiDemoController::class, 'index'])->name('admin.ui-demo');

    Route::post('quill/upload', [EditorUploadController::class, 'quill'])->name('admin.quill.upload');

    Route::get('inventory', [InventoryController::class, 'index'])->name('admin.inventory.index');
    Route::get('inventory/ledger', [InventoryController::class, 'ledger'])->name('admin.inventory.ledger');
    Route::get('inventory/adjustment', [InventoryController::class, 'adjustment'])->name('admin.inventory.adjustment');
    Route::post('inventory/adjustment', [InventoryController::class, 'adjustmentStore'])->name('admin.inventory.adjustment.store');

    Route::get('variants', [VariantController::class, 'index'])->name('admin.variants.index');
    Route::get('variants/create', [VariantController::class, 'create'])->name('admin.variants.create');
    Route::post('variants', [VariantController::class, 'store'])->name('admin.variants.store');
    Route::get('variants/{id}/edit', [VariantController::class, 'edit'])->name('admin.variants.edit');
    Route::post('variants/{id}', [VariantController::class, 'update'])->name('admin.variants.update');

    Route::get('returns', [ReturnsRefundsController::class, 'returnsIndex'])->name('admin.returns.index');

    // DataTables feed for the grid this screen shows.
    Route::get('returns/datatable', [ReturnsRefundsController::class, 'datatable'])->name('returns.datatable');
    Route::post('returns/{orderId}/approve', [ReturnsRefundsController::class, 'approveReturn'])->name('admin.returns.approve');

    Route::get('refunds', [ReturnsRefundsController::class, 'refundsIndex'])->name('admin.refunds.index');
    Route::post('refunds/{orderId}/mark', [ReturnsRefundsController::class, 'markRefunded'])->name('admin.refunds.mark');

    Route::get('attributes', [AttributeController::class, 'index'])->name('admin.attributes.soon');
    Route::get('attributes/manage', [AttributeController::class, 'index'])->name('admin.attributes.index');
    Route::get('attributes/create', [AttributeController::class, 'create'])->name('admin.attributes.create');
    Route::post('attributes', [AttributeController::class, 'store'])->name('admin.attributes.store');
    Route::get('attributes/{id}/edit', [AttributeController::class, 'edit'])->name('admin.attributes.edit');
    Route::post('attributes/{id}', [AttributeController::class, 'update'])->name('admin.attributes.update');
    Route::get('attributes/{id}/values', [AttributeController::class, 'values'])->name('admin.attributes.values');
    Route::post('attributes/{id}/values', [AttributeController::class, 'valuesStore'])->name('admin.attributes.values.store');
    Route::post('attributes/{id}/destroy', [AttributeController::class, 'destroy'])->name('admin.attributes.destroy');
    Route::post('attributes/{id}/values/{valueId}/destroy', [AttributeController::class, 'valuesDestroy'])->name('admin.attributes.values.destroy');

    Route::get('website-setup', [WebsiteSetupController::class, 'index'])->name('admin.website-setup.index');

    Route::get('website-setup/header', [WebsiteSetupController::class, 'header'])->name('admin.website-setup.header');
    Route::post('website-setup/header', [WebsiteSetupController::class, 'updateHeader'])->name('admin.website-setup.header.update');

    Route::get('website-setup/footer', [WebsiteSetupController::class, 'footer'])->name('admin.website-setup.footer');
    Route::post('website-setup/footer', [WebsiteSetupController::class, 'updateFooter'])->name('admin.website-setup.footer.update');

    Route::get('website-setup/appearance', [WebsiteSetupController::class, 'appearance'])->name('admin.website-setup.appearance');
    Route::post('website-setup/appearance', [WebsiteSetupController::class, 'updateAppearance'])->name('admin.website-setup.appearance.update');

    Route::get('website-setup/seo', [WebsiteSetupController::class, 'seo'])->name('admin.website-setup.seo');
    Route::post('website-setup/seo', [WebsiteSetupController::class, 'updateSeo'])->name('admin.website-setup.seo.update');

    // homepage settings
    Route::get('homepage/settings', [HomepageSettingsController::class, 'edit'])->name('admin.homepage.settings.edit');
    Route::post('homepage/settings', [HomepageSettingsController::class, 'update'])->name('admin.homepage.settings.update');

    // front-end template (design) switch
    Route::get('frontend-theme', [FrontendThemeController::class, 'edit'])->name('admin.frontend-theme.edit');
    Route::post('frontend-theme', [FrontendThemeController::class, 'update'])->name('admin.frontend-theme.update');
    Route::post('frontend-theme/preview', [FrontendThemeController::class, 'preview'])->name('admin.frontend-theme.preview');

    Route::get('prompt', function () {
        return view('backEnd.prompt.index');
    })->name('admin.prompt');

    Route::get('change-password', [DashboardController::class, 'changepassword'])->name('change_password');
    Route::post('new-password', [DashboardController::class, 'newpassword'])->name('new_password');

    // users route 
    Route::get('users/manage', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users/save', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::post('users/update', [UserController::class, 'update'])->name('users.update');
    Route::post('users/inactive', [UserController::class, 'inactive'])->name('users.inactive');
    Route::post('users/active', [UserController::class, 'active'])->name('users.active');
    Route::post('users/destroy', [UserController::class, 'destroy'])->name('users.destroy');

    // roles
    Route::get('roles/manage', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{id}/show', [RoleController::class, 'show'])->name('roles.show');
    Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('roles/save', [RoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::post('roles/update', [RoleController::class, 'update'])->name('roles.update');
    Route::post('roles/destroy', [RoleController::class, 'destroy'])->name('roles.destroy');

    // permissions
    Route::get('permissions/manage', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('permissions/{id}/show', [PermissionController::class, 'show'])->name('permissions.show');
    Route::get('permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('permissions/save', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('permissions/{id}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::post('permissions/update', [PermissionController::class, 'update'])->name('permissions.update');
    Route::post('permissions/destroy', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // categories
    Route::get('categories/manage', [CategoryController::class, 'index'])->name('categories.index');

    // DataTables feed for the grid this screen shows.
    Route::get('categories/manage/datatable', [CategoryController::class, 'datatable'])->name('category.datatable');
    Route::get('categories/{id}/show', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('categories/save', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::post('categories/update', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('categories/inactive', [CategoryController::class, 'inactive'])->name('categories.inactive');
    Route::post('categories/active', [CategoryController::class, 'active'])->name('categories.active');
    Route::post('categories/home-page-on', [CategoryController::class, 'homePageOn'])->name('categories.home-page-on');
    Route::post('categories/home-page-off', [CategoryController::class, 'homePageOff'])->name('categories.home-page-off');
    Route::post('categories/home-page-set-only', [CategoryController::class, 'homePageSetOnly'])->name('categories.home-page-set-only');
    Route::post('categories/destroy', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('categories/bulk-destroy', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');

    // Subcategories
    Route::get('subcategories/manage', [SubcategoryController::class, 'index'])->name('subcategories.index');
    Route::get('subcategories/{id}/show', [SubcategoryController::class, 'show'])->name('subcategories.show');
    Route::get('subcategories/create', [SubcategoryController::class, 'create'])->name('subcategories.create');
    Route::post('subcategories/save', [SubcategoryController::class, 'store'])->name('subcategories.store');
    Route::get('subcategories/{id}/edit', [SubcategoryController::class, 'edit'])->name('subcategories.edit');
    Route::post('subcategories/update', [SubcategoryController::class, 'update'])->name('subcategories.update');
    Route::post('subcategories/inactive', [SubcategoryController::class, 'inactive'])->name('subcategories.inactive');
    Route::post('subcategories/active', [SubcategoryController::class, 'active'])->name('subcategories.active');
    Route::post('subcategories/destroy', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');
    Route::post('subcategories/bulk-destroy', [SubcategoryController::class, 'bulkDestroy'])->name('subcategories.bulk-destroy');

    // Childcategories
    Route::get('childcategories/manage', [ChildcategoryController::class, 'index'])->name('childcategories.index');
    Route::get('childcategories/{id}/show', [ChildcategoryController::class, 'show'])->name('childcategories.show');
    Route::get('childcategories/create', [ChildcategoryController::class, 'create'])->name('childcategories.create');
    Route::post('childcategories/save', [ChildcategoryController::class, 'store'])->name('childcategories.store');
    Route::get('childcategories/{id}/edit', [ChildcategoryController::class, 'edit'])->name('childcategories.edit');
    Route::post('childcategories/update', [ChildcategoryController::class, 'update'])->name('childcategories.update');
    Route::post('childcategories/inactive', [ChildcategoryController::class, 'inactive'])->name('childcategories.inactive');
    Route::post('childcategories/active', [ChildcategoryController::class, 'active'])->name('childcategories.active');
    Route::post('childcategories/destroy', [ChildcategoryController::class, 'destroy'])->name('childcategories.destroy');
    Route::post('childcategories/bulk-destroy', [ChildcategoryController::class, 'bulkDestroy'])->name('childcategories.bulk-destroy');

    // paymentgeteway
    Route::get('paymentgeteway/manage', [ApiIntegrationController::class, 'pay_manage'])->name('paymentgeteway.manage');
    Route::post('paymentgeteway/save', [ApiIntegrationController::class, 'pay_update'])->name('paymentgeteway.update');

    // smsgeteway
    Route::get('smsgeteway/manage', [ApiIntegrationController::class, 'sms_manage'])->name('smsgeteway.manage');
    Route::post('smsgeteway/save', [ApiIntegrationController::class, 'sms_update'])->name('smsgeteway.update');

    // courierapi
    Route::get('courierapi/manage', [ApiIntegrationController::class, 'courier_manage'])->name('courierapi.manage');
    Route::post('courierapi/save', [ApiIntegrationController::class, 'courier_update'])->name('courierapi.update');
    Route::post('courierapi/test-steadfast', [ApiIntegrationController::class, 'testSteadfastConnection'])->name('admin.courier.test.steadfast');
    Route::post('courierapi/test-pathao', [ApiIntegrationController::class, 'testPathaoConnection'])->name('admin.courier.test.pathao');
    Route::post('courierapi/sync-orders', [ApiIntegrationController::class, 'syncOrdersWithCourier'])->name('admin.courierapi.sync.orders');

    // courier fraud checker
    Route::get('courier/fraud-checker', [CourierFraudCheckerController::class, 'index'])->name('admin.courier.fraud-checker');
    Route::post('courier/fraud-checker/check', [CourierFraudCheckerController::class, 'check'])->name('admin.courier.fraud-checker.check');
    Route::post('courier/fraud-checker/override', [CourierFraudCheckerController::class, 'override'])->name('admin.courier.fraud-checker.override');

    // courier settings
    Route::get('courier/settings', [CourierSettingsController::class, 'index'])->name('admin.courier.settings');
    Route::post('courier/steadfast/update', [CourierSettingsController::class, 'updateSteadfast'])->name('admin.courier.steadfast.update');
    Route::post('courier/pathao/update', [CourierSettingsController::class, 'updatePathao'])->name('admin.courier.pathao.update');
    Route::post('courier/sync-orders', [CourierSettingsController::class, 'syncOrdersWithCourier'])->name('admin.courier.sync.orders');
    Route::post('courier/check-phone', [CourierSettingsController::class, 'checkPhoneViaCourier'])->name('admin.courier.check.phone');

    // ---------------------------------------------------------------------
    // courier module: overview, per-provider credentials, live connection
    // test, and the shipments the webhooks have recorded.
    //
    // NOTE THE ORDER: the literal `courier/shipments` routes are declared
    // BEFORE the `courier/{type}/...` ones. Both would match a GET of
    // /admin/courier/shipments, and the first declaration wins - with the
    // wildcard first, "shipments" would be read as a courier type and 404.
    // ---------------------------------------------------------------------
    Route::get('courier', [CourierController::class, 'index'])->name('admin.courier.index');
    Route::get('courier/shipments', [CourierController::class, 'shipments'])->name('admin.courier.shipments');
    Route::get('courier/shipments/{shipment}', [CourierController::class, 'shipment'])->name('admin.courier.shipment');
    Route::get('courier/{type}/edit', [CourierController::class, 'edit'])->name('admin.courier.edit');
    Route::post('courier/{type}/toggle', [CourierController::class, 'toggle'])->name('admin.courier.toggle');
    Route::post('courier/{type}/test', [CourierController::class, 'test'])->name('admin.courier.test');
    Route::post('courier/{type}/rotate-token', [CourierController::class, 'rotateToken'])->name('admin.courier.rotate-token');
    Route::post('courier/{type}', [CourierController::class, 'update'])->name('admin.courier.update');

    // visitors
    Route::get('visitors/manage', [VisitorManageController::class, 'index'])->name('admin.visitors.index');
    Route::get('visitors/list', [VisitorManageController::class, 'list'])->name('admin.visitors.list');
    Route::get('visitors/blocked', [VisitorManageController::class, 'blocked'])->name('admin.visitors.blocked');
    Route::post('visitors/block', [VisitorManageController::class, 'block'])->name('admin.visitors.block');
    Route::post('visitors/unblock', [VisitorManageController::class, 'unblock'])->name('admin.visitors.unblock');
    Route::post('visitors/bulk-block', [VisitorManageController::class, 'bulkBlock'])->name('admin.visitors.bulk-block');
    Route::post('visitors/bulk-unblock', [VisitorManageController::class, 'bulkUnblock'])->name('admin.visitors.bulk-unblock');

    // attribute
    Route::get('orderstatus/manage', [OrderStatusController::class, 'index'])->name('orderstatus.index');
    Route::get('orderstatus/{id}/show', [OrderStatusController::class, 'show'])->name('orderstatus.show');
    Route::get('orderstatus/create', [OrderStatusController::class, 'create'])->name('orderstatus.create');
    Route::post('orderstatus/save', [OrderStatusController::class, 'store'])->name('orderstatus.store');
    Route::get('orderstatus/{id}/edit', [OrderStatusController::class, 'edit'])->name('orderstatus.edit');
    Route::post('orderstatus/update', [OrderStatusController::class, 'update'])->name('orderstatus.update');
    Route::post('orderstatus/inactive', [OrderStatusController::class, 'inactive'])->name('orderstatus.inactive');
    Route::post('orderstatus/active', [OrderStatusController::class, 'active'])->name('orderstatus.active');
    Route::post('orderstatus/destroy', [OrderStatusController::class, 'destroy'])->name('orderstatus.destroy');

    // pixels
    Route::get('pixels/manage', [PixelsController::class, 'index'])->name('pixels.index');
    Route::get('pixels/{id}/show', [PixelsController::class, 'show'])->name('pixels.show');
    Route::get('pixels/create', [PixelsController::class, 'create'])->name('pixels.create');
    Route::post('pixels/save', [PixelsController::class, 'store'])->name('pixels.store');
    Route::get('pixels/{id}/edit', [PixelsController::class, 'edit'])->name('pixels.edit');
    Route::post('pixels/update', [PixelsController::class, 'update'])->name('pixels.update');
    Route::post('pixels/inactive', [PixelsController::class, 'inactive'])->name('pixels.inactive');
    Route::post('pixels/active', [PixelsController::class, 'active'])->name('pixels.active');
    Route::post('pixels/destroy', [PixelsController::class, 'destroy'])->name('pixels.destroy');

    // tag manager
    Route::get('tag-manager/manage', [TagManagerController::class, 'index'])->name('tagmanagers.index');
    Route::get('tag-manager/{id}/show', [TagManagerController::class, 'show'])->name('tagmanagers.show');
    Route::get('tag-manager/create', [TagManagerController::class, 'create'])->name('tagmanagers.create');
    Route::post('tag-manager/save', [TagManagerController::class, 'store'])->name('tagmanagers.store');
    Route::get('tag-manager/{id}/edit', [TagManagerController::class, 'edit'])->name('tagmanagers.edit');
    Route::post('tag-manager/update', [TagManagerController::class, 'update'])->name('tagmanagers.update');
    Route::post('tag-manager/inactive', [TagManagerController::class, 'inactive'])->name('tagmanagers.inactive');
    Route::post('tag-manager/active', [TagManagerController::class, 'active'])->name('tagmanagers.active');
    Route::post('tag-manager/destroy', [TagManagerController::class, 'destroy'])->name('tagmanagers.destroy');

    // attribute
    Route::get('brands/manage', [BrandController::class, 'index'])->name('brands.index');

    // DataTables feed for the grid this screen shows.
    Route::get('brands/manage/datatable', [BrandController::class, 'datatable'])->name('brand.datatable');
    Route::get('brands/{id}/show', [BrandController::class, 'show'])->name('brands.show');
    Route::get('brands/create', [BrandController::class, 'create'])->name('brands.create');

    // homepage collections
    Route::get('homepage/collections/manage', [HomepageCollectionController::class, 'index'])->name('admin.homepage.collections.index');
    Route::get('homepage/collections/create', [HomepageCollectionController::class, 'create'])->name('admin.homepage.collections.create');
    Route::post('homepage/collections/save', [HomepageCollectionController::class, 'store'])->name('admin.homepage.collections.store');
    Route::get('homepage/collections/{id}/edit', [HomepageCollectionController::class, 'edit'])->name('admin.homepage.collections.edit');
    Route::post('homepage/collections/{id}/update', [HomepageCollectionController::class, 'update'])->name('admin.homepage.collections.update');
    Route::post('homepage/collections/inactive', [HomepageCollectionController::class, 'inactive'])->name('admin.homepage.collections.inactive');
    Route::post('homepage/collections/active', [HomepageCollectionController::class, 'active'])->name('admin.homepage.collections.active');
    Route::post('homepage/collections/destroy', [HomepageCollectionController::class, 'destroy'])->name('admin.homepage.collections.destroy');

    // product recommendations
    Route::get('recommendations/manage', [ProductRecommendationController::class, 'index'])->name('admin.recommendations.index');
    Route::get('recommendations/search', [ProductRecommendationController::class, 'search'])->name('admin.recommendations.search');
    Route::post('recommendations/update', [ProductRecommendationController::class, 'update'])->name('admin.recommendations.update');

    // social event manager
    Route::get('social-events', [SocialEventController::class, 'index'])->name('admin.social-events.index');
    Route::get('social-events/dashboard', [SocialEventDashboardController::class, 'index'])->name('admin.social-events.dashboard');
    Route::get('social-events/facebook', [SocialEventPlatformController::class, 'facebook'])->name('admin.social-events.facebook');
    Route::get('social-events/google', [SocialEventPlatformController::class, 'google'])->name('admin.social-events.google');
    Route::get('social-events/tiktok', [SocialEventPlatformController::class, 'tiktok'])->name('admin.social-events.tiktok');
    Route::post('social-events/update', [SocialEventController::class, 'update'])->name('admin.social-events.update');

    // advanced real-time analytics & event attribution dashboard
    Route::get('analytics', [AnalyticsDashboardController::class, 'index'])->name('admin.analytics.index');
    Route::get('analytics/live', [AnalyticsDashboardController::class, 'live'])->name('admin.analytics.live');
    Route::get('analytics/visitor/{visitorId}', [AnalyticsDashboardController::class, 'visitor'])
        ->where('visitorId', '[0-9a-fA-F-]{36}')
        ->name('admin.analytics.visitor');

    // user submissions
    Route::get('user-submissions/newsletter', [UserSubmissionsController::class, 'newsletter'])->name('admin.user-submissions.newsletter');
    Route::get('user-submissions/contacts', [UserSubmissionsController::class, 'contacts'])->name('admin.user-submissions.contacts');

    Route::post('user-submissions/newsletter/{id}/destroy', [UserSubmissionsController::class, 'destroyNewsletter'])->name('admin.user-submissions.newsletter.destroy');
    Route::post('user-submissions/contacts/{id}/destroy', [UserSubmissionsController::class, 'destroyContact'])->name('admin.user-submissions.contacts.destroy');

    // notifications: templates + bulk actions
    Route::get('notification-templates', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'index'])->name('admin.notification-templates.index');
    Route::get('notification-templates/create', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'create'])->name('admin.notification-templates.create');
    Route::post('notification-templates/store', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'store'])->name('admin.notification-templates.store');
    Route::get('notification-templates/{id}/edit', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'edit'])->name('admin.notification-templates.edit');
    Route::post('notification-templates/{id}/update', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'update'])->name('admin.notification-templates.update');
    Route::post('notification-templates/{id}/toggle', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'toggle'])->name('admin.notification-templates.toggle');
    Route::post('notification-templates/{id}/destroy', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'destroy'])->name('admin.notification-templates.destroy');

    Route::post('user-submissions/bulk-delete', [\App\Http\Controllers\Admin\UserSubmissionBulkActionController::class, 'bulkDelete'])->name('admin.user-submissions.bulk-delete');
    Route::post('user-submissions/bulk-notify', [\App\Http\Controllers\Admin\UserSubmissionBulkActionController::class, 'bulkNotify'])->name('admin.user-submissions.bulk-notify');

    Route::get('user-submissions/service-requests', [ServiceRequestsController::class, 'index'])->name('admin.user-submissions.service-requests.index');
    Route::get('user-submissions/service-requests/{id}/show', [ServiceRequestsController::class, 'show'])->name('admin.user-submissions.service-requests.show');
    Route::get('user-submissions/service-requests/{id}/edit', [ServiceRequestsController::class, 'edit'])->name('admin.user-submissions.service-requests.edit');
    Route::post('user-submissions/service-requests/{id}/update', [ServiceRequestsController::class, 'update'])->name('admin.user-submissions.service-requests.update');
    Route::post('user-submissions/service-requests/{id}/status', [ServiceRequestsController::class, 'updateStatus'])->name('admin.user-submissions.service-requests.status');
    Route::post('user-submissions/service-requests/{id}/destroy', [ServiceRequestsController::class, 'destroy'])->name('admin.user-submissions.service-requests.destroy');
    Route::post('brands/save', [BrandController::class, 'store'])->name('brands.store');
    Route::get('brands/{id}/edit', [BrandController::class, 'edit'])->name('brands.edit');
    Route::post('brands/update', [BrandController::class, 'update'])->name('brands.update');
    Route::post('brands/inactive', [BrandController::class, 'inactive'])->name('brands.inactive');
    Route::post('brands/active', [BrandController::class, 'active'])->name('brands.active');
    Route::post('brands/destroy', [BrandController::class, 'destroy'])->name('brands.destroy');

    // color
    Route::get('color/manage', [ColorController::class, 'index'])->name('colors.index');
    Route::get('color/{id}/show', [ColorController::class, 'show'])->name('colors.show');
    Route::get('color/create', [ColorController::class, 'create'])->name('colors.create');
    Route::post('color/save', [ColorController::class, 'store'])->name('colors.store');
    Route::get('color/{id}/edit', [ColorController::class, 'edit'])->name('colors.edit');
    Route::post('color/update', [ColorController::class, 'update'])->name('colors.update');
    Route::post('color/inactive', [ColorController::class, 'inactive'])->name('colors.inactive');
    Route::post('color/active', [ColorController::class, 'active'])->name('colors.active');
    Route::post('color/destroy', [ColorController::class, 'destroy'])->name('colors.destroy');

    // size
    Route::get('size/manage', [SizeController::class, 'index'])->name('sizes.index');
    Route::get('size/{id}/show', [SizeController::class, 'show'])->name('sizes.show');
    Route::get('size/create', [SizeController::class, 'create'])->name('sizes.create');
    Route::post('size/save', [SizeController::class, 'store'])->name('sizes.store');
    Route::get('size/{id}/edit', [SizeController::class, 'edit'])->name('sizes.edit');
    Route::post('size/update', [SizeController::class, 'update'])->name('sizes.update');
    Route::post('size/inactive', [SizeController::class, 'inactive'])->name('sizes.inactive');
    Route::post('size/active', [SizeController::class, 'active'])->name('sizes.active');
    Route::post('size/destroy', [SizeController::class, 'destroy'])->name('sizes.destroy');


    // product
    Route::get('products/manage', [ProductController::class, 'index'])->name('products.index');

    // DataTables feed for the products grid. Declared after 'products/manage'
    // so the two patterns can never be confused for one another.
    Route::get('products/manage/datatable', [ProductController::class, 'datatable'])->name('products.datatable');
    Route::get('products/{id}/show', [ProductController::class, 'show'])->name('admin.products.show');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products/save', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('products/update', [ProductController::class, 'update'])->name('products.update');
    Route::post('products/inactive', [ProductController::class, 'inactive'])->name('products.inactive');
    Route::post('products/active', [ProductController::class, 'active'])->name('products.active');

    // Inline price/stock edit from the product grid. A POST because it writes;
    // it inherits ProductController's permission list, which covers update.
    Route::post('products/quick-update', [ProductController::class, 'quick_update'])->name('products.quick_update');
    Route::post('products/destroy', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('products/image/destroy', [ProductController::class, 'imgdestroy'])->name('products.image.destroy');
    Route::post('products/update-deals', [ProductController::class, 'update_deals'])->name('products.update_deals');
    Route::post('products/update-feature', [ProductController::class, 'update_feature'])->name('products.update_feature');
    Route::post('products/update-status', [ProductController::class, 'update_status'])->name('products.update_status');
    Route::get('products/price-edit', [ProductController::class, 'price_edit'])->name('products.price_edit');
    Route::post('products/price-update', [ProductController::class, 'price_update'])->name('products.price_update');

    // campaign
    Route::get('campaign/manage', [CampaignController::class, 'index'])->name('campaign.index');
    Route::get('campaign/{id}/show', [CampaignController::class, 'show'])->name('campaign.show');
    Route::get('campaign/create', [CampaignController::class, 'create'])->name('campaign.create');
    Route::post('campaign/save', [CampaignController::class, 'store'])->name('campaign.store');
    Route::get('campaign/{id}/edit', [CampaignController::class, 'edit'])->name('campaign.edit');
    Route::post('campaign/update', [CampaignController::class, 'update'])->name('campaign.update');
    Route::post('campaign/inactive', [CampaignController::class, 'inactive'])->name('campaign.inactive');
    Route::post('campaign/active', [CampaignController::class, 'active'])->name('campaign.active');
    Route::post('campaign/destroy', [CampaignController::class, 'destroy'])->name('campaign.destroy');
    Route::get('campaign/image/destroy', [CampaignController::class, 'imgdestroy'])->name('campaign.image.destroy');

    // Landing Pages route 
    Route::get('landing-pages/manage', [LandingPageController::class, 'index'])->name('landing-pages.index');
    Route::get('landing-pages/create', [LandingPageController::class, 'create'])->name('landing-pages.create');
    Route::post('landing-pages/save', [LandingPageController::class, 'store'])->name('landing-pages.store');
    Route::get('landing-pages/{id}/show', [LandingPageController::class, 'show'])->name('landing-pages.show');
    Route::get('landing-pages/{id}/edit', [LandingPageController::class, 'edit'])->name('landing-pages.edit');
    Route::post('landing-pages/update', [LandingPageController::class, 'update'])->name('landing-pages.update');
    Route::post('landing-pages/destroy', [LandingPageController::class, 'destroy'])->name('landing-pages.destroy');
    Route::get('landing-pages/template-config/{id}', [LandingPageController::class, 'templateConfig'])->name('landing-pages.template-config');
    Route::get('landing-pages/templates/{id}/preview', [LandingPageController::class, 'previewTemplate'])->name('landing-pages.templates.preview');

    // settings route 
    Route::get('settings/manage', [GeneralSettingController::class, 'index'])->name('settings.index');
    Route::get('settings/create', [GeneralSettingController::class, 'create'])->name('settings.create');
    Route::post('settings/save', [GeneralSettingController::class, 'store'])->name('settings.store');
    Route::get('settings/{id}/edit', [GeneralSettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings/update', [GeneralSettingController::class, 'update'])->name('settings.update');
    Route::post('settings/inactive', [GeneralSettingController::class, 'inactive'])->name('settings.inactive');
    Route::post('settings/active', [GeneralSettingController::class, 'active'])->name('settings.active');
    Route::post('settings/toggle-top-categories-section', [GeneralSettingController::class, 'toggleTopCategoriesSection'])->name('settings.toggle-top-categories-section');
    Route::post('settings/toggle-brands-section', [GeneralSettingController::class, 'toggleBrandsSection'])->name('settings.toggle-brands-section');
    Route::post('settings/toggle-hot-deal-section', [GeneralSettingController::class, 'toggleHotDealSection'])->name('settings.toggle-hot-deal-section');
    Route::post('settings/destroy', [GeneralSettingController::class, 'destroy'])->name('settings.destroy');

    // Facebook Tracking route 
    Route::get('facebook-tracking', [FacebookTrackingController::class, 'index'])->name('facebook-tracking.index');
    Route::post('facebook-tracking/update', [FacebookTrackingController::class, 'update'])->name('facebook-tracking.update');
    Route::post('facebook-tracking/test', [FacebookTrackingController::class, 'test'])->name('facebook-tracking.test');
    Route::get('facebook-tracking/status', [FacebookTrackingController::class, 'status'])->name('facebook-tracking.status');

    // TikTok Test Routes
    Route::get('tiktok-test', [TikTokTestController::class, 'index'])->name('admin.tiktok-test.index');
    Route::post('tiktok-test/connection', [TikTokTestController::class, 'testConnection'])->name('admin.tiktok-test.connection');
    Route::post('tiktok-test/send-event', [TikTokTestController::class, 'sendTestEvent'])->name('admin.tiktok-test.send-event');

    // settings route 
    Route::get('social-media/manage', [SocialMediaController::class, 'index'])->name('socialmedias.index');
    Route::get('social-media/create', [SocialMediaController::class, 'create'])->name('socialmedias.create');
    Route::post('social-media/save', [SocialMediaController::class, 'store'])->name('socialmedias.store');
    Route::get('social-media/{id}/edit', [SocialMediaController::class, 'edit'])->name('socialmedias.edit');
    Route::post('social-media/update', [SocialMediaController::class, 'update'])->name('socialmedias.update');
    Route::post('social-media/inactive', [SocialMediaController::class, 'inactive'])->name('socialmedias.inactive');
    Route::post('social-media/active', [SocialMediaController::class, 'active'])->name('socialmedias.active');
    Route::post('social-media/destroy', [SocialMediaController::class, 'destroy'])->name('socialmedias.destroy');

    // contact route 
    Route::get('contact/manage', [ContactController::class, 'index'])->name('contact.index');
    Route::get('contact/create', [ContactController::class, 'create'])->name('contact.create');
    Route::post('contact/save', [ContactController::class, 'store'])->name('contact.store');
    Route::get('contact/{id}/edit', [ContactController::class, 'edit'])->name('contact.edit');
    Route::post('contact/update', [ContactController::class, 'update'])->name('contact.update');
    Route::post('contact/inactive', [ContactController::class, 'inactive'])->name('contact.inactive');
    Route::post('contact/active', [ContactController::class, 'active'])->name('contact.active');
    Route::post('contact/destroy', [ContactController::class, 'destroy'])->name('contact.destroy');

    // banner category route 
    Route::get('banner-category/manage', [BannerCategoryController::class, 'index'])->name('banner_category.index');
    Route::get('banner-category/create', [BannerCategoryController::class, 'create'])->name('banner_category.create');
    Route::post('banner-category/save', [BannerCategoryController::class, 'store'])->name('banner_category.store');
    Route::get('banner-category/{id}/edit', [BannerCategoryController::class, 'edit'])->name('banner_category.edit');
    Route::post('banner-category/update', [BannerCategoryController::class, 'update'])->name('banner_category.update');
    Route::post('banner-category/inactive', [BannerCategoryController::class, 'inactive'])->name('banner_category.inactive');
    Route::post('banner-category/active', [BannerCategoryController::class, 'active'])->name('banner_category.active');
    Route::post('banner-category/destroy', [BannerCategoryController::class, 'destroy'])->name('banner_category.destroy');

    // banner  route 
    Route::get('banner/manage', [BannerController::class, 'index'])->name('banners.index');

    // DataTables feed for the grid this screen shows.
    Route::get('banner/manage/datatable', [BannerController::class, 'datatable'])->name('banner.datatable');
    Route::get('banner/create', [BannerController::class, 'create'])->name('banners.create');
    Route::post('banner/save', [BannerController::class, 'store'])->name('banners.store');
    Route::get('banner/{id}/edit', [BannerController::class, 'edit'])->name('banners.edit');
    Route::post('banner/update', [BannerController::class, 'update'])->name('banners.update');
    Route::post('banner/inactive', [BannerController::class, 'inactive'])->name('banners.inactive');
    Route::post('banner/active', [BannerController::class, 'active'])->name('banners.active');
    Route::post('banner/destroy', [BannerController::class, 'destroy'])->name('banners.destroy');

    // promo banner route (home slider bottom)
    Route::get('promo-banner/manage', [PromoBannerController::class, 'index'])->name('promo_banners.index');
    Route::get('promo-banner/create', [PromoBannerController::class, 'create'])->name('promo_banners.create');
    Route::post('promo-banner/save', [PromoBannerController::class, 'store'])->name('promo_banners.store');
    Route::get('promo-banner/{id}/edit', [PromoBannerController::class, 'edit'])->name('promo_banners.edit');
    Route::post('promo-banner/update', [PromoBannerController::class, 'update'])->name('promo_banners.update');
    Route::post('promo-banner/inactive', [PromoBannerController::class, 'inactive'])->name('promo_banners.inactive');
    Route::post('promo-banner/active', [PromoBannerController::class, 'active'])->name('promo_banners.active');
    Route::post('promo-banner/destroy', [PromoBannerController::class, 'destroy'])->name('promo_banners.destroy');

    // Coupon routes
    Route::get('coupon/manage', [AdminCouponController::class, 'index'])->name('coupons.index');

    // DataTables feed for the grid this screen shows.
    Route::get('coupon/manage/datatable', [AdminCouponController::class, 'datatable'])->name('coupon.datatable');
    Route::get('coupon/create', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('coupon/save', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::get('coupon/{id}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
    Route::post('coupon/update', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::post('coupon/inactive', [AdminCouponController::class, 'inactive'])->name('coupons.inactive');
    Route::post('coupon/active', [AdminCouponController::class, 'active'])->name('coupons.active');
    Route::post('coupon/destroy', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

    // Flash Sale routes
    Route::get('flash-sale/manage', [AdminFlashSaleController::class, 'index'])->name('flash-sales.index');
    Route::get('flash-sale/create', [AdminFlashSaleController::class, 'create'])->name('flash-sales.create');
    Route::post('flash-sale/save', [AdminFlashSaleController::class, 'store'])->name('flash-sales.store');
    Route::get('flash-sale/{id}/edit', [AdminFlashSaleController::class, 'edit'])->name('flash-sales.edit');
    Route::post('flash-sale/update', [AdminFlashSaleController::class, 'update'])->name('flash-sales.update');
    Route::post('flash-sale/inactive', [AdminFlashSaleController::class, 'inactive'])->name('flash-sales.inactive');
    Route::post('flash-sale/active', [AdminFlashSaleController::class, 'active'])->name('flash-sales.active');
    Route::post('flash-sale/destroy', [AdminFlashSaleController::class, 'destroy'])->name('flash-sales.destroy');
    Route::get('flash-sale/products/ajax', [AdminFlashSaleController::class, 'getProductsAjax'])->name('flash-sales.products.ajax');

    // contact route 
    Route::get('page/manage', [CreatePageController::class, 'index'])->name('pages.index');
    Route::get('page/create', [CreatePageController::class, 'create'])->name('pages.create');
    Route::post('page/save', [CreatePageController::class, 'store'])->name('pages.store');
    Route::get('page/{id}/edit', [CreatePageController::class, 'edit'])->name('pages.edit');
    Route::post('page/update', [CreatePageController::class, 'update'])->name('pages.update');
    Route::post('page/inactive', [CreatePageController::class, 'inactive'])->name('pages.inactive');
    Route::post('page/active', [CreatePageController::class, 'active'])->name('pages.active');
    Route::post('page/destroy', [CreatePageController::class, 'destroy'])->name('pages.destroy');

    /*
     * The order board. index/cards are reads; move is a POST because a drag
     * between columns is a status change, and status changes run stock
     * arithmetic - the same arithmetic the list view's dropdown runs.
     */
    Route::get('order/board', [OrderBoardController::class, 'index'])->name('admin.order.board');
    Route::get('order/board/cards', [OrderBoardController::class, 'cards'])->name('admin.order.board.cards');
    Route::post('order/board/move', [OrderBoardController::class, 'move'])->name('admin.order.board.move');

    // Pos route
    Route::get('order/create', [OrderController::class, 'order_create'])->name('admin.order.create');
    Route::post('order/store', [OrderController::class, 'order_store'])->name('admin.order.store');
    Route::get('order/cart-add', [OrderController::class, 'cart_add'])->name('admin.order.cart_add');
    Route::get('order/cart-content', [OrderController::class, 'cart_content'])->name('admin.order.cart_content');
    Route::get('order/cart-increment', [OrderController::class, 'cart_increment'])->name('admin.order.cart_increment');
    Route::get('order/cart-decrement', [OrderController::class, 'cart_decrement'])->name('admin.order.cart_decrement');
    Route::get('order/cart-remove', [OrderController::class, 'cart_remove'])->name('admin.order.cart_remove');
    Route::get('order/cart-product-discount', [OrderController::class, 'product_discount'])->name('admin.order.product_discount');
    Route::get('order/cart-extra-discount', [OrderController::class, 'cart_extra_discount'])->name('admin.order.cart_extra_discount');
    Route::get('order/cart-apply-coupon', [OrderController::class, 'cart_apply_coupon'])->name('admin.order.cart_apply_coupon');
    Route::get('order/cart-remove-coupon', [OrderController::class, 'cart_remove_coupon'])->name('admin.order.cart_remove_coupon');
    Route::get('order/cart-details', [OrderController::class, 'cart_details'])->name('admin.order.cart_details');
    Route::get('order/cart-shipping', [OrderController::class, 'cart_shipping'])->name('admin.order.cart_shipping');
    Route::get('order/cart-clear', [OrderController::class, 'cart_clear'])->name('admin.order.cart_clear');

    // Order route 
    Route::get('order/{slug}', [OrderController::class, 'index'])->name('admin.orders');

    // DataTables server-side feed for the orders list.
    //
    // It sits AFTER 'order/{slug}' on purpose. Laravel matches in declaration
    // order, so putting it first would be safe only because the extra path
    // segment makes the pattern not match; declaring it second means that
    // stays true even if the pattern is ever loosened to 'order/{slug?}'.
    Route::get('order/{slug}/datatable', [OrderController::class, 'datatable'])->name('admin.orders.datatable');
    Route::get('invoices', [OrderController::class, 'invoicesIndex'])->name('admin.invoices.index');
    Route::get('order/edit/{invoice_id}', [OrderController::class, 'order_edit'])->name('admin.order.edit');
    Route::post('order/update', [OrderController::class, 'order_update'])->name('admin.order.update');
    Route::get('order/invoice/{invoice_id}', [OrderController::class, 'invoice'])->name('admin.order.invoice');
    Route::get('order/process/{invoice_id}', [OrderController::class, 'process'])->name('admin.order.process');
    Route::post('order/change', [OrderController::class, 'order_process'])->name('admin.order_change');
    Route::post('order/destroy', [OrderController::class, 'destroy'])->name('admin.order.destroy');
    Route::post('order-assign', [OrderController::class, 'order_assign'])->name('admin.order.assign');
    Route::post('order-status', [OrderController::class, 'order_status'])->name('admin.order.status');
    Route::post('order-bulk-destroy', [OrderController::class, 'bulk_destroy'])->name('admin.order.bulk_destroy');
    Route::get('order-print', [OrderController::class, 'order_print'])->name('admin.order.order_print');
    Route::post('bulk-courier/{slug}', [OrderController::class, 'bulk_courier'])->name('admin.bulk_courier');

    // Send ONE order to a courier, as a two step preview-then-confirm.
    // A consignment is irreversible (the courier bills for it and the parcel
    // exists upstream), so the admin sees the exact payload before anything is
    // created. See Admin\OrderCourierController.
    Route::get('order/{order}/courier/{provider}', [OrderCourierController::class, 'show'])
        ->name('admin.order.courier.show');
    Route::post('order/{order}/courier/{provider}', [OrderCourierController::class, 'store'])
        ->name('admin.order.courier.store');
    Route::get('stock-report', [OrderController::class, 'stock_report'])->name('admin.stock_report');
    Route::get('order-report', [OrderController::class, 'order_report'])->name('admin.order_report');
    // Both of these are AJAX endpoints: they read an `order_ids` array out of the
    // request body, and backEnd/order/index.blade.php posts to them with the CSRF
    // token. They were still declared GET, so every click on the Pathao button
    // came back 405 "The POST method is not supported" (verified live).
    Route::post('order-pathao', [OrderController::class, 'order_pathao'])->name('admin.order.pathao');
    Route::post('order-pathao-auto', [OrderController::class, 'order_pathao_auto'])->name('admin.order.pathao.auto');
    Route::get('/pathao-city', [OrderController::class, 'pathaocity'])->name('pathaocity');
    Route::get('/pathao-zone', [OrderController::class, 'pathaozone'])->name('pathaozone');

    // Order route 
    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('review/pending', [ReviewController::class, 'pending'])->name('reviews.pending');
    Route::post('review/inactive', [ReviewController::class, 'inactive'])->name('reviews.inactive');
    Route::post('review/active', [ReviewController::class, 'active'])->name('reviews.active');
    Route::get('review/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('review/save', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('review/{id}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::post('review/update', [ReviewController::class, 'update'])->name('reviews.update');
    Route::post('review/destroy', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Wishlists - per-customer rows written by the frontend add-to-wishlist
    // route closures; guests stay session-only and are invisible here.
    Route::get('wishlist', [WishlistController::class, 'index'])->name('admin.wishlist.index');
    Route::post('wishlist/{id}/destroy', [WishlistController::class, 'destroy'])->name('admin.wishlist.destroy');

    // flavor    // Shipping
    Route::get('shipping/manage', [ShippingChargeController::class, 'index'])->name('shippingcharges.index');
    Route::get('shipping/create', [ShippingChargeController::class, 'create'])->name('shippingcharges.create');
    Route::post('shipping/store', [ShippingChargeController::class, 'store'])->name('shippingcharges.store');
    Route::get('shipping/{id}/edit', [ShippingChargeController::class, 'edit'])->name('shippingcharges.edit');
    Route::post('shipping/update', [ShippingChargeController::class, 'update'])->name('shippingcharges.update');
    Route::post('shipping/inactive', [ShippingChargeController::class, 'inactive'])->name('shippingcharges.inactive');
    Route::post('shipping/active', [ShippingChargeController::class, 'active'])->name('shippingcharges.active');
    Route::post('shipping/destroy', [ShippingChargeController::class, 'destroy'])->name('shippingcharges.destroy');

    // Shipping Verification / Weight Checker
    Route::get('shipping/verification', [ShippingVerificationController::class, 'index'])->name('admin.shipping.verification');
    Route::post('shipping/verification/save', [ShippingVerificationController::class, 'save'])->name('admin.shipping.verification.save');

    // backend customer route 
    Route::get('customer', [CustomerManageController::class, 'index'])->name('customers.index');

    // DataTables feed for the grid this screen shows.
    Route::get('customer/datatable', [CustomerManageController::class, 'datatable'])->name('customers.datatable');
    Route::get('customer/manage', [CustomerManageController::class, 'index'])->name('customers.manage');
    Route::get('customer/{id}/edit', [CustomerManageController::class, 'edit'])->name('customers.edit');
    Route::post('customer/update', [CustomerManageController::class, 'update'])->name('customers.update');
    Route::post('customer/inactive', [CustomerManageController::class, 'inactive'])->name('customers.inactive');
    Route::post('customer/active', [CustomerManageController::class, 'active'])->name('customers.active');
    Route::get('customer/profile', [CustomerManageController::class, 'profile'])->name('customers.profile');
    Route::post('customer/adminlog', [CustomerManageController::class, 'adminlog'])->name('customers.adminlog');
    Route::get('customer/ip-block', [CustomerManageController::class, 'ip_block'])->name('customers.ip_block');
    Route::post('customer/ip-store', [CustomerManageController::class, 'ipblock_store'])->name('customers.ipblock.store');
    Route::post('customer/ip-update', [CustomerManageController::class, 'ipblock_update'])->name('customers.ipblock.update');
    Route::post('customer/ip-destroy', [CustomerManageController::class, 'ipblock_destroy'])->name('customers.ipblock.destroy');
    Route::post('customer/destroy', [CustomerManageController::class, 'destroy'])->name('customers.destroy');
});

// Kookvai route - no gate, direct access
