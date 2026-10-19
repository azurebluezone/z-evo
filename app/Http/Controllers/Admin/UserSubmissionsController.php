<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Toastr;

class UserSubmissionsController extends Controller
{
    public function newsletter(Request $request)
    {
        $subscribers = NewsletterSubscriber::query()
            ->orderByDesc('id')
            ->paginate(10);

        $templates = NotificationTemplate::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('backEnd.user-submissions.newsletter', compact('subscribers', 'templates'));
    }

    public function contacts(Request $request)
    {
        $messages = ContactMessage::query()
            ->orderByDesc('id')
            ->paginate(10);

        $templates = NotificationTemplate::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('backEnd.user-submissions.contacts', compact('messages', 'templates'));
    }

    public function destroyNewsletter($id)
    {
        $item = NewsletterSubscriber::findOrFail($id);
        $item->delete();

        Toastr::success('Subscriber deleted', 'Success!');
        return redirect()->back();
    }

    public function destroyContact($id)
    {
        $item = ContactMessage::findOrFail($id);
        $item->delete();

        Toastr::success('Message deleted', 'Success!');
        return redirect()->back();
    }
}
