<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\GenericDbNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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

        // Notify admin about new service request
        try {
            Notification::send(User::all(), new GenericDbNotification(
                title: 'New Service Request',
                body: "Service request from {$validated['first_name']} {$validated['last_name']} ({$validated['phone']}): {$validated['service_type']}",
                meta: ['type' => 'service_request', 'phone' => $validated['phone']]
            ));
        } catch (\Throwable $e) {
            // no-op
        }

        Toastr::success('Your service request has been submitted', 'Success!');
        return redirect()->back()->with('service_request_submitted', 'Thank you! Your service request has been submitted successfully.');
    }
}
