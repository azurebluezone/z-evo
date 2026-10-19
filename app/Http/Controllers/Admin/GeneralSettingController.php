<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Toastr;
use Image;
use File;
use DB;

class GeneralSettingController extends Controller
{

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
        $this->middleware('permission:setting-list|setting-create|setting-edit|setting-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:setting-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:setting-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:setting-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = GeneralSetting::orderBy('id', 'DESC')->get();
        return view('backEnd.settings.index', compact('show_data'));
    }
    public function create()
    {
        return view('backEnd.settings.create');
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'white_logo' => 'required',
            'favicon' => 'required',
            'status' => 'required',
        ]);

        // image with intervention 
        $image = $request->file('white_logo');
        $name =  time() . '-' . $image->getClientOriginalName();
        $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
        $name = strtolower(preg_replace('/\s+/', '-', $name));
        $uploadpath = 'public/uploads/settings/';
        $imageUrl = $uploadpath . $name;
        $img = Image::make($image->getRealPath());
        $img->encode('webp', 90);
        $width = '';
        $height = '';
        $img->height() > $img->width() ? $width = null : $height = null;
        $img->resize($width, $height);
        $img->save($imageUrl);

        // dark logo
        //
        // NOTE: the validation above makes white_logo and favicon required but
        // NOT dark_logo. The original code nevertheless called ->file('dark_logo')
        // and getClientOriginalName() unconditionally, so creating settings
        // without a dark logo died with "Call to a member function
        // getClientOriginalName() on null" instead of saving.
        $image2Url = '';
        if ($image2 = $request->file('dark_logo')) {
            $name2 =  time() . '-' . $image2->getClientOriginalName();
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
            $name2 = strtolower(preg_replace('/\s+/', '-', $name2));
            $uploadpath2 = 'public/uploads/settings/';
            $image2Url = $uploadpath2 . $name2;
            $img2 = Image::make($image2->getRealPath());
            $img2->encode('webp', 90);
            $width2 = '';
            $height2 = '';
            $img2->height() > $img2->width() ? $width2 = null : $height2 = null;
            $img2->resize($width2, $height2);
            $img2->save($image2Url);
        }

        // image with intervention 
        $image3 = $request->file('favicon');
        $name3 =  time() . '-' . $image3->getClientOriginalName();
        $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
        $name3 = strtolower(preg_replace('/\s+/', '-', $name3));
        $uploadpath3 = 'public/uploads/settings/';
        $image3Url = $uploadpath3 . $name3;
        $img3 = Image::make($image3->getRealPath());
        $img3->encode('webp', 90);
        $width3 = 32;
        $height3 = 32;
        $img3->height() > $img3->width() ? $width3 = null : $height3 = null;
        $img3->resize($width3, $height3);
        $img3->save($image3Url);

        $input = $request->all();
        $input['white_logo'] = $imageUrl;
        $input['dark_logo'] = $image2Url;
        $input['favicon'] = $image3Url;

        $setting = GeneralSetting::create($input);

        if ($request->has('seo_meta')) {
            $setting->updateSeoMeta($this->extractSeoMeta($request));
        }

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('settings.index');
    }

    public function edit($id)
    {
        $edit_data = GeneralSetting::find($id);
        return view('backEnd.settings.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);
        $update_data = GeneralSetting::find($request->id);
        $input = $request->all();
        // new white logo
        $image = $request->file('white_logo');
        if ($image) {
            try {
                // Validate image
                $this->validate($request, [
                    'white_logo' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                ]);

                // Create directory if not exists
                $uploadpath = 'public/uploads/settings/';
                if (!file_exists($uploadpath)) {
                    mkdir($uploadpath, 0755, true);
                }

                $name = time() . '-' . $image->getClientOriginalName();
                $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name); // Remove special characters
                $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
                $name = strtolower($name);

                $imageUrl = $uploadpath . $name;

                // Check if GD extension is available
                if (!extension_loaded('gd')) {
                    throw new \Exception('GD Library extension is not available');
                }

                // Process image with error handling
                $img = Image::make($image->getRealPath());

                // Resize image if too large
                $maxWidth = 800;
                $maxHeight = 600;

                if ($img->width() > $maxWidth || $img->height() > $maxHeight) {
                    $img->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                // Save as WebP
                $img->encode('webp', 85);
                $img->save($imageUrl);

                $input['white_logo'] = $imageUrl;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image processing failed: ' . $e->getMessage());
                return redirect()->back();
            }
        } else {
            $input['white_logo'] = $update_data->white_logo;
        }
        // new dark logo
        $image2 = $request->file('dark_logo');
        if ($image2) {
            try {
                // Validate image
                $this->validate($request, [
                    'dark_logo' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                ]);

                // Create directory if not exists
                $uploadpath2 = 'public/uploads/settings/';
                if (!file_exists($uploadpath2)) {
                    mkdir($uploadpath2, 0755, true);
                }

                $name2 = time() . '-' . $image2->getClientOriginalName();
                $name2 = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name2); // Remove special characters
                $name2 = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name2);
                $name2 = strtolower($name2);

                $image2Url = $uploadpath2 . $name2;

                // Check if GD extension is available
                if (!extension_loaded('gd')) {
                    throw new \Exception('GD Library extension is not available');
                }

                // Process image with error handling
                $img2 = Image::make($image2->getRealPath());

                // Resize image if too large
                $maxWidth = 800;
                $maxHeight = 600;

                if ($img2->width() > $maxWidth || $img2->height() > $maxHeight) {
                    $img2->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                // Save as WebP
                $img2->encode('webp', 85);
                $img2->save($image2Url);

                $input['dark_logo'] = $image2Url;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image processing failed: ' . $e->getMessage());
                return redirect()->back();
            }
        } else {
            $input['dark_logo'] = $update_data->dark_logo;
        }

        // new favicon image
        $image3 = $request->file('favicon');
        if ($image3) {
            try {
                // Validate image
                $this->validate($request, [
                    'favicon' => 'image|mimes:jpeg,png,jpg,gif,webp|max:1024'
                ]);

                // Create directory if not exists
                $uploadpath3 = 'public/uploads/settings/';
                if (!file_exists($uploadpath3)) {
                    mkdir($uploadpath3, 0755, true);
                }

                $name3 = time() . '-' . $image3->getClientOriginalName();
                $name3 = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name3); // Remove special characters
                $name3 = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name3);
                $name3 = strtolower($name3);

                $image3Url = $uploadpath3 . $name3;

                // Check if GD extension is available
                if (!extension_loaded('gd')) {
                    throw new \Exception('GD Library extension is not available');
                }

                // Process image with error handling
                $img3 = Image::make($image3->getRealPath());

                // Resize favicon to 32x32
                $img3->resize(32, 32, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Save as WebP
                $img3->encode('webp', 85);
                $img3->save($image3Url);

                $input['favicon'] = $image3Url;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image processing failed: ' . $e->getMessage());
                return redirect()->back();
            }
        } else {
            $input['favicon'] = $update_data->favicon;
        }
        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        if ($request->has('seo_meta')) {
            $update_data->updateSeoMeta($this->extractSeoMeta($request));
        }

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('settings.index');
    }

    public function inactive(Request $request)
    {
        $inactive = GeneralSetting::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = GeneralSetting::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = GeneralSetting::find($request->hidden_id);
        File::delete($delete_data->image);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }

    public function toggleTopCategoriesSection(Request $request)
    {
        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('Error', 'General settings not found');
            return redirect()->back();
        }

        $setting->home_page_top_categories_section = $setting->home_page_top_categories_section ? 0 : 1;
        $setting->save();

        $message = $setting->home_page_top_categories_section ? 
            'Top Categories section is now visible on home page' : 
            'Top Categories section is now hidden from home page';
            
        Toastr::success('Success', $message);
        return redirect()->back();
    }

    public function toggleBrandsSection(Request $request)
    {
        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('Error', 'General settings not found');
            return redirect()->back();
        }

        $setting->home_page_brands_section = $setting->home_page_brands_section ? 0 : 1;
        $setting->save();

        $message = $setting->home_page_brands_section ? 
            'Brands section is now visible on home page' : 
            'Brands section is now hidden from home page';
            
        Toastr::success('Success', $message);
        return redirect()->back();
    }

    public function toggleHotDealSection(Request $request)
    {
        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('Error', 'General settings not found');
            return redirect()->back();
        }

        $setting->home_page_hot_deal_section = $setting->home_page_hot_deal_section ? 0 : 1;
        $setting->save();

        $message = $setting->home_page_hot_deal_section ? 
            'Hot Deal section is now visible on home page' : 
            'Hot Deal section is now hidden from home page';
            
        Toastr::success('Success', $message);
        return redirect()->back();
    }
}
