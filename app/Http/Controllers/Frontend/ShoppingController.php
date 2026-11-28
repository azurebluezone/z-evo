<?php

namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\VariantStock;
use Illuminate\Http\Request;

use App\Models\Campaign;
use App\Models\Product;
use App\Services\CampaignPricing;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use App\Models\ShippingCharge;
use App\Models\PaymentGateway;
use App\Models\GeneralSetting;
use App\Services\GuestCheckoutAccountService;
use App\Services\OrderPlacementService;
use App\Services\Analytics\CartActivityRecorder;
use App\Services\SmsGatewayService;
use Illuminate\Support\Facades\Log;
use Toastr;
use Cart;
use DB;

class ShoppingController extends Controller
{
    private function resolveSelectedVariant(int $productId, array $selectedValueIds)
    {
        $selectedValueIds = array_values(array_filter(array_map(function ($v) {
            $v = is_scalar($v) ? (string) $v : '';
            $v = trim($v);
            return ctype_digit($v) ? (int) $v : null;
        }, $selectedValueIds), function ($v) {
            return $v !== null;
        }));

        if (!empty($selectedValueIds)) {
            $variants = DB::table('product_variants')
                ->where('product_id', (int) $productId)
                ->orderBy('id', 'asc')
                ->get();

            $needle = $selectedValueIds;
            sort($needle);

            foreach ($variants as $variant) {
                $json = $variant->attributes_json ?? null;
                if (!$json) {
                    continue;
                }
                $decoded = json_decode((string) $json, true);
                if (!is_array($decoded)) {
                    continue;
                }

                $idsMap = [];
                if (isset($decoded['ids']) && is_array($decoded['ids'])) {
                    $idsMap = $decoded['ids'];
                } elseif (isset($decoded['attribute_ids']) && is_array($decoded['attribute_ids'])) {
                    $idsMap = $decoded['attribute_ids'];
                }

                $candidate = array_values(array_filter(array_map(function ($v) {
                    $v = is_scalar($v) ? (string) $v : '';
                    $v = trim($v);
                    return ctype_digit($v) ? (int) $v : null;
                }, array_values($idsMap)), function ($v) {
                    return $v !== null;
                }));

                if (empty($candidate)) {
                    continue;
                }
                sort($candidate);

                if ($candidate === $needle) {
                    return [$variant, $selectedValueIds];
                }
            }

            // Selected but not matched
            return [null, $selectedValueIds];
        }

        // No selection: fallback to DEFAULT variant if exists
        // Raw query: the SoftDeletes scope does not apply, so a trashed
        // product must be rejected explicitly instead of being sellable.
        $product = DB::table('products')->where('id', (int) $productId)->whereNull('deleted_at')->first(['id', 'product_code']);
        if (!$product) {
            return [null, []];
        }

        $code = (string) ($product->product_code ?? '');
        $defaultSku = ($code !== '' ? $code : ('P' . $productId)) . '-DEFAULT';
        $defaultVariant = DB::table('product_variants')
            ->where('product_id', (int) $productId)
            ->where('sku', $defaultSku)
            ->first();

        if ($defaultVariant) {
            return [$defaultVariant, []];
        }

        return [null, []];
    }

    private function enforceVariantStockOrFail(int $variantId, int $qty)
    {
        $qty = max(1, (int) $qty);
        $stock = DB::table('variant_stocks')->where('variant_id', (int) $variantId)->first();

        /*
         * A variant with NO stock row used to read as $available = 0 and the
         * customer was told "Out of stock for this variant" - a deadlock, because
         * the only code that creates the missing row lives on the ADMIN side
         * while approving an order, and an order cannot be placed in the first
         * place. Observed live: product P0001 with a legacy product stock of 50
         * and two variants, zero variant_stocks rows, add-to-cart 422.
         *
         * Seeding it here breaks the deadlock without inventing stock: a
         * variant whose projection row is missing starts at zero.
         */
        if (! $stock) {
            VariantStock::ensureRow((int) $variantId);
            $stock = DB::table('variant_stocks')->where('variant_id', (int) $variantId)->first();
        }

        $available = (int) ($stock->available ?? 0);

        $reserved = (int) DB::table('inventory_reservations')
            ->where('variant_id', (int) $variantId)
            ->where('expires_at', '>', now())
            ->sum('qty');
        $availableToPromise = max(0, $available - $reserved);
        if ($availableToPromise <= 0) {
            return ['ok' => false, 'message' => 'Out of stock for this variant.', 'available' => 0];
        }
        if ($qty > $availableToPromise) {
            return ['ok' => false, 'message' => 'Not enough stock for this variant.', 'available' => $availableToPromise];
        }
        return ['ok' => true, 'message' => null, 'available' => $availableToPromise];
    }

