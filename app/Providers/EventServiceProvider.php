<?php

namespace App\Providers;

use App\Events\CustomerRegistered;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Events\PaymentFailed;
use App\Events\PaymentStatusChanged;
use App\Events\RefundCompleted;
use App\Listeners\SendCustomerRegisteredNotification;
use App\Listeners\SendOrderPlacedNotifications;
use App\Listeners\SendOrderStatusNotification;
use App\Listeners\SendPaymentFailedNotification;
use App\Listeners\SendPaymentStatusNotification;
use App\Listeners\SendRefundCompletedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // E-commerce notification system. Each listener is wrapped in its own
        // try/catch, so a template or SMTP problem never fails the request.
        OrderPlaced::class => [
            SendOrderPlacedNotifications::class,
        ],
        OrderStatusChanged::class => [
            SendOrderStatusNotification::class,
        ],
        PaymentStatusChanged::class => [
            SendPaymentStatusNotification::class,
        ],
        PaymentFailed::class => [
            SendPaymentFailedNotification::class,
        ],
        RefundCompleted::class => [
            SendRefundCompletedNotification::class,
        ],
        CustomerRegistered::class => [
            SendCustomerRegisteredNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
