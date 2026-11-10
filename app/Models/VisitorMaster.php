<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorMaster extends Model
{
    use HasFactory;

    protected $table = 'visitors_master';

    protected $guarded = [];

    /**
     * `last_seen_at` is a custom timestamp, not one of Eloquent's two magic
     * columns. Without this cast the analytics page receives a raw string and
     * diffForHumans()/format() fail on it.
     */
    protected $casts = [
        'last_seen_at' => 'datetime',
        'status' => 'integer',
        'pageview_count' => 'integer',
        'event_count' => 'integer',
        'browser_event_count' => 'integer',
        'server_event_count' => 'integer',
    ];
}
