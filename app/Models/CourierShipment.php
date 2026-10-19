<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierShipment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
