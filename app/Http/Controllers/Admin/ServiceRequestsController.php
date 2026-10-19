<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Toastr;

class ServiceRequestsController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');

        $requests = ServiceRequest::query()
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $templates = NotificationTemplate::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('backEnd.user-submissions.service-requests.index', compact('requests', 'status', 'templates'));
    }

    public function show($id)
    {
        $requestItem = ServiceRequest::findOrFail($id);
        return view('backEnd.user-submissions.service-requests.show', compact('requestItem'));
    }

    public function edit($id)
    {
        $requestItem = ServiceRequest::findOrFail($id);
        return view('backEnd.user-submissions.service-requests.edit', compact('requestItem'));
    }

    public function update(Request $request, $id)
    {
        $requestItem = ServiceRequest::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'service_type' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:pending,approved'],
        ]);

        $requestItem->update($validated);

        Toastr::success('Service request updated', 'Success!');
        return redirect()->route('admin.user-submissions.service-requests.index');
    }

    public function updateStatus(Request $request, $id)
    {
        $requestItem = ServiceRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved'],
        ]);

        $requestItem->update($validated);

        Toastr::success('Status updated', 'Success!');
        return redirect()->back();
    }

    public function destroy($id)
    {
        $requestItem = ServiceRequest::findOrFail($id);
        $requestItem->delete();

        Toastr::success('Service request deleted', 'Success!');
        return redirect()->back();
    }
}
