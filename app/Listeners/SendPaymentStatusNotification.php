<?php

namespace App\Listeners;

use App\Events\PaymentStatusChanged;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;

class SendPaymentStatusNotification
{
    public function handle(PaymentStatusChanged $event): void
    {
        try {
            if ($event->to !== 'paid') {
                return;
            }

            $customer = $event->order->customer;

            if (! $customer) {
                return;
            }

            $variables = NotificationVariables::forOrder($event->order, [
                'payment_status' => $event->to,
            ]);

            app(NotificationDispatcher::class)->send('payment.paid', $variables, [$customer]);
        } catch (\Throwable $e) {
            Log::warning('payment.paid notification failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
