<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'General: Welcome',
                'title' => 'Welcome',
                'body' => "Welcome to our store. We're happy to have you.",
                'is_active' => true,
            ],
            [
                'name' => 'General: Offer',
                'title' => 'Special Offer',
                'body' => 'Check out our latest offers and discounts.',
                'is_active' => true,
            ],
            [
                'name' => 'Support: We Received Your Request',
                'title' => 'Request received',
                'body' => 'We have received your request. Our team will contact you soon.',
                'is_active' => true,
            ],
            [
                'name' => 'Support: Request Approved',
                'title' => 'Request approved',
                'body' => 'Your request has been approved. Thank you.',
                'is_active' => true,
            ],
            [
                'name' => 'Support: Request Pending',
                'title' => 'Request pending',
                'body' => 'Your request is pending review. We will update you soon.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            NotificationTemplate::query()->firstOrCreate(
                ['name' => $tpl['name']],
                [
                    'title' => $tpl['title'],
                    'body' => $tpl['body'],
                    'is_active' => $tpl['is_active'],
                ]
            );
        }
    }
}
