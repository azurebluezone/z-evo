<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class OrderStatus extends Model
{
    use HasFactory;
    use HasSeoMeta;
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `OrderStatus` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function orders(){
        return $this->hasMany(Order::class, 'order_status')->select('id','order_status');
    }
}
