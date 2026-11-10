<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One resolved IP address.
 *
 * The table existed since 2026_10_13 but this model did not, so every read and
 * write in GeoIpResolver threw "Class not found" and was swallowed by its
 * try/catch. The resolver worked, but nothing was ever cached: every pageview
 * from an unresolved IP called the geo providers again, and ip_geo_cache stayed
 * empty. The model is what turns the cache on.
 */
class IpGeoCache extends Model
{
    protected $table = 'ip_geo_cache';

    protected $guarded = [];

    protected $casts = [
        'last_lookup_at' => 'datetime',
    ];
}
