<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use Illuminate\Http\Request;
use App\Models\Category;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Str;
class CategoryController extends Controller
{
    use InteractsWithDataTable;
    function __construct()
    {
         $this->middleware('permission:category-list|category-create|category-edit|category-delete', ['only' => ['index','store','datatable']]);
         $this->middleware('permission:category-create', ['only' => ['create','store']]);
         $this->middleware('permission:category-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:category-edit', ['only' => ['homePageSetOnly']]);
         $this->middleware('permission:category-delete', ['only' => ['destroy', 'bulkDestroy']]);
    }

    public function index(Request $request)
    {
        $data = Category::orderBy('id','DESC')->with('category')->paginate(10);
        $generalSetting = \App\Models\GeneralSetting::first();
        // return $data;
        return view('backEnd.category.index',compact('data', 'generalSetting'));
    }

    public function show($id)
    {
        return redirect()->route('categories.edit', ['id' => $id]);
    }
    public function create()
    {
        $categories = Category::orderBy('id','DESC')->select('id','name')->get();
        $model = new Category(); // Create empty model for SEO fields
        return view('backEnd.category.create',compact('categories', 'model'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        // image with intervention 
        $image = $request->file('image');
        if($image){
        $name =  time().'-'.$image->getClientOriginalName();
        $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
        $name = strtolower(preg_replace('/\s+/', '-', $name));
        $uploadDir = public_path('uploads/category/');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $imageUrl = 'uploads/category/'.$name;
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
            $imageUrl = 'uploads/category/default.png';
        }

        $input = $request->except(['seo_meta']);
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id'] = $request->parent_id?$request->parent_id:0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['home_page'] = $request->home_page ? 1 : 0;
        $input['image'] = $imageUrl;
        $category = Category::create($input);
        
        // Handle SEO meta data
        if ($request->has('seo_meta')) {
            $category->updateSeoMeta($request->seo_meta);
        }
        
        return redirect()->route('categories.index')->with('success', 'Category created successfully');
    }
    
    public function edit($id)
    {
        $edit_data = Category::find($id);
        $categories = Category::select('id','name')->get();
        $model = $edit_data; // Pass the actual model for SEO fields
        return view('backEnd.category.edit',compact('edit_data','categories', 'model'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
        $update_data = Category::find($request->id);
        $input = $request->except(['seo_meta']);
        $image = $request->file('image');
        if($image){
            // image with intervention 
            $name =  time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            
            // Create directory if it doesn't exist
            $uploadpath = public_path('uploads/category/');
            if (!file_exists($uploadpath)) {
                mkdir($uploadpath, 0755, true);
            }
            
            $imageUrl = 'uploads/category/'.$name;
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
            if($update_data->image && file_exists(public_path($update_data->image))) {
                unlink(public_path($update_data->image));
            }
        }else{
            $input['image'] = $update_data->image;
        }
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id'] = $request->parent_id?$request->parent_id:0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['home_page'] = $request->home_page ? 1 : 0;
        $input['status'] = $request->status?1:0;
        
        $update_data->update($input);
        
        // Handle SEO meta data
        if ($request->has('seo_meta')) {
            $update_data->updateSeoMeta($request->seo_meta);
        }

        return redirect()->route('categories.index')->with('success', 'Category updated successfully');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Category::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        return redirect()->back()->with('success', 'Category deactivated successfully');
    }
    public function active(Request $request)
    {
        $active = Category::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        return redirect()->back()->with('success', 'Category activated successfully');
    }
    
    public function homePageOn(Request $request)
    {
        $category = Category::find($request->hidden_id);
        $category->home_page = 1;
        $category->save();
        return redirect()->back()->with('success', 'Category will be shown on home page');
    }
    
    public function homePageOff(Request $request)
    {
        $category = Category::find($request->hidden_id);
        $category->home_page = 0;
        $category->save();
        return redirect()->back()->with('success', 'Category will be hidden from home page');
    }

    public function homePageSetOnly(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        $ids = array_values(array_unique($ids));

        if (count($ids) !== 2) {
            return redirect()->back()->with('warning', 'Please select exactly 2 categories');
        }

        Category::query()->update(['home_page' => 0]);
        Category::whereIn('id', $ids)->update(['home_page' => 1]);

        return redirect()->back()->with('success', 'Home Page categories updated');
    }
    
    public function destroy(Request $request)
    {
        $delete_data = Category::find($request->hidden_id);
        $delete_data->delete();
        return redirect()->back()->with('success', 'Category deleted successfully');
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

        Category::whereIn('id', $ids)->delete();
        return redirect()->back()->with('success', 'Selected items deleted successfully');
    }

    /** DataTables feed for the departments grid. */
    public function datatable(Request $request)
    {
        [$start, $length] = $this->dtPaging($request);
        $search = $this->dtSearch($request);
        $query = Category::query()->where('parent_id', '=', '0');

        // How many rows exist at all - the pager needs this
        // number before the search narrows the table down.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where('name', 'LIKE', "%{$search}%");
        }
        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        [$column, $dir] = $this->dtOrdering($request, [1 => 'name', 3 => 'home_page', 4 => 'status'], 'name');

        $rows = [];
        foreach ($query->orderBy($column, $dir)->skip($start)->take($length)->get() as $i => $category) {
            $html = trim(view('backEnd.category._row', ['value' => $category, 'index' => $start + $i + 1])->render());
            $rows[] = $this->dtCells($html, 6, 'category/_row.blade.php');
        }

        return $this->dtResponse((int) $request->input('draw', 1), $total, $filtered, $rows);
    }
}
