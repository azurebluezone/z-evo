<?php

namespace App\Models;

use App\Services\CourierStatusNormalizer;
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

    /**
     * Keep status_normalized in step with current_status for every write path
     * (API senders, webhook, admin edits). A raw status change is the only
     * trigger: an explicit normalized value is left alone when the raw status
     * did not move.
     */
    protected static function booted(): void
    {
        static::saving(function (CourierShipment $shipment): void {
            if ($shipment->isDirty('current_status') || blank($shipment->status_normalized)) {
                $shipment->status_normalized = CourierStatusNormalizer::normalize($shipment->current_status);
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
