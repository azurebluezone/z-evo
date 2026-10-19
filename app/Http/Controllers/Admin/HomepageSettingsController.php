<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Toastr;

class HomepageSettingsController extends Controller
{
    public function edit(): \Illuminate\View\View|RedirectResponse
    {
        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'General settings not found. Please create General Settings first.');
            return redirect()->back();
        }

        return view('backEnd.homepage.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = GeneralSetting::first();
        if (!$settings) {
            Toastr::error('Error', 'General settings not found. Please create General Settings first.');
            return redirect()->back();
        }

        $this->validate($request, [
            'home_hero_title' => 'nullable|string|max:255',
            'home_recommended_title' => 'nullable|string|max:255',
            'home_flash_sale_title' => 'nullable|string|max:255',
            'home_category_title' => 'nullable|string|max:255',
            'home_footer_title' => 'nullable|string|max:255',
            'home_promo_title' => 'nullable|string|max:255',
            'home_featured_title' => 'nullable|string|max:255',
            'home_collections_title' => 'nullable|string|max:255',
            'home_brands_title' => 'nullable|string|max:255',
            'home_service_title' => 'nullable|string|max:255',
            'home_best_selling_title' => 'nullable|string|max:255',
            'home_discount_products_title' => 'nullable|string|max:255',
        ]);

        $settings->update([
            'home_hero_section' => $request->boolean('home_hero_section') ? 1 : 0,
            'home_hero_title' => $request->input('home_hero_title'),

            'home_flash_sale_section' => $request->boolean('home_flash_sale_section') ? 1 : 0,
            'home_flash_sale_title' => $request->input('home_flash_sale_title'),

            'home_recommended_section' => $request->boolean('home_recommended_section') ? 1 : 0,
            'home_recommended_title' => $request->input('home_recommended_title'),

            'home_category_section' => $request->boolean('home_category_section') ? 1 : 0,
            'home_category_title' => $request->input('home_category_title'),

            'home_footer_section' => $request->boolean('home_footer_section') ? 1 : 0,
            'home_footer_title' => $request->input('home_footer_title'),

            'home_promo_section' => $request->boolean('home_promo_section') ? 1 : 0,
            'home_promo_title' => $request->input('home_promo_title'),

            'home_featured_section' => $request->boolean('home_featured_section') ? 1 : 0,
            'home_featured_title' => $request->input('home_featured_title'),

            'home_best_selling_section' => $request->boolean('home_best_selling_section') ? 1 : 0,
            'home_best_selling_title' => $request->input('home_best_selling_title'),

            'home_discount_products_section' => $request->boolean('home_discount_products_section') ? 1 : 0,
            'home_discount_products_title' => $request->input('home_discount_products_title'),

            'home_collections_section' => $request->boolean('home_collections_section') ? 1 : 0,
            'home_collections_title' => $request->input('home_collections_title'),

            'home_brands_section' => $request->boolean('home_brands_section') ? 1 : 0,
            'home_brands_title' => $request->input('home_brands_title'),

            'home_service_section' => $request->boolean('home_service_section') ? 1 : 0,
            'home_service_title' => $request->input('home_service_title'),
        ]);

        Toastr::success('Success', 'Home settings updated successfully');
        return redirect()->back();
    }
}
