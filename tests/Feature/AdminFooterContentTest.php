<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Footer content + managed links.
 *
 * The admin footer screen is one shared form: the about editor and the
 * contact fields live in the card form, the link repeater attaches to it
 * through the HTML5 `form` attribute. These tests pin the write path that
 * both Save buttons use.
 */
class AdminFooterContentTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_it_saves_about_text_contact_and_managed_links(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.website-setup.footer.update'), [
            'footer_about_text' => '<p><strong>Fresh</strong> copy</p><script>alert(1)</script>'
                . '<a href="javascript:alert(1)">x</a>',
            'copyright' => '© QA Footer',
            'contact_address' => 'QA Road 1',
            'contact_phone' => '+8801000000000',
            'contact_email' => 'qa-footer@example.com',
            'footer_links_present' => '1',
            'footer_links' => [
                ['group' => 'terms', 'label' => 'Privacy', 'url' => '/privacy', 'new_tab' => '1'],
                // Blank rows are skipped by the server, not stored as empty links.
                ['group' => 'site', 'label' => '', 'url' => ''],
                ['group' => 'other', 'label' => 'Mail us', 'url' => 'https://example.test/contact'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $settings = GeneralSetting::query()->first();
        $this->assertNotNull($settings);

        $this->assertStringContainsString('<strong>Fresh</strong>', (string) $settings->footer_about_text);
        $this->assertStringNotContainsString('<script', (string) $settings->footer_about_text);
        $this->assertStringNotContainsString('javascript:', (string) $settings->footer_about_text);

        $links = $settings->footer_links;
        $this->assertIsArray($links);
        $this->assertCount(2, $links);

        $this->assertSame('terms', $links[0]['group']);
        $this->assertSame('Privacy', $links[0]['label']);
        $this->assertSame('/privacy', $links[0]['url']);
        $this->assertSame(1, $links[0]['new_tab']);

        $this->assertSame('other', $links[1]['group']);
        $this->assertSame('Mail us', $links[1]['label']);
        $this->assertSame('https://example.test/contact', $links[1]['url']);
        $this->assertSame(0, $links[1]['new_tab']);

        $this->assertDatabaseHas('contacts', [
            'address' => 'QA Road 1',
            'phone' => '+8801000000000',
            'email' => 'qa-footer@example.com',
            'status' => 1,
        ]);

        $page = $this->actingAs($this->admin())->get($this->footerUrl());
        $page->assertOk();
        $page->assertSee('Privacy');
        $page->assertSee('Mail us');
        $page->assertSee('footer_links_present', false);
    }

    public function test_marker_only_clears_the_stored_links(): void
    {
        $settings = GeneralSetting::query()->first();
        $settings->update([
            'footer_links' => [
                ['group' => 'site', 'label' => 'Old', 'url' => '/old', 'new_tab' => 0],
            ],
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.website-setup.footer.update'), [
            'footer_links_present' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame([], $settings->fresh()->footer_links);
    }

    public function test_about_text_over_400_visible_characters_is_rejected(): void
    {
        $settings = GeneralSetting::query()->first();
        $before = (string) $settings->footer_about_text;

        $response = $this->actingAs($this->admin())->post(route('admin.website-setup.footer.update'), [
            'footer_about_text' => '<p>' . str_repeat('a', 401) . '</p>',
        ]);

        $response->assertSessionHasErrors('footer_about_text');
        $this->assertSame($before, (string) $settings->fresh()->footer_about_text);
    }
}
