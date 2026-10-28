<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Childcategory;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use App\Models\Coupon;
use App\Services\ProductStockSync;
use Toastr;
use File;
use Illuminate\Support\Str;
use Image;
use DB;

class ProductController extends Controller
{
    use InteractsWithDataTable;

    private function extractSeoMeta(Request $request): array
    {
        $seoMeta = (array) $request->input('seo_meta', []);
        $seoImage = $request->file('seo_meta.meta_image');
        if ($seoImage) {
            $name = time() . '-' . $seoImage->getClientOriginalName();
            $name = preg_replace('/\.(jpg|jpeg|png|webp)$/i', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            $uploadDir = public_path('uploads/seo/');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $relativePath = 'uploads/seo/' . $name;
            $fullPath = public_path($relativePath);

            $img = Image::make($seoImage->getRealPath());
            $img->encode('webp', 90);
            $img->save($fullPath);

            $seoMeta['meta_image'] = $relativePath;
        }

        return $seoMeta;
    }
    public function getSubcategory(Request $request)
    {
        $subcategory = DB::table("subcategories")
            ->where("category_id", $request->category_id)
            ->pluck('subcategoryName', 'id');
        return response()->json($subcategory);
    }

    public function getChildcategory(Request $request)
    {
        $childcategory = DB::table("childcategories")
            ->where("subcategory_id", $request->subcategory_id)
            ->pluck('childcategoryName', 'id');
        return response()->json($childcategory);
    }

    function __construct()
    {
        $this->middleware('permission:product-list|product-create|product-edit|product-delete', ['only' => ['index', 'show', 'datatable']]);
        $this->middleware('permission:product-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:product-edit', ['only' => ['edit', 'update', 'quick_update']]);
        $this->middleware('permission:product-delete', ['only' => ['destroy']]);
    }

    /**
     * DataTables feed for the products grid.
     *
     * Same contract as orders: SQL does the searching, sorting and paging, and
     * each row is rendered once by backEnd/product/_row.blade.php and handed
     * over as an array of cell HTML.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function datatable(Request $request)
    {
        $draw    = (int) $request->input('draw', 1);
        $start   = max(0, (int) $request->input('start', 0));
        $length  = (int) $request->input('length', 20);
        $length  = $length > 0 ? min($length, 200) : 20;
        $search  = $this->dtSearch($request);

        $query = Product::query()->with('image', 'category');

        // Taken before the filters below, so recordsTotal stays the size of the
        // table and recordsFiltered is the size of what the search left.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhereHas('category', fn ($sub) => $sub->where('name', 'LIKE', "%{$search}%"));
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->input('category'));
        }

        $filtered = (clone $query)->count();

        // By column index, matching the <thead> the grid partial prints.
        $sortable = [1 => 'name', 4 => 'new_price', 5 => 'stock', 7 => 'status'];
        [$column, $dir] = $this->dtOrdering($request, $sortable, 'id');
        $query->orderBy($column, $dir);

        $rows = [];
        foreach ($query->skip($start)->take($length)->get() as $product) {
            $html = trim(view('backEnd.product._row', ['value' => $product])->render());
            $rows[] = array_map(
                static fn (string $cell): string => trim((string) preg_replace('#^<td[^>]*>|</td>$#i', '', trim($cell))),
                $this->splitRowIntoCells($html, 9)
            );
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
    }

    /**
     * Split a rendered <tr> into its cells, checking the count.
     *
     * Shared with the orders screen: the two lists differ only in how many
     * columns they print, so the check is the part that matters - if a row
     * gains or loses a cell, this throws instead of the grid quietly showing
     * every column one place out.
     *
     * @throws \RuntimeException
     */
    private function splitRowIntoCells(string $html, int $expected): array
    {
        $inner = preg_replace('#^<tr[^>]*>|</tr>$#i', '', trim($html)) ?? trim($html);
        $inner = preg_replace('#^</?tbody>#i', '', $inner) ?? $inner;

        $cells = preg_split('#</td>#i', $inner) ?: [];
        if (end($cells) === false || trim((string) end($cells)) === '') {
            array_pop($cells);
        }
        $cells = array_values(array_filter(array_map('trim', $cells), static fn ($c) => $c !== ''));

        if (count($cells) !== $expected) {
            throw new \RuntimeException(sprintf(
                '%s rendered %d cells but its grid has %d columns.',
                basename($html) === '' ? 'row partial' : 'row partial',
                count($cells),
                $expected
            ));
        }

        return $cells;
    }

    public function index(Request $request)
    {
        if ($request->keyword) {
            $data = Product::orderBy('id', 'DESC')->where('name', 'LIKE', '%' . $request->keyword . "%")->with('image', 'category')->paginate(10);
        } else {
            $data = Product::orderBy('id', 'DESC')->with('image', 'category')->paginate(10);
        }

        $product_kpis = (object) [
            'total' => Product::count(),
            'active' => Product::where('status', 1)->count(),
            'inactive' => Product::where('status', 0)->count(),
            'hot_deals' => Product::where('topsale', 1)->count(),
        ];
        $generalSetting = \App\Models\GeneralSetting::first();
        return view('backEnd.product.index', compact('data', 'generalSetting', 'product_kpis'));
    }

    public function show($id)
    {
        return redirect()->route('products.edit', ['id' => $id]);
    }

    public function create()
    {
        $categories = Category::where('parent_id', '=', '0')->where('status', 1)->select('id', 'name', 'status')->with('childrenCategories')->get();
        $brands = Brand::where('status', '1')->select('id', 'name', 'status')->get();
        $colors = Color::where('status', '1')->get();
        $sizes = Size::where('status', '1')->get();
        $coupons = Coupon::orderByDesc('id')->running()->get(['id', 'code', 'end_at', 'status']);
        $attributes = DB::table('attributes')->where('status', 1)->orderBy('id', 'asc')->get(['id', 'name', 'slug', 'kind', 'status']);
        $attributeValues = DB::table('attribute_values')
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'attribute_id', 'value', 'code', 'multiplier', 'base_unit', 'kind', 'status']);

