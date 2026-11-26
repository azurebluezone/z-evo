<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The nine design views are files, not seed rows. These tests used to assume
 * the database already carried one active campaign per design, which made them
 * pass or fail depending on whatever demo data happened to be seeded. Build the
 * missing campaign instead, and delete it again in tearDown.
 */
trait EnsuresDesignCampaigns
{
    /** @var int[] */
    protected array $designCampaignIds = [];

    /** @var int[] */
    protected array $designProductIds = [];

    /** @var int[] */
    protected array $designTemplateIds = [];

    protected function activeCampaignForDesign(string $view): Campaign
    {
        $existing = Campaign::with('template')
            ->where('status', 1)
            ->whereHas('template', fn ($query) => $query->where('view_name', $view))
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $templateId = (int) DB::table('templates')->where('view_name', $view)->value('id');

        if ($templateId === 0) {
            $templateId = (int) DB::table('templates')->insertGetId([
                'name' => 'QA ' . $view,
                'view_name' => $view,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->designTemplateIds[] = $templateId;
        }

        $productId = (int) DB::table('products')->insertGetId([
            'name' => 'QA Design Product ' . Str::upper(Str::random(5)),
            'slug' => 'qa-design-product-' . Str::lower(Str::random(8)),
            'category_id' => 1,
            'product_code' => 'QAD-' . Str::upper(Str::random(8)),
            'purchase_price' => 400,
            'old_price' => 1200,
            'new_price' => 900,
            'description' => 'QA design campaign product',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->designProductIds[] = $productId;

        $campaignId = (int) DB::table('campaigns')->insertGetId([
            'name' => 'QA Design Campaign ' . Str::upper(Str::random(5)),
            'slug' => 'qa-design-campaign-' . Str::lower(Str::random(8)),
            'date' => now()->toDateString(),
            'short_description' => 'QA design campaign',
            'review' => 'QA review',
            'description' => 'QA design campaign description',
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
        $this->designCampaignIds[] = $campaignId;

        return Campaign::with('template')->findOrFail($campaignId);
    }

    protected function cleanupDesignCampaignFixtures(): void
    {
        if (!empty($this->designCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->designCampaignIds)->delete();
        }

        if (!empty($this->designProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->designProductIds)->delete();
            DB::table('products')->whereIn('id', $this->designProductIds)->delete();
        }

        if (!empty($this->designTemplateIds)) {
            DB::table('templates')->whereIn('id', $this->designTemplateIds)->delete();
        }

        $this->designCampaignIds = [];
        $this->designProductIds = [];
        $this->designTemplateIds = [];
    }
}
