# STATUS — Arafat (Laravel) ফ্রন্ট-এন্ড আধুনিকায়ন

> **Path correction (2026-10-28):** the project path was corrected from
> `/home/sahon/Desktop/arafat` to `/home/sahon/Desktop/new evo`. The brand shown in-app is
> **"GS Corporation"**. Historical backup paths under `/home/sahon/uds/hi/arafat_bak/` were
> intentionally left unchanged.

**তারিখ:** ২০২৬-১০-২৮ (সর্বশেষ: ফ্রন্টএন্ড ধাপ শেষ — colour variant + default asset + event tracking + admin Pages; §৬.৬;
আগের সেশন: ২০২৬-১০-০৮ কন্টাক্ট/অ্যাবাউট + হেডার বাগ; ২০২৬-১০-০৭ ইংরেজিকরণ + এআই-সার্চ)
**প্রকল্প:** `/home/sahon/Desktop/new evo` — Laravel 10.50.3, MySQL `z_evo` (৩০ সক্রিয় পণ্য; ব্র্যান্ড নাম ইন-অ্যাপ "GS Corporation")
**ডিজাইন সোর্স:** `/home/sahon/Downloads/01/hi/` (Bootstrap 5 HTML ফাইল; হেডার/ফুটার/মোবাইল মেনুর
ক্যাননিক্যাল উৎস `freshmart_homepage_index.html`)
**এই ডকুমেন্টের উদ্দেশ্য:** একটি সম্পূর্ণ সেলফ-কন্টেইনড অনবোর্ডিং ডক — শুধু এই ফাইল পড়ে একজন নতুন
সেশনের এজেন্ট/ডেভেলপার প্রকল্পের বর্তমান অবস্থা বুঝে কাজ শুরু করতে পারবে। এখানে যা নেই, সেটা
`/home/sahon/z/monu_the_builder/` (Colab GPU গেটওয়ে — আলাদা প্রকল্প, তার নিজস্ব ডকুমেন্ট আছে)।

---

## ১. এক নজরে — এই প্রকল্প কী ও মূল নিয়মগুলো

দুইটি সমান্তরাল ফ্রন্ট-এন্ড আছে, একটা অ্যাডমিন ভ্যালু দিয়ে সুইচ হয়:

| থিম | ভিউ ট্রি | অবস্থা |
|---|---|---|
| **classic** | `resources/views/frontend/*` (পুরোনো, `frontend.pages.*`) | **হুবহু অক্ষত — স্পর্শ করা যাবে না** |
| **modern (fm)** | `resources/views/frontend/fm/*` | সক্রিয় উন্নয়ন — এই ট্রি নিয়েই কাজ |

**সুইচ:** ডিবি `general_settings.frontend_theme` (ডিফল্ট `classic`, বর্তমান মান `classic`)। অ্যাডমিন
স্ক্রিনে এক ভ্যালু বদলালেই পুরো সাইটের ডিজাইন বদলে যায় — কোডে হাত লাগে না।

**সবচেয়ে গুরুত্বপূর্ণ আর্কিটেকচার নিয়ম (লেআউট = সিঙ্গেল সোর্স অব ট্রুথ):**

1. **লেআউট (`frontend/fm/layouts.blade.php`) = সমস্ত কাঠামো ও নিয়মের একমাত্র জায়গা।**
   এর ভেতরেই: হেডার / ফুটার / মোবাইল মেনু (ডিজাইন সোর্স থেকে হুবহু), CSS লোড অর্ডার,
   `FM_CONFIG` (currencies, labels, URLs), শেয়ার্ড ইনক্লুড (এআই-সার্চ বাটন ইত্যাদি)।
2. **পেজগুলো (`frontend/fm/pages/*`) শুধু `<main>`-এর কনটেন্ট** রাখে এবং `@extends('frontend.fm.layouts')`
   করে। কোনো পেজই নিজের জন্য হেডার/ফুটার বানায় না।
3. **প্রতিটি পেজ তার ডেটা ঠিকমতো পায়:** ভিউ-ফাইন্ডার শুধু ভিউ-নাম রিরাইট করে, কন্ট্রোলার একই
   রুট একই ভ্যারিয়েবল-লিস্ট পাস করে — তাই পেজ লেখার সময় কন্ট্রোলারের `compact(...)` কী দেয়
   তা-ই ভিত্তি (নিচে §৩-এর কন্ট্রোলার নোট দেখুন)।
4. **`@include` চাইল্ডে প্যারেন্টের ভ্যারিয়েবল এক্সপোর্ট করে না** — বেশিরভাগ শেয়ার্ড ডেটার জন্য
   সাপোর্ট ক্লাস (যেমন `ShopPromises::all()`) ব্যবহার করতে হয়, `with()` নয়।
5. **কোডে বাংলা নেই:** কোড/কমেন্ট/ভেরিয়েবল/ক্লাস সব ইংরেজি। বাংলা শুধু আমাদের পারস্পরিক
   কথোপকথনে। *ডেটা বাংলা থাকতে পারে* (ডিবিতে পণ্য/ক্যাটাগরির নাম) — সেটা ডেটা, কোড নয়।
6. **কোয়েরি স্ট্রিং সবসময় আসল ফিল্টারের জন্য ফ্রি** — `?brand=`, `?q=`, `?page=`, `?min_price=` ইত্যাদি।
   থিম প্রিভিউ **কখনো কোয়েরিতে যায় না**, যায় সেশনে (`/site/theme/{theme}/preview`)।
7. **হক নষ্ট করা যাবে না:** কোনো এডিটের আগে ব্যাকআপ (ব্যাকআপ নিয়ম §৮)।

---

## ২. থিম সুইচ — কীভাবে কাজ করে (ইনফ্রাস্ট্রাকচার)

| ফাইল | কাজ |
|---|---|
| `app/Support/FrontendTheme.php` | রেজিস্ট্রি: `active()` / `stored()` / `previewing()` / `save()` / `startPreview()` / `clearPreview()` / `resolve()` / `modernViewFor()` / `coverage()` |
| `app/Support/ThemeViewFinder.php` | `FileViewFinder::find($name)` ওভাররাইট — ভিউ-নাম রিরাইট |
| `app/Support/ShopPromises.php` | সার্ভিস-স্ট্রিপ/সামারি/ট্যাবের অভিন্ন ৪টি প্রতিশ্রুতি (Deliveries/Payments/Returns/Support) |
| `app/Providers/AppServiceProvider.php` | `view.finder` এক্সটেনশন + `fm` অ্যাননিমাস কম্পোনেন্ট নেমস্পেস (`<x-fm::...>`) |
| `database/migrations/2026_10_06_000001_add_frontend_theme_to_general_settings_table.php` | `frontend_theme` varchar(32) ডিফল্ট `classic` — রান হয়েছে |
| `app/Http/Controllers/Admin/FrontendThemeController.php` | অ্যাডমিন স্ক্রিন + সেভ + প্রিভিউ |
| `resources/views/backEnd/website-setup/frontend-theme.blade.php` | অ্যাডমিন স্ক্রিন: ২টি থিম কার্ড — শুধু নাম + Live/Previewing ব্যাজ + প্রতি কার্ডে Preview ও Use-this-template; সামারি/পথ/ব্যাখ্যা-লেখা ও ৪০-সারির কভারেজ টেবিল সরানো (২০২৬-১০-৩১, সিবলিং অ্যাডমিন প্যাটার্নে); `FrontendTheme::coverage()` সাপোর্ট ক্লাসে আছে, শুধু স্ক্রিনে নেই |
| `resources/views/backEnd/layouts/partials/sidebar.blade.php` | সাইডবার আইটেম "Front-end Template" |

**রুট:**
- `GET  /admin/frontend-theme` → `admin.frontend-theme.edit`
- `POST /admin/frontend-theme` → `admin.frontend-theme.update`
- `POST /admin/frontend-theme/preview` → `admin.frontend-theme.preview`
- `GET  /site/theme/{theme}/preview` → `site.theme.preview` (সেশন-স্কোপড)
- `GET  /site/theme/clear` → `site.theme.clear`
- `GET  /cart/mini-items` → `cart.mini-items` (মডার্ন মিনি-কার্ট ফ্র্যাগমেন্ট)

**ম্যাপিং কনভেনশন:**
- `frontend.home → frontend.fm.pages.home`, `frontend.pages.X → frontend.fm.pages.X`
- যে ভিউ মডার্নে নেই, সে ক্লাসিকে ফলব্যাক করে, লেআউট ম্যাপ করা হয় না —
  মডার্ন পেজগুলো নিজে `@extends('frontend.fm.layouts')` করে।

**চুক্তি (প্রতিটি কন্ট্রোলার মানবে):** কন্ট্রোলার সবসময় **ক্লাসিক ভিউ-নাম** দেয়
(`frontend.home`, `frontend.pages.*`) — মডার্ন/ক্লাসিক সিদ্ধান্ত একমাত্র
`ThemeViewFinder → FrontendTheme::resolve()` করে। **হার্ডকোড `frontend.fm.*` কন্ট্রোলারে
দেওয়া যাবে না** — তাইলে থিম সুইচ সেই রুটকে নিয়ন্ত্রণ করে না (১০-৩১-২০২৬ হোমপেজে এভাবেই
ভাঙা ছিল: `frontendHome()` মডার্ন নাম হার্ডকোড করেছিল, তাই প্রিভিউ/সেভ হোমপেজ বদলাত না;
সমাধান: `frontend.home` ফিরিয়ে দেওয়া হয়েছিল)।

**ভেরিফাই (যা প্রমাণিত):** `save('modern')` → `/` = ~৩৮২ KB মডার্ন মার্কারসহ; `save('classic')` →
`/` = ~৮২৬ KB ও ০টি মডার্ন মার্কার। প্রিভিউ আসে শুধু নিজের ব্রাউজারের সেশনে।
আপডেট (১০-৩১-২০২৬): প্রিভিউও এবার live যাচাই করা হয়েছিল —
`/site/theme/classic/preview` সেসনে `/` ক্লাসিক রেন্ডার করে (owl-carousel/preloader, fm-marker ০),
`/site/theme/modern/preview` সেসনে fm মার্কারসহ।

---

## ৩. ফাইল ম্যাপ — কোথায় কী

```
resources/views/frontend/fm/
├── layouts.blade.php               হেডার/ফুটার/মোবাইল মেনু + CSS লোড অর্ডার + FM_CONFIG
├── pages/
│   ├── home.blade.php              হোমপেজ (শুধু <main>)
│   ├── products.blade.php          পণ্য তালিকা (Shop page)
│   ├── product-details.blade.php   পণ্য ডিটেইল (গ্যালারি/সামারি/ট্যাব/রিভিউ/রিলেটেড)
│   └── cart.blade.php              শপিং কার্ট (`GET /shop/cart`) — একবার গণনা করে ৩টি সেকশন ইনক্লুড করে
├── components/product-card.blade.php   <x-fm::product-card>
├── partials/
│   ├── cart-items.blade.php            মিনি-কার্ট লাইন ফ্র্যাগমেন্ট
│   ├── pagination.blade.php            পেজার (ফিল্টারসহ URL রাখে)
│   ├── ai-search-voice.blade.php       ভয়েস-সার্চ বাটন (হেডারে, এখন স্ট্যাটিক)
│   └── ai-search-camera.blade.php      ক্যামেরা-সার্চ বাটন (হেডারে, এখন স্ট্যাটিক)
├── sections/
│   ├── hero.blade.php, categories.blade.php (ব্র্যান্ড সারিসহ), product-feeds.blade.php,
│   ├── promo-banner.blade.php, testimonials.blade.php, newsletter.blade.php,
│   ├── shop-header.blade.php, shop-sidebar.blade.php, shop-toolbar.blade.php,
│   ├── detail-gallery.blade.php, detail-summary.blade.php, detail-tabs.blade.php,
│   ├── cart-progress.blade.php         ব্রেডক্রাম্ব + ৪-ধাপের স্টেপার (ক্লাসিকের ৪ লেবেল অক্ষত)
│   ├── cart-list.blade.php             লাইন-আইটেম কার্ড + খালি অবস্থা (ডিজাইন সোর্সের খালি-স্টেট)
│   └── cart-summary.blade.php          অর্ডার সারাংশ (Items/Subtotal/Shipping/Discount/Total) + কুপন + চেকআউট
└── sections/partials/
    ├── _section-head.blade.php, _product-grid.blade.php, _brand-tile.blade.php
```

**সাপোর্ট ও অ্যাসেট:**

