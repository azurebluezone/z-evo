<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FrontendTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Toastr;

/**
 * Admin screen that decides which front-end template set is live.
 *
 * The selector is deliberately the only control: switching the design must not
 * require touching code, routes or queries.
 */
class FrontendThemeController extends Controller
{
    public function edit(): View
    {
        return view('backEnd.website-setup.frontend-theme', [
            'stored' => FrontendTheme::stored(),
            'preview' => FrontendTheme::previewing(),
            'catalog' => FrontendTheme::catalog(),
            'previewUrls' => [
                FrontendTheme::CLASSIC => FrontendTheme::previewRoute(FrontendTheme::CLASSIC),
                FrontendTheme::MODERN => FrontendTheme::previewRoute(FrontendTheme::MODERN),
            ],
            'clearUrl' => FrontendTheme::clearRoute(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $key = (string) $request->input('frontend_theme');

        if (! FrontendTheme::save($key)) {
            Toastr::error('Error', 'Choose one of the listed templates.');
            return redirect()->back();
        }

        Toastr::success('Success', 'Front-end template updated.');
        return redirect()->back();
    }

    /**
     * Handy shortcut for staff: saves nothing, just turns the temporary
     * preview on for this browser session.
     */
    public function preview(Request $request): RedirectResponse
    {
        $key = (string) $request->input('theme');

        if (! array_key_exists($key, FrontendTheme::catalog())) {
            return redirect()->route('admin.frontend-theme.edit');
        }

        FrontendTheme::startPreview($key);

        return redirect()->route('admin.frontend-theme.edit');
    }
}
