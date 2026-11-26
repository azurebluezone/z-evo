<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An order moved between two statuses. Fired once the transaction committed.
 */
class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Order $order,
        public string $from,
        public string $to,
        public ?string $note = null
    ) {}
}
