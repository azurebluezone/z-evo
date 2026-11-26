<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;

/**
 * Customer notification for every order status the shop talks about.
 *
 * A delivery also asks for a review once: transition() returns early when the
 * status did not change, so a repeated webhook cannot send it twice.
 */
class SendOrderStatusNotification
{
    private const TEMPLATES = [
        'processing' => 'order.processing',
        'shipped' => 'order.shipped',
        'delivered' => 'order.delivered',
        'cancelled' => 'order.cancelled',
        'returned' => 'order.returned',
    ];

    public function handle(OrderStatusChanged $event): void
    {
        try {
            $customer = $event->order->customer;

            if (! $customer) {
                return;
            }

            $variables = NotificationVariables::forOrder($event->order, [
                'order_status' => $event->to,
                'support_note' => (string) ($event->note ?? ''),
            ]);

            $dispatcher = app(NotificationDispatcher::class);

            $key = self::TEMPLATES[$event->to] ?? null;

            if ($key !== null) {
                $dispatcher->send($key, $variables, [$customer]);
            }

            if ($event->to === 'delivered') {
                $dispatcher->send('review.request', $variables, [$customer]);
            }
        } catch (\Throwable $e) {
            Log::warning('order status notification failed', [
                'order_id' => $event->order->id,
                'status' => $event->to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
