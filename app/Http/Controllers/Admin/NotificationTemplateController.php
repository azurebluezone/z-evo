<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Toastr;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::query()->orderByDesc('id')->paginate(10);
        return view('backEnd.notifications.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('backEnd.notifications.templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        NotificationTemplate::create($validated);

        Toastr::success('Template created', 'Success!');
        return redirect()->route('admin.notification-templates.index');
    }

    public function edit($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return view('backEnd.notifications.templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $template->update($validated);

        Toastr::success('Template updated', 'Success!');
        return redirect()->route('admin.notification-templates.index');
    }

    public function toggle($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->is_active = ! (bool) $template->is_active;
        $template->save();

        Toastr::success('Status updated', 'Success!');
        return redirect()->back();
    }

    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        Toastr::success('Template deleted', 'Success!');
        return redirect()->back();
    }
}