| ফাইল | কাজ |
|---|---|
| `app/Support/FrontendTheme.php`, `ThemeViewFinder.php`, `ShopPromises.php` | উপরে §২ |
| `app/Http/Controllers/Frontend/FrontendController.php` | **শেয়ার্ড রুটের কন্ট্রোলার — এখানে বড় ধরনের বদল নয়।** মডার্ন পেজগুলো এই রুটগুলোর ভিউ-নাম প্রতিস্থাপন মাত্র। প্রোডাক্টস রুটে ফিল্টার/সর্ট/`withAvg` ইতিমধ্যে যোগ (ক্লাসিকও পায়) |
| `public/frontend/css/fm-*.css` | `index.css` (মূল ডিজাইন) + `fm-sections.css`, `fm-sections-home.css`, `fm-tap-target.css`, `fm-runtime-fix.css`, `fm-interaction.css`, `fm-ai.css`, `fm-products.css` (shop), `fm-product.css` (detail), `fm-cart.css` (কার্ট পেজ, `fm-basket-` প্রিফিক্স) |
| `public/frontend/js/index.js` | মূল ডিজাইনের (toast/back-to-top) |
| `public/frontend/js/fm-app.js` | আসল কার্ট/উইশলিস্ট/সার্চ/ড্রয়ার ওয়্যারিং |
| `public/frontend/js/fm-ai.js` | ভয়েস/ক্যামেরা সার্চ রিজার্ভ (স্ট্যাটিক) |
| `tools/bangla_to_english.py` | কোড থেকে বাংলা সরানো/যাচাইয়ের স্ক্রিপ্ট (§৪ শেষে) |
| `/home/sahon/uds/hi/verify/*.mjs` | ব্রাউজার হার্নেস (§৫) |
| `docs/PLAN-category-brand.md` | ক্যাটাগরি/ব্র্যান্ড সেশনের স্কোপ→রেকর্ড ডক (§৬.২) |
| `tools/split_design_sections.py` | ডিজাইন HTML থেকে মার্কার-ভিত্তিক Blade পার্শিয়াল কাটার স্ক্রিপ্ট (§৬.৩) |
| `fm/sections/header/*.blade.php` | সার্চ বার, ক্যাটাগরি সিলেক্ট, কুইক-অ্যাকশন (§৬.৩) |
| `fm/sections/ai/*.blade.php` | ভয়েস · ক্যামেরা · AI Lens পিল — প্রতিটি আলাদা (§৬.৩) |
| `fm/sections/design/` | স্প্লিটার-কাটা ৬টি ডিজাইন ব্লক + `manifest.json` (রেফারেন্স, সরাসরি ইনক্লুড হয় না) |

**CSS লোড অর্ডার** (শুধু `layouts.blade.php`-এ একবার, সেখানেই ডকুমেন্টেড):
`CDN fonts/bootstrap → index.css → fm-sections.css → fm-sections-home.css → fm-tap-target.css →
fm-runtime-fix.css → fm-interaction.css → fm-ai.css`; পেজ-লেভেল শীট `@push('css')`-এ (`fm-products.css`,
`fm-product.css`)।
**JS লোড অর্ডার:** `index.js → fm-app.js → fm-ai.js`; পেজ-লেভেল স্ক্রিপ্ট `@push('script')`-এ।

**কন্ট্রোলার নোট (ডেটা ঠিক করে আনা):**
- প্রোডাক্টস/হোম/ডিটেইল রুটগুলো আলাদা `compact()` লিস্ট পাস করে — পেজ লেখার আগে সেই রুটের
  `compact(...)` মিলিয়ে নিতে হবে; নতুন ডেটা দরকার হলে কন্ট্রোলারে যোগ করতে হবে (ক্লাসিক পড়ে
  যাওয়ার ভয়ে নয় — ক্লাসিকও একই ভিউ-নেমস্পেসে আলাদা টেমপ্লেট, তাই নিরাপদ)।
- ডিটেইল রুট: `$productReviews` (শুধু active, newest-first), `$productVariants`,
  `$variantAttributes`, `$related_products`, `$product_categories`, `$product_images`,
  `$product_thumbs`, `$product_videos` — সবই পাস হয়।

---

## ৪. কোড কনভেনশন (মানদণ্ড — নতুন কোড অবশ্যই মানবে)

1. **লেআউট = একমাত্র জায়গা** হেডার/ফুটার/মোবাইল মেনু/CSS অর্ডার/`FM_CONFIG`-এর; পেজ শুধু কনটেন্ট।
2. **কম্পোনেন্ট `@include` / `<x-fm::>` দিয়ে** — হাতে ডুপ্লিকেট মার্কআপ নয়। সিনট্যাক্স
   `x-fm::name` (ডট নয়)।
3. **প্রতিটি সেকশন `FM:SECTION` মার্কার কমেন্টে সীমাঙ্কিত** — কোন লাইন কোন সেকশনের, সহজে জানা যায়।
4. **`!important` নেই** (মূল ডিজাইনের নিজস্ব অডিট-ফিক্স ব্লক ছাড়া — ওই ব্লক অক্ষত থাকবে)।
5. **এক ক্লাস এক জায়গা** — একই ক্লাস দুটি CSS ফাইলে নয়; নতুন ক্লাস নতুন সেকশন-শীটে।
6. **পেজ-লেভেল CSS/JS `@push`-এ**, সর্বনিম্ন স্কোপে।
7. **ভেরিয়েবল প্রিফিক্স `$fm...`** (ব্লেড), `CFG.`/`data-` (ফ্রন্ট-টাইম) — ব্লেড লোকাল ব্লক
   ভেরিয়েবল (`@php`) সেকশন স্কোপে রাখা হয় যেন পেজে নাম-সংঘর্ষ না ঘটে।
8. **`@php` ব্লকে `@include` নেই**; `@php` ভেতরে শুধু গণনা।
9. **`{{ ... ?? default }}` বাধ্যতামূলক** — অ্যাননিমাস কম্পোনেন্টে ডিফল্ট প্রপ নেই।
10. **কেবলমাত্র fm-স্কোপে বাংলা-মুক্ত নিশ্চিতকরণ টুল** (নিচে)।

### বাংলা→ইংরেজি রক্ষণাবেক্ষণ (`tools/bangla_to_english.py`)
`--apply` বাংলা প্রতিস্থাপন করে, `--check` যাচাই করে (উভয়ই exit 0 দিলে পরিষ্কার)।

```bash
python3 tools/bangla_to_english.py --list    # কোন ফাইলে কত লাইন বাংলা আছে
python3 tools/bangla_to_english.py --apply   # PHRASE_MAP অনুযায়ী প্রতিস্থাপন
python3 tools/bangla_to_english.py --check   # অবশিষ্ট বাংলা নেই কিনা (0 = OK)
```

- **স্কোপ:** `resources/views/frontend/fm/**`, `app/Support/FrontendTheme.php`,
  `app/Support/ShopPromises.php`, `public/frontend/js/fm-app.js`, `public/frontend/js/fm-ai.js`,
  `public/frontend/css/fm-*.css`, `/home/sahon/uds/hi/verify/*.mjs`।
- **আউট-অফ-স্কোপ (স্পর্শ করা যাবে না):** ক্লাসিক ভিউ (`frontend/*`), `FrontendController`-এর ৪টি
  বাংলা স্ট্রিং (সেগুলো ক্লাসিক-শেয়ার্ড, fm কখনো দেখায় না)।
- **নিয়ম:** `PHRASE_MAP` দীর্ঘতম কী আগে (লংগেস্ট-ফার্স্ট); ফুল-সেন্টেন্স কী; `৳`→`Tk ` (ট্রেইলিং
  স্পেস), `০-৯`→`0-9`, `।`→`.`; JS এস্কেপ `\u09xx`-ও স্ক্যান হয় (`products.blade.php`-এর
  `'\u09F3'` সেখানে ধরা পড়ে)।
- **হাতে-করতে-হবে:** `lang="bn"` → `lang="en"` (স্ক্রিপ্ট ধরে না — ল্যাটিন)।
- fm-স্কোপে বাংলা ফিরে এলে `--check` fail করবে; নতুন বাংলা UI টেক্সট যোগ করলে কীও যোগ করতে হবে।

---

## ৫. ডেভ পরিবেশ ও যাচাই (হার্নেস)

**ডেভ সার্ভার** (opcache ছাড়া ~১.১s/রিকোয়েস্ট — তাই হার্নেসে পোলিং, কোডে অপেক্ষা নয়):
```bash
cd "/home/sahon/Desktop/new evo"
nohup php artisan serve --host=127.0.0.1 --port=8123 > /tmp/serve.log 2>&1 &
php artisan view:clear          # blade এডিটের পরে অবশ্যই
```
**হেডলেস ক্রোম** (CDP 9222):
```bash
google-chrome --headless=new --remote-debugging-port=9222 \
  --user-data-dir=/tmp/opencode/chrome-prof --no-sandbox --disable-gpu \
  --disable-dev-shm-usage --hide-scrollbars --window-size=1920,1080 &
```
**প্রিভিউ সেশন (শুধু নিজের ব্রাউজারের জন্য):**
```bash
curl -c /tmp/fmjar.txt -b /tmp/fmjar.txt -L 'http://127.0.0.1:8123/site/theme/modern/preview?back=/'
curl -c /tmp/fmjar.txt -b /tmp/fmjar.txt -L 'http://127.0.0.1:8123/site/theme/clear'
```

**হার্নেস স্যুট** (`/home/sahon/uds/hi/verify/`, ডিজাইন/এপ্লিকেশন কোড থেকে আলাদা — সেখানে কপি):

| ফাইল | কী চেক করে |
|---|---|
| `ui-check.mjs` | লাইভ সার্চ, কার্টে যোগ (toast+ব্যাজ), উইশলিস্ট, ড্রয়ার স্টেপার (inc/dec/remove), নেটওয়ার্ক+কনসোল ট্রেস |
| `products-check.mjs` | `/products`: ২৪ কার্ড, পেজার, গ্রিড/লিস্ট সুইচ, পিল+অ্যাপ্লাই → ক্লিন URL, সর্ট-রিটেনশন, ক্লাসিক ফলব্যাক, ৩৭৫px স্ক্রল, ০ এরর |
| `detail-check.mjs` | ডিটেইল ৩০টি চেক (ট্যাব, রিভিউ-স্ট্যাটস, ভ্যারিয়েন্ট পিল+check-stock, স্টেপার, অ্যাড-টু-কার্ট, থাম্ব সোয়াপ, ক্লাসিক ফলব্যাক...) |
| `cart-check.mjs` | `/shop/cart` **২৮টি চেক**: নিজে বাস্কেট সিড করে → ৪-ধাপ রেল, `ShopPromises` ট্রাস্ট-লিস্ট, +/− রি-পেইন্ট **এবং রিলোডে সার্ভার-ম্যাথের সাথে মিল**, কুপন বাতিল কোড রিজেক্ট/অ্যাপ্লাই (রিলোডেও রয়ে যায়), বাস্কেট বদলালে কুপন সত্যিই মুছে যায় (রিলোডেও), স্টক-ওভার কোডে ৪২২, qty ১-এ মাইনাস → রো মুছে যায়, শেষ রো মুছলে খালি-স্টেট + চেকআউট লিংক অক্ষত, ৩৭৫px স্ক্রল, ক্লাসিক ফলব্যাক, ০ এরর |
| `category-check.mjs` | `/category/{slug}` + `/products/brand/{slug}` **২৪টি চেক**: হেডিং ও ব্রেডক্রাম্ব সত্যি নাম দেখায় (BUG A), ফিল্টার-ফর্ম নিজের পাথেই সাবমিট করে — সত্যিকার সর্ট করে রুট ও ব্র্যান্ড-কনটেক্সট বাঁচে (BUG B), `?category=` দিয়ে পাথ-স্কোপড পেজের নাম বদলানো যায় না (BUG C), সর্ট সত্যিই গ্রিড রি-অর্ডার করে — `price_lth` রোহণীয় `[150,150,150,200,350,350]` (BUG D), পাথ-স্কোপডে ডিপার্টমেন্ট **লিংক**/`/products`-এ **রেডিও**, `?type=featured` টিকে থাকে, ৩৭৫px স্ক্রল, ক্লাসিক ফলব্যাক, ০ ফার্স্ট-পার্টি এরর |
| `resp-check.mjs [path]` | ৪ প্রস্থে (১৯২০/১৩৬৬/৭৬৮/৩৭৫) পাশ-স্ক্রল, অফেন্ডার, কলাম সংখ্যা |
| `shots.mjs` | ৪ প্রস্থ + ড্রয়ার স্ক্রিনশট → `shots/*.png` |
| `dom-probe.mjs` | নির্দিষ্ট DOM হুক ডাম্প |

**চালানোর ক্রম:** Chrome চালু → (fm-চেকের জন্য) প্রিভিউ কুকি → `node ui-check.mjs` /
`node products-check.mjs` / `node detail-check.mjs` / `node cart-check.mjs` /
`node category-check.mjs` / `node resp-check.mjs /products`।

**Chrome CDP না থাকলে:** `/tmp/opencode/start-cdp.sh` — `--headless=new` + আলাদা
`--user-data-dir` দিয়ে `:9222`-এ **দ্বিতীয়** ইনস্ট্যান্স চালায়, তাই ডেস্কটপে খোলা
ব্রাউজার ছুঁয়েও যায় না কিংবা কিছু মারা হয় না। আগের `restart-chrome.sh` গেছে
(`/tmp` মুছে গেছে), এটাই বর্তমান স্ক্রিপ্ট।

**কার্ট-হার্নেসের কুপন ইনজেক্ট/ডিলিট নিয়ম** (কুপন-স্টেপগুলো চালাতে চলমান কুপন লাগে;
সংখ্যা ডিবিতে ০ থাকে — পরীক্ষার পর মুছে দিতে হবে):
```bash
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB; $n=now();
DB::table("coupons")->insert(["code"=>"FMTEST10","type"=>"percent","value"=>10,
  "min_amount"=>null,"max_discount"=>null,"start_at"=>$n->copy()->subDay(),
  "end_at"=>$n->copy()->addDay(),"usage_limit"=>null,"used_count"=>0,"status"=>1,
  "created_at"=>$n,"updated_at"=>$n]); echo "inserted\n";'
# ... node cart-check.mjs ...
# ডিলিট:
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::table("coupons")->where("code","FMTEST10")->delete();
echo "cleaned\n";'
```
কার্ট-হার্নেস **নিজেই বাস্কেট রিসেট ও সিড করে** (৪৯১৬×২ + ৪৯১৪×১ = ৩টি আইটেম, ১০৫০ Tk) —
এই দুই পণ্যে `old_price 450 > price 350`, তাই সেভিংস-ক্যালআউট ৩০০ Tk দেখায়। শেষে বাস্কেট খালি থাকে।