        return view('backEnd.product.create', compact('categories', 'brands', 'colors', 'sizes', 'coupons', 'attributes', 'attributeValues'));
    }

    public function store(Request $request)
    {
        // Validate request fields
        $this->validate($request, [
            'name' => 'required',
            'category_id' => 'required',
            'coupon_ids' => 'nullable|array',
            'coupon_ids.*' => 'integer|exists:coupons,id',
            'variant_attrs_ids' => 'array',
            'variant_attrs_ids.*' => 'nullable|string',
            'variant_attrs_text' => 'array',
            'variant_attrs_text.*' => 'nullable|string',
            'variant_price_override' => 'array',
            'variant_price_override.*' => 'nullable|integer|min:0',
            'variant_purchase_price' => 'array',
            'variant_purchase_price.*' => 'nullable|integer|min:0',
            'variant_sale_price' => 'array',
            'variant_sale_price.*' => 'nullable|integer|min:0',
            'variant_discount_price' => 'array',
            'variant_discount_price.*' => 'nullable|integer|min:0',
            'variant_weight_gram' => 'array',
            'variant_weight_gram.*' => 'nullable|integer|min:0',
            'variant_initial_stock' => 'array',
            'variant_initial_stock.*' => 'nullable|integer|min:0',
            'variant_image' => 'array',
            'variant_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'new_price' => 'required',
            'purchase_price' => 'required',
            'stock' => 'required',
            'description' => 'required',
            'image' => 'required', // Add validation for image
            'image.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Validation for each image file
            'title.*' => 'nullable|string|max:255', // Validation for each image title
            // Allow common downloadable docs up to 20MB (Laravel max is in KB).
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:20480',
        ]);

        $docFile = $request->file('document');
        $docRelativePath = null;
        $docTargetDir = null;
        $docFilename = null;
        if ($docFile) {
            $ext = strtolower((string) $docFile->getClientOriginalExtension());
            $ext = $ext !== '' ? ('.' . $ext) : '';
            $docFilename = time() . '-' . Str::random(10) . $ext;
            $docRelativePath = 'uploads/product-documents/' . $docFilename;
            $docTargetDir = public_path('uploads/product-documents');
        }

        $createdProduct = null;

        DB::transaction(function () use ($request, $docRelativePath, &$createdProduct) {
            $now = now();
            // Generate last product ID for slug and product code
            $last_id = Product::orderBy('id', 'desc')->select('id')->first();
            $last_id = $last_id ? $last_id->id + 1 : 1;

            // Prepare input fields, excluding arrays and non-products-table fields
            $input = $request->except([
                'image',
                'title',
                'proSize',
                'proColor',
                'document',
                'seo_meta',
                'coupon_ids',
                'variant_attrs_ids',
                'variant_attrs_text',
                'variant_price_override',
                'variant_purchase_price',
                'variant_sale_price',
                'variant_discount_price',
                'variant_weight_gram',
                'variant_initial_stock',
                'variant_image',
            ]);

            $input['slug'] = strtolower(preg_replace('/[\/\s]+/', '-', $request->name . '-' . $last_id));
            $input['status'] = $request->status ? 1 : 0;
            $input['topsale'] = $request->topsale ? 1 : 0;
            $input['feature_product'] = $request->feature_product ? 1 : 0;
            $input['is_recommended'] = $request->is_recommended ? 1 : 0;
            $input['product_code'] = 'P' . str_pad($last_id, 4, '0', STR_PAD_LEFT);

            if ($docRelativePath) {
                $input['document'] = $docRelativePath;
            }

            // Now create product
            $save_data = Product::create($input);
            $createdProduct = $save_data;

            $couponIds = $request->input('coupon_ids', []);
            if (is_array($couponIds)) {
                $save_data->coupons()->sync($couponIds);
            }

            if ($request->has('seo_meta')) {
                $save_data->updateSeoMeta($this->extractSeoMeta($request));
            }

            // B16: the old sizes()/colors() attach branches were removed here.
            // No form or script posts `proSize`/`proColor` (verified across all
            // views, JS and routes), and store()/update() strip them from the
            // mass-assign input anyway, so the `if ($request->has('proSize'))`
            // branches could never fire. Product variants are handled by the
            // product_variants flow below, not this vestigial pivot.

            // Handle image uploads with titles
            $images = $request->file('image');
            $titles = $request->title; // This will fetch the titles as an array

            if ($images) {
                foreach ($images as $key => $image) {
                    $name = time() . '-' . $image->getClientOriginalName();
                    $name = strtolower(preg_replace('/\s+/', '-', $name));
                    $uploadPath = public_path('uploads/product');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    $image->move($uploadPath, $name);
                    $imageUrl = 'uploads/product/' . $name;

                    // Save the image details including title in Productimage table
                    $pimage = new Productimage();
                    $pimage->product_id = $save_data->id;
                    $pimage->image = $imageUrl;
                    /*
                     * B9: the create form has no per-image title[] input (a
                     * `multiple` gallery upload cannot be paired with a fixed
                     * set of title fields), so $titles was always null here and
                     * EVERY created image got title=''. Use a provided title when
                     * present, otherwise fall back to the original filename so an
                     * image is never untitled.
                     */
                    $providedTitle = is_array($titles) ? trim((string) ($titles[$key] ?? '')) : '';
                    $pimage->title = $providedTitle !== ''
                        ? $providedTitle
                        : pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $pimage->save();
                }
            }

            // Inventory: create default variant + seed initial stock into ledger+cache
            // Note: If per-variant initial stock is provided, product-level stock should not be double-counted.
            $qty = (int) ($save_data->stock ?? 0);
            if ($qty < 0) {
                $qty = 0;
            }

            $defaultSku = ($save_data->product_code ? $save_data->product_code : ('P' . $save_data->id)) . '-DEFAULT';
            $variantId = DB::table('product_variants')->where('sku', $defaultSku)->value('id');
            if (!$variantId) {
                $variantId = DB::table('product_variants')->insertGetId([
                    'product_id' => $save_data->id,
                    'sku' => $defaultSku,
                    'title' => 'Default',
                    'price_override' => null,
                    'weight_gram' => null,
                    'status' => 1,
                    'attributes_json' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('variant_stocks')->updateOrInsert(
                ['variant_id' => $variantId],
                [
                    'on_hand' => $qty,
                    'reserved' => 0,
                    'available' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $variantInitialStocks = (array) $request->input('variant_initial_stock', []);
            $hasVariantInitialStock = false;
            $variantInitialStockSum = 0;
            foreach ($variantInitialStocks as $v) {
                $iv = ($v !== null && $v !== '') ? (int) $v : 0;
                if ($iv > 0) {
                    $hasVariantInitialStock = true;
                    $variantInitialStockSum += $iv;
                }
            }

            // Keep legacy + current order flow consistent:
            // Orders currently deduct stock using the DEFAULT variant, so ensure it reflects total stock.
            if ($hasVariantInitialStock) {
                $save_data->stock = $variantInitialStockSum;
                $save_data->save();

                DB::table('variant_stocks')->where('variant_id', $variantId)->update([
                    'on_hand' => $variantInitialStockSum,
                    'available' => $variantInitialStockSum,
                    'updated_at' => $now,
                ]);
            }

            // If the user is providing per-variant initial stock, skip writing product-level initial stock
            // to the default variant to avoid double-counting.
            if (!$hasVariantInitialStock && $qty > 0) {
                DB::table('inventory_movements')->insert([
                    'variant_id' => $variantId,
                    'type' => 'ADJUSTMENT',
                    'qty_delta' => $qty,
                    'ref_type' => 'product_create',
                    'ref_id' => $save_data->id,
                    'created_by' => auth()->id(),
                    'note' => 'Initial stock on product create',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $variantAttrsIds = (array) $request->input('variant_attrs_ids', []);
            $variantAttrsText = (array) $request->input('variant_attrs_text', []);
            $variantPrices = (array) $request->input('variant_price_override', []);
            $variantPurchasePrices = (array) $request->input('variant_purchase_price', []);
            $variantSalePrices = (array) $request->input('variant_sale_price', []);
            $variantDiscountPrices = (array) $request->input('variant_discount_price', []);
            $variantWeights = (array) $request->input('variant_weight_gram', []);
            $variantInitialStocks = (array) $request->input('variant_initial_stock', []);
            $variantImages = (array) $request->file('variant_image', []);

            $rowsCount = max(
                count($variantAttrsIds),
                count($variantAttrsText),
                count($variantPrices),
                count($variantPurchasePrices),
                count($variantSalePrices),
                count($variantDiscountPrices),
                count($variantWeights),
                count($variantInitialStocks)
            );
            if ($rowsCount > 0) {
                $productCode = $save_data->product_code ? $save_data->product_code : ('P' . $save_data->id);
                $weightCache = [];

                for ($i = 0; $i < $rowsCount; $i++) {
                    $idsJson = $variantAttrsIds[$i] ?? null;
                    $textJson = $variantAttrsText[$i] ?? null;
                    if (!$idsJson || !$textJson) {
                        continue;
                    }

                    $attrsIds = json_decode($idsJson, true);
                    $attrsText = json_decode($textJson, true);
                    if (!is_array($attrsIds) || !is_array($attrsText) || empty($attrsText)) {
                        continue;
                    }

                    $attrsTextMap = $attrsText;
                    if (isset($attrsText['attrs']) && is_array($attrsText['attrs'])) {
                        $attrsTextMap = $attrsText['attrs'];
                    }

                    $attrsIdsMap = $attrsIds;
                    if (isset($attrsIds['ids']) && is_array($attrsIds['ids'])) {
                        $attrsIdsMap = $attrsIds['ids'];
                    }

                    $parts = [];
                    foreach ($attrsTextMap as $k => $v) {
                        $parts[] = $k . ': ' . $v;
                    }
                    $title = implode(' | ', $parts);

                    $sku = $productCode . '-V' . ($i + 1);

                    $imagePath = null;
                    if (isset($variantImages[$i]) && $variantImages[$i]) {
                        $img = $variantImages[$i];
                        $name = time() . '-' . $img->getClientOriginalName();
                        $name = strtolower(preg_replace('/\s+/', '-', $name));
                        $uploadPath = public_path('uploads/variant');
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }
                        $img->move($uploadPath, $name);
                        $imagePath = 'uploads/variant/' . $name;
                    }

                    $weightGram = null;
                    if (isset($attrsIdsMap['weight'])) {
                        $weightId = (int) $attrsIdsMap['weight'];
                        if (!array_key_exists($weightId, $weightCache)) {
                            $val = DB::table('attribute_values')->where('id', $weightId)->first(['multiplier', 'base_unit']);
                            $weightCache[$weightId] = ($val && ($val->base_unit ?? null) === 'g' && $val->multiplier !== null)
                                ? (int) $val->multiplier
                                : null;
                        }
                        $weightGram = $weightCache[$weightId];
                    }

                    // Explicit override from UI input takes priority
                    if (isset($variantWeights[$i]) && $variantWeights[$i] !== null && $variantWeights[$i] !== '') {
                        $weightGram = (int) $variantWeights[$i];
                    }

                    $purchasePrice = isset($variantPurchasePrices[$i]) && $variantPurchasePrices[$i] !== null && $variantPurchasePrices[$i] !== ''
                        ? (int) $variantPurchasePrices[$i]
                        : null;
                    $salePrice = isset($variantSalePrices[$i]) && $variantSalePrices[$i] !== null && $variantSalePrices[$i] !== ''
                        ? (int) $variantSalePrices[$i]
                        : null;
                    $discountPrice = isset($variantDiscountPrices[$i]) && $variantDiscountPrices[$i] !== null && $variantDiscountPrices[$i] !== ''
                        ? (int) $variantDiscountPrices[$i]
                        : null;

                    // Compatibility: frontend/cart uses price_override as the sale price override.
                    $priceOverride = null;
                    if ($salePrice !== null) {
                        $priceOverride = $salePrice;
                    } elseif (isset($variantPrices[$i]) && $variantPrices[$i] !== null && $variantPrices[$i] !== '') {
                        $priceOverride = (int) $variantPrices[$i];
                    }

                    $newVariantId = DB::table('product_variants')->insertGetId([
                        'product_id' => $save_data->id,
                        'sku' => $sku,
                        'title' => $title,
                        'image' => $imagePath,
                        'price_override' => $priceOverride,
                        'purchase_price' => $purchasePrice,
                        'sale_price' => $salePrice,
                        'discount_price' => $discountPrice,
                        'weight_gram' => $weightGram,
                        'status' => 1,
                        'attributes_json' => json_encode([
                            'attrs' => $attrsTextMap,
                            'ids' => $attrsIdsMap,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $initialStock = 0;
                    if (isset($variantInitialStocks[$i]) && $variantInitialStocks[$i] !== null && $variantInitialStocks[$i] !== '') {
                        $initialStock = (int) $variantInitialStocks[$i];
                        if ($initialStock < 0) {
                            $initialStock = 0;
                        }
                    }

                    // New variants won't have a stock row yet; avoid extra exists() query per variant.
                    DB::table('variant_stocks')->insert([
                        'variant_id' => $newVariantId,
                        'on_hand' => $initialStock,
                        'reserved' => 0,
                        'available' => $initialStock,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($initialStock > 0) {
                        DB::table('inventory_movements')->insert([
                            'variant_id' => $newVariantId,
                            'type' => 'ADJUSTMENT',
                            'qty_delta' => $initialStock,
                            'ref_type' => 'product_create_variant',
                            'ref_id' => $save_data->id,
                            'created_by' => auth()->id(),
                            'note' => 'Initial stock on variant create (product create)',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        });

        // Move the document file AFTER DB commit so a file-system hiccup does not block product creation.
        if ($docFile && $docTargetDir && $docFilename) {
            try {
                if (!file_exists($docTargetDir)) {
                    mkdir($docTargetDir, 0755, true);
                }
                $docFile->move($docTargetDir, $docFilename);
            } catch (\Throwable $e) {
                // Document is optional: keep product but clear the broken path to avoid dead links.
                if ($createdProduct && $createdProduct->id) {
                    Product::where('id', (int) $createdProduct->id)->update(['document' => null]);
                }
                // Bubble up a friendly error without losing the product insert.
                Toastr::warning('Warning', 'Product saved, but document upload failed. Please upload again from Edit.');
            }
        }

        Toastr::success('Success', 'Data inserted successfully');
        return redirect()->route('products.index');
    }


    public function edit($id)
    {
        $edit_data = Product::with('images', 'coupons')->find($id);
        $categories = Category::where('parent_id', '=', '0')->where('status', 1)->select('id', 'name', 'status')->get();
        $categoryId = Product::find($id)->category_id;
        $subcategoryId = Product::find($id)->subcategory_id;
        $subcategory = Subcategory::where('category_id', '=', $categoryId)->select('id', 'subcategoryName', 'status')->get();
        $childcategory = Childcategory::where('subcategory_id', '=', $subcategoryId)->select('id', 'childcategoryName', 'status')->get();
        $brands = Brand::where('status', '1')->select('id', 'name', 'status')->get();
        $coupons = Coupon::orderByDesc('id')->running()->get(['id', 'code', 'end_at', 'status']);
        $selected_coupon_ids = $edit_data ? $edit_data->coupons()->pluck('coupons.id')->values()->all() : [];
        $totalsizes = Size::where('status', 1)->get();
        $totalcolors = Color::where('status', 1)->get();
        $selectcolors = Productcolor::where('product_id', $id)->get();
        $selectsizes = Productsize::where('product_id', $id)->get();
        $variants = DB::table('product_variants')
            ->where('product_id', (int) $id)
            ->orderBy('id', 'asc')
            ->get(['id', 'sku', 'title', 'status']);

        return view('backEnd.product.edit', compact('edit_data', 'categories', 'subcategory', 'childcategory', 'brands', 'coupons', 'selected_coupon_ids', 'selectcolors', 'selectsizes', 'totalsizes', 'totalcolors', 'variants'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'category_id' => 'required',
            'new_price' => 'required',
            'purchase_price' => 'required',
            'description' => 'required',

            'coupon_ids' => 'nullable|array',
            'coupon_ids.*' => 'integer|exists:coupons,id',

            'title.*' => 'nullable|string|max:255',
            // Allow common downloadable docs up to 20MB (Laravel max is in KB).
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:20480',
        ]);

        $update_data = Product::find($request->id);
        $input = $request->except(['image', 'title', 'files', 'proSize', 'proColor', 'document', 'seo_meta', 'stock', 'coupon_ids']);
        $last_id = Product::orderBy('id', 'desc')->select('id')->first();
        $input['slug'] = strtolower(preg_replace('/[\/\s]+/', '-', $request->name . '-' . $last_id->id));
        $input['status'] = $request->status ? 1 : 0;
        $input['topsale'] = $request->topsale ? 1 : 0;
        $input['feature_product'] = $request->feature_product ? 1 : 0;
        $input['is_recommended'] = $request->is_recommended ? 1 : 0;

        if ($request->hasFile('document')) {
            $doc = $request->file('document');
            $ext = strtolower((string) $doc->getClientOriginalExtension());
            $ext = $ext !== '' ? ('.' . $ext) : '';
            $name = time() . '-' . Str::random(10) . $ext;
            $uploadPath = public_path('uploads/product-documents');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $doc->move($uploadPath, $name);
            $input['document'] = 'uploads/product-documents/' . $name;
        }

        $update_data->update($input);

        if ($request->has('seo_meta')) {
            $update_data->updateSeoMeta($this->extractSeoMeta($request));
        }
        // B16: removed the vestigial `$update_data->sizes()->sync($request->proSize)`
        // and colors() sync. No form posts those keys, so they always synced to
        // null on every product update - a pointless write clearing an empty
        // pivot. Variants are handled by the product_variants flow.

        $couponIds = $request->input('coupon_ids', []);
        if (is_array($couponIds)) {
            $update_data->coupons()->sync($couponIds);
        }


        $images = $request->file('image');
        $titles = $request->title;
        if ($images) {
            foreach ($images as $key => $image) {
                $name =  time() . '-' . $image->getClientOriginalName();
                $name = strtolower(preg_replace('/\s+/', '-', $name));

                $uploadPath = public_path('uploads/product/');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $name);
                $imageUrl = 'uploads/product/' . $name;

                $pimage = new Productimage();
                $pimage->product_id = $update_data->id;
                $pimage->image = $imageUrl;
                $pimage->title = $titles[$key] ?? '';
                $pimage->save();
            }
        }

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('products.index');
    }

    public function price_edit()
    {
        $products = DB::table('products')->select('id', 'name', 'status', 'old_price', 'new_price', 'stock')->where('status', 1)->get();
        return view('backEnd.product.price_edit', compact('products'));
    }

    public function price_update(Request $request)
    {
        $ids = $request->ids;
        $oldPrices = $request->old_price;
        $newPrices = $request->new_price;
        $stocks = $request->stock;

        $sync = app(ProductStockSync::class);
        $syncedVariants = 0;

        foreach ($ids as $key => $id) {
            $product = Product::select('id', 'name', 'status', 'old_price', 'new_price', 'stock')->find($id);

            if ($product) {
                $product->update([
                    'old_price' => $oldPrices[$key],
                    'new_price' => $newPrices[$key],
                    'stock' => $stocks[$key],
                ]);

                /*
                 * Push the new figure onto the variants.
                 *
                 * This only wrote products.stock, while the storefront reads
                 * variant_stocks exclusively. So restocking a product from this
                 * screen left the shop still saying "out of stock" - the admin
                 * row said 50 and the cart could not add it, with nothing to
                 * explain the disagreement. Every other write path already
                 * mirrors both; this one had been missed.
                 *
                 * Variants stocked individually are left alone; see
                 * ProductStockSync for why.
                 */
                $syncedVariants += $sync->syncForProduct($product->fresh(), false, auth()->id());
            }
        }

        Toastr::success(
            'Success',
            'Price update successfully'
            . ($syncedVariants > 0 ? ' (stock synced to ' . $syncedVariants . ' variant)' : '')
        );
        return redirect()->back();
    }

    public function inactive(Request $request)
    {
        $inactive = Product::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Product::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Product::find($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }

    public function imgdestroy(Request $request)
    {
        // POST + CSRF only (this was a GET link, so any page could fire an
        // image deletion). findOrFail: an unknown id used to be a fatal error
        // when ->image was read from null.
        $request->validate(['id' => 'required|integer']);
        $delete_data = Productimage::findOrFail($request->input('id'));

        // `image` is a path relative to public/. Only ever delete inside
        // uploads/, so a corrupted row cannot point File::delete at, say, .env.
        $path = ltrim(preg_replace('~^public/~', '', (string) $delete_data->image), '/');
        if ($path !== '' && str_starts_with($path, 'uploads/')) {
            File::delete(public_path($path));
        }

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }

    public function update_deals(Request $request)
    {
        $products = Product::whereIn('id', $request->input('product_ids'))->update(['topsale' => $request->status]);
        return response()->json(['status' => 'success', 'message' => 'Hot deals product status change']);
    }

    public function update_feature(Request $request)
    {
        $products = Product::whereIn('id', $request->input('product_ids'))->update(['feature_product' => $request->status]);
        return response()->json(['status' => 'success', 'message' => 'Feature product status change']);
    }

    public function update_status(Request $request)
    {
        $products = Product::whereIn('id', $request->input('product_ids'))->update(['status' => $request->status]);
        return response()->json(['status' => 'success', 'message' => 'Product status change successfully']);
    }

    /**
     * Inline edit of a single product's price or stock, from the list.
     *
     * The product grid shows price and stock as read-only text. Changing either
     * one used to mean opening a separate bulk page that loaded every active
     * product into one form and posted the lot. This is the small, honest
     * version of that: one product, one field, one POST.
     *
     * Only the two numeric columns are writable, and only as whole numbers -
     * so a crafted request cannot set a price of "-100" or a stock of "5; drop
     * table products", which a mass-assignment route would take verbatim.
     */
    public function quick_update(Request $request)
    {
        $data = $request->validate([
            'id'     => ['required', 'integer', 'exists:products,id'],
            'price'  => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'stock'  => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $product = Product::findOrFail($data['id']);

        $changed = [];
        // new_price is what the grid prints; keep old_price consistent so the
        // "was" price on the storefront does not end up above the new one.
        if (array_key_exists('price', $data) && $data['price'] !== null) {
            $newPrice = (float) $data['price'];
            if ((float) $product->old_price < $newPrice) {
                $product->old_price = $newPrice;
                $changed[] = 'old_price';
            }
            $product->new_price = $newPrice;
            $changed[] = 'new_price';
        }

        $stockChanged = array_key_exists('stock', $data) && $data['stock'] !== null;

        if ($stockChanged) {
            $product->stock = (int) $data['stock'];
            $changed[] = 'stock';
        }

        if ($changed === []) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Nothing to change',
            ]);
        }

        $product->save();

        if ($stockChanged) {
            /*
             * Same split as price_update: the grid wrote products.stock and
             * nothing else, so a quick restock from the list did not reach the
             * storefront, which reads variant_stocks. See ProductStockSync.
             */
            app(ProductStockSync::class)->syncForProduct($product->fresh(), false, auth()->id());
        }

        /*
         * Re-read before answering. The price column is an INT, so MySQL
         * rounds or truncates whatever was typed - 777.25 is stored as 777.
         * Echoing the typed value back would paint 777.25 on the row and then
         * show 777 on the next draw, and the number on screen would never
         * match the number in the database. The response therefore carries the
         * stored value, and the grid repaints with exactly what is in the table.
         */
        $product->refresh();

        return response()->json([
            'status'  => 'success',
            'message' => 'Updated',
            'id'      => $product->id,
            'price'   => (float) $product->new_price,
            'stock'   => (int) $product->stock,
            'fields'  => $changed,
        ]);
    }
}
