<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\GeneralSetting;
use App\Models\Product;

/**
 * Resolve the 72 landing-template variables for one rendered page.
 *
 * Priority for every value:
 *   1. campaign extra_fields (admin content)
 *   2. campaign / product / general-setting record
 *   3. the template default
 *
 * A template only renders the variables enabled in templates.config
 * ("enabled_variables"); the registry's default_groups is the fallback for
 * rows created before that column existed.
 */
class LandingTemplateVars
{
    protected array $config;
    protected array $extra = [];
    protected array $values = [];
    protected array $rows = [];
    protected array $enabled = [];
    protected ?Product $product = null;
    protected ?Campaign $campaign = null;
    protected ?GeneralSetting $settings = null;
    protected string $templateKey = 'ht2';

    public function __construct(array $config = [])
    {
        $this->config = $config ?: (array) config('landing_templates', []);
    }

    public static function registry(): array
    {
        return (array) config('landing_templates', []);
    }

    public static function configArray($template): array
    {
        if (is_object($template) && isset($template->config)) {
            $config = $template->config;
        } elseif (is_array($template)) {
            $config = $template['config'] ?? [];
        } else {
            $config = [];
        }

        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        return is_array($config) ? $config : [];
    }

    public static function templateKeyFor($template, string $fallback = 'ht2'): string
    {
        $config = self::configArray($template);
        if (!empty($config['landing_template'])) {
            return (string) $config['landing_template'];
        }

        if (is_object($template) && !empty($template->view_name)) {
            return (string) $template->view_name;
        }

        if (is_array($template) && !empty($template['view_name'])) {
            return (string) $template['view_name'];
        }

        return $fallback;
    }

    public static function defaultEnabledVariables(string $templateKey): array
    {
        $registry = self::registry();
        $groups = $registry['templates'][$templateKey]['default_groups'] ?? array_keys($registry['groups'] ?? []);
        $enabled = [];

        foreach ((array) $groups as $groupKey) {
            foreach (array_keys((array) ($registry['groups'][$groupKey]['variables'] ?? [])) as $variable) {
                $enabled[] = $variable;
            }
        }

        return array_values(array_unique($enabled));
    }

    public static function enabledVariablesFor($template): array
    {
        $config = self::configArray($template);

        if (array_key_exists('enabled_variables', $config) && is_array($config['enabled_variables'])) {
            return array_values(array_filter(array_map('strval', $config['enabled_variables'])));
        }

        return self::defaultEnabledVariables(self::templateKeyFor($template));
    }

    /**
     * Content variables a campaign form may edit, grouped by storage field.
     *
     * Scalar variables map to themselves. Repeater variables map to the row
     * field declared on the variable definition, so one repeater is rendered
     * once even when several of its columns are enabled.
     */
    public static function contentFieldsFor($template): array
    {
        $registry = self::registry();
        $enabled = self::enabledVariablesFor($template);
        $fields = [];

        foreach ((array) ($registry['groups'] ?? []) as $group) {
            foreach ((array) ($group['variables'] ?? []) as $key => $definition) {
                if (($definition['source'] ?? '') !== 'content' || !in_array($key, $enabled, true)) {
                    continue;
                }

                $repeater = $definition['repeater'] ?? null;
                if ($repeater) {
                    $fields[$repeater]['type'] = 'rows';
                    $fields[$repeater]['columns'][($definition['column'] ?? $key)] = $key;
                } else {
                    $fields[$key]['type'] = 'text';
                }
            }
        }

        return $fields;
    }

    public static function make($landingPage = null, $template = null, $campaign = null): self
    {
        $instance = new self();
        $instance->boot($landingPage, $template, $campaign);

        return $instance;
    }

