<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new order exists. Fired after place() has written the order, shipping,
 * payment, lines and stock reservation.
 */
class OrderPlaced
{
    use Dispatchable;

    public function __construct(public Order $order) {}
}
