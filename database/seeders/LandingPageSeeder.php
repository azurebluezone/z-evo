<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\Template;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    /**
     * Two demo landing pages so the admin list, the public template render and
     * the sort order have real rows after a fresh install.
     *
     * DEMO CONTENT: both pages use the Default template and point their CTA at
     * the storefront root. Replace the copy before publishing a real campaign.
     * Idempotent by slug, so re-running does not duplicate rows.
     */
    public function run(): void
    {
        $template = Template::where('view_name', 'default')->first();

        if (! $template) {
            $this->command->warn('TemplateSeeder must run before LandingPageSeeder.');

            return;
        }

        $pages = [
            [
                'slug' => 'demo-default-offer',
                'title' => 'Demo Default Offer',
                'subtitle' => 'A starter landing page for the Default template.',
                'description' => 'Use this page as the copy-and-edit example for a campaign.',
                'cta_text' => 'Order Now',
                'cta_link' => url('/'),
                'extra_fields' => ['old_price' => 1200, 'new_price' => 899],
                'sort_order' => 1,
            ],
            [
                'slug' => 'demo-second-offer',
                'title' => 'Demo Second Offer',
                'subtitle' => 'A second row so sorting and listing are visible.',
                'description' => 'Both demo pages use the Default template on purpose: the other templates stay preview-only until their copy exists.',
                'cta_text' => 'Shop Now',
                'cta_link' => url('/'),
                'extra_fields' => ['old_price' => 1500, 'new_price' => 1099],
                'sort_order' => 2,
            ],
        ];

        foreach ($pages as $page) {
            LandingPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page + [
                    'template_id' => $template->id,
                    'status' => 1,
                ]
            );
        }

        $this->command->info('Landing pages seeded: ' . count($pages));
    }
}