    public function boot($landingPage = null, $template = null, $campaign = null): void
    {
        $this->extra = $this->arrayValue($landingPage->extra_fields ?? []);
        $this->settings = GeneralSetting::first();

        if ($campaign instanceof Campaign) {
            $this->campaign = $campaign;
        } elseif (!empty($this->extra['campaign_id'])) {
            $this->campaign = Campaign::find((int) $this->extra['campaign_id']);
        }

        if (!empty($this->extra['product']) && is_array($this->extra['product'])) {
            $this->product = null;
        } elseif (!empty($this->extra['product_id'])) {
            $this->product = Product::with(['image', 'images'])->find((int) $this->extra['product_id']);
        }

        $this->templateKey = $this->resolveTemplateKey($template, $landingPage);
        $this->enabled = $this->resolveEnabled($template);

        $this->rows = [
            'trust_items' => $this->rowsFrom('trust_items', $this->defaultTrust()),
            'variants' => $this->rowsFrom('variants', $this->defaultVariants()),
            'comparison' => $this->rowsFrom('comparison', $this->defaultComparison()),
            'reviews' => $this->rowsFrom('reviews', $this->defaultReviews()),
            'faqs' => $this->rowsFrom('faqs', $this->defaultFaqs()),
        ];

        $this->values = $this->resolveValues($landingPage);
    }

    public function templateKey(): string
    {
        return $this->templateKey;
    }

    public function isEnabled(string $key): bool
    {
        return in_array($key, $this->enabled, true);
    }

    public function enabled(): array
    {
        return $this->enabled;
    }

    public function assets(): array
    {
        $assets = $this->config['templates'][$this->templateKey]['assets'] ?? [];

        return [
            'css' => (array) ($assets['css'] ?? []),
            'head_js' => (array) ($assets['head_js'] ?? []),
            'body_js' => (array) ($assets['body_js'] ?? []),
        ];
    }

    public function bodyClass(): string
    {
        return (string) ($this->config['templates'][$this->templateKey]['body_class'] ?? '');
    }

