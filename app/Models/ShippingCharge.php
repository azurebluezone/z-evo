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
     * The delivery rates the business has fixed.
     *
     * These three rows are policy, not catalog data: checkout totals, the profit
     * report and the free-shipping value all assume them. Letting an admin edit
     * or delete one silently changes every historical comparison, so the admin
     * screens refuse to touch them. New zones can still be added freely.
     *
     * `ঢাকার বাইরে` / `ঢাকার বাইরে।` are the legacy spellings of the canonical
     * `ঢাকার বাহিরে` row; they stay locked too so a duplicate cannot be created
     * through the admin form.
     */
    public const READ_ONLY_NAMES = [
        'Standard delivery',
        'ঢাকার ভিতরে',
        'ঢাকার বাহিরে',
        'ঢাকার বাইরে',
    ];

    /** Trim, drop the Bengali full stop, collapse whitespace, lower-case. */
    public static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = str_replace(['।', '.'], '', $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';

        return mb_strtolower($name);
    }

    public static function isReadOnlyName(?string $name): bool
    {
        $needle = static::normalizeName($name);

        if ($needle === '') {
            return false;
        }

        foreach (self::READ_ONLY_NAMES as $locked) {
            if (static::normalizeName($locked) === $needle) {
                return true;
            }
        }

        return false;
    }

    public function isReadOnly(): bool
    {
        return static::isReadOnlyName($this->name);
    }

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
