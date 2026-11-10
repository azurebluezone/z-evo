<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Admin landing-page slice.
 *
 * Regressions pinned here:
 *
 *   1. landing-pages/update had no {id} segment, so the edit form's
 *      route('landing-pages.update', $id) call threw UrlGenerationException.
 *   2. the View button pointed at backEnd.landing-pages.show, which did not
 *      exist, so every click was a 500.
 *   3. templateConfig ran json_decode() on an `array` cast, so the template
 *      picker received null config and drew no extra fields.
 *
 * Every row this test creates is prefixed qa-landing- and removed afterwards.
 */
class LandingPageAdminTest extends TestCase
{
    private const SLUG_PREFIX = 'qa-landing-';

    protected function tearDown(): void
    {
        LandingPage::where('slug', 'like', self::SLUG_PREFIX . '%')->delete();

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so this test cannot authenticate.');

        return $admin;
    }

    private function template(): Template
    {
        return Template::query()->firstOrCreate(
            ['view_name' => 'default'],
            [
                'name' => 'Default',
                'description' => 'Test template',
                'is_active' => true,
                'config' => ['extra_fields' => ['old_price', 'new_price']],
            ]
        );
    }

    private function landingPage(): LandingPage
    {
        return LandingPage::create([
            'title' => 'QA Landing Page',
            'subtitle' => 'QA subtitle',
            'description' => 'QA description',
            'cta_text' => 'Order Now',
            'cta_link' => url('/'),
            'template_id' => $this->template()->id,
            'slug' => self::SLUG_PREFIX . Str::random(8),
            'status' => true,
            'sort_order' => 99,
            'extra_fields' => ['old_price' => 1200, 'new_price' => 899],
        ]);
    }

    public function test_index_lists_landing_pages_and_offers_create(): void
    {
        $page = $this->landingPage();

        $response = $this->actingAs($this->admin())->get('/admin/landing-pages/manage');

        $response->assertOk();
        $response->assertSee($page->title);
        $response->assertSee(route('landing-pages.create'), false);
        $response->assertSee(route('landing-pages.show', $page->id), false);
    }

    public function test_show_page_renders_the_page_and_its_template(): void
    {
        $page = $this->landingPage();

        $response = $this->actingAs($this->admin())->get('/admin/landing-pages/' . $page->id . '/show');

        $response->assertOk();
        $response->assertSee('Landing Page Detail');
        $response->assertSee($page->title);
        $response->assertSee($this->template()->name);
        $response->assertSee('old_price');
    }

    public function test_update_route_accepts_the_id_and_persists_it(): void
    {
        $page = $this->landingPage();

        $response = $this->actingAs($this->admin())->post('/admin/landing-pages/update/' . $page->id, [
            'title' => 'QA Landing Page Updated',
            'subtitle' => 'Updated subtitle',
            'description' => 'Updated description',
            'cta_text' => 'Buy Now',
            'cta_link' => url('/checkout'),
            'template_id' => $this->template()->id,
            'status' => 1,
        ]);

        $response->assertRedirect(route('landing-pages.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('landing_pages', [
            'id' => $page->id,
            'title' => 'QA Landing Page Updated',
            'cta_text' => 'Buy Now',
        ]);
    }

    public function test_template_config_returns_the_cast_array(): void
    {
        $template = $this->template();

        $response = $this->actingAs($this->admin())
            ->getJson('/admin/landing-pages/template-config/' . $template->id);

        $response->assertOk();
        $response->assertJsonPath('view_name', 'default');
        $response->assertJsonPath('config.extra_fields.0', 'old_price');
    }

    /**
     * The public index used to request frontEnd.landing-pages.index (wrong case
     * on a case-sensitive filesystem, and the file on disk was .blade.html),
     * so the listing half of the module was a 500 while the detail half worked.
     */
    public function test_public_index_lists_active_landing_pages(): void
    {
        $page = $this->landingPage();

        $response = $this->get('/landing-pages');

        $response->assertOk();
        $response->assertSee($page->title);
        $response->assertSee(route('landing.show', $page->slug), false);
    }
}
