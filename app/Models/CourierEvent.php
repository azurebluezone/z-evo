<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * One row per courier webhook callback, in arrival order.
 *
 * courier_shipments keeps only the latest status; this keeps the whole history
 * so an admin can see a parcel go pending -> in_transit -> delivered -> returned
 * rather than just the final word.
 */
class CourierEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function shipment()
    {
        return $this->belongsTo(CourierShipment::class, 'shipment_id');
    }
}