    private function cartQuantitySum(): int
    {
        try {
            return (int) Cart::instance('shopping')->content()->sum(function ($item) {
                return (int) ($item->qty ?? 0);
            });
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Phase 3 item 5: store the current session cart so an abandoned cart is a
     * row with a real status, not something a later report has to infer from
     * logs. Never allowed to break a cart action.
     */
    private function recordCartActivity(): void
    {
        try {
            $cart = Cart::instance('shopping');
            $subtotal = (float) str_replace(',', '', (string) $cart->subtotal());

            app(CartActivityRecorder::class)->record($this->cartQuantitySum(), $subtotal);
        } catch (\Throwable $e) {
            Log::warning('Cart activity record failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function trackingUserData(): array
    {
        $customer = auth('customer')->user();
        $webUser = auth()->user();

        return [
            'email' => $customer->email ?? $webUser->email ?? null,
            'phone' => $customer->phone ?? $webUser->phone ?? null,
            'first_name' => $customer->name ?? $webUser->name ?? null,
        ];
    }

    private function dispatchAddToCartTracking(array $productData): void
    {
        $userData = $this->trackingUserData();

        try {
            $facebook = app(FacebookTrackingService::class);
            if ($facebook->isEnabled()) {
                $facebook->sendAddToCartEvent($productData, $userData);
            }
        } catch (\Throwable $e) {
        }

        try {
            $tiktok = app(TikTokTrackingService::class);
            if ($tiktok->isEnabled()) {
                $tiktok->sendAddToCartEvent($productData, $userData);
            }
        } catch (\Throwable $e) {
        }
    }

    public function addTocartGet($id, Request $request)
    {
        $qty = (int) ($request->route('qty') ?? $request->get('qty', 1));
        if ($qty < 1) {
            $qty = 1;
        }
        // Raw query: exclude soft-deleted products by hand, otherwise a
        // bookmarked add-to-cart URL still sells a product the admin removed.
        $productInfo = DB::table('products')->where('id', $id)->whereNull('deleted_at')->first();
        $productImage = DB::table('productimages')->where('product_id', $id)->first();
        if (!$productInfo) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        // Variant support (expects query: ?variant[]=<attribute_value_id>...)
        $selectedValueIds = $request->query('variant', []);
        if (!is_array($selectedValueIds)) {
            $selectedValueIds = [$selectedValueIds];
        }
        [$resolvedVariant, $selectedValueIds] = $this->resolveSelectedVariant((int) $id, (array) $selectedValueIds);
        if (!empty($request->query('variant')) && !$resolvedVariant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not available.',
            ], 422);
        }

        $cartPrice = (int) ($productInfo->new_price ?? 0);
        $cartImage = $productImage->image ?? '';
        $variantTitle = null;
        $variantId = null;
        $variantName = null;

        if ($resolvedVariant) {
            $variantId = (int) $resolvedVariant->id;
            $variantTitle = $resolvedVariant->title ?? null;
            if (!empty($resolvedVariant->price_override)) {
                $cartPrice = (int) $resolvedVariant->price_override;
            }
            if (!empty($resolvedVariant->image)) {
                $cartImage = (string) $resolvedVariant->image;
            }

            // Best-effort: readable selected value label(s) for UI (e.g., Forest Green)
            try {
                $decoded = json_decode((string) ($resolvedVariant->attributes_json ?? ''), true);
                if (is_array($decoded) && isset($decoded['attrs']) && is_array($decoded['attrs'])) {
                    $parts = array_values(array_filter(array_map(function ($v) {
                        $v = is_scalar($v) ? (string) $v : '';
                        $v = trim($v);
                        return $v === '' ? null : $v;
                    }, $decoded['attrs'])));
                    if (!empty($parts)) {
                        $variantName = implode(' / ', $parts);
                    }
                }
            } catch (\Throwable $e) {
            }

            $check = $this->enforceVariantStockOrFail($variantId, $qty);
            if (!$check['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $check['message'],
                    'available' => $check['available'],
                ], 422);
            }
        }

        // If already in cart with same variant, accumulate quantity (stock-validated)
        $existingItem = null;
        try {
            $existingItem = Cart::instance('shopping')->content()->filter(function ($cartItem) use ($id, $variantId) {
                $sameVariant = (int) ($cartItem->options->variant_id ?? 0) === (int) ($variantId ?? 0);
                return (int) $cartItem->id === (int) $id && $sameVariant;
            })->first();
        } catch (\Throwable $e) {
            $existingItem = null;
        }

        if ($existingItem && isset($existingItem->rowId)) {
            $nextQty = (int) ($existingItem->qty ?? 0) + (int) $qty;
            if ($nextQty < 1) {
                $nextQty = 1;
            }
            if ($variantId) {
                $check = $this->enforceVariantStockOrFail($variantId, $nextQty);
                if (!$check['ok']) {
                    return response()->json([
                        'success' => false,
                        'message' => $check['message'],
                        'available' => $check['available'],
                    ], 422);
                }
            }
            Cart::instance('shopping')->update($existingItem->rowId, $nextQty);
            $cartinfo = Cart::instance('shopping')->get($existingItem->rowId);
        } else {
            $cartinfo = Cart::instance('shopping')->add([
                'id' => $productInfo->id,
                'name' => $productInfo->name,
                'qty' => $qty,
                'price' => $cartPrice,
                'options' => [
                    'image' => $cartImage,
                    'old_price' => $productInfo->old_price,
                    'slug' => $productInfo->slug,
                    'purchase_price' => $productInfo->purchase_price,
                    'variant_id' => $variantId,
                    'variant_title' => $variantTitle,
                    'variant_values' => $selectedValueIds,
                    'variant_name' => $variantName,
                    // Store marker into legacy fields so order_save can persist it without schema changes
                    'product_size' => $variantId ? ('variant_id:' . (int) $variantId) : '',
                    'product_color' => $variantName ?: '',
                ]
            ]);
        }

        $this->dispatchAddToCartTracking([
            'product_id' => $productInfo->id,
            'name' => $productInfo->name,
            'price' => $cartPrice,
            'quantity' => $qty,
        ]);

        $this->recordCartActivity();

        return response()->json([
            'rowId' => $cartinfo->rowId ?? null,
            'id' => (int) ($cartinfo->id ?? $productInfo->id),
            'qty' => (int) ($cartinfo->qty ?? $qty),
            'price' => (float) ($cartinfo->price ?? $cartPrice),
            'options' => $cartinfo->options ?? [],
            'cart_count' => $this->cartQuantitySum(),
        ]);
    }

    public function cart_store(Request $request)
    {
        $product = Product::where('id', $request->id)->first();
        if (!$product) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Product not found.', 'cart_count' => $this->cartQuantitySum()], 404);
            }
            return redirect()->back()->with('error', 'Product not found.');
        }

        $requestQty = (int) ($request->qty ?? 1);
        if ($requestQty < 1) {
            $requestQty = 1;
        }

        $variantQuery = $request->input('variant', []);
        if (!is_array($variantQuery)) {
            $variantQuery = [$variantQuery];
        }
        [$resolvedVariant, $selectedValueIds] = $this->resolveSelectedVariant((int) $product->id, (array) $variantQuery);

        $variantId = $resolvedVariant ? (int) $resolvedVariant->id : null;
        if (!empty($variantQuery) && !$resolvedVariant) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Variant not available.', 'cart_count' => $this->cartQuantitySum()], 422);
            }
            return redirect()->back()->with('error', 'Variant not available.');
        }

        if ($variantId) {
            $check = $this->enforceVariantStockOrFail($variantId, $requestQty);
            if (!$check['ok']) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $check['message'], 'available' => $check['available'], 'cart_count' => $this->cartQuantitySum()], 422);
                }
                return redirect()->back()->with('error', $check['message']);
            }
        }

        $cartItem = Cart::instance('shopping')->content()->filter(function ($cartItem) use ($request, $variantId) {
            $sameVariant = (int) ($cartItem->options->variant_id ?? 0) === (int) ($variantId ?? 0);
            return $cartItem->id == $request->id && $sameVariant;
        })->first();

        if ($cartItem) {
            $nextQty = (int) $cartItem->qty + $requestQty;
            if ($variantId) {
                $check = $this->enforceVariantStockOrFail($variantId, $nextQty);
                if (!$check['ok']) {
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $check['message'], 'available' => $check['available'], 'cart_count' => $this->cartQuantitySum()], 422);
                    }
                    return redirect()->back()->with('error', $check['message']);
                }
            }
            Cart::instance('shopping')->update($cartItem->rowId, $nextQty);
        } else {
            $cartPrice = (float) ($product->new_price ?? 0);
            $cartImage = optional($product->image)->image ?? '';
            $variantTitle = null;
            $variantName = null;
            if ($resolvedVariant) {
                $variantTitle = $resolvedVariant->title ?? null;
                if (!empty($resolvedVariant->price_override)) {
                    $cartPrice = (float) $resolvedVariant->price_override;
                }
                if (!empty($resolvedVariant->image)) {
                    $cartImage = (string) $resolvedVariant->image;
                }
                try {
                    $decoded = json_decode((string) ($resolvedVariant->attributes_json ?? ''), true);
                    if (is_array($decoded) && isset($decoded['attrs']) && is_array($decoded['attrs'])) {
                        $parts = array_values(array_filter(array_map(function ($v) {
                            $v = is_scalar($v) ? (string) $v : '';
                            $v = trim($v);
                            return $v === '' ? null : $v;
                        }, $decoded['attrs'])));
                        if (!empty($parts)) {
                            $variantName = implode(' / ', $parts);
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
            Cart::instance('shopping')->add([
                'id' => $product->id,
                'name' => $product->name,
                'qty' => $requestQty,
                'price' => $cartPrice,
                'options' => [
                    'slug' => $product->slug,
                    'image' => $cartImage,
                    'old_price' => $product->old_price,
                    'purchase_price' => $product->purchase_price,
                    'variant_id' => $variantId,
                    'variant_title' => $variantTitle,
                    'variant_values' => $selectedValueIds,
                    'variant_name' => $variantName,
                    'product_size' => $variantId ? ('variant_id:' . (int) $variantId) : ($request->product_size ?? ''),
                    'product_color' => $variantName ?: ($request->product_color ?? ''),
                    'pro_unit' => $request->pro_unit ?? '',
                ]
            ]);
        }

        $cart_count = $this->cartQuantitySum();

        $this->dispatchAddToCartTracking([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->new_price,
            'quantity' => $requestQty,
        ]);

        $this->recordCartActivity();
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully.',
                'cart_count' => $cart_count,
            ]);
        }
        
        return redirect()->route('customer.checkout');
    }

    public function cart_show()
    {
        $data = Cart::instance('shopping')->content();
        return view('frontend.pages.cart', compact('data'));
    }

    /**
     * Landing page order form.
     *
     * The landing form carries the SAME fields the storefront checkout saves
     * (name, phone, address, delivery area, payment method), so the order is
     * written by OrderPlacementService - the same order, shipping, payment and
     * order_details rows a normal product order writes. The visitor types the
     * details once, on the landing page, and the order is placed there.
     */
    public function landingOrder(Request $request)
    {
        $bkashEnabled = PaymentGateway::where(['status' => 1, 'type' => 'bkash'])->exists();
        $paymentMethods = $bkashEnabled ? ['Cash On Delivery', 'bkash'] : ['Cash On Delivery'];

        // The same field names and rules the storefront checkout uses, so the
        // saved order holds the values a normal product order holds.
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'address' => 'required|string|max:500',
            'area' => 'required|integer',
            'payment_method' => 'required|in:' . implode(',', $paymentMethods),
            'quantity' => 'nullable|integer|min:1|max:100',
        ]);

        $productId = (int) $request->input('product_id', 0);
        $qty = (int) $request->input('quantity', 1);
        if ($qty < 1) {
            $qty = 1;
        }

        if ($productId < 1) {
            return redirect()->back()->with('error', 'Product not selected.')->withInput();
        }

        $product = Product::with('image')->where('id', $productId)->first();
        if (!$product) {
            return redirect()->back()->with('error', 'Product not found.')->withInput();
        }

        $shippingArea = ShippingCharge::where('status', 1)->find((int) $request->input('area'));
        if (!$shippingArea) {
            return redirect()->back()->with('error', 'Select a delivery area.')->withInput();
        }

        $campaign = null;
        $campaignId = (int) $request->input('campaign_id', 0);
        if ($campaignId > 0) {
            $campaign = Campaign::where('id', $campaignId)->where('status', 1)->first();

            if (!$campaign) {
                return redirect()->back()->with('error', 'This offer is no longer available.')->withInput();
            }

            if ($campaign->product_id !== null && (int) $campaign->product_id !== $productId) {
                return redirect()->back()->with('error', 'This offer does not match the selected product.')->withInput();
            }

            $now = now();
            if ($campaign->start_date && $campaign->start_date->copy()->startOfDay()->gt($now)) {
                return redirect()->back()->with('error', 'This offer has not started yet.')->withInput();
            }
            if ($campaign->end_date && $campaign->end_date->copy()->endOfDay()->lt($now)) {
                return redirect()->back()->with('error', 'This offer has ended.')->withInput();
            }
        }

        $variantQuery = $request->input('variant', []);
        if (!is_array($variantQuery)) {
            $variantQuery = [$variantQuery];
        }
        [$resolvedVariant, $selectedValueIds] = $this->resolveSelectedVariant((int) $product->id, (array) $variantQuery);
        $variantId = $resolvedVariant ? (int) $resolvedVariant->id : null;

        if (!empty($variantQuery) && !$resolvedVariant) {
            return redirect()->back()->with('error', 'Variant not available.')->withInput();
        }

        if ($variantId) {
            $check = $this->enforceVariantStockOrFail($variantId, $qty);
            if (!$check['ok']) {
                return redirect()->back()->with('error', $check['message'])->withInput();
            }
        }

        $pricing = app(CampaignPricing::class)->resolve($product, $resolvedVariant, $campaign);
        $campaignApplies = $campaign !== null && $pricing['source'] === 'campaign';
        $orderPrice = (float) $pricing['price'];

        $variantName = null;
        $variantTitle = null;
        if ($resolvedVariant) {
            $variantTitle = $resolvedVariant->title ?? null;
            try {
                $decoded = json_decode((string) ($resolvedVariant->attributes_json ?? ''), true);
                if (is_array($decoded) && isset($decoded['attrs']) && is_array($decoded['attrs'])) {
                    $parts = array_values(array_filter(array_map(function ($v) {
                        return is_scalar($v) ? (string) $v : '';
                    }, $decoded['attrs']), function ($v) {
                        return $v !== '';
                    }));
                    $variantName = implode(' / ', $parts);
                }
            } catch (\Throwable $e) {
            }
        }

        // The guest account is resolved exactly like the storefront checkout
        // does, so the landing order belongs to the same customer record.
        try {
            $account = app(GuestCheckoutAccountService::class)->resolveForOrder(
                (string) $request->name,
                (string) $request->phone,
                (string) $request->address,
                (int) $shippingArea->id
            );
            $customerId = $account['customer_id'];
        } catch (\Throwable $e) {
            Log::error('Landing order account resolution failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'We could not start your account. Please try again.')
                ->withInput();
        }

        $lines = [[
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'qty' => $qty,
            'price' => $orderPrice,
            'regular_price' => $pricing['regular'],
            'purchase_price' => $product->purchase_price,
            'product_color' => $variantName ?: $variantTitle,
            'product_size' => $variantId ? ('variant_id:' . (int) $variantId) : null,
            'campaign_id' => $campaignApplies ? (int) $campaign->id : null,
            'variant_id' => $variantId,
        ]];

        // The funnel step the old cart-based flow recorded, so the landing
        // conversion keeps the same add-to-cart attribution.
        $this->dispatchAddToCartTracking([
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'price' => $orderPrice,
            'regular_price' => $pricing['regular'],
            'price_source' => $pricing['source'],
            'campaign_id' => $campaignApplies ? (int) $campaign->id : null,
            'quantity' => $qty,
        ]);

        $order = app(OrderPlacementService::class)->place(
            $lines,
            [
                'customer_id' => $customerId,
                'name' => (string) $request->name,
                'phone' => (string) $request->phone,
                'address' => (string) $request->address,
                'area' => $shippingArea->name,
                'payment_method' => (string) $request->payment_method,
            ],
            [
                'shipping_fee' => (float) $shippingArea->amount,
                'discount' => 0,
                'fraud_source' => 'landing',
            ]
        );

        app(OrderPlacementService::class)->trackPurchase($order, $lines, [
            'visitor_id' => $request->cookie('visitor_id'),
            'session_id' => $request->cookie('session_id'),
            'url' => $request->fullUrl(),
            'page_path' => '/landing-order',
            'user_data' => [
                'email' => $request->email ?? null,
                'phone' => $request->phone ?? null,
                'first_name' => $request->name ?? null,
            ],
        ]);

        // OrderPlaced (fired inside place()) sends the customer receipt and the
        // admin copy from the templates, so no hardcoded notification here.

        Toastr::success('Thanks, Your order place successfully', 'Success!');

        $siteSetting = GeneralSetting::where('status', 1)->first();
        try {
            app(SmsGatewayService::class)->send(
                (string) $request->phone,
                "Dear $request->name!\r\nYour order has been successfully placed. check your customer panel on our website to know more about your order. Thank you for using " . ($siteSetting->name ?? config('app.name')),
                'order'
            );
        } catch (\Throwable $e) {
            // no-op: a failed SMS must never fail the order
        }

        if ($request->payment_method === 'bkash') {
            return redirect('/bkash/checkout-url/create?order_id=' . $order->id);
        }

        return redirect()->route('customer.order_success', ['id' => $order->id]);
    }
    public function cart_store_by_now(Request $request)
    {
        $product = Product::where(['id' => $request->id])->first();
        if (!$product) {
            return redirect()->back()->with('error', 'Product not found.');
        }

        $variantQuery = $request->input('variant', []);
        if (!is_array($variantQuery)) {
            $variantQuery = [$variantQuery];
        }
        [$resolvedVariant, $selectedValueIds] = $this->resolveSelectedVariant((int) $product->id, (array) $variantQuery);
        $variantId = $resolvedVariant ? (int) $resolvedVariant->id : null;

        if (!empty($variantQuery) && !$resolvedVariant) {
            return redirect()->back()->with('error', 'Variant not available.');
        }

        if ($variantId) {
            $check = $this->enforceVariantStockOrFail($variantId, (int) $request->qty);
            if (!$check['ok']) {
                return redirect()->back()->with('error', $check['message']);
            }
        }

        $cartItem = Cart::instance('shopping')->content()->filter(function ($cartItem) use ($request, $variantId) {
            $sameVariant = (int) ($cartItem->options->variant_id ?? 0) === (int) ($variantId ?? 0);
            return $cartItem->id == $request->id && $sameVariant;
        })->first();

        if ($cartItem) {
            $nextQty = (int) $cartItem->qty + (int) $request->qty;
            if ($variantId) {
                $check = $this->enforceVariantStockOrFail($variantId, $nextQty);
                if (!$check['ok']) {
                    return redirect()->back()->with('error', $check['message']);
                }
            }
            Cart::instance('shopping')->update($cartItem->rowId, $nextQty);
        } else {
            $cartPrice = (float) ($product->new_price ?? 0);
            $cartImage = optional($product->image)->image ?? '';
            $variantTitle = null;
            $variantName = null;
            if ($resolvedVariant) {
                $variantTitle = $resolvedVariant->title ?? null;
                if (!empty($resolvedVariant->price_override)) {
                    $cartPrice = (float) $resolvedVariant->price_override;
                }
                if (!empty($resolvedVariant->image)) {
                    $cartImage = (string) $resolvedVariant->image;
                }
                try {
                    $decoded = json_decode((string) ($resolvedVariant->attributes_json ?? ''), true);
                    if (is_array($decoded) && isset($decoded['attrs']) && is_array($decoded['attrs'])) {
                        $parts = array_values(array_filter(array_map(function ($v) {
                            $v = is_scalar($v) ? (string) $v : '';
                            $v = trim($v);
                            return $v === '' ? null : $v;
                        }, $decoded['attrs'])));
                        if (!empty($parts)) {
                            $variantName = implode(' / ', $parts);
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            Cart::instance('shopping')->add([
                'id' => $product->id,
                'name' => $product->name,
                'qty' => $request->qty,
                'price' => $cartPrice,
                'options' => [
                    'slug' => $product->slug,
                    'image' => $cartImage,
                    'old_price' => $product->old_price,
                    'purchase_price' => $product->purchase_price,
                    'variant_id' => $variantId,
                    'variant_title' => $variantTitle,
                    'variant_values' => $selectedValueIds,
                    'variant_name' => $variantName,
                    'product_size' => $variantId ? ('variant_id:' . (int) $variantId) : ($request->product_size ?? ''),
                    'product_color' => $variantName ?: ($request->product_color ?? ''),
                    'pro_unit' => $request->pro_unit ?? '',
                ]
            ]);
        }

        $this->dispatchAddToCartTracking([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->new_price,
            'quantity' => (int) $request->qty,
        ]);

        $this->recordCartActivity();

        return redirect()->route('customer.checkout');
    }

    public function cart_remove(Request $request)
    {
        Cart::instance('shopping')->update($request->id, 0);
        $this->recordCartActivity();
        if ($request->wantsJson() || $request->ajax()) {
            $cartHtml = view('frontend.layouts.ajax.cart')->render();
            return response()->json([
                'success' => true,
                'cart_count' => $this->cartQuantitySum(),
                'cart_html' => $cartHtml,
            ]);
        }

        return redirect()->back();
    }

    public function cart_increment(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        if (!$item) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found.',
                ], 404);
            }
            return redirect()->back();
        }

        $qty = $item->qty + 1;

        $variantId = (int) ($item->options->variant_id ?? 0);
        if ($variantId > 0) {
            $check = $this->enforceVariantStockOrFail($variantId, $qty);
            if (!$check['ok']) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $check['message'],
                        'available' => $check['available'],
                        'cart_count' => $this->cartQuantitySum(),
                    ], 422);
                }
                return redirect()->back()->with('error', $check['message']);
            }
        }

        Cart::instance('shopping')->update($request->id, $qty);

        $this->recordCartActivity();

        if ($request->wantsJson() || $request->ajax()) {
            $cartHtml = view('frontend.layouts.ajax.cart')->render();
            return response()->json([
                'success' => true,
                'cart_count' => $this->cartQuantitySum(),
                'cart_html' => $cartHtml,
            ]);
        }

        return redirect()->back();
    }

    public function cart_decrement(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        $qty = $item->qty - 1;
        Cart::instance('shopping')->update($request->id, $qty);
        $this->recordCartActivity();

        if ($request->wantsJson() || $request->ajax()) {
            $cartHtml = view('frontend.layouts.ajax.cart')->render();
            return response()->json([
                'success' => true,
                'cart_count' => $this->cartQuantitySum(),
                'cart_html' => $cartHtml,
            ]);
        }

        return redirect()->back();
    }

    public function cart_count(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $this->cartQuantitySum(),
            ]);
        }

        return redirect()->back();
    }

    public function mobilecart_qty(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $this->cartQuantitySum(),
            ]);
        }

        return redirect()->back();
    }
}
