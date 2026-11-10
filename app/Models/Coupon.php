<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

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

    /**
     * Sum only the cart lines this coupon may discount. A coupon with attached
     * products is restricted to those products; one with no attachments applies
     * to the whole cart.
     *
     * Campaign lines follow the campaign's own switch: they are skipped unless
     * campaigns.allow_coupon is on, so "no coupon on an offer" is the default.
     * The switch is read from the database at apply-time and again at
     * order-time, not from the cart, so a stale cart cannot opt itself in.
     *
     * A campaign line's price is already the campaign price, so an allowed
     * campaign line is discounted on the offer price, never on the regular one.
     *
     * Apply-time and order-time both call this, so a product-restricted coupon
     * cannot show one discount in the cart and record a larger one on the order.
     *
     * @param  iterable<int, object>  $items  Cart items with id, price, qty and options.campaign_id
     */
    public function eligibleSubtotalForItems(iterable $items): float
    {
        if (!is_array($items)) {
            $items = iterator_to_array($items);
        }

        $allowedIds = null;

        if ($this->isRestrictedToProducts()) {
            $allowedIds = array_map('intval', $this->products()->pluck('products.id')->all());
        }

        $campaignIds = [];
        foreach ($items as $item) {
            $campaignId = (int) data_get($item->options, 'campaign_id');
            if ($campaignId > 0) {
                $campaignIds[$campaignId] = true;
            }
        }

        $couponCampaigns = [];
        if (!empty($campaignIds)) {
            $couponCampaigns = DB::table('campaigns')
                ->whereIn('id', array_keys($campaignIds))
                ->where('allow_coupon', 1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            if ($allowedIds !== null && !in_array((int) $item->id, $allowedIds, true)) {
                continue;
            }

            $campaignId = (int) data_get($item->options, 'campaign_id');
            if ($campaignId > 0 && !in_array($campaignId, $couponCampaigns, true)) {
                continue;
            }

            $subtotal += ((float) $item->price) * ((int) $item->qty);
        }

        return $subtotal;
    }

    public function calculateDiscountForItems(iterable $items): float
    {
        return $this->calculateDiscount($this->eligibleSubtotalForItems($items));
    }
}
