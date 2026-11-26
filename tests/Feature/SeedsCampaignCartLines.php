<?php

namespace Tests\Feature;

use Cart;

/**
 * Seeds the session cart with a priced line.
 *
 * The landing page order form places the order directly now (see
 * OrderPlacementService), so a test that needs a line INSIDE the cart - the
 * checkout revalidation and the coupon eligibility rules - puts it there
 * itself, with the same options the landing order used to write.
 */
trait SeedsCampaignCartLines
{
    /**
     * A line priced by the campaign offer.
     */
    protected function seedCampaignCartLine(
        object $product,
        object $campaign,
        int $qty = 1,
        ?float $campaignPrice = null,
        ?float $regularPrice = null
    ): void {
        $campaignPrice = $campaignPrice ?? (float) $campaign->campaign_price;
        $regularPrice = $regularPrice ?? (float) $product->new_price;

        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $campaignPrice,
            'options' => [
                'slug' => $product->slug,
                'image' => '',
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'campaign_id' => (int) $campaign->id,
                'campaign_slug' => $campaign->slug,
                'campaign_price' => $campaignPrice,
                'regular_price' => $regularPrice,
                'price_source' => 'campaign',
                'variant_id' => null,
            ],
        ]);
    }

    /**
     * A line at the plain product price.
     */
    protected function seedNormalCartLine(object $product, int $qty = 1): void
    {
        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => (float) $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => '',
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'campaign_id' => null,
                'regular_price' => (float) $product->new_price,
                'price_source' => 'product',
                'variant_id' => null,
            ],
        ]);
    }
}
