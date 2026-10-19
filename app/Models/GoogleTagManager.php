<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleTagManager extends Model
{
    use HasFactory;
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `GoogleTagManager` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'code',
        'status',
    ];
}
