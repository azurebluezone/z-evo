<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use Illuminate\Http\Request;
use App\Models\BannerCategory;
use App\Models\Banner;
use Toastr;
use Image;
use File;
class BannerController extends Controller
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

    function __construct()
    {
         $this->middleware('permission:banner-list|banner-create|banner-edit|banner-delete', ['only' => ['index','store','datatable']]);
         $this->middleware('permission:banner-create', ['only' => ['create','store']]);
         $this->middleware('permission:banner-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:banner-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Banner::orderBy('id','DESC')->with('category')->paginate(10);
        return view('backEnd.banner.index',compact('data'));
    }
    public function create()
    {
        $categories = BannerCategory::orderBy('id','DESC')->select('id','name')->get();
        $model = new Banner();
        return view('backEnd.banner.create',compact('categories', 'model'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'link' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'media_type' => 'nullable|in:image,video',
        ]);

        $mediaType = $request->input('media_type', 'image');

        if ($mediaType === 'video') {
            $this->validate($request, [
                'video' => 'required|file|mimes:mp4,webm,ogg|max:256000',
            ]);
        } else {
            $this->validate($request, [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);
        }

        try {
            $input = $request->except(['seo_meta']);
            $input['status'] = $request->status ? 1 : 0;
            $input['media_type'] = $mediaType;
            $input['category_id'] = 1;

            $input['video'] = null;

            if ($mediaType === 'video') {
                $videoFile = $request->file('video');

                if ($videoFile) {
                    $uploadDir = public_path('uploads/banner-video/');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $name = time() . '-' . $videoFile->getClientOriginalName();
                    $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
                    $name = strtolower($name);

                    $relativePath = 'uploads/banner-video/' . $name;
                    $videoFile->move($uploadDir, $name);
                    $input['video'] = $relativePath;
                }

                if (empty($input['video'])) {
                    Toastr::error('Error', 'No video file provided');
                    return redirect()->back()->withInput();
                }

                if (empty($input['image'])) {
                    $input['image'] = 'assets/images/frontend/banner/6718b8ecbd6a11729673452.png';
                }
            } else {
                $image = $request->file('image');

                $uploadDir = public_path('uploads/banner/');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $name = time() . '-' . $image->getClientOriginalName();
                $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
                $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
                $name = strtolower($name);

                $relativePath = 'uploads/banner/' . $name;
                $fullPath = public_path($relativePath);

                if (!extension_loaded('gd')) {
                    throw new \Exception('GD Library extension is not available');
                }

                $img = Image::make($image->getRealPath());
                $maxWidth = 1920;
                $maxHeight = 1080;
                if ($img->width() > $maxWidth || $img->height() > $maxHeight) {
                    $img->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $img->encode('webp', 85);
                $img->save($fullPath);

                $input['image'] = $relativePath;
            }

            $banner = Banner::create($input);

            if ($request->has('seo_meta')) {
                $banner->updateSeoMeta($this->extractSeoMeta($request));
            }
            Toastr::success('Success', 'Data insert successfully');
        } catch (\Exception $e) {
            Toastr::error('Error', 'Banner save failed: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect()->route('banners.index');
    }
    
    public function edit($id)
    {
        $edit_data = Banner::find($id);
        $categories = BannerCategory::select('id','name')->get();
        $model = $edit_data;
        return view('backEnd.banner.edit',compact('edit_data','categories', 'model'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'link' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'media_type' => 'nullable|in:image,video',
        ]);

        $update_data = Banner::find($request->id);
        $mediaType = $request->input('media_type', $update_data->media_type ?? 'image');

        if ($mediaType === 'video') {
            $this->validate($request, [
                'video' => 'nullable|file|mimes:mp4,webm,ogg|max:256000',
            ]);
        } else {
            $this->validate($request, [
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);
        }

        try {
            $input = $request->except(['seo_meta']);
            $input['status'] = $request->status ? 1 : 0;
            $input['media_type'] = $mediaType;
            $input['category_id'] = $update_data->category_id ?? 1;

            if ($mediaType === 'video') {
                $videoFile = $request->file('video');

                if (!$videoFile && empty($update_data->video)) {
                    Toastr::error('Error', 'No video file provided');
                    return redirect()->back()->withInput();
                }

                if ($videoFile) {
                    $uploadDir = public_path('uploads/banner-video/');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $name = time() . '-' . $videoFile->getClientOriginalName();
                    $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
                    $name = strtolower($name);

                    $relativePath = 'uploads/banner-video/' . $name;
                    $videoFile->move($uploadDir, $name);
                    $input['video'] = $relativePath;
                } else {
                    $input['video'] = $update_data->video;
                }

                $input['image'] = $update_data->image;
            } else {
                $image = $request->file('image');
                if ($image) {
                    $file = $request->file('image');
                    $name = time() . '-' . $file->getClientOriginalName();
                    $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
                    $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
                    $name = strtolower($name);

                    $uploadDir = public_path('uploads/banner/');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $relativePath = 'uploads/banner/' . $name;
                    $fullPath = public_path($relativePath);

                    $img = Image::make($file->getRealPath());
                    $img->encode('webp', 85);
                    $img->save($fullPath);

                    $input['image'] = $relativePath;
                    if (!empty($update_data->image) && file_exists(public_path($update_data->image))) {
                        File::delete(public_path($update_data->image));
                    }
                } else {
                    $input['image'] = $update_data->image;
                }

                $input['video'] = null;
            }

            $update_data->update($input);

            if ($request->has('seo_meta')) {
                $update_data->updateSeoMeta($this->extractSeoMeta($request));
            }

            Toastr::success('Success', 'Data update successfully');
            return redirect()->route('banners.index');
        } catch (\Exception $e) {
            Toastr::error('Error', 'Banner update failed: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
 
    public function inactive(Request $request)
    {
        $inactive = Banner::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Banner::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = Banner::find($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }

    /** DataTables feed for the banners grid. */
    public function datatable(Request $request)
    {
        [$start, $length] = $this->dtPaging($request);
        $search = $this->dtSearch($request);
        $query = Banner::query()->with('category');

        // How many rows exist at all - the pager needs this
        // number before the search narrows the table down.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhereHas('category', fn ($sub) => $sub->where('name', 'LIKE', "%{$search}%"));
            });
        }
        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        // Only the category column is meaningful to sort by: the media cell is
        // an image and the status cell is a switch.

        [$column, $dir] = $this->dtOrdering($request, [1 => 'title', 3 => 'status'], 'id');

        $rows = [];
        foreach ($query->orderBy($column, $dir)->skip($start)->take($length)->get() as $i => $banner) {
            $html = trim(view('backEnd.banner._row', ['value' => $banner, 'index' => $start + $i + 1])->render());
            $rows[] = $this->dtCells($html, 5, 'banner/_row.blade.php');
        }

        return $this->dtResponse((int) $request->input('draw', 1), $total, $filtered, $rows);
    }
}