**টেস্ট পণ্য:**
- `6788` — "Baby Cap, SP80-92": ভ্যারিয়েন্ট ids 2848/2849/2850, SKU `P6788-V1/V2/V3`,
  দাম ১০০/১৫০, বেস-স্টক ৬, ভ্যারিয়েন্ট-স্টক ২; ডিটেইল হার্নেসের প্রধান পণ্য। **রিভিউ = ০** (সর্বশেষে মুছে রাখা হয়েছে)।
- `6529` — গ্যালারি (একাধিক ছবি), দাম ৪৫০/৬৫০, স্টক ১; থাম্ব-সোয়াপ চেকের জন্য।
- `4916` — কার্ট-হার্নেস রো ১: দাম ৩৫০, `old_price` ৪৫০, স্টক ৪। **সিড: কুয়ান্টিটি ২।**
- `4914` — কার্ট-হার্নেস রো ২: দাম ৩৫০, `old_price` ৪৫০, স্টক ২। **সিড: কুয়ান্টিটি ১।**
  (৩ আইটেম × ৩৫০ = **১০৫০ Tk** সাবটোটাল; ৩ × (৪৫০−৩৫০) = **৩০০ Tk** সেভিংস;
  ১০% কুপন → ১০৫ ডিসকাউন্ট, ৯৪৫ টোটাল।)
- `4918` — স্টক ১ (শুধু ম্যানুয়াল স্টক-গার্ড পরীক্ষায় ব্যবহৃত; হার্নেসে `quantity=1000000` পাঠিয়ে ৪২২ আনে)।

**ডিটেইল-হার্নেসের রিভিউ ইনজেক্ট/ডিলিট নিয়ম** (যাচাইয়ের আগে ৩টি active রিভিউ লাগে,
পরীক্ষার পর মুছে দিতে হবে — ডেটাবেস আগের অবস্থায় ফিরানো মানেই শেষ):
```bash
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$cid=DB::table("customers")->value("id");
DB::table("reviews")->where("product_id",6788)->delete();
foreach (["Rafiul Islam"=>5,"Nusrat Jahan"=>5,"Tanvir Ahmed"=>4] as $n=>$r)
  DB::table("reviews")->insert(["name"=>$n,"email"=>"inject@test.local","ratting"=>$r,
    "review"=>"test review","product_id"=>6788,"customer_id"=>$cid,"status"=>"active",
    "created_at"=>now(),"updated_at"=>now()]);
echo "injected\n";'
# ... হার্নেস চালান ...
# ডিলিট:
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
DB::table("reviews")->where("product_id",6788)->where("email","like","inject%@test.local")->delete();
echo "cleaned\n";'
```
(গড় ৪.৭ → ৩টি রিভিউ ৫,৫,৪; ভেরিফাইড-ব্যাজের জন্য `customer_id` পূরণ করা লাগে।)

---

## ৬. বর্তমান অবস্থা (০৭ অক্টোবর — ইংরেজিকরণ সেশন)

**সারসংক্ষেপ:** fm-স্কোপে বাংলা কোড শূন্য, সব UI টেক্সট ইংরেজি, সব হার্নেস সবুজ, ডেটাবেস
আগের অবস্থায় (theme=`classic`, 6788-এ রিভিউ ০)। নিচে এই সেশনের প্রতিটি কাজ ও যাচাই।

**এই সেশনে যা যা হলো (চেঞ্জলগ):**
1. `tools/bangla_to_english.py`-এ `PHRASE_MAP` সম্পূর্ণ করা — ২৯৩টি বাংলা লাইনের জন্য
   দীর্ঘতম-কী-প্রথম (longest-first) ম্যাপ (২৯৩ লাইন/৪৪ ফাইলে ছিল; প্রতিটিরই কী-প্ল্যান করা)।
   কনভেনশন: `৳`→`Tk ` (ট্রেইলিং স্পেস), `০-৯`→`0-9`, `।`→`.`; ফুল-সেন্টেন্স কী; JS এস্কেপ
   `\u09xx`-ও স্ক্যান (`products.blade.php`-এর `'\u09F3'` সেখানে ধরা পড়ে)।
2. `--apply` → **২৫টি স্কোপ-ফাইল রিরাইট** (fm ভিউ, `FrontendTheme.php`, `ShopPromises.php`,
   `fm-app.js`, হার্নেস .mjs); `--check` → **exit 0** — fm-স্কোপে আর কোনো বাংলা নেই
   (গ্রেপ-ভেরিফাইড: ০টি ফাইলে বাংলা অক্ষর)।
3. `lang="bn"` → `lang="en"` — `layouts.blade.php` (স্ক্রিপ্ট ল্যাটিন শনাক্ত করে না, হাতে করা)।
4. `layouts.blade.php`-এর head থেকে **`{{-- FM LAYOUT ... --}}` কমেন্ট ব্লক রিমুভ** (কোড পরিষ্কার)।
5. **এআই-সার্চ বাটন আলাদা কম্পোনেন্ট:** `frontend/fm/partials/ai-search-voice.blade.php` ও
   `ai-search-camera.blade.php` (ইংরেজি aria/title), layouts-এর `<span class="fm-ai-inline"
   data-fm-inline-icons>`-এ `@include` দিয়ে বসানো — এখন স্ট্যাটিক (ভবিষ্যতে `fm-ai.js` দিয়ে তার)।
6. **বাগ ফিক্স:** `money()`-এ ডাবল-স্পেস — `CFG.currency + ' ' + value` → `CFG.currency + value`
   (currency-এর মান `'Tk '` ট্রেইলিং-স্পেসসহ, তাই অতিরিক্ত `' '` দিলে `Tk  100` হতো)।
   ধরা পড়েছে ডিটেইল-হার্নেসে, ঠিক করার পর পুনরায় পুরো স্যুট চালানো হয়েছে।
7. **পিকি-ফিক্স:** ডিকশনারিতে `pickAll` কী-র টাইপো, `সব অফার`/`শুধু স্টকে আছে`-র কোট-ম্যাচ
   (HTML attr-এ কোট নেই), মিসিং `হেলপলাইন`/`তারা` কী, `সর্বস্বত্ব`-এর লিডিং-স্পেস — সব ধরার পর `--check` ক্লিন।

**হার্নেস (সর্বশেষ রান, সব সবুজ):**
- `detail-check.mjs` → **30/30 পাস** (রিভিউ ৩টি ইনজেক্ট করে — গড় ৪.৭, ব্যাজ ×৩ — তারপর ডিলিট)
- `products-check.mjs` → **12/12 পাস**
- `resp-check /` ও `/products` → ৪ প্রস্থে ০ পাশ-স্ক্রল, ক্লাসিক ফলব্যাকে ০ মডার্ন
- `ui-check.mjs` → সার্চ/কার্ট/উইশলিস্ট/ড্রয়ার সবগ্রিন; শুধু pre-existing Tailwind CDN warning

**যাচাইকৃত নন-রিগ্রেশন (ইংরেজিকরণে ক্লাসিক/কোয়েরি অক্ষত):**
- ক্লাসিক ডিজাইন হুবহু অক্ষত — ক্লাসিক ফাইল স্পর্শই করা হয়নি (স্কোপের বাইরে)
- `selectCategory('All categories')` সেন্টিনেল নিরাপদ — `search()` শুধু `q` পড়ে
- `/products?brand=x`, `/search?q=shirt`, `/products?page=2`, `/livesearch?q=shirt`,
  `/category/premature?sort=price` → সব ২০০; না-থাকা পেজে ক্লাসিক ফলব্যাক
- থিম সুইচ এন্ড-টু-এন্ড (modern/classic বাইট-ভিন্ন, ক্লাসিকে ০টি মডার্ন মার্কার)
- লাইভ ইন্টারঅ্যাকশন: ০ কনসোল এরর, ০ ফেলড রিকোয়েস্ট (ডিবাগবারের নিজস্ব JS এক্সেপশন ফিল্টার করা)

**বর্তমান অবস্থা (ডেটাবেস/এনভায়রনমেন্ট):**
- `general_settings.frontend_theme` = **`classic`** (ডিফল্ট); প্রিভিউ সেশনেই মডার্ন দেখা যায়
- পণ্য 6788-এ রিভিউ **০** (পরীক্ষার ৩টি মুছে ফেলা হয়েছে — ডেটাবেস আগের অবস্থায়)
- ডেভ সার্ভার 8123 + হেডলেস ক্রোম 9222 চালু রাখা হয়েছে

---

## ৬.১. কার্ট পেজ সেশন (০৮ অক্টোবর) — চেঞ্জলগ ও প্রমাণ

**সারাসংক্ষেপ:** `/shop/cart` মডার্ন থিমে রূপান্তর শেষ। `cart-check.mjs` **২৮/২৮ পাস** (পরপর ৪ রান সবুজ)।
এই সেশনে **ক্লাসিক ট্রির ০টি ফাইল এডিট** (`find -newermt '2026-10-08'` → খালি; ক্লাসিক কার্ট পেজের mtime
এপ্রিল ২০২৬ থেকে অক্ষত)। ডিবি ফিরে গেছে: `coupons=0`, কার্ট খালি, `frontend_theme=classic`।

**নতুন ফাইল (৫টি):**

| ফাইল | কাজ |
|---|---|
| `pages/cart.blade.php` | `GET /shop/cart`। `@php`-তে একবার গণনা (`$fmLines/$fmQty/$fmSubtotal/$fmShipping/$fmDiscount/$fmTotal/$fmSavings/$fmCouponEnabled`) → ৩টি সেকশন ইনক্লুড → `@push('css')`/`@push('script')`। কন্ট্রোলার শুধু `$data` দেয়, তাই সব মান সেশন/কার্ট অপশন থেকেই আসে — নতুন কোয়েরি নেই। |
| `sections/cart-progress.blade.php` | ব্রেডক্রাম্ব + ৪-ধাপের স্টেপার। শুধু ধাপ ১ আসল লিংক (`route('cart.show')`)। |
| `sections/cart-list.blade.php` | লাইন-কার্ড (থাম্ব/ভ্যারিয়েন্ট/নাম/ইউনিট-দাম/লাইন-টোটাল/`<s>` পুরোনো-দাম/`.fm-qty`/Remove) + খালি-স্টেট। |
| `sections/cart-summary.blade.php` | Items/Subtotal/Shipping/Discount/Total + সেভিংস-ক্যালআউট + কুপন (ইনপুট ও অ্যাপ্লাইড-চিপ **দুটোই DOM-এ**, `hidden` টগল) + চেকআউট লিংক + `ShopPromises::all()` ট্রাস্ট-লিস্ট। |
| `public/frontend/css/fm-cart.css` | `fm-basket-` প্রিফিক্স, `!important` নেই, `.fm-qty` রি-ইউজ (ডুপ্লিকেট নয়)। |

**লেআউট এডিট (`fm/layouts.blade.php`, ৩টি):**
1. ড্রয়ার ফুটারে **"View full cart"** লিংক → `/shop/cart`। আগে মডার্নে কার্ট পেজে যাওয়ার **কোনো পথই ছিল না**
   (হেডার বাটন শুধু ড্রয়ার খোলে)।
2. `FM_CONFIG`-এ `cartUpdateUrl` / `couponApplyUrl` / `couponRemoveUrl` যোগ।
3. **আগে থেকেই থাকা ২টি বাগ ফিক্স (সব মডার্ন পেজে প্রভাবিত ছিল):**
   - `\Illuminate\Support\Facades\Cart` ক্লাসটি **বিদ্যমান নয়** → `Error` থ্রো → `catch` গিলে ফেলত → হেডার
     ব্যাজ ও মোটাল **সবসময় `0` / `Tk 0`** পেইন্ট হতো (JS পরে ঠিক করত, কিন্তু ফার্স্ট-পেইন্ট ভুল)।
     সংশোধন: প্যাকেজ অ্যালিয়াস `\Cart::` — ঠিক যেমন `partials/cart-items.blade.php` ব্যবহার করে।
     প্রুফ: পরিবর্তনের আগে হেডার `Tk 1` (নিচে দেখুন), পরে `Tk 1,050`।
   - `$fmCart->subtotal()` **কমা-ফরম্যাটেড স্ট্রিং** (`"1,050.00"`) ফেরায়, সরাসরি `(float)` দিলে কমায় থেমে
     `1` হতো → হেডারে `Tk 1`। সংশোধন: `str_replace(',', '', ...)` করে কাস্ট (ক্লাসিকও একই ট্রিক করে)।
     এখন হেডার/ড্রয়ার/সারাংশ **তিনটিই `Tk 1,050`** দেখায়।

**প্রমাণ — `node cart-check.mjs` → ২৮/২৮ (পরপর ৪ রান সবুজ):**

- সিড: হার্নেস **নিজে** বাস্কেট রিসেট ও সিড করে (৪৯১৬×২ + ৪৯১৪×১ = ৩ আইটেম, ১০৫০ Tk) → পুনরুৎপাদনযোগ্য।
- রেন্ডার: ৪-ধাপ রেল, ২ রো, ক্লাসিক মার্কাপ লিক নেই, সেভিংস **৩০০ Tk** (৪৫০−৩৫০) × ৩, শিপিং `Tk 0`,
  ডিসকাউন্ট রো `hidden`, চেকআউট = `/customer/checkout`, রো-লিংক = আসল প্রোডাক্ট-স্লাগ, **০টি ডেড `#` লিংক**।
