<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\LandingPage;
use App\Models\Template;
use Illuminate\Support\Str;
use Toastr;

class LandingPageController extends Controller
{
    public function index()
    {
        // The registry is the source of truth for the new designs. Keeping the
        // inactive new rows visible here lets an editor finish a template's
        // variable map before it is offered to campaigns.
        $registryTemplates = array_keys((array) config('landing_templates.templates', []));
        $templates = Template::where('is_active', true)
            ->orWhereIn('view_name', $registryTemplates)
            ->orderBy('id')
            ->get();

        // One card image per design: LandingCardImage resolves the campaign
        // hero, the generated design hero, the Appearance fallback and finally
        // the tracked branding fallback, so a card never renders a broken file.
        $campaignsByTemplate = Campaign::query()
            ->where('status', 1)
            ->whereNotNull('template_id')
            ->orderBy('id')
            ->get()
            ->keyBy('template_id');

        return view('backEnd.landing-pages.index', compact('templates', 'campaignsByTemplate'));
    }

    /** Per-template toggle for the 72 measured landing variables. */
    public function templateVariables($id)
    {
        $template = Template::findOrFail($id);
        $registry = (array) config('landing_templates', []);
        $groups = (array) ($registry['groups'] ?? []);
        $templateKey = \App\Support\LandingTemplateVars::templateKeyFor($template);
        $displayName = (string) (config('landing_templates.templates.' . $templateKey . '.name') ?: $template->name);
        $enabled = \App\Support\LandingTemplateVars::enabledVariablesFor($template);
        $config = \App\Support\LandingTemplateVars::configArray($template);
        $customised = array_key_exists('enabled_variables', $config);
        $contentFields = \App\Support\LandingTemplateVars::contentFieldsFor($template);

        $totalVariables = 0;
        foreach ($groups as $group) {
            $totalVariables += count((array) ($group['variables'] ?? []));
        }

        return view('backEnd.landing-pages.template-variables', compact(
            'template',
            'groups',
            'templateKey',
            'displayName',
            'enabled',
            'customised',
            'contentFields',
            'totalVariables'
        ));
    }

    /** Persist the enabled variable list into templates.config. */
    public function updateTemplateVariables(Request $request, $id)
    {
        $template = Template::findOrFail($id);
        $registry = (array) config('landing_templates', []);
        $valid = [];

        foreach ((array) ($registry['groups'] ?? []) as $group) {
            foreach (array_keys((array) ($group['variables'] ?? [])) as $key) {
                $valid[] = $key;
            }
        }

        $enabled = array_values(array_intersect(
            $valid,
            array_map('strval', (array) $request->input('enabled_variables', []))
        ));

        $config = \App\Support\LandingTemplateVars::configArray($template);
        $config['enabled_variables'] = $enabled;
        $template->config = $config;
        $template->save();

        Toastr::success('Success', count($enabled) . ' template variables enabled');

        return redirect()->route('landing-pages.template-variables', $template->id);
    }

    public function previewTemplate($id)
    {
        // Admins may preview an inactive template too: the campaign edit form
        // keeps the campaign's current template selectable after deactivation.
        $template = Template::findOrFail($id);

        $landingPage = new LandingPage();
        $landingPage->title = (string) (config('landing_templates.templates.' . $template->view_name . '.name') ?: $template->name);
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
            abort(404, 'Template view not found: ' . $templateView);
        }

        $tpl = \App\Support\LandingTemplateVars::make($landingPage, $template, null);

        return view($templateView, compact('landingPage', 'tpl'));
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

        // Template::$casts declares config as array, so normally it arrives as
        // an array. Rows written by the old seeder double-encoded the JSON; a
        // string here is legacy data, not a second format to support forward.
        $config = $template->config;
        if (is_string($config)) {
            $config = json_decode($config, true) ?: null;
        }

        return response()->json([
            'config' => $config,
            'view_name' => $template->view_name,
            'description' => $template->description,
            'name' => $template->name,
        ]);
    }
}
