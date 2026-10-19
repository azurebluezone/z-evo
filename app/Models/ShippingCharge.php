<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class ShippingCharge extends Model
{
    use HasFactory;
    use HasSeoMeta;
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `ShippingCharge` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'name',
        'amount',
        'status',
    ];
}
