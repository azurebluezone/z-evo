<?php

namespace Tests\Feature;

use App\Models\SocialMedia;
use App\Models\User;
use Tests\TestCase;

/**
 * Footer social icons.
 *
 * The admin form has always posted `link` and `color`, and the storefront
 * footer has always read `$social->link` - but `social_media` only ever had
 * title/icon/status, so both fields were dropped on save and every icon in the
 * footer pointed at "#". These tests pin the columns, the controller writes and
 * the rendered storefront link.
 */
class AdminFooterSocialTest extends TestCase
{
    private const TITLE_PREFIX = 'QA Footer Social';

    protected function tearDown(): void
    {
        SocialMedia::where('title', 'like', self::TITLE_PREFIX . '%')->delete();

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the footer cannot be authenticated.');

        return $admin;
    }

    private function footerUrl(): string
    {
        return route('admin.website-setup.footer');
    }

    public function test_footer_page_renders_the_social_form_and_table(): void
    {
        $response = $this->actingAs($this->admin())->get($this->footerUrl());

        $response->assertOk();
        $response->assertSee('Social icons');
        $response->assertSee('Add icon');
        $response->assertSee('name="link"', false);

        // The rebuilt screen is one shared form: the icon picker renders the
        // real catalog, and the links repeater posts into the same request.
        $response->assertSee('data-icon-value="fab fa-facebook-f"', false);
        $response->assertSee('id="footer-about-count"', false);
        $response->assertSee('id="footerLinkCountBadge"', false);
        $response->assertSee('form="footerContentAdminForm"', false);
        $response->assertSee('name="footer_links_present"', false);
    }

    public function test_creating_an_icon_stores_link_and_colour(): void
    {
        $response = $this->actingAs($this->admin())->post(route('socialmedias.store'), [
            'title' => self::TITLE_PREFIX . ' Create',
            'icon' => 'fab fa-facebook',
            'link' => 'https://facebook.com/qa-footer-create',
            'color' => '#123456',
            'status' => '1',
            'redirect_to' => $this->footerUrl(),
        ]);

        $response->assertRedirect($this->footerUrl());
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('social_media', [
            'title' => self::TITLE_PREFIX . ' Create',
            'link' => 'https://facebook.com/qa-footer-create',
            'color' => '#123456',
            'status' => 1,
        ]);
    }

    public function test_an_unchecked_status_saves_as_inactive(): void
    {
        $response = $this->actingAs($this->admin())->post(route('socialmedias.store'), [
            'title' => self::TITLE_PREFIX . ' Inactive',
            'icon' => 'fab fa-x-twitter',
            'link' => 'https://x.com/qa-footer-inactive',
            'color' => '#000000',
            'redirect_to' => $this->footerUrl(),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('social_media', [
            'title' => self::TITLE_PREFIX . ' Inactive',
            'status' => 0,
        ]);
    }

    public function test_updating_an_icon_persists_the_new_link_and_colour(): void
    {
        $social = SocialMedia::create([
            'title' => self::TITLE_PREFIX . ' Update',
            'icon' => 'fab fa-facebook',
            'link' => 'https://facebook.com/qa-before',
            'color' => '#111111',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin())->post(route('socialmedias.update'), [
            'id' => $social->id,
            'title' => self::TITLE_PREFIX . ' Updated',
            'icon' => 'fab fa-instagram',
            'link' => 'https://instagram.com/qa-after',
            'color' => '#654321',
            'status' => '1',
            'redirect_to' => $this->footerUrl(),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('social_media', [
            'id' => $social->id,
            'title' => self::TITLE_PREFIX . ' Updated',
            'link' => 'https://instagram.com/qa-after',
            'color' => '#654321',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get($this->footerUrl());
        $response->assertSee('https://instagram.com/qa-after', false);
    }

    public function test_the_storefront_footer_links_to_the_saved_url(): void
    {
        SocialMedia::create([
            'title' => self::TITLE_PREFIX . ' Storefront',
            'icon' => 'fab fa-facebook',
            'link' => 'https://facebook.com/qa-footer-storefront',
            'color' => '#1877f2',
            'status' => 1,
        ]);

        $this->assertDatabaseHas('social_media', [
            'title' => self::TITLE_PREFIX . ' Storefront',
            'link' => 'https://facebook.com/qa-footer-storefront',
            'status' => 1,
        ]);

        // AppServiceProvider shares $socialicons at boot, and a PHPUnit
        // process keeps one booted app across tests. Reboot so this request
        // sees the row created above instead of the boot-time snapshot.
        $this->refreshApplication();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('https://facebook.com/qa-footer-storefront', false);
    }
}
