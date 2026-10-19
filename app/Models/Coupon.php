<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_amount',
        'max_discount',
        'start_at',
        'end_at',
        'usage_limit',
        'used_count',
        'status',
    ];

    protected $casts = [
        'min_amount' => 'float',
        'max_discount' => 'float',
        'value' => 'float',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'usage_limit' => 'int',
        'used_count' => 'int',
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeRunning($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products')->withTimestamps();
    }

    public function isRestrictedToProducts(): bool
    {
        return $this->products()->exists();
    }

    public function isUsageAvailable(): bool
    {
        if ($this->usage_limit === null) {
            return true;
        }

        return (int) $this->used_count < (int) $this->usage_limit;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal <= 0) return 0.0;

        if ($this->min_amount !== null && $subtotal < (float) $this->min_amount) {
            return 0.0;
        }

        $type = strtolower((string) $this->type);

        if ($type === 'percent') {
            $amount = ($subtotal * (float) $this->value) / 100.0;
        } else {
            $amount = (float) $this->value;
        }

        if ($this->max_discount !== null) {
            $amount = min($amount, (float) $this->max_discount);
        }

        $amount = max(0.0, $amount);
        $amount = min($amount, $subtotal);

        return $amount;
    }
}