- **+ / − রি-পেইন্ট + রিলোডে সার্ভার-সত্য:** `+` → qty ৩ / লাইন-টোটাল ১০৫০ / Items ৪ / ১৪০০ Tk /
  ব্যাজ ৪ / হেডার `Tk 1,400`; **রিলোড করলেও হুবহু একই সংখ্যা** → ক্লাইয়েন্ট-অঙ্ক ও সার্ভার-রেন্ডার সমান (মূল প্রমাণ)।
  `−` → সব আবার ১০৫০/৩-এ ফেরে, রিলোডেও মিলে।
- **কুপন:** বাতিল কোড → `Invalid or expired coupon` টোস্ট + অবস্থা অক্ষত। `FMTEST10` → ডিসকাউন্ট ১০৫ /
  টোটাল ৯৪৫; **রিলোডেও রয়ে যায়** (সেশন সত্যিই সেট হয়েছে)। বাস্কেট বদলালে → `Applied coupon has been removed`
  টোস্ট + রো লুকায় + টোটাল ১৪০০; **রিলোডেও লুকিয়ে থাকে** (সেশন সত্যিই মুছেছে — ক্লাসিক শুধু DOM-এ মুছত,
  রিলোডে কুপন ফিরে আসত; সেই বাগ এখানে নেই)।
- **স্টক-গার্ড:** `POST /cart-update/{rowId}` `quantity=1000000` → **422** `{status:"error",
  message:"Not enough stock", quantity:3, available:4, max_qty:4}` — ঠিক সেই কন্ট্র্যাক্ট যা রোলব্যাক পড়ে;
  তারপর বাস্কেট অবিকল।
- **qty ১-এ মাইনাস → রো মুছে যায়** (ক্লাসিক পেজ-হ্যান্ডলারের আচরণ; বাটন disable **নয়**), অন্য রো টিকে থাকে।
- **শেষ রো মুছলে** → সার্ভার-রেন্ডারড খালি-স্টেট; **সারাংশ কার্ড ও চেকআউট লিংক তখনও আছে** (ক্লাসিকও রাখে)।
- ৩৭৫px: `scrollWidth=375 = innerWidth` → কোনো পাশ-স্ক্রল নেই। ড্রয়ারে `View full cart` লিংক আছে।
- কুকি ছাড়া → ক্লাসিক কার্ট আগের মতো। **০ কনসোল এরর, ০ ফেলড রিকোয়েস্ট** (প্রাইজড ৪২২ প্রোব বাদ)।
- স্ক্রিনশট: `verify/shots/cart-desktop.png`, `cart-coupon.png`, `cart-empty.png`, `cart-mobile.png`।

**ডিজাইন সোর্স (`hi/cart.html`) থেকে ইচ্ছাকৃত বিচ্যুতি:**
1. **স্টেপার:** ডিজাইনে ৩ ধাপ, ক্লাসিকে ৪ লেবেল → **৪ রাখা** (জিরো-লস ডিজাইনের চেয়ে বড়)।
2. **খালি বাস্কেট:** ডিজাইন পুরো গ্রিড লুকিয়ে শুধু খালি-স্টেট দেখায়; আমরা **সারাংশ কার্ড + চেকআউট লিংক রাখি**
   (ক্লাসিকও রাখে — লিংকটি কখনো লুকানো হয় না)।
3. **ট্রাস্ট-লিস্ট:** ডিজাইনের স্থানীয় কপি নয়, `ShopPromises::all()` (সাইটজুড়ে অভিন্ন ৪ প্রতিশ্রুতি)।
4. **মাইনাস:** ড্রয়ারের মতো disable নয় — ক্লাসিক পেজ-হ্যান্ডলারের মতো qty ১-এ রো মুছে।

**ব্লকব্যাক — ডিজাইন সোর্সে আছে কিন্তু ব্যাকএন্ড নেই (ইচ্ছাকৃতভাবে বানানো হয়নি):**

| ডিজাইনের ফিচার | কেন বাদ |
|---|---|
| ফ্রি-শিপিং থ্রেশহোল্ড ব্যানার | `general_settings`-এ কোনো ফ্রি-শিপিং কী নেই (সব কলাম পরীক্ষিত) |
| Select-All / Clear Cart | ফ্রন্টএন্ডে ক্লিয়ার-কার্ট রুট **নেই** (শুধু অ্যাডমিন `order/cart-clear`) |
| Save for Later | কোনো ব্যাকএন্ড/টেবিল নেই (`cart.shortlist` শুধু লিগেসি ভিউ) |
| ডেলিভারি স্লট / কুরিয়ার-নোট | চেকআউট ধাপ ২-এর জন্য; স্লট কনফিগ কোথাও নেই |
| এক্সপ্রেস-পে (Apple Pay/PayPal/Google Pay) | কোনো পেমেন্ট গেটওয়ে নেই |
| Sales-tax রো | ট্যাক্স কনফিগ নেই; ক্লাসিকও দেখায় না |
| ক্রস-সেল "Frequently Added" | `cart_show()` শুধু `$data` পাস করে — সুझাব দেওয়া হবে না |

**আরও নোট:**
- **কুপন ব্লক** `Coupon::running()->exists()` দিয়ে গেট করা → ডিবিতে ০ কুপন থাকলে রেন্ডারই হয় না
  (সেশনের শেষে `coupons=0`, তাই শেষ রানে ফর্ম অনুপস্থিত — প্রত্যাশিত)।
- **লাইন-অ্যাকশন অ্যাট্রিবিউট `data-fm-line-action`** — ইচ্ছাকৃতভাবে `data-cart-action` **নয়**
  (সেটি `fm-app.js`-র, শুধু ড্রয়ার রি-পেইন্ট করে → দুই হ্যান্ডলার একই ক্লিকে লড়ত)।
- **হার্নেস-স্টেক:** পেজ-লোডে `fm-app.refreshCart()` `/cart/count` আনে; ধীর সার্ভারে সেই রেসপন্স দেরিতে
  এলে ক্লিকের পরে এসে **পুরানো সংখ্যা দিয়ে ব্যাজ ওভাররাইট** করতে পারে। হার্নেস in-flight
  `/cart/count` ট্র্যাক করে সেটি এড়ায় (নিচে §১০-এ নতুন সমস্যা হিসেবে তালিকাভুক্ত)।

**ব্যাকআপ:** `/home/sahon/uds/hi/arafat_bak/cart-1007-2328/` (কাজের আগের fm ট্রি) —
`diff -rq fm <backup>` দেখায় ঠিক **১টি পরিবর্তিত ফাইল + ৪টি নতুন ফাইল**, বাকিটা অক্ষত।

---

## ৬.২. ক্যাটাগরি/ব্র্যান্ড পেজ সেশন (০৮ অক্টোবর) — চেঞ্জলগ ও প্রমাণ

স্কোপ-ডক: `docs/PLAN-category-brand.md` (শুরুর পরিকল্পনা + শেষ অবস্থা দুটোই এক ফাইলে)।

**প্রথম আবিষ্কার — "পেন্ডিং" ভুল ছিল:**
`ThemeViewFinder` প্রতিটি লুকআপে `frontend.pages.products` → `frontend.fm.pages.products`
ম্যাপ করে। `FrontendController::category()`, `products()` (চাইল্ড), `subcategory()`,
`brand()` — চারটিই ওই ভিউ ফেরায়, তাই **চারটি রুটই আগে থেকেই পূর্ণ আধুনিক শপ পেজ**
(সাইডবার ফিল্টার, টুলবার, গ্রিড, কার্ট-ফর্ম, পেজার) রেন্ডার করছিল। বাস্তবে ছিল ৪টি লুকানো ত্রুটি:

| # | ত্রুটি | প্রমাণ |
|---|---|---|
| **A** | ব্র্যান্ড পেজের `<h1>` ও ব্রেডক্রাম্ব বলছিল "All products & groceries" | `$fmHeading` শুধু কোয়েরি-স্ট্রিং থেকে বানানো; ব্র্যান্ড স্লাগ তো **পাথে** (`/products/brand/{slug}`) |
| **B** | ফিল্টার ফর্ম সবসময় `/products`-এ সাবমিট হতো | `action="{{ route('products.page') }}"` — ব্র্যান্ড পেজ থেকে সর্ট চাপলে ব্র্যান্ড-কনটেক্সট হারিয়ে **পুরো ক্যাটালগ** ফেরত |
| **C** | `?category=` কন্ট্রোলার অগ্রাহ্য করে, কিন্তু হেডিং মানত | `/category/premature?category=newborn` → **"Newborn"** শিরোনামে Premature পণ্যের তালিকা |
| **D** | কন্ট্রোলার-পাথের সব প্রাইস-সর্ট **500** দিত | `Unknown column 'price'` — টেবিলে `price` নেই, আছে `new_price` |

**D সম্পর্কে গুরুত্বপূর্ণ:** এটি **আগে থেকেই বিদ্যমান ও থিম-নিরপেক্ষ** বাগ — ক্লাসিকেও
`/category/premature?sort_by=price_lth` কুকি-ছাড়া 500 দিত। দুইটি সর্ট-ইমপ্লিমেন্টেশন
একে অপরের সাথে ড্রিফট করেছিল:

| ইমপ্লিমেন্টেশন | সর্ট করে | ফল |
|---|---|---|
| `/products` ক্লোজার (`routes/web.php:322`) | `new_price` | 200 ✅ |
| `FrontendController::applyProductListingSort()` (`:45`) | `price` | **500** ❌ |

সংশোধন `'price'` → `'new_price'` (দুই শাখাই) — কোনো নতুন আচরণ নয়, কাজ করা ভাই-সিবলিং
ইমপ্লিমেন্টেশনের সাথে মিলিয়ে ভুল রেফারেন্স ঠিক করা। এতে ৬টি কল-সাইট বাঁচে:
`category`, চাইল্ড-ক্যাটাগরি, `subcategory`, `brand`, এবং `:1253`/`:1289`-এর সার্চ-দুটো।
(এটাই একমাত্র `FrontendController` পরিবর্তন — ক্লাসিকের 500→200 করে, তাই গ্রহণযোগ্য।)

**পরিবর্তিত ফাইল (৬টি):**

1. `resources/views/frontend/fm/pages/products.blade.php`
   - `$fmHeading` চেইনে `$pageTitle` ফলব্যাক (`isset()` গার্ডসহ) → **A সমাধান**।
     `/products` ক্লোজার `pageTitle` পাঠায় না, তাই সেখানে কোনো বদল নেই।
   - পাথ-স্কোপড রুটে **রুট কোয়েরির চেয়ে জয়ী** — `category`, `products.category`,
     `products.brand` `$fmCategorySlug` রুট-প্যারামিটার থেকে নেয় → **C সমাধান**।
2. `resources/views/frontend/fm/sections/shop-sidebar.blade.php`
   - `action="{{ request()->url() }}"` (বর্তমান পাথ, কোয়েরি ছাড়া) → **B সমাধান**।
     GET ফর্ম কোয়েরি-স্ট্রিং সম্পূর্ণ প্রতিস্থাপন করে, তাই পাথই রুট-কনটেক্সট বহন করে।
   - `brand[]` শুধু **ব্লক না থাকলে** হিডেন ইনপুটে বহন (`@unless($fmFilterBrands->isNotEmpty())`)।
   - ক্যাটাগরি-ব্লক **রুট অনুযায়ী ভাগ**: পাথ-স্কোপড রুটে **লিংক**, `/products`-এ **রেডিও**।
     কারণ পাথ-স্কোপড রুটে কন্ট্রোলার `?category=` পড়ে না → সেখানকার রেডিও কখনোই কাজ করত না।
     ক্লাসিকও এভাবেই করে: `/category/premature`-এ **৩৯২টি ক্যাটাগরি-লিংক, শূন্য রেডিও**।
   - `type` হিডেন-ইনপুটের কোনো কাজ লাগেনি — সেটি আগে থেকেই ছিল; এই সেশনে যোগ করা
     দ্বিতীয় কপি (`type=featured&type=featured` হতো) সরিয়ে ফেলা হয়েছে।
3. `public/frontend/css/fm-products.css` — `a.fm-check-row[aria-current="page"]`
   দিয়ে লিংকের সিলেক্টেড-লুক (ইনপুট ছাড়া একই রঙ + কাউন্ট-চিপ)।
4. `app/Http/Controllers/Frontend/FrontendController.php` — **D সমাধান** (দেখুন উপরে)।
5. `docs/PLAN-category-brand.md` — নতুন (স্কোপ → রেকর্ড ডক)।
6. `verify/category-check.mjs` — নতুন হার্নেস।

**প্রমাণ:**

- **`category-check.mjs` → ২৪/২৪**, পরপর **৩ সবুজ রান** (তারপর URL-সহ ফেইলড-রিকোর্ডিং ও
  ক্যাশ-ডিসেবলিং যোগের পর আরও ২ সবুজ)।
- সত্যিকারের সর্ট প্রমাণ (শুধু কোয়েরি পৌঁছালেই নয়, গ্রিড ফিরে পড়ে):
  `price_lth` → `[150,150,150,200,350,350]` রোহণীয় ✅,
  `price_htl` → `[450,350,350,350,350,200]` অবরোহী ✅, `latest` → মূল্য-ক্রমবদ্ধ নয় ✅
  (অর্থাৎ `id`-ভিত্তিক, ঠিক `/products`-এর মতো)।
- স্ক্রিনশট: `verify/shots/category-desktop.png`, `verify/shots/category-mobile.png` (৩৭৫px পূর্ণ-পাতা)।
- `tools/bangla_to_english.py --check` → `OK - no Bengali in scope.`

