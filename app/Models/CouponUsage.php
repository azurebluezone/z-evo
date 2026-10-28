<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One coupon use on one order. Append-only: rows are written when an order
 * is placed and never edited, because the admin coupon details page counts
 * "used today" and "used total" straight from this log.
 */
class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'order_id',
        'coupon_code',
        'source',
        'discount_amount',
    ];

    protected $casts = [
        'coupon_id' => 'int',
        'order_id' => 'int',
        'discount_amount' => 'float',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Record one use and keep coupons.used_count in step with the log.
     * Kept in one place so checkout and POS cannot drift apart.
     */
    public static function record(Coupon $coupon, ?int $orderId, float $discount, string $source = 'checkout'): self
    {
        $usage = self::create([
            'coupon_id' => $coupon->id,
            'order_id' => $orderId,
            'coupon_code' => $coupon->code,
            'source' => $source,
            'discount_amount' => $discount,
        ]);

        $coupon->increment('used_count');

        return $usage;
    }
}
