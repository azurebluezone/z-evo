<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Product;

/**
 * The single source of truth for a campaign price.
 *
 * Rules:
 *  - The product catalog price (and a variant's effective price) is the
 *    regular price. This service never writes products.new_price.
 *  - A campaign must be enabled and must belong to the product.
 *  - campaign_discount_percent takes precedence over campaign_price; this lets
 *    variant products use one percentage across different variant prices.
 *  - A campaign never charges zero, a negative amount, or an amount greater
 *    than or equal to the regular price. In those cases the regular price wins
 *    and a reason is returned so the caller can log or warn.
 */
class CampaignPricing
{
    /**
     * @param  object|null  $variant  Eloquent model or stdClass with price_override/discount_price
     * @return array{price: float, regular: float, source: string, reason: string|null}
     */
    public function resolve(Product $product, ?object $variant = null, ?Campaign $campaign = null): array
    {
        $regular = $this->regularPrice($product, $variant);

        if (!$campaign || !$campaign->campaign_price_enabled) {
            return $this->result($regular, $regular, 'product', 'campaign_disabled');
        }

        if ($campaign->product_id !== null && (int) $campaign->product_id !== (int) $product->id) {
            return $this->result($regular, $regular, 'product', 'product_mismatch');
        }

        $percent = $campaign->campaign_discount_percent;

        if ($percent !== null && $percent !== '') {
            $percent = (float) $percent;

            if ($percent <= 0 || $percent >= 100) {
                return $this->result($regular, $regular, 'product', 'invalid_percent');
            }

            $price = round($regular * (1 - ($percent / 100)), 2);
        } else {
            $price = (float) ($campaign->campaign_price ?? 0);
        }

        if ($price <= 0 || $regular <= 0) {
            return $this->result($regular, $regular, 'product', 'invalid_price');
        }

        if ($price >= $regular) {
            return $this->result($regular, $regular, 'product', 'not_a_discount');
        }

        return $this->result($price, $regular, 'campaign', null);
    }

    /**
     * True when the campaign is enabled and now is inside its start/end window.
     * A missing bound is open-ended, matching ShoppingController@landingOrder.
     */
    public function isWindowOpen(Campaign $campaign, ?\DateTimeInterface $now = null): bool
    {
        $moment = $now ? \Illuminate\Support\Carbon::instance($now) : now();

        if (!$campaign->status) {
            return false;
        }

        if ($campaign->start_date && $campaign->start_date->copy()->startOfDay()->gt($moment)) {
            return false;
        }

        if ($campaign->end_date && $campaign->end_date->copy()->endOfDay()->lt($moment)) {
            return false;
        }

        return true;
    }

    private function regularPrice(Product $product, ?object $variant): float
    {
        $regular = (float) ($product->new_price ?? 0);

        if (!$variant) {
            return $regular;
        }

        $override = $variant->price_override ?? null;
        if ($override !== null && $override !== '' && (float) $override > 0) {
            $regular = (float) $override;
        }

        $discount = $variant->discount_price ?? null;
        if ($discount !== null && $discount !== '' && (float) $discount > 0 && (float) $discount < $regular) {
            $regular = (float) $discount;
        }

        return $regular;
    }

    /**
     * @return array{price: float, regular: float, source: string, reason: string|null}
     */
    private function result(float $price, float $regular, string $source, ?string $reason): array
    {
        return [
            'price' => round($price, 2),
            'regular' => round($regular, 2),
            'source' => $source,
            'reason' => $reason,
        ];
    }
}
