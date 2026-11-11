<?php

/*
 * Landing-template registry.
 *
 * One place that describes, for each new public/frontend/landing design:
 *   - which CSS/JS files the shared layout must load,
 *   - which of the 72 measured content variables are enabled by default.
 *
 * The same group map is the admin toggle list and the source of truth for the
 * campaign form. `source` decides where a value comes from:
 *   product   -> Product record (name, description, price, image, gallery, video)
 *   campaign  -> Campaign columns (hero copy, offer price, window, CTA)
 *   system    -> GeneralSetting / shipping / payment / event layer
 *   content   -> admin-entered campaign extra_fields
 *   removed   -> not rendered (the layout drops the footer by design)
 */

return [

    'templates' => [
        'ht2' => [
            'name' => 'Premium Collection',
            'view' => 'ht2',
            'body_class' => 'bg-white text-brand-dark',
            'default_groups' => ['meta', 'announcement', 'hero', 'trust', 'gallery', 'variants', 'comparison', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap',
                    'assets/global/css/all.min.css',
                    'frontend/landing/ht2.css',
                ],
                'head_js' => [
                    'https://cdn.tailwindcss.com?plugins=forms,container-queries',
                    'frontend/landing/ht2-tailwind-config.js',
                ],
                'body_js' => [],
            ],
        ],
        't1' => [
            'name' => 'Funnel Offer',
            'view' => 't1',
            'body_class' => '',
            'default_groups' => ['meta', 'announcement', 'hero', 'video', 'trust', 'gallery', 'variants', 'comparison', 'reviews', 'order', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&display=swap',
                    'assets/global/css/bootstrap.min.css',
                    'assets/global/css/all.min.css',
                    'frontend/landing/t1.css',
                ],
                'head_js' => [],
                'body_js' => [
                    'assets/global/js/bootstrap.bundle.min.js',
                ],
            ],
        ],
        'th3' => [
            'name' => 'Premium Wall Art',
            'view' => 'th3',
            'body_class' => 'bg-white text-brand-dark',
            'default_groups' => ['meta', 'announcement', 'hero', 'video', 'trust', 'gallery', 'variants', 'comparison', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap',
                    'assets/global/css/all.min.css',
                    'frontend/landing/th3.css',
                ],
                'head_js' => [
                    'https://cdn.tailwindcss.com?plugins=forms,container-queries',
                    'frontend/landing/th3-tailwind-config.js',
                ],
                'body_js' => [],
            ],
        ],
        'th4' => [
            'name' => 'Artisan Honey',
            'view' => 'th4',
            'body_class' => '',
            'default_groups' => ['meta', 'announcement', 'hero', 'video', 'trust', 'gallery', 'variants', 'comparison', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'assets/global/css/bootstrap.min.css',
                    'assets/global/css/all.min.css',
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap',
                    'frontend/landing/th4.css',
                ],
                'head_js' => [],
                'body_js' => [
                    'assets/global/js/bootstrap.bundle.min.js',
                ],
            ],
        ],
        'th5' => [
            'name' => 'Natural Honey',
            'view' => 'th5',
            'body_class' => '',
            'default_groups' => ['meta', 'announcement', 'hero', 'video', 'trust', 'gallery', 'variants', 'comparison', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'assets/global/css/bootstrap.min.css',
                    'assets/global/css/all.min.css',
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap',
                    'frontend/landing/th5.css',
                ],
                'head_js' => [],
                'body_js' => [
                    'assets/global/js/bootstrap.bundle.min.js',
                ],
            ],
        ],
        'th6' => [
            'name' => 'Rose Editorial Abaya',
            'view' => 'th6',
            'body_class' => 'bg-[#FFFDFD] text-slate-800 font-bangla antialiased selection:bg-rose-100 selection:text-rose-700 relative overflow-x-hidden',
            'default_groups' => ['meta', 'announcement', 'hero', 'video', 'trust', 'gallery', 'variants', 'reviews', 'order', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,700;1,600&display=swap',
                    'assets/global/css/all.min.css',
                    'frontend/landing/th6.css',
                ],
                'head_js' => [
                    'https://cdn.tailwindcss.com?plugins=forms,container-queries',
                    'frontend/landing/th6-tailwind-config.js',
                ],
                'body_js' => [],
            ],
        ],
        'th7' => [
            'name' => 'Fashion Editorial Abaya',
            'view' => 'th7',
            'body_class' => '',
            'default_groups' => ['meta', 'announcement', 'hero', 'video', 'trust', 'gallery', 'variants', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap',
                    'assets/global/css/all.min.css',
                    'frontend/landing/th7.css',
                ],
                'head_js' => [],
                'body_js' => [],
            ],
        ],
        'th8' => [
            'name' => 'Dark Premium Panjabi',
            'view' => 'th8',
            'body_class' => 'bg-zombie-midnight text-gray-200 selection:bg-zombie-gold selection:text-black',
            'default_groups' => ['meta', 'announcement', 'hero', 'trust', 'gallery', 'variants', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800;900&family=Hind+Siliguri:wght@300;400;500;600;700&family=Outfit:wght@300;400;600;700&display=swap',
                    'assets/global/css/all.min.css',
                    'frontend/landing/th8.css',
                ],
                'head_js' => [
                    'https://cdn.tailwindcss.com?plugins=forms,container-queries',
                    'frontend/landing/th8-tailwind-config.js',
                ],
                'body_js' => [],
            ],
        ],
        'th9' => [
            'name' => 'Soft Commerce Hoodie',
            'view' => 'th9',
            'body_class' => 'bg-[#FFF9FA] text-slate-800 antialiased font-bangla selection:bg-rose-200 selection:text-rose-900 pb-20 md:pb-6',
            'default_groups' => ['meta', 'announcement', 'hero', 'trust', 'gallery', 'variants', 'reviews', 'order', 'faq', 'sticky'],
            'assets' => [
                'css' => [
                    'https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@300;400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap',
                    'assets/global/css/all.min.css',
                    'frontend/landing/th9.css',
                ],
                'head_js' => [
                    'https://cdn.tailwindcss.com?plugins=forms,container-queries',
                    'frontend/landing/th9-tailwind-config.js',
                ],
                'body_js' => [],
            ],
        ],
    ],

    'groups' => [
        'meta' => [
            'label' => 'Meta & SEO',
            'variables' => [
                'page_title' => ['label' => 'Page title', 'type' => 'text', 'source' => 'auto', 'auto' => 'Product name / campaign meta title'],
                'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'source' => 'auto', 'auto' => 'Product meta description'],
                'lang' => ['label' => 'Language', 'type' => 'text', 'source' => 'system', 'auto' => 'bn'],
                'brand_name' => ['label' => 'Brand / app name', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting name'],
                'favicon' => ['label' => 'Favicon', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting favicon'],
            ],
        ],
        'announcement' => [
            'label' => 'Announcement bar',
            'variables' => [
                'announce_text' => ['label' => 'Announcement text', 'type' => 'text', 'source' => 'content'],
                'announce_highlight' => ['label' => 'Highlight / offer tag', 'type' => 'text', 'source' => 'content'],
                'announce_countdown_end' => ['label' => 'Countdown end', 'type' => 'datetime-local', 'source' => 'campaign', 'auto' => 'Campaign end date'],
            ],
        ],
        'header' => [
            'label' => 'Header (app-name header replaces it)',
            'variables' => [
                'logo_text' => ['label' => 'Logo text', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting name'],
                'nav_links' => ['label' => 'Nav links', 'type' => 'text', 'source' => 'system', 'auto' => 'App header home link'],
                'hotline_number' => ['label' => 'Hotline', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting support phone'],
                'header_cta' => ['label' => 'Header CTA', 'type' => 'text', 'source' => 'content'],
            ],
        ],
        'hero' => [
            'label' => 'Hero section',
            'variables' => [
                'hero_badge' => ['label' => 'Hero badge', 'type' => 'text', 'source' => 'content'],
                'hero_title' => ['label' => 'Hero title', 'type' => 'text', 'source' => 'campaign', 'auto' => 'Campaign hero title / product name'],
                'hero_subtitle' => ['label' => 'Hero subtitle', 'type' => 'textarea', 'source' => 'campaign', 'auto' => 'Campaign hero subtitle / product description'],
                'hero_image' => ['label' => 'Hero image', 'type' => 'text', 'source' => 'product', 'auto' => 'Product image'],
                'hero_image_alt' => ['label' => 'Hero image alt', 'type' => 'text', 'source' => 'product', 'auto' => 'Product name'],
                'price_current' => ['label' => 'Current price', 'type' => 'text', 'source' => 'campaign', 'auto' => 'Campaign price / product new price'],
                'price_old' => ['label' => 'Old price', 'type' => 'text', 'source' => 'product', 'auto' => 'Product old price'],
                'discount_label' => ['label' => 'Discount label', 'type' => 'text', 'source' => 'auto', 'auto' => 'Computed from old and current price'],
                'hero_cta' => ['label' => 'Hero CTA', 'type' => 'text', 'source' => 'campaign', 'auto' => 'Campaign hero button text'],
            ],
        ],
        'video' => [
            'label' => 'Video section',
            'variables' => [
                'video_url' => ['label' => 'Video URL', 'type' => 'url', 'source' => 'auto', 'auto' => 'Product video / video_url extra field'],
                'video_poster' => ['label' => 'Video poster', 'type' => 'text', 'source' => 'product', 'auto' => 'Product image'],
                'video_caption' => ['label' => 'Video caption', 'type' => 'text', 'source' => 'content'],
            ],
        ],
        'trust' => [
            'label' => 'Trust badges',
            'variables' => [
                'trust_icon' => ['label' => 'Trust icon', 'type' => 'text', 'source' => 'content', 'repeater' => 'trust_items', 'column' => 'icon'],
                'trust_title' => ['label' => 'Trust title', 'type' => 'text', 'source' => 'content', 'repeater' => 'trust_items', 'column' => 'title'],
                'trust_text' => ['label' => 'Trust text', 'type' => 'textarea', 'source' => 'content', 'repeater' => 'trust_items', 'column' => 'text'],
            ],
        ],
        'gallery' => [
            'label' => 'Product gallery',
            'variables' => [
                'gallery_image' => ['label' => 'Gallery image', 'type' => 'text', 'source' => 'product', 'auto' => 'Product gallery'],
                'gallery_image_alt' => ['label' => 'Gallery alt', 'type' => 'text', 'source' => 'product', 'auto' => 'Product name + index'],
            ],
        ],
        'variants' => [
            'label' => 'Variants & bundles',
            'variables' => [
                'variant_label' => ['label' => 'Variant label', 'type' => 'text', 'source' => 'content', 'repeater' => 'variants', 'column' => 'label'],
                'variant_price' => ['label' => 'Variant price', 'type' => 'number', 'source' => 'content', 'repeater' => 'variants', 'column' => 'price'],
                'variant_old_price' => ['label' => 'Variant old price', 'type' => 'number', 'source' => 'content', 'repeater' => 'variants', 'column' => 'old_price'],
                'variant_image' => ['label' => 'Variant image', 'type' => 'text', 'source' => 'content', 'repeater' => 'variants', 'column' => 'image'],
                'variant_badge' => ['label' => 'Variant badge', 'type' => 'text', 'source' => 'content', 'repeater' => 'variants', 'column' => 'badge'],
                'quantity_options' => ['label' => 'Quantity options', 'type' => 'text', 'source' => 'system', 'auto' => 'Stock / product unit'],
            ],
        ],
        'comparison' => [
            'label' => 'Comparison table',
            'variables' => [
                'compare_feature' => ['label' => 'Feature', 'type' => 'text', 'source' => 'content', 'repeater' => 'comparison', 'column' => 'feature'],
                'compare_ours' => ['label' => 'Our value', 'type' => 'text', 'source' => 'content', 'repeater' => 'comparison', 'column' => 'ours'],
                'compare_theirs' => ['label' => 'Their value', 'type' => 'text', 'source' => 'content', 'repeater' => 'comparison', 'column' => 'theirs'],
            ],
        ],
        'reviews' => [
            'label' => 'Customer reviews',
            'variables' => [
                'review_name' => ['label' => 'Reviewer name', 'type' => 'text', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'name'],
                'review_location' => ['label' => 'Reviewer location', 'type' => 'text', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'location'],
                'review_text' => ['label' => 'Review text', 'type' => 'textarea', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'text'],
                'review_rating' => ['label' => 'Rating', 'type' => 'number', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'rating'],
                'review_avatar' => ['label' => 'Reviewer avatar', 'type' => 'text', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'avatar'],
                'review_date' => ['label' => 'Review date', 'type' => 'text', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'date'],
                'review_verified' => ['label' => 'Verified buyer', 'type' => 'text', 'source' => 'content', 'repeater' => 'reviews', 'column' => 'verified'],
            ],
        ],
        'order' => [
            'label' => 'Order form',
            'variables' => [
                'form_name' => ['label' => 'Name field', 'type' => 'text', 'source' => 'system', 'auto' => 'Main checkout name field'],
                'form_phone' => ['label' => 'Phone field', 'type' => 'text', 'source' => 'system', 'auto' => 'Main checkout phone field'],
                'form_address' => ['label' => 'Address field', 'type' => 'text', 'source' => 'system', 'auto' => 'Main checkout address field'],
                'form_district' => ['label' => 'District field', 'type' => 'text', 'source' => 'system', 'auto' => 'Main checkout area selector'],
                'form_area' => ['label' => 'Area field', 'type' => 'text', 'source' => 'system', 'auto' => 'Shipping charge selector'],
                'form_note' => ['label' => 'Order note field', 'type' => 'text', 'source' => 'system', 'auto' => 'Optional note'],
                'delivery_zone' => ['label' => 'Delivery zone', 'type' => 'text', 'source' => 'system', 'auto' => 'Shipping charges'],
                'delivery_charge_inside' => ['label' => 'Inside Dhaka charge', 'type' => 'text', 'source' => 'system', 'auto' => 'Shipping charge'],
                'delivery_charge_outside' => ['label' => 'Outside Dhaka charge', 'type' => 'text', 'source' => 'system', 'auto' => 'Shipping charge'],
                'free_delivery_threshold' => ['label' => 'Free delivery threshold', 'type' => 'text', 'source' => 'system', 'auto' => 'Shipping rule'],
                'payment_methods' => ['label' => 'Payment methods', 'type' => 'text', 'source' => 'system', 'auto' => 'Active payment gateways'],
                'submit_label' => ['label' => 'Submit button label', 'type' => 'text', 'source' => 'content'],
            ],
        ],
        'faq' => [
            'label' => 'FAQ',
            'variables' => [
                'faq_question' => ['label' => 'Question', 'type' => 'text', 'source' => 'content', 'repeater' => 'faqs', 'column' => 'question'],
                'faq_answer' => ['label' => 'Answer', 'type' => 'textarea', 'source' => 'content', 'repeater' => 'faqs', 'column' => 'answer'],
            ],
        ],
        'footer' => [
            'label' => 'Footer (layout removes it)',
            'variables' => [
                'footer_about' => ['label' => 'About', 'type' => 'textarea', 'source' => 'removed'],
                'footer_address' => ['label' => 'Address', 'type' => 'text', 'source' => 'removed'],
                'footer_phone' => ['label' => 'Phone', 'type' => 'text', 'source' => 'removed'],
                'footer_email' => ['label' => 'Email', 'type' => 'email', 'source' => 'removed'],
                'footer_social' => ['label' => 'Social links', 'type' => 'text', 'source' => 'removed'],
                'footer_policy' => ['label' => 'Policy links', 'type' => 'text', 'source' => 'removed'],
                'copyright' => ['label' => 'Copyright', 'type' => 'text', 'source' => 'removed'],
            ],
        ],
        'sticky' => [
            'label' => 'Sticky order bar',
            'variables' => [
                'sticky_price' => ['label' => 'Sticky price', 'type' => 'text', 'source' => 'auto', 'auto' => 'Current price'],
                'sticky_cta' => ['label' => 'Sticky CTA', 'type' => 'text', 'source' => 'auto', 'auto' => 'Hero CTA'],
                'sticky_timer' => ['label' => 'Sticky timer', 'type' => 'text', 'source' => 'auto', 'auto' => 'Countdown'],
            ],
        ],
        'tracking' => [
            'label' => 'Tracking (event layer)',
            'variables' => [
                'fb_pixel_id' => ['label' => 'Facebook pixel', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting / event layer'],
                'ga_id' => ['label' => 'GA4 / GTM', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting / event layer'],
                'whatsapp_number' => ['label' => 'WhatsApp', 'type' => 'text', 'source' => 'system', 'auto' => 'General setting'],
            ],
        ],
    ],

    'repeaters' => [
        'trust_items' => [
            'label' => 'Trust badges',
            'columns' => [
                'icon' => ['label' => 'Icon class', 'type' => 'text', 'class' => 'col-md-3'],
                'title' => ['label' => 'Title', 'type' => 'text', 'class' => 'col-md-4'],
                'text' => ['label' => 'Text', 'type' => 'textarea', 'class' => 'col-md-5'],
            ],
        ],
        'variants' => [
            'label' => 'Variants / bundles',
            'columns' => [
                'label' => ['label' => 'Label', 'type' => 'text', 'class' => 'col-md-3'],
                'price' => ['label' => 'Price', 'type' => 'number', 'step' => '0.01', 'class' => 'col-md-2'],
                'old_price' => ['label' => 'Old price', 'type' => 'number', 'step' => '0.01', 'class' => 'col-md-2'],
                'image' => ['label' => 'Image URL', 'type' => 'text', 'class' => 'col-md-3'],
                'badge' => ['label' => 'Badge', 'type' => 'text', 'class' => 'col-md-2'],
            ],
        ],
        'comparison' => [
            'label' => 'Comparison rows',
            'columns' => [
                'feature' => ['label' => 'Feature', 'type' => 'text', 'class' => 'col-md-4'],
                'ours' => ['label' => 'Our value', 'type' => 'text', 'class' => 'col-md-4'],
                'theirs' => ['label' => 'Their value', 'type' => 'text', 'class' => 'col-md-4'],
            ],
        ],
        'reviews' => [
            'label' => 'Customer reviews',
            'columns' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'class' => 'col-md-3'],
                'location' => ['label' => 'Location', 'type' => 'text', 'class' => 'col-md-2'],
                'text' => ['label' => 'Review', 'type' => 'textarea', 'class' => 'col-md-4'],
                'rating' => ['label' => 'Rating', 'type' => 'number', 'step' => '1', 'class' => 'col-md-1'],
                'avatar' => ['label' => 'Avatar URL', 'type' => 'text', 'class' => 'col-md-2'],
                'date' => ['label' => 'Date', 'type' => 'text', 'class' => 'col-md-2'],
                'verified' => ['label' => 'Verified label', 'type' => 'text', 'class' => 'col-md-2'],
            ],
        ],
        'faqs' => [
            'label' => 'FAQ rows',
            'columns' => [
                'question' => ['label' => 'Question', 'type' => 'text', 'class' => 'col-md-5'],
                'answer' => ['label' => 'Answer', 'type' => 'textarea', 'class' => 'col-md-7'],
            ],
        ],
    ],
];