**হার্নেস-শেখা (আগের সেশনের মতো সংরক্ষণযোগ্য):**
- `Network.setCacheDisabled(true)` আবশ্যক — ক্লাসিক-চেক প্রিভিউ-কুকি মুছে ঠিক
  **সেই একই URL** আবার রিকোয়েস্ট করে; ক্যাশে থাকা আধুনিক কপি সার্ভার কখনও না পাঠানো
  মার্কআপ পাঠিয়ে পাস করত।
- `loadingFailed`-এ শুধু এরর-টেক্সট আসে, তাই হার্নেস এখন **URL-সহ** রেকর্ড করে —
  নইলে ফেইল কোনো ফার্স্ট-পার্টি অ্যাসেট নাকি থার্ড-পার্টি রিসোর্স তা বোঝা যায় না।

**সীমানা/উল্লেখযোগ্য:**
- **হোম হেড-টেক্সটে `<b>sadf</b>` দেখায়** — `general_settings.description` (id=2, name=Adora)
  কলামে অ্যাডমিন-টাইপ করা জাঙ্ক বসে আছে। এটা **কোড-বাগ নয়**: Blade `{{ }}` সেটিকে escape
  করে, তাই HTML হিসেবে চলছে না (সঠিক আচরণ) — শুধু লেখাটাই অদ্ভুত। `hero.blade.php`-এ
  `?:` ফলব্যাক কাজ করে না কারণ কলাম খালি নয়, মান খালি হলেই ফলব্যাক দেখাত।
  ডিবি স্পর্শ করা হয়নি — অ্যাডমিনের সিদ্ধান্ত।
- **ব্র্যান্ডের ডিসপ্লে-নাম ডেমো-ডাটা অদ্ভুত** (`xiaomi-redmi` → `George`,
  `n/a` → `-`)। হেডিং ঠিক বলছে — ডিবি যা বলে তাই প্রিন্ট করছে। বাগ **নয়**,
  যাতে কেউ "ঠিক" করে না এটা এখানে লেখা আছে।
- **সাইডবারে ব্র্যান্ড-ফিল্টার ক্যাটাগরি/ব্র্যান্ড পেজে রেন্ডার হয় না** — কারণ
  ওই অ্যাকশন `$brandCounts` পাঠায় না। ক্লাসিকেও শপ-পেজে `name="brand"` ফিল্টার নেই,
  তাই প্যারিটি-নিরপেক্ষ; রুট যা অগ্রাহ্য করে তা বানানো হয়নি। রিপোর্টেড, বিল্ড করা নয়।

**ব্যাকআপ:** এই সেশনে কোনো নতুন ব্যাকআপ নেই — `cart-1007-2328/`-এর সাথে diff-এ এখন
৪টি পরিবর্তিত + ৬টি নতুন ফাইল (পরিবর্তিত: `layouts.blade.php`, `products.blade.php`,
`shop-sidebar.blade.php`, `fm-products.css`, `FrontendController.php` — শেষটি অ্যাপ-লেভেল)।

---

## ৬.৩. ডিজাইন-স্প্লিটার ও কম্পোনেন্ট ভাঙা (০৮ অক্টোবর) — স্ক্রিপ্ট ও AI কম্পোনেন্ট

**প্রশ্ন ছিল:** `work/2.md`-এর প্রতিটি ব্লকে মার্কার আছে, সেগুলো ভেঙে আলাদা কম্পোনেন্ট বানাতে
হবে — হেডারের সার্চ বার ও AI-এর প্রতিটি কম্পোনেন্ট আলাদা করে layout-এ ইনক্লুড করতে হবে।

### নতুন স্ক্রিপ্ট: `tools/split_design_sections.py`

ডিজাইন সোর্স একটি ফ্যাট ডকুমেন্ট; প্রতিটি পেজে হেডার/ফুটার/`aside`/ট্রাস্ট-স্ট্রিপ
আবারতার করা থাকে। চারটি হেডার-ভ্যারিয়েন্ট এভাবেই একে অপর থেকে সরে গিয়েছিল, তাই
কপি-পেস্ট নয় — **স্ক্রিপ্ট** লেখা হয়েছে, যা `data-section` / `data-purpose` /
`aria-label` / `id` মার্কারে কেটে একেকটি `.blade.php` বানায়।

| কমান্ড | কাজ |
|---|---|
| `split <src> --out <dir>` | সব টপ-লেভেল landmark আলাদা ফাইলে + `manifest.json` |
| `split <src> --dry-run` | শুধু দেখায়, ফাইল লেখে না |
| `extract <src> --id <id>` | **একটিমাত্র** এলিমেন্ট বের করে (AI বোতামের জন্য) |
| `extract <src> --stdout` | স্ক্রিনে দেখায় |

`work/2.md` → **৬টি ব্লক**: `shop-header`, `aside`, `catalog-pagination`,
`shop-guarantees`, `footer-footer-links`, `header-primary-header`
→ `resources/views/frontend/fm/sections/design/`।

**স্ক্রিপ্টের দুটো বাগ (নিজেই ধরা ও ঠিক করা):**
- `scan_tags`-এর রেগেক্স `</button>`-এর সাথে মিলত না → ক্লোজিং ট্যাগ একেবারে পড়া
  হত না → `extract` ফাইলের **শেষ পর্যন্ত** টেনে নিচ্ছিল, `split` মাত্র ১টি ব্লক দিচ্ছিল।
  এখন `<\s*(/?)\s*([A-Za-z]...)` দিয়ে দুটোই ধরে, আর attribute-এর `"` ট্র্যাক করে।
- `str.format()`-এ `{{--` → `{--` হয়ে যেত (Blade কমেন্ট নয়!) → এখন `replace()`-এ তৈরি।

### নতুন কম্পোনেন্ট (আলাদা আলাদা, layout-এ `@include`)

| ফাইল | কী |
|---|---|
| `sections/header/search-bar.blade.php` | পুরো সার্চ বার (`variant=desktop\|mobile`) |
| `sections/header/search-category-select.blade.php` | ক্যাটাগরি সিলেক্ট |
| `sections/header/quick-actions.blade.php` | wishlist · account · cart (+ AI Lens পিল) |
| `sections/ai/voice-search.blade.php` | ভয়েস ট্রিগার (`variant=inline\|pill`) |
| `sections/ai/camera-lens.blade.php` | ক্যামেরা/লেন্স ট্রিগার |
| `sections/ai/ai-lens-toggle.blade.php` | "AI Lens" পিল (pulsing dot) |
| `sections/guarantees.blade.php` | হোমের চার-আইটি ট্রাস্ট স্ট্রিপ |

মোবাইল স্ট্রিপ ও ডেস্কটপ বার — **একই কম্পোনেন্ট, দুইটি variant**; তাই দ্বিতীয় কপি নেই
(দ্বিতীয় `id="fmFilterForm"` সর্ট-কন্ট্রোল নষ্ট করত, যেমন ফিল্টার-ট্রলিতে হয়েছিল)।

**আচরণ আমাদেরই, ডিজাইনের mockup-এর নয়:**
- ডিজাইনে inline `onclick="openAiDrawer('voice')"` ছিল (শুধু স্ট্যাটিক মকআপে কাজ করে) —
  তিনটাই ট্রিগার এখন `data-fm-open` বহন করে, আর `fm-ai.js` একবার document-এ বাঁধে।
- ডিজাইন Material Symbols ব্যবহার করে; আমাদের UI সারা বুটস্ট্র্যাপ আইকনে → মিলিয়ে দেওয়া।
- ফর্মটা সত্যিকারের GET `route('search')`, আর `data-fm-search-input` হুক অক্ষত —
  জাভাস্ক্রিপ্ট বন্ধ থাকলেও সার্চ চলে, লাইভ-সাজেস্টও কাজ করে।
- `quick-actions`-এ সব `id`/`data-fm-*` হুক আগের মতোই (`cartCountBadge`, `cartTotalHeader`,
  `wishlistCount`) — নাম বদলালে add-to-cart-এর পরে ব্যাজ আটকে যেত, কারণ `fm-app.js`
  ওগুলো DOM-এ repaint করে।

### গ্যারান্টিজ সেকশন: ডিজাইনের কপি **ব্যবহার করা হয়নি**

ডিজাইনের ট্রাস্ট-স্ট্রিপ বলে: *"Same-Day Express"*, *"100% Fresh Guarantee"*,
*"Eco-Friendly Pack (100% biodegradable totes)"*, *"24/7 Farm Concierge"*। এই দোকান
এগুলো **পূরণ করে না** — লিখে দিলে পেজে চারটি মিথ্যা প্রতিশ্রুতি চলে আসত। তাই মার্কআপ
ডিজাইনের, কিন্তু কপি **`ShopPromises::all()`** থেকে (কার্ট-সামারি ও ডিটেইল-ট্যাব যেখান
ব্যবহার করে) — ফলে চারটি প্রতিশ্রুতি সব জায়গায় এক, একবারেই বদলানো যায়।

**একটি বাগ ধরা পড়েছিল:** `ShopPromises::all()` **array** ফেরায়, Collection নয় — তাই
`->isNotEmpty()` ফেলে `/` পেজ ৫০০ দিচ্ছিল। অন্য দুই জায়গায় শুধু `@foreach` থাকায় সমস্যা
হয়নি। এখন `!empty()`।

---

## ৬.৪. ক্লাসিক-পেজ অডিট ও সার্চ পেজ রূপান্তর (০৮ অক্টোবর)

**প্রশ্ন ছিল:** `resources/views/frontend/pages`-এর কোনগুলো নতুন টেমপ্লেটে যুক্ত হয়েছে।

### মেথড (অনুমান নয়, পরিমাপ)

`ThemeViewFinder::find()` → `FrontendTheme::resolve()` → `modernViewFor()`। এটাই একমাত্র
স্থান যেখানে ক্লাসিক নাম আধুনিক নামে বদলায়। তাই প্রতিটি ক্লাসিক পেজ নামের বিপরীতে
আধুনিক ক্যান্ডিডেট বের করে ডিস্কে ফাইল আছে কিনা মাপা হয়েছে — ব্যক্তিগত রায় নয়।

**ফলাফল: ১৭টি ক্লাসিক পেজের মধ্যে ৪টি fm-ফাইল ছিল** (`index`, `products`, `cart`,
`product-details`) → **১৩টি এখনও ক্লাসিক রেন্ডার করে**। ডিজাইন সোর্স (`hi/*.html`) নিজেই
ভুল নামে ছিল না; বরং আগের ধাপে বেশিরভাগ পেজ একসাথে করা হয়নি।

### যা করা হয়েছে: `search`

কারণ বাছাই **প্রমাণভিত্তিক**, স্বতন্ত্র পছন্দ নয়:
- এটিই একমাত্র অনুর্বর্ণ পেজ যেখানে ক্লাসিক মার্কআপে **বাংলা লেখা ছিল**
  ("Nটি পণ্য পাওয়া গেছে", "খুঁজতে চাওয়া শব্দ লিখুন");
- `hi/search.html` ডিজাইন ফাইল আছে, আর সেটি আসলে **ওই একই শপ লিস্টিং** — ফিল্টার
  সাইডবার, গ্রিড/লিস্ট সুইচ, রেজাল্ট কাউন্ট, পেজার (মাপা: `fmFilterForm`, গ্রিড বাটন,
  guarantees স্ট্রিপ — তিনটি ফাইলেই হুবহু এক)। তাই নতুন গ্রিড লেখা বদলে **বিদ্যমান
  শপ পেজই ডেলিগেট** করা হয়েছে; ফলে দ্বিতীয় কপি তৈরি হয় না।

**ফাইল:** `resources/views/frontend/fm/pages/search.blade.php`

সাইডবার-ডেটার জন্য কন্ট্রোলারে একটি পরিবর্তন: `search()` এখন
`withListingSidebar()` ব্যবহার করে (ক্যাটাগরি/ব্র্যান্ড রুটের মতো) — নইলে সাইডবারের
প্রতিটি কাউন্ট `0` দেখাত, কারণ `categoryCounts`/`priceCeiling`/`hasReviewData` আসত না।

**যা যাচাই করা হয়েছে:** হেডিং ও ব্রেডক্রাম টার্ম দিয়ে নামায়; টার্মটি `×`-সহ
চিপ হিসেবে দেখায় (ডিজাইনের active-filter স্ট্রিপের সাথে মিলিয়ে); ফিল্টার সাইডবার ও
টুলবার কাজ করে; মোবাইলে ফিল্টার collapse হয় (`fmFilterForm`-এর একটাই আইডি — ফিল্টার
ট্রলিতে যেভাবে হয়েছিল); ক্লাসিক পেজ অক্ষত।

### একটি ভুল ধরা পড়েছিল (আমারই)

প্রথম ভার্সনে `/search` (কোনো টার্ম ছাড়া) **২৪টি কার্ড** দেখাচ্ছিল। কারণ: কন্ট্রোলার
টার্ম খালি হলে কোনো ফিল্টারই প্রয়োগ করে না → পুরো ক্যাটালগ (মাপা: **১২৪০** টি)।
ক্লাসিক এটা নিজের শাখায় লুকিয়ে রেখেছিল; আমি সেটা **প্রকাশ** করে ফেলেছিলাম — একটা
সার্চ পেজে পুরো শপ দেখানো ভুল। এখন কোনো টার্ম ও কোনো ফিল্টার না থাকলে গ্রিড ও
পেজার দুটোই দেখানো হয় না।

### প্রমাণ

