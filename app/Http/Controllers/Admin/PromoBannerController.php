<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoBanner;
use Brian2694\Toastr\Facades\Toastr;
use File;
use Illuminate\Http\Request;
use Image;

class PromoBannerController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:banner-list|banner-create|banner-edit|banner-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:banner-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:banner-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:banner-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $data = PromoBanner::orderBy('sort_order')->orderBy('id')->get();
        return view('backEnd.promo-banner.index', compact('data'));
    }

    public function create()
    {
        return view('backEnd.promo-banner.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|string',
            'title' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $input = $request->only(['title', 'link', 'sort_order']);
        $input['status'] = $request->status ? 1 : 0;

        $image = $request->file('image');
        if ($image) {
            $uploadDir = public_path('uploads/promo-banner/');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $name = time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
            $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
            $name = strtolower($name);

            $relativePath = 'uploads/promo-banner/' . $name;
            $fullPath = public_path($relativePath);

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 85);
            $img->save($fullPath);

            $input['image'] = $relativePath;
        } else {
            $input['image'] = 'assets/images/default.png';
        }

        PromoBanner::create($input);
        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('promo_banners.index');
    }

    public function edit($id)
    {
        $edit_data = PromoBanner::findOrFail($id);
        return view('backEnd.promo-banner.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'link' => 'nullable|string',
            'title' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $update_data = PromoBanner::findOrFail($request->id);
        $input = $request->only(['title', 'link', 'sort_order']);
        $input['status'] = $request->status ? 1 : 0;

        $image = $request->file('image');
        if ($image) {
            $uploadDir = public_path('uploads/promo-banner/');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $name = time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name);
            $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
            $name = strtolower($name);

            $relativePath = 'uploads/promo-banner/' . $name;
            $fullPath = public_path($relativePath);

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 85);
            $img->save($fullPath);

            $input['image'] = $relativePath;

            if (!empty($update_data->image)) {
                File::delete(public_path($update_data->image));
            }
        }

        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('promo_banners.index');
    }

    public function inactive(Request $request)
    {
        $inactive = PromoBanner::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = PromoBanner::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = PromoBanner::findOrFail($request->hidden_id);
        if (!empty($delete_data->image)) {
            File::delete($delete_data->image);
        }
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
