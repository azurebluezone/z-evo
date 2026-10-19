<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CampaignReview;
use App\Models\Campaign;
use Image;
use Toastr;
use Str;
use File;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $show_data = Campaign::orderBy('id','DESC')->get();
        return view('backEnd.campaign.index',compact('show_data'));
    }
    /** `{id}/show` had no method behind it and returned a 500; redirect to the edit screen like the other Admin controllers do. */
    public function show($id)
    {
        return redirect()->route('campaign.edit', ['id' => $id]);
    }

    public function create()
    {
        $products = Product::where(['status'=>1])->select('id','name','status')->get();
        $templates = \App\Models\Template::active()->get();
        return view('backEnd.campaign.create',compact('products', 'templates'));
    }
    public function store(Request $request)
{
    $this->validate($request, [
        'short_description' => 'required',
        'description' => 'required',
        'name' => 'required',
        'status' => 'required',
        'campaign_type' => 'required|in:product,service,landing,promotional',
        'template_id' => 'required|exists:templates,id',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // Added validation for image array
    ]);
    
    $input = $request->except(['image', 'files']);
    
    // Set default values for new fields
    $input['show_features'] = $request->has('show_features') ? 1 : 0;
    $input['show_benefits'] = $request->has('show_benefits') ? 1 : 0;
    $input['show_testimonials'] = $request->has('show_testimonials') ? 1 : 0;
    $input['show_faq'] = $request->has('show_faq') ? 1 : 0;
    $input['show_cta'] = $request->has('show_cta') ? 1 : 0;
    $input['is_featured'] = $request->has('is_featured') ? 1 : 0;
    $input['status'] = $request->has('status') ? 1 : 0;
    // Handle image_one
    if ($request->hasFile('image_one')) {
        try {
            // Validate image
            $this->validate($request, [
                'image_one' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);
            
            // Create directory if not exists
            $uploadpath1 = 'public/uploads/campaign/';
            if (!file_exists($uploadpath1)) {
                mkdir($uploadpath1, 0755, true);
            }
            
            $image1 = $request->file('image_one');
            $name1 = time() . '-' . $image1->getClientOriginalName();
            $name1 = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name1); // Remove special characters
            $name1 = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name1);
            $name1 = strtolower($name1);
            
            $image1Url = $uploadpath1 . $name1;
            
            // Check if GD extension is available
            if (!extension_loaded('gd')) {
                throw new \Exception('GD Library extension is not available');
            }
            
            // Process image with error handling
            $img1 = Image::make($image1->getRealPath());
            
            // Resize image if too large
            $maxWidth = 800;
            $maxHeight = 600;
            
            if ($img1->width() > $maxWidth || $img1->height() > $maxHeight) {
                $img1->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Save as WebP
            $img1->encode('webp', 85);
            $img1->save($image1Url);
            
            $input['image_one'] = $image1Url;
            
        } catch (\Exception $e) {
            Toastr::error('Error', 'Image processing failed: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    // Repeat similar process for image_two and image_three
    // ...

    $input['slug'] = strtolower(Str::slug($request->name));
    $campaign = Campaign::create($input); 

    // Handle multiple images
    if ($request->hasFile('image')) {
        try {
            // Create directory if not exists
            $uploadPath = 'public/uploads/campaign/';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $images = $request->file('image');
            foreach ($images as $image) {
                // Validate each image
                $this->validate($request, [
                    'image.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                ]);
                
                $name = time() . '-' . $image->getClientOriginalName();
                $name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name); // Remove special characters
                $name = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name);
                $name = strtolower($name);
                
                $imageUrl = $uploadPath . $name;
                
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

                $pimage = new CampaignReview();
                $pimage->campaign_id = $campaign->id;
                $pimage->image = $imageUrl;
                $pimage->save();
            }
        } catch (\Exception $e) {
            Toastr::error('Error', 'Multiple images processing failed: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
    
    Toastr::success('Success', 'Data inserted successfully');
    return redirect()->route('campaign.index');
}

    
    public function edit($id)
    {
        $edit_data = Campaign::with('images')->find($id);
        $select_products = Product::where('campaign_id',$id)->get();
        $show_data = Campaign::orderBy('id','DESC')->get();
        $products = Product::where(['status'=>1])->select('id','name','status')->get();
        return view('backEnd.campaign.edit',compact('edit_data','products','select_products'));
    }
    
    public function update(Request $request)
    { 
        $this->validate($request, [
            'name' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'status' => 'required',
            'campaign_type' => 'required|in:product,service,landing,promotional',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        // image one
        $update_data = Campaign::find($request->hidden_id);
        $input = $request->except('hidden_id','product_ids','files','image');
        
        // Set default values for new fields
        $input['show_features'] = $request->has('show_features') ? 1 : 0;
        $input['show_benefits'] = $request->has('show_benefits') ? 1 : 0;
        $input['show_testimonials'] = $request->has('show_testimonials') ? 1 : 0;
        $input['show_faq'] = $request->has('show_faq') ? 1 : 0;
        $input['show_cta'] = $request->has('show_cta') ? 1 : 0;
        $input['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $input['status'] = $request->has('status') ? 1 : 0;
        $image_one = $request->file('image_one');
        if($image_one){
            try {
                // Validate image
                $this->validate($request, [
                    'image_one' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                ]);
                
                // Create directory if not exists
                $uploadpath1 = 'public/uploads/campaign/';
                if (!file_exists($uploadpath1)) {
                    mkdir($uploadpath1, 0755, true);
                }
                
                $name1 = time() . '-' . $image_one->getClientOriginalName();
                $name1 = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $name1); // Remove special characters
                $name1 = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $name1);
                $name1 = strtolower($name1);
                
                $imageUrl1 = $uploadpath1 . $name1;
                
                // Check if GD extension is available
                if (!extension_loaded('gd')) {
                    throw new \Exception('GD Library extension is not available');
                }
                
                // Process image with error handling
                $img1 = Image::make($image_one->getRealPath());
                
                // Resize image if too large
                $maxWidth = 800;
                $maxHeight = 600;
                
                if ($img1->width() > $maxWidth || $img1->height() > $maxHeight) {
                    $img1->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                
                // Save as WebP
                $img1->encode('webp', 85);
                $img1->save($imageUrl1);
                
                $input['image_one'] = $imageUrl1;
                
                // Delete old image if exists
                if ($update_data->image_one && file_exists($update_data->image_one)) {
                    File::delete($update_data->image_one);
                }
                
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image processing failed: ' . $e->getMessage());
                return redirect()->back()->withInput();
            }
        }else{
            $input['image_one'] = $update_data->image_one;
        }
        // image two
        $image_two = $request->file('image_two');
        if($image_two){
            // image with intervention 
            $image_two = $request->file('image_two');
            $name2 =  time().'-'.$image_two->getClientOriginalName();
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name2);
            $name2 = strtolower(preg_replace('/\s+/', '-', $name2));
            $uploadpath2 = 'public/uploads/campaign/';
            $imageUrl2 = $uploadpath2.$name2; 
            $img2=Image::make($image_two->getRealPath());
            $img2->encode('webp', 90);
            $width2 = '';
            $height2 = '';
            $img2->height() > $img2->width() ? $width2=null : $height2=null;
            $img2->resize($width2, $height2, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img2->save($imageUrl2);
            $input['image_two'] = $imageUrl2;
            File::delete($update_data->image_two);
        }else{
            $input['image_two'] = $update_data->image_two;
        }
        // image three
        $image_three = $request->file('image_three');
        if($image_three){
            // image with intervention 
            $image_three = $request->file('image_three');
            $name3 =  time().'-'.$image_three->getClientOriginalName();
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name3);
            $name3 = strtolower(preg_replace('/\s+/', '-', $name3));
            $uploadpath3 = 'public/uploads/campaign/';
            $imageUrl3 = $uploadpath3.$name3; 
            $img3 = Image::make($image_three->getRealPath());
            $img3->encode('webp', 90);
            $width3 = '';
            $height3 = '';
            $img3->height() > $img3->width() ? $width3=null : $height3=null;
            $img3->resize($width3, $height3, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img3->save($imageUrl3);
            $input['image_three'] = $imageUrl3;
            File::delete($update_data->image_three);
        }else{
            $input['image_three'] = $update_data->image_three;
        }
        // image four
        $input['slug'] = strtolower(Str::slug($request->name));
        $update_data = Campaign::find($request->hidden_id);
        $update_data->update($input);

        $images = $request->file('image');  
        if($images){
            foreach ($images as $key => $image) {
                $name =  time().'-'.$image->getClientOriginalName();
                $name = strtolower(preg_replace('/\s+/', '-', $name));
                $uploadPath = 'public/uploads/campaign/';
                $image->move($uploadPath,$name);
                $imageUrl =$uploadPath.$name;

                $pimage             = new CampaignReview();
                $pimage->campaign_id = $update_data->id;
                $pimage->image      = $imageUrl;
                $pimage->save();
            }
        }

        Toastr::success('Success','Data update successfully');
        return redirect()->route('campaign.index');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Campaign::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Campaign::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
       
        $delete_data = Campaign::find($request->hidden_id);
        $delete_data->delete();
        
        $campaign = Product::whereNotNull('campaign_id')->get();
        foreach($campaign as $key=>$value){
            $product = Product::find($value->id);
            $product->campaign_id = null;
            $product->save();
        }
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
    public function imgdestroy(Request $request)
    { 
        $delete_data = CampaignReview::find($request->id);
        File::delete($delete_data->image);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    } 
}
