<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\NotificationTemplate;
use App\Models\ServiceRequest;
use App\Notifications\GenericDbNotification;
use Illuminate\Http\Request;
use Toastr;

class UserSubmissionBulkActionController extends Controller
{
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:newsletter,contacts,service_requests'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $type = $validated['type'];
        $ids = $validated['ids'];

        $deleted = 0;

        if ($type === 'newsletter') {
            $deleted = NewsletterSubscriber::query()->whereIn('id', $ids)->delete();
        } elseif ($type === 'contacts') {
            $deleted = ContactMessage::query()->whereIn('id', $ids)->delete();
        } elseif ($type === 'service_requests') {
            $deleted = ServiceRequest::query()->whereIn('id', $ids)->delete();
        }

        Toastr::success("Deleted {$deleted} item(s)", 'Success!');
        return redirect()->back();
    }

    public function bulkNotify(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:newsletter,contacts,service_requests'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'template_id' => ['required', 'integer', 'exists:notification_templates,id'],
        ]);

        $type = $validated['type'];
        $ids = $validated['ids'];
        $template = NotificationTemplate::active()->findOrFail($validated['template_id']);

        $sent = 0;
        $skipped = 0;

        if ($type === 'newsletter') {
            $items = NewsletterSubscriber::query()->whereIn('id', $ids)->get(['id', 'email']);
            foreach ($items as $item) {
                $customer = Customer::query()->where('email', $item->email)->first();
                if (!$customer) {
                    $skipped++;
                    continue;
                }
                $customer->notify(new GenericDbNotification($template->title, $template->body, [
                    'source' => 'newsletter',
                    'source_id' => $item->id,
                ]));
                $sent++;
            }
        } elseif ($type === 'contacts') {
            $items = ContactMessage::query()->whereIn('id', $ids)->get(['id', 'email']);
            foreach ($items as $item) {
                $customer = Customer::query()->where('email', $item->email)->first();
                if (!$customer) {
                    $skipped++;
                    continue;
                }
                $customer->notify(new GenericDbNotification($template->title, $template->body, [
                    'source' => 'contacts',
                    'source_id' => $item->id,
                ]));
                $sent++;
            }
        } elseif ($type === 'service_requests') {
            $items = ServiceRequest::query()->whereIn('id', $ids)->get(['id', 'phone']);
            foreach ($items as $item) {
                $customer = Customer::query()->where('phone', $item->phone)->first();
                if (!$customer) {
                    $skipped++;
                    continue;
                }
                $customer->notify(new GenericDbNotification($template->title, $template->body, [
                    'source' => 'service_requests',
                    'source_id' => $item->id,
                ]));
                $sent++;
            }
        }

        Toastr::success("Sent {$sent} notification(s). Skipped {$skipped}.", 'Success!');
        return redirect()->back();
    }
}
