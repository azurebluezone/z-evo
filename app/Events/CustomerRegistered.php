<?php

namespace App\Events;

use App\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A customer created an account through the storefront registration form.
 */
class CustomerRegistered
{
    use Dispatchable;

    public function __construct(public Customer $customer) {}
}
