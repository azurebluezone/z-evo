<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A phone number the admin has blocked from placing frontend orders.
 *
 * The normalised `phone` is the key, so 01712345678, +8801712345678 and
 * 8801712345678 are the same block. `original_phone` keeps the shape the admin
 * typed, which is only useful for display and search.
 */
class PhoneBlock extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_blocked_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Collapse the shapes a Bangladeshi phone number arrives in.
     *
     * Returns null when the value cannot be a phone number, so a half-typed
     * search never creates a block row.
     */
    public static function normalize(?string $phone): ?string
    {
        if (!is_string($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) > 11 && str_starts_with($digits, '880')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) > 11 && str_starts_with($digits, '88')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) < 6 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
