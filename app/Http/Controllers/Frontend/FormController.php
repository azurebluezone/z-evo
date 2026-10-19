<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\GenericDbNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class FormController extends Controller
{
    public function newsletterSubscribe(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower($validated['email'])],
            [
                'ip' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Subscribed successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Subscribed successfully');
    }

    public function contactSubmit(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        // Notify admin about new contact message
        try {
            Notification::send(User::all(), new GenericDbNotification(
                title: 'New Contact Message',
                body: "New message from {$validated['name']} ({$validated['email']}): {$validated['subject']}",
                meta: ['type' => 'contact_message', 'email' => $validated['email']]
            ));
        } catch (\Throwable $e) {
            // no-op
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Message sent successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Message sent successfully');
    }
}
