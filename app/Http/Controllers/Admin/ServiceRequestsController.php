<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\NotificationTemplate;
use App\Models\ServiceRequest;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $previousStatus = (string) $requestItem->status;

        $requestItem->update($validated);

        if ((string) $requestItem->status !== $previousStatus) {
            $this->notifyStatusChange($requestItem);
        }

        Toastr::success('Service request updated', 'Success!');
        return redirect()->route('admin.user-submissions.service-requests.index');
    }

    public function updateStatus(Request $request, $id)
    {
        $requestItem = ServiceRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved'],
        ]);

        $previousStatus = (string) $requestItem->status;

        $requestItem->update($validated);

        if ((string) $requestItem->status !== $previousStatus) {
            $this->notifyStatusChange($requestItem);
        }

        Toastr::success('Status updated', 'Success!');
        return redirect()->back();
    }

    /**
     * Send the approved/pending customer template when the status changed.
     * Only accounts matched by phone can receive it; the bulk Notify screen
     * remains the fallback for everyone else.
     */
    private function notifyStatusChange(ServiceRequest $requestItem): void
    {
        $eventKey = match ((string) $requestItem->status) {
            'approved' => 'support.request.approved',
            'pending' => 'support.request.pending',
            default => null,
        };

        if ($eventKey === null) {
            return;
        }

        $customer = Customer::query()->where('phone', $requestItem->phone)->first();

        if (! $customer) {
            return;
        }

        try {
            $variables = array_merge(NotificationVariables::base(), [
                'customer_name' => (string) ($customer->name ?: trim($requestItem->first_name . ' ' . $requestItem->last_name)),
                'customer_phone' => (string) $requestItem->phone,
                'service_type' => (string) $requestItem->service_type,
                'support_note' => '',
            ]);

            app(NotificationDispatcher::class)->send($eventKey, $variables, [$customer]);
        } catch (\Throwable $e) {
            Log::warning('Service request status notification failed', [
                'service_request_id' => $requestItem->id,
                'status' => $requestItem->status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        $requestItem = ServiceRequest::findOrFail($id);
        $requestItem->delete();

        Toastr::success('Service request deleted', 'Success!');
        return redirect()->back();
    }
}
