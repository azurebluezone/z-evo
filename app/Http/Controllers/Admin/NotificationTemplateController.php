<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $validated = $this->validated($request);

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

        $validated = $this->validated($request, $template);

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

    /**
     * Shared create/update validation.
     *
     * `event_key` is nullable and unique (null = manual template). An empty
     * posted value must become NULL, otherwise two manual templates would
     * collide on the unique index with ''. Channels default to the in-app
     * inbox, so an admin can never save a template that sends nowhere.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?NotificationTemplate $template = null): array
    {
        $eventKey = $request->filled('event_key')
            ? trim((string) $request->input('event_key'))
            : null;

        $request->merge(['event_key' => $eventKey]);

        $unique = Rule::unique('notification_templates', 'event_key');

        if ($template) {
            $unique->ignore($template->id);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_key' => ['nullable', 'string', 'max:100', $unique],
            'description' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'in:database,mail'],
            'variables' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable'],
        ]);

        $validated['event_key'] = $eventKey;
        $validated['description'] = $request->filled('description')
            ? trim((string) $request->input('description'))
            : null;
        $validated['channels'] = array_values(array_unique((array) $request->input('channels', [])))
            ?: ['database'];
        $validated['variables'] = self::parseVariables((string) $request->input('variables', ''));
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        return $validated;
    }

    /**
     * "customer_name, {order_number}" -> ["customer_name", "order_number"].
     *
     * @return list<string>
     */
    private static function parseVariables(string $value): array
    {
        $variables = [];

        foreach (explode(',', $value) as $variable) {
            $variable = trim(trim($variable), '{}');

            if ($variable !== '' && preg_match('/^[A-Za-z0-9_]+$/', $variable)) {
                $variables[] = $variable;
            }
        }

        return array_values(array_unique($variables));
    }
}
