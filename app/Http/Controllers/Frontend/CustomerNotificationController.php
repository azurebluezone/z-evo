<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Toastr;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $customer = auth()->guard('customer')->user();

        $notifications = $customer->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $unreadCount = $customer->unreadNotifications()->count();

        return view('frontend.layouts.customer.notifications', compact('notifications', 'unreadCount'));
    }

    public function read(Request $request, $id)
    {
        $customer = auth()->guard('customer')->user();

        /** @var DatabaseNotification $notification */
        $notification = $customer->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        Toastr::success('Notification marked as read', 'Success!');
        return redirect()->back();
    }

    public function readAll(Request $request)
    {
        $customer = auth()->guard('customer')->user();

        $customer->unreadNotifications()->update(['read_at' => now()]);

        Toastr::success('All notifications marked as read', 'Success!');
        return redirect()->back();
    }
}
