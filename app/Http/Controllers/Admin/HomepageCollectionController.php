<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Toastr;

class HomepageCollectionController extends Controller
{
    public function index()
    {
        $collections = HomepageCollection::with('category')->orderBy('sort_order')->orderBy('id', 'desc')->get();
        return view('backEnd.homepage.collections.index', compact('collections'));
    }

    public function create()
    {
        $categories = Category::where('status', 1)->orderBy('name')->get();
        return view('backEnd.homepage.collections.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'product_limit' => 'required|integer|min:1|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $input = $request->except(['banner_image']);
        $input['sort_order'] = $request->sort_order ?? 0;
        $input['status'] = $request->has('status') ? 1 : 0;

        if ($request->hasFile('banner_image')) {
            try {
                $uploadPath = 'public/uploads/homepage-collections/';
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image = $request->file('banner_image');
                $name = time() . '-' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                $imageUrl = $uploadPath . $name;

                $img = \Image::make($image->getRealPath());
                $img->encode('webp', 85);
                $img->save($imageUrl);

                $input['banner_image'] = $imageUrl;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image upload failed: ' . $e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        HomepageCollection::create($input);
        Toastr::success('Success', 'Homepage collection created successfully');
        return redirect()->route('admin.homepage.collections.index');
    }

    public function edit($id)
    {
        $collection = HomepageCollection::findOrFail($id);
        $categories = Category::where('status', 1)->orderBy('name')->get();
        return view('backEnd.homepage.collections.edit', compact('collection', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'product_limit' => 'required|integer|min:1|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $collection = HomepageCollection::findOrFail($id);
        $input = $request->except(['banner_image']);
        $input['sort_order'] = $request->sort_order ?? 0;
        $input['status'] = $request->has('status') ? 1 : 0;

        if ($request->hasFile('banner_image')) {
            try {
                if ($collection->banner_image && file_exists($collection->banner_image)) {
                    unlink($collection->banner_image);
                }

                $uploadPath = 'public/uploads/homepage-collections/';
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image = $request->file('banner_image');
                $name = time() . '-' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                $imageUrl = $uploadPath . $name;

                $img = \Image::make($image->getRealPath());
                $img->encode('webp', 85);
                $img->save($imageUrl);

                $input['banner_image'] = $imageUrl;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image upload failed: ' . $e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        $collection->update($input);
        Toastr::success('Success', 'Homepage collection updated successfully');
        return redirect()->route('admin.homepage.collections.index');
    }

    public function destroy(Request $request)
    {
        $collection = HomepageCollection::findOrFail($request->hidden_id);

        if ($collection->banner_image && file_exists($collection->banner_image)) {
            unlink($collection->banner_image);
        }

        $collection->delete();
        Toastr::success('Success', 'Homepage collection deleted successfully');
        return redirect()->back();
    }

    public function inactive(Request $request)
    {
        $collection = HomepageCollection::findOrFail($request->hidden_id);
        $collection->status = 0;
        $collection->save();
        Toastr::success('Success', 'Collection inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $collection = HomepageCollection::findOrFail($request->hidden_id);
        $collection->status = 1;
        $collection->save();
        Toastr::success('Success', 'Collection active successfully');
        return redirect()->back();
    }
}
