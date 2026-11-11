<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Tests\TestCase;

/**
 * The shared landing order form is part of layouts.templates, so every one of
 * the nine designs must expose exactly one working form. The admin preview and
 * the public campaign page are checked separately because editors order from
 * the preview and customers order from the public URL.
 */
class LandingOrderFormTest extends TestCase
{
    private const DESIGN_VIEWS = ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'];

    public function test_admin_campaign_preview_contains_the_working_order_form(): void
    {
        $campaign = Campaign::with('template', 'product')->find(806);

        if (!$campaign || !$campaign->product || !$campaign->template) {
            $this->markTestSkipped('Campaign 806 with its product/template is not present in this database.');
        }

        $response = $this->actingAs($this->admin())->get('/admin/campaign/' . $campaign->id . '/preview');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, preg_match_all('~action="[^"]*/landing-order"~', $html), 'The preview must contain exactly one landing order form.');
        $this->assertSame(1, substr_count($html, 'id="landing-order"'));
        $this->assertStringContainsString('name="product_id" value="' . $campaign->product_id . '"', $html);
        $this->assertStringContainsString('name="campaign_id" value="' . $campaign->id . '"', $html);
        $this->assertStringContainsString('name="quantity"', $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_every_public_design_campaign_has_exactly_one_order_form(): void
    {
        $campaigns = Campaign::with('template')
            ->where('status', 1)
            ->whereHas('template', fn ($query) => $query->whereIn('view_name', self::DESIGN_VIEWS))
            ->orderBy('id')
            ->get()
            ->keyBy(fn (Campaign $campaign) => $campaign->template->view_name);

        foreach (self::DESIGN_VIEWS as $view) {
            $this->assertTrue($campaigns->has($view), 'No active campaign exists for design ' . $view . '.');
        }

        foreach ($campaigns as $view => $campaign) {
            $response = $this->get('/campaign/' . $campaign->slug);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertSame(
                1,
                preg_match_all('~action="[^"]*/landing-order"~', $html),
                $view . ' must contain exactly one landing order form.'
            );
            $this->assertSame(1, substr_count($html, 'id="landing-order"'), $view . ' order section id is missing or duplicated.');
            $this->assertStringContainsString('name="product_id" value="' . $campaign->product_id . '"', $html);
            $this->assertStringContainsString('name="campaign_id" value="' . $campaign->id . '"', $html);
        }
    }

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user exists, so the preview request cannot authenticate.');

        return $admin;
    }
}
