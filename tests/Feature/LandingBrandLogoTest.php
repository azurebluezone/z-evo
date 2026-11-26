<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\GeneralSetting;
use App\Models\User;
use Tests\TestCase;

/**
 * Brand identity on the campaign list and on the nine landing designs.
 *
 * The landing header used to print the shop name as text next to a coloured
 * letter tile. The real application logo (general_settings.white_logo, with
 * the tracked public/branding/logo.png as fallback) is shown instead, and the
 * shared order card carries the same logo. The admin campaign list must not
 * print a second line under the campaign name either.
 */
class LandingBrandLogoTest extends TestCase
{
    use EnsuresDesignCampaigns;

    private const DESIGN_VIEWS = ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'];

    protected function tearDown(): void
    {
        $this->cleanupDesignCampaignFixtures();
        parent::tearDown();
    }

    public function test_admin_campaign_list_has_no_subtitle_under_the_campaign_name(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/campaign/manage');

        $response->assertOk();
        $html = $response->getContent();

        // The slug line under each campaign name is gone.
        $this->assertStringNotContainsString('text-body-secondary small text-truncate', $html);
        // The description paragraph under the "Campaign Manager" heading is gone.
        $this->assertStringNotContainsString('Product landing pages built from', $html);
        // The list itself still renders every campaign.
        $this->assertStringContainsString('/admin/campaign/', $html);
        $this->assertStringContainsString('Campaign Manager', $html);
    }

    public function test_every_design_header_shows_the_application_logo_instead_of_the_static_name(): void
    {
        $logoSrc = $this->configuredLogoUrl();

        foreach ($this->campaignsByDesign() as $view => $campaign) {
            $response = $this->get('/campaign/' . $campaign->slug);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString(
                'class="zlt-brand-logo"',
                $html,
                $view . ' must show the application logo in the header.'
            );
            $this->assertStringContainsString(
                'src="' . $logoSrc . '"',
                $html,
                $view . ' must point the header logo at the configured logo file.'
            );

            // The typed-out brand name block and its letter tile are gone.
            $this->assertStringNotContainsString('zlt-brand-mark', $html, $view . ' still renders the letter tile.');
            $this->assertStringNotContainsString('zlt-brand-sub', $html, $view . ' still renders the static name block.');
        }
    }

    public function test_every_design_order_section_carries_the_logo_and_one_working_form(): void
    {
        $logoSrc = $this->configuredLogoUrl();

        foreach ($this->campaignsByDesign() as $view => $campaign) {
            $response = $this->get('/campaign/' . $campaign->slug);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString(
                'class="zlt-order-logo"',
                $html,
                $view . ' order card must carry the application logo.'
            );
            $this->assertStringContainsString('src="' . $logoSrc . '"', $html);

            $this->assertSame(
                1,
                preg_match_all('~action="[^"]*/landing-order"~', $html),
                $view . ' must contain exactly one landing order form.'
            );
            $this->assertSame(1, substr_count($html, 'id="landing-order"'));
            $this->assertStringContainsString('name="product_id" value="' . $campaign->product_id . '"', $html);
            $this->assertStringContainsString('name="campaign_id" value="' . $campaign->id . '"', $html);
            $this->assertStringContainsString('name="quantity"', $html);
            $this->assertStringContainsString('name="_token"', $html);

            // The running total is announced when the quantity changes.
            $this->assertStringContainsString('id="zlt-order-total" role="status" aria-live="polite"', $html);
        }
    }

    private function configuredLogoUrl(): string
    {
        $settings = GeneralSetting::first();

        $this->assertNotNull($settings, 'No general settings row exists, so no logo can be configured.');
        $this->assertNotEmpty($settings->white_logo, 'No logo is configured in the general settings.');

        return asset(preg_replace('~^public/~', '', (string) $settings->white_logo));
    }

    /**
     * @return \Illuminate\Support\Collection<string, Campaign>
     */
    private function campaignsByDesign()
    {
        $campaigns = collect();

        foreach (self::DESIGN_VIEWS as $view) {
            $campaigns->put($view, $this->activeCampaignForDesign($view));
        }

        return $campaigns;
    }

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user exists, so the campaign list cannot be opened.');

        return $admin;
    }
}
