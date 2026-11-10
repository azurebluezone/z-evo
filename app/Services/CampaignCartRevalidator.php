<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Product;
use Cart;
use Illuminate\Support\Facades\DB;

/**
 * Reprice campaign cart lines from the live campaign and product state.
 *
 * A cart can sit in a session for hours. The price it carries was valid when
 * the item was added; by checkout the campaign may have ended, been disabled,
 * changed price, or lost its product. This service compares every campaign
 * line against CampaignPricing (the same resolver used at add-to-cart time)
 * and rewrites the line when anything differs.
 *
 * The caller must treat a non-empty result as "do not charge this cart yet":
 * the customer has to see the refreshed total and submit again. Silently
 * charging either the old or the new price would break the price the customer
 * agreed to.
 */
class CampaignCartRevalidator
{
    public function __construct(private CampaignPricing $pricing)
    {
    }

    /**
     * @return array<int, string> Product names whose line was rewritten.
     */
    public function revalidate(): array
    {
        $items = Cart::instance('shopping')->content();

        $campaignIds = [];
        foreach ($items as $item) {
            $campaignId = (int) data_get($item->options, 'campaign_id');
            if ($campaignId > 0) {
                $campaignIds[$campaignId] = true;
            }
        }

        if (empty($campaignIds)) {
            return [];
        }

        $campaigns = Campaign::whereIn('id', array_keys($campaignIds))->get()->keyBy('id');

        $productIds = $items->map(fn ($item) => (int) $item->id)->unique()->values()->all();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $variantIds = $items
            ->map(fn ($item) => (int) data_get($item->options, 'variant_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $variants = empty($variantIds)
            ? collect()
            : DB::table('product_variants')->whereIn('id', $variantIds)->get()->keyBy('id');

        $changed = [];

        foreach ($items as $item) {
            $campaignId = (int) data_get($item->options, 'campaign_id');
            if ($campaignId <= 0) {
                continue;
            }

            $product = $products->get((int) $item->id);
            if (!$product) {
                // A deleted product is a separate failure; order_save already
                // skips inventory for it, so do not invent a price here.
                continue;
            }

            $campaign = $campaigns->get($campaignId);
            $variant = $variants->get((int) data_get($item->options, 'variant_id'));

            if ($variant && (int) $variant->product_id !== (int) $product->id) {
                $variant = null;
            }

            $campaignApplies = $campaign
                && $this->pricing->isWindowOpen($campaign)
                && ($campaign->product_id === null || (int) $campaign->product_id === (int) $product->id);

            $resolved = $this->pricing->resolve($product, $variant, $campaignApplies ? $campaign : null);
            $campaignStillApplies = $resolved['source'] === 'campaign' && $campaignApplies;

            $priceMatches = round((float) $item->price, 2) === round((float) $resolved['price'], 2)
                && round((float) data_get($item->options, 'regular_price'), 2) === round((float) $resolved['regular'], 2);

            $metaMatches = $campaignStillApplies
                && (int) data_get($item->options, 'campaign_id') === (int) $campaign->id
                && data_get($item->options, 'campaign_slug') === $campaign->slug
                && data_get($item->options, 'price_source') === $resolved['source'];

            if ($priceMatches && $metaMatches) {
                continue;
            }

            $options = $item->options->toArray();
            $options['campaign_id'] = $campaignStillApplies ? (int) $campaign->id : null;
            $options['campaign_slug'] = $campaignStillApplies ? $campaign->slug : null;
            $options['campaign_price'] = $campaignStillApplies ? $resolved['price'] : null;
            $options['regular_price'] = $resolved['regular'];
            $options['price_source'] = $resolved['source'];

            Cart::instance('shopping')->update($item->rowId, [
                'price' => $resolved['price'],
                'options' => $options,
            ]);

            $changed[$item->name] = true;
        }

        return array_keys($changed);
    }
}
