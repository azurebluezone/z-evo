<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The single primary attribution row of an order.
 *
 * Written once, at order creation, by OrderAttributionRecorder. The unique
 * index on order_id (2026_11_28_000002) is what makes "one primary" a schema
 * fact rather than a convention.
 */
class OrderAttribution extends Model
{
    public const MODEL_FIRST_TOUCH = 'first_touch';

    protected $fillable = [
        'order_id',
        'source',
        'medium',
        'campaign',
        'visitor_id',
        'model',
        'attributed_at',
    ];

    protected $casts = [
        'attributed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
