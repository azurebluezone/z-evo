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
        
        // Check if template view exists, otherwise use default
        if (!view()->exists($templateView)) {
            $templateView = 'templates.default';
        }

        return view($templateView, compact('landingPage'));
    }

    public function index()
    {
        $landingPages = LandingPage::with('template')
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('frontEnd.landing-pages.index', compact('landingPages'));
    }
}
