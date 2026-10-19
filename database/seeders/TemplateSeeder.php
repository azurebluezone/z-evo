<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed if no templates exist
        if (Template::count() > 0) {
            $this->command->info('Templates already exist. Skipping seeder.');
            return;
        }

        $templates = [
            [
                'name' => 'Default',
                'description' => 'Simple hero + content layout with clean design',
                'view_name' => 'default',
                'is_active' => true,
                'config' => json_encode([
                    'extra_fields' => ['old_price', 'new_price']
                ]),
            ],
            [
                'name' => 'Herbal Health',
                'description' => 'Green herbal product layout with benefits and testimonials sections',
                'view_name' => 'herbal-health',
                'is_active' => true,
                'config' => json_encode([
                    'extra_fields' => ['old_price', 'new_price', 'show_benefits', 'show_testimonials', 'benefits', 'testimonials']
                ]),
            ],
            [
                'name' => 'Product Countdown (Template 1)',
                'description' => 'Countdown timer style product page with features list',
                'view_name' => 'template-1',
                'is_active' => true,
                'config' => json_encode([
                    'extra_fields' => ['old_price', 'new_price', 'countdown_date', 'features', 'why_us', 'contact_phone']
                ]),
            ],
            [
                'name' => 'Modern Product (Template 2)',
                'description' => 'Modern side-by-side hero layout with feature cards grid',
                'view_name' => 'template-2',
                'is_active' => true,
                'config' => json_encode([
                    'extra_fields' => ['old_price', 'new_price', 'countdown_date', 'features', 'why_us', 'contact_phone']
                ]),
            ],
            [
                'name' => 'Brand Product (Template 3)',
                'description' => 'Premium brand product layout with video, gallery swiper, and package options',
                'view_name' => 'template-3',
                'is_active' => true,
                'config' => json_encode([
                    'extra_fields' => ['old_price', 'new_price', 'video_url', 'features', 'benefits', 'gallery_images', 'packages', 'contact_phone', 'email', 'social_links']
                ]),
            ],
            [
                'name' => 'Sample Template',
                'description' => 'Flexible template with dynamic sections for features, benefits, testimonials, and FAQs',
                'view_name' => 'sample-template',
                'is_active' => true,
                'config' => json_encode([
                    'extra_fields' => ['old_price', 'new_price', 'features', 'benefits', 'testimonials', 'faqs']
                ]),
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }

        $this->command->info('Templates seeded successfully!');
    }
}
