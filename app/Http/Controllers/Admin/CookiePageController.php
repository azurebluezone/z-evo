<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CookiePage;
use App\Support\Consent;
use Illuminate\Http\Request;
use Toastr;

/**
 * The admin screen for the public cookie page.
 *
 * Editable: title, intro and the rich-text body.
 * Locked: Consent::RETENTION_NOTICE, rendered by the view from code. There is
 * no request field for it, so a crafted POST cannot overwrite the retention
 * truth either.
 */
class CookiePageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page-list|page-edit', ['only' => ['edit']]);
        $this->middleware('permission:page-edit', ['only' => ['update']]);
    }

    public function edit()
    {
        $cookiePage = CookiePage::forEditing();
        $retentionNotice = Consent::RETENTION_NOTICE;

        return view('backEnd.cookie-page.edit', compact('cookiePage', 'retentionNotice'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'intro' => 'nullable|string|max:1000',
            'body' => 'nullable|string',
        ]);

        $cookiePage = CookiePage::forEditing();
        $cookiePage->fill([
            'title' => $validated['title'],
            'intro' => $validated['intro'] ?? '',
            'body' => $validated['body'] ?? '',
            'updated_by' => $request->user()?->id,
        ])->save();

        Toastr::success('Cookie page updated', 'Success');

        return redirect()->route('cookie-page.edit');
    }
}
