<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\SocialMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Image;
use Toastr;

class WebsiteSetupController extends Controller
{
    /**
     * The old landing page was a set of tabs labelled HeaderSoon /
     * FooterSoon / AppearanceSoon, while the real pages lived one URL
     * deeper. It was a dead end pretending to be a settings screen, so the
     * hub now sends the admin to the first real page.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.website-setup.header');
    }

    private function getSettings(): GeneralSetting
    {
        $settings = GeneralSetting::first();
        if ($settings) {
            return $settings;
        }

        return new GeneralSetting();
    }

    private function storeImageAsWebp($file, string $relativeDir): string
    {
        $uploadDir = public_path(trim($relativeDir, '/') . '/');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $originalName = time() . '-' . $file->getClientOriginalName();
        $originalName = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $originalName);
        $originalName = strtolower($originalName);

        $webpName = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $originalName);
        $webpRelativePath = trim($relativeDir, '/') . '/' . $webpName;
        $webpFullPath = public_path($webpRelativePath);

        try {
            if (!extension_loaded('gd')) {
                throw new \RuntimeException('GD extension not available');
            }

            $img = Image::make($file->getRealPath());
            $img->encode('webp', 85);
            $img->save($webpFullPath);

            return $webpRelativePath;
        } catch (\Throwable $e) {
            $fallbackRelativePath = trim($relativeDir, '/') . '/' . $originalName;
            $fallbackFullPath = public_path($fallbackRelativePath);

            $file->move($uploadDir, $originalName);

            return $fallbackRelativePath;
        }
    }

    public function header(): \Illuminate\View\View
    {
        $settings = $this->getSettings();
        return view('backEnd.website-setup.header', compact('settings'));
    }

    public function updateHeader(Request $request): RedirectResponse
    {
        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'General settings not found. Please create General Settings first.');
            return redirect()->back();
        }

        $this->validate($request, [
            'name' => 'nullable|string|max:255',
            'white_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'dark_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'about_url' => 'nullable|string|max:255',
            'contact_url' => 'nullable|string|max:255',
            'header_links' => 'nullable|array',
            'header_links.*.title' => 'nullable|string|max:100',
            'header_links.*.url' => 'nullable|string|max:255',
        ]);

        $input = [];
        if ($request->filled('name')) {
            $input['name'] = $request->input('name');
        }

        if ($request->filled('about_url')) {
            $input['about_url'] = $request->input('about_url');
        }
        if ($request->filled('contact_url')) {
            $input['contact_url'] = $request->input('contact_url');
        }

        if (is_array($request->input('header_links'))) {
            $links = (array) $request->input('header_links', []);
            $normalized = [];
            foreach ($links as $link) {
                $title = trim((string) ($link['title'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));

                if ($title === '' && $url === '') {
                    continue;
                }

                $normalized[] = [
                    'title' => $title,
                    'url' => $url,
                ];
            }
            $input['header_links'] = !empty($normalized) ? json_encode($normalized) : null;
        }

        if ($request->file('white_logo')) {
            $input['white_logo'] = 'public/' . $this->storeImageAsWebp($request->file('white_logo'), 'uploads/settings');
        }
        if ($request->file('dark_logo')) {
            $input['dark_logo'] = 'public/' . $this->storeImageAsWebp($request->file('dark_logo'), 'uploads/settings');
        }
        if ($request->file('favicon')) {
            $input['favicon'] = 'public/' . $this->storeImageAsWebp($request->file('favicon'), 'uploads/settings');
        }

        if (!empty($input)) {
            $settings->update($input);
        }

        Toastr::success('Success', 'Header settings updated successfully');
        return redirect()->back();
    }

    public function footer(): \Illuminate\View\View
    {
        $settings = $this->getSettings();
        $socials = SocialMedia::orderBy('id', 'DESC')->get();
        // Values shown in the storefront footer's Contact Us widget.
        $contact = Contact::where('status', 1)->orderBy('id')->first();
        return view('backEnd.website-setup.footer', compact('settings', 'socials', 'contact'));
    }

    public function updateFooter(Request $request): RedirectResponse
    {
        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'General settings not found. Please create General Settings first.');
            return redirect()->back();
        }

        $this->validate($request, [
            // Capped because the storefront prints this inside one footer
            // column: an unbounded paragraph stretched the grid and broke
            // the homepage layout (reported 2026-10-28). ~60 words fits.
            'footer_about_text' => 'nullable|string|max:400',
            'footer_payment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'copyright' => 'nullable|string|max:255',
            'contact_address' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:190',
        ]);

        $input = [
            'footer_about_text' => $request->input('footer_about_text'),
            'copyright' => $request->input('copyright'),
        ];

        if ($request->file('footer_payment_image')) {
            $input['footer_payment_image'] = $this->storeImageAsWebp($request->file('footer_payment_image'), 'uploads/settings');
        }

        $settings->update($input);

        // The storefront footer reads address/phone/email from the active
        // Contact row (AppServiceProvider shares it as $contact). Before
        // this page existed, that row could only be written from the
        // separate Contact Manager, so the footer silently showed fallback
        // placeholder text. Save the row here so the footer form owns the
        // values it displays.
        $contact = Contact::where('status', 1)->orderBy('id')->first() ?? new Contact();
        // phone/email/address are NOT NULL in the 2023 migration, so empty
        // inputs are saved as '' rather than NULL; the storefront falls
        // back to its placeholder text for blank values.
        $contact->fill([
            'address' => (string) $request->input('contact_address'),
            'phone' => (string) $request->input('contact_phone'),
            'email' => (string) $request->input('contact_email'),
            'status' => 1,
        ]);
        $contact->save();

        Toastr::success('Success', 'Footer settings updated successfully');
        return redirect()->back();
    }

    public function appearance(): \Illuminate\View\View
    {
        $settings = $this->getSettings();
        return view('backEnd.website-setup.appearance', compact('settings'));
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'General settings not found. Please create General Settings first.');
            return redirect()->back();
        }

        $this->validate($request, [
            'primary_color' => 'nullable|string|max:32',
            'secondary_color' => 'nullable|string|max:32',
            'support_phone' => 'nullable|string|max:64',
            'messenger_link' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:64',
            'order_whatsapp_enabled' => 'nullable|in:0,1',
            'order_whatsapp_number' => 'nullable|string|max:64',
            'frontend_empty_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $input = [
            'primary_color' => $request->input('primary_color'),
            'secondary_color' => $request->input('secondary_color'),
            'support_phone' => $request->input('support_phone'),
            'messenger_link' => $request->input('messenger_link'),
            'whatsapp_number' => $request->input('whatsapp_number'),
            'order_whatsapp_enabled' => (int) $request->input('order_whatsapp_enabled', 0) === 1 ? 1 : 0,
            'order_whatsapp_number' => $request->input('order_whatsapp_number'),
        ];

        if ($request->file('frontend_empty_image')) {
            $input['frontend_empty_image'] = 'public/' . $this->storeImageAsWebp($request->file('frontend_empty_image'), 'uploads/settings');
        }

        $settings->update($input);

        Toastr::success('Success', 'Appearance settings updated successfully');
        return redirect()->back();
    }

    public function seo(): \Illuminate\View\View
    {
        $settings = $this->getSettings();
        return view('backEnd.website-setup.seo', compact('settings'));
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'General settings not found. Please create General Settings first.');
            return redirect()->back();
        }

        $seoMeta = (array) $request->input('seo_meta', []);
        $seoImage = $request->file('seo_meta.meta_image');
        if ($seoImage) {
            $relativePath = $this->storeImageAsWebp($seoImage, 'uploads/seo');
            $seoMeta['meta_image'] = $relativePath;
        }

        $settings->updateSeoMeta($seoMeta);

        Toastr::success('Success', 'SEO settings updated successfully');
        return redirect()->back();
    }
}
