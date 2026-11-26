<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;

/**
 * Customer receipt + admin copy for a new order.
 *
 * Replaces the two hardcoded Notification::send() blocks that used to live in
 * the checkout and landing-order controllers, so both paths send once with the
 * admin-editable wording.
 */
class SendOrderPlacedNotifications
{
    public function handle(OrderPlaced $event): void
    {
        try {
            $order = $event->order;
            $variables = NotificationVariables::forOrder($order);
            $dispatcher = app(NotificationDispatcher::class);

            $dispatcher->send('order.placed', $variables, array_filter([$order->customer]));
            $dispatcher->send('admin.order.placed', $variables, User::query()->get());
        } catch (\Throwable $e) {
            Log::warning('order.placed notification failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
