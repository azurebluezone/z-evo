<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Childcategory;
use App\Models\Product;
use App\Models\District;
use App\Models\Brand;
use App\Models\CreatePage;
use App\Models\Campaign;
use App\Models\Banner;
use App\Models\ShippingCharge;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\HomepageCollection;
use App\Models\Customer;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Review;
use App\Models\PromoBanner;
use App\Models\FlashSale;
use App\Models\Service;
use App\Models\GeneralSetting;
use App\Support\ShopFilterData;
use Illuminate\Support\Facades\DB;
use Session;
use Cart;
use Auth;

class FrontendController extends Controller
{
    private function applyProductListingSort($query, Request $request)
    {
        $sortBy = (string) $request->query('sort_by', '');

        // Sort on new_price, the column the /products route already sorts on
        // (routes/web.php) and the only selling price the products table has:
        // the columns are purchase_price, old_price and new_price. This used to
        // say 'price', which does not exist, so every price sort on the routes
        // below returned 500 instead of a sorted list - classic and modern
        // alike. latest/oldest sort on id, exactly like /products.
        if ($sortBy === 'price_htl') {
            return $query->orderBy('new_price', 'desc');
        }

        if ($sortBy === 'price_lth') {
            return $query->orderBy('new_price', 'asc');
        }

        if ($sortBy === 'latest') {
            return $query->orderBy('id', 'desc');
        }

        if ($sortBy === 'oldest') {
            return $query->orderBy('id', 'asc');
        }

        return $query;
    }

    /**
     * Sidebar data every catalog listing renders.
     *
     * /products built these inline in routes/web.php while category(), brand(),
     * the child-category and subcategory actions passed none of them, so the
     * modern sidebar on those routes displayed a category counter of 0 for a
     * category holding 9 products and gave the price slider a ceiling of
     * max="1". One definition keeps the numbers identical everywhere.
     *
     * sidecategories, brands, brandCounts and maxPrice are also supplied here.
     * shop-sidebar loops over all four, and because the partial falls back to
     * `?? collect()` an absent value renders no rows at all rather than
     * failing: the department list and the brand list came out blank instead
     * of showing the shop's own catalogue. Only categories and brands that
     * have at least one published product are listed, so a filter can never
     * offer a choice whose only outcome is "no results".
     */
    private function withListingSidebar(array $data): array
    {
        // The seven numbers live in App\Support\ShopFilterData, which the
        // sidebar/toolbar components also read. Keeping the queries there means
        // the panel renders the same counters whether the controller prepared
        // the route or the component had to fetch them itself.
        return array_merge($data, ShopFilterData::forListingSidebar());
    }

    public function campaign($slug)
    {
        $generalsetting = GeneralSetting::first();

        $campaign = Campaign::with(['template', 'product', 'images'])
            ->where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        $templateView = null;
        if ($campaign->template && $campaign->template->view_name) {
            $templateView = 'templates.' . $campaign->template->view_name;
        }
        if (!$templateView || !view()->exists($templateView)) {
            $templateView = 'templates.default';
        }

        $landingPage = (object) [
            'title' => $campaign->name,
            'subtitle' => $campaign->short_description,
            'description' => $campaign->description,
            'cta_text' => $campaign->hero_button_text ?? 'অর্ডার করুন',
            'cta_link' => route('landing.order'),
            'image_url' => $campaign->image_one,
            'extra_fields' => [
                'old_price' => optional($campaign->product)->old_price,
                'new_price' => optional($campaign->product)->new_price,
                'countdown_date' => $campaign->end_date ? $campaign->end_date->format('Y-m-d H:i:s') : null,
                'features' => [],
                'why_us' => [],
                'contact_phone' => $campaign->phone ?? ($generalsetting->phone ?? null),
                'product_id' => $campaign->product_id,
            ],
        ];

        return view($templateView, compact('landingPage', 'campaign'));
    }

    public function checkStock(Request $request)
    {
        $ids = (array) $request->input('variant', []);
        $ids = array_values(array_filter(array_map(function ($v) {
            $v = is_scalar($v) ? (string) $v : '';
            $v = trim($v);
            return ctype_digit($v) ? (int) $v : null;
        }, $ids), function ($v) {
            return $v !== null;
        }));

        if (count($ids) < 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select variants',
                'sku' => null,
            ]);
        }

        $idsSorted = $ids;
        sort($idsSorted);

        $productId = (int) $request->input('product_id', 0);
        if ($productId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid product',
                'sku' => null,
            ]);
        }

        $product = Product::where('status', 1)->where('id', $productId)->first();
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
                'sku' => null,
            ]);
        }

        $variants = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get(['id', 'sku', 'price_override', 'discount_price', 'image', 'attributes_json']);

        $matched = null;
        foreach ($variants as $v) {
            if (empty($v->attributes_json)) {
                continue;
            }
            $decoded = json_decode((string) $v->attributes_json, true);
            if (!is_array($decoded)) {
                continue;
            }
            $idsMap = $decoded['ids'] ?? null;
            if (!is_array($idsMap) || empty($idsMap)) {
                continue;
            }
            $variantIds = array_values(array_filter(array_map(function ($x) {
                return (is_numeric($x) ? (int) $x : null);
            }, $idsMap), function ($x) {
                return $x !== null;
            }));
            sort($variantIds);
            if ($variantIds === $idsSorted) {
                $matched = $v;
                break;
            }
        }

        if (!$matched) {
            return response()->json([
                'status' => 'error',
                'message' => 'Variant not available',
                'sku' => 'Not Available',
                'track_inventory' => true,
                'stock_quantity' => 0,
                'show_stock' => false,
                'price' => null,
                'formatted_price' => '<span>৳ ' . e($product->new_price ?? '') . '</span>',
            ]);
        }

        $stock = DB::table('variant_stocks')->where('variant_id', (int) $matched->id)->first(['available']);
        $available = (int) ($stock->available ?? 0);

        $reserved = (int) DB::table('inventory_reservations')
            ->where('variant_id', (int) $matched->id)
            ->where('expires_at', '>', now())
            ->sum('qty');
        $availableToPromise = max(0, $available - $reserved);

        $price = $matched->price_override !== null ? (float) $matched->price_override : (float) ($product->new_price ?? 0);

        /*
         * B10: a variant-level discount_price, when set and lower than the base
         * price, IS the selling price; the base price becomes the struck old
         * price (mirroring products.old_price -> products.new_price). Without
         * this the discount_price column was written but never read.
         */
        $variantDiscount = $matched->discount_price !== null && $matched->discount_price !== ''
            ? (float) $matched->discount_price
            : null;
        $basePrice = $price;
        if ($variantDiscount !== null && $variantDiscount < $price) {
            $price = $variantDiscount;
        }

        $oldOk = ($variantDiscount !== null && $variantDiscount < $basePrice)
            ? true
            : (!empty($product->old_price) && (float) $product->old_price > $price);
        $oldShown = ($variantDiscount !== null && $variantDiscount < $basePrice)
            ? $basePrice
            : (float) ($product->old_price ?? 0);
        $formatted = '৳ ' . $price;
        if ($oldOk) {
            $formatted .= ' <del>৳ ' . e($oldShown) . '</del>';
        }

        return response()->json([
            'status' => 'success',
            'message' => null,
            'sku' => $matched->sku,
            'track_inventory' => true,
            'stock_quantity' => $availableToPromise,
            'show_stock' => false,
            'price' => $price,
            'formatted_price' => $formatted,
        ]);
    }

