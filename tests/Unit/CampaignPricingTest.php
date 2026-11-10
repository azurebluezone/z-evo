<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Product;
use App\Services\CampaignPricing;
use Tests\TestCase;

class CampaignPricingTest extends TestCase
{
    private CampaignPricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new CampaignPricing();
    }

    private function product(int $id = 1, int $newPrice = 1200): Product
    {
        $product = new Product();
        $product->id = $id;
        $product->new_price = $newPrice;

        return $product;
    }

    private function campaign(array $attributes): Campaign
    {
        $campaign = new Campaign();

        foreach ($attributes as $key => $value) {
            $campaign->setAttribute($key, $value);
        }

        return $campaign;
    }

    public function test_enabled_fixed_price_wins(): void
    {
        $result = $this->pricing->resolve($this->product(), null, $this->campaign([
            'product_id' => 1,
            'campaign_price' => 899,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(899.0, $result['price']);
        $this->assertSame(1200.0, $result['regular']);
        $this->assertSame('campaign', $result['source']);
        $this->assertNull($result['reason']);
    }

    public function test_disabled_campaign_uses_product_price(): void
    {
        $result = $this->pricing->resolve($this->product(), null, $this->campaign([
            'product_id' => 1,
            'campaign_price' => 899,
            'campaign_price_enabled' => false,
        ]));

        $this->assertSame(1200.0, $result['price']);
        $this->assertSame('product', $result['source']);
        $this->assertSame('campaign_disabled', $result['reason']);
    }

    public function test_percent_uses_variant_override_as_regular_price(): void
    {
        $variant = (object) ['price_override' => 1500, 'discount_price' => null];

        $result = $this->pricing->resolve($this->product(), $variant, $this->campaign([
            'product_id' => 1,
            'campaign_discount_percent' => 25,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1500.0, $result['regular']);
        $this->assertSame(1125.0, $result['price']);
        $this->assertSame('campaign', $result['source']);
    }

    public function test_variant_discount_price_is_the_regular_price(): void
    {
        $variant = (object) ['price_override' => 1500, 'discount_price' => 1000];

        $result = $this->pricing->resolve($this->product(), $variant, $this->campaign([
            'product_id' => 1,
            'campaign_discount_percent' => 25,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1000.0, $result['regular']);
        $this->assertSame(750.0, $result['price']);
    }

    public function test_campaign_price_at_or_above_regular_is_rejected(): void
    {
        $result = $this->pricing->resolve($this->product(), null, $this->campaign([
            'product_id' => 1,
            'campaign_price' => 1200,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1200.0, $result['price']);
        $this->assertSame('product', $result['source']);
        $this->assertSame('not_a_discount', $result['reason']);
    }

    public function test_zero_campaign_price_is_rejected(): void
    {
        $result = $this->pricing->resolve($this->product(), null, $this->campaign([
            'product_id' => 1,
            'campaign_price' => 0,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1200.0, $result['price']);
        $this->assertSame('invalid_price', $result['reason']);
    }

    public function test_product_mismatch_falls_back(): void
    {
        $result = $this->pricing->resolve($this->product(id: 1), null, $this->campaign([
            'product_id' => 2,
            'campaign_price' => 899,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1200.0, $result['price']);
        $this->assertSame('product_mismatch', $result['reason']);
    }

    public function test_percent_takes_precedence_over_fixed_price(): void
    {
        $result = $this->pricing->resolve($this->product(), null, $this->campaign([
            'product_id' => 1,
            'campaign_price' => 899,
            'campaign_discount_percent' => 10,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1080.0, $result['price']);
        $this->assertSame('campaign', $result['source']);
    }

    public function test_no_campaign_uses_product_price(): void
    {
        $result = $this->pricing->resolve($this->product(), null, null);

        $this->assertSame(1200.0, $result['price']);
        $this->assertSame('product', $result['source']);
        $this->assertSame('campaign_disabled', $result['reason']);
    }

    public function test_variant_without_override_uses_product_price(): void
    {
        $variant = (object) ['price_override' => null, 'discount_price' => null];

        $result = $this->pricing->resolve($this->product(), $variant, $this->campaign([
            'product_id' => 1,
            'campaign_discount_percent' => 10,
            'campaign_price_enabled' => true,
        ]));

        $this->assertSame(1200.0, $result['regular']);
        $this->assertSame(1080.0, $result['price']);
    }
}