| | |
|---|---|
| `/search?q=romper` | 200 · h1 `Results for "romper"` · ২৪ কার্ড · pager আছে · `"romper" ×` চিপ |
| `/search` | 200 · **০ কার্ড** · ০ পেজার |
| `/search?q=zzzznotfound` | 200 · ০ কার্ড · "No products match …" |
| ক্লাসিক `/search?q=romper` (কুকি ছাড়া) | 200 · FM_CONFIG=0 → ক্লাসিক অক্ষত |
| ১৪৪০ / ৩৭৫ | কোনো h-স্ক্রল নেই · **০ কনসোল এরর** |
| রিগ্রেশন | products **12/12** · category **24/24** · detail **30/30** · cart **28/28** · ui exit 0 |
| বাংলা-চেক | `OK - no Bengali in scope` |

### এখনো পেন্ডিং (ডিজাইন ও রুট দুটোই আছে)

- `categories` / `main-categories` / `subcategory` → ডিজাইন `hi/category.html`
- `contact` → ডিজাইন `hi/contact.html`

`about-us`-এর ডিজাইন ফাইল **নেই** (`hi/` তে শুধু `freshmart_about_us…` নামে একটি
Stitch ফোল্ডার আছে, `hi/about.html` নয়) — তাই সেটি ছাড়া।

---

## ৬.৫. কন্টাক্ট ও অ্যাবাউট পেজ + হেডার অফ-স্ক্রিন বাগ ফিক্স (০৮ অক্টোবর)

**প্রশ্ন ছিল:** `/about-us`, `/site/contact-us` ও ফুটারের পেজগুলো ডিজাইন থেকে মিলিয়ে যুক্ত করতে।

### ফুটার → রুট অডিট (১৫টি লিংক)

| রুট | কোড | fm-ফাইল |
|---|---|---|
| `/about-us` | 200 | ❌ ছিল না → **এখন আছে** |
| `/site/contact-us` | 200 | ❌ ছিল না → **এখন আছে** |
| `/products`, `/categories` | 200 | শপ-পেজই (আগের কাজ) |
| `/track-order`, `/register` | 200 | ক্লাসিক (এই সেশনের বাইরে) |
| `/brands`, `/login`, `/faq`, `/offers`, `/policy/*` | 302 | লগইন-পেজে/পুনর্বিন্যাস |

### ১. কন্টাক্ট পেজ

**ডিজাইন বাছাই:** `hi/contact.html` বনাম Stitch-এর contact — দুটোই মাপা হয়েছে একই
(forms=2, textarea=1, img=2), তাই নতুন কিছু নয়; `hi/contact.html`-ই নেওয়া হয়েছে।

**তবে ডিজাইনের ক্লাস কপি করা হয়নি — কারণ ওগুলো আমাদের কাছে নেই।**
`contact.html` লেখা Material-3 টোকেনে: `bg-surface-container-low`,
`text-on-surface`, `font-label-md`, `text-label-md`। মাপা হয়েছে — **এই চারটিই
আমাদার Tailwind config-এ নেই** (`layouts.blade.php`-এর config ব্লকে `surface` ২টি,
`on-surface`/`font-label`/`text-label` **০টি** বার)। হুবহু কপি করলে পেজ স্টাইলহীন
দেখাত। তাই গঠন নিয়ে **আমাদার নিজস্ব টোকেনে** (`fm-sections-home.css`) নতুন করে লেখা।

**ফর্ম:** পোস্ট করে `route('contact.submit')` = বিদ্যমান এন্ডপয়েন্ট, এবং ঠিক চারটি
ফিল্ড — `FormController::contactSubmit` যা validate করে (`name`, `email`, `subject`,
`message`)। ডিজাইনে `urgency` রেডিও ও `orderId` ছিল; **দুটোই বাদ** — ব্যাকএন্ড এগুলো
validate বা store করে না, তাই পাঠালে ডেটা নীরবে পড়ে যেত। টপিক বাছাই `subject`
সিলেক্টে সামলানো হয়েছে।

**ম্যাপ এমবেড বাদ:** সাইটে ম্যাপ কনফিগ নেই; তৃতীয় পক্ষের হার্ডকোডেড কোঅর্ডিনেটে
iframe দিলে মৃত কন্ট্রোল হতো।

**তথ্যের উৎস:** `$contact` (গ্লোবালি শেয়ার্ড) — ঠিকানা, ফোন, ইমেইল; সোশ্যাল লিংক
`$socialicons`। `contacts` টেবিলে সোশ্যাল কলাম **নেই** (আছে: id, hotline, hotmail,
phone, email, address, maplink, status) — তাই ফুটারের Facebook/Official Mail যেখান থেকে
আসে (`social_media` টেবিল) সেখান থেকেই নেওয়া, যাতে পেজ ও ফুটারে ভিন্ন তথ্য না যায়।

### ২. অ্যাবাউট পেজ — ডিজাইনের লেখা **ব্যবহার করা হয়নি**

**ডিজাইন বাছাই:** তিনটি ভিন্ন সংস্করণ পাওয়া গেছে (78 KB / 75 KB / 69 KB), তিনটিই এক
গঠন; সবচেয়ে বড়টি (১১টি রেসপন্সিভ গ্রিড ব্রেকপয়েন্ট) নেওয়া।

**কেন শুধু গঠন:** ডিজাইনটি একটি কাল্পনিক অর্গানিক-গ্রোসারি সহবস্থার জন্য লেখা এবং দাবি
করে — *"FreshMart"*, *"85+ grower partners"*, *"proprietary optical AI"*,
*"patented hyperspectral sensors"*, *"regenerative soil"*। মাপা: এই দোকান **Adora**,
বাংলাদেশি বেবি-ফ্যাশন রিটেইলার, ১২ ক্যাটাগরি, ১২৪০ পণ্য। About পেজে ধার করা দাবি
লেখা এটিকে প্লেসহোল্ডার না, **সরাসরি মিথ্যা** বানায়।

তাই: **লেখা ডিবি থেকে গণনা করে** — `1,240` পণ্য, `12` ডিপার্টমেন্ট, `11` ব্র্যান্ড,
ঢাকা। কোনো সংখ্যা হাতে লেখা নেই, ক্যাটালগ বাড়লে নিজে থেকেই সত্য থাকবে।

**টাইমলাইন বাদ:** ডিজাইনে "Our Journey So Far" (২০২১/২০২২/২০২৩/২০২৪) আছে। এই দোকানের
খোলার তারিখ বা ইতিহাস কোথাও নেই — একটি বাস্তব ব্যবসার ইতিহাস বানানো ডিজাইন
সিদ্ধান্ত নয়, মিথ্যা বক্তব্য। ওই জায়গায় **ডিপার্টমেন্ট তালিকা ও প্রকৃত সংখ্যা**।

**পুরোনো পেজটি আরও খারাপ ছিল:** ক্লাসিক about-us বলছিল *"Visermart is a web-based
commercial center … in USA"* — ভুল দোকানের নাম, ভুল দেশ।

### ৩. হেডারে একটি প্রি-এক্সিস্টিং বাগ (সব পেজে ছিল)

মাপার সময় ধরা পড়ল: **৭৬৮px ও ৯৯২px-এ কার্ট বাটন ভিউপোর্টের বাইরে** —
`cart right=843` বনাম `viewport=768` (এবং `948` বনাম `992`)। কারণ? `fm-ai.css`-এ AI
Lens পিল ১০৬px জায়গা নিচ্ছিল, আর পেজ `overflow-x:hidden` থাকায় অতিরিক্ত অংশ
**নীরবে লুকিয়ে** ছিল — মানে ট্যাবলেট/স্মল ল্যাপটপে কার্ট বাটনটাই অদৃশ্য ছিল।

ফিক্স: পিলটি **১০২৪px-এর নিচে** লুকানো (`fm-ai.css`), আর ওই প্রস্থগুলোতে **ড্রয়ারে**
পাঠানো হয়েছে — নইলে ওই প্রস্থে AI সার্চ পুরোপুরি হারিয়ে যেত।

**ফিক্সের পর মাপা:** cart `right` → ৭৬৮px: **729** · ৯৯২px: **849** · ১২০০px: ১০৮১ ·
১৪৪০px: ১৩২৯ — **সব প্রস্থেই ভিউপোর্টের ভেতরে**।

### প্রমাণ

| পরীক্ষা | ফল |
|---|---|
| `/site/contact-us` | ২০০ · h1 `Contact us` · ফর্ম ৪টি ফিল্ড · `POST contact-submit` |
| **ফর্ম বৈধ POST** | `200 {"status":true,"message":"Message sent successfully"}` |
| **ফর্ম অসম্পূর্ণ POST** | `422` চারটি ফিল্ডের ত্রুটি সহ |
| `/about-us` | ২০০ · সংখ্যা `1,240` `12` `11` `Dhaka` · কোনো Visermart/FreshMart লিক নেই |
| **৫ পেজ × ৫ প্রস্থ** (375/768/1024/1280/1440) | প্রতিটিতে `scrollW < innerW` · **০ কনসোল এরর** |
| রিগ্রেশন | products **12/12** · category **24/24** · detail **30/30** · cart **28/28** · ui exit 0 |
| ক্লাসিক | `/about-us` ও `/site/contact-us` কুকি ছাড়া ২০০ · FM_CONFIG=0 |
| পরিষ্কার | রিভিউ ০ · কুপন ০ · প্রোব-বার্তা ০ · থিম `classic` |
| বাংলা-চেক | `OK - no Bengali in scope` |

---

## ৬.৬. ২৮ অক্টোবর সেশন — ফ্রন্টএন্ড ধাপ শেষ (প্রমাণসহ)

> **নতুন সেশনের জন্য দ্রুত প্রবেশ:** এই সেশনের সব দাবি একটি স্ক্রিপ্টে যাচাইযোগ্য —
> `bash verify/session-proof-2026-10-28.sh` → **৩১/৩১ PASS, exit 0** (২০২৬-১০-২৮-এ সবুজ)।
> ঘণ্টাব্যাপী পুনর্যাচাই নয়; স্ক্রিপ্ট ফেল করলে তবেই তথ্য পুনর্মূল্যায়ন।

### ৬.৬.১. কী করা হয়েছে (দুটি delegated job + arbiter-এর ছোট ফিক্স)

| # | কাজ | ফাইল (file:line) | অবস্থা |
|---|---|---|---|
| **A** | অ্যাডমিন সাইডবারে **Pages** dropdown (আগে orphan — URL টাইপ না করলে ঢোকা যেত না) | `resources/views/backEnd/layouts/partials/sidebar.blade.php` (links `pages.index` + `pages.create`) | ✅ browser click-through: `admin/page/manage` |
| **A** | কুরিয়ারে "Incomplete" বদলে **"No API Connect"** + credential hint | `resources/views/backEnd/courier/index.blade.php`, `resources/views/backEnd/order/courier_send.blade.php` | ✅ লেখা দুই জায়গায় |
| **A** | `STATUS.md`-এর ভুল প্রকল্প-পথ ও DB নাম সংশোধন | `STATUS.md` (header, §৫) | ✅ `arafat`→`new evo`, `adorazst`→`z_evo`, ১২৪০→৩০ পণ্য |
| **B** | হোমপেজ event listener ৩টি: `logo_click` / `search` / `favorite` — **দুই emitter-এই** (`__EVENT_LAYER__` GTM + `__EVENT_LOGGER__` DB) | `resources/views/frontend/partials/event-layer-head.blade.php` | ✅ listener ৩টি grep-এ আছে; browser click → DB-তে `Click_Logo=1`, `Search=1` |
| **B** | Analytics dashboard cache **৩ ঘণ্টা** (`Cache::remember` + `now()->addHours(3)`) | `app/Http/Controllers/Admin/AnalyticsDashboardController.php:267` | ✅ grep `addHours(3)` |
| **C** | **Colour variant test data** (আগে একটি পণ্যেরেও colour ছিল না — gap ছিল এটাই) | DB: attribute `colour` (id=5), values id 15/16/17, variants id 6/7/8, stock 20 প্রতিটিতে | ✅ নিচে ৬.৬.২ |
| **D** | **৩টি missing default asset** (প্রতি page load-এ 404) তৈরি | `public/uploads/default/no-image.png`, `public/uploads/category/default.png`, `public/uploads/settings/{default-logo,default-favicon}.webp` | ✅ চারটোই HTTP 200; console error ০ (আগে ৩) |

**Job A/B-এর বিস্তারিত spec ও acceptance output:** `docs/specs/2026-10-28-job-a-admin-pages-sidebar-courier.md`,
`docs/specs/2026-10-28-job-b-homepage-events-3h-cache.md`।

### ৬.৬.২. Colour variant প্রমাণ (প্রতিটি রঙে আলাদা দাম/ছবি/স্টক)

| Colour | value id | variant id | SKU | দাম | image | stock |
|---|---|---|---|---|---|---|
| Red | 15 | 6 | `P0001-RED` | ১১৫০ | `uploads/variant/variant-red.svg` | 20 |
| Blue | 16 | 7 | `P0001-BLUE` | ১২৫০ | `uploads/variant/variant-blue.svg` | 20 |
| Green | 17 | 8 | `P0001-GREEN` | ১৩৫০ | `uploads/variant/variant-green.svg` | 20 |

- **Server:** `GET /products/1/variant-detail?attribute_values=[15|16|17]` → 200 + `"found":true` + উপরের SKU/দাম/ছবি।
- **Browser:** প্রতিটি `.fm-attr-pill` click-এ `#fmDetailPrice` + `#fmDetailMainPhoto` + স্টক **একসাথে** বদলায় (৩টি `variant-detail` request, ০ console error)।
- **Stock-out:** variant 8 (Green) `available=0` করে দেখা → "Out of stock" + `[data-fm-cta]` disabled → পরে **20-এ restore** (স্ক্রিপ্টে ধরা আছে)।

