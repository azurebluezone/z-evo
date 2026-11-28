<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One cart lifecycle, with its state stored instead of inferred.
 *
 * The shopping cart itself lives in the session (hardevine/shoppingcart); this
 * row is the durable record of it. Statuses are the four values in the
 * migration comment: open, abandoned, recovered, converted.
 */
class AbandonedCart extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_RECOVERED = 'recovered';
    public const STATUS_CONVERTED = 'converted';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ABANDONED,
        self::STATUS_RECOVERED,
        self::STATUS_CONVERTED,
    ];

    protected $fillable = [
        'cart_key',
        'customer_id',
        'email',
        'phone',
        'item_count',
        'subtotal',
        'status',
        'recovery_token',
        'first_seen_at',
        'last_activity_at',
        'abandoned_at',
        'recovered_at',
        'converted_at',
        'order_id',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'item_count' => 'integer',
        'subtotal' => 'decimal:2',
        'order_id' => 'integer',
        'first_seen_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'recovered_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function isRecoverable(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_ABANDONED], true);
    }
}
