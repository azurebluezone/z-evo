<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Support\Str;

class CampaignDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first product for campaign
        $product = Product::first();

        if (!$product) {
            $this->command->error('No product found. Please create a product first.');
            return;
        }

        $campaigns = [
            [
                'name' => 'Premium Wallet Collection 2024',
                'slug' => 'premium-wallet-collection-2024',
                'short_description' => '<p><strong>🎉 Exclusive Offer:</strong> Get our premium leather wallets at <span style="color: #e74c3c; font-weight: bold;">50% OFF</span>! Limited time only.</p><p>✨ <strong>Features:</strong></p><ul><li>Genuine leather material</li><li>Multiple card slots</li><li>Coin pocket</li><li>RFID protection</li></ul>',
                'review' => '4.8/5 (500+ Reviews)',
                'description' => '<h2>Premium Wallet Collection 2024</h2><p>Discover our exclusive collection of premium leather wallets designed for the modern professional. Each wallet is crafted with the finest materials and attention to detail.</p><h3>🌟 Why Choose Our Wallets?</h3><ul><li><strong>Premium Quality:</strong> Made from genuine leather</li><li><strong>Smart Design:</strong> Multiple compartments for organization</li><li><strong>RFID Protection:</strong> Keep your cards safe</li><li><strong>Durable:</strong> Built to last for years</li></ul><h3>🎁 Special Features</h3><p>Our wallets come with:</p><ul><li>Coin pocket with zipper</li><li>6+ card slots</li><li>Bill compartment</li><li>ID window</li><li>Key holder</li></ul><h3>💎 Limited Time Offer</h3><p>Don\'t miss this amazing opportunity! Order now and get:</p><ul><li>✅ 50% discount</li><li>✅ Free shipping</li><li>✅ 30-day money back guarantee</li><li>✅ Lifetime warranty</li></ul>',
                'image_one' => 'public/uploads/campaign/demo-wallet-1.webp',
                'image_two' => 'public/uploads/campaign/demo-wallet-2.webp',
                'image_three' => 'public/uploads/campaign/demo-wallet-3.webp',
                'status' => '1',
                'product_id' => $product->id,
                'campaign_type' => 'product',
                'hero_title' => 'Premium Wallet Collection 2024',
                'hero_subtitle' => 'Discover luxury and functionality in every detail. Our premium leather wallets are designed for the modern professional who demands both style and substance.',
                'hero_button_text' => 'Order Now - 50% OFF',
                'show_features' => true,
                'show_benefits' => true,
                'show_testimonials' => true,
                'show_faq' => true,
                'show_cta' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'start_date' => '2024-12-01',
                'end_date' => '2024-12-31',
            ],
            [
                'name' => 'Smart Watch Pro Series',
                'slug' => 'smart-watch-pro-series',
                'short_description' => '<p><strong>🚀 New Launch:</strong> Smart Watch Pro Series with <span style="color: #3498db; font-weight: bold;">advanced health monitoring</span>!</p><p>📱 <strong>Smart Features:</strong></p><ul><li>Heart rate monitoring</li><li>GPS tracking</li><li>Water resistant</li><li>7-day battery life</li></ul>',
                'review' => '4.9/5 (300+ Reviews)',
                'description' => '<h2>Smart Watch Pro Series</h2><p>Experience the future of wearable technology with our Smart Watch Pro Series. Packed with advanced features to keep you connected and healthy.</p><h3>🔋 Key Features</h3><ul><li><strong>Health Monitoring:</strong> Heart rate, blood pressure, sleep tracking</li><li><strong>Fitness Tracking:</strong> GPS, step counter, calorie burn</li><li><strong>Smart Notifications:</strong> Calls, messages, social media alerts</li><li><strong>Water Resistant:</strong> IP68 rating for swimming</li></ul><h3>📊 Advanced Analytics</h3><p>Track your fitness journey with detailed analytics:</p><ul><li>Daily activity summary</li><li>Weekly progress reports</li><li>Goal setting and achievement</li><li>Social sharing features</li></ul><h3>🎯 Perfect For</h3><ul><li>🏃‍♂️ Fitness enthusiasts</li><li>💼 Busy professionals</li><li>👨‍👩‍👧‍👦 Health-conscious individuals</li><li>📱 Tech-savvy users</li></ul>',
                'image_one' => 'public/uploads/campaign/demo-watch-1.webp',
                'image_two' => 'public/uploads/campaign/demo-watch-2.webp',
                'image_three' => 'public/uploads/campaign/demo-watch-3.webp',
                'status' => '1',
                'product_id' => $product->id,
                'campaign_type' => 'product',
                'hero_title' => 'Smart Watch Pro Series',
                'hero_subtitle' => 'Stay connected, stay healthy, stay ahead. The future of wearable technology is here.',
                'hero_button_text' => 'Get Your Smart Watch',
                'show_features' => true,
                'show_benefits' => true,
                'show_testimonials' => true,
                'show_faq' => false,
                'show_cta' => true,
                'is_featured' => false,
                'sort_order' => 2,
                'start_date' => '2024-12-01',
                'end_date' => '2025-01-31',
            ],
            [
                'name' => 'Eco-Friendly Water Bottle',
                'slug' => 'eco-friendly-water-bottle',
                'short_description' => '<p><strong>🌱 Go Green:</strong> Eco-friendly water bottles that <span style="color: #27ae60; font-weight: bold;">save the planet</span>!</p><p>♻️ <strong>Eco Benefits:</strong></p><ul><li>BPA-free material</li><li>Reusable design</li><li>Keeps drinks cold for 24h</li><li>Reduces plastic waste</li></ul>',
                'review' => '4.7/5 (200+ Reviews)',
                'description' => '<h2>Eco-Friendly Water Bottle</h2><p>Make a difference with our eco-friendly water bottles. Every purchase helps reduce plastic waste and protect our environment.</p><h3>🌍 Environmental Impact</h3><ul><li><strong>Plastic Reduction:</strong> Save 365 plastic bottles per year</li><li><strong>Sustainable Materials:</strong> Made from recycled materials</li><li><strong>Carbon Footprint:</strong> 50% less CO2 emissions</li><li><strong>Biodegradable:</strong> Safe for the environment</li></ul><h3>💧 Perfect Performance</h3><p>Our bottles are designed for optimal performance:</p><ul><li>Keeps drinks cold for 24 hours</li><li>Keeps drinks hot for 12 hours</li><li>Leak-proof design</li><li>Easy to clean</li><li>Dishwasher safe</li></ul><h3>🎨 Beautiful Design</h3><p>Available in multiple colors and sizes:</p><ul><li>500ml - Perfect for daily use</li><li>750ml - Great for workouts</li><li>1L - Ideal for long trips</li></ul>',
                'image_one' => 'public/uploads/campaign/demo-bottle-1.webp',
                'image_two' => 'public/uploads/campaign/demo-bottle-2.webp',
                'image_three' => 'public/uploads/campaign/demo-bottle-3.webp',
                'status' => '1',
                'product_id' => $product->id,
                'campaign_type' => 'promotional',
                'hero_title' => 'Eco-Friendly Water Bottle',
                'hero_subtitle' => 'Join the movement to save our planet. Every bottle counts in the fight against plastic pollution.',
                'hero_button_text' => 'Save the Planet',
                'show_features' => true,
                'show_benefits' => true,
                'show_testimonials' => false,
                'show_faq' => true,
                'show_cta' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'start_date' => '2024-12-01',
                'end_date' => '2024-12-31',
            ]
        ];

        foreach ($campaigns as $campaignData) {
            Campaign::create($campaignData);
        }

        $this->command->info('Demo campaigns created successfully!');
    }
}
