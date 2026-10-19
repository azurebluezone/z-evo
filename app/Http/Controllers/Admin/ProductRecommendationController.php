<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Toastr;

class ProductRecommendationController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('image', 'category')->where('is_recommended', 1)->orderBy('recommended_sort_order')->orderByDesc('id');

        if ($request->keyword) {
            $kw = trim((string) $request->keyword);
            $query->where('name', 'LIKE', '%' . $kw . '%');
        }

        $data = $query->paginate(10);

        return view('backEnd.recommendations.index', compact('data'));
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Product::query()
            ->select('id', 'name')
            ->where('status', 1)
            ->where('is_recommended', 0)
            ->orderByDesc('id');

        if ($term !== '') {
            $query->where('name', 'LIKE', '%' . $term . '%');
        }

        $items = $query->limit(30)->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'text' => $p->name,
            ];
        });

        return response()->json([
            'results' => $items,
        ]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer|exists:products,id',
            'is_recommended' => 'nullable|in:0,1',
            'recommended_sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        $product = Product::findOrFail($request->id);

        if ($request->has('is_recommended')) {
            $product->is_recommended = (int) $request->is_recommended;
        }

        if ($request->has('recommended_sort_order')) {
            $product->recommended_sort_order = (int) $request->recommended_sort_order;
        }

        $product->save();

        // If the product is marked recommended but it's inactive, notify admin
        if ($request->has('is_recommended') && (int) $request->is_recommended === 1 && $product->status != 1) {
            Toastr::warning('Warning', 'Product marked as recommended but is inactive. Activate the product to make it visible on the home page.');
            return redirect()->back();
        }

        if ($request->has('is_recommended') && (int) $request->is_recommended === 0) {
            Toastr::success('Success', 'Product removed from recommendations successfully');
        } else {
            Toastr::success('Success', 'Recommendation updated successfully');
        }
        
        return redirect()->back();
    }
}
