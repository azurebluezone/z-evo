<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use Illuminate\Http\Request;
use App\Models\Brand;
use Image;
use File;
use Toastr;

class BrandController extends Controller
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

    public function index(Request $request)
    {
        $data = Brand::orderBy('id', 'DESC')->paginate(10);
        $generalSetting = \App\Models\GeneralSetting::first();
        return view('backEnd.brand.index', compact('data', 'generalSetting'));
    }

    public function show($id)
    {
        return redirect()->route('brands.edit', ['id' => $id]);
    }
    public function create()
    {
        return view('backEnd.brand.create');
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        // image with intervention 
        $image = $request->file('image');
        if ($image) {
            $name =  time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            // Create directory if it doesn't exist
            $uploadpath = public_path('uploads/brand/');
            if (!file_exists($uploadpath)) {
                mkdir($uploadpath, 0755, true);
            }

            $imageUrl = 'uploads/brand/' . $name;
            $fullPath = public_path($imageUrl);

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = 210;
            $height = 210;
            $img->height() > $img->width() ? $width = null : $height = null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($fullPath);
        } else {
            $imageUrl = 'uploads/category/default.png';
        }


        $input = $request->except(['seo_meta']);
        $input['slug'] = strtolower(preg_replace('/\s+/u', '-', trim($request->name)));
        $input['image'] = $imageUrl;

        $brand = Brand::create($input);

        if ($request->has('seo_meta')) {
            $brand->updateSeoMeta($this->extractSeoMeta($request));
        }

        return redirect()->route('brands.index')->with('success', 'Brand created successfully');
    }

    public function edit($id)
    {
        $edit_data = Brand::find($id);
        return view('backEnd.brand.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
        $update_data = Brand::find($request->id);
        $input = $request->except(['seo_meta']);
        $image = $request->file('image');
        if ($image) {
            // image with intervention 
            $name =  time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            // Create directory if it doesn't exist
            $uploadpath = public_path('uploads/brand/');
            if (!file_exists($uploadpath)) {
                mkdir($uploadpath, 0755, true);
            }

            $imageUrl = 'uploads/brand/' . $name;
            $fullPath = public_path($imageUrl);

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = 210;
            $height = 210;
            $img->height() > $img->width() ? $width = null : $height = null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($fullPath);
            $input['image'] = $imageUrl;
            File::delete(public_path($update_data->image));
        } else {
            $input['image'] = $update_data->image;
        }
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        if ($request->has('seo_meta')) {
            $update_data->updateSeoMeta($this->extractSeoMeta($request));
        }

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully');
    }

    public function inactive(Request $request)
    {
        $inactive = Brand::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        return redirect()->back()->with('success', 'Brand deactivated successfully');
    }
    public function active(Request $request)
    {
        $active = Brand::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        return redirect()->back()->with('success', 'Brand activated successfully');
    }
    public function destroy(Request $request)
    {
        $delete_data = Brand::find($request->hidden_id);
        $delete_data->delete();
        return redirect()->back()->with('success', 'Brand deleted successfully');
    }

    /** DataTables feed for the brands grid. */
    public function datatable(Request $request)
    {
        [$start, $length] = $this->dtPaging($request);
        $search = $this->dtSearch($request);
        $query = Brand::query();

        // How many rows exist at all - the pager needs this
        // number before the search narrows the table down.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where('name', 'LIKE', "%{$search}%");
        }
        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        [$column, $dir] = $this->dtOrdering($request, [1 => 'name', 3 => 'status'], 'name');

        $rows = [];
        foreach ($query->orderBy($column, $dir)->skip($start)->take($length)->get() as $i => $brand) {
            $html = trim(view('backEnd.brand._row', ['value' => $brand, 'index' => $start + $i + 1])->render());
            $rows[] = $this->dtCells($html, 5, 'brand/_row.blade.php');
        }

        return $this->dtResponse((int) $request->input('draw', 1), $total, $filtered, $rows);
    }
}
