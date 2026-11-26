<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An admin marked a return as refunded.
 */
class RefundCompleted
{
    use Dispatchable;

    public function __construct(
        public Order $order,
        public float $amount = 0.0,
        public ?string $note = null
    ) {}
}
