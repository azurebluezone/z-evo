<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Campaign attribution.
 *
 * Pins the funnel a campaign landing page must produce:
 *   - the rendered page exposes campaign/product/price context;
 *   - the event layer queues ViewContent, Engagement_FormStart and Lead for it;
 *   - /events/collect stores campaign_id as a real column and the campaign
 *     details inside event_details.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase), so every row
 * it creates is deleted in tearDown.
 */
class CampaignAttributionTest extends TestCase
{
    private array $createdProductIds = [];
    private array $createdCampaignIds = [];
    private array $visitorIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->visitorIds)) {
            DB::table('event_logs')->whereIn('visitor_id', $this->visitorIds)->delete();
            DB::table('visitors_master')->whereIn('visitor_id', $this->visitorIds)->delete();
        }

        if (!empty($this->createdCampaignIds)) {
            DB::table('event_logs')->whereIn('campaign_id', $this->createdCampaignIds)->delete();
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_campaign_page_renders_attribution_context_and_funnel_hooks(): void
    {
        [$product, $campaign] = $this->createCampaignPage('sample-template');

        $response = $this->get('/campaign/' . $campaign->slug);

        $response->assertOk();
        $response->assertSee('window.__CAMPAIGN__', false);
        $response->assertSee('"id":' . $campaign->id, false);
        $response->assertSee('"slug":"' . $campaign->slug . '"', false);
        $response->assertSee('"product_id":' . $product->id, false);
        $response->assertSee('"price":900', false);
        $response->assertSee('"currency":"BDT"', false);

        // DataLayer names and collector names are intentionally different.
        $response->assertSee("push('view_item'", false);
        $response->assertSee("queue('ViewContent'", false);
        $response->assertSee("push('form_start'", false);
        $response->assertSee("queue('Engagement_FormStart'", false);
        $response->assertSee("push('generate_lead'", false);
        $response->assertSee("queue('Lead'", false);
        $response->assertSee('form[action*="/landing-order"]', false);
    }

    public function test_collector_persists_campaign_id_and_details_for_the_funnel(): void
    {
        [$product, $campaign] = $this->createCampaignPage('sample-template');

        $visitorId = (string) Str::uuid();
        $this->visitorIds[] = $visitorId;

        DB::table('visitors_master')->insert([
            'visitor_id' => $visitorId,
            'ip' => '127.0.0.1',
            'user_agent' => 'campaign-attribution-test',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pagePath = '/campaign/' . $campaign->slug;
        $details = [
            'campaign_id' => (int) $campaign->id,
            'campaign_slug' => $campaign->slug,
            'campaign_name' => $campaign->name,
            'campaign_template' => 'sample-template',
            'product_id' => (int) $product->id,
            'product_name' => $product->name,
            'value' => 900,
            'regular_price' => 1200,
            'price_source' => 'campaign',
            'currency' => 'BDT',
        ];

        $events = [];
        foreach (['ViewContent', 'Engagement_FormStart', 'Lead'] as $name) {
            $events[] = [
                'name' => $name,
                'event_id' => bin2hex(random_bytes(16)),
                'url' => 'http://localhost' . $pagePath,
                'page_path' => $pagePath,
                'ts' => now()->toIso8601String(),
                'campaign_id' => (int) $campaign->id,
                'event_data' => $details,
            ];
        }

        $response = $this->postJson('/events/collect', [
            'visitor_id' => $visitorId,
            'events' => $events,
        ]);

        $response->assertOk();

        $saved = DB::table('event_logs')
            ->where('visitor_id', $visitorId)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $saved);
        $this->assertSame(
            ['ViewContent', 'Engagement_FormStart', 'Lead'],
            $saved->pluck('event_name')->all()
        );

        foreach ($saved as $row) {
            $this->assertSame((int) $campaign->id, (int) $row->campaign_id);
            $this->assertSame($pagePath, $row->page_path);

            $stored = json_decode((string) $row->event_details, true);
            $this->assertIsArray($stored);
            $this->assertSame((int) $campaign->id, (int) $stored['campaign_id']);
            $this->assertSame($campaign->slug, $stored['campaign_slug']);
            $this->assertSame((int) $product->id, (int) $stored['product_id']);
            $this->assertSame(900, $stored['value']);
            $this->assertSame('BDT', $stored['currency']);
        }
    }

    /**
     * @return array{0: object, 1: object}
     */
    private function createCampaignPage(string $viewName): array
    {
        $templateId = DB::table('templates')->where('view_name', $viewName)->value('id');
        $this->assertNotNull($templateId, "Template {$viewName} is missing.");

        $productId = DB::table('products')->insertGetId([
            'name' => 'QA Attribution Product ' . Str::random(6),
            'slug' => 'qa-attribution-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'QAT-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'QA attribution product description',
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdProductIds[] = $productId;
        $product = DB::table('products')->where('id', $productId)->first();

        $campaignId = DB::table('campaigns')->insertGetId([
            'name' => 'QA Attribution Campaign ' . Str::random(6),
            'slug' => 'qa-attribution-campaign-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'QA attribution campaign',
            'review' => 'QA review',
            'description' => 'QA attribution campaign description',
            'image_one' => '',
            'status' => '1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'template_id' => $templateId,
            'product_id' => $productId,
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdCampaignIds[] = $campaignId;
        $campaign = DB::table('campaigns')->where('id', $campaignId)->first();

        return [$product, $campaign];
    }
}
