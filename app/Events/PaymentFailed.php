<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A gateway payment attempt failed. The payment row stays `pending` because
 * the customer may retry, so this is separate from PaymentStatusChanged.
 */
class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public Order $order,
        public Payment $payment,
        public ?string $reason = null
    ) {}
}
