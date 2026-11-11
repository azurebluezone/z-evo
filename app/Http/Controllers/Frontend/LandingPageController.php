<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Template;

class LandingPageController extends Controller
{
    public function show($slug)
    {
        $landingPage = LandingPage::with('template')
            ->where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        // Get template view name
        $templateView = 'templates.' . $landingPage->template->view_name;
        
        // Check if template view exists, otherwise stop with a 404.
        if (!view()->exists($templateView)) {
            abort(404, 'Template view not found: ' . $templateView);
        }

        $tpl = \App\Support\LandingTemplateVars::make($landingPage, $landingPage->template, null);

        return view($templateView, compact('landingPage', 'tpl'));
    }

    public function index()
    {
        $landingPages = LandingPage::with('template')
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('frontend.landing-pages.index', compact('landingPages'));
    }
}
