<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;

class SendPaymentFailedNotification
{
    public function handle(PaymentFailed $event): void
    {
        try {
            $customer = $event->order->customer;

            if (! $customer) {
                return;
            }

            $variables = NotificationVariables::forOrder($event->order, [
                'payment_status' => 'failed',
            ]);

            app(NotificationDispatcher::class)->send('payment.failed', $variables, [$customer]);
        } catch (\Throwable $e) {
            Log::warning('payment.failed notification failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
