<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Product;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * One live campaign per new landing design.
 *
 * The nine designs are the only templates with real converted Blade views.
 * Every campaign carries its own hero, trust, comparison, review, FAQ and
 * variant content inside `extra_fields`; the product row supplies the name,
 * price and order-form product id. Re-running updates the same slug.
 */
class LandingCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $designs = [
            'ht2' => [
                'name' => 'প্রিমিয়াম পাঞ্জাবি কালেকশন — HT2',
                'slug' => 'ht2-premium-collection',
                'product_id' => 5,
                'hero_title' => 'প্রিমিয়াম পাঞ্জাবি কালেকশন',
                'hero_subtitle' => 'হাতে বাছাই করা প্রিমিয়াম ফ্যাব্রিক, রাজকীয় ফিনিশিং আর আরামদায়ক ফিট—উৎসবের জন্য একেবারে উপযুক্ত।',
                'hero_badge' => '১০০% অরিজিনাল প্রোডাক্ট',
                'announce_text' => 'সীমিত সময়ের বিশেষ অফার—আজই অর্ডার করুন!',
                'announce_highlight' => 'ধামাকা অফার',
                'sort_order' => 1,
            ],
            't1' => [
                'name' => 'আজকের ফানেল অফার — T1',
                'slug' => 't1-funnel-offer',
                'product_id' => 5,
                'hero_title' => 'আজকের ফানেল অফার',
                'hero_subtitle' => 'এক ক্লিকে অর্ডার করুন, ক্যাশ অন ডেলিভারিতে পণ্য হাতে পেয়ে টাকা পরিশোধ করুন।',
                'hero_badge' => 'সীমিত স্টক',
                'announce_text' => 'প্রতিদিন প্রথম ৫০ জন ক্রেতার জন্য বিশেষ মূল্য',
                'announce_highlight' => 'আজকের ডিল',
                'sort_order' => 2,
            ],
            'th3' => [
                'name' => 'প্রিমিয়াম ওয়াল আর্ট — TH3',
                'slug' => 'th3-premium-wall-art',
                'product_id' => 1,
                'hero_title' => 'প্রিমিয়াম ওয়াল আর্ট কালেকশন',
                'hero_subtitle' => 'ঘরের দেয়ালে আধুনিক ছোঁয়া—উচ্চ রেজোলিউশন প্রিন্ট ও টেকসই ফ্রেম।',
                'hero_badge' => 'ভিডিওতে দেখে নিন',
                'announce_text' => 'ভিডিও দেখে পছন্দ হলে আজই অর্ডার করুন',
                'announce_highlight' => 'নতুন কালেকশন',
                'sort_order' => 3,
            ],
            'th4' => [
                'name' => 'খাঁটি আর্টিজান মধু — TH4',
                'slug' => 'th4-artisan-honey',
                'product_id' => 14,
                'hero_title' => 'খাঁটি আর্টিজান মধু',
                'hero_subtitle' => 'প্রাকৃতিক ফুলের মধু, কোনো চিনি বা প্রিজারভেটিভ ছাড়া—পরিবারের জন্য নিরাপদ ও পুষ্টিকর।',
                'hero_badge' => 'ল্যাব টেস্টেড',
                'announce_text' => 'সীমিত সময়ের ধামাকা অফার: আজই অর্ডার করলে ফ্রি ডেলিভারি!',
                'announce_highlight' => 'ফ্রি ডেলিভারি',
                'sort_order' => 4,
            ],
            'th5' => [
                'name' => 'প্রাকৃতিক খাঁটি মধু — TH5',
                'slug' => 'th5-natural-honey',
                'product_id' => 14,
                'hero_title' => 'প্রাকৃতিক খাঁটি মধু',
                'hero_subtitle' => 'সরাসরি চাষির কাছ থেকে সংগৃহীত, প্রাকৃতিক স্বাদ ও সুবাসে ভরপুর।',
                'hero_badge' => '১০০% ন্যাচারাল',
                'announce_text' => 'প্রকৃতির উপহার—আজকের বিশেষ মূল্যে অর্ডার করুন',
                'announce_highlight' => 'ন্যাচারাল চয়েস',
                'sort_order' => 5,
            ],
            'th6' => [
                'name' => 'রোজ এডিটোরিয়াল আবায়া — TH6',
                'slug' => 'th6-rose-editorial-abaya',
                'product_id' => 6,
                'hero_title' => 'রোজ এডিটোরিয়াল আবায়া',
                'hero_subtitle' => 'নরম কাপড়, এলিগ্যান্ট ড্রেপ আর আরামদায়ক ফিট—প্রতিদিনের সৌন্দর্যে নতুন মাত্রা।',
                'hero_badge' => 'প্রিমিয়াম ফ্যাব্রিক',
                'announce_text' => 'নতুন রোজ কালেকশন—সীমিত সময়ের বিশেষ ছাড়',
                'announce_highlight' => 'নতুন কালেকশন',
                'sort_order' => 6,
            ],
            'th7' => [
                'name' => 'ফ্যাশন এডিটোরিয়াল আবায়া — TH7',
                'slug' => 'th7-fashion-editorial-abaya',
                'product_id' => 6,
                'hero_title' => 'ফ্যাশন এডিটোরিয়াল আবায়া',
                'hero_subtitle' => 'মডার্ন সিলুয়েট, প্রিমিয়াম ফিনিশিং—যেকোনো অনুষ্ঠানে আত্মবিশ্বাসী লুক।',
                'hero_badge' => 'এক্সক্লুসিভ কালেকশন',
                'announce_text' => 'এক্সক্লুসিভ কালেকশনে আজকের বিশেষ অফার',
                'announce_highlight' => 'লিমিটেড এডিশন',
                'sort_order' => 7,
            ],
            'th8' => [
                'name' => 'ডার্ক প্রিমিয়াম পাঞ্জাবি — TH8',
                'slug' => 'th8-dark-premium-panjabi',
                'product_id' => 5,
                'hero_title' => 'ডার্ক প্রিমিয়াম পাঞ্জাবি',
                'hero_subtitle' => 'গাঢ় রঙের প্রিমিয়াম পাঞ্জাবি—সূক্ষ্ম এমব্রয়ডারি ও রাজকীয় ফিনিশিং।',
                'hero_badge' => 'প্রিমিয়াম এডিশন',
                'announce_text' => 'প্রিমিয়াম এডিশনে সীমিত সময়ের ছাড়',
                'announce_highlight' => '৪০% পর্যন্ত ছাড়',
                'sort_order' => 8,
            ],
            'th9' => [
                'name' => 'সফট কমার্স উইন্টার হুডি — TH9',
                'slug' => 'th9-soft-commerce-hoodie',
                'product_id' => 2,
                'hero_title' => 'সফট কমার্স উইন্টার হুডি',
                'hero_subtitle' => 'নরম ভেতরের লাইনিং, আরামদায়ক ফিট—শীতের প্রতিদিনের জন্য উষ্ণ ও স্টাইলিশ।',
                'hero_badge' => 'শীতের আরাম',
                'announce_text' => 'শীতের কালেকশনে আজকের বিশেষ মূল্য',
                'announce_highlight' => 'শীতের অফার',
                'sort_order' => 9,
            ],
        ];

        $videoUrl = 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ';
        $galleryTail = [
            'frontend/landing/pictures/generated/wallet-front.jpg',
            'frontend/landing/pictures/generated/wallet-detail.jpg',
            'frontend/landing/pictures/generated/wallet-lifestyle.jpg',
        ];

        // Old demo campaigns pointed at the removed legacy templates. They are
        // deactivated rather than deleted so their order history stays intact.
        Campaign::query()
            ->whereIn('id', range(767, 772))
            ->update(['status' => 0]);

        foreach ($designs as $key => $design) {
            $template = Template::where('view_name', $key)->first();

            if (!$template) {
                $this->command->warn("Template {$key} not found; run TemplateSeeder first.");
                continue;
            }

            $product = Product::find($design['product_id'])
                ?? Product::where('status', 1)->first();

            if (!$product) {
                $this->command->warn('No product available; campaign seeding stopped.');
                return;
            }

            $price = (float) $product->new_price;
            $oldPrice = (float) $product->old_price;
            $offerPrice = max(1, (int) round($price * 0.9));
            $productName = (string) $product->name;
            $heroImage = 'frontend/landing/pictures/generated/' . $key . '-hero.jpg';
            $gallery = array_merge([$heroImage], $galleryTail);

            $extra = [
                'announce_text' => $design['announce_text'],
                'announce_highlight' => $design['announce_highlight'],
                'hero_badge' => $design['hero_badge'],
                'hero_image' => $heroImage,
                'hero_image_alt' => $design['hero_title'],
                'video_url' => $videoUrl,
                'video_caption' => 'প্রোডাক্ট ভিডিও',
                'gallery_images' => $gallery,
                'trust_items' => [
                    ['icon' => 'fa-solid fa-shield-halved', 'title' => '১০০% অরিজিনাল', 'text' => 'সরাসরি বাছাই করা অরিজিনাল পণ্য'],
                    ['icon' => 'fa-solid fa-truck-fast', 'title' => 'দ্রুত ডেলিভারি', 'text' => 'ঢাকায় ২৪ ঘণ্টা, জেলা শহরে ২-৩ দিন'],
                    ['icon' => 'fa-solid fa-hand-holding-dollar', 'title' => 'ক্যাশ অন ডেলিভারি', 'text' => 'পণ্য হাতে পেয়ে টাকা পরিশোধ'],
                    ['icon' => 'fa-solid fa-rotate-left', 'title' => 'সহজ রিটার্ন', 'text' => '৭ দিনের মধ্যে রিটার্ন সুবিধা'],
                ],
                'comparison' => [
                    ['feature' => 'উপাদানের মান', 'ours' => 'প্রিমিয়াম ও টেকসই', 'theirs' => 'সস্তা ও কম টেকসই'],
                    ['feature' => 'ফিনিশিং', 'ours' => 'হাতে করা ফিনিশিং', 'theirs' => 'মেশিন ফিনিশিং'],
                    ['feature' => 'ওয়ারেন্টি', 'ours' => '৭ দিনের রিটার্ন', 'theirs' => 'কোনো গ্যারান্টি নেই'],
                    ['feature' => 'ডেলিভারি', 'ours' => 'দ্রুত ও ট্র্যাকিং', 'theirs' => 'অনির্দিষ্ট সময়'],
                    ['feature' => 'সাপোর্ট', 'ours' => '২৪/৭ হটলাইন', 'theirs' => 'সীমিত সাপোর্ট'],
                ],
                'reviews' => [
                    ['name' => 'রাশেদা আক্তার', 'location' => 'ঢাকা', 'text' => 'পণ্যের মান সত্যিই ভালো। ডেলিভারিও দ্রুত পেয়েছি।', 'rating' => 5, 'verified' => 'ভেরিফায়েড বায়ার'],
                    ['name' => 'মেহেদী হাসান', 'location' => 'চট্টগ্রাম', 'text' => 'ছবিতে যেমন দেখেছি, হাতে পেয়ে ঠিক তেমনই পেয়েছি।', 'rating' => 5, 'verified' => 'ভেরিফায়েড বায়ার'],
                    ['name' => 'সাদিয়া ইসলাম', 'location' => 'সিলেট', 'text' => 'দাম অনুযায়ী সেরা। পরিবারের জন্য আবার অর্ডার করব।', 'rating' => 4, 'verified' => 'ভেরিফায়েড বায়ার'],
                ],
                'faqs' => [
                    ['question' => 'ডেলিভারি পেতে কতদিন লাগে?', 'answer' => 'ঢাকায় ২৪ ঘণ্টা এবং ঢাকার বাইরে ২-৩ কর্মদিবস লাগে।'],
                    ['question' => 'ক্যাশ অন ডেলিভারি আছে?', 'answer' => 'হ্যাঁ, পণ্য হাতে পেয়ে টাকা পরিশোধ করতে পারবেন।'],
                    ['question' => 'পণ্য রিটার্ন করা যাবে?', 'answer' => 'পণ্যে সমস্যা থাকলে ৭ দিনের মধ্যে রিটার্ন করতে পারবেন।'],
                    ['question' => 'অর্ডার কনফার্মেশন কীভাবে পাব?', 'answer' => 'অর্ডার দেওয়ার পর আমাদের প্রতিনিধি ফোনে কনফার্ম করবেন।'],
                ],
                'variants' => [
                    ['label' => '১টি প্যাক', 'price' => $offerPrice, 'old_price' => $oldPrice, 'badge' => 'নতুন'],
                    ['label' => '২টির কম্বো', 'price' => $offerPrice * 2, 'old_price' => $oldPrice * 2, 'badge' => 'সবচেয়ে জনপ্রিয়'],
                    ['label' => '৩টির কম্বো', 'price' => $offerPrice * 3, 'old_price' => $oldPrice * 3, 'badge' => 'সেরা সাশ্রয়'],
                    ['label' => 'ফ্যামিলি প্যাক', 'price' => $offerPrice * 4, 'old_price' => $oldPrice * 4, 'badge' => 'লিমিটেড'],
                ],
            ];

            Campaign::updateOrCreate(
                ['slug' => $design['slug']],
                [
                    'name' => $design['name'],
                    'date' => now()->toDateString(),
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays(7)->toDateString(),
                    'short_description' => $design['hero_subtitle'],
                    'description' => $design['hero_subtitle'] . ' ' . $productName . ' এখন বিশেষ ছাড়ে পাওয়া যাচ্ছে।',
                    'review' => '৪.৮/৫ (২৫০+ রিভিউ)',
                    'image_one' => $heroImage,
                    'status' => 1,
                    'is_featured' => 1,
                    'sort_order' => $design['sort_order'],
                    'campaign_type' => 'product',
                    'template_id' => $template->id,
                    'product_id' => $product->id,
                    'campaign_price_enabled' => 1,
                    'campaign_price' => $offerPrice,
                    'campaign_discount_percent' => null,
                    'allow_coupon' => 0,
                    'hero_title' => $design['hero_title'],
                    'hero_subtitle' => $design['hero_subtitle'],
                    'hero_button_text' => 'এখনই অর্ডার করুন',
                    'hero_button_link' => '#landing-order',
                    'show_features' => 1,
                    'show_benefits' => 1,
                    'show_testimonials' => 1,
                    'show_faq' => 1,
                    'show_cta' => 1,
                    'extra_fields' => $extra,
                ]
            );
        }

        $this->command->info('Landing campaigns seeded: ' . count($designs));
    }
}
