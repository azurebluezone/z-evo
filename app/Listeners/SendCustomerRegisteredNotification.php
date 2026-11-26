<?php

namespace App\Listeners;

use App\Events\CustomerRegistered;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;

class SendCustomerRegisteredNotification
{
    public function handle(CustomerRegistered $event): void
    {
        try {
            $customer = $event->customer;
            $variables = NotificationVariables::forCustomer($customer);
            $dispatcher = app(NotificationDispatcher::class);

            $dispatcher->send('customer.welcome', $variables, [$customer]);
            $dispatcher->send('admin.customer.registered', $variables, User::query()->get());
        } catch (\Throwable $e) {
            Log::warning('customer.welcome notification failed', [
                'customer_id' => $event->customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
