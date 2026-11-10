<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CookiePage;
use App\Support\Consent;

/**
 * The cookie page. Public, theme-aware (ThemeViewFinder swaps in the fm view when
 * the modern template is active), and it reads the same consent cookie the
 * banner writes so the current choice is shown as selected.
 *
 * The editable copy comes from CookiePage; the retention sentence does not. It
 * is printed by each view from Consent::RETENTION_NOTICE.
 */
class ConsentController extends Controller
{
    public function show()
    {
        $consent = Consent::state();
        $cookiePage = CookiePage::current();

        return view('frontend.pages.privacy-cookies', compact('consent', 'cookiePage'));
    }
}
