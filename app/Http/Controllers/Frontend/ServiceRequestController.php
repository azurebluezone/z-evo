<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $products = Product::where('status', 1)
            ->with('image')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('frontend.pages.service', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'service_type' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ServiceRequest::create($validated + ['status' => 'pending']);

        // Event-keyed templates: every admin gets the internal copy; the
        // customer copy only sends when the submitted phone belongs to an
        // account. A notification failure must never lose the request.
        try {
            $name = trim($validated['first_name'] . ' ' . $validated['last_name']);

            $variables = array_merge(NotificationVariables::base(), [
                'customer_name' => $name,
                'customer_phone' => $validated['phone'],
                'service_type' => $validated['service_type'],
            ]);

            $dispatcher = app(NotificationDispatcher::class);
            $dispatcher->send('admin.service.request.received', $variables, User::query()->get());

            $customer = Customer::query()->where('phone', $validated['phone'])->first();
            if ($customer) {
                $dispatcher->send('support.request.received', $variables, [$customer]);
            }
        } catch (\Throwable $e) {
            Log::warning('Service request notification failed', [
                'phone' => $validated['phone'],
                'error' => $e->getMessage(),
            ]);
        }

        Toastr::success('Your service request has been submitted', 'Success!');
        return redirect()->back()->with('service_request_submitted', 'Thank you! Your service request has been submitted successfully.');
    }
}
