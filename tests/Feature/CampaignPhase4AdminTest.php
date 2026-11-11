<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Product;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 4 campaign manager slice.
 *
 * Pinned regressions:
 *
 *   1. campaigns.slug had no unique index and the controller wrote the raw
 *      slug, so a duplicate name either collided or produced a duplicate URL.
 *   2. the edit form only listed active products, so saving a campaign whose
 *      product had been deactivated silently dropped the product link.
 *   3. there was no admin preview for an inactive campaign or an inactive
 *      template.
 *   4. campaign routes had no permission middleware.
 *   5. the campaign list showed only a title and status, so the manager could
 *      not see the product, template, schedule or the live page link.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase); every row
 * is QA-prefixed and removed in tearDown.
 */
class CampaignPhase4AdminTest extends TestCase
{
    private const PREFIX = 'qa-phase4-';

    private array $createdCampaignIds = [];
    private array $createdProductIds = [];
    private array $createdTemplateIds = [];
    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function tearDown(): void
    {
        DB::table('campaigns')->where('slug', 'like', self::PREFIX . '%')->delete();

        if (!empty($this->createdCampaignIds)) {
            DB::table('campaigns')->whereIn('id', $this->createdCampaignIds)->delete();
        }

        if (!empty($this->createdProductIds)) {
            DB::table('products')->whereIn('id', $this->createdProductIds)->delete();
        }

        if (!empty($this->createdTemplateIds)) {
            DB::table('templates')->whereIn('id', $this->createdTemplateIds)->delete();
        }

        if (!empty($this->createdUserIds)) {
            DB::table('model_has_roles')
                ->whereIn('model_id', $this->createdUserIds)
                ->where('model_type', User::class)
                ->delete();
            DB::table('users')->whereIn('id', $this->createdUserIds)->delete();
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user exists, so this test cannot authenticate.');

        return $admin;
    }

    private function product(array $overrides = []): Product
    {
        $id = DB::table('products')->insertGetId(array_merge([
            'name' => 'QA Phase4 Product ' . Str::random(6),
            'slug' => self::PREFIX . 'product-' . Str::random(8),
            'category_id' => 1,
            'brand_id' => null,
            'product_code' => 'QA4-' . strtoupper(Str::random(8)),
            'purchase_price' => 800,
            'old_price' => 1500,
            'new_price' => 1200,
            'stock' => 20,
            'meta_description' => null,
            'description' => 'QA phase4 product description',
            'topsale' => null,
            'feature_product' => null,
            'campaign_id' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $this->createdProductIds[] = (int) $id;

        return Product::findOrFail($id);
    }

    private function template(bool $active = true): Template
    {
        $template = Template::create([
            'name' => 'QA Phase4 Template ' . Str::random(5),
            'description' => 'QA phase4 template',
            'view_name' => 'ht2',
            'is_active' => $active,
            'config' => ['extra_fields' => ['old_price', 'new_price']],
        ]);

        $this->createdTemplateIds[] = (int) $template->id;

        return $template;
    }

    /** Required POST fields for /admin/campaign/save. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => self::PREFIX . 'campaign ' . Str::random(6),
            'short_description' => 'QA phase4 short description',
            'description' => 'QA phase4 description',
            'review' => 'QA phase4 review',
            'campaign_type' => 'product',
            'status' => 1,
            'image_one' => 'public/uploads/campaign/qa-phase4.webp',
            'sort_order' => 0,
        ], $overrides);
    }

    /** Insert a campaign row directly so the test controls status/slug. */
    private function campaignRow(array $overrides = []): Campaign
    {
        $product = $overrides['product'] ?? $this->product();
        $template = $overrides['template'] ?? $this->template();
        unset($overrides['product'], $overrides['template']);

        $id = DB::table('campaigns')->insertGetId(array_merge([
            'name' => self::PREFIX . 'row ' . Str::random(6),
            'slug' => self::PREFIX . 'row-' . Str::random(8),
            'date' => now()->toDateString(),
            'short_description' => 'QA phase4 short description',
            'review' => 'QA phase4 review',
            'description' => 'QA phase4 description',
            'image_one' => 'public/uploads/campaign/qa-phase4.webp',
            'image_two' => null,
            'image_three' => null,
            'status' => '1',
            'product_id' => $product->id,
            'template_id' => $template->id,
            'campaign_type' => 'product',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $this->createdCampaignIds[] = (int) $id;

        return Campaign::findOrFail($id);
    }

    public function test_duplicate_campaign_names_get_a_suffixed_slug(): void
    {
        $product = $this->product();
        $template = $this->template();
        $name = self::PREFIX . 'duplicate name ' . Str::random(4);
        $payload = $this->payload([
            'name' => $name,
            'product_id' => $product->id,
            'template_id' => $template->id,
        ]);

        $admin = $this->admin();

        $first = $this->actingAs($admin, 'web')->post('/admin/campaign/save', $payload);
        $first->assertRedirect(route('campaign.index'));
        $first->assertSessionHasNoErrors();

        $second = $this->actingAs($admin, 'web')->post('/admin/campaign/save', $payload);
        $second->assertRedirect(route('campaign.index'));
        $second->assertSessionHasNoErrors();

        $rows = Campaign::query()->where('name', $name)->orderBy('id')->get();
        $this->createdCampaignIds = array_merge($this->createdCampaignIds, $rows->pluck('id')->all());

        $base = Str::slug($name);
        $this->assertCount(2, $rows);
        $this->assertSame($base, $rows[0]->slug);
        $this->assertSame($base . '-2', $rows[1]->slug);
    }

    public function test_database_rejects_a_duplicate_slug(): void
    {
        $slug = self::PREFIX . 'db-dup-' . Str::random(6);
        $this->campaignRow(['slug' => $slug]);

        $this->expectException(QueryException::class);

        $this->campaignRow(['slug' => $slug]);
    }

    public function test_edit_form_keeps_the_inactive_current_product_and_template(): void
    {
        $product = $this->product([
            'name' => 'QA Phase4 Inactive Product',
            'status' => 0,
        ]);
        $template = $this->template(false);
        $campaign = $this->campaignRow([
            'product' => $product,
            'template' => $template,
        ]);

        $response = $this->actingAs($this->admin(), 'web')
            ->get('/admin/campaign/' . $campaign->id . '/edit');

        $response->assertOk();
        $response->assertSee('QA Phase4 Inactive Product');
        $response->assertSee('(inactive)');
        $response->assertSee($template->name);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/value="' . $product->id . '"[^>]*selected/',
            $html,
            'The inactive current product is not the selected option on the edit form.'
        );
        $this->assertMatchesRegularExpression(
            '/value="' . $template->id . '"[^>]*selected/',
            $html,
            'The inactive current template is not the selected option on the edit form.'
        );
    }

    public function test_user_without_campaign_permission_gets_403(): void
    {
        $user = User::create([
            'name' => 'QA Phase4 No Permission',
            'email' => self::PREFIX . Str::random(8) . '@example.test',
            'password' => bcrypt('secret123'),
            'status' => 1,
        ]);
        $this->createdUserIds[] = (int) $user->id;

        $this->actingAs($user, 'web')
            ->get('/admin/campaign/manage')
            ->assertStatus(403);

        $this->actingAs($user, 'web')
            ->get('/admin/campaign/create')
            ->assertStatus(403);
    }

    public function test_admin_can_preview_an_inactive_campaign_but_the_public_route_cannot(): void
    {
        $product = $this->product();
        $template = $this->template();
        $campaign = $this->campaignRow([
            'product' => $product,
            'template' => $template,
            'name' => 'QA Phase4 Draft Campaign',
            'status' => '0',
        ]);

        $this->actingAs($this->admin(), 'web')
            ->get('/admin/campaign/' . $campaign->id . '/preview')
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($campaign->slug);

        $this->get('/campaign/' . $campaign->slug)->assertNotFound();
    }

    public function test_template_preview_works_for_an_inactive_template(): void
    {
        $template = $this->template(false);

        $this->actingAs($this->admin(), 'web')
            ->get('/admin/campaign/template/' . $template->id . '/preview')
            ->assertOk();
    }

    public function test_slug_is_kept_when_the_name_is_unchanged_and_suffixed_on_rename(): void
    {
        $product = $this->product();
        $template = $this->template();
        $name = self::PREFIX . 'rename ' . Str::random(4);
        $campaign = $this->campaignRow([
            'product' => $product,
            'template' => $template,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);

        $admin = $this->admin();
        $payload = $this->payload([
            'hidden_id' => $campaign->id,
            'name' => $name,
            'product_id' => $product->id,
            'template_id' => $template->id,
        ]);

        $this->actingAs($admin, 'web')->post('/admin/campaign/update', $payload)
            ->assertRedirect(route('campaign.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(Str::slug($name), $campaign->fresh()->slug);

        // Rename to a name whose slug is already taken: the edited campaign
        // must move to -2 without touching the other campaign.
        $takenName = self::PREFIX . 'taken ' . Str::random(4);
        $taken = $this->campaignRow([
            'product' => $product,
            'template' => $template,
            'name' => $takenName,
            'slug' => Str::slug($takenName),
        ]);

        $payload['name'] = $takenName;
        $this->actingAs($admin, 'web')->post('/admin/campaign/update', $payload)
            ->assertRedirect(route('campaign.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(Str::slug($takenName) . '-2', $campaign->fresh()->slug);
        $this->assertSame(Str::slug($takenName), $taken->fresh()->slug);
    }

    public function test_admin_can_deactivate_activate_and_delete_a_campaign_without_unlinking_other_products(): void
    {
        $product = $this->product();
        $otherProduct = $this->product();
        $template = $this->template();

        // products.campaign_id is a legacy tinyint (max 127); give these two
        // QA campaigns low explicit ids so the link is representable.
        $campaign = $this->campaignRow([
            'id' => 120,
            'product' => $product,
            'template' => $template,
            'status' => '1',
        ]);
        $otherCampaign = $this->campaignRow([
            'id' => 121,
            'product' => $otherProduct,
            'template' => $template,
            'status' => '1',
        ]);

        DB::table('products')->where('id', $product->id)->update(['campaign_id' => $campaign->id]);
        DB::table('products')->where('id', $otherProduct->id)->update(['campaign_id' => $otherCampaign->id]);

        $admin = $this->admin();

        $this->actingAs($admin, 'web')
            ->post('/admin/campaign/inactive', ['hidden_id' => $campaign->id])
            ->assertRedirect();
        $this->assertSame(0, (int) $campaign->fresh()->status);

        $this->actingAs($admin, 'web')
            ->post('/admin/campaign/active', ['hidden_id' => $campaign->id])
            ->assertRedirect();
        $this->assertSame(1, (int) $campaign->fresh()->status);

        $this->actingAs($admin, 'web')
            ->post('/admin/campaign/destroy', ['hidden_id' => $campaign->id])
            ->assertRedirect();

        $this->assertNull(Campaign::find($campaign->id));
        $this->assertNull(DB::table('products')->where('id', $product->id)->value('campaign_id'));
        $this->assertSame(
            (int) $otherCampaign->id,
            (int) DB::table('products')->where('id', $otherProduct->id)->value('campaign_id'),
            'Deleting one campaign unlinked a product that belongs to another campaign.'
        );
    }

    public function test_index_lists_product_template_dates_and_live_preview(): void
    {
        $product = $this->product(['name' => 'QA Phase4 List Product']);
        $template = $this->template();

        $active = $this->campaignRow([
            'name' => 'QA Phase4 List Active',
            'product' => $product,
            'template' => $template,
            'status' => '1',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'sort_order' => 5,
        ]);

        $draft = $this->campaignRow([
            'name' => 'QA Phase4 List Draft',
            'product' => $product,
            'template' => $template,
            'status' => '0',
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($this->admin(), 'web')->get('/admin/campaign/manage');

        $response->assertOk();
        $response->assertSee('QA Phase4 List Product');
        $response->assertSee($template->name);
        $response->assertSee('Nov 01, 2026');
        $response->assertSee('Nov 30, 2026');
        $response->assertSee(route('campaign.preview', $active->id), false);
        $response->assertSee(route('campaign', $active->slug), false);
        $response->assertDontSee(route('campaign', $draft->slug), false);

        $html = $response->getContent();
        $activePosition = strpos($html, 'QA Phase4 List Active');
        $draftPosition = strpos($html, 'QA Phase4 List Draft');

        $this->assertNotFalse($activePosition, 'The active campaign is missing from the list.');
        $this->assertNotFalse($draftPosition, 'The draft campaign is missing from the list.');
        $this->assertLessThan($draftPosition, $activePosition, 'Campaigns are not ordered by sort_order.');
    }
}
