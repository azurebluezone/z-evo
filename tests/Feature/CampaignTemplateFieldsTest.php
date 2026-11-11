<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Template-specific landing content.
 *
 * The admin writes campaign copy in campaigns.extra_fields; the controller
 * whitelists it, the frontend merges it under computed product/price keys and
 * every template renders it defensively. Product prices stay untouched.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase), so every
 * row it creates is deleted in tearDown.
 */
class CampaignTemplateFieldsTest extends TestCase
{
    private array $createdProductIds = [];
    private array $createdCampaignIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('productimages')->whereIn('product_id', $this->createdProductIds)->delete();
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        parent::tearDown();
    }

    public function test_admin_create_persists_registry_content_fields(): void
    {
        $product = $this->createProduct();
        $name = 'QA Registry Fields ' . Str::random(8);

        $create = $this->actingAs($this->admin())->get('/admin/campaign/create');
        $create->assertOk();
        $create->assertSee('Landing Content');
        $create->assertSee('name="extra_fields[trust_items][__INDEX__][title]"', false);
        $create->assertSee('name="extra_fields[announce_text]"', false);
        $create->assertDontSee('name="extra_fields[features]', false);
        $create->assertDontSee('One row per item');
        $create->assertDontSee("Fields follow the selected template's enabled variables");

        $response = $this->actingAs($this->admin())->post('/admin/campaign/save', [
            'name' => $name,
            'short_description' => 'QA registry short description',
            'description' => 'QA registry campaign description',
            'review' => 'QA review',
            'campaign_type' => 'product',
            'template_id' => $this->templateId('ht2'),
            'product_id' => $product->id,
            'status' => 1,
            'image_one' => '',
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'sort_order' => 0,
            'extra_fields' => [
                'announce_text' => 'Free delivery this week',
                'hero_badge' => 'New arrival',
                'trust_items' => [
                    ['icon' => 'fas fa-truck', 'title' => 'Fast delivery', 'text' => 'Ships in 24 hours'],
                    ['icon' => '', 'title' => '', 'text' => ''],
                    ['icon' => 'fas fa-shield', 'title' => 'Secure packaging', 'text' => ''],
                ],
                'variants' => [
                    ['label' => 'Single pack', 'price' => 900, 'old_price' => 1200, 'image' => '', 'badge' => 'Best value'],
                ],
                'comparison' => [
                    ['feature' => 'Fabric', 'ours' => 'Premium cotton', 'theirs' => 'Polyester'],
                ],
                'reviews' => [
                    ['name' => 'Rahim', 'location' => 'Dhaka', 'text' => 'Great product', 'rating' => 5],
                ],
                'faqs' => [
                    ['question' => 'How long is delivery?', 'answer' => 'Two working days'],
                ],
                'unexpected_key' => 'must be dropped',
            ],
        ]);

        $response->assertRedirect(route('campaign.index'));
        $response->assertSessionHasNoErrors();

        $campaign = Campaign::where('name', $name)->latest('id')->first();
        $this->assertNotNull($campaign, 'The admin POST did not create the campaign.');
        $this->createdCampaignIds[] = (int) $campaign->id;

        $stored = $campaign->extra_fields;
        $this->assertIsArray($stored);
        $this->assertSame('Free delivery this week', $stored['announce_text']);
        $this->assertSame('New arrival', $stored['hero_badge']);
        $this->assertCount(2, $stored['trust_items']);
        $this->assertSame('Fast delivery', $stored['trust_items'][0]['title']);
        $this->assertSame('Secure packaging', $stored['trust_items'][1]['title']);
        $this->assertSame('Single pack', $stored['variants'][0]['label']);
        $this->assertSame('Premium cotton', $stored['comparison'][0]['ours']);
        $this->assertSame('Great product', $stored['reviews'][0]['text']);
        $this->assertSame('How long is delivery?', $stored['faqs'][0]['question']);
        $this->assertArrayNotHasKey('unexpected_key', $stored);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_admin_update_replaces_registry_fields_and_edit_screen_prefills_them(): void
    {
        $product = $this->createProduct();
        $campaign = $this->createCampaign($product->id, 'ht2', []);

        $response = $this->actingAs($this->admin())->post('/admin/campaign/update', [
            'hidden_id' => $campaign->id,
            'name' => $campaign->name . ' updated',
            'short_description' => 'Updated short description',
            'description' => 'Updated campaign description',
            'review' => 'Updated review',
            'campaign_type' => 'product',
            'template_id' => $this->templateId('ht2'),
            'product_id' => $product->id,
            'status' => 1,
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'extra_fields' => [
                'announce_text' => 'Updated announcement',
                'trust_items' => [
                    ['icon' => 'fas fa-check', 'title' => 'Updated trust', 'text' => 'Updated copy'],
                ],
                'faqs' => [
                    ['question' => 'Updated question?', 'answer' => 'Updated answer'],
                ],
            ],
        ]);

        $response->assertRedirect(route('campaign.index'));
        $response->assertSessionHasNoErrors();

        $campaign->refresh();
        $this->assertSame('Updated announcement', $campaign->extra_fields['announce_text']);
        $this->assertSame('Updated trust', $campaign->extra_fields['trust_items'][0]['title']);
        $this->assertSame('Updated question?', $campaign->extra_fields['faqs'][0]['question']);

        $edit = $this->actingAs($this->admin())->get('/admin/campaign/' . $campaign->id . '/edit');
        $edit->assertOk();
        $edit->assertSee('name="template_id"', false);
        $edit->assertSee('Landing Content');
        $edit->assertSee('name="extra_fields[trust_items][0][title]"', false);
        $edit->assertSee('value="Updated trust"', false);
        $edit->assertSee('name="extra_fields[faqs][0][question]"', false);
        $edit->assertSee('Updated question?');
    }

    public function test_invalid_content_is_rejected(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->admin())
            ->from('/admin/campaign/create')
            ->post('/admin/campaign/save', [
                'name' => 'QA Invalid Content ' . Str::random(8),
                'short_description' => 'QA invalid content',
                'description' => 'QA invalid content',
                'review' => 'QA review',
                'campaign_type' => 'product',
                'template_id' => $this->templateId('ht2'),
                'product_id' => $product->id,
                'status' => 1,
                'image_one' => '',
                'extra_fields' => [
                    'announce_text' => str_repeat('x', 2100),
                    'variants' => [
                        ['label' => 'Broken', 'price' => -5],
                    ],
                    'reviews' => [
                        ['name' => 'QA', 'text' => 'QA', 'rating' => 9],
                    ],
                ],
            ]);

        $response->assertRedirect('/admin/campaign/create');
        $response->assertSessionHasErrors([
            'extra_fields.announce_text',
            'extra_fields.variants.0.price',
            'extra_fields.reviews.0.rating',
        ]);
    }

    public function test_row_count_over_limit_is_rejected(): void
    {
        $product = $this->createProduct();
        $rows = [];

        for ($i = 0; $i < 20; $i++) {
            $rows[] = ['title' => 'Trust ' . $i];
        }

        $name = 'QA Capped Content ' . Str::random(8);

        $response = $this->actingAs($this->admin())
            ->from('/admin/campaign/create')
            ->post('/admin/campaign/save', [
                'name' => $name,
                'short_description' => 'QA capped content',
                'description' => 'QA capped content',
                'review' => 'QA review',
                'campaign_type' => 'product',
                'template_id' => $this->templateId('ht2'),
                'product_id' => $product->id,
                'status' => 1,
                'image_one' => '',
                'extra_fields' => ['trust_items' => $rows],
            ]);

        $response->assertRedirect('/admin/campaign/create');
        $response->assertSessionHasErrors('extra_fields.trust_items');
        $this->assertSame(0, Campaign::where('name', $name)->count());
    }

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user exists, so the admin request cannot authenticate.');

        return $admin;
    }

    private function templateId(string $viewName): int
    {
        $id = DB::table('templates')->where('view_name', $viewName)->value('id');
        $this->assertNotNull($id, 'Template row is missing: ' . $viewName);

        return (int) $id;
    }

    private function createProduct(): object
    {
        $id = DB::table('products')->insertGetId([
            'name' => 'QA Template Product ' . Str::random(6),
            'slug' => 'qa-template-product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'QTF-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'QA template product description',
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdProductIds[] = $id;

        return DB::table('products')->where('id', $id)->first();
    }

    private function createCampaign(int $productId, string $viewName, array $extraFields, array $overrides = []): Campaign
    {
        $campaign = Campaign::create(array_merge([
            'name' => 'QA Template Campaign ' . Str::random(8),
            'slug' => 'qa-template-campaign-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'QA template short description',
            'review' => 'QA review',
            'description' => 'QA template campaign description',
            'image_one' => '',
            'status' => '1',
            'campaign_type' => 'product',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'template_id' => $this->templateId($viewName),
            'product_id' => $productId,
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'extra_fields' => $extraFields,
        ], $overrides));

        $this->createdCampaignIds[] = (int) $campaign->id;

        return $campaign;
    }
}
