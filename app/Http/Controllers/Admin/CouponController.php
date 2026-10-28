<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Product;
use Illuminate\Http\Request;
use Toastr;

class CouponController extends Controller
{
    use InteractsWithDataTable;
    public function index()
    {
        $coupons = Coupon::orderByDesc('id')->get();
        return view('backEnd.coupon.index', compact('coupons'));
    }

    /**
     * Usage details for one coupon: status, how many times it was used
     * today, and how many times in total. Counts come from the
     * coupon_usages log (one row per real order, checkout or POS).
     */
    public function usage($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            Toastr::error('Error', 'Coupon not found');
            return redirect()->route('coupons.index');
        }

        $today = CouponUsage::where('coupon_id', $coupon->id)
            ->whereDate('created_at', today())
            ->count();
        $total = CouponUsage::where('coupon_id', $coupon->id)->count();

        $recent = CouponUsage::where('coupon_id', $coupon->id)
            ->with('order')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('backEnd.coupon.usage', compact('coupon', 'today', 'total', 'recent'));
    }

    public function create()
    {
        $products = Product::where('status', 1)
            ->where('name', 'NOT LIKE', '%test%')
            ->select('id', 'name', 'status')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        return view('backEnd.coupon.create', compact('products'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'usage_limit' => 'nullable|integer|min:1',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $input = $request->only([
            'code',
            'type',
            'value',
            'min_amount',
            'max_discount',
            'start_at',
            'end_at',
            'usage_limit',
        ]);

        $input['code'] = strtoupper(trim($input['code']));
        $input['status'] = $request->has('status') ? 1 : 0;
        $input['used_count'] = 0;

        $coupon = Coupon::create($input);

        $productIds = $request->input('product_ids', []);
        $coupon->products()->sync($productIds);
        return redirect()->route('coupons.index')->with('success', 'Coupon created successfully');
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);

        $products = Product::where('status', 1)
            ->where('name', 'NOT LIKE', '%test%')
            ->select('id', 'name', 'status')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $selected_product_ids = $coupon->products()->pluck('products.id')->values()->all();

        return view('backEnd.coupon.edit', compact('coupon', 'products', 'selected_product_ids'));
    }

    public function update(Request $request)
    {
        $coupon = Coupon::findOrFail($request->id);

        $this->validate($request, [
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'usage_limit' => 'nullable|integer|min:1',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $input = $request->only([
            'code',
            'type',
            'value',
            'min_amount',
            'max_discount',
            'start_at',
            'end_at',
            'usage_limit',
        ]);

        $input['code'] = strtoupper(trim($input['code']));
        $input['status'] = $request->has('status') ? 1 : 0;

        $coupon->update($input);

        $productIds = $request->input('product_ids', []);
        $coupon->products()->sync($productIds);
        return redirect()->route('coupons.index')->with('success', 'Coupon updated successfully');
    }

    public function inactive(Request $request)
    {
        $coupon = Coupon::findOrFail($request->hidden_id);
        $coupon->status = 0;
        $coupon->save();
        return redirect()->back()->with('success', 'Coupon deactivated successfully');
    }

    public function active(Request $request)
    {
        $coupon = Coupon::findOrFail($request->hidden_id);
        $coupon->status = 1;
        $coupon->save();
        return redirect()->back()->with('success', 'Coupon activated successfully');
    }

    public function destroy(Request $request)
    {
        $coupon = Coupon::findOrFail($request->hidden_id);
        $coupon->delete();
        return redirect()->back()->with('success', 'Coupon deleted successfully');
    }

    /** DataTables feed for the coupons grid. */
    public function datatable(Request $request)
    {
        [$start, $length] = $this->dtPaging($request);
        $search = $this->dtSearch($request);
        $query = Coupon::query();

        // How many rows exist at all - the pager needs this
        // number before the search narrows the table down.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where('code', 'LIKE', "%{$search}%");
        }
        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        [$column, $dir] = $this->dtOrdering($request, [1 => 'code', 8 => 'status'], 'id');

        $rows = [];
        foreach ($query->orderBy($column, $dir)->skip($start)->take($length)->get() as $i => $coupon) {
            $html = trim(view('backEnd.coupon._row', ['value' => $coupon, 'index' => $start + $i + 1])->render());
            $rows[] = $this->dtCells($html, 10, 'coupon/_row.blade.php');
        }

        return $this->dtResponse((int) $request->input('draw', 1), $total, $filtered, $rows);
    }
}