**আর্কিটেকচার শেখা (ভুল ধরলে যেন না হয়):**
- ডিটেইল পেজ আসলে `resources/views/frontend/fm/pages/product-details.blade.php` + `public/frontend/js/fm-product-detail.js` ব্যবহার করে — **পুরনো `product_details.js`/`attributeBtn` নয়**। JS একটিমাত্র `variant-detail` call-এ পুরো bundle নেয় (photo+price+stock আলাদা call-এ নয়, তাই অসঙ্গতি হয় না)।
- `attributes_json` shape: `{"ids":{"colour":<valueId>},"attrs":{"colour":"Red"}}` — **ids map-এর key = attribute slug**, attribute_id নয়।
- Swatch রঙ আসে `attribute_values.code` (`#RRGGBB`); variant image relative path (`uploads/variant/...`) → `MediaPath::url`।
- `check-stock` route **GET-only**, query-তে `product_id` লাগে (JS `checkStockUrl`-এ বসানো)।
- **DB guard:** `general_settings` টেবিলে logo/favicon path বসানো থাকে, কিন্তু `.gitignore:44` = `/public/uploads/*` তাই file commit-এ যায় না → deploy-এ 404। Asset যোগ করলে `.gitignore` বাদ দিতে হবে, নইলে পুনরায় হারাবে।

### ৬.৬.৩. টেস্ট আর্টিফ্যাক্ট (ডিবিতে — সিদ্ধান্ত লাগবে)

| artifact | অবস্থা | প্রস্তাব |
|---|---|---|
| অর্ডার `id=4` (invoice 40733, ১২৭০৳, Pending) | checkout end-to-end test-এ তৈরি | মুছে ফেলা যেতে পারে — ইউজারের সিদ্ধান্ত বাকি |
| CMS page `id=2` "Terms and Conditions" | আসল কন্টেন্টসহ demo | ইচ্ছেমতো রাখা/মুছা — ইউজারের সিদ্ধান্ত বাকি |
| Colour variant data (৬.৬.২) | স্থায়ী রাখা উচিত — এটাই variant feature-এর একমাত্র test fixture | রাখুন |

### ৬.৬.৪. পরের ধাপ (backend phase — এখনো শুরু হয়নি)

1. **কুরিয়ার status flow অ্যাডমিনে** — "No API Connect" badge কেবল দেখায়; কুরিয়ার যোগ/কনফিগার করলে কী হয়, সেই flow যাচাই ও সম্পাদনা।
2. **Stock reservation visibility** — `inventory_reservations` (১৮০ মিনিট/৩ ঘণ্টা hold) অ্যাডমিনে কোথাও দেখা যায় না; expiry দেখার জায়গা লাগতে পারে।
3. **Analytics/cookie data read-only** নিশ্চিত করা (admin-only modification) — policy হিসেবে যাচাই।
4. **টেস্ট আর্টিফ্যাক্ট পরিষ্কার** (৬.৬.৩-এর দুটো সিদ্ধান্ত)।
5. **Known issues (আগে থেকেই, §১০)** — toast `kind` ব্যবহার হয় না; `/cart-count` order-inversion; দুই সর্ট-ইমপ্লিমেন্টেশনের ড্রিফট ঝুঁকি — এগুলো backend ধাপে ভাবা যেতে পারে।

---

## ৬.৭. ফুটার অ্যাডমিন পুনর্নির্মাণ ও ইমেজ ফলব্যাক (৩১ অক্টোবর)

### ৬.৭.১. স্ক্রিন কনট্রাক্ট (তিনটি কার্ড, একটি ফর্ম)

`GET /admin/website-setup/footer` (`admin.website-setup.footer`) এখন তিনটি কার্ড; লেখা/কন্টাক্ট/লিংক একই `footerContentAdminForm`-এ পোস্ট করে।

| কার্ড | কী আছে | কোথায় জমা হয় | যাচাই |
|---|---|---|---|
| **Footer content** | Quill about editor (live visible-length counter, 400-এর বেশি হলে submit আটকায়), copyright, contact address/phone/email | `general_settings.footer_about_text`, `copyright`; `contacts`-এর active row (`updateOrCreate`) | `AdminFooterContentTest` ৩টি টেস্ট |
| **Footer links** | repeater: group (`terms`/`site`/`useful`/`other`), label, URL (`https?://` বা `/`), new-tab; সর্বোচ্চ ৩০; ফাঁকা row বাদ | `general_settings.footer_links` (JSON) + marker `footer_links_present` | normalize/skip/marker-clear টেস্ট + storefront গ্রুপিং |
| **Social icons** | custom Bootstrap dropdown icon-grid: ১৬টি preset + custom FA class; add form ও edit modal একই picker | `social_media` (`title`,`icon`,`link`,`color`,`status`) | `AdminFooterSocialTest` ৫টি টেস্ট |

**UI সিদ্ধান্ত (কেন এমন):**
- helper-text-এর দেয়াল সরানো হয়েছে; প্রতিটি কার্ডে শুধু কাজের লেবেল, counter আর badge (`2 links`, `16 total`)।
- লিংকের ইনপুট কার্ডের `<form>`-এর বাইরে, তাই HTML5 `form="footerContentAdminForm"` দিয়ে associate করা — দুই Save বাটনই একই request পাঠায়।
- `#footer-about-editor .ql-editor`-এর `min-block-size: 7.5rem` — shared Quill CSS-এর 15rem ৪০০ অক্ষরের জন্য অপ্রয়োজনীয়।
- **Bootstrap 12-কলাম:** form-এ তিন ফিল্ডের row `col-md-4` (৩টি = ১২), জোড়া `col-md-6` (২টি = ১২); `col-lg` বাদ, তাই ৭৬৮px থেকেই ভাগ হয়। Picker menu `row row-cols-2 row-cols-lg-3 g-1` — ৯৯২px-এর নিচে ২ কলাম (কন্ট্রোল ~২৩৫px, ৩ কলামে লেবেল কাটে), ৯৯২px+ এ ৩ কলাম (মেনু ~৩৫৬px); পুরনো custom `.icon-picker__grid` CSS সরানো।
- **Picker control agora `.form-select`:** template-এর পিকার select, আমাদেরটা আগে `.btn` ছিল — তাই ৩৮px (input ৪৮px) আর left inset ২২px (input ১৫px), টেক্সট কখনোই Name/Link-এর সাথে বসত না। এখন `.form-select`-এ ৪৮px height, ১৬px text offset (১px border + ১৫px padding), ৮px radius, একই border colour, theme caret — 1440/800/375 তিনটিতেই input-এর সাথে হুবহু। শুধু `text-align: start` লোকাল, কারণ এটা `<button>`।
- **About text:** classic ও fm — Quill-এর `<p>`-এর ভিতরে `<p>` nesting বাদ দিয়ে `<div class="…-about-text">`; classic-এ ৬ লাইনের বাজেট `max-height: 8.925rem` (line-clamp block children-এ কাজ করে না)। DB-তে একবার escaped `<p>&lt;p&gt;…` save হয়েছিল (data, render bug নয়) — পরিষ্কার HTML দিয়ে rewrite করা।
- `<576px`-এ shared `.card-header.d-flex` column করে দেয়; এই তিন হেডারে শুধু title + ছোট badge, তাই locally `flex-direction: row` ফিরিয়ে এক লাইনে রাখা হয়েছে। লিংক টেবিলের wrapper-এ `position-relative` — নইলে visually-hidden header span ৩৭৫px-এ page-wide horizontal scroll বানায়।

**Icon catalog:** `App\Support\SocialIcons::all()`-এ ১৬টি entry; প্রতিটির `fa` (DB + classic storefront), `ri` (admin), `bi` (fm), brand `color`। প্রতিটি `ri` `public/admin/assets/vendor/fonts/iconify-icons.css`-এ এবং প্রতিটি `bi` Bootstrap Icons 1.11.3 CDN-এ আছে — স্ক্রিপ্টে যাচাই করা।

### ৬.৭.২. প্রমাণ (real run)

| চেক | কমান্ড | ফলাফল |
|---|---|---|
| Feature tests | `php artisan test --filter='AdminFooter'` | **8 passed (57 assertions)** |
| Acceptance gate | `bash /tmp/opencode/picker-size-check.sh` | **`PICKER_SIZE_CHECK: PASS`**, exit 0 |
| Gate যা মাপে | Chromium + computed style | 1440/800/375: picker ও input দুটোই `48px`, text offset `16px`, radius `8px`, একই font/border colour; menu = control width ও same left edge; 16 option, 0 clipped, overflow নেই |
| Rendered screen | authentic Blade render (tinker, logged-in user) | 132,986 bytes; ২টি picker × ১৬ option; `footer_links_present` ও `form="footerContentAdminForm"` আছে; পুরনো helper-text দেয়াল নেই |
| Picker interaction | real Chromium session | option click → hidden `icon=fab fa-facebook-f`, label `Facebook`, colour `#1877f2`, menu বন্ধ |
| DB write path | real controller action (`updateFooter`, `SocialMediaController@store`) | about/copyright/`footer_links` JSON/contact ও `social_media` row (id 129) সত্যিই DB-তে গেছে |
| Storefront | `GET /` → 200 | `<div class="mb-0 footer-about-text"><p>…</p></div>`, `Proof Home`, `Proof Terms`, `<i class="fab fa-facebook-f"></i>`; `&lt;p&gt;` count **0** |
| Restore | একই controller action | about আসল বাক্যে ফিরেছে, `footer_links=[]`, probe row মুছে (`social_rows=0`); `GET /` 200, escaped markup নেই |

**Live HTTP (৩১ অক্টো, owner-দেওয়া admin account):** `/admin/login` → `/admin/dashboard`; `/admin/website-setup/footer` 200; ফর্মে Name `Facebook` + picker option `fab fa-facebook-f` + link দিয়ে আসল submit → DB `social_media id=162`; admin table `1 Facebook Facebook https://facebook.com/jannatminimart Active`; `GET /` 200, 720,370 bytes, footer-এ `<i class="fab fa-facebook-f"></i>`; 1440/800/375-এ input ও picker দুটোই `h=48`, same width, overflow নেই। স্ক্রিনশট `/tmp/opencode/shots/`। পাসওয়ার্ড কোথাও লেখা নেই।

### ৬.৭.৩. ইমেজ ফলব্যাক (আগের ধাপ, একই সেশনে ডকুমেন্টেড)

- `general_settings.frontend_empty_image` upload (`appearance()`), `App\Support\MediaPath::fallbackPath/fallbackUrl/forgetFallback` — একটাই fallback SSOT; theme/section আলাদা default বানায় না।
- `php artisan images:audit [--area=frontend|backEnd] [--json] [--limit=N]` — `App\Support\ImageUsage` live scan; কোন `<img>` এখনো `MediaPath` দিয়ে যায় না তা দেখায়।
- বিস্তারিত: `docs/ADMIN-footer-2026-10-31.md`।

**ডেটা অবস্থা:** `general_settings.footer_about_text = <p>Jannat Mini Mart brings you quality products at the best price, delivered across Bangladesh.</p>` (escaped `&lt;p&gt;` নেই), `copyright = © 2026 Jannat Mini Mart`, `footer_links = []`, `frontend_empty_image = NULL`; `social_media` খালি (proof row id 129 মুছে ফেলা); contact = House 12, Road 3, Barishal / +8801700000000 / info@jannatminimart.com।

---

## ৭. পেজ রূপান্তর — অবস্থা ও অগ্রাধিকার

| অগ্রাধিকার | পেজ | রুট | অবস্থা |
|---|---|---|---|
| ১ | হোম | `home` | **শেষ ✅** |
| ২ | প্রোডাক্ট লিস্ট | `products.page` (`/products`) | **শেষ ✅** |
| ৩ | প্রোডাক্ট ডিটেইল | `product` (`/product/{id}`) | **শেষ ✅** — colour variant যাচাইসহ (§৬.৬.২); `verify/session-proof-2026-10-28.sh` |
| ৪ | কার্ট পেজ | `cart.show` (`/shop/cart`) | **শেষ ✅** — `cart-check.mjs` ২৮/২৮ (দেখুন §৬.১) |
| ৫ | ক্যাটাগরি/ব্র্যান্ড | `category`, `products.brand` | **শেষ ✅** — `category-check.mjs` ২৪/২৪ (দেখুন §৬.২); আসলে আগে থেকেই কনভার্টেড ছিল, ৪টি ত্রুটি ছিল |
| ৬ | সার্চ রেজাল্ট | `search` | **শেষ ✅** — `fm/pages/search.blade.php` (দেখুন §৬.৪) |
| ৭ | কন্টাক্ট | `contact` | **শেষ ✅** — ফর্ম বৈধ POST + মোবাইল/ট্যাব/ডেস্ক ঠিক (§৬.৫) |
| ৮ | অ্যাবাউট | `about.us` | **শেষ ✅** — সব সংখ্যা ডিবি থেকে (§৬.৫) |
| ৯ | ক্যাটাগরি লিস্ট (`categories`, `main-categories`, `subcategory`) | `categories.page` | পেন্ডিং |
| ৭ | স্ক্রিনশট রিভিউ ও ভিজ্যুয়াল অনুমোদন | — | অপেক্ষমাণ — `verify/shots/`-এ হোম/প্রোডাক্টস ৯ + ডিটেইল ৩ + কার্ট ৪ + ক্যাটাগরি ২ (১৮টি) ছবি ইউজারের অনুমোদনের জন্য |

**পরবর্তী সেশনের জন্য স্থগিত (ইউজার-নির্দেশিত, ০৭ অক্টোবর):**
- **হোম স্লাইডার ও সেকশন-স্ট্রাকচার আপডেট:** `hi/freshmart_homepage_index.html`-এর সাথে মিলিয়ে
  হোমের স্লাইডার ও সেকশন কাঠামো বদলাতে হবে — এই সেশনে করা হয়নি, পরবর্তী সেশনে প্রথম আলোচনা হবে।

