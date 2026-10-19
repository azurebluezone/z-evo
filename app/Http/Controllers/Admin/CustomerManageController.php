<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\CustomerProfit;
use App\Models\Customer;
use App\Models\IpBlock;
use Toastr;
use Image;
use File;
use Auth;
use Hash;
class CustomerManageController extends Controller
{
    use InteractsWithDataTable;
    public function index(Request $request){
        if($request->keyword){
            $show_data = Customer::orWhere('phone',$request->keyword)->orWhere('name',$request->keyword)->paginate(10);
        }else{
             $show_data = Customer::paginate(10);
        }
       
        return view('backEnd.customer.index',compact('show_data'));
    }

    public function edit($id){
        $edit_data = Customer::find($id);
        return view('backEnd.customer.edit',compact('edit_data'));
    }
    
    public function update(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required',
        ]);

        $update_data = Customer::find($request->hidden_id);
        
        // Update basic fields individually
        $update_data->name = $request->name;
        $update_data->phone = $request->phone;
        $update_data->email = $request->email;
        $update_data->address = $request->address;
        $update_data->status = $request->status ? 1 : 0;
        
        // Update password if provided
        if(!empty($request->password)){ 
            $update_data->password = Hash::make($request->password);
        }

        // Update image if provided
        $image = $request->file('image');
        if($image){
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/customer/';
            $imageUrl = $uploadpath.$name;
            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = 100;
            $height = 100;
            $img->height() > $img->width() ? $width=null : $height=null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($imageUrl);
            
            // Delete old image
            if($update_data->image && file_exists($update_data->image)){
                File::delete($update_data->image);
            }
            $update_data->image = $imageUrl;
        }
        
        $update_data->save();

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully');
    }
 
    public function inactive(Request $request){
        $inactive = Customer::find($request->hidden_id);
        $inactive->status = 'inactive';
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request){
        $active = Customer::find($request->hidden_id);
        $active->status = 'active';
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function profile(Request $request){
        // find() is null when the id is missing or stale, and the view reads
        // $profile->name straight away - so this page was a 500, not a 404.
        $profile = Customer::with('orders')->find($request->id);

        if (!$profile) {
            abort(404);
        }

        return view('backEnd.customer.profile',compact('profile'));
    }
    public function adminlog(Request $request){
        $customer = Customer::find($request->hidden_id);
        Auth::guard('customer')->loginUsingId($customer->id);
        return redirect()->route('customer.account');
    }
    public function ip_block(Request $request){
        $data = IpBlock::get();
        return view('backEnd.reports.ipblock',compact('data'));
    }
    public function ipblock_store(Request $request){

        $store_data = new IpBlock();
        $store_data->ip_no = $request->ip_no;
        $store_data->reason = $request->reason;
        $store_data->save();
        Toastr::success('Success','IP address add successfully');
        return redirect()->back();
    }
    public function ipblock_update(Request $request){
        $update_data = IpBlock::find($request->id);
        $update_data->ip_no = $request->ip_no;
        $update_data->reason = $request->reason;
        $update_data->save();
        Toastr::success('Success','IP address update successfully');
        return redirect()->back();
    }
    public function ipblock_destroy(Request $request){
        $delete_data = IpBlock::find($request->id)->delete();
        Toastr::success('Success','IP address delete successfully');
        return redirect()->back();
    }

    public function destroy(Request $request){
        $customer = Customer::find($request->hidden_id);
        if($customer){
            // Delete customer image if exists
            if($customer->image && file_exists($customer->image)){
                File::delete($customer->image);
            }
            $customer->delete();
            Toastr::success('Success','Customer deleted successfully');
        }else{
            Toastr::error('Error','Customer not found');
        }
        return redirect()->route('customers.index');
    }

    /**
     * DataTables feed for the customers grid.
     *
     * The old index() searched with Customer::orWhere('phone', ...)->orWhere('name', ...)
     * as the FIRST condition of the query, so with a keyword it could match any
     * row and the keyword acted as an OR across the whole table. Here the same
     * three fields are matched inside a grouped where, which is what the search
     * box always meant.
     */
    public function datatable(Request $request)
    {
        [$start, $length] = $this->dtPaging($request);
        $search = $this->dtSearch($request);
        $query = Customer::query();

        // How many rows exist at all - the pager needs this
        // number before the search narrows the table down.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        [$column, $dir] = $this->dtOrdering($request, [1 => 'name', 2 => 'phone'], 'name');

        $rows = [];
        foreach ($query->orderBy($column, $dir)->skip($start)->take($length)->get() as $i => $customer) {
            $html = trim(view('backEnd.customer._row', ['value' => $customer, 'index' => $start + $i + 1])->render());
            $rows[] = $this->dtCells($html, 6, 'customer/_row.blade.php');
        }

        return $this->dtResponse((int) $request->input('draw', 1), $total, $filtered, $rows);
    }
}
