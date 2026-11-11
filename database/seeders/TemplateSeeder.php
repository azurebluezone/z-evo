<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Landing-page templates, keyed by view_name.
     *
     * updateOrCreate makes this safe to re-run. It also repairs rows written
     * by the old version, which passed json_encode($array) into an `array`
     * cast, so the JSON was encoded twice.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Default',
                'description' => 'Simple hero + content layout with clean design',
                'view_name' => 'default',
                'is_active' => false,
                'config' => [
                    'extra_fields' => ['old_price', 'new_price'],
                ],
            ],
            [
                'name' => 'Herbal Health',
                'description' => 'Green herbal product layout with benefits and testimonials sections',
                'view_name' => 'herbal-health',
                'is_active' => false,
                'config' => [
                    'extra_fields' => ['old_price', 'new_price', 'show_benefits', 'show_testimonials', 'benefits', 'testimonials'],
                ],
            ],
            [
                'name' => 'Product Countdown (Template 1)',
                'description' => 'Countdown timer style product page with features list',
                'view_name' => 'template-1',
                'is_active' => false,
                'config' => [
                    'extra_fields' => ['old_price', 'new_price', 'countdown_date', 'features', 'why_us', 'contact_phone'],
                ],
            ],
            [
                'name' => 'Modern Product (Template 2)',
                'description' => 'Modern side-by-side hero layout with feature cards grid',
                'view_name' => 'template-2',
                'is_active' => false,
                'config' => [
                    'extra_fields' => ['old_price', 'new_price', 'countdown_date', 'features', 'why_us', 'contact_phone'],
                ],
            ],
            [
                'name' => 'Brand Product (Template 3)',
                'description' => 'Premium brand product layout with video, gallery swiper, and package options',
                'view_name' => 'template-3',
                'is_active' => false,
                'config' => [
                    'extra_fields' => ['old_price', 'new_price', 'video_url', 'features', 'benefits', 'gallery_images', 'packages', 'contact_phone', 'email', 'social_links'],
                ],
            ],
            [
                'name' => 'Sample Template',
                'description' => 'Flexible template with dynamic sections for features, benefits, testimonials, and FAQs',
                'view_name' => 'sample-template',
                'is_active' => false,
                'config' => [
                    'extra_fields' => ['old_price', 'new_price', 'features', 'benefits', 'testimonials', 'faqs'],
                ],
            ],
        ];

        // New landing designs. All nine have converted Blade views and real
        // local assets, so they are active. The six legacy rows above are
        // deactivated because their CSS/JS files were removed with the old
        // designs and a campaign on them renders a broken page.
        $registry = (array) config('landing_templates', []);

        foreach ((array) ($registry['templates'] ?? []) as $key => $definition) {
            $enabled = [];

            foreach ((array) ($definition['default_groups'] ?? []) as $groupKey) {
                $enabled = array_merge(
                    $enabled,
                    array_keys((array) ($registry['groups'][$groupKey]['variables'] ?? []))
                );
            }

            $templates[] = [
                'name' => $definition['name'] ?? $key,
                'description' => 'New landing design. Variable map is managed from Landing Pages → Variables.',
                'view_name' => $definition['view'] ?? $key,
                'is_active' => true,
                'config' => [
                    'landing_template' => $key,
                    'enabled_variables' => array_values(array_unique($enabled)),
                    'extra_fields' => [],
                ],
            ];
        }

        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['view_name' => $template['view_name']],
                $template
            );
        }

        $this->command->info('Templates seeded: ' . count($templates));
    }
}