/**
     * Resolve a whole variant in ONE request.
     *
     * The detail page used to need two round trips: products/{id}/variant-images
     * for the photo and products/check-stock for price and stock. Both resolved
     * the same variant separately, so the picture could arrive from one request
     * while the price came from another, and a shopper could momentarily see a
     * photo that did not belong to the price on screen. A variant is one thing:
     * photo, price, sku, stock and the chosen attribute values belong together,
     * so they are answered together here.
     *
     * Response shape:
     *   found false                  -> nothing matches this combination
     *   found true, variant { ... }  -> the complete bundle
     *
     * @param  int  $productId
     */
    public function variantDetail($productId, Request $request)
    {
        $productId = (int) $productId;

        // The storefront JS sends a JSON string (`[1,4]`), but a hand-written
        // URL may send real array syntax (`attribute_values[]=1&...`). Casting
        // an array to string raised "Array to string conversion" and returned a
        // 500 HTML page instead of JSON, so accept both shapes explicitly.
        $rawValues = $request->input('attribute_values', '[]');
        if (is_array($rawValues)) {
            $attributeValues = $rawValues;
        } else {
            $attributeValues = json_decode((string) $rawValues, true);
        }
        $attributeValues = is_array($attributeValues) ? $attributeValues : [];
        $attributeValues = array_values(array_filter(array_map(function ($v) {
            $v = is_scalar($v) ? (string) $v : '';
            $v = trim($v);

            return ctype_digit($v) ? (int) $v : null;
        }, $attributeValues), function ($v) {
            return $v !== null;
        }));

        $product = Product::where('status', 1)->where('id', $productId)->with('images')->first();
        if (! $product) {
            return response()->json(['found' => false, 'reason' => 'product_not_found']);
        }

        $basePrice = (float) ($product->new_price ?? 0);

        // No selection yet: answer with the product itself so the page can
        // fall back to the default variant without a second guess.
        if (count($attributeValues) < 1) {
            return response()->json([
                'found' => false,
                'reason' => 'no_selection',
                'product' => [
                    'image' => \App\Support\MediaPath::product($product),
                    'price' => $basePrice,
                    'sku' => (string) ($product->sku ?: ''),
                ],
            ]);
        }

        $wanted = $attributeValues;
        sort($wanted);

        $variants = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get(['id', 'sku', 'title', 'image', 'price_override', 'discount_price', 'attributes_json']);

        $matched = null;
        foreach ($variants as $v) {
            if (empty($v->attributes_json)) {
                continue;
            }
            $decoded = json_decode((string) $v->attributes_json, true);
            $idsMap = is_array($decoded) ? ($decoded['ids'] ?? null) : null;
            if (!is_array($idsMap) || empty($idsMap)) {
                continue;
            }
            $variantIds = array_values(array_filter(array_map(function ($x) {
                return is_numeric($x) ? (int) $x : null;
            }, $idsMap), function ($x) {
                return $x !== null;
            }));
            sort($variantIds);
            if ($variantIds === $wanted) {
                $matched = $v;
                break;
            }
        }

        if (!$matched) {
            return response()->json(['found' => false, 'reason' => 'combination_unavailable']);
        }

        $stock = DB::table('variant_stocks')->where('variant_id', (int) $matched->id)->first(['available']);
        $available = (int) ($stock->available ?? 0);

        $reserved = (int) DB::table('inventory_reservations')
            ->where('variant_id', (int) $matched->id)
            ->where('expires_at', '>', now())
            ->sum('qty');

        $availableToPromise = max(0, $available - $reserved);

        $basePriceVariant = $matched->price_override !== null && $matched->price_override !== ''
            ? (float) $matched->price_override
            : $basePrice;

        $price = $basePriceVariant;

        /*
         * B10: same discount rule as checkStock - a set discount_price lower
         * than the base becomes the selling price, and the base becomes the
         * struck old_price the JS renders. The variant's own old price wins
         * over the product-level one so the save badge is accurate per pick.
         */
        $variantDiscount = $matched->discount_price !== null && $matched->discount_price !== ''
            ? (float) $matched->discount_price
            : null;
        if ($variantDiscount !== null && $variantDiscount < $basePriceVariant) {
            $price = $variantDiscount;
        }

        $productOldPrice = (float) ($product->old_price ?? 0);
        $hasVariantSaving = $variantDiscount !== null && $variantDiscount < $basePriceVariant;
        $oldPrice = $hasVariantSaving ? $basePriceVariant : $productOldPrice;
        $hasSaving = $oldPrice > 0 && $oldPrice > $price;

        // Turn the stored attribute map back into labels the page can show on
        // the swatch ("Colour: Forest Green"), so a click can state the choice
        // in words and not only as a colour chip.
        $decoded = json_decode((string) $matched->attributes_json, true);
        $attrs = [];
        $ids = $decoded['ids'] ?? [];
        $labels = $decoded['attrs'] ?? [];
        if (is_array($ids)) {
            foreach ($ids as $slug => $valueId) {
                $value = DB::table('attribute_values')->where('id', (int) $valueId)->first();
                $attrs[] = [
                    'slug' => (string) $slug,
                    'label' => $value->value ?? ($labels[$slug] ?? ''),
                    'value_id' => (int) $valueId,
                ];
            }
        }

        return response()->json([
            'found' => true,
            'variant' => [
                'id' => (int) $matched->id,
                'sku' => $matched->sku,
                'title' => $matched->title,
                'image' => $matched->image
                    ? \App\Support\MediaPath::url($matched->image, 'product')
                    : \App\Support\MediaPath::product($product),
                'price' => $price,
                'old_price' => $hasSaving ? $oldPrice : null,
                'formatted_price' => 'Tk ' . $price,
                'stock_quantity' => $availableToPromise,
                'in_stock' => $availableToPromise > 0,
                'attributes' => $attrs,
            ],
        ]);
    }

    public function variantImages($productId, Request $request)
    {
        $productId = (int) $productId;
        $attributeValues = json_decode((string) $request->input('attribute_values', '[]'), true);
        $attributeValues = is_array($attributeValues) ? $attributeValues : [];
        $attributeValues = array_values(array_filter(array_map(function ($v) {
            $v = is_scalar($v) ? (string) $v : '';
            $v = trim($v);
            return ctype_digit($v) ? (int) $v : null;
        }, $attributeValues), function ($v) {
            return $v !== null;
        }));

        $product = Product::where('status', 1)->where('id', $productId)->with('images')->first();
        if (!$product) {
            return response()->json(['error' => true]);
        }

        if (count($attributeValues) < 1) {
            $mainImageRaw = (string) ($product->thumbnail_image ?? optional($product->image)->image ?? '');
            $mainImagePath = preg_replace('~^public/~', '', $mainImageRaw);
            $mainImagePath = ltrim((string) $mainImagePath, '/');
            if (!empty($mainImagePath) && !str_contains($mainImagePath, '/') && !str_contains($mainImagePath, '\\')) {
                $mainImagePath = 'uploads/product/' . $mainImagePath;
            }
            $mainImageUrl = !empty($mainImagePath) ? asset($mainImagePath) : asset('uploads/default/placeholder.png');
            $mainImageOnError = "this.onerror=null;this.src='" . asset('uploads/default/no-image.png') . "';";

            $imagesHtml = '<div class="xzoom-container product-gallery">'
                . '<img class="xzoom5" id="xzoom-magnific" src="' . e($mainImageUrl) . '" xoriginal="' . e($mainImageUrl) . '" onerror="' . e($mainImageOnError) . '" width="600" height="600" style="width:100%;height:auto;aspect-ratio:1/1;object-fit:contain;" />'
                . '</div>';

            return response()->json([
                'error' => false,
                'media_ids' => ['default'],
                'images' => $imagesHtml,
            ]);
        }

        $idsSorted = $attributeValues;
        sort($idsSorted);

        $variants = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get(['id', 'image', 'attributes_json']);

        $matchedImage = null;
        foreach ($variants as $v) {
            if (empty($v->attributes_json)) {
                continue;
            }
            $decoded = json_decode((string) $v->attributes_json, true);
            if (!is_array($decoded)) {
                continue;
            }
            $idsMap = $decoded['ids'] ?? null;
            if (!is_array($idsMap) || empty($idsMap)) {
                continue;
            }
            $variantIds = array_values(array_filter(array_map(function ($x) {
                return (is_numeric($x) ? (int) $x : null);
            }, $idsMap), function ($x) {
                return $x !== null;
            }));
            sort($variantIds);
            if ($variantIds === $idsSorted) {
                $matchedImage = $v->image ? (string) $v->image : null;
                break;
            }
        }

        $mainImageRaw = $matchedImage ?: (string) ($product->thumbnail_image ?? optional($product->image)->image ?? '');
        $mainImagePath = preg_replace('~^public/~', '', $mainImageRaw);
        $mainImagePath = ltrim((string) $mainImagePath, '/');
        if (!empty($mainImagePath) && !str_contains($mainImagePath, '/') && !str_contains($mainImagePath, '\\')) {
            $mainImagePath = 'uploads/product/' . $mainImagePath;
        }
        $mainImageUrl = !empty($mainImagePath) ? asset($mainImagePath) : asset('uploads/default/placeholder.png');
        $mainImageOnError = "this.onerror=null;this.src='" . asset('uploads/default/no-image.png') . "';";

        $imagesHtml = '<div class="xzoom-container product-gallery">'
            . '<img class="xzoom5" id="xzoom-magnific" src="' . e($mainImageUrl) . '" xoriginal="' . e($mainImageUrl) . '" onerror="' . e($mainImageOnError) . '" width="600" height="600" style="width:100%;height:auto;aspect-ratio:1/1;object-fit:contain;" />'
            . '</div>';

        return response()->json([
            'error' => false,
            'media_ids' => [$matchedImage ? md5($matchedImage) : 'default'],
            'images' => $imagesHtml,
        ]);
    }

    /**
     * Areas belonging to a district, for the profile form's cascading
     * district -> area selects.
     *
     * The route (routes/web.php, name `districts`) has been declared for a
     * long time but this method was missing, so every call returned a 500 and
     * the area dropdown in the classic profile page could never repopulate.
     *
     * Response shape is { "<area_id>": "<area_name>", ... } because that is
     * what the caller has always built its <option> list from; keys are cast to
     * strings by json_encode, which is what the DOM wants.
     */
    public function districts(Request $request)
    {
        $district = trim((string) $request->query('id', ''));

        if ($district === '') {
            return response()->json([], 422);
        }

        $areas = District::where('district', $district)
            ->orderBy('area_name')
            ->get(['id', 'area_name']);

        $payload = [];

        foreach ($areas as $area) {
            $payload[$area->id] = (string) $area->area_name;
        }

        return response()->json($payload);
    }

    public function shipping_charge(Request $request)
    {
        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid shipping area'], 422);
        }

        $charge = ShippingCharge::where('status', 1)->where('id', $id)->first();
        if (!$charge) {
            return response()->json(['status' => false, 'message' => 'Shipping area not found'], 404);
        }

        $amount = (float) ($charge->amount ?? 0);
        Session::put('shipping', $amount);

        return response()->json([
            'status' => true,
            'amount' => $amount,
            'label' => (string) ($charge->name ?? ''),
        ]);
    }

    public function homeSectionProducts(Request $request)
    {
        $section = (string) $request->query('section', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $query = null;
        $total = 0;

        if ($section === 'featured') {
            $query = Product::where(['status' => 1, 'feature_product' => 1])
                ->orderBy('id', 'desc');
            $total = (int) Product::where(['status' => 1, 'feature_product' => 1])->count();
        } elseif ($section === 'best_selling') {
            $query = Product::where(['status' => 1, 'topsale' => 1])
                ->orderBy('id', 'desc');
            $total = (int) Product::where(['status' => 1, 'topsale' => 1])->count();
        } elseif ($section === 'discount') {
            $query = Product::where('status', 1)
                ->whereNotNull('old_price')
                ->whereColumn('old_price', '>', 'new_price')
                ->orderBy('id', 'desc');
            $total = (int) Product::where('status', 1)
                ->whereNotNull('old_price')
                ->whereColumn('old_price', '>', 'new_price')
                ->count();
        } elseif ($section === 'recommended') {
            $query = Product::where(['status' => 1, 'is_recommended' => 1])
                ->orderBy('recommended_sort_order')
                ->orderBy('id', 'desc');
            $total = (int) Product::where(['status' => 1, 'is_recommended' => 1])->count();
        } else {
            return response()->json(['message' => 'Invalid section'], 422);
        }

        $products = $query
            ->with('image')
            ->withCount('reviews')
            ->withAvg('reviews', 'ratting')
            ->skip($offset)
            ->limit($perPage)
            ->get()
            ->each(function ($product) {
                $avg = (float)($product->reviews_avg_ratting ?? 0);
                $product->average_rating = (int)round($avg);
                $product->reviews_count = (int)($product->reviews_count ?? 0);
            });

        // Fallback to general products when section is empty (same behavior as homepage)
        if ($products->isEmpty() && $section !== 'discount') {
            $products = Product::where('status', 1)
                ->with('image')
                ->withCount('reviews')
                ->withAvg('reviews', 'ratting')
                ->orderBy('id', 'desc')
                ->skip($offset)
                ->limit($perPage)
                ->get()
                ->each(function ($product) {
                    $avg = (float)($product->reviews_avg_ratting ?? 0);
                    $product->average_rating = (int)round($avg);
                    $product->reviews_count = (int)($product->reviews_count ?? 0);
                });

            $total = (int) Product::where('status', 1)->count();
        }

        $hasPrev = $page > 1;
        $hasNext = ($offset + $perPage) < $total;

        $view = $section === 'recommended'
            ? 'frontend.sections.partials._recommended-product-cards'
            : 'frontend.sections.partials._home-product-cards';

        $html = view($view, ['products' => $products])->render();

        return response()->json([
            'html' => $html,
            'page' => $page,
            'hasPrev' => $hasPrev,
            'hasNext' => $hasNext,
        ]);
    }

    /**
     * AJAX quick-view modal — route name `quickview` (GET /quick-view?id=N).
     *
     * The storefront ($(".quick_view") click handler in
     * frontend/layouts/master.blade.php) injects whatever comes back straight
     * into #custom-modal, so this MUST return HTML, never JSON.
     *
     * The partial expects $data to be a Product exposing image / category /
     * brand / reviews_count. The route existed with no method behind it, so
     * every quick-view click used to 500.
     */
    public function quickview(Request $request)
    {
        $id = $request->input('id');

        if (!$id) {
            return response('', 204);
        }

        $data = Product::where('status', 1)
            ->with(['image', 'category', 'brand'])
            ->withCount('reviews')
            ->find($id);

        if (!$data) {
            return response('', 204);
        }

        return view('frontend.layouts.ajax.quickview', compact('data'));
    }

    /**
     * Read-only CMS page — route name `page` (GET /page/{slug}).
     *
     * Content comes from the `create_pages` table the admin already manages.
     * `$cmnmenu` is shared globally by AppServiceProvider, so only $page is
     * passed. The route also existed with no method, so every page link 500'd.
     */
    public function page($slug)
    {
        $page = CreatePage::where('status', 1)->where('slug', $slug)->first();

        if (!$page) {
            abort(404);
        }

        return view('frontend.pages.page', compact('page'));
    }

    public function index()
    {
        // return "Welcome to Kenakatar.com";
        $frontcategory = Category::where(['status' => 1])
            ->select('id', 'name', 'image', 'slug', 'status')
            ->get();

        $sliders = Banner::where(['status' => 1, 'category_id' => 1])
            ->select('id', 'image', 'video', 'media_type', 'link', 'title')
            ->get();

        $sliderbottomads = Banner::where(['status' => 1, 'category_id' => 5])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $footertopads = Banner::where(['status' => 1, 'category_id' => 6])
            ->select('id', 'image', 'link')
            ->limit(2)
            ->get();

        $hotdeal_top = Product::where(['status' => 1, 'topsale' => 1])
            ->orderBy('id', 'DESC')
            ->with('image', 'prosizes', 'procolors')
            ->limit(12)
            ->get();
        // return $hotdeal_top;

        $hotdeal_bottom = Product::where(['status' => 1, 'topsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price')
            ->skip(12)
            ->limit(12)
            ->get();

        $homeproducts = Category::where('status', 1)
            ->where(function ($q) {
                $q->where('front_view', 1)->orWhere('home_page', 1);
            })
            ->orderBy('id', 'ASC')
            ->with(['products', 'products.image', 'products.prosizes', 'products.procolors'])
            ->get()
            ->map(function ($query) {
                $query->setRelation('products', $query->products->take(12));
                return $query;
            });

        $zombie_products = Product::where('status', 1)
            ->with('image', 'prosizes', 'procolors')
            ->orderBy('id', 'DESC')
            ->limit(12)
            ->get();

        $brands = Brand::where('status', 1)->get();

        $generalsetting = \App\Models\GeneralSetting::first();

        return view('frontend.pages.index', compact('sliders', 'frontcategory', 'hotdeal_top', 'hotdeal_bottom', 'homeproducts', 'zombie_products', 'sliderbottomads', 'footertopads', 'brands', 'generalsetting'));
    }

    public function frontendHome()
    {
        $banners = Banner::where(['status' => 1, 'category_id' => 1])
            ->select('id', 'image', 'video', 'media_type', 'link', 'title')
            ->get();

        $homePerPage = 15;
        $featuredPage = max(1, (int) request()->query('featured_page', 1));
        $bestSellingPage = max(1, (int) request()->query('best_selling_page', 1));
        $discountPage = max(1, (int) request()->query('discount_page', 1));
        $recommendedPage = max(1, (int) request()->query('recommended_page', 1));

        $featured_categories = Category::where('status', 1)
            ->select('id', 'name', 'slug', 'image')
            ->orderBy('id', 'desc')
            ->limit(12)
            ->get();

        $homeCategoryCollections = Category::where('status', 1)
            ->select('id', 'name', 'slug')
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($category) {
                $products = Product::where('status', 1)
                    ->where('category_id', $category->id)
                    ->with('image')
                    ->withCount('reviews')
                    ->withAvg('reviews', 'ratting')
                    ->orderBy('id', 'desc')
                    ->limit(12)
                    ->get()
                    ->each(function ($product) {
                        $avg = (float)($product->reviews_avg_ratting ?? 0);
                        $product->average_rating = (int)round($avg);
                        $product->reviews_count = (int)($product->reviews_count ?? 0);
                    });

                return [
                    'title' => $category->name,
                    'slug' => $category->slug,
                    'products' => $products,
                ];
            })
            ->values();

        $collections = HomepageCollection::with('category')
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($collection) {
                $limit = (int)($collection->product_limit ?? 12);

                $products = Product::where('status', 1)
                    ->when($collection->category_id, function ($q) use ($collection) {
                        return $q->where('category_id', $collection->category_id);
                    })
                    ->with('image')
                    ->orderBy('id', 'desc')
                    ->limit($limit)
                    ->get();

                return [
                    'title' => $collection->title,
                    'banner_image' => $collection->banner_image,
                    'groups' => $products->chunk(2)->values(),
                ];
            });

        $featuredOffset = ($featuredPage - 1) * $homePerPage;
        $featured_products = Product::where(['status' => 1, 'feature_product' => 1])
            ->with('image')
            ->withCount('reviews')
            ->withAvg('reviews', 'ratting')
            ->orderBy('id', 'desc')
            ->skip($featuredOffset)
            ->limit($homePerPage)
            ->get()
            ->each(function ($product) {
                $avg = (float)($product->reviews_avg_ratting ?? 0);
                $product->average_rating = (int)round($avg);
                $product->reviews_count = (int)($product->reviews_count ?? 0);
            });

        $featuredTotal = (int) Product::where(['status' => 1, 'feature_product' => 1])->count();
        $featuredHasPrev = $featuredPage > 1;
        $featuredHasNext = ($featuredOffset + $homePerPage) < $featuredTotal;

        if ($featured_products->isEmpty()) {
            $featured_products = Product::where('status', 1)
                ->with('image')
                ->withCount('reviews')
                ->withAvg('reviews', 'ratting')
                ->orderBy('id', 'desc')
                ->skip($featuredOffset)
                ->limit($homePerPage)
                ->get()
                ->each(function ($product) {
                    $avg = (float)($product->reviews_avg_ratting ?? 0);
                    $product->average_rating = (int)round($avg);
                    $product->reviews_count = (int)($product->reviews_count ?? 0);
                });

            $featuredTotal = (int) Product::where('status', 1)->count();
            $featuredHasPrev = $featuredPage > 1;
            $featuredHasNext = ($featuredOffset + $homePerPage) < $featuredTotal;
        }

        $bestSellingOffset = ($bestSellingPage - 1) * $homePerPage;
        $best_selling_products = Product::where(['status' => 1, 'topsale' => 1])
            ->with('image')
            ->withCount('reviews')
            ->withAvg('reviews', 'ratting')
            ->orderBy('id', 'desc')
            ->skip($bestSellingOffset)
            ->limit($homePerPage)
            ->get()
            ->each(function ($product) {
                $avg = (float)($product->reviews_avg_ratting ?? 0);
                $product->average_rating = (int)round($avg);
                $product->reviews_count = (int)($product->reviews_count ?? 0);
            });

        $bestSellingTotal = (int) Product::where(['status' => 1, 'topsale' => 1])->count();
        $bestSellingHasPrev = $bestSellingPage > 1;
        $bestSellingHasNext = ($bestSellingOffset + $homePerPage) < $bestSellingTotal;

        $discountOffset = ($discountPage - 1) * $homePerPage;
        $discount_products = Product::where('status', 1)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'new_price')
            ->with('image')
            ->withCount('reviews')
            ->withAvg('reviews', 'ratting')
            ->orderBy('id', 'desc')
            ->skip($discountOffset)
            ->limit($homePerPage)
            ->get()
            ->each(function ($product) {
                $avg = (float)($product->reviews_avg_ratting ?? 0);
                $product->average_rating = (int)round($avg);
                $product->reviews_count = (int)($product->reviews_count ?? 0);
            });

        $discountTotal = (int) Product::where('status', 1)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'new_price')
            ->count();
        $discountHasPrev = $discountPage > 1;
        $discountHasNext = ($discountOffset + $homePerPage) < $discountTotal;

        $featured_brands = Brand::where('status', 1)
            ->select('id', 'name', 'slug', 'image')
            ->orderBy('id', 'desc')
            ->limit(12)
            ->get();

        $winter_sale_title = 'Winter Sale';
        $winter_sale_starts_at = null;
        $winter_sale_ends_at = null;
        $winter_sale_banner_url = 'javascript:void(0)';
        $winter_sale_banner_image = 'assets/images/frontend/banner/673099d36f6e71731238355.png';
        $winter_sale_products = $featured_products->take(10)->chunk(2)->values();

        // Dynamic flash sale: prefer currently running, otherwise show next upcoming active flash sale
        $flash_sale = FlashSale::running()
            ->with(['products.image'])
            ->first();

        if (!$flash_sale) {
            $flash_sale = FlashSale::active()
                ->where('start_time', '>', now())
                ->with(['products.image'])
                ->orderBy('start_time')
                ->first();
        }

        $flash_sale_products = $flash_sale ? $flash_sale->products : collect();

        // Dynamic promo banners
        $promo_banners = PromoBanner::active()
            ->ordered()
            ->limit(3)
            ->get();

        // Services section data
        $services = Service::active()
            ->ordered()
            ->get();

        $generalsetting = GeneralSetting::first();

        $recommendedOffset = ($recommendedPage - 1) * $homePerPage;
        $recommended_products = Product::where(['status' => 1, 'is_recommended' => 1])
            ->with('image')
            ->withCount('reviews')
            ->withAvg('reviews', 'ratting')
            ->orderBy('recommended_sort_order')
            ->orderBy('id', 'desc')
            ->skip($recommendedOffset)
            ->limit($homePerPage)
            ->get()
            ->each(function ($product) {
                $avg = (float)($product->reviews_avg_ratting ?? 0);
                $product->average_rating = (int)round($avg);
                $product->reviews_count = (int)($product->reviews_count ?? 0);
            });

        $recommendedTotal = (int) Product::where(['status' => 1, 'is_recommended' => 1])->count();
        $recommendedHasPrev = $recommendedPage > 1;
        $recommendedHasNext = ($recommendedOffset + $homePerPage) < $recommendedTotal;

        if ($recommended_products->isEmpty()) {
            $recommended_products = Product::where('status', 1)
                ->with('image')
                ->withCount('reviews')
                ->withAvg('reviews', 'ratting')
                ->orderBy('id', 'desc')
                ->skip($recommendedOffset)
                ->limit($homePerPage)
                ->get()
                ->each(function ($product) {
                    $avg = (float)($product->reviews_avg_ratting ?? 0);
                    $product->average_rating = (int)round($avg);
                    $product->reviews_count = (int)($product->reviews_count ?? 0);
                });

            $recommendedTotal = (int) Product::where('status', 1)->count();
            $recommendedHasPrev = $recommendedPage > 1;
            $recommendedHasNext = ($recommendedOffset + $homePerPage) < $recommendedTotal;
        }

        $sections = [
            'hero' => [
                'show' => (int)($generalsetting->home_hero_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_hero_title ?? ''),
            ],
            'promo' => [
                'show' => (int)($generalsetting->home_promo_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_promo_title ?? 'Promo Banners'),
            ],
            'recommended' => [
                'show' => (int)($generalsetting->home_recommended_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_recommended_title ?? 'Recommended'),
            ],
            'category' => [
                'show' => (int)($generalsetting->home_category_section ?? ($generalsetting->home_page_top_categories_section ?? 1)) === 1,
                'title' => (string)($generalsetting->home_category_title ?? 'Categories'),
            ],
            'featured' => [
                'show' => (int)($generalsetting->home_featured_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_featured_title ?? 'Featured Products'),
            ],
            'best_selling' => [
                'show' => (int)($generalsetting->home_best_selling_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_best_selling_title ?? 'Best Selling Products'),
            ],
            'discount_products' => [
                'show' => (int)($generalsetting->home_discount_products_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_discount_products_title ?? 'Discount Products'),
            ],
            'collections' => [
                'show' => (int)($generalsetting->home_collections_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_collections_title ?? 'Collections'),
            ],
            'brands' => [
                'show' => (int)($generalsetting->home_brands_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_brands_title ?? 'Top Brands'),
            ],
            'service' => [
                'show' => (int)($generalsetting->home_service_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_service_title ?? 'Service'),
            ],
            'flash_sale' => [
                'show' => (int)($generalsetting->home_flash_sale_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_flash_sale_title ?? 'FLASH SALE'),
            ],
            'footer' => [
                'show' => (int)($generalsetting->home_footer_section ?? 1) === 1,
                'title' => (string)($generalsetting->home_footer_title ?? ''),
            ],
        ];

        // Approved customer reviews, richest first. Short test rows such as
        // "hmm" or "555" are filtered out so the home page never publishes
        // meaningless quotes; when nothing survives the testimonials section
        // simply hides itself in the view.
        $testimonials = Review::with('product:id,slug,name')
            ->where('status', 'active')
            ->whereNotNull('review')
            ->whereRaw('CHAR_LENGTH(TRIM(review)) >= 20')
            ->orderByDesc('ratting')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // The classic name is served and the registry decides which set
        // answers: ThemeViewFinder -> FrontendTheme::resolve() rewrites it to
        // frontend.fm.pages.home when modern is active/previewing, and keeps
        // frontend.home otherwise. Never hardcode an fm* view name here: that
        // would bypass the theme switch (preview/save would not affect `/`).
        // Both views read the same variables ($sections, the product rails,
        // $banners, the collections and the brands) - frontend.home guards
        // every section with isset()/?? true, so both render from this data.
        return view('frontend.home', compact(
            'collections',
            'homeCategoryCollections',
            'banners',
            'featured_categories',
            'featured_products',
            'best_selling_products',
            'discount_products',
            'featured_brands',
            'recommended_products',
            'homePerPage',
            'featuredPage',
            'featuredHasPrev',
            'featuredHasNext',
            'bestSellingPage',
            'bestSellingHasPrev',
            'bestSellingHasNext',
            'discountPage',
            'discountHasPrev',
            'discountHasNext',
            'recommendedPage',
            'recommendedHasPrev',
            'recommendedHasNext',
            'sections',
            'winter_sale_title',
            'winter_sale_starts_at',
            'winter_sale_ends_at',
            'winter_sale_banner_url',
            'winter_sale_banner_image',
            'winter_sale_products',
            'flash_sale',
            'flash_sale_products',
            'promo_banners',
            'services',
            'testimonials',
            'generalsetting'
        ));
    }

    public function campaigns()
    {
        $campaigns = Campaign::where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('frontend.pages.campaigns', compact('campaigns'));
    }

    public function contact(Request $request)
    {
        $generalsetting = GeneralSetting::first();
        return view('frontend.pages.contact', compact('generalsetting'));
    }

    public function category($slug, Request $request)
    {
        $generalsetting = GeneralSetting::first();

        $rawSlug = trim(urldecode((string) $slug));
        $normalizedSlug = trim(str_replace(',', '', $rawSlug));

        $category = Category::where('status', 1)
            ->where(function ($query) use ($slug, $normalizedSlug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', $normalizedSlug);
                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->first();

        $productsQuery = Product::where('status', 1)
            ->with('image')
            ->withCount('reviews');

        $pageTitle = 'Products';

        if ($category) {
            $productsQuery->where('category_id', $category->id);
            $pageTitle = (string) ($category->name ?? 'Products');
        } else {
            $subcategory = Subcategory::where('status', 1)
                ->where(function ($query) use ($slug, $normalizedSlug) {
                    $query->where('slug', $slug)
                        ->orWhere('slug', $normalizedSlug);
                    if (is_numeric($slug)) {
                        $query->orWhere('id', (int) $slug);
                    }
                })
                ->first();

            if ($subcategory) {
                $productsQuery->where('subcategory_id', $subcategory->id);
                $pageTitle = (string) ($subcategory->subcategoryName ?? 'Products');
            } else {
                $childcategory = Childcategory::where('status', 1)
                    ->where(function ($query) use ($slug, $normalizedSlug) {
                        $query->where('slug', $slug)
                            ->orWhere('slug', $normalizedSlug);
                        if (is_numeric($slug)) {
                            $query->orWhere('id', (int) $slug);
                        }
                    })
                    ->firstOrFail();

                $productsQuery->where('childcategory_id', $childcategory->id);
                $pageTitle = (string) ($childcategory->childcategoryName ?? 'Products');
            }
        }

        $productsQuery = $this->applyProductListingSort($productsQuery, $request);
        $products = $productsQuery->paginate((int) $request->get('per_page', 24))->appends($request->query());
        $metaDescription = 'Browse ' . $pageTitle;

        return view('frontend.pages.products', $this->withListingSidebar(compact('generalsetting', 'products', 'pageTitle', 'metaDescription')));
    }

    public function subcategory($slug, Request $request)
    {
        $generalsetting = GeneralSetting::first();

        $rawSlug = trim(urldecode((string) $slug));
        $normalizedSlug = trim(str_replace(',', '', $rawSlug));

        $subcategory = Subcategory::where('status', 1)
            ->where(function ($query) use ($slug, $normalizedSlug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', $normalizedSlug);
                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->firstOrFail();

        $productsQuery = Product::where('status', 1)
            ->where('subcategory_id', $subcategory->id)
            ->with('image')
            ->withCount('reviews');

        $productsQuery = $this->applyProductListingSort($productsQuery, $request);
        $products = $productsQuery->paginate((int) $request->get('per_page', 24))->appends($request->query());

        $pageTitle = (string) ($subcategory->subcategoryName ?? 'Products');
        $metaDescription = 'Browse ' . $pageTitle;

        return view('frontend.pages.products', $this->withListingSidebar(compact('generalsetting', 'products', 'pageTitle', 'metaDescription')));
    }

    public function products($slug, Request $request)
    {
        $generalsetting = GeneralSetting::first();

        $rawSlug = trim(urldecode((string) $slug));
        $normalizedSlug = trim(str_replace(',', '', $rawSlug));

        $childcategory = Childcategory::where('status', 1)
            ->where(function ($query) use ($slug, $normalizedSlug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', $normalizedSlug);
                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->firstOrFail();

        $productsQuery = Product::where('status', 1)
            ->where('childcategory_id', $childcategory->id)
            ->with('image')
            ->withCount('reviews');

        $productsQuery = $this->applyProductListingSort($productsQuery, $request);
        $products = $productsQuery->paginate((int) $request->get('per_page', 24))->appends($request->query());

        $pageTitle = (string) ($childcategory->childcategoryName ?? 'Products');
        $metaDescription = 'Browse ' . $pageTitle;

        return view('frontend.pages.products', $this->withListingSidebar(compact('generalsetting', 'products', 'pageTitle', 'metaDescription')));
    }

    public function brand($slug, Request $request)
    {
        $generalsetting = GeneralSetting::first();

        $rawSlug = trim(urldecode((string) $slug));
        $normalizedSlug = trim(str_replace(',', '', $rawSlug));

        $brand = Brand::where('status', 1)
            ->where(function ($query) use ($slug, $normalizedSlug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', $normalizedSlug);
                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->firstOrFail();

        $productsQuery = Product::where('status', 1)
            ->where('brand_id', $brand->id)
            ->with('image')
            ->withCount('reviews');

        $productsQuery = $this->applyProductListingSort($productsQuery, $request);
        $products = $productsQuery->paginate((int) $request->get('per_page', 24))->appends($request->query());

        $pageTitle = (string) ($brand->name ?? 'Products');
        $metaDescription = 'Browse ' . $pageTitle;

        return view('frontend.pages.products', $this->withListingSidebar(compact('generalsetting', 'products', 'pageTitle', 'metaDescription')));
    }

    public function details($id)
    {
        $generalsetting = GeneralSetting::first();

        $product = Product::where('status', 1)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('slug', $id);
            })
            ->with([
                'image',
                'images',
                'prosizes.size',
                'procolors.color',
                'reviews.customer',
                'category',
                'subcategory',
                'childcategory',
            ])
            ->withCount('reviews')
            ->firstOrFail();

        $normalizeMediaPath = function ($path) {
            $path = preg_replace('~^public/~', '', (string) $path);
            return ltrim((string) $path, '/');
        };

        $productImages = $product->images ? $product->images->pluck('image')->filter()->values()->all() : [];
        $product_images = array_values(array_filter(array_map($normalizeMediaPath, $productImages)));

        $product_thumbs = array_map(function ($img) {
            $img = (string) $img;
            return [
                'id' => md5($img),
                'full' => $img,
                'thumb' => $img,
            ];
        }, $product_images);

        $product_categories = [];
        if ($product->category) {
            $product_categories[] = [
                'name' => $product->category->name,
                'url' => route('category', $product->category->slug),
            ];
        }
        if ($product->subcategory) {
            $product_categories[] = [
                'name' => $product->subcategory->subcategoryName,
                'url' => route('subcategory', $product->subcategory->slug),
            ];
        }
        if ($product->childcategory) {
            $product_categories[] = [
                'name' => $product->childcategory->childcategoryName,
                'url' => route('products', $product->childcategory->slug),
            ];
        }

        $product_videos = [];
        foreach ($product_images as $img) {
            if (str_ends_with(strtolower((string) $img), '.mp4')) {
                $product_videos[] = $img;
            }
        }

        $related_products = Product::where('status', 1)
            ->where('id', '!=', $product->id)
            ->when(!empty($product->subcategory_id), function ($q) use ($product) {
                $q->where('subcategory_id', $product->subcategory_id);
            }, function ($q) use ($product) {
                if (!empty($product->category_id)) {
                    $q->where('category_id', $product->category_id);
                }
            })
            ->with('image')
            ->withCount('reviews')
            ->limit(8)
            ->get();

        $productVariants = DB::table('product_variants')
            ->where('product_id', $product->id)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get(['id', 'sku', 'title', 'image', 'price_override', 'weight_gram', 'attributes_json']);

        $variantAttributes = [];
        $attributeSlugToId = [];
        $attributeSlugToName = [];
        foreach ($productVariants as $v) {
            if (empty($v->attributes_json)) {
                continue;
            }
            $decoded = json_decode((string) $v->attributes_json, true);
            if (!is_array($decoded)) {
                continue;
            }
            $attrs = $decoded['attrs'] ?? $decoded;
            $ids = $decoded['ids'] ?? [];
            if (!is_array($attrs)) {
                continue;
            }
            foreach ($attrs as $slug => $label) {
                $slugKey = (string) $slug;
                if ($slugKey === '' || $slugKey === 'undefined' || $slugKey === 'null') {
                    continue;
                }
                if (!isset($variantAttributes[$slugKey])) {
                    // attributes_json keys are raw slugs ("weight-unit"), but the
                    // label must be the human name from the attributes table
                    // ("Weight Unit"). Cache it so N variants cost one lookup per
                    // attribute, and fall back to the slug if no row exists.
                    if (!array_key_exists($slugKey, $attributeSlugToName)) {
                        $attributeSlugToName[$slugKey] = (string) (DB::table('attributes')
                            ->where('slug', $slugKey)
                            ->value('name') ?? '');
                    }
                    $attributeName = $attributeSlugToName[$slugKey] !== ''
                        ? $attributeSlugToName[$slugKey]
                        : $slugKey;

                    $variantAttributes[$slugKey] = [
                        'slug' => $slugKey,
                        'name' => $attributeName,
                        'values' => [],
                    ];
                }
                $valueId = $ids[$slugKey] ?? null;
                if ($valueId === null) {
                    $attrId = $attributeSlugToId[$slugKey] ?? null;
                    if ($attrId === null) {
                        $attrRow = DB::table('attributes')
                            ->where('slug', $slugKey)
                            ->orWhere('name', $slugKey)
                            ->first(['id']);
                        $attrId = $attrRow ? (int) $attrRow->id : null;
                        $attributeSlugToId[$slugKey] = $attrId;
                    }

                    if ($attrId !== null) {
                        $valRow = DB::table('attribute_values')
                            ->where('attribute_id', $attrId)
                            ->where('value', (string) $label)
                            ->first(['id']);
                        if ($valRow) {
                            $valueId = (int) $valRow->id;
                        }
                    }
                }

                if ($valueId === null) {
                    continue;
                }

                // A colour swatch is only drawn when the shopkeeper actually
                // entered a colour for this value. attribute_values.code holds
                // the free-text code from the admin form; a valid hex in it is
                // treated as a colour, anything else is left as plain text so
                // the page never invents a shade the shop does not sell.
                $valueRow = DB::table('attribute_values')
                    ->where('id', $valueId)
                    ->first(['id', 'code']);
                // first() returns a stdClass, not an array. Reading it as an
                // array silently yields null and the swatch never appears.
                $hex = $valueRow ? (string) ($valueRow->code ?? '') : '';
                $isColour = (bool) preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', trim($hex));

                $variantAttributes[$slugKey]['values'][] = [
                    'id' => $valueId,
                    'label' => (string) $label,
                    'colour' => $isColour ? trim($hex) : null,
                    'media_id' => null,
                ];
            }
        }

        foreach ($variantAttributes as $k => $attr) {
            $uniq = [];
            foreach ($attr['values'] as $val) {
                $uniqKey = ($val['id'] !== null ? ('id:' . $val['id']) : ('label:' . $val['label']));
                $uniq[$uniqKey] = $val;
            }
            $variantAttributes[$k]['values'] = array_values($uniq);
        }

        // Product::reviews() selects only ids (it feeds the counts), so the
        // review stream runs its own query: published rows only, newest first.
        // Pending reviews wait for an administrator, exactly as before.
        $productReviews = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'ratting', 'review', 'customer_id', 'created_at']);

        return view('frontend.pages.product-details', compact(
            'generalsetting',
            'product',
            'product_images',
            'product_thumbs',
            'product_categories',
            'product_videos',
            'related_products',
            'productVariants',
            'variantAttributes',
            'productReviews'
        ));
    }



    public function offers()
    {
        return view('frontend.pages.offers');
    }

    /**
     * Hot deals listing.
     *
     * The route has always existed (web.php -> hotdeals) but the action was
     * never written, so /hot-deals returned HTTP 500. Reuses the exact same
     * query + sort pipeline as the other product listings so the frontend
     * blade gets an identical shape: a paginator plus pageTitle/metaDescription.
     */
    public function hotdeals(Request $request)
    {
        $generalsetting = GeneralSetting::first();

        $productsQuery = Product::where('status', 1)
            ->whereNotNull('old_price')
            ->whereRaw('old_price > new_price')
            ->with('image')
            ->withCount('reviews');

        $productsQuery = $this->applyProductListingSort($productsQuery, $request);

        $products = $productsQuery
            ->paginate((int) $request->get('per_page', 24))
            ->appends($request->query());

        $pageTitle = 'Hot Deals';
        $metaDescription = 'Discounted products and limited time deals';

        // The FM (modern) layout owns this page now: frontend/fm/pages/
        // hotdeals.blade.php, which is the product listing shell (same sidebar,
        // toolbar, grid and pager as /products) over this same result set. The
        // classic view - frontend/pages/hotdeals.blade.php, on the master
        // layout with its own .sh-footer-v2 footer - is still on disk; change
        // the name below back to switch this one page off the redesign.
        return view('frontend.fm.pages.hotdeals', compact('generalsetting', 'products', 'pageTitle', 'metaDescription'));
    }

    /**
     * Site search results.
     *
     * Same situation as hotdeals: routed at /search but no action existed, so
     * the page 500'd. Searches name + product_code, reuses applyProductListingSort.
     */
    public function search(Request $request)
    {
        $generalsetting = GeneralSetting::first();

        $term = trim((string) $request->query('q', $request->query('search', '')));

        $productsQuery = Product::where('status', 1)
            // A search started from inside a category keeps that scope, so the
            // results stay inside the department the visitor was browsing
            // instead of jumping to the whole catalogue. Only an explicit
            // category= is honoured; the /category/{slug} pages send their
            // scope through the path, not the query string.
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereIn('category_id', function ($sub) use ($request) {
                    $sub->select('id')->from('categories')->where('slug', (string) $request->query('category'));
                });
            })
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('product_code', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->with('image')
            ->withCount('reviews');

        $productsQuery = $this->applyProductListingSort($productsQuery, $request);

        $products = $productsQuery
            ->paginate((int) $request->get('per_page', 24))
            ->appends($request->query());

        $pageTitle = $term !== '' ? 'Search results for "' . $term . '"' : 'Search';

        // The search page shows the query as a removable chip. When a category
        // scope travels with the search the chip has to remove both, otherwise
        // dropping the word silently widens the search back to every category
        // the visitor had just narrowed away from.
        $searchCategorySlug = trim((string) $request->query('category', ''));
        $metaDescription = 'Search products';

        // The modern search page reuses the shop listing markup, whose sidebar
        // reads categoryCounts / priceCeiling / hasReviewData. Without them the
        // counters fall back to an empty set, which shows a "0" next to every
        // department, so the same helper the category and brand routes use is
        // applied here too.
        return view('frontend.pages.search', $this->withListingSidebar(
            compact('generalsetting', 'products', 'pageTitle', 'metaDescription', 'term', 'searchCategorySlug')
        ));
    }

    /**
     * Type-ahead suggestions for the header search box (route: /livesearch).
     * Returns JSON so the header dropdown can render without a full page load.
     */
    public function livesearch(Request $request)
    {
        $term = trim((string) $request->query('q', $request->query('search', '')));

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        $products = Product::where('status', 1)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('product_code', 'like', $like);
            })
            ->with('image')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => (float) ($product->new_price ?? 0),
                    'old_price' => (float) ($product->old_price ?? 0),
                    'image' => $product->thumbnail_image,
                    'url' => route('product', $product->slug),
                ];
            });

        return response()->json(['results' => $products]);
    }
}
