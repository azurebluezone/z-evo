<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\FrontendController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CampaignReview;
use App\Models\Campaign;
use App\Models\Template;
use App\Services\CampaignContent;
use Image;
use Toastr;
use Str;
use File;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        // The admin list is the campaign manager's home screen: it needs the
        // product and template names without one query per row, and it follows
        // the same sort_order the public campaign list uses.
        $show_data = Campaign::with(['product', 'template'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('backEnd.campaign.index', compact('show_data'));
    }
    /** `{id}/show` had no method behind it and returned a 500; redirect to the edit screen like the other Admin controllers do. */
    public function show($id)
    {
        return redirect()->route('campaign.edit', ['id' => $id]);
    }

    /**
     * Admin preview of the real campaign page, including inactive campaigns.
     * The public route filters status=1, so editors could not inspect a draft.
     */
    public function preview($id)
    {
        $campaign = Campaign::with(['template', 'product.image', 'product.images', 'images'])
            ->findOrFail($id);

        return app(FrontendController::class)->renderCampaign($campaign);
    }

    /** Template design preview used by the create/edit form. */
    public function templatePreview($id)
    {
        return app(LandingPageController::class)->previewTemplate($id);
    }

    public function create()
    {
        $products = Product::where(['status' => 1])
            ->select('id', 'name', 'status', 'new_price', 'old_price', 'stock')
            ->orderBy('name')
            ->get();
        $templates = Template::active()->get();
        return view('backEnd.campaign.create', compact('products', 'templates'));
    }
    public function store(Request $request)
{
    $this->validate($request, [
        'short_description' => 'required',
        'description' => 'required',
        'name' => 'required',
        'review' => 'required',
        'status' => 'nullable|boolean',
        'is_featured' => 'nullable|boolean',
        'sort_order' => 'nullable|integer|min:0|max:999999',
        'campaign_type' => 'required|in:product,service,landing,promotional',
        'template_id' => 'required|exists:templates,id',
        'product_id' => 'required|exists:products,id',
        'campaign_price' => 'nullable|numeric|min:0',
        'campaign_discount_percent' => 'nullable|numeric|gt:0|lt:100',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // Added validation for image array
    ]);
    $this->validate($request, $this->contentRules());

    $input = $request->except(['image', 'files']);
    $input['extra_fields'] = (new CampaignContent())->normalize((array) $request->input('extra_fields', []));

    // The `date` column is NOT NULL and has no default; the form does not post
    // it, so without this every admin campaign create ended in a SQL error.
    $input['date'] = $request->input('date', now()->toDateString());
    $input['campaign_price_enabled'] = $request->boolean('campaign_price_enabled') ? 1 : 0;
    $input['allow_coupon'] = $request->boolean('allow_coupon') ? 1 : 0;

    if ($input['campaign_price_enabled']
        && !$request->filled('campaign_price')
        && !$request->filled('campaign_discount_percent')) {
        return redirect()->back()
            ->withErrors(['campaign_price' => 'Enter an offer price or a discount percent.'])
            ->withInput();
    }

    $product = Product::find($request->product_id);
    if ($product && $request->filled('campaign_price')
        && (float) $request->campaign_price >= (float) $product->new_price) {
        return redirect()->back()
            ->withErrors(['campaign_price' => 'The offer price must be lower than the product price.'])
            ->withInput();
    }

    // Set default values for new fields
    $input['show_features'] = $request->boolean('show_features') ? 1 : 0;
    $input['show_benefits'] = $request->boolean('show_benefits') ? 1 : 0;
    $input['show_testimonials'] = $request->boolean('show_testimonials') ? 1 : 0;
    $input['show_faq'] = $request->boolean('show_faq') ? 1 : 0;
    $input['show_cta'] = $request->boolean('show_cta') ? 1 : 0;
    $input['is_featured'] = $request->boolean('is_featured') ? 1 : 0;
    $input['status'] = $request->boolean('status') ? 1 : 0;

    if (!$request->hasFile('image_one') && !$request->filled('image_one')) {
        $input['image_one'] = '';
    }
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

    $input['slug'] = $this->uniqueSlug($request->name);
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
        $edit_data = Campaign::with(['images', 'product', 'template'])->findOrFail($id);

        $products = Product::where(['status' => 1])
            ->select('id', 'name', 'status', 'new_price', 'old_price', 'stock')
            ->orderBy('name')
            ->get();

        // Keep the campaign's current product selectable even when it has been
        // deactivated; otherwise the edit form silently drops the link on save.
        if ($edit_data->product && !$products->contains('id', $edit_data->product->id)) {
            $products->push($edit_data->product);
        }

        $templates = Template::active()->get();
        if ($edit_data->template && !$templates->contains('id', $edit_data->template->id)) {
            $templates->push($edit_data->template);
        }

        $select_products = Product::where('campaign_id', $id)->get();
        $show_data = Campaign::orderBy('id', 'DESC')->get();

        return view('backEnd.campaign.edit', compact('edit_data', 'products', 'select_products', 'templates'));
    }
    
    public function update(Request $request)
    { 
        $this->validate($request, [
            'name' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'status' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'campaign_type' => 'required|in:product,service,landing,promotional',
            'product_id' => 'required|exists:products,id',
            'campaign_price' => 'nullable|numeric|min:0',
            'campaign_discount_percent' => 'nullable|numeric|gt:0|lt:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $this->validate($request, $this->contentRules());
        // image one
        $update_data = Campaign::find($request->hidden_id);
        if (!$update_data) {
            return redirect()->back()->with('error', 'Campaign not found.');
        }
        $input = $request->except('hidden_id','product_ids','files','image');
        $input['extra_fields'] = (new CampaignContent())->normalize((array) $request->input('extra_fields', []));

        $input['date'] = $request->input('date', $update_data->date ?: now()->toDateString());
        $input['campaign_price_enabled'] = $request->boolean('campaign_price_enabled') ? 1 : 0;
        $input['allow_coupon'] = $request->boolean('allow_coupon') ? 1 : 0;

        if ($input['campaign_price_enabled']
            && !$request->filled('campaign_price')
            && !$request->filled('campaign_discount_percent')) {
            return redirect()->back()
                ->withErrors(['campaign_price' => 'Enter an offer price or a discount percent.'])
                ->withInput();
        }

        $product = Product::find($request->product_id);
        if ($product && $request->filled('campaign_price')
            && (float) $request->campaign_price >= (float) $product->new_price) {
            return redirect()->back()
                ->withErrors(['campaign_price' => 'The offer price must be lower than the product price.'])
                ->withInput();
        }

        // Set default values for new fields
        $input['show_features'] = $request->boolean('show_features') ? 1 : 0;
        $input['show_benefits'] = $request->boolean('show_benefits') ? 1 : 0;
        $input['show_testimonials'] = $request->boolean('show_testimonials') ? 1 : 0;
        $input['show_faq'] = $request->boolean('show_faq') ? 1 : 0;
        $input['show_cta'] = $request->boolean('show_cta') ? 1 : 0;
        $input['is_featured'] = $request->boolean('is_featured') ? 1 : 0;
        $input['status'] = $request->boolean('status') ? 1 : 0;
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
        // Keep the public URL stable unless the campaign name really changed.
        $input['slug'] = $request->name !== $update_data->name
            ? $this->uniqueSlug($request->name, $update_data->id)
            : $update_data->slug;
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
        $inactive = Campaign::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Campaign::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = Campaign::findOrFail($request->hidden_id);
        $delete_data->delete();

        // Only unlink products that pointed at this campaign; the previous
        // query cleared every product with any campaign_id.
        Product::where('campaign_id', $delete_data->id)->update(['campaign_id' => null]);

        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
    public function imgdestroy(Request $request)
    {
        // POST + CSRF only (was a GET link). findOrFail: a stale id used to
        // fatal on ->image. Deletion is confined to uploads/ so a corrupted
        // row cannot aim File::delete outside the public upload directory.
        $request->validate(['id' => 'required|integer']);
        $delete_data = CampaignReview::findOrFail($request->input('id'));

        $path = ltrim(preg_replace('~^public/~', '', (string) $delete_data->image), '/');
        if ($path !== '' && str_starts_with($path, 'uploads/')) {
            File::delete(public_path($path));
        }

        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }

    /**
     * Stable, collision-free public slug. The DB has a unique index on
     * campaigns.slug; suffixing here keeps the admin form usable instead of
     * turning a duplicate name into a 500.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? Str::substr($base, 0, 240) : 'campaign';

        $slug = $base;
        $counter = 2;

        while (
            Campaign::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Rules for the template-specific content panel. The same whitelist is
     * enforced again by CampaignContent::normalize() before the row is saved.
     */
    private function contentRules(): array
    {
        $maxRows = CampaignContent::MAX_ROWS;

        $rules = [
            'extra_fields' => 'nullable|array',
            'extra_fields.video_url' => 'nullable|url|max:500',
            'extra_fields.countdown_date' => 'nullable|date',
            'extra_fields.contact_phone' => 'nullable|string|max:50',
            'extra_fields.email' => 'nullable|email|max:255',
            'extra_fields.facebook' => 'nullable|url|max:500',
            'extra_fields.instagram' => 'nullable|url|max:500',
            'extra_fields.youtube' => 'nullable|url|max:500',
        ];

        $limits = [
            'title' => 255,
            'name' => 255,
            'icon' => 255,
            'question' => 500,
            'description' => 2000,
            'text' => 2000,
            'answer' => 2000,
            'image' => 500,
        ];

        foreach (CampaignContent::ROW_FIELDS as $field => $keys) {
            $rules["extra_fields.$field"] = "nullable|array|max:$maxRows";

            foreach ($keys as $key) {
                $limit = $limits[$key] ?? 255;
                $rules["extra_fields.$field.*.$key"] = "nullable|string|max:$limit";
            }
        }

        $rules['extra_fields.testimonials.*.rating'] = 'nullable|numeric|min:1|max:5';
        $rules['extra_fields.packages.*.price'] = 'nullable|numeric|min:0';

        return $rules;
    }
}
