<?php

/**
 * Seed one live landing campaign per active template, each tied to a real
 * catalog product.
 *
 * Idempotent: campaigns are matched by slug and product images by product_id,
 * so running this twice does not duplicate rows.
 *
 * Images are representative demo assets. Red rice is CC0 from Wikimedia
 * Commons; the other five are bundled FreshMart demo assets from public/img.
 * They are not photographs of the physical stock in this shop.
 *
 * Usage: php tools/seed_landing_campaigns.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Campaign;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\Template;
use App\Services\CampaignContent;
use Illuminate\Support\Facades\DB;

$productImages = [
    20 => ['image' => 'uploads/product/red-apple-demo.jpg', 'title' => 'Red Apple'],
    14 => ['image' => 'uploads/product/raw-honey-demo.jpg', 'title' => 'Artisan Raw Honey'],
    25 => ['image' => 'uploads/product/desi-ghee-demo.jpg', 'title' => 'Desi Ghee'],
    24 => ['image' => 'uploads/product/mustard-oil-demo.svg', 'title' => 'Mustard Oil (1L)'],
    23 => ['image' => 'uploads/product/red-rice-demo.jpg', 'title' => 'Red Rice'],
    22 => ['image' => 'uploads/product/sourdough-demo.jpg', 'title' => 'Artisan Sourdough Bread'],
];

$galleryImages = [
    22 => [
        ['image' => 'uploads/product/sourdough-slice-demo.jpg', 'title' => 'Sourdough slice'],
        ['image' => 'uploads/product/sourdough-loaf-demo.jpg', 'title' => 'Sourdough loaf'],
    ],
];

$contactPhone = '01XXXXXXXXX';
$contactEmail = 'support@zevo.example';
$socialBase = 'https://example.com/zevo';

$campaigns = [
    [
        'slug' => 'red-apple-fresh-offer',
        'name' => 'Red Apple Fresh Offer',
        'template' => 'default',
        'product_id' => 20,
        'price' => 279,
        'cta' => 'এখনই অর্ডার করুন',
        'short_description' => 'ফার্ম ফ্রেশ লাল আপেল—প্রতিদিনের টিফিন, ফলের ঝুড়ি আর পরিবারের নাস্তার জন্য সতেজ পছন্দ।',
        'description' => 'লাল আপেলের মুচমুচে স্বাদ ও স্বাভাবিক মিষ্টি—সকালের নাস্তা থেকে সন্ধ্যার হালকা খাবার পর্যন্ত সহজে যোগ করা যায়। ১ কেজি প্যাক, বর্তমানে স্টকে আছে।',
        'extra' => [
            'features' => [
                ['title' => 'সতেজ প্যাক', 'description' => 'পরিবারের নাস্তার জন্য ১ কেজি প্যাক।'],
                ['title' => 'সহজ ব্যবহার', 'description' => 'টিফিন, ফলের ঝুড়ি বা জুসে যোগ করা যায়।'],
                ['title' => 'সহজ অর্ডার', 'description' => 'ফর্ম পূরণ করে অর্ডার করুন; ক্যাশ অন ডেলিভারি সুবিধা আছে।'],
            ],
        ],
    ],
    [
        'slug' => 'raw-honey-herbal-offer',
        'name' => 'Raw Honey Herbal Offer',
        'template' => 'herbal-health',
        'product_id' => 14,
        'price' => 749,
        'cta' => 'এখনই অর্ডার করুন',
        'short_description' => 'প্রাকৃতিক স্বাদ ও সুবাসে ভরা আর্টিজান র মধু—সকালের নাস্তা, পানীয় আর ডেজার্টের জন্য।',
        'description' => 'আর্টিজান র মধুর সমৃদ্ধ স্বাদ ও সুবাস। ৫০০ গ্রাম প্যাক, বর্তমানে স্টকে আছে।',
        'extra' => [
            'benefits' => [
                ['icon' => 'fas fa-leaf', 'title' => 'প্রাকৃতিক স্বাদ', 'text' => 'সকালের রুটি, দই বা পানীয়ে এক চামচ মধু যোগ করুন।'],
                ['icon' => 'fas fa-utensils', 'title' => 'রান্না ও নাস্তায়', 'text' => 'মিষ্টি, সস বা মারিনেডে ব্যবহারের জন্য সহজ।'],
                ['icon' => 'fas fa-weight-hanging', 'title' => '৫০০ গ্রাম প্যাক', 'text' => 'পরিবারের নিয়মিত ব্যবহারের জন্য সুবিধাজনক পরিমাণ।'],
                ['icon' => 'fas fa-shopping-cart', 'title' => 'সহজ অর্ডার', 'text' => 'ফর্ম পূরণ করলেই অর্ডার; ক্যাশ অন ডেলিভারি সুবিধা আছে।'],
            ],
            'testimonials' => [
                ['name' => 'গ্রাহক, ঢাকা', 'text' => 'মধুর স্বাদ খুব ভালো লেগেছে, প্যাকিংও ঠিক ছিল।', 'rating' => 5],
                ['name' => 'গ্রাহক, চট্টগ্রাম', 'text' => 'সকালের নাস্তায় ব্যবহার করছি, পরিবারের সবাই পছন্দ করেছে।', 'rating' => 5],
                ['name' => 'গ্রাহক, সিলেট', 'text' => 'অর্ডার করার পর দ্রুত যোগাযোগ পেয়েছি।', 'rating' => 4],
            ],
        ],
    ],
    [
        'slug' => 'mustard-oil-countdown-offer',
        'name' => 'Mustard Oil Countdown Offer',
        'template' => 'template-1',
        'product_id' => 24,
        'price' => 289,
        'cta' => 'অর্ডার করতে চাই',
        'short_description' => 'সরিষার তেলের ঘ্রাণ ও স্বাদ—বাঙালি রান্নার চিরচেনা সঙ্গী, ১ লিটার প্যাক।',
        'description' => 'রান্নায় সরিষার তেলের ঝাঁঝালো ঘ্রাণ ও স্বাদ যোগ করুন। ১ লিটার প্যাক, বর্তমানে স্টকে আছে।',
        'extra' => [
            'features' => [
                ['title' => '১ লিটার প্যাক', 'description' => 'দৈনন্দিন রান্নার জন্য যথেষ্ট পরিমাণ।'],
                ['title' => 'সরিষার স্বাদ', 'description' => 'ভর্তা, মাছ বা সবজির রান্নায় সরিষার তেলের চেনা স্বাদ।'],
                ['title' => 'সহজ অর্ডার', 'description' => 'ক্যাশ অন ডেলিভারি সুবিধা।'],
            ],
            'why_us' => [
                ['text' => 'ক্যাম্পেইন মূল্যে সীমিত সময়ের অফার।'],
                ['text' => 'অর্ডার ফর্মে নাম, ফোন ও ঠিকানা দিলেই হবে।'],
                ['text' => 'ক্যাশ অন ডেলিভারি সুবিধা।'],
            ],
            'contact_phone' => $contactPhone,
        ],
    ],
    [
        'slug' => 'red-rice-modern-offer',
        'name' => 'Red Rice Modern Offer',
        'template' => 'template-2',
        'product_id' => 23,
        'price' => 229,
        'cta' => 'অর্ডার করতে চাই',
        'short_description' => 'লাল চালের পুষ্টিকর দানা—ভাত, খিচুড়ি আর পোলাওয়ের জন্য ভিন্ন স্বাদ।',
        'description' => 'লাল চালের দানায় তৈরি ভাত বা খিচুড়ি—প্রতিদিনের খাবারে নতুন স্বাদ ও টেক্সচার। ১ কেজি প্যাক, বর্তমানে স্টকে আছে।',
        'extra' => [
            'features' => [
                ['title' => '১ কেজি প্যাক', 'description' => 'পরিবারের জন্য সুবিধাজনক পরিমাণ।'],
                ['title' => 'বহুমুখী ব্যবহার', 'description' => 'ভাত, খিচুড়ি বা পোলাওয়ে ব্যবহার করা যায়।'],
                ['title' => 'সহজ অর্ডার', 'description' => 'ফর্ম পূরণ করে অর্ডার করুন।'],
            ],
            'why_us' => [
                ['text' => 'ক্যাম্পেইন মূল্যে সীমিত সময়ের অফার।'],
                ['text' => 'সহজ অর্ডার ও ক্যাশ অন ডেলিভারি সুবিধা।'],
                ['text' => 'প্রতিটি অর্ডার যত্নসহকারে প্যাক করে পাঠানো হয়।'],
            ],
            'contact_phone' => $contactPhone,
        ],
    ],
    [
        'slug' => 'sourdough-brand-offer',
        'name' => 'Sourdough Brand Offer',
        'template' => 'template-3',
        'product_id' => 22,
        'price' => 129,
        'cta' => 'অর্ডার করুন',
        'short_description' => 'প্রাকৃতিক ফারমেন্টেশনে তৈরি আর্টিজান সাওয়ারডো ব্রেড—মুচমুচে ক্রাস্ট, নরম ভেতর।',
        'description' => 'আর্টিজান সাওয়ারডো ব্রেডের মুচমুচে ক্রাস্ট আর নরম ভেতর—সকালের নাস্তা, স্যান্ডউইচ বা স্যুপের সঙ্গে দারুণ। ৪০০ গ্রাম প্যাক।',
        'extra' => [
            'features' => [
                ['title' => 'আর্টিজান স্টাইল', 'description' => 'ঐতিহ্যবাহী সাওয়ারডো পদ্ধতিতে তৈরি।'],
                ['title' => '৪০০ গ্রাম প্যাক', 'description' => 'সকালের নাস্তা বা ছোট পরিবারের জন্য।'],
                ['title' => 'বহুমুখী', 'description' => 'টোস্ট, স্যান্ডউইচ বা স্যুপের সঙ্গে পরিবেশন করুন।'],
            ],
            'benefits' => [
                ['title' => 'মুচমুচে ক্রাস্ট', 'text' => 'বাইরের দিকে মুচমুচে, ভেতরে নরম টেক্সচার।'],
                ['title' => 'সমৃদ্ধ স্বাদ', 'text' => 'সাওয়ারডো ফারমেন্টেশনের হালকা টক ভাব।'],
                ['title' => 'সহজ অর্ডার', 'text' => 'ক্যাশ অন ডেলিভারি সুবিধা।'],
            ],
            'packages' => [
                ['name' => '১টি সাওয়ারডো (৪০০ গ্রাম)', 'price' => 129],
                ['name' => '২টি সাওয়ারডো', 'price' => 249],
                ['name' => '৩টি সাওয়ারডো', 'price' => 359],
            ],
            'video_url' => 'https://www.youtube.com/embed/7Un6mV2YQ54',
            'contact_phone' => $contactPhone,
            'email' => $contactEmail,
            'facebook' => $socialBase . '/facebook',
            'instagram' => $socialBase . '/instagram',
            'youtube' => $socialBase . '/youtube',
        ],
    ],
    [
        'slug' => 'desi-ghee-sample-offer',
        'name' => 'Desi Ghee Sample Offer',
        'template' => 'sample-template',
        'product_id' => 25,
        'price' => 649,
        'cta' => 'এখনই অর্ডার করুন',
        'short_description' => 'ঘি-এর সমৃদ্ধ স্বাদ ও সুবাস—গরম ভাত, রুটি বা মিষ্টিতে এক চামচ।',
        'description' => 'দেশি ঘি-এর সমৃদ্ধ স্বাদ ও সুবাস—দৈনন্দিন রান্না বা বিশেষ খাবারে ব্যবহারের জন্য। ৫০০ গ্রাম প্যাক, বর্তমানে স্টকে আছে।',
        'extra' => [
            'features' => [
                ['title' => '৫০০ গ্রাম প্যাক', 'description' => 'পরিবারের নিয়মিত ব্যবহারের জন্য।'],
                ['title' => 'রান্নায় ব্যবহার', 'description' => 'ভাত, রুটি, মিষ্টি বা সবজি রান্নায়।'],
                ['title' => 'সহজ অর্ডার', 'description' => 'নাম, ফোন ও ঠিকানা দিয়ে অর্ডার করুন।'],
            ],
            'benefits' => [
                ['title' => 'সমৃদ্ধ স্বাদ', 'text' => 'রান্নায় ঘি-এর চেনা স্বাদ ও সুবাস।'],
                ['title' => 'বহুমুখী ব্যবহার', 'text' => 'দৈনন্দিন রান্না থেকে উৎসবের খাবার।'],
                ['title' => 'সহজ অর্ডার', 'text' => 'ক্যাশ অন ডেলিভারি সুবিধা।'],
            ],
            'testimonials' => [
                ['name' => 'গ্রাহক, ঢাকা', 'text' => 'ঘি-এর স্বাদ ও সুবাস দুটোই ভালো লেগেছে।', 'rating' => 5],
                ['name' => 'গ্রাহক, রাজশাহী', 'text' => 'রান্নায় ব্যবহার করছি, প্যাকিং ঠিক ছিল।', 'rating' => 4],
                ['name' => 'গ্রাহক, খুলনা', 'text' => 'অর্ডার প্রক্রিয়া সহজ ছিল।', 'rating' => 5],
            ],
            'faqs' => [
                ['question' => 'অর্ডার কীভাবে করব?', 'answer' => 'পেজের ফর্মে নাম, ফোন ও ঠিকানা দিয়ে Place Order চাপুন। আমাদের প্রতিনিধি ফোনে অর্ডার নিশ্চিত করবেন।'],
                ['question' => 'ক্যাশ অন ডেলিভারি আছে?', 'answer' => 'হ্যাঁ, চেকআউটে ক্যাশ অন ডেলিভারি নির্বাচন করতে পারবেন।'],
                ['question' => 'ডেলিভারি কোথায় পাওয়া যায়?', 'answer' => 'বাংলাদেশের যেকোনো জেলায় ডেলিভারির জন্য অর্ডার নিতে পারবেন।'],
                ['question' => 'পণ্য কীভাবে সংরক্ষণ করব?', 'answer' => 'ঠান্ডা, শুকনো জায়গায় ঢাকনা বন্ধ করে রাখুন।'],
            ],
        ],
    ],
];

DB::transaction(function () use ($productImages, $galleryImages, $campaigns) {
    foreach ($productImages as $productId => $data) {
        $product = Product::find($productId);
        if (!$product) {
            throw new RuntimeException("Product {$productId} not found");
        }
        if (!is_file(public_path($data['image']))) {
            throw new RuntimeException("Image missing: public/{$data['image']}");
        }

        $first = Productimage::where('product_id', $productId)->orderBy('id')->first();
        if ($first) {
            $first->update(['image' => $data['image'], 'title' => $data['title']]);
        } else {
            Productimage::create([
                'product_id' => $productId,
                'image' => $data['image'],
                'title' => $data['title'],
            ]);
        }

        $product->update(['thumbnail_image' => $data['image']]);
    }

    foreach ($galleryImages as $productId => $images) {
        foreach ($images as $image) {
            if (!is_file(public_path($image['image']))) {
                throw new RuntimeException("Image missing: public/{$image['image']}");
            }
            Productimage::firstOrCreate(
                ['product_id' => $productId, 'image' => $image['image']],
                ['title' => $image['title']]
            );
        }
    }

    $normalizer = new CampaignContent();
    $start = now()->toDateString();
    $end = now()->addDays(30)->toDateString();
    $countdown = now()->addDays(30)->endOfDay()->format('Y-m-d H:i:s');

    foreach ($campaigns as $index => $row) {
        $template = Template::where('view_name', $row['template'])->first();
        if (!$template) {
            throw new RuntimeException("Template {$row['template']} not found");
        }
        $product = Product::find($row['product_id']);
        if (!$product) {
            throw new RuntimeException("Product {$row['product_id']} not found");
        }
        if ($row['price'] >= (float) $product->new_price) {
            throw new RuntimeException("Offer price must be below product price for {$row['slug']}");
        }

        $extra = $row['extra'];
        $extra['countdown_date'] = $countdown;
        $extra = $normalizer->normalize($extra);

        $campaign = Campaign::updateOrCreate(
            ['slug' => $row['slug']],
            [
                'name' => $row['name'],
                'date' => $start,
                'short_description' => $row['short_description'],
                'review' => 'Landing campaign demo for the ' . $row['template'] . ' template.',
                'description' => $row['description'],
                'hero_title' => $product->name,
                'hero_subtitle' => $row['short_description'],
                'hero_button_text' => $row['cta'],
                'hero_button_link' => url('/landing-order'),
                'cta_button_text' => $row['cta'],
                'meta_title' => $product->name . ' — ' . $row['name'],
                'meta_description' => $row['short_description'],
                'show_features' => 1,
                'show_benefits' => 1,
                'show_testimonials' => 1,
                'show_faq' => 1,
                'show_cta' => 1,
                'campaign_type' => 'product',
                'start_date' => $start,
                'end_date' => $end,
                'is_featured' => 0,
                'sort_order' => $index + 1,
                'image_one' => $productImages[$row['product_id']]['image'],
                'image_two' => null,
                'image_three' => null,
                'status' => 1,
                'template_id' => $template->id,
                'product_id' => $product->id,
                'campaign_price' => $row['price'],
                'campaign_price_enabled' => 1,
                'campaign_discount_percent' => null,
                'allow_coupon' => 0,
                'extra_fields' => $extra,
            ]
        );

        echo sprintf(
            "SEEDED %-32s id=%-3d template=%-14s product=%-24s price=%s\n",
            $campaign->slug,
            $campaign->id,
            $row['template'],
            $product->name,
            $row['price']
        );
    }
});

echo "SEED_LANDING_CAMPAIGNS_DONE\n";
