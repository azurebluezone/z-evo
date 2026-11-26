<?php

namespace App\Listeners;

use App\Events\RefundCompleted;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;

class SendRefundCompletedNotification
{
    public function handle(RefundCompleted $event): void
    {
        try {
            $customer = $event->order->customer;

            if (! $customer) {
                return;
            }

            $variables = NotificationVariables::forOrder($event->order, [
                'refund_amount' => number_format($event->amount, 2, '.', ''),
                'support_note' => (string) ($event->note ?? ''),
            ]);

            app(NotificationDispatcher::class)->send('refund.completed', $variables, [$customer]);
        } catch (\Throwable $e) {
            Log::warning('refund.completed notification failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
