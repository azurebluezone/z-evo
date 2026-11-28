<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One marketing spend row: a channel, a day, and the money that left.
 *
 * The table already existed (report-column-map Wave 2) and had 0 rows; this
 * model only gives the entry screen a typed surface. Nothing is copied or
 * cached anywhere: ROAS and CPA are aggregates over this table, so a saved row
 * is visible on the next report load.
 */
class MarketingSpend extends Model
{
    /**
     * Channels the entry form offers. A free-text channel would silently
     * split the same spend across "Facebook", "facebook" and "fb", which is
     * exactly what breaks a ROAS figure later.
     */
    public const CHANNELS = [
        'facebook',
        'instagram',
        'google',
        'tiktok',
        'youtube',
        'email',
        'sms',
        'influencer',
        'offline',
        'other',
    ];

    protected $fillable = [
        'channel',
        'campaign_name',
        'spent',
        'impressions',
        'clicks',
        'spent_at',
        'source_reference',
        'notes',
    ];

    protected $casts = [
        'spent' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'spent_at' => 'date',
    ];

    /**
     * Filter by spend day, inclusive on both ends. Null means "no bound".
     */
    public function scopeBetweenDays(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('spent_at', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('spent_at', '<=', $to);
        }

        return $query;
    }
}