**ক্লাসিক পেজ → আধুনিক রূপান্তরের অবস্থা (০৮ অক্টোবর যাচাই করা):**

`ThemeViewFinder` → `FrontendTheme::modernViewFor()` সব `frontend.pages.*` নামকে
`frontend.fm.pages.*`-এ ম্যাপ করে, আর ফাইল না থাকলে **ক্লাসিকই উত্তর দেয়** (তাই অর্ধেক
রূপান্তরিত অবস্থা ৫০০ দেয় না)। ১৭টি ক্লাসিক পেজের মধ্যে **৪টি** fm-ফাইল আছে:

| ক্লাসিক পেজ | fm ফাইল | অবস্থা |
|---|---|---|
| `index` | `pages/home.blade.php` | ✅ |
| `products` | `pages/products.blade.php` | ✅ |
| `cart` | `pages/cart.blade.php` | ✅ |
| `product-details` | `pages/product-details.blade.php` | ✅ |
| `search` | `pages/search.blade.php` | ✅ (এই সেশনে, §৬.৪) |
| বাকি **১২টি** | — | ❌ এখনও ক্লাসিক রেন্ডার হয় |

১২টির মধ্যে বাস্তবে যোগাযোগ হয় এমন: `categories`, `main-categories`, `subcategory`,
`contact`। বাকিগুলো (`offers`, `hotdeals`, `about-us`, `track-order`, `service`,
`campaigns`, `backpacks`, `page`) — ডিজাইন ফাইল ও কার্যকর রুট দুটোই নেই, তাই এগুলো
এখনো ক্লাসিক দেখায় এবং তা সমস্যা **নয়**।

**মডার্ন কভারেজের সত্য উৎস:** অ্যাডমিন স্ক্রিনের ৩৯-সারির কভারেজ টেবিল (কোন রুট কোন ভিউ পায়)।

**বাগ-মেমোরি (সমাধানকৃত — আবার যেন না হয়):**
- `FM_CONFIG` খালি → layouts-এর `<script>`-এ ডুপ্লিকেট ব্লক; ব্যাকআপ-ডিফ দিয়ে মেরামত।
- `Cart` ফ্যাকাডে প্যাকেজ-অ্যালিয়াস (`hardevine/shoppingcart` → অ্যাপ `Cart`)।
- মিনি-কার্ট মেটাডেটা `data-fm-cart-meta-count`/`-subtotal` (নেই তো textContent দিয়ে মুছে ফেলা)।
- `@include` ভ্যারিয়েবল এক্সপোর্ট করে না → `ShopPromises::all()`।
- `Review` মডেলের `customer()`/`product()` রিলেশন না থাকায় ল্যাটেন্ট ৫০০ (ডিটেইল+হোম) — যোগ হয়েছে।
- ভ্যারিয়েন্ট deselect → স্টক-টেক্সট বেস-অবস্থায় ফেরে (সংখ্যাছাড়া "In stock")।
- **হেডার ব্যাজ/মোটাল সবসময় `0`:** `fm/layouts.blade.php`-তে `\Illuminate\Support\Facades\Cart`
  (ক্লাস নেই) → `Error` → `catch` গিলে ফেলত → সব মডার্ন পেজে ফার্স্ট-পেইন্ট ভুল। সমাধান: `\Cart::` (০৮ অক্ট)।
- **হেডারে `Tk 1`:** `(float) Cart::subtotal()` — `subtotal()` কমা-ফরম্যাটেড স্ট্রিং (`"1,050.00"`)
  ফেরায় কাস্ট থেমে `1` হতো। সমাধান: `str_replace(',', '', ...)` করে কাস্ট (০৮ অক্ট)।
  চেক: হেডার/ড্রয়ার-সাবটোটাল/ড্রয়ার-টোটাল **তিনটিই** সারাংশের সাথে মিলতে হবে।

---

## ৮. ব্যাকআপ ও রোলব্যাক (প্রজেক্টে git নেই)

- `/home/sahon/uds/hi/arafat_bak/frontend-1006-1045/` — শুরুর অবস্থা
- `/home/sahon/uds/hi/arafat_bak/fm-1006-1247/` — fm ট্রি লেখার পর
- `/home/sahon/uds/hi/arafat_bak/cart-1007-2328/` — **কার্ট-পেজ কাজের আগের fm ট্রি**
  (রোলব্যাক: `diff -rq <প্রজেক্ট>/resources/views/frontend/fm <এই ডিরেক্টরি>/fm` —
  কাজের পর মাত্র ১টি পরিবর্তিত + ৪টি নতুন ফাইল দেখায়, বাকিটা অক্ষত)

মডার্ন ট্রি নষ্ট হলে কপি করে ফেরানো যাবে; ক্লাসিক ফাইলগুলো স্পর্শই করা হয়নি।
যেকোনো এডিটের আগে প্রাসঙ্গিক ফাইলের কপি `/tmp/opencode/`-এ রাখা নিরাপদ অভ্যাস।

---

## ৯. দ্রুত কমান্ড রেফারেন্স

```bash
# ★ নতুন সেশনের প্রথম কমান্ড — ২৮ অক্টোবর সেশনের সব দাবি যাচাই (~১০ সেকেন্ড)
bash verify/session-proof-2026-10-28.sh   # ৩১/৩১ PASS, exit 0 হতে হবে; ফেল হলে §৬.৬ দেখুন

# ডেভ সার্ভার + ভিউ ক্যাশ
cd "/home/sahon/Desktop/new evo" && php artisan serve --host=127.0.0.1 --port=8123
php artisan view:clear

# সেশন-স্কোপড প্রিভিউ
curl -c /tmp/fmjar.txt -b /tmp/fmjar.txt -L "http://127.0.0.1:8123/site/theme/modern/preview"
curl -c /tmp/fmjar.txt -b /tmp/fmjar.txt -L "http://127.0.0.1:8123/site/theme/clear"

# CLI থেকে থিম বদলানো
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
var_dump(\App\Support\FrontendTheme::save("modern"));'   # অথবা "classic"

# বাংলা-চেক
python3 tools/bangla_to_english.py --check
```

**লগ:** `storage/logs/laravel.log` | **স্ক্রিনশট:** `/home/sahon/uds/hi/verify/shots/`

---

## ১০. পরিচিত সমস্যা / সতর্কতা (নতুন নয়)

- `php artisan view:cache` আগে থেকেই ব্যর্থ — `shurjopayv2/laravel8/src` একটি নেই-এমন `views`
  ডিরেক্টরি লোড করে। নতুন সমস্যা নয়।
- লিগেসি `frontend/partials/cart-sidebar-popup.blade.php` এ `route('products.show')` ডাকে — এমন
  রুট নেই (আগে থেকেই ভাঙা; মডার্নে কপি করা হয়নি)।
- `AppServiceProvider::boot()` কনসোলে আগেই রিটার্ন করে — প্রোভাইডার ওয়্যারিং `register()`-এ থাকতে হবে।
- অ্যাননিমাস ব্লেড কম্পোনেন্টে ডিফল্ট প্রপ নেই — সব জায়গায় `?? default`।
- `x-fm::name` সিনট্যাক্স লাগবে, `x-fm.name` কাজ করে না।
- ব্র্যান্ড-সারির `.fm-brand-row`-এ ৮px গাটার-ম্যাথ — ওরিজিনাল ফাইলেও আছে, দোষ নয়।
- কনসোলে ডিবাগবারের নিজস্ব JS এক্সেপশন হার্নেসে ফিল্টার করা (অ্যাপ-ত্রুটি নয়)।

**নতুন আবিষ্কৃত (০৮ অক্টোবর — সমাধান করা হয়নি, পরের সেশনের সিদ্ধান্ত লাগবে):**

- **টোস্টের `kind` কোথাও ব্যবহার হয় না।** `FM.toast(msg, kind)` ডাক হলে `fm-app.js` সেটি
  গ্লোবাল `showToast(message)`-এ ডেলিগেট করে, আর `public/frontend/js/index.js:14`-এর
  `showToast` ইচ্ছেমতো সবসময় **সবুজ চেক-আইকন** দেখায় — তাই "Invalid or expired coupon" টোস্টও
  সফল-চেক আইকনে দেখায়। কার্ট-পেজের JS-এ এটা আপগ্রেড করা হয়নি (সার্ভার-সাইডের বার্তা ও
  রোলব্যাক সঠিক; শুধু আইকন/রঙ ভুল)। ঠিক করতে হলে `index.js`-এর গ্লোবাল `showToast`-কে
  `kind` মানতে হবে — সেটা সব পেজের টোস্ট প্রভাবিত করে, তাই আলাদা সিদ্ধান্ত।
- **`fm-app.refreshCart()` পেজ-লোডে `/cart-count` আনে এবং ধীর সার্ভারে সেই রেসপন্স দেরিতে এলে
  ইউজারের কোয়ান্টিটি-ক্লিকের পরে এসে ব্যাজকে পুরানো সংখ্যায় ওভাররাইট করতে পারে** (অর্ডার-ইনভার্শন)।
  ব্যবহারকারীর কাছে দুর্লভ (রেসপন্স সাধারণত ms-এ আসে), কিন্তু এটাই কার্ট-হার্নেসকে ফ্ল্যাকি করেছিল —
  তাই হার্নেস in-flight `/cart/count` ট্র্যাক করে প্রতিটি অ্যাকশন শুরু করে সেটি এড়িয়ে।
  আসল ফিক্স হবে `fm-app.js`-এ: লোকাল পেইন্টের পরে আসা পুরানো কাউন্ট বাদ দেওয়া (টাইমস্ট্যাম্প/সিকোয়েন্স)।

**নতুন আবিষ্কৃত (০৮ অক্টোবর — ক্যাটাগরি/ব্র্যান্ড সেশন):**

- **দুইটি সর্ট-ইমপ্লিমেন্টেশন, একটাই সত্য উৎস নয়।** `routes/web.php`-এর `/products`
  ক্লোজার (`:322`) আর `FrontendController::applyProductListingSort()` (`:45`) একই কাজ
  আলাদা করে — একটা `new_price` পড়েছিল, অন্যটা `price` (যা টেবিলে **নেই**) → পরেরটা ৬টি
  রুটে 500 দিত (§৬.২-এ BUG D)। এখন দুটোই `new_price`, কিন্তু **ডুপ্লিকেশন টিকে আছে** —
  নতুন সর্ট-কী একটায় যোগ করে অন্যটায় না করলে আবার ড্রিফট করবে। স্থায়ী সমাধান হবে একটি
  শেয়ার্ড হেল্পার, কিন্তু সেটা `/products` ক্লোজার ও ক্লাসিক রুট ছুঁয়ে যায় — আলাদা সিদ্ধান্ত।
- **পাথ-স্কোপড রুটে সাইডবারের ব্র্যান্ড-ব্লক রেন্ডার হয় না**, কারণ ক্যাটাগরি/ব্র্যান্ড
  অ্যাকশন `$brandCounts` পাঠায় না (`$brands` তো `AppServiceProvider:121` থেকে সব ভিউতে
  শেয়ার্ড)। ক্লাসিকেও শপ-পেজে `name="brand"` ফিল্টার নেই → প্যারিটি-নিরপেক্ষ, বানানো হয়নি।
  সেই রুটগুলো `brand[]` পড়েও না, তাই সেখানে চেকবক্স দিলে **কাজ না করা নিয়ন্ত্রণ** হতো।
- **ব্র্যান্ডের ডিসপ্লে-নাম ডেমো-ডাটা অদ্ভুত** — `xiaomi-redmi`→`George`, `n/a`→`-`।
  বাগ নয় (হেডিং ডিবিতে যা আছে তাই প্রিন্ট করে), তবে কেউ ভাববে "ব্র্যান্ড-নাম দেখাচ্ছে না"
  → সমাধান ডাটা ঠিক করা, কোড নয়।
- **থার্ড-পার্টি ট্র্যাকার হার্নেসে ইন্টারমিটেন্ট ফেইলিং ঘটায়।** পেজে রয়েছে
  `facebook.com/tr`, `connect.facebook.net`, `googletagmanager.com`, `cdn.jsdelivr.net`,
  Google Fonts — এদের একটা কখনো `net::ERR_BLOCKED_BY_ORB` দেয় (Chrome Opaque Response
  Blocking), যা **সাইটের অ্যাসেট ভাঙা নয়**। তাই `category-check.mjs` এখন ফেইল রিকোর্ড
  **URL-সহ** রাখে ও ফার্স্ট-পার্টি/থার্ড-পার্টি আলাদা করে: শুধু ফার্স্ট-পার্টি ফেইলিং
  রান ফেল করে, থার্ড-পার্টি INFO হিসেবে প্রিন্ট হয় (লুকানো নয়)। `cart-check.mjs`-এ এই
  শ্রেণিবিন্যাস নেই — পরে যোগ করলে সেটাও স্থিতিশীল হবে।

---

## ১১. অন্যান্য প্রকল্প (এখানে নয়)

- **Colab GPU গেটওয়ে / `zombiecoder.my.id` / `gpu.zombiecoder.my.id`:** আলাদা প্রকল্প
  (`/home/sahon/z/monu_the_builder/`) — তার নিজস্ব ডকুমেন্ট সেখানেই। API key শুধু `.env`-এ
  `CUSTOM_PROVIDER_3_KEY` হিসেবে; কোথাও শেয়ার/লিখা নয়। `COLAB-RUN.py` শেয়ার করা যাবে না।