<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VariantAttributes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VariantController extends Controller
{
    public function index(Request $request)
    {
        $variants = DB::table('product_variants as v')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->select(
                'v.id',
                'v.product_id',
                'v.sku',
                'v.title',
                'v.price_override',
                'v.weight_gram',
                'v.status',
                'v.created_at',
                'p.name as product_name'
            )
            ->orderBy('v.id', 'desc')
            ->paginate(50);

        return view('backEnd.variants.index', compact('variants'));
    }

    public function create(Request $request)
    {
        $products = DB::table('products')
            ->select('id', 'name', 'product_code')
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        return view('backEnd.variants.create', compact('products') + [
            'attributes' => app(VariantAttributes::class)->selectableAttributes(),
            'selected'   => [],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'sku' => 'required|string|max:80|unique:product_variants,sku',
            'title' => 'nullable|string|max:190',
            'price_override' => 'nullable|integer|min:0',
            'weight_gram' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',

            /*
             * These three, plus the attribute selection, are what make the
             * storefront's variant swap actually work.
             *
             * product_variants.image is read by FrontendController::variantDetail
             * and written into the page as the main photo when a swatch is
             * picked. price_override is the variant price. Without either, every
             * swatch shows the product's own price and photo, so picking a
             * variant appears to do nothing.
             *
             * These columns existed and were validated at product-creation time
             * (ProductController) but this screen could not set or change them,
             * so a variant could never be given its own price or picture after
             * the product was created.
             */
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'purchase_price' => 'nullable|integer|min:0',
            'sale_price' => 'nullable|integer|min:0',
            'discount_price' => 'nullable|integer|min:0',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'nullable|integer',
        ]);

        $productExists = DB::table('products')->where('id', (int) $request->product_id)->exists();
        if (!$productExists) {
            return back()->withInput()->with('error', 'Invalid product selected');
        }

        $attributes = app(VariantAttributes::class);

        DB::table('product_variants')->insert([
            'image' => $this->storeVariantImage($request),
            'product_id' => (int) $request->product_id,
            'sku' => $request->sku,
            'title' => $request->title,
            'price_override' => $request->price_override !== null ? (int) $request->price_override : null,
            'weight_gram' => $request->weight_gram !== null ? (int) $request->weight_gram : null,
            'purchase_price' => $this->blankToNull($request->input('purchase_price')),
            'sale_price' => $this->blankToNull($request->input('sale_price')),
            'discount_price' => $this->blankToNull($request->input('discount_price')),
            'status' => $request->status !== null ? (int) $request->status : 1,
            // Previously hard-coded to null, which is why no variant created
            // through this screen ever showed a swatch on the product page.
            'attributes_json' => $attributes->buildFromRequest($request),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.variants.index')->with('success', 'Variant created successfully');
    }

    public function edit(Request $request, $id)
    {
        $variant = DB::table('product_variants')->where('id', (int) $id)->first();
        if (!$variant) {
            abort(404);
        }

        $products = DB::table('products')
            ->select('id', 'name', 'product_code')
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        return view('backEnd.variants.edit', compact('variant', 'products') + [
            'attributes' => app(VariantAttributes::class)->selectableAttributes(),
            'selected'   => app(VariantAttributes::class)->selectionOf($variant->attributes_json ?? null),
        ]);
    }

    public function update(Request $request, $id)
    {
        $variantId = (int) $id;

        $variant = DB::table('product_variants')->where('id', $variantId)->first();
        if (!$variant) {
            abort(404);
        }

        $request->validate([
            'product_id' => 'required|integer',
            'sku' => 'required|string|max:80|unique:product_variants,sku,' . $variantId,
            'title' => 'nullable|string|max:190',
            'price_override' => 'nullable|integer|min:0',
            'weight_gram' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'purchase_price' => 'nullable|integer|min:0',
            'sale_price' => 'nullable|integer|min:0',
            'discount_price' => 'nullable|integer|min:0',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'nullable|integer',
        ]);

        $productExists = DB::table('products')->where('id', (int) $request->product_id)->exists();
        if (!$productExists) {
            return back()->withInput()->with('error', 'Invalid product selected');
        }

        DB::table('product_variants')->where('id', $variantId)->update([
            'product_id' => (int) $request->product_id,
            'sku' => $request->sku,
            'title' => $request->title,
            'price_override' => $request->price_override !== null ? (int) $request->price_override : null,
            'weight_gram' => $request->weight_gram !== null ? (int) $request->weight_gram : null,
            'purchase_price' => $this->blankToNull($request->input('purchase_price')),
            'sale_price' => $this->blankToNull($request->input('sale_price')),
            'discount_price' => $this->blankToNull($request->input('discount_price')),
            'status' => $request->status !== null ? (int) $request->status : 1,
            'attributes_json' => app(VariantAttributes::class)->buildFromRequest($request),
            'updated_at' => now(),
        ]);

        // Only overwrite the stored picture when a NEW file was actually
        // uploaded. Assigning null when the field is simply left empty would
        // silently wipe a picture the admin had already set.
        if ($request->hasFile('image')) {
            $newImage = $this->storeVariantImage($request);

            if ($newImage !== null) {
                DB::table('product_variants')
                    ->where('id', $variantId)
                    ->update(['image' => $newImage, 'updated_at' => now()]);
            }
        }

        return redirect()->route('admin.variants.index')->with('success', 'Variant updated successfully');
    }

    /**
     * Save an uploaded variant picture and return the stored path.
     *
     * Stored relative to public_path(), the same convention ProductController
     * uses for product gallery images, so MediaPath::url() resolves it without
     * a second convention to support.
     */
    private function storeVariantImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');

        $directory = public_path('uploads/variant');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Unique name: the original may collide between two variants of the
        // same product, and two same-named files would overwrite each other.
        $filename = uniqid('variant-', true) . '.' . $file->getClientOriginalExtension();

        $file->move($directory, $filename);

        return 'uploads/variant/' . $filename;
    }

    /**
     * An empty form field means "leave it unset", not "set it to zero".
     *
     * Without this a price box the admin cleared would become 0 and the
     * storefront would show the variant as free.
     */
    private function blankToNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
