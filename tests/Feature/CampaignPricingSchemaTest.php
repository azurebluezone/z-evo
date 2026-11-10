<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 campaign-pricing schema contract.
 *
 * These assertions pin the naming and nullability the resolver and the
 * order snapshot depend on. They run against the live MySQL connection
 * (this suite has no RefreshDatabase), so they only read schema metadata.
 */
class CampaignPricingSchemaTest extends TestCase
{
    public function test_campaigns_has_campaign_pricing_columns(): void
    {
        foreach ([
            'product_id',
            'campaign_price',
            'campaign_price_enabled',
            'campaign_discount_percent',
            'allow_coupon',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('campaigns', $column),
                "campaigns.{$column} is missing"
            );
        }
    }

    public function test_order_details_has_campaign_snapshot_columns(): void
    {
        foreach (['campaign_id', 'regular_price'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('order_details', $column),
                "order_details.{$column} is missing"
            );
        }
    }

    public function test_campaign_price_columns_keep_their_expected_types(): void
    {
        $this->assertSame('decimal', Schema::getColumnType('campaigns', 'campaign_price'));
        $this->assertSame('decimal', Schema::getColumnType('campaigns', 'campaign_discount_percent'));
        $this->assertSame('boolean', $this->normalizeBooleanType(Schema::getColumnType('campaigns', 'campaign_price_enabled')));
        $this->assertSame('bigint', Schema::getColumnType('campaigns', 'product_id'));
        $this->assertSame('integer', $this->normalizeIntegerType(Schema::getColumnType('order_details', 'campaign_id')));
        $this->assertSame('decimal', Schema::getColumnType('order_details', 'regular_price'));
    }

    public function test_order_details_campaign_id_is_nullable_for_legacy_orders(): void
    {
        $columns = collect(Schema::getColumns('order_details'))->keyBy('name');
        $this->assertArrayHasKey('campaign_id', $columns->all());
        $this->assertTrue((bool) $columns['campaign_id']['nullable'], 'campaign_id must stay nullable');

        $campaignColumns = collect(Schema::getColumns('campaigns'))->keyBy('name');
        $this->assertTrue((bool) $campaignColumns['product_id']['nullable'], 'product_id must stay nullable');
        $this->assertTrue((bool) $campaignColumns['campaign_price']['nullable'], 'campaign_price must stay nullable');
    }

    private function normalizeBooleanType(string $type): string
    {
        return in_array($type, ['tinyint', 'boolean', 'bool'], true) ? 'boolean' : $type;
    }

    private function normalizeIntegerType(string $type): string
    {
        return $type === 'int' ? 'integer' : $type;
    }
}
