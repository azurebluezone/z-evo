<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Childcategory;
use App\Models\Subcategory;
use Toastr;
use Image;
use File;
use Str;
use DB;

class ChildcategoryController extends Controller
{
    public function getSubCategory(Request $request){
        $category = DB::table("subcategories")
        ->where("subcategorytype", $request->childcategorytype)
        ->pluck('subcategoryName', 'id');
        return response()->json($category);
    }        

    function __construct()
    {
        $this->middleware('permission:childcategory-list|childcategory-create|childcategory-edit|childcategory-delete', ['only' => ['index','store']]);
        $this->middleware('permission:childcategory-create', ['only' => ['create','store']]);
        $this->middleware('permission:childcategory-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:childcategory-delete', ['only' => ['destroy', 'bulkDestroy']]);
    }

    public function index(Request $request)
    {
        $data = Childcategory::orderBy('id','DESC')->with('subcategory')->get();
        return view('backEnd.childcategory.index', compact('data'));
    }
    /** `{id}/show` had no method behind it and returned a 500; redirect to the edit screen like the other Admin controllers do. */
    public function show($id)
    {
        return redirect()->route('childcategories.edit', ['id' => $id]);
    }

    public function create()
    {
        $menucategories = \App\Models\Category::where('parent_id', 0)
            ->where('status', 1)
            ->with(['subcategories' => function ($q) {
                $q->where('status', 1);
            }])
            ->select('id', 'name')
            ->orderBy('id', 'DESC')
            ->get();

        $model = new Childcategory();

        return view('backEnd.childcategory.create', compact('menucategories', 'model'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'subcategory_id' => 'required',
            'childcategoryName' => 'required',
            'status' => 'required',
        ]);
        // image with intervention 
        
        
        $input = $request->except(['seo_meta']);

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->childcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        
        $child = Childcategory::create($input);

        if ($request->has('seo_meta')) {
            $child->updateSeoMeta($request->seo_meta);
        }
        return redirect()->route('childcategories.index')->with('success', 'Childcategory created successfully');
    }
    
    public function edit($id)
    {
        $edit_data = Childcategory::find($id);
        $categories = Subcategory::select('id','subcategoryName')->get();
        $menucategories = \App\Models\Category::where('parent_id', 0)
            ->where('status', 1)
            ->with(['subcategories' => function ($q) {
                $q->where('status', 1);
            }])
            ->select('id', 'name')
            ->orderBy('id', 'DESC')
            ->get();

        $model = $edit_data;

        return view('backEnd.childcategory.edit',compact('edit_data','categories', 'menucategories', 'model'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'subcategory_id' => 'required',
            'childcategoryName' => 'required',
            'status' => 'required',
        ]);
        $update_data = Childcategory::find($request->id);
        $input = $request->except(['seo_meta']);
        
       
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->childcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['status'] = $request->status?1:0;
        
        $update_data->update($input);

        if ($request->has('seo_meta')) {
            $update_data->updateSeoMeta($request->seo_meta);
        }

        return redirect()->route('childcategories.index')->with('success', 'Childcategory updated successfully');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Childcategory::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        return redirect()->back()->with('success', 'Childcategory deactivated successfully');
    }
    public function active(Request $request)
    {
        $active = Childcategory::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        return redirect()->back()->with('success', 'Childcategory activated successfully');
    }
    public function destroy(Request $request)
    {
        $delete_data = Childcategory::find($request->hidden_id);
        $delete_data->delete();
        return redirect()->back()->with('success', 'Childcategory deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (count($ids) === 0) {
            return redirect()->back()->with('warning', 'No items selected');
        }

        Childcategory::whereIn('id', $ids)->delete();
        return redirect()->back()->with('success', 'Selected items deleted successfully');
    }
}
