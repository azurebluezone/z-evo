<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use App\Models\Category;
use App\Models\Subcategory;
use File;
use DB;

class SubcategoryController extends Controller
{
    public function getCategory(Request $request){
        $category = DB::table("categories")
        ->where("service_category", $request->service_category)
        ->pluck('name', 'id');
        return response()->json($category);
    }        

    function __construct()
    {
        $this->middleware('permission:subcategory-list|subcategory-create|subcategory-edit|subcategory-delete', ['only' => ['index','store']]);
        $this->middleware('permission:subcategory-create', ['only' => ['create','store']]);
        $this->middleware('permission:subcategory-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:subcategory-delete', ['only' => ['destroy', 'bulkDestroy']]);
    }

    public function index(Request $request)
    {
        $data = Subcategory::orderBy('id','DESC')->with('category')->get();
        return view('backEnd.subcategory.index',compact('data'));
    }
    /** `{id}/show` had no method behind it and returned a 500; redirect to the edit screen like the other Admin controllers do. */
    public function show($id)
    {
        return redirect()->route('subcategories.edit', ['id' => $id]);
    }

    public function create()
    {
        $categories = Category::get();
        return view('backEnd.subcategory.create', compact('categories'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'subcategoryName' => 'required',
            'status' => 'required',
        ]);
        // image with intervention 
        $image = $request->file('image');
        if($image !=NULL){
            $name =  time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadDir = public_path('uploads/subcategory/');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $imageUrl = 'uploads/subcategory/'.$name;
            $fullPath = public_path($imageUrl);
            $img=Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = "";
            $height = "";
            $img->height() > $img->width() ? $width=null : $height=null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($fullPath);
        }else{
            $imageUrl = NULL;
        }
        
      
        $input = $request->except(['seo_meta']);

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->subcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['image'] = $imageUrl;
        $subcategory = Subcategory::create($input);

        if ($request->has('seo_meta')) {
            $subcategory->updateSeoMeta($request->seo_meta);
        }
        return redirect()->route('subcategories.index')->with('success', 'Subcategory created successfully');
    }
    
    public function edit($id)
    {
        $edit_data = Subcategory::find($id);
        $categories = Category::select('id','name')->get();
        $model = $edit_data;
        return view('backEnd.subcategory.edit',compact('edit_data','categories', 'model'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'subcategoryName' => 'required',
            'status' => 'required',
        ]);
        $update_data = Subcategory::find($request->id);
        $input = $request->except(['seo_meta']);
        $image = $request->file('image');
        
        if($image){
            // image with intervention 
            $name =  time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            
            // Create directory if it doesn't exist
            $uploadpath = public_path('uploads/subcategory/');
            if (!file_exists($uploadpath)) {
                mkdir($uploadpath, 0755, true);
            }
            
            $imageUrl = 'uploads/subcategory/'.$name;
            $fullPath = public_path($imageUrl);
            
            $img=Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = "";
            $height = "";
            $img->height() > $img->width() ? $width=null : $height=null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($fullPath);
            $input['image'] = $imageUrl;
            File::delete(public_path($update_data->image));
        }else{
            $input['image'] = $update_data->image;
        }


        
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->subcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['status'] = $request->status?1:0;
        
        $update_data->update($input);

        if ($request->has('seo_meta')) {
            $update_data->updateSeoMeta($request->seo_meta);
        }

        return redirect()->route('subcategories.index')->with('success', 'Subcategory updated successfully');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Subcategory::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        return redirect()->back()->with('success', 'Subcategory deactivated successfully');
    }
    public function active(Request $request)
    {
        $active = Subcategory::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        return redirect()->back()->with('success', 'Subcategory activated successfully');
    }
    public function destroy(Request $request)
    {
        $delete_data = Subcategory::find($request->hidden_id);
        $delete_data->delete();
        return redirect()->back()->with('success', 'Subcategory deleted successfully');
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

        Subcategory::whereIn('id', $ids)->delete();
        return redirect()->back()->with('success', 'Selected items deleted successfully');
    }
}