    public function text(string $key, $default = null): string
    {
        $value = $this->values[$key] ?? null;
        if ($value === null || $value === '') {
            $value = $default;
        }

        if (is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    public function raw(string $key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function rows(string $key): array
    {
        $rows = $this->rows[$key] ?? [];

        return is_array($rows) ? $rows : [];
    }

    public function priceCurrent(): string
    {
        return $this->text('price_current', '0');
    }

    public function priceOld(): string
    {
        return $this->text('price_old', '');
    }

    public function priceCurrentNumber(): float
    {
        return $this->number($this->values['price_current'] ?? 0);
    }

    public function priceOldNumber(): float
    {
        return $this->number($this->values['price_old'] ?? 0);
    }

    public function money($value): string
    {
        $number = $this->number($value);

        return '৳' . number_format($number, 0);
    }

    public function bnNumber($value): string
    {
        $digits = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

        return strtr((string) $value, $digits);
    }

    public function moneyBn($value): string
    {
        return '৳' . $this->bnNumber(number_format($this->number($value), 0));
    }

    public function imageUrl(?string $path, ?string $fallback = null): string
    {
        $path = $path ?: $fallback;

        if (!$path) {
            return '';
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return asset(preg_replace('~^public/~', '', $path));
    }

    public function productName(): string
    {
        return $this->text('page_title', $this->text('hero_title', 'Product'));
    }

    public function productId(): ?int
    {
        $id = $this->extra['product_id'] ?? null;

        return $id ? (int) $id : null;
    }

    public function campaignId(): ?int
    {
        $id = $this->extra['campaign_id'] ?? null;

        return $id ? (int) $id : null;
    }

    public function orderAnchor(): string
    {
        return (string) ($this->config['templates'][$this->templateKey]['order_anchor'] ?? 'order-section');
    }

    protected function resolveTemplateKey($template, $landingPage): string
    {
        if (is_string($template) && $template !== '') {
            return $template;
        }

        if (is_object($template) || is_array($template)) {
            return self::templateKeyFor($template);
        }

        if (is_object($landingPage) && !empty($landingPage->template->view_name)) {
            return self::templateKeyFor($landingPage->template);
        }

        return (string) ($this->extra['template'] ?? 'ht2');
    }

    protected function resolveEnabled($template): array
    {
        return self::enabledVariablesFor($template ?? ['view_name' => $this->templateKey]);
    }

    protected function resolveValues($landingPage): array
    {
        $extra = $this->extra;
        $product = $this->product;
        $campaign = $this->campaign;
        $settings = $this->settings;
        $productArray = is_array($extra['product'] ?? null) ? $extra['product'] : [];

        $productName = $extra['product']['name'] ?? ($product->name ?? ($campaign->name ?? ($landingPage->title ?? 'Product')));
        $productDescription = $extra['product']['description'] ?? ($product->description ?? '');
        $productImage = $extra['hero_image'] ?? ($extra['product']['image'] ?? (optional(optional($product)->image)->image ?? null));
        $gallery = $extra['gallery_images'] ?? ($extra['product']['images'] ?? []);
        if ((!is_array($gallery) || $gallery === []) && $product) {
            $gallery = $product->images->pluck('image')->all();
        }
        if (!is_array($gallery) || $gallery === []) {
            $gallery = $this->defaultGallery();
        }
        // The admin repeater stores gallery_images as rows (image => path);
        // the seeded/public shape is a flat list of paths. Accept both.
        $gallery = array_values(array_filter(array_map(function ($item) {
            if (is_array($item)) {
                $item = $item['image'] ?? '';
            }

            return is_scalar($item) ? (string) $item : '';
        }, $gallery)));
        if (!$productImage) {
            $productImage = $gallery[0] ?? null;
        }

        $current = $extra['new_price'] ?? ($extra['campaign_price'] ?? null);
        if ($current === null && $campaign) {
            $current = $campaign->campaign_price_enabled ? $campaign->campaign_price : null;
        }
        if ($current === null && $productArray) {
            $current = $productArray['new_price'] ?? null;
        }
        if ($current === null && $product) {
            $current = $product->new_price;
        }

        $old = $extra['old_price'] ?? ($productArray['old_price'] ?? ($product->old_price ?? null));
        $countdown = $extra['countdown_date'] ?? (optional($campaign)->end_date?->format('Y-m-d H:i:s'));
        $heroTitle = $campaign->hero_title ?? $productName;
        $heroSubtitle = $campaign->hero_subtitle ?? $campaign->short_description ?? $landingPage->subtitle ?? '';
        $cta = $campaign->hero_button_text ?? ($landingPage->cta_text ?? 'অর্ডার করুন');

        $discount = '';
        if ($old !== null && $current !== null && (float) $old > 0 && (float) $current < (float) $old) {
            $discount = 'সাশ্রয় ' . $this->moneyBn((float) $old - (float) $current);
        }

        return [
            // meta
            'page_title' => $campaign->meta_title ?? $productName,
            'meta_description' => $campaign->meta_description ?? ($productArray['meta_description'] ?? ($product->meta_description ?? $landingPage->subtitle ?? '')),
            'lang' => $extra['lang'] ?? 'bn',
            'brand_name' => $settings->name ?? config('app.name', 'ZEvo'),
            'favicon' => $this->imageUrl($settings->favicon ?? null),
            // announcement
            'announce_text' => $extra['announce_text'] ?? ($campaign->short_description ?? ''),
            'announce_highlight' => $extra['announce_highlight'] ?? 'সীমিত অফার',
            'announce_countdown_end' => $countdown,
            // header / app header
            'logo_text' => $settings->name ?? config('app.name', 'ZEvo'),
            'nav_links' => 'Home',
            'hotline_number' => $settings->support_phone ?? ($settings->whatsapp_number ?? ''),
            'header_cta' => $extra['header_cta'] ?? $cta,
            // hero
            'hero_badge' => $extra['hero_badge'] ?? '১০০% অরিজিনাল প্রোডাক্ট',
            'hero_title' => $heroTitle,
            'hero_subtitle' => $heroSubtitle,
            'hero_image' => $this->imageUrl($productImage),
            'hero_image_alt' => $extra['hero_image_alt'] ?? $productName,
            'price_current' => $current,
            'price_old' => $old,
            'discount_label' => $extra['discount_label'] ?? $discount,
            'hero_cta' => $cta,
            // video
            'video_url' => $extra['video_url'] ?? ($productArray['pro_video'] ?? ($product->pro_video ?? '')),
            'video_poster' => $this->imageUrl($productImage),
            'video_caption' => $extra['video_caption'] ?? '',
            // gallery
            'gallery_image' => $gallery,
            'gallery_image_alt' => $productName,
            // variants
            'variant_label' => $extra['variant_label'] ?? '',
            'variant_price' => $extra['variant_price'] ?? $current,
            'variant_old_price' => $extra['variant_old_price'] ?? $old,
            'variant_image' => $extra['variant_image'] ?? '',
            'variant_badge' => $extra['variant_badge'] ?? '',
            'quantity_options' => $extra['quantity_options'] ?? [1, 2, 3],
            // comparison
            'compare_feature' => $extra['compare_feature'] ?? '',
            'compare_ours' => $extra['compare_ours'] ?? '',
            'compare_theirs' => $extra['compare_theirs'] ?? '',
            // reviews
            'review_name' => $extra['review_name'] ?? '',
            'review_location' => $extra['review_location'] ?? '',
            'review_text' => $extra['review_text'] ?? '',
            'review_rating' => $extra['review_rating'] ?? 5,
            'review_avatar' => $extra['review_avatar'] ?? '',
            'review_date' => $extra['review_date'] ?? '',
            'review_verified' => $extra['review_verified'] ?? '',
            // order
            'form_name' => $extra['form_name'] ?? 'আপনার নাম',
            'form_phone' => $extra['form_phone'] ?? 'মোবাইল নম্বর',
            'form_address' => $extra['form_address'] ?? 'সম্পূর্ণ ঠিকানা',
            'form_district' => $extra['form_district'] ?? 'জেলা',
            'form_area' => $extra['form_area'] ?? 'এলাকা',
            'form_note' => $extra['form_note'] ?? 'অর্ডার নোট',
            'delivery_zone' => $extra['delivery_zone'] ?? 'ঢাকার ভিতরে / বাইরে',
            'delivery_charge_inside' => $extra['delivery_charge_inside'] ?? 'ফ্রি',
            'delivery_charge_outside' => $extra['delivery_charge_outside'] ?? 'ফ্রি',
            'free_delivery_threshold' => $extra['free_delivery_threshold'] ?? '',
            'payment_methods' => $extra['payment_methods'] ?? 'ক্যাশ অন ডেলিভারি',
            'submit_label' => $extra['submit_label'] ?? $cta,
            // faq handled by rows
            'faq_question' => $extra['faq_question'] ?? '',
            'faq_answer' => $extra['faq_answer'] ?? '',
            // footer is intentionally not rendered
            'footer_about' => null,
            'footer_address' => null,
            'footer_phone' => null,
            'footer_email' => null,
            'footer_social' => null,
            'footer_policy' => null,
            'copyright' => null,
            // sticky
            'sticky_price' => $current,
            'sticky_cta' => $cta,
            'sticky_timer' => $countdown,
            // tracking
            'fb_pixel_id' => $settings->facebook_pixel_id ?? '',
            'ga_id' => '',
            'whatsapp_number' => $settings->whatsapp_number ?? '',
        ];
    }

    protected function rowsFrom(string $key, array $default): array
    {
        $rows = $this->extra[$key] ?? null;

        if (!is_array($rows) || $rows === []) {
            return $default;
        }

        $clean = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $clean[] = $row;
            }
        }

        return $clean ?: $default;
    }

    protected function arrayValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function number($value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', '৳', ' '], '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function defaultTrust(): array
    {
        return [
            ['icon' => 'fa-solid fa-shield-halved', 'title' => '১০০% আসল লেদার', 'text' => 'প্রমাণে ব্যর্থতায় মানিব্যাক'],
            ['icon' => 'fa-solid fa-box-open', 'title' => 'চেক করে পেমেন্ট', 'text' => 'ডেলিভারিম্যানের সামনে দেখুন'],
            ['icon' => 'fa-solid fa-truck-fast', 'title' => 'সারাদেশে ফ্রি ডেলিভারি', 'text' => 'অগ্রিম কোনো টাকা নেই'],
            ['icon' => 'fa-solid fa-arrow-rotate-left', 'title' => '৭ দিনের ওয়ারেন্টি', 'text' => 'সহজ রিটার্ন বা পরিবর্তন'],
        ];
    }

    protected function defaultGallery(): array
    {
        return [
            'frontend/landing/pictures/wallet-black.jpg',
            'frontend/landing/pictures/wallet-brown.jpg',
            'frontend/landing/pictures/wallet-navy.jpg',
            'frontend/landing/pictures/wallet-duo-tone.jpg',
        ];
    }

    protected function defaultVariants(): array
    {
        return [
            ['label' => 'ক্লাসিক ব্ল্যাক', 'price' => 1025, 'old_price' => 1550, 'image' => 'frontend/landing/pictures/wallet-black.jpg', 'badge' => 'সর্বোচ্চ জনপ্রিয়'],
            ['label' => 'চকলেট ব্রাউন', 'price' => 1025, 'old_price' => 1550, 'image' => 'frontend/landing/pictures/wallet-brown.jpg', 'badge' => 'লাক্সারি ফিনিশিং'],
            ['label' => 'রয়্যাল নেভি ব্লু', 'price' => 1025, 'old_price' => 1550, 'image' => 'frontend/landing/pictures/wallet-navy.jpg', 'badge' => 'নতুন এক্সক্লুসিভ'],
        ];
    }

    protected function defaultComparison(): array
    {
        return [
            ['feature' => 'মূল মেটেরিয়াল', 'ours' => '১০০% আসল জেনুইন কাওহাইড লেদার', 'theirs' => 'নকল প্লাস্টিক রেক্সিন বা পিইউ লেদার'],
            ['feature' => 'স্থায়িত্ব ও ব্যবহারকাল', 'ours' => '৫ থেকে ১০ বছরেও চামড়া ফাটবে না', 'theirs' => '২-৩ মাসেই কোনা ফেটে চামড়া উঠে যায়'],
            ['feature' => 'সেলাই ও চেইন কোয়ালিটি', 'ours' => 'অ্যান্টি-রাস্ট মেটাল জিপার ও নাইলন ডাবল স্টিচ', 'theirs' => 'দুর্বল পাতলা সুতা, কিছুদিনেই চেইন আটকে যায়'],
            ['feature' => 'কার্ড ও সিকিউরিটি', 'ours' => 'RFID ব্লকিং ডাটা থেফট প্রোটেকশন', 'theirs' => 'কোনো ধরনের ডাটা নিরাপত্তা নেই'],
            ['feature' => 'ডেলিভারি ও যাচাই সুবিধা', 'ours' => 'প্যাকেট খুলে সামনে দেখে নিশ্চিত হয়ে পেমেন্ট', 'theirs' => 'আগে টাকা দিয়ে পার্সেল খুলতে হয়'],
        ];
    }

    protected function defaultReviews(): array
    {
        return [
            ['name' => 'কামরুল হাসান', 'location' => 'উত্তরা, ঢাকা', 'text' => 'চামড়ার গন্ধ ও সফটনেস দেখেই বোঝা যায় এটা খাঁটি গরুর চামড়া। চেইন ও সেলাই একদম নিখুঁত।', 'rating' => 5, 'verified' => 'ভেরিফায়েড বায়ার'],
            ['name' => 'জাহিদুল ইসলাম', 'location' => 'চট্টগ্রাম', 'text' => 'আমি চকলেট ব্রাউন কালারটি নিয়েছি। ওয়ালেটটি দেখতে অনেক প্রিমিয়াম আর পকেটে খুব আরামদায়ক ভাবে এঁটে যায়।', 'rating' => 5, 'verified' => 'ভেরিফায়েড বায়ার'],
            ['name' => 'সাইফুল ইসলাম', 'location' => 'রাজশাহী', 'text' => 'কম্বো প্যাকে ২টা ওয়ালেট নিয়েছিলাম ভাই আর নিজের জন্য। প্যাকিং দারুণ ছিল আর ২ দিনের মধ্যে ডেলিভারি পেয়েছি।', 'rating' => 5, 'verified' => 'ভেরিফায়েড বায়ার'],
        ];
    }

    protected function defaultFaqs(): array
    {
        return [
            ['question' => 'প্রোডাক্টটি কি সত্যিই আসল?', 'answer' => 'হ্যাঁ, এটি ১০০% খাঁটি ম্যাটেরিয়াল দিয়ে তৈরি। প্রমাণে ব্যর্থ হলে সম্পূর্ণ টাকা ফেরত দেওয়া হবে।'],
            ['question' => 'ডেলিভারি পেতে কতদিন সময় লাগবে?', 'answer' => 'ঢাকার ভেতরে সাধারণত ২৪ ঘণ্টার মধ্যে এবং ঢাকার বাইরে ৪৮ থেকে ৭২ ঘণ্টার মধ্যে ডেলিভারি দেওয়া হয়।'],
            ['question' => 'আমি কি ডেলিভারিম্যানের সামনে চেক করে নিতে পারব?', 'answer' => 'অবশ্যই! ডেলিভারি ম্যানের সামনে প্যাকেট খুলে চেক করে তারপর মূল্য পরিশোধ করার পূর্ণ স্বাধীনতা রয়েছে।'],
        ];
    }
}
