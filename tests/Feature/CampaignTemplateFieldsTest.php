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

    public function test_admin_create_persists_whitelisted_extra_fields(): void
    {
        $product = $this->createProduct();
        $name = 'QA Template Fields ' . Str::random(8);

        $create = $this->actingAs($this->admin())->get('/admin/campaign/create');
        $create->assertOk();
        $create->assertSee('Landing Content');
        $create->assertSee('name="extra_fields[features][__INDEX__][title]"', false);
        $create->assertSee('name="extra_fields[video_url]"', false);

        $response = $this->actingAs($this->admin())->post('/admin/campaign/save', [
            'name' => $name,
            'short_description' => 'QA template short description',
            'description' => 'QA template campaign description',
            'review' => 'QA review',
            'campaign_type' => 'product',
            'template_id' => $this->templateId('sample-template'),
            'product_id' => $product->id,
            'status' => 1,
            'image_one' => '',
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'sort_order' => 0,
            'show_features' => 1,
            'show_benefits' => 1,
            'show_testimonials' => 1,
            'show_faq' => 1,
            'extra_fields' => [
                'contact_phone' => '01700000000',
                'email' => 'hello@example.com',
                'video_url' => 'https://www.youtube.com/embed/qa123',
                'features' => [
                    ['title' => 'Fast delivery', 'description' => 'Ships in 24 hours', 'image' => ''],
                    ['title' => '', 'description' => '', 'image' => ''],
                    ['title' => 'Secure packaging', 'description' => '', 'image' => ''],
                ],
                'benefits' => [
                    ['icon' => 'fas fa-leaf', 'title' => 'Natural', 'text' => 'No chemicals'],
                ],
                'testimonials' => [
                    ['name' => 'Rahim', 'text' => 'Great product', 'rating' => 5],
                ],
                'faqs' => [
                    ['question' => 'How long is delivery?', 'answer' => 'Two working days'],
                ],
                'why_us' => [
                    ['text' => 'Best price in town'],
                ],
                'packages' => [
                    ['name' => 'One pack', 'price' => 900, 'image' => ''],
                ],
                'unexpected_key' => 'must be dropped',
                'features_extra' => 'must be dropped',
            ],
        ]);

        $response->assertRedirect(route('campaign.index'));
        $response->assertSessionHasNoErrors();

        $campaign = Campaign::where('name', $name)->latest('id')->first();
        $this->assertNotNull($campaign, 'The admin POST did not create the campaign.');
        $this->createdCampaignIds[] = (int) $campaign->id;

        $stored = $campaign->extra_fields;
        $this->assertIsArray($stored);
        $this->assertSame('01700000000', $stored['contact_phone']);
        $this->assertSame('hello@example.com', $stored['email']);
        $this->assertCount(2, $stored['features']);
        $this->assertSame('Fast delivery', $stored['features'][0]['title']);
        $this->assertSame('Ships in 24 hours', $stored['features'][0]['description']);
        $this->assertSame('Secure packaging', $stored['features'][1]['title']);
        $this->assertSame('Natural', $stored['benefits'][0]['title']);
        $this->assertSame('Great product', $stored['testimonials'][0]['text']);
        $this->assertSame('How long is delivery?', $stored['faqs'][0]['question']);
        $this->assertSame('Best price in town', $stored['why_us'][0]['text']);
        $this->assertSame('One pack', $stored['packages'][0]['name']);
        $this->assertArrayNotHasKey('unexpected_key', $stored);
        $this->assertArrayNotHasKey('features_extra', $stored);

        $this->assertSame(1200, (int) DB::table('products')->where('id', $product->id)->value('new_price'));
    }

    public function test_admin_update_replaces_extra_fields_and_edit_screen_prefills_them(): void
    {
        $product = $this->createProduct();
        $campaign = $this->createCampaign($product->id, 'sample-template', []);

        $response = $this->actingAs($this->admin())->post('/admin/campaign/update', [
            'hidden_id' => $campaign->id,
            'name' => $campaign->name . ' updated',
            'short_description' => 'Updated short description',
            'description' => 'Updated campaign description',
            'review' => 'Updated review',
            'campaign_type' => 'product',
            'template_id' => $this->templateId('sample-template'),
            'product_id' => $product->id,
            'status' => 1,
            'campaign_price' => 900,
            'campaign_price_enabled' => 1,
            'allow_coupon' => 0,
            'show_features' => 1,
            'show_benefits' => 1,
            'show_testimonials' => 0,
            'show_faq' => 1,
            'extra_fields' => [
                'email' => 'updated@example.com',
                'features' => [
                    ['title' => 'Updated feature', 'description' => 'Updated copy', 'image' => ''],
                ],
                'testimonials' => [
                    ['name' => 'Karim', 'text' => 'Hidden after update', 'rating' => 4],
                ],
            ],
        ]);

        $response->assertRedirect(route('campaign.index'));
        $response->assertSessionHasNoErrors();

        $campaign->refresh();
        $this->assertSame('updated@example.com', $campaign->extra_fields['email']);
        $this->assertSame('Updated feature', $campaign->extra_fields['features'][0]['title']);
        $this->assertSame(0, (int) $campaign->show_testimonials);

        $edit = $this->actingAs($this->admin())->get('/admin/campaign/' . $campaign->id . '/edit');
        $edit->assertOk();
        $edit->assertSee('name="template_id"', false);
        $edit->assertSee('Landing Content');
        $edit->assertSee('name="extra_fields[features][0][title]"', false);
        $edit->assertSee('value="Updated feature"', false);
        $edit->assertSee('name="extra_fields[testimonials][0][text]"', false);
        $edit->assertSee('Hidden after update');
    }

    public function test_sample_template_renders_stored_content_and_computed_values_win(): void
    {
        $product = $this->createProduct();
        $campaign = $this->createCampaign($product->id, 'sample-template', [
            'new_price' => 1,
            'old_price' => 2,
            'product_id' => 999999,
            'campaign_id' => 999999,
            'features' => [
                ['title' => 'Rendered feature', 'description' => 'Rendered feature copy', 'image' => ''],
            ],
            'benefits' => [
                ['icon' => 'fas fa-leaf', 'title' => 'Rendered benefit', 'text' => 'Rendered benefit copy'],
            ],
            'testimonials' => [
                ['name' => 'Rendered customer', 'text' => 'Rendered testimonial', 'rating' => 5],
            ],
            'faqs' => [
                ['question' => 'Rendered question?', 'answer' => 'Rendered answer'],
            ],
        ]);

        $response = $this->get('/campaign/' . $campaign->slug);

        $response->assertOk();
        $response->assertSee('Rendered feature');
        $response->assertSee('Rendered feature copy');
        $response->assertSee('Rendered benefit');
        $response->assertSee('Rendered benefit copy');
        $response->assertSee('Rendered customer');
        $response->assertSee('Rendered testimonial');
        $response->assertSee('Rendered question?');
        $response->assertSee('Rendered answer');

        // Stored price/product identity can never override the real values.
        $response->assertSee('900');
        $response->assertSee('1200');
        $response->assertDontSee('value="999999"', false);
        $response->assertSee('value="' . $product->id . '"', false);
        $response->assertSee('value="' . $campaign->id . '"', false);
    }

    public function test_switches_hide_optional_sections(): void
    {
        $product = $this->createProduct();
        $campaign = $this->createCampaign($product->id, 'sample-template', [
            'features' => [['title' => 'Switch feature', 'description' => '']],
            'benefits' => [['title' => 'Switch benefit', 'text' => '']],
            'testimonials' => [['name' => 'Switch customer', 'text' => 'Switch testimonial']],
            'faqs' => [['question' => 'Switch question?', 'answer' => 'Switch answer']],
        ], [
            'show_features' => 0,
            'show_benefits' => 1,
            'show_testimonials' => 0,
            'show_faq' => 0,
        ]);

        $response = $this->get('/campaign/' . $campaign->slug);

        $response->assertOk();
        $response->assertDontSee('Switch feature');
        $response->assertSee('Switch benefit');
        $response->assertDontSee('Switch customer');
        $response->assertDontSee('Switch testimonial');
        $response->assertDontSee('Switch question?');
        $response->assertDontSee('Switch answer');
    }

    public function test_template_one_accepts_array_and_legacy_string_content(): void
    {
        $product = $this->createProduct();
        $campaign = $this->createCampaign($product->id, 'template-1', [
            'features' => [
                ['title' => 'Array feature', 'description' => 'Array feature copy'],
            ],
            'why_us' => [
                ['text' => 'Array reason'],
            ],
        ]);

        $arrayPage = $this->get('/campaign/' . $campaign->slug);
        $arrayPage->assertOk();
        $arrayPage->assertSee('Array feature');
        $arrayPage->assertSee('Array feature copy');
        $arrayPage->assertSee('Array reason');

        DB::table('campaigns')->where('id', $campaign->id)->update([
            'show_features' => 0,
            'extra_fields' => json_encode([
                'features' => ['Legacy string feature'],
                'why_us' => ['Legacy string reason'],
            ]),
        ]);

        $hiddenPage = $this->get('/campaign/' . $campaign->slug);
        $hiddenPage->assertOk();
        $hiddenPage->assertDontSee('Legacy string feature');
        $hiddenPage->assertDontSee('Legacy string reason');

        DB::table('campaigns')->where('id', $campaign->id)->update([
            'show_features' => 1,
            'extra_fields' => json_encode([
                'features' => ['Legacy string feature'],
                'why_us' => ['Legacy string reason'],
            ]),
        ]);

        $stringPage = $this->get('/campaign/' . $campaign->slug);
        $stringPage->assertOk();
        $stringPage->assertSee('Legacy string feature');
        $stringPage->assertSee('Legacy string reason');
    }

    public function test_template_two_three_and_herbal_health_render_new_row_shapes(): void
    {
        $product = $this->createProduct();

        $templateTwo = $this->createCampaign($product->id, 'template-2', [
            'features' => [['title' => 'Template two feature', 'description' => 'Template two copy']],
            'why_us' => [['text' => 'Template two reason']],
        ]);
        $pageTwo = $this->get('/campaign/' . $templateTwo->slug);
        $pageTwo->assertOk();
        $pageTwo->assertSee('Template two feature');
        $pageTwo->assertSee('Template two copy');
        $pageTwo->assertSee('Template two reason');

        $templateThree = $this->createCampaign($product->id, 'template-3', [
            'features' => [['title' => 'Template three feature', 'description' => 'Template three copy']],
            'benefits' => [['title' => 'Template three benefit', 'text' => 'Template three benefit copy']],
            'packages' => [['name' => 'Template three pack', 'price' => 950, 'image' => '']],
        ]);
        $pageThree = $this->get('/campaign/' . $templateThree->slug);
        $pageThree->assertOk();
        $pageThree->assertSee('Template three feature');
        $pageThree->assertSee('Template three copy');
        $pageThree->assertSee('Template three benefit');
        $pageThree->assertSee('Template three benefit copy');
        $pageThree->assertSee('Template three pack');

        $herbal = $this->createCampaign($product->id, 'herbal-health', [
            'benefits' => [['icon' => 'fas fa-leaf', 'title' => 'Herbal benefit', 'text' => 'Herbal benefit copy']],
            'testimonials' => [['name' => 'Herbal customer', 'text' => 'Herbal testimonial', 'rating' => 4]],
        ], [
            'show_benefits' => 1,
            'show_testimonials' => 1,
        ]);
        $pageHerbal = $this->get('/campaign/' . $herbal->slug);
        $pageHerbal->assertOk();
        $pageHerbal->assertSee('Herbal benefit');
        $pageHerbal->assertSee('Herbal benefit copy');
        $pageHerbal->assertSee('Herbal customer');
        $pageHerbal->assertSee('Herbal testimonial');
    }

    public function test_show_cta_hides_marketing_cta_but_keeps_order_form(): void
    {
        $product = $this->createProduct();

        $withoutCta = $this->createCampaign($product->id, 'template-1', [], [
            'show_cta' => 0,
            'hero_button_text' => 'QA CTA label',
        ]);
        $hidden = $this->get('/campaign/' . $withoutCta->slug);
        $hidden->assertOk();
        $hidden->assertDontSee('QA CTA label');
        $hidden->assertSee('class="btn-submit"', false);

        $withCta = $this->createCampaign($product->id, 'template-1', [], [
            'show_cta' => 1,
            'hero_button_text' => 'QA CTA label',
        ]);
        $visible = $this->get('/campaign/' . $withCta->slug);
        $visible->assertOk();
        $visible->assertSee('QA CTA label');
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
                'template_id' => $this->templateId('sample-template'),
                'product_id' => $product->id,
                'status' => 1,
                'image_one' => '',
                'extra_fields' => [
                    'email' => 'not-an-email',
                    'facebook' => 'not-a-url',
                    'features' => [
                        ['title' => str_repeat('x', 300)],
                    ],
                    'testimonials' => [
                        ['name' => 'QA', 'text' => 'QA', 'rating' => 9],
                    ],
                ],
            ]);

        $response->assertRedirect('/admin/campaign/create');
        $response->assertSessionHasErrors([
            'extra_fields.email',
            'extra_fields.facebook',
            'extra_fields.features.0.title',
            'extra_fields.testimonials.0.rating',
        ]);
    }

    public function test_row_count_over_limit_is_rejected(): void
    {
        $product = $this->createProduct();
        $rows = [];

        for ($i = 0; $i < 20; $i++) {
            $rows[] = ['title' => 'Feature ' . $i];
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
                'template_id' => $this->templateId('sample-template'),
                'product_id' => $product->id,
                'status' => 1,
                'image_one' => '',
                'extra_fields' => ['features' => $rows],
            ]);

        $response->assertRedirect('/admin/campaign/create');
        $response->assertSessionHasErrors('extra_fields.features');
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
            'show_features' => 1,
            'show_benefits' => 1,
            'show_testimonials' => 1,
            'show_faq' => 1,
            'show_cta' => 1,
            'extra_fields' => $extraFields,
        ], $overrides));

        $this->createdCampaignIds[] = (int) $campaign->id;

        return $campaign;
    }
}
