<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\Product;
use App\Models\Review;
use App\Models\Customer;
class ReviewController extends Controller
{
    // function __construct()
    // {
    //      $this->middleware('permission:review-list|review-create|review-edit|review-delete', ['only' => ['index','store']]);
    //      $this->middleware('permission:review-create', ['only' => ['create','store']]);
    //      $this->middleware('permission:review-edit', ['only' => ['edit','update']]);
    //      $this->middleware('permission:review-delete', ['only' => ['destroy']]);
    // }

    public function index(Request $request)
    {
        // The list has to name the product and the moderation state: without
        // them an admin cannot tell which product a row belongs to, or which
        // rows are already live on the storefront.
        $show_data = Review::with('product:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        return view('backEnd.review.index',compact('show_data'));
    }
    public function create()
    {
        $products = Product::where(['status'=>1])->select('id','name')->get();
        $customers = Customer::where('status', 'active')->get();
        return view('backEnd.review.create',compact('products', 'customers'));
    }
    public function store(Request $request)
    {
        // customer_id is optional on purpose: the common case is a walk-in
        // buyer with no account, and the admin still has to be able to record
        // their rating. A linked customer still wins over the typed name.
        $this->validate($request, [
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required_without:customer_id|nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'ratting' => 'required|integer|between:1,5',
            'review' => 'required',
            'product_id' => 'required|exists:products,id',
            'status' => 'nullable',
        ]);

        $customer = $request->filled('customer_id')
            ? Customer::find($request->customer_id)
            : null;

        $input = $request->only(['ratting', 'review', 'product_id', 'customer_id', 'name', 'email']);
        $input['name'] = $customer->name ?? ($request->input('name') ?: 'N / A');
        $input['email'] = $customer->email ?? ($request->input('email') ?: 'N / A');
        $input['status'] = $request->status==1?'active':'pending';

        Review::create($input);
        Toastr::success('Success','Data insert successfully');
        return redirect()->route('reviews.index');
    }
    
    public function edit($id)
    {
        $edit_data = Review::find($id);
        $products = Product::where(['status'=>1])->select('id','name')->get();
        return view('backEnd.review.edit',compact('edit_data','products'));
    }
    
    public function update(Request $request)
    {
        
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'ratting' => 'required|integer|between:1,5',
            'review' => 'required',
            'product_id' => 'required|exists:products,id',
            'status' => 'nullable',
        ]);
        $input = $request->except('hidden_id');
        $input['status'] = $request->status==1?'active':'pending';
        $update_data = Review::findOrFail($request->hidden_id);
        $update_data->update($input);

        Toastr::success('Success','Data update successfully');
        return redirect()->route('reviews.index');
    }
 
    public function pending(){
        // The list names the product and the customer, so the moderator can
        // decide without opening each review. Eager-loaded to avoid one query
        // per row.
        $data = Review::with('product:id,name')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();

        return view('backEnd.review.pending', compact('data'));
    }
    public function inactive(Request $request){
        $inactive = Review::findOrFail($request->hidden_id);
        $inactive->status = 'pending';
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request){
        // This used to increment products.ratting. That column does not exist
        // (Schema::getColumnType('products','ratting') throws), so every
        // approval was a 500. The storefront rating is AVG(reviews.ratting)
        // over active rows, so flipping the status is the whole job.
        $active = Review::findOrFail($request->hidden_id);
        $active->status = 'active';
        $active->save();

        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = Review::findOrFail($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
}
