<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A payment record changed status (the gateway callback is the usual source).
 */
class PaymentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Order $order,
        public Payment $payment,
        public string $from,
        public string $to
    ) {}
}
