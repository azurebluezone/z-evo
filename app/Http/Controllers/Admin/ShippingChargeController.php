<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShippingCharge;
use Toastr;

class ShippingChargeController extends Controller
{    
    function __construct()
    {
        $this->middleware('permission:shipping-list|shipping-create|shipping-edit|shipping-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shipping-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = ShippingCharge::orderBy('id', 'ASC')->get();
        return view('backEnd.shippingcharge.index', compact('show_data'));
    }
    public function create()
    {
        return view('backEnd.shippingcharge.create');
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);        

        // The three policy rates already exist and must not be duplicated by a
        // hand-typed spelling variant.
        if (ShippingCharge::isReadOnlyName((string) $request->name)) {
            Toastr::error('This delivery rate is fixed and cannot be duplicated or renamed.', 'Read only');
            return redirect()->back();
        }

        $input = $request->all();
        $input['status'] = $request->status?1:0;
        // dd($input);
        ShippingCharge::create($input);
        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('shippingcharges.index');
    }

    public function edit($id)
    {
        $edit_data = ShippingCharge::find($id);
        return view('backEnd.shippingcharge.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);

        $update_data = ShippingCharge::find($request->id);

        if (! $update_data) {
            Toastr::error('Shipping charge not found.', 'Failed');
            return redirect()->back();
        }

        // Fixed rates are read-only: not the amount, not the status, not the
        // name. Renaming one would also orphan the historical comparisons.
        if ($update_data->isReadOnly() || ShippingCharge::isReadOnlyName((string) $request->name)) {
            Toastr::error('Dhaka inside / Dhaka outside / Standard delivery are read-only rates.', 'Read only');
            return redirect()->back();
        }

        $input = $request->all();       
        $input['status'] = $request->status?1:0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('shippingcharges.index');
    }

    public function inactive(Request $request)
    {
        $inactive = ShippingCharge::find($request->hidden_id);

        if ($inactive && $inactive->isReadOnly()) {
            Toastr::error('This delivery rate is read-only.', 'Read only');
            return redirect()->back();
        }

        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = ShippingCharge::find($request->hidden_id);

        if ($active && $active->isReadOnly()) {
            Toastr::error('This delivery rate is read-only.', 'Read only');
            return redirect()->back();
        }

        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = ShippingCharge::find($request->hidden_id);

        if ($delete_data && $delete_data->isReadOnly()) {
            Toastr::error('This delivery rate is read-only.', 'Read only');
            return redirect()->back();
        }

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
