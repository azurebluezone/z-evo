<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\Request;
use Toastr;
use File;

class FlashSaleController extends Controller
{
    public function index()
    {
        $flash_sales = FlashSale::orderByDesc('id')->withCount('products')->get();

        /*
         * Headline numbers for the manage screen. They come from the same
         * scopes the storefront uses (`active`, `running`), so the dashboard
         * cannot disagree with what customers actually see.
         */
        $stats = [
            'total' => FlashSale::count(),
            'active' => FlashSale::active()->count(),
            'running' => FlashSale::running()->count(),
            'products' => (int) \DB::table('flash_sale_products')->distinct()->count('product_id'),
        ];

        return view('backEnd.flash-sale.index', compact('flash_sales', 'stats'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->select('id', 'name', 'status')->orderByDesc('id')->limit(500)->get();
        return view('backEnd.flash-sale.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date|after_or_equal:now',
            'end_time' => 'required|date|after:start_time',
            'banner_link' => 'nullable|string|max:255',
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $input = $request->only(['title', 'start_time', 'end_time', 'banner_link']);
        $input['banner_link'] = $input['banner_link'] ?: 'javascript:void(0)';
        $input['status'] = $request->has('status') ? 1 : 0;

        if ($request->hasFile('banner_image')) {
            $uploadPath = 'public/uploads/flash-sale/';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $image = $request->file('banner_image');
            $name = time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
            $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
            $name = strtolower($name);
            $imageUrl = $uploadPath . $name;
            $image->move($uploadPath, $name);
            $input['banner_image'] = $imageUrl;
        }

        $flashSale = FlashSale::create($input);

        $productIds = $request->input('product_ids', []);
        $productIds = array_values(array_unique(array_slice($productIds, 0, 10)));
        $attach = [];
        $order = 0;
        foreach ($productIds as $pid) {
            $attach[$pid] = ['sort_order' => $order++];
        }
        if (!empty($attach)) {
            $flashSale->products()->sync($attach);
        }

        return redirect()->route('flash-sales.index')->with('success', 'Flash sale created successfully');
    }

    public function edit($id)
    {
        $flash_sale = FlashSale::with('products')->findOrFail($id);
        $products = Product::where('status', 1)->select('id', 'name', 'status')->orderByDesc('id')->limit(500)->get();
        $selected_product_ids = $flash_sale->products->pluck('id')->values()->all();

        return view('backEnd.flash-sale.edit', compact('flash_sale', 'products', 'selected_product_ids'));
    }

    public function update(Request $request)
    {
        $flashSale = FlashSale::findOrFail($request->id);

        $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date|after_or_equal:now',
            'end_time' => 'required|date|after:start_time',
            'banner_link' => 'nullable|string|max:255',
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $input = $request->only(['title', 'start_time', 'end_time', 'banner_link']);
        $input['banner_link'] = $input['banner_link'] ?: 'javascript:void(0)';
        $input['status'] = $request->has('status') ? 1 : 0;

        if ($request->hasFile('banner_image')) {
            $uploadPath = 'public/uploads/flash-sale/';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $image = $request->file('banner_image');
            $name = time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
            $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
            $name = strtolower($name);
            $imageUrl = $uploadPath . $name;
            $image->move($uploadPath, $name);
            $input['banner_image'] = $imageUrl;

            if (!empty($flashSale->banner_image)) {
                try {
                    File::delete($flashSale->banner_image);
                } catch (\Throwable $e) {
                }
            }
        }

        $flashSale->update($input);

        $productIds = $request->input('product_ids', []);
        $productIds = array_values(array_unique(array_slice($productIds, 0, 10)));
        $attach = [];
        $order = 0;
        foreach ($productIds as $pid) {
            $attach[$pid] = ['sort_order' => $order++];
        }
        $flashSale->products()->sync($attach);

        return redirect()->route('flash-sales.index')->with('success', 'Flash sale updated successfully');
    }

    public function inactive(Request $request)
    {
        $flashSale = FlashSale::findOrFail($request->hidden_id);
        $flashSale->status = 0;
        $flashSale->save();
        return redirect()->back()->with('success', 'Flash sale deactivated successfully');
    }

    public function active(Request $request)
    {
        $flashSale = FlashSale::findOrFail($request->hidden_id);
        $flashSale->status = 1;
        $flashSale->save();
        return redirect()->back()->with('success', 'Flash sale activated successfully');
    }

    public function destroy(Request $request)
    {
        $flashSale = FlashSale::findOrFail($request->hidden_id);
        if (!empty($flashSale->banner_image)) {
            try {
                File::delete($flashSale->banner_image);
            } catch (\Throwable $e) {
            }
        }
        $flashSale->delete();
        return redirect()->back()->with('success', 'Flash sale deleted successfully');
    }

    public function getProductsAjax(Request $request)
    {
        $query = Product::where('status', 1)
            ->select('id', 'name as text');

        if ($request->has('q') && !empty($request->q)) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $products = $query->paginate(20);

        return response()->json($products);
    }
}
