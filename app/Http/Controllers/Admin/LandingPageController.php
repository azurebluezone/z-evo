<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LandingPage;
use App\Models\Template;
use Illuminate\Support\Str;
use Toastr;

class LandingPageController extends Controller
{
    public function index()
    {
        $landingPages = LandingPage::with('template')->orderBy('created_at', 'desc')->get();
        $templates = Template::active()->orderBy('id', 'desc')->get();
        return view('backEnd.landing-pages.index', compact('landingPages', 'templates'));
    }

    public function previewTemplate($id)
    {
        $template = Template::active()->findOrFail($id);

        $landingPage = new LandingPage();
        $landingPage->title = $template->name;
        $landingPage->subtitle = $template->description;
        $landingPage->description = null;
        $landingPage->cta_text = 'Order Now';
        $landingPage->cta_link = route('landing.order');
        $landingPage->image_url = null;
        $landingPage->extra_fields = [
            'old_price' => null,
            'new_price' => null,
            'countdown_date' => null,
            'features' => [],
            'why_us' => [],
            'contact_phone' => null,
            'product_id' => null,
        ];

        $templateView = 'templates.' . $template->view_name;
        if (!view()->exists($templateView)) {
            $templateView = 'templates.default';
        }

        return view($templateView, compact('landingPage'));
    }

    public function create()
    {
        $templates = Template::active()->get();
        return view('backEnd.landing-pages.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|url',
            'template_id' => 'required|exists:templates,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $input = $request->only(['title', 'subtitle', 'description', 'cta_text', 'cta_link', 'template_id']);
        
        // Handle extra_fields JSON
        if ($request->has('extra_fields')) {
            $input['extra_fields'] = $request->extra_fields;
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $uploadPath = 'public/uploads/landing-pages/';
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                $image = $request->file('image');
                $name = time() . '-' . Str::slug($image->getClientOriginalName()) . '.webp';
                $imageUrl = $uploadPath . $name;
                
                // Process image
                $img = \Image::make($image->getRealPath());
                $img->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->encode('webp', 85);
                $img->save($imageUrl);
                
                $input['image_url'] = $imageUrl;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image upload failed: ' . $e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        // Generate slug
        $input['slug'] = Str::slug($request->title);
        
        // Set status
        $input['status'] = $request->has('status') ? 1 : 0;

        LandingPage::create($input);
        
        Toastr::success('Success', 'Landing page created successfully');
        return redirect()->route('landing-pages.index');
    }

    public function show($id)
    {
        $landingPage = LandingPage::with('template')->findOrFail($id);
        return view('backEnd.landing-pages.show', compact('landingPage'));
    }

    public function edit($id)
    {
        $landingPage = LandingPage::findOrFail($id);
        $templates = Template::active()->get();
        return view('backEnd.landing-pages.edit', compact('landingPage', 'templates'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|url',
            'template_id' => 'required|exists:templates,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $landingPage = LandingPage::findOrFail($id);
        $input = $request->only(['title', 'subtitle', 'description', 'cta_text', 'cta_link', 'template_id']);
        
        // Handle extra_fields JSON
        if ($request->has('extra_fields')) {
            $input['extra_fields'] = $request->extra_fields;
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                // Delete old image
                if ($landingPage->image_url && file_exists($landingPage->image_url)) {
                    unlink($landingPage->image_url);
                }
                
                $uploadPath = 'public/uploads/landing-pages/';
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                $image = $request->file('image');
                $name = time() . '-' . Str::slug($image->getClientOriginalName()) . '.webp';
                $imageUrl = $uploadPath . $name;
                
                // Process image
                $img = \Image::make($image->getRealPath());
                $img->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->encode('webp', 85);
                $img->save($imageUrl);
                
                $input['image_url'] = $imageUrl;
            } catch (\Exception $e) {
                Toastr::error('Error', 'Image upload failed: ' . $e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        // Update slug if title changed
        if ($landingPage->title !== $request->title) {
            $input['slug'] = Str::slug($request->title);
        }
        
        // Set status
        $input['status'] = $request->has('status') ? 1 : 0;

        $landingPage->update($input);
        
        Toastr::success('Success', 'Landing page updated successfully');
        return redirect()->route('landing-pages.index');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id', $request->input('hidden_id'));
        $landingPage = LandingPage::findOrFail($id);
        
        // Delete image
        if ($landingPage->image_url && file_exists($landingPage->image_url)) {
            unlink($landingPage->image_url);
        }
        
        $landingPage->delete();
        
        Toastr::success('Success', 'Landing page deleted successfully');
        return redirect()->route('landing-pages.index');
    }

    /**
     * Get template configuration for AJAX requests
     */
    public function templateConfig($id)
    {
        $template = Template::findOrFail($id);
        return response()->json([
            'config' => $template->config ? json_decode($template->config, true) : null,
            'view_name' => $template->view_name,
            'description' => $template->description,
            'name' => $template->name,
        ]);
    }
}
