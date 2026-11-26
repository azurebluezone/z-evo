# রিপোর্ট ও অ্যানালিটিক্স আপগ্রেড — যাচাইকৃত রিভিউ ও সংশোধিত পরিকল্পনা (v2)

**প্রজেক্ট:** `/home/sahon/Desktop/new evo` (Laravel 10.50.3, PHP 8.4.27)
**ডেটাবেজ:** MySQL 8.0.46 · `z_evo` · 127.0.0.1:3306
**তারিখ:** ২০২৬-১১-১৬
**ভূমিকা:** রিভিউ + পরিকল্পনা। এখনো কোনো কোড বা মাইগ্রেশন লেখা হয়নি।

> **নিয়ম:** এই ডকুমেন্টের প্রতিটি দাবি কোড, ফাইল পাথ, লাইন নম্বর বা লাইভ SQL আউটপুট দিয়ে
> যাচাই করা। যেখানে অনুমান, সেখানে স্পষ্ট `অনুমান` লেখা। যেখানে প্রমাণ নেই, সেখানে
> `UNVERIFIED` লেখা — কোনোকিছু ফাঁকা করে "হয়ে গেছে" বলা হয়নি।

---

## 0. v3 — Dev-first baseline — `SEED-FIRST-V3`

> **মূল সিদ্ধান্ত:** এই ডেটাবেস প্রোডাকশন নয়, ডেভেলপমেন্ট। বর্তমান ৩০টি প্রোডাক্ট,
> ১০টি অর্ডার, ১৩টি পেমেন্ট, ৪টি courier shipment — সবই ডেভেলপমেন্ট ফিক্সচার।
> এগুলোকে "যাচাইকৃত বাস্তব ডেটা" ধরে প্ল্যান করা ভুল। এর বদলে **একটাই canonical
> seed baseline** বানাব; সেটাই dev SSOT, আর সেখান থেকেই ধাপে ধাপে রিপোর্ট/অ্যানালিটিক্স
> তৈরি হবে। `DEV-RESET-ALLOWED`

### 0.1 কেন v2-এর ডেটা-ভিত্তি বদলাল

- v2 ডেটা গ্যাপকে constraint ধরেছিল — "১০ অর্ডার, ০ রিটার্ন, ০ কুপন usage, তাই রিপোর্ট
  খালি দেখাবে"। ডেভ সাইটে এটা সমস্যা নয়, কারণ **ডেটা বানানো যায়**।
- প্রোডাকশন সাইট হলে legacy preservation/backfill লাগত। এখানে লাগবে না —
  `php artisan migrate:fresh --seed`-ই প্রমিত dev workflow।
- তাই লক্ষ্য বদলেছে: "পুরোনো ডেটা বাঁচানো" নয়, **"সঠিক ডেটা তৈরি করা"**।
- `products.stock` (legacy), `ProductStockSync`, raw `DemoOrderDetailsSeeder` — এগুলো
  dev-এ রাখার কোনো কারণ নেই। `DROP-LEGACY-STOCK`

### 0.2 seed-first আর্কিটেকচার — `DEV-CATALOG-SEED`

**একটাই canonical ক্যাটালগ সিডার, একটাই canonical অর্ডার সিডার।** বর্তমান চারটি
প্রোডাক্ট সিডার (`DemoStorefrontSeeder`, `StorefrontDemoSeeder`, `FreshCartTestDataSeeder`,
`ShinjukuProductsSeeder`) আলাদা আলাদা ভাবে `products.stock` লিখে একে অপরের সাথে
সংঘর্ষ করে। এগুলোকে `DatabaseSeeder` থেকে সরিয়ে নতুন layer-এ যেতে হবে।

| নতুন ফাইল | দায়িত্ব |
|---|---|
| `database/seeders/DevCatalogSeeder.php` | প্রোডাক্ট + gallery images + variants + `variant_stocks` + opening ledger |
| `database/seeders/DevOrderSeeder.php` | অর্ডার + order_details (variant_id) + payments + shipping |
| `database/seeders/DevReportFixtureSeeder.php` | returns/refunds, coupon usages, courier shipments/events — রিপোর্ট টেস্টের জন্য |
| `database/seeders/data/dev-catalog.php` | deterministic fixture manifest (slug, variants, prices, stock, image) |

**ক্যাটালগ সিডের নিয়ম — `SEED-VARIANTS-STOCK-IMAGES`:**

1. প্রতিটি প্রোডাক্টে **কমপক্ষে একটি variant**; প্রতিটি variant-এ unique `sku`,
   `title`, `attributes_json` (color/size), `price_override`, `purchase_price`,
   `sale_price`, `image`।
2. প্রতিটি variant-এ **অবশ্যই একটি `variant_stocks` row** — `on_hand`, `reserved=0`,
   `available`, `damaged=0`; কোনো variant stock row ছাড়া থাকবে না।
3. initial stock কেবল `InventoryMovement::record()` দিয়ে `OPENING_BALANCE` হিসেবে;
   `variant_stocks` হবে সেই লেজারের projection — direct insert নয়।
4. gallery: `productimages`-এ ২–৪টি image per product; primary image relation ভাঙবে না।
5. variant image path: `public/uploads/product/seed/{slug}/{sku}.webp` (বা `.png`);
   seed চালানোর আগে file existence যাচাই, missing হলে fail-fast — `default.png`-এ
   চুপচাপ নয়।
6. stable natural key: `slug` + `sku`; `updateOrCreate`; fixed `fake()->seed(20261116)`;
   `random_int()`/`time()` নয়। `migrate:fresh --seed` হলো canonical reset।

**বাস্তবায়ন (২০২৬-১১-১৬) — `DEV-CATALOG-SEED`:** `DevCatalogSeeder` এখন
`public/seed-assets/adora-2026/catalog.json`-কে একমাত্র manifest ধরে ১২টি age
category, ৩০টি প্রোডাক্ট, gallery, একটি default variant (`AD-<source_id>-DEFAULT`),
`variant_stocks` ও `OPENING_BALANCE` লেখে। যাচাই: `tools/verify_dev_catalog.php`।
বিস্তারিত: `docs/DEV-CATALOG-SEED-2026-11-16.md`।

**অর্ডার সিডের নিয়ম:**

- `OrderPlacementService::place()` দিয়ে base order; তারপর status transition domain path
  দিয়ে — যাতে `order_details.variant_id`, payment, shipping, audit log সব আসল পথে তৈরি হয়।
- বিতরণ: ১২ delivered, ১০ pending, ৪ shipped, ২ cancelled, ২ returned — মোট ৩০টি।
- প্রতিটি delivered অর্ডারে `delivered_at`; cancelled-এ `cancelled_at`।
- `DevReportFixtureSeeder`: ২টি `order_returns` + `order_return_items`, ৩টি
  `coupon_usages`, pathao+steadfast `courier_shipments` + `courier_events`, একাধিক
  payment method — যাতে চারটি রিপোর্টই বাস্তব ডেটায় চলে।
- সব seed idempotent; দ্বিতীয়বার চালালে duplicate নয়। Dev reset-এ `migrate:fresh`-ই
  প্রথম পছন্দ, তাই production-style backfill code লিখবেন না।

### 0.3 লিগেসি সরানোর সিদ্ধান্ত — `DROP-LEGACY-STOCK`

`products.stock` শুধু একটি কলাম নয় — এটি সাতটি read/write path-এ ছড়ানো:

| জায়গা | আজ কী করে | করণীয় |
|---|---|---|
| `ProductController` create/update/price_update/quick_update | `products.stock` লেখে | stock field বাদ; variant stock service |
| `OrderController` POS create/edit/approve | `$product->stock` fallback + `GREATEST(0, stock-qty)` | variant ledger only |
| `InventoryController::adjustmentStore` | legacy stock sync | movement only |
| `Product::displayStockQuantity()` | product fallback | variant sum only; variant না থাকলে 0/fail |
| `ProductStockSync` + `InitVariantStocksCommand` + `BackfillDefaultVariantsCommand` | legacy sync/backfill | retire |
| `reports/stock.blade.php`, `product/_row`, `price_edit`, campaign views | `products.stock` পড়ে | `variant_stocks` aggregate |
| `frontend` product card/details | `$product->stock` | variant availability |

**ক্রম:** seed layer তৈরি → সব read path variant-এ সরান → সব write path ledger-এ সরান →
`ProductStockSync` retire → `products.stock` ড্রপ। Dev site, তাই backfill নয়;
`migrate:fresh --seed`-এ নতুন schema + নতুন ডেটা একসাথে আসবে।

### 0.4 Portable Adora 2026 seed asset bundle — `SEED-ASSET-BUNDLE`

> **কেন:** seed-এর প্রোডাক্ট নাম ও ডেমো ছবি external drive
> (`/media/sahon/301E3D251E3CE60E/Adora 2026/`) থেকে একবার import হবে, কিন্তু
> প্রজেক্ট যেখানেই বিক্রি/ডিপ্লয় হোক — `migrate:fresh --seed`-এ ছবি সহ সাইট
> উঠতে হবে। তাই bundle repo-তেই committed, external drive শুধু provenance।

| উপাদান | পাথ | কাজ |
|---|---|---|
| Import tool | `tools/import_adora_2026_assets.py` | SQL dump parse + ১২ ক্যাটাগরি থেকে round-robin ৩০ প্রোডাক্ট + ছবি resize (max 1400px, JPEG q85) |
| Bundle manifest | `public/seed-assets/adora-2026/catalog.json` | ৩০ প্রোডাক্টের name, slug, category, price, product_code, image list |
| Bundle images | `public/seed-assets/adora-2026/images/<slug>/` | প্রতি প্রোডাক্টের demo ছবি (৩০টি ফাইল, ~৫.৩ MB) |
| Publish service | `app/Support/SeedAssets.php` | bundle → `public/uploads/product/seed/<slug>/`; SHA-256 মিললে skip, missing source-এ fail-fast |
| Seeder | `database/seeders/SeedAssetSeeder.php` | `DatabaseSeeder`-এ wired; `migrate:fresh --seed`-এ ছবি কপি করে |
| Usage doc | `docs/SEED-ASSETS-ADORA-2026.md` | re-import, verify, portability নোট (`ADORA-2026`) |

**নিয়ম:**

1. নির্বাচন deterministic — ১২টি source age category-তে round-robin, lowest product id
   first; প্রতিটি প্রোডাক্টে সর্বোচ্চ ৩টি ছবি।
2. `catalog.json`-ই একমাত্র manifest; `DevCatalogSeeder` পরে একই ফাইল থেকে নাম/ছবি
   পড়বে, আলাদা hardcoded list রাখবে না।
3. Runtime path `public/uploads/product/seed/<slug>/<file>`; DB-তে সেই relative path-ই
   লেখা হবে — bundle path কখনো DB-তে যাবে না।
4. Copy idempotent (SHA-256); দ্বিতীয় `db:seed`-এ 0 copied, সব unchanged।
5. Bundle delete করা যাবে না — এটাই demo ছবির committed source of truth।

যাচাই:

```bash
python3 tools/import_adora_2026_assets.py
php artisan db:seed --class=SeedAssetSeeder
php artisan db:seed --class=SeedAssetSeeder   # 0 copied, 30 unchanged
```

---

## 0. সারসংক্ষেপ — এক নজরে

> **এটি v3 — dev-first সংস্করণ (`SEED-FIRST-V3`)।** v2-এর সব প্রযুক্তিগত সিদ্ধান্ত অটুট আছে —
> `RBAC-CORRECTED`, `STOCK-SSOT-LEDGER`, `TREND-ORDERED-DELIVERED`, `NO-PRODUCTS-STOCK`,
> `SHIPPING-FREE-TOGGLE`, `DUAL-PROFIT-VIEWS`, `ORDER-NOTES`, `RETURNS-COUPON-DISCOUNT`।
> যা বদলেছে তা হলো ডেটার ভিত্তি: বর্তমান ৩০টি প্রোডাক্ট ও ১০টি অর্ডার প্রোডাকশন ডেটা নয়,
> ডেভেলপমেন্ট ফিক্সচার। তাই এদের গ্যাপ backfill দিয়ে নয়, **একটাই canonical seed layer**
> দিয়ে পূরণ করা হবে — প্রতিটি প্রোডাক্টে variant, `variant_stocks`, ইমেজ; প্রতিটি অর্ডার
> আসল placement path দিয়ে। চিহ্ন: `DEV-CATALOG-SEED` · `SEED-VARIANTS-STOCK-IMAGES` ·
> `DROP-LEGACY-STOCK` · `DEV-RESET-ALLOWED` · `SEED-ASSET-BUNDLE`।

আপনার ৬টি পয়েন্ট বাস্তবায়নযোগ্য — v3-এ ক্রম:
**dev reset + canonical seed → data integrity → RBAC → net profit → chart → stock → reports → automation**।

| # | আপনার পয়েন্ট | আমার সিদ্ধান্ত | মূল কারণ (প্রমাণ) |
|---|---|---|---|
| ১ | টাইম-সিরিজ চার্ট | **গ্রহণ — Ordered vs Delivered দুই সিরিজ** | ApexCharts আগেই লোডেড; `getSalesTrendForDays()` আছে কিন্তু **শুধু delivered**। ডেভ ফিক্সচারেই ৯টি pending অর্ডার চার্টে invisible — দুই সিরিজ ছাড়া কোনো ভবিষ্যৎ ডেটাতেও একই ভুল থাকবে। |
| ২ | নেট প্রফিট | **গ্রহণ — দুই ভিউ (delivery-inclusive ও product-only)** | `orders.amount = subtotal + shipping − discount` (`OrderPlacementService.php:55`); ডিসকাউন্ট আগেই বাদ, আবার বিয়োগ করলে double-count। কুরিয়ার খরচ `raw_payload.data.delivery_fee`-এ লুকানো — কলাম নেই। |
| ৩ | লো স্টক অ্যালার্ট + লেজার | **গ্রহণ — `products.stock` বাদ** | ডেভ DB-তে variant-হীন/stock-হীন প্রোডাক্ট ছিল, তাই seed-এ প্রতিটি variant-এ `variant_stocks` row + `OPENING_BALANCE` বাধ্যতামূলক। SSOT = `variant_stocks` + `inventory_movements`; legacy কলাম ড্রপ। |
| ৪ | ৪টি বিশেষায়িত রিপোর্ট | **গ্রহণ — seed fixture সহ** | লাইভ ডেটায় `coupon_usages`=০, `order_returns`=০, `courier_events`=০ ছিল; `DevReportFixtureSeeder` এগুলো deterministic ভাবে বানাবে, তাই চারটি রিপোর্টই আসল ডেটায় যাচাই হবে। |
| ৫ | RBAC | **গ্রহণ — তবে scope বড়** | ৭২ permission, Admin=৭২, Manager=০, Support=০; `permission:` middleware মাত্র ১১টি (সব campaign); `@can`=০; order/coupon/payment/courier/report permission নেই। |
| ৬ | এক্সপোর্ট আইকন + শিডিউলড রিপোর্ট | **গ্রহণ** | আইকন `ri-file-excel-line` কিন্তু ফাইল আসলে `.csv`; `QUEUE_CONNECTION=sync`, `jobs` টেবিল নেই, mailhog বন্ধ। |

**এক বাক্যে:** আগে `migrate:fresh --seed` দিয়ে canonical dev baseline (variant + stock + image + order + রিপোর্ট ফিক্সচার),
তারপর ledger write path ও `delivered_at` integrity → RBAC → দুই লাভের ভিউ → চার্ট → স্টক রিপোর্ট → চারটি রিপোর্ট → অটোমেশন।
ফিক্সচার ছাড়া চার্ট বানালে সংখ্যা সুন্দর দেখাবে, কিন্তু ভুল হবে।

---

## 1. যাচাইকৃত বর্তমান অবস্থা

### 1.1 রিপোর্ট — আজ মাত্র ২টি, দুটোই স্ট্যাটিক টেবিল

| বিষয় | প্রমাণ |
|---|---|
| রিপোর্ট রুট মাত্র ২টি | `routes/web.php:1493` (`admin.stock_report`), `routes/web.php:1494` (`admin.order_report`) |
| দুটোই `OrderController`-এ | `OrderController.php:1549` (`stock_report`), `OrderController.php:1568` (`order_report`) |
| ভিউ | `resources/views/backEnd/reports/stock.blade.php`, `resources/views/backEnd/reports/order.blade.php` |
| `ReportsController` খালি খোলস | `app/Http/Controllers/Admin/ReportsController.php` — ১৬৪ বাইট, কোনো মেথড নেই |
| চার্ট কোথাও নেই | `resources/views/backEnd/reports/*.blade.php`-এ কোনো chart element নেই |

**সুযোগ:** `ReportsController` খালি খোলা আছে — এটাই স্বাভাবিক জায়গা নতুন রিপোর্ট মডিউলের।

### 1.2 ভিজ্যুয়ালাইজেশন — লাইব্রেরি প্রস্তুত, শুধু ব্যবহার হয়নি

| বিষয় | প্রমাণ |
|---|---|
| ApexCharts ভেন্ডার করা | `public/admin/assets/vendor/libs/apex-charts/apexcharts.js` |
| **গ্লোবালি লোড হয়** | `resources/views/backEnd/layouts/partials/scripts.blade.php:6` — সব অ্যাডমিন পেজে আগে থেকেই লোডেড |
| Chart.js ও ভেন্ডার করা | `public/admin/assets/vendor/libs/chartjs/chartjs.js` — কিন্তু **কোনো অ্যাডমিন ভিউতে ব্যবহার হয় না** |
| ইতিমধ্যে ApexCharts-এর ইউজ | `resources/views/backEnd/social-events/dashboard.blade.php:188` → `public/admin/assets/js/admin-page-social-events.js` |
| ইতিমধ্যে আছে টাইম-সিরিজ লজিক | `AnalyticsDashboardController::trendData()` (`AnalyticsDashboardController.php:800`) — ঘণ্টা/দিন বাকেটিং, ফাঁকা পয়েন্ট ফিল-আপ, ৪০০ পয়েন্ট ক্যাপ |
| ইতিমধ্যে আছে রেঞ্জ প্রিসেট | `AnalyticsDashboardController::resolveRange()` (`:98`) — `today / yesterday / last30 / last90 / custom` |

> **সিদ্ধান্ত:** নতুন চার্ট লাইব্রেরি যোগ করবেন না। `AnalyticsDashboardController`-এর ট্রেন্ড প্যাটার্ন
> (bucket + gap-fill + 400-ক্যাপ) রিপোর্টে সরাসরি পুনঃব্যবহার করুন। নতুন `npm install` বা CDN
> লিংক যোগ করলে সেই ফাইল আর পরের ডিপ্লয়ে ভেঙে যাবে।

### 1.3 ডেটাবেজ — আর্থিক মডেল ও স্টক মডেল

**অর্ডারের টাকার সূত্র (ভিত্তি: `app/Services/OrderPlacementService.php:47-61`):**

```php
$subtotal = Σ(line.price × line.qty);
$amount   = max(0, $subtotal + $shippingFee − $discount);

$order->amount          = $amount;   // টাকা হিসাবে মোট
$order->discount        = $discount; // অর্ডার-লেভেল ডিসকাউন্ট (কুপন ইত্যাদি)
$order->shipping_charge = $shippingFee;
```

**লাইভ যাচাই (DB → সংখ্যা মিলিয়ে দেখা হয়েছে):**

| অর্ডার | লাইন | subtotal | shipping | discount | `orders.amount` | হিসাব |
|---|---|---|---|---|---|---|
| ২৬৩ | ১ × ৩৮০ | 380 | 70 | 0 | **450** | 380+70−0 = 450 ✅ |
| ৪৪৯ | ১০০ × ১১৫০ | 115000 | 120 | 0 | **115120** | 115000+120−0 = 115120 ✅ |
| ৫৪২ | ১ × ২৬০ | 260 | 120 | 0 | **380** | 260+120−0 = 380 ✅ |

→ **গুরুত্বপূর্ণ:** `orders.amount`-এ ডেলিভারি চার্জ **আগে থেকেই যুক্ত**। তাই
"বিক্রয়মূল্য" আর "বিক্রয়মূল্য + ডেলিভারি" — দুটো আলাদা করে দেখাতে হবে।

**প্রাসঙ্গিক কলাম:**

| টেবিল | কলাম | ধরন | নোট |
|---|---|---|---|
| `orders` | `amount`, `discount`, `shipping_charge` | decimal(12,2) | অর্ডার-লেভেল |
| `orders` | `order_status` | varchar(55) | **slug না, ID** — এখন `1` আর `4` আছে; slug map: 1=pending, 4=delivered (`order_statuses`) |
| `order_details` | `purchase_price`, `sale_price`, `regular_price` | decimal(12,2) | লাইন-লেভেল স্ন্যাপশট |
| `order_details` | `product_discount` | decimal(12,2) | checkout path লেখে না; POS-এ লেখে এবং `orders.discount`-এর ভেতরেই থাকে — **আলাদা বিয়োগ নয়** |
| `coupon_usages` | `discount_amount`, `source` (checkout\|pos) | — | `CouponUsage::record()` থেকে আসে; **এখন ০ রো** |
| `order_returns` | `refund_amount`, `refunded`, `reason`, `return_mode` | — | **এখন ০ রো** |
| `payments` | `payment_method`, `payment_status`, `amount` | — | এখন ১৩টি রো, সব `Cash On Delivery` + `pending` |
| `courier_shipments` | `provider`, `current_status`, `raw_payload` (json) | — | ৪টি রো, সব `pathao`, সব `Pending` |
| `courier_events` | `courier_status`, `order_status_slug` | — | **০ রো** → সাকসেস রেট বের করার মূল উৎস খালি |
| `shipping_charges` | `name`, `amount` | int | ৩টি: Standard delivery ১২০, ঢাকার ভিতরে ৭০, ঢাকার বাইরে ১৩০ |

**স্টক মডেল — সিদ্ধান্ত: legacy `products.stock` বাদ, SSOT = `variant_stocks` + `inventory_movements`।**
`STOCK-SSOT-LEDGER` · `NO-PRODUCTS-STOCK`

| জিনিস | টেবিল | নতুন ভূমিকা |
|---|---|---|
| পণ্য-পর্যায়ের ফ্ল্যাট কাউন্টার | `products.stock` | **legacy — সরানো হবে; আর কখনো SSOT বা write target নয়** |
| ভ্যারিয়েন্ট-পর্যায়ের স্টক | `variant_stocks.on_hand / reserved / available / damaged` | বর্তমান স্টকের SSOT |
| মুভমেন্ট লেজার | `inventory_movements` (`type`, `bucket`, `qty_delta`, `ref_type`, `ref_id`, `created_by`, `note`, `created_at`) | append-only সত্য; প্রতিটি স্টক পরিবর্তনের একমাত্র উৎস |

**আজকের বাস্তব অমিল (live `z_evo`):**

- ৩০টি প্রোডাক্টের **২৫টিতে কোনো variant নেই**; ৯টি variant, ৬টি `variant_stocks` row।
- variant `280`, `390`, `498`-এর কোনো stock row নেই → available = 0 পড়ছে।
- Product 1: `products.stock = 155`, কিন্তু variant available-এর যোগফল `২১৫`।
- Order 449: variant 5-এর **১০০ পিস** অর্ডার হয়েছে, তবু `variant_stocks.available = 150` — কাটেনি।
- `inventory_movements`-এ ৭টি row (২টি `OPENING_BALANCE` + ৫টি `ADJUSTMENT`) — **একটিও `SALE` নেই**।

> ⚠️ **লেজারে অর্ডার-সংক্রান্ত মুভমেন্ট নেই।**
> `inventory_reservations`-এ order 449-এর রিজার্ভ row আছে (`expires_at = 2026-11-15 07:43:29`,
> ইতিমধ্যে মেয়াদোত্তীর্ণ), কিন্তু `OrderPlacementService` checkout পথে কোনো
> `InventoryMovement::record()` কল করে না। `InventoryMovement::record()` কল হয় শুধু
> `OrderController` (821, 1056, 1184, 2198, 2428), `ProductController` (416, 570),
> `ReturnsRefundsController` (233), `ProductStockSync` (115, 211, 337)-এ।
> মানে: **লেজার ও বর্তমান স্টক — দুটোই বাস্তব বিক্রির প্রতিফলন নয়।**

**সিদ্ধান্ত (v3, ক্রম অনুযায়ী):**

1. **Seed:** canonical `DevCatalogSeeder` প্রতিটি প্রোডাক্টের জন্য variant + `variant_stocks` row
   + `OPENING_BALANCE` movement তৈরি করবে। পুরোনো `products.stock`-এর মান কপি করার প্রশ্ন নেই —
   dev site, তাই `migrate:fresh --seed`-এ নতুন ডেটাই আসবে (`DEV-RESET-ALLOWED`)।
2. **Write path:** checkout/order approve/cancel/return/adjustment — সব পরিবর্তন কেবল
   `InventoryMovement::record()` দিয়ে। `variant_stocks` লেজারের projection, সরাসরি edit নয়।
3. **Edit-এ স্টক বাড়ানো নিষিদ্ধ:** product/variant edit ফর্মে stock field **read-only**;
   পাশে আলাদা "Adjust stock" অ্যাকশন — যেটা `ADJUSTMENT` movement + কারণ/নোট + user লিখে।
4. **Read path:** storefront, স্টক রিপোর্ট, dashboard — সব `variant_stocks`/ledger থেকে পড়বে।
5. **Column drop:** সব read/write path সরানোর পর `products.stock` ড্রপ করার মাইগ্রেশন।
   Dev site-এ Stock হারানোর ভয় নেই — seed-ই স্টকের উৎস।

### 1.4 RBAC — `RBAC-CORRECTED`

| বিষয় | প্রমাণ |
|---|---|
| প্যাকেজ | `spatie/laravel-permission ^5.10` (`composer.json`) |
| রোল | `Admin`, `Manager`, `Support` (৩টি) |
| পারমিশন | **৭২টি** — ৬৮টি CRUD + ৪টি campaign (`2026_11_10_000002_add_campaign_permissions.php`) |
| রোল→পারমিশন | **Admin = ৭২, Manager = ০, Support = ০** (`role_has_permissions`) |
| ইউজার→পারমিশন | `model_has_permissions` — **ফাঁকা** (০ row) → পুরোটা রোল-ভিত্তিক |
| রুটে প্রয়োগ | `permission:` middleware মাত্র **১১টি** — সবগুলো `campaign.*` (`routes/web.php`) |
| admin view-এ `@can` | **০টি** |
| order / coupon / payment / courier / report permission | **কোনোটি নেই** |

> ⚠️ **আগের দাবি "অন্য ৬৭টি পারমিশন বসানো আছে" ভুল।** বাস্তবে Manager ও Support রোলের
> একটিও permission নেই; শুধু রোলের নাম আছে। ফলে `order-*`, `coupon-*`, `payment-*`,
> `courier-*`, `report-*` — এসব ক্ষমতা কোনো রোলেই সংজ্ঞায়িত নয়, আর admin view-এ একটিও
> `@can` নেই। মানে শুধু রিপোর্ট রুট গার্ড করলে পাশের dashboard revenue কার্ড, order list,
> coupon, return — সব খোলা থাকবে। তাই **P1-এ রিপোর্ট + finance-সংবেদনশীল পেজ একসাথে
> গেট করতে হবে**, শুধু ৮টি রিপোর্ট রুট নয়।

### 1.5 নোটিফিকেশন — সার্ভার পার্ট সেটআপ, ক্লায়েন্ট পার্ট নেই

**সার্ভার পার্ট (কাজ করছে):**

| বিষয় | প্রমাণ |
|---|---|
| ক্লাস | `app/Notifications/GenericDbNotification.php` — `via()` = `['database']` |
| পাঠানো হয় | `Frontend/ShoppingController.php:638`, `Frontend/CustomerController.php:125,522`, `Frontend/FormController.php:61`, `Frontend/ServiceRequestController.php:41`, `Admin/UserSubmissionBulkActionController.php:66,80,94` |
| ডেটা | `notifications` টেবিলে **২০১ রো**, `notifiable_type = App\Models\User` |
| রেন্ডার | `resources/views/backEnd/layouts/partials/navbar.blade.php:57-105` — বেল আইকন, ৫টি লেটেস্ট, আনরিড কাউন্ট |
| টেমপ্লেট | `notification_templates` টেবিল (৫টি রো, `name/title/body/is_active`) |

**ক্লায়েন্ট পার্ট — তিনটি ফাঁক:**

1. **পোলিং নেই।** নোটিফিকেশন সার্ভার-সাইড রেন্ডার — পেজ রিলোড না করলে নতুন নোটিফিকেশন আসবে না।
   কোনো `admin.notifications.poll` রুট নেই (গ্রাহকের জন্য রুট আছে, `routes/web.php:891-893`, কিন্তু সেটা ফ্রন্টএন্ড)।
2. **Echo/Pusher বন্ধ।** `resources/js/bootstrap.js`-এ পুরো Echo + Pusher ব্লকটা কমেন্ট করা।
3. **Broadcast চালু নেই।** `.env`: `BROADCAST_DRIVER=log`, `QUEUE_CONNECTION=sync`,
   আর **Redis (6379) চালু নেই** — `ss -ltn`-এ শুধু ৩৩০৬ (MySQL), ৮০০০ (Laravel dev server), ৮০৮০ শুধু আছে।
   ফলে `laravel-websockets`/`reverb` চালু করতে হলে আলাদা সার্ভার লাগবে।

**মেইল:** `MAIL_MAILER=smtp`, `MAIL_HOST=mailhog`, `MAIL_PORT=1025` — কিন্তু **মেইলহগ চালু নেই**
(১০২৫ পোর্টে কোনো listener নেই)। তাই শিডিউলড ইমেইল রিপোর্ট এখনই পাঠানো যাবে না।

### 1.6 এক্সপোর্ট — আইকন আর ফাইল ফরম্যাট মিলযোগ নয় ✅ (আপনার ধারণা সঠিক)

| বিষয় | প্রমাণ |
|---|---|
| বাটনের আইকন | `ri-file-excel-line` (**এক্সেল**) — `reports/stock.blade.php:97`, `reports/order.blade.php:96` |
| আসল ফাইল | **CSV** — `public/admin/assets/js/admin-report-table.js` → `type: 'text/csv'`, `link.download = … '.csv'` |
| প্রযুক্তি | ক্লায়েন্ট-সাইড, Blob ডাউনলোড, কোনো সার্ভার রিকোয়েস্ট নেই |
| এক্সেল লাইব্রেরি | `composer.json`-এ `phpoffice/phpspreadsheet` **নেই** |

### 1.7 শিডিউলড রিপোর্টিং — শুরুই হয়নি

| বিষয় | প্রমাণ |
|---|---|
| স্কেডিউলার | `app/Console/Kernel.php` → `schedule()` **ফাঁকা** (শুধু কমেন্ট করা `inspire`) |
| কমান্ড | `app/Console/Commands/`-এ ১০টি কমান্ড, কোনোটিই রিপোর্ট সংক্রান্ত নয় |
| কিউ | `QUEUE_CONNECTION=sync` → লাইন আপ সাইল ব্লক করবে |
| মেইল ট্রান্সপোর্ট | mailhog (বন্ধ) |

### 1.8 ডেটার বাস্তবতা — ফিক্সচার, প্রোডাকশন ডেটা নয়

`z_evo` ডেটাবেজের ২০২৬-১১-১৬ snapshot (নিচের সব সংখ্যা **ডেভ ফিক্সচার**, প্রোডাকশন প্রমাণ নয়):

| টেবিল | রো | মন্তব্য |
|---|---|---|
| `orders` | **১০** | সবগুলো ২০২৬-১১-১৩/১৫ তারিখে, স্ট্যাটাস `1`(pending) ও `4`(delivered) |
| `order_details` | ৯ | অর্ডার ৪৬৫-এর লাইন নেই |
| `payments` | ১৩ | সব `Cash On Delivery` + `pending` — কোনো অনলাইন গেটওয়ে নেই |
| `coupons` | ২ | `A12454` (fixed ১০), `JANNAT10` (percent ১০) |
| `coupon_usages` | **০** | → কুপন ইমপ্যাক্ট রিপোর্টে কোনো ডেটা নেই |
| `order_returns` | **০** | → রিটার্ন/ক্যান্সেলেশন রিপোর্টে কোনো ডেটা নেই |
| `courier_events` | **০** | → কুরিয়ার সাকসেস রেটের মূল উৎস খালি |
| `courier_shipments` | ৪ | সব `pathao`, সব `Pending` |
| `inventory_movements` | ৭ | ২টি `OPENING_BALANCE` + ৫টি `ADJUSTMENT` (নোটে "AUDIT PROBE") — কোনো `SALE` নেই |
| `variant_stocks` | ৬ | ২টি ভ্যারিয়েন্টের (`GS-0003-DEFAULT`, `GS-0020-DEFAULT`) **সার-রোই নেই** |
| `notifications` | ২০১ | সব `GenericDbNotification` |
| `audit_logs` | ৪১ | |
| `products` | ৩০ | |
| `users` | ৩ | |

> **উপসংহার (v3):** উপরের সংখ্যাগুলো ফিচারের সীমা নয় — এগুলো পুরোনো dev DB-র অবস্থা, তাই
> backfill বা "sample data" লেবেল দিয়ে সারানোর কিছু নেই। `DEV-RESET-ALLOWED`: canonical baseline
> হলো `php artisan migrate:fresh --seed`, আর ডেটা-কভারেজের দায়িত্ব `DevCatalogSeeder` +
> `DevOrderSeeder` + `DevReportFixtureSeeder`-এর। Seed-এর পর প্রতিটি রিপোর্টে যথেষ্ট row
> থাকবে; কোনো রিপোর্ট শূন্য থাকলে সেটা seed bug, কোডের সীমা নয়।

---

## 2. আপনার পয়েন্টগুলোর উপর আমার মন্তব্য — এক এক করে

### পয়েন্ট ১ — টাইম-সিরিজ চার্ট ✅ Modify — `TREND-ORDERED-DELIVERED`

**ঠিক আছে, কিন্তু "শুধু delivered" ভুল।** ড্যাশবোর্ডে চার্ট আগেই আছে:

- `AdminDashboardService::getSalesTrendForDays()` (`:58`) → ৭ দিনের **delivered-only** revenue
- `admin-page-admin-dashboard.js` → ApexCharts-এ আঁকে
- `AnalyticsDashboardController::trendData()` (`:800`) → bucket + gap-fill + 400-point cap

নতুন লাইব্রেরি/চাকা নয় — এই প্যাটার্নই রিপোর্টে নিয়ে যেতে হবে। যা বদলাবে:

1. **দুই সিরিজ:** "Ordered" (সব অর্ডার, `created_at`) আর "Delivered" (শুধু delivered,
   `delivered_at`)। এখন ৯টি pending (`১২৩,৫৭০৳`) চার্টে নেই — এটাই সবচেয়ে বড় ভুল।
2. **`delivered_at` কলাম:** এখন `orders`-এ নেই; `updated_at` দিয়ে মাপা ভুল (পুরোনো অর্ডার
   এডিট করলে আজকের delivered দেখাবে)। P0-তে nullable `delivered_at`, `cancelled_at` যোগ করে
   seed-এ delivered/cancelled অর্ডারের timestamp ভরতে হবে — পুরোনো dev DB-র backfill দরকার নেই।
3. **ফিল্টার:** sales/order report-এ `created_at`; realized profit/trend-এ `delivered_at`।
   বর্তমান `updated_at` ফিল্টার (`OrderController.php:1621`, `:1559`) বদলাতে হবে।
4. **Period:** day → ২৪ hourly, week → ৭ daily, month → ২৮–৩১ daily;
   `period=day|week|month` query string-এ ধরে রাখুন।
5. **Charts:** Area = ordered vs delivered revenue (দুই সিরিজ); Bar = net profit;
   Donut = status mix। Ordered/Delivered ছাড়া চার্ট ship করবেন না।

### পয়েন্ট ২ — নেট প্রফিট ✅ Modify (দুই ভিউ) — `DUAL-PROFIT-VIEWS`

**ঠিক আছে, কিন্তু একটাই "নেট প্রফিট" নয় — দুই ভিউ লাগবে।** বিস্তারিত সূত্র §3-এ।

- **ভিউ A — Delivery-inclusive (মোট লাভ):** গ্রাহক যা দিল (ডেলিভারি চার্জসহ)
  − কুরিয়ারের আসল খরচ − COGS − refund। আপনার কথায়: "সেল প্রাইস ইনক্লুডিং ডেলিভারি চার্জ
  মাইনাস কুরিয়ারের খরচ"।
- **ভিউ B — Product-only (শিপিং বাদে পণ্যের লাভ):** ডেলিভারি চার্জ বাদ দিয়ে শুধু পণ্যের
  net revenue − COGS − refund। শিপিংয়ের লাভ/লস এতে ঢুকবে না; দুটো সংখ্যা পাশাপাশি দেখালে
  admin বুঝবে লাভের কতটা পণ্য থেকে, কতটা শিপিং থেকে।

**সংশোধন ১ — শিপিং দুই রকম।** `orders.shipping_charge` হলো *customer-কে চার্জ করা* টাকা।
কুরিয়ারকে দেওয়া *আসল* খরচ আলাদা — সেটা এখন শুধু JSON-এ আছে:

```
courier_shipments.raw_payload → {"code":200,"data":{"delivery_fee":60, ...}}   ← Pathao-এর প্রকৃত ডেলিভারি খরচ
```

প্রমাণ: `PathaoOrderSender.php:201` → `'raw_payload' => $response['body']`; লাইভ row-এ
`delivery_fee: 60`, আর customer-কে চার্জ ১২০। মানে প্রতি পার্সেলে **৬০ টাকা শিপিং মার্জিন**।

`JSON_EXTRACT` দিয়ে বের করা যাবে, কিন্তু **এটা কখনোই queryable SSOT হবে না** — প্রতিটি
স্ট্যাটাস সিঙ্ক `raw_payload` ওভাররাইট করে। তাই **`courier_shipments`-এ `shipping_cost
decimal(12,2)` কলাম যোগ করা বাধ্যতামূলক**, পার্সেল পাঠানোর সময় `delivery_fee` থেকে সেট করতে হবে।

**সংশোধন ২ — ফ্রি শিপিংয়ের ভ্যালু।** যখন শিপিং ফ্রি, `shipping_charge = 0` কিন্তু কুরিয়ার
খরচ তবু থাকে। তখন `shipping_list_price` (স্বাভাবিক শিপিং রেট) snapshot করে রাখতে হবে —
এটাই "ফ্রি শিপিংয়ে গ্রাহককে দেওয়া সুবিধার মূল্য"। ভিউ A-তে কুরিয়ার খরচ আগেই বিয়োগ হয়,
তাই `shipping_list_price` **আবার বিয়োগ করবেন না** — এটা discount/marketing value, দ্বিতীয় খরচ নয়।

**সংশোধন ৩ — ডিসকাউন্ট double-count নয়।** `orders.amount`-এ ডিসকাউন্ট **আগেই বাদ**;
POS-এ `orders.discount`-এর ভেতরেই `order_details.product_discount` আছে। তাই
`orders.discount` আর `Σ product_discount` — দুটোই বিয়োগ করলে লাভ কম দেখাবে। শুধু
`orders.amount`-কে authoritative ধরুন।

**সংশোধন ৪ — ফেরত ও ক্যান্সেল আলাদা।**
- `order_returns.refunded = 1` হলে `refund_amount` বিয়োগ (refund হয়ে গেছে)।
- `refund_required = 1` কিন্তু `refunded = 0` হলে এটা **অর্জিত লাভ নয়** — "ঝুঁকিতে থাকা লাভ"।
  আলাদা লাইনে: `Net Profit (realised)` আর `Net Profit (after pending refunds)`।
  অন্যথায় admin ভুল বুঝবে যে টাকা আয় হয়েছে।

### পয়েন্ট ৩ — স্টক অ্যালার্ট ও লেজার ✅ Modify (আগে SSOT ও write path)

**ঠিক আছে, তবে `products.stock` আর থাকবে না।** বিস্তারিত §4-এ। যা যোগ করবেন:

- **SSOT:** `variant_stocks` + `inventory_movements`; `products.stock` legacy — read/write path
  সরিয়ে P4-তে ড্রপ করতে হবে। Seed থেকেই প্রতিটি variant-এর stock আসে, তাই backfill নেই।
- **থ্রেশহোল্ড:** `variant_stocks.low_stock_threshold int DEFAULT 10` (প্রতি variant)।
  variant-হীন প্রোডাক্ট থাকবে না, তাই fallback-এর প্রশ্ন নেই। আগের "প্রথমে শুধু
  `products.low_stock_threshold`" সিদ্ধান্ত **বাতিল** — কারণ কলামটাই যাচ্ছে।
- **লেজার:** স্টক রিপোর্টে Movements সেকশন — `type / bucket / qty_delta / ref_type /
  ref_id / note / user`; `ref_type = 'order'` হলে অর্ডার লিংক।
- **আগে write path:** checkout/approve/cancel/return-এ movement লিখতে হবে; নইলে লেজার
  দেখিয়ে "এই প্রোডাক্ট কতবার বিক্রি হয়েছে" জিজ্ঞাসার উত্তর আসবে "শূন্য"।
- **অ্যালার্ট:** adjustment/order approve/return restock-এ trigger; একই variant+threshold-এ
  ২৪ ঘণ্টায় একবার (idempotent); প্রাপক `report-stock-view` permission-ওয়ালারা — `User::all()` নয়।

### পয়েন্ট ৪ — ৪টি বিশেষায়িত অ্যানালিটিক্স রিপোর্ট ✅ Modify

**ঠিক আছে, কিন্তু ডেটা প্রস্তুত নয়।** সব চারটির জন্য সঠিক সোর্স ম্যাপিং:

| রিপোর্ট | সোর্স টেবিল | স্ট্যাটাস | যা লাগবে |
|---|---|---|---|
| **পেমেন্ট মেথড অ্যানালিটিক্স** | `payments` | ✅ কাজ করবে | `payment_method` অনুযায়ী গ্রুপ, success/failed, avg order value। সতর্কতা: এখন ১৩টি রোই সব `Cash On Delivery` + `pending` → একটাই বার দেখাবে। |
| **কুরিয়ার পারফরম্যান্স** | `courier_shipments` + `courier_events` | ⚠️ আংশিক | `courier_shipments` (৪টি, সব pathao/Pending) থেকে provider-ভিত্তিক সারাংশ বানানো যাবে; **কিন্তু সাকসেস রেটের জন্য `courier_events` দরকার, সেটা ০ রো**। ফলে "success rate" দেখাতে হলে fallback দরকার: `courier_shipments.current_status` (Pending) থেকে। |
| **রিটার্ন ও ক্যান্সেলেশন** | `order_returns` + `orders.order_status` | ⚠️ আংশিক | `order_returns` ০ রো → কারণ, রেট, হার সব শূন্য। `orders.order_status` থেকে ক্যান্সেলেড সংখ্যা বের হবে (এখন ০)। `reason` ফিল্টার কাজ করবে, কিন্তু ডেটা নেই। |
| **কুপন ইমপ্যাক্ট** | `coupon_usages` + `coupons` | ❌ খালি | `coupon_usages` ০ রো → কোনো কুপন ব্যবহারই হয়নি। **`CouponUsage::record()` ঠিক আছে কিন্তু ফ্রন্টএন্ড চেকআউটে `CustomerController.php:497` আর POS-এ `OrderController.php:1718` থেকে কল হয় — সেগুলো চালু আছে কিনা যাচাই দরকার।** |

**প্রতিটি রিপোর্টের জন্য একটি সাধারণ "খালি অবস্থা" UI দরকার** — "এই রিপোর্টে এখনো কোনো ডেটা নেই"
বলে স্পষ্ট বলা, খালি চার্ট বা ভাঙা গ্রাফ দেখানোর বদলে। এটা UX-এর সবচেয়ে জরুরি অংশ।

### পয়েন্ট ৫ — RBAC ✅ Accept (সবচেয়ে জরুরি, সবচেয়ে সস্তা)

**ঠিক আছে, এবং এটা সবার আগে করা উচিত** — কারণ এটাই একমাত্র পয়েন্ট যেটা বাস্তবে
"সবাই সব আর্থিক ডেটা দেখতে পারছে" সমস্যাটা এখনই থামায়।

আমার পরিকল্পনা → §6-এ পুরো ম্যাট্রিক্স।

### পয়েন্ট ৬ — UX ও অটোমেশন ✅ Accept

**৬ক — এক্সপোর্ট আইকন/ফরম্যাট:** আপনার পর্যবেক্ষণ ১০০% সঠিক, প্রমাণিত (§1.6)।
দুটো অপশন:
- **সস্তা (১ ঘণ্টা):** আইকন বদলে `ri-file-text-line` + লেবেল "Export CSV"।
- **আদর্শ (১ দিন):** আসল **XLSX** এক্সপোর্ট — সার্ভার-সাইড, `phpoffice/phpspreadsheet`।
  কারণ: বাংলা + টাকার চিহ্ন + একাধিক শিট (Summary / Detail) — CSV পারে না।
  আমার সুপারিশ: **দুটোই দিন — CSV বাটন + XLSX বাটন** (সাইডবাই ড্রপডাউন)।

**৬খ — শিডিউলড/ইমেইল রিপোর্ট:** ঠিক আছে। কিন্তু দুটো বাধা আগে সমাধান করতে হবে:
- **মেইলহগ চালু নেই** → আসল SMTP ক্রেডেনশিয়াল দরকার (বা লোকালে mailhog চালু করা)।
- **`QUEUE_CONNECTION=sync`** → রিপোর্ট জেনারেট হওয়ামাত্র ইমেইল পাঠাবে, শিডিউলার ব্লক হবে।
  সমাধান: `database` queue কানেকশন ব্যবহার করে `queue:work` সেট করা (Redis লাগবে না)।

---

## 3. NET_PROFIT_FORMULA — সঠিক সূত্র

### 3.1 বর্তমান অবস্থা

`OrderController::order_report()` (`:1623-1625`) আজ যা গণনা করে:

```php
$total_purchase = $orders->sum(DB::raw('purchase_price * qty'));  // COGS
$total_sales    = $orders->sum(DB::raw('sale_price * qty'));     // Revenue (gross)
```

→ এটা **গ্রস মার্জিন**, নেট প্রফিট নয়। ডিসকাউন্ট, শিপিং, রিফান্ড — কোনোটাই নেই।

### 3.2 প্রস্তাবিত সূত্র — দুই ভিউ + এক authoritative revenue

`orders.amount`-ই authoritative: `subtotal + shipping_charge − discount`। `discount` আগেই বাদ,
তাই `orders.discount` বা `order_details.product_discount` আবার বিয়োগ করলে **double-count** হবে।

```
CollectedTotal       = Σ orders.amount                    // discount already deducted
ProductRevenueNet    = Σ (orders.amount − orders.shipping_charge)
ShippingRevenue      = Σ orders.shipping_charge           // free হলে 0
ShippingCost         = Σ courier_shipments.shipping_cost  // actual courier cost
FreeShippingValue    = Σ orders.shipping_list_price WHERE shipping_is_free = 1
COGS                 = Σ order_details.purchase_price × qty
RefundRealised       = Σ order_returns.refund_amount WHERE refunded = 1
RefundPending        = Σ order_returns.refund_amount WHERE refund_required = 1 AND refunded = 0

View A — Delivery-inclusive profit (মোট লাভ)
  = CollectedTotal − ShippingCost − COGS − RefundRealised

View B — Product-only profit (শিপিং বাদে পণ্যের লাভ)
  = ProductRevenueNet − COGS − RefundRealised

Shipping margin       = ShippingRevenue − ShippingCost
After pending refunds = View A − RefundPending
Margin A %            = View A ÷ CollectedTotal × 100
Margin B %            = View B ÷ ProductRevenueNet × 100
```

**ফ্রি শিপিং:** `shipping_is_free = 1` → `shipping_charge = 0`, কিন্তু `shipping_list_price`
(স্বাভাবিক রেট) snapshot থাকে। `FreeShippingValue` শুধু "কত সুবিধা দেওয়া হলো" হিসেবে দেখাবে;
View A-তে কুরিয়ার খরচ আগেই বিয়োগ হয়েছে, তাই এই ভ্যালু **আবার বিয়োগ করবেন না**।
"ফ্রি শিপিং না দিলে লাভ কত হতো" দেখাতে চাইলে `View A + FreeShippingValue` — এটা hypothetical,
মূল লাভ নয়; UI-তে আলাদা লেবেল দিন।

**ডিসকাউন্ট:** `orders.discount` authoritative। POS-এ `product_discount` এর ভেতরেই আছে;
web checkout-এ coupon discount-এর attribution `coupon_usages`-এ। রিপোর্টে শুধু breakdown,
আর কোনো subtraction নয় — নইলে লাভ দুইবার কমবে।

**রিফান্ড:** `order_returns` order-level header থাকবে; product-level attribution-এর জন্য
`order_return_items` (order_detail_id, qty, refund_amount) — §4B.3-এ বিস্তারিত।

### 3.3 লাইভ ডেটা দিয়ে যাচাই (অর্ডার ৪৪৯)

```
CollectedTotal    = 115,120
ProductRevenueNet = 115,000
ShippingRevenue   =     120
ShippingCost      =      60   (raw_payload.data.delivery_fee)
COGS              = 105,000
Refund            =       0   (order_returns ০ row)
─────────────────────────────────────────────────
View A (delivery-inclusive) = 115,120 − 60 − 105,000 = 10,060
View B (product-only)       = 115,000 − 105,000      = 10,000
Shipping margin             = 120 − 60               =     60

Margin A = 10,060 ÷ 115,120 = ৮.৭৪%
Margin B = 10,000 ÷ 115,000 = ৮.৭০%
```

**লক্ষ্য করুন:** আগের "গ্রস মার্জিন ১০,০০০" আসলে **View B**; delivery-inclusive আসল মোট লাভ
**১০,০৬০**। দুটোই পাশাপাশি দেখাতে হবে — একটাকে অন্যের জায়গায় বসাবেন না।

### 3.4 বাস্তবায়ন

- নতুন সার্ভিস: `app/Services/Report/NetProfitCalculator.php` — এক জায়গায় দুই ভিউর হিসাব,
  সব রিপোর্ট এখান থেকে নেবে (একটি সূত্র, একটি জায়গা)।
- `NetProfitCalculator::summary($query, $from, $to)` → `collected_total`, `product_revenue`,
  `shipping_revenue`, `shipping_cost`, `free_shipping_value`, `cogs`, `refund_realised`,
  `refund_pending`, `profit_delivery_inclusive`, `profit_product_only`, `margin_a`, `margin_b`।
- `NetProfitCalculator::forOrder($order)` same breakdown — order-wise drill-down-এর জন্য।
- ভিউতে কোনো হিসাব নয় — শুধু কন্ট্রোলার থেকে আসা সংখ্যা দেখাবে
  (প্রজেক্টের নিয়ম, দেখে `analytics/index.blade.php:6-13`)।

### 3.5 যা যোগ করতে হবে (DB)

| মাইগ্রেশন | কোথায় | কী |
|---|---|---|
| `add_shipping_cost_to_courier_shipments` | `courier_shipments` | `shipping_cost decimal(12,2) NULL` |
| `add_shipping_fields_to_orders` | `orders` | `shipping_is_free tinyint(1) DEFAULT 0`, `shipping_list_price decimal(12,2) NULL` |
| `add_delivered_at_to_orders` | `orders` | `delivered_at`, `cancelled_at` timestamp NULL |
| `create_order_return_items` | নতুন টেবিল | `order_return_id`, `order_detail_id`, `qty`, `refund_amount` |
| `add_note_to_audit_logs` | `audit_logs` | `note text NULL` |
| `add_stock_threshold_to_variant_stocks` | `variant_stocks` | `low_stock_threshold int DEFAULT 10` |

`shipping_cost` কে পূরণ করবে: `PathaoOrderSender.php:201`-এর `updateOrCreate`-এ,
`data_get($response['body'], 'data.delivery_fee')` → `shipping_cost` কলামে লেখা।
Dev baseline-এ `DevReportFixtureSeeder` deterministic courier cost বসাবে; পুরোনো
`raw_payload` backfill লাগবে না।

---

## 4. STOCK_ALERT_DESIGN — স্টক অ্যালার্ট ও লেজার

### 4.1 সমস্যা: পাশাপাশি স্টক-সত্য, আর কোনো একটাই লেখে না

| প্রশ্ন | `products.stock` | `variant_stocks` | `inventory_movements` |
|---|---|---|---|
| স্টোরফ্রন্ট পড়ে? | না | **হ্যাঁ** (`ShoppingController::enforceVariantStockOrFail()`) | না |
| অ্যাডমিন স্টক রিপোর্ট পড়ে? | না (শুধু legacy mirror) | **হ্যাঁ** (`OrderController::stock_report()`, 2026-11-16 থেকে on_hand + variant price) | না |
| অ্যাডমিন ইনভেন্টরি পড়ে? | না | **হ্যাঁ** (`InventoryController::index()`) | **হ্যাঁ** (`InventoryController::ledger()`) |
| অর্ডারে কমে? | পুরোনো পথে | **checkout-এ RESERVE** (hold), ship-এ SALE | checkout-এ `InventoryLedger::reserveOrder()` |
| কে লেখে? | legacy CRUD + `ProductStockSync` | movement/sync | `InventoryMovement::record()` (অর্ডার পাথে নয়) |

**এই অবস্থায় থ্রেশহোল্ড বসালে কোন সংখ্যার সাপেক্ষে অ্যালার্ট হবে সেটাই অস্পষ্ট।**
আর `products.stock` থাকলে কেউ edit করে স্টক বাড়িয়ে দিতে পারে — যা লেজারের সাথে মিথ্যা বলবে।

### 4.2 সিদ্ধান্ত — এক SSOT, এক write path — `STOCK-SSOT-LEDGER` / `NO-PRODUCTS-STOCK`

**SSOT = `variant_stocks` (বর্তমান projection) + `inventory_movements` (append-only সত্য)।**
`products.stock` একটি **legacy column — সরিয়ে ফেলা হবে**, ডেরিভড ক্যাশ হিসেবেও রাখা হবে না।

1. **Seed:** `DevCatalogSeeder`-এ প্রতিটি প্রোডাক্টের variant + `variant_stocks` row +
   `OPENING_BALANCE` movement; canonical reset `migrate:fresh --seed`।
2. **Write path:** checkout-এ `RESERVE`; ship/deliver-এ `SALE` (একবারই); cancel-এ `RELEASE`;
   return restock-এ `RETURN`; manual-এ `ADJUSTMENT`। প্রতিটি row-এ `ref_type`, `ref_id`,
   `created_by`, `note` বাধ্যতামূলক (adjustment-এ note ছাড়া save নয়)।
3. **Projection:** `variant_stocks` কেবল movement Apply করে আপডেট হবে; model/controller থেকে
   সরাসরি `on_hand` বাড়ানো/কমানো নিষিদ্ধ। `ProductStockSync` retire হবে।
4. **Edit-এ স্টক নয়:** product/variant edit ফর্মে stock field read-only;
   পাশে "Adjust stock" অ্যাকশন — যেটা `ADJUSTMENT` movement + কারণ + user লিখে।
   DB-level guard: `variant_stocks`-এ শুধু movement service লিখতে পারবে (observer/service check)।
5. **Drop:** সব read/write path variant-এ সরানোর পর `products.stock` ড্রপ করার মাইগ্রেশন।
   Backfill/reconcile বাধা নয় — dev reset-এই নতুন schema + seeded stock একসাথে আসে।

**থ্রেশহোল্ড কোথায়?** → `variant_stocks.low_stock_threshold int DEFAULT 10` (প্রতি variant)।
variant-হীন প্রোডাক্ট আর থাকবে না; প্রোডাক্ট-লেভেলে আলাদা থ্রেশহোল্ড
রাখবেন না — দুটো থাকলে কোনটা থেকে অ্যালার্ট আসছে বোঝা যাবে না।

### 4.3 লেজার রিপোর্টে

`inventory_movements` টেবিলের কলামগুলোই যথেষ্ট:
`type` (OPENING_BALANCE / ADJUSTMENT / SALE / RESERVE / RELEASE / RESTOCK / RETURN),
`bucket` (sellable / damaged), `qty_delta`, `ref_type`, `ref_id`, `created_by`, `note`, `created_at`.

স্টক রিপোর্টে যোগ করবেন:
- ফিল্টার: `movement_type`, `variant_id`, তারিখ পরিসর
- কলাম: তারিখ, টাইপ, বাকেট, পরিবর্তন (+/−), রেফারেন্স (অর্ডার/ভ্যারিয়েন্ট), নোট, কে করেছে
- `ref_type = 'order'` হলে অর্ডার নম্বর লিংক

### 4.4 অ্যালার্ট ট্রিগার

| ইভেন্ট | কোথায় | শর্ত |
|---|---|---|
| ম্যানুয়াল অ্যাডজাস্টমেন্ট | `InventoryController::adjustmentStore()` (`:198`) | নতুন available < threshold |
| অর্ডার অ্যাপ্রুভ (স্টক কমে) | `OrderController` স্ট্যাটাস-চেঞ্জ | নতুন available < threshold |
| রিটার্ন রিস্টক | `ReturnsRefundsController.php:233` | নতুন available < threshold |

অ্যালার্ট বারবার যাওয়া উচিত না — `notifications` টেবিলে একই variant+threshold ইভেন্টের
আগের অনুরূপ রো আছে কি না দেখে গেট করবেন (idempotency)।

### 4.5 যাচাইকৃত বাস্তবায়ন — 2026-11-16

নিচের আচরণ ইতিমধ্যে কোডে আছে এবং `tests/Feature/ShopWorkflowAuditTest.php`
(৯টি টেস্ট, ১০৯টি assertion) ও `tests/Feature/StockReportReadPathTest.php`
(২টি টেস্ট, ১৯টি assertion) দিয়ে প্রমাণিত। রিপোর্ট লেয়ারের বাকি কাজ P4-তে আলাদা।

| আচরণ | প্রমাণ |
|---|---|
| ম্যানুয়াল `+`/`−` adjustment projection, ledger ও legacy mirror তিনটেই আপডেট করে; শূন্যের নিচে নামলে validation error, silent clamp নয় | `InventoryController.php:198` · `test_manual_adjustment_*` |
| `DAMAGE` ইউনিট sellable থেকে damaged-এ যায়, `on_hand` বদলায় না, `available = on_hand − damaged − reserved` | `InventoryController.php:239` · `test_damage_*` |
| Inventory KPI পুরো filtered dataset-এর উপর (পেজের ৫০ row নয়); `available` ATP, active reservation বাদ দিয়ে | `InventoryController.php:44-55` · `test_inventory_kpis_*` |
| Product-level availability সব active variant যোগ করে + reservation বাদ দেয়; home rail batch একবারই query করে | `Product.php:180` · `FrontendController::attachHomeStockAvailability()` (`:815`) · `test_any_variant_*` |
| Out-of-stock card-এ badge + disabled action; variant product-এ "Choose Options" | `featured-products.blade.php` ইত্যাদি · `test_out_of_stock_*` |
| Checkout `RESERVE`, ship `SALE` একবার, delivered দ্বিতীয়বার কমায় না, cancel `RELEASE` | `OrderLifecycle.php:80` · `InventoryLedger.php:53/140` · `test_visitor_cart_checkout_*`, `test_cancelling_*` |
| কুরিয়ার credentials ছাড়া gracefully fail, কোনো consignment row তৈরি হয় না | `CourierOrderSender::preview()` (`:110`) · `test_courier_*` |
| Admin flash `<body>`-তে, inventory toolbar responsive | `backEnd/layouts/app.blade.php:51` · `admin-responsive.css:91` · `test_admin_flash_*` |
| Stock Report variant projection থেকে পড়ে: প্রতি row-তে active variant-এর `on_hand` যোগ, variant `price_override`/\`purchase_price` প্রয়োগ, legacy `products.stock` উপেক্ষা; footer totals পুরো filter-এর উপর | `OrderController::stock_report()` (`:1617`) · `reports/stock.blade.php` · `test_stock_report_*`, `test_filtered_totals_*` |
| Legacy `products.stock` mirror reconciliation: active variant-এর `on_hand` যোগফলে পুরো কলাম sync, দ্বিতীয় run no-op; live ৩০/৩০ mismatch → ০ | `app/Console/Commands/ReconcileLegacyStock.php` · `tests/Feature/LegacyStockReconcileTest.php` |
| Stock Report filter correctness: same-day `start_date`/`end_date` দিনের শেষ পর্যন্ত ধরে; pagination link-এ keyword/category/date থাকে | `OrderController::stock_report()` (`:1625-1626`, `:1660`) · `tests/Feature/StockReportFilterTest.php` |

**চালানোর কমান্ড:** `php artisan test --filter=ShopWorkflowAuditTest` → `9 passed (109 assertions)`;
`php artisan test --filter=StockReportReadPathTest` → `2 passed (19 assertions)`;
`php artisan test --filter=LegacyStockReconcileTest` → `1 passed (8 assertions)`;
`php artisan test --filter=StockReportFilterTest` → `2 passed (12 assertions)`।

**Reconcile (2026-11-16):** `php artisan inventory:reconcile-legacy` চালিয়ে live
`SUM(products.stock)` 750 → 2220, mismatch ৩০/৩০ → ০।

**এখনও বাকি (রিপোর্ট লেয়ার):** `products.stock` ড্রপ, `ProductStockSync` retire এবং
low-stock alert trigger এখনো P4-এর বাকি কাজ। Stock Report-এর read path ও legacy mirror
reconcile সম্পন্ন, কিন্তু ledger/section ও threshold ভিত্তিক অ্যালার্ট এখনো যোগ হয়নি।

---

## 4B. নতুন সিদ্ধান্ত (v2) — নোট, ফ্রি শিপিং, রিটার্ন/কুপন/ডিসকাউন্ট

### 4B.1 ORDER-NOTES — অডিট লগে নোট + অর্ডার তৈরি/এডিটে নোট — `ORDER-NOTES`

**কী আছে আজ:**

- `audit_logs`-এ `description`, `before`, `after` আছে — কিন্তু আলাদা **`note` কলাম নেই**।
- `orders`-এ `admin_note text NULL` আছে (মাইগ্রেশন `2026_03_03_124223`), আর
  `AuditLogger::orderNoteUpdated()` / action `order.note.updated` আছে।
- কিন্তু POS order create-এ note ফিল্ড নেই; পুরোনো কোড `$order->note` লিখতে গিয়ে
  "Unknown column 'note'"-এ অর্ডার creation ভাঙছিল।

**সিদ্ধান্ত:**

1. **Audit log note:** `audit_logs.note text NULL` যোগ করুন। `AuditLogger::record()`-এ
   optional `note` parameter; টেবিলে note কলাম + ফিল্টার; append-only — পুরোনো row edit নয়,
   নতুন note-row যোগ হবে। সংবেদনশীল action-এ note বাধ্যতামূলক:
   `inventory.adjustment`, `order.note.updated`, `order.refund`, coupon manual override।
2. **Admin order create:** shipping/payment ব্লকের পাশে `admin_note` textarea (max ২০০০),
   create payload-এ `admin_note` স্পষ্টভাবে set — `note` নয়।
3. **Admin order edit:** একই textarea, বর্তমান `admin_note` prefill; save করলে
   `AuditLogger::orderNoteUpdated($order, $before, $after)` → `audit_logs`-এ before/after + note।
4. **দেখানো:** order detail-এ admin note আলাদা কার্ডে (শেষ আপডেটকারী + সময়);
   customer invoice/frontend-এ **কখনো নয়**। `orders.admin_note` আর customer note (যদি থাকে)
   আলাদা রাখুন — এক ফিল্ডে মিশাবেন না।
5. **Permission:** note দেখা/এডিট করা `order-edit`-এর অধীনে; audit log পেজ Admin-only
   (`audit-log-view` নতুন permission, Admin শুধু)।

### 4B.2 SHIPPING-FREE-TOGGLE — read-only স্ট্যাটাস ও ফ্রি শিপিং ভ্যালু — `SHIPPING-FREE-TOGGLE`

**আজকের অবস্থা:** `orders.shipping_charge` আছে; `shipping_charges`-এ ৩টি রেট (Standard ১২০,
ঢাকার ভিতরে ৭০, বাইরে ১৩০)। কিন্তু "এই অর্ডারে শিপিং ফ্রি ছিল" — এমন কোনো ফ্ল্যাগ বা
স্বাভাবিক রেটের snapshot নেই। তাই ফ্রি শিপিং দিলে কত টাকা ছাড় দিলেন, সেটা রিপোর্টে থাকে না।

**DB:**

| কলাম | ধরন | অর্থ |
|---|---|---|
| `orders.shipping_is_free` | `tinyint(1) DEFAULT 0` | ১ = এই অর্ডারে শিপিং ফ্রি |
| `orders.shipping_charge` | decimal(12,2) (আছে) | গ্রাহককে **আসলে চার্জ করা** (ফ্রি হলে ০) |
| `orders.shipping_list_price` | `decimal(12,2) NULL` | একই এলাকার **স্বাভাবিক** শিপিং রেট snapshot |
| `orders.shipping_discount` | derived | `shipping_list_price − shipping_charge` (ফ্রি ভ্যালু) |

**Admin shipping box:**

- একটি **read-only "Shipping Free" status toggle** — অর্ডারের বর্তমান অবস্থা দেখায়,
  সরাসরি টগল করা যায় না (ভুল করে টাকা বদলাবে না)।
- টগলের পাশে আলাদা **"Make shipping free" / "Charge shipping"** অ্যাকশন
  (`order-finance-edit` permission, Admin-only), যেটা `shipping_is_free`,
  `shipping_list_price`, `shipping_charge` সেট করে এবং `audit_logs`-এ note সহ row লেখে।
- ফ্রি করার সময় `shipping_list_price` = নির্বাচিত `shipping_charges.amount` snapshot;
  `shipping_charge = 0`।

**Customer order page:**

- `shipping_is_free = 1` → শিপিং-চার্জ ড্রপডাউন **লুকানো**, বদলে read-only
  "Free delivery (regular ৳120)" লাইন; `shipping_charge = 0`।
- `shipping_is_free = 0` → স্বাভাবিক ড্রপডাউন আগের মতোই।

> ⚠️ **আপনার বাক্যের ON/OFF ম্যাপিং:** আপনি লিখেছেন "অফ করলে ইউজার ড্রপডাউনে দেখতে পারবে না,
> অন করলে পারবে"। "Shipping Free" লেবেলের সাথে এটা উল্টো শোনায় — লজিক্যাল ম্যাপিং হলো
> **ON = ফ্রি → ড্রপডাউন লুকানো; OFF = চার্জ আছে → ড্রপডাউন দেখা যায়**। এই ডকুমেন্টে
> নিরাপদ ম্যাপিংটাই সিদ্ধান্ত হিসেবে রাখা হলো; UI-তে টগলের পাশে helper text থাকবে।
> আপনি যদি সত্যিই উল্টোটি চান, কোডে একটাই boolean check উল্টাতে হবে — কিন্তু তখন
> "ফ্রি" অর্ডারেও গ্রাহক শিপিং চার্জ বেছে নিতে পারবে, তাই সেটা সুপারিশ করি না।

**ফ্রি শিপিং ভ্যালু কোথায় দেখাবে:** order detail-এ "Shipping discount ৳120";
profit/report-এ `FreeShippingValue = Σ shipping_list_price WHERE shipping_is_free=1`।
এটা marketing/discount value — View A-তে কুরিয়ার খরচ আগেই বিয়োগ হয়েছে, তাই এই ভ্যালু
আবার বিয়োগ করবেন না (§3.2)।

### 4B.3 RETURNS-COUPON-DISCOUNT — রিটার্ন, কুপন, ডিসকাউন্ট — `RETURNS-COUPON-DISCOUNT`

#### রিটার্ন ও রিফান্ড

**আজ:** `order_returns` header-only; `order_id` **UNIQUE** (এক অর্ডারে একটির বেশি রিটার্ন row নয়);
`status` (requested/…), `return_mode`, `restock_condition`, `restocked`, `refund_required`,
`refunded`, `refund_amount`, `refund_note`, `reason` আছে; ০ row। line-level attribution নেই।

**সিদ্ধান্ত:**

1. **Header থাকবে:** `order_returns` অর্ডার-লেভেল রিটার্ন ইভেন্ট (কারণ, মোড, অনুমোদন, রিফান্ড)।
2. **Line-level যোগ:** `order_return_items` (`order_return_id`, `order_detail_id`, `variant_id`,
   `qty`, `refund_amount`, `restock_bucket`)। `order_returns.order_id`-এর UNIQUE index সরিয়ে
   normal index করুন — নইলে একাধিক আংশিক রিটার্ন সম্ভব নয়।
3. **Flow:** `requested → approved → received → restocked → refunded` (অথবা `rejected`)।
   প্রতিটি ধাপে `audit_logs` + note; `approved_by`, `approved_at` আগের কলামেই।
4. **স্টক:** `restock_condition = sellable` হলে `RETURN` movement `bucket=sellable`;
   `damaged` হলে `bucket=damaged` — available-এ যোগ হবে না। সরাসরি stock edit নয়।
5. **রিফান্ড:** `refund_amount` ≤ ওই লাইনের net paid; original `orders.amount` **কখনো বদলাবে না**;
   realised profit থেকে `refunded=1` রিফান্ড বিয়োগ; `refund_required=1 && refunded=0` = pending।
   রিফান্ড gateway-এ না গিয়ে ম্যানুয়াল হলে `refund_note`-এ méthode/TRX রাখুন।

#### কুপন

**আজ:** `coupons` ২টি; `coupon_usages` ০ row; `CouponUsage::record()` checkout
(`CustomerController.php:497`) ও POS (`OrderController.php:1718`) — দুটোতেই কল হয়।
`coupon_usages` append-only এবং `coupon_code` snapshot রাখে — তাই কুপন delete হলেও attribution থাকে।

**সিদ্ধান্ত:**

1. **Attribution:** `coupon_usages.discount_amount` = কুপনের অংশ; `source` = checkout/POS;
   `coupon_code` snapshot। রিপোর্টে `coupons`-এর সাথে LEFT JOIN — deleted coupon-ও দেখাবে।
2. **Double-subtract নয়:** `orders.discount`-এ কুপন ডিসকাউন্ট আগেই আছে; তাই
   `Σ coupon_usages.discount_amount` **আবার বিয়োগ করবেন না** — শুধু breakdown/attribution।
3. **Usage limit:** `coupons.used_count` আর `coupon_usages` row মিলিয়ে রাখুন; usage record
   append-only, edit/delete নয়। Refund হলেও usage row মুছবেন না — `refunded` আলাদা।
4. **মেট্রিক:** uses, discount given, revenue, margin before/after coupon।
   `margin_before_coupon` = (order amount + coupon discount) − COGS;
   `margin_after_coupon` = order amount − COGS। দুইটাই দেখান।

#### ডিসকাউন্ট

1. **Authoritative:** `orders.discount`। `orders.amount` = subtotal + shipping − discount।
   `order_details.product_discount` শুধু POS-এর line-level snapshot — `orders.discount`-এর
   ভেতরেই আছে, তাই আলাদা বিয়োগ নয়।
2. **Breakdown:** `coupon_usages` = coupon part; বাকিটা manual/POS product discount।
   `orders`-এ `discount_source enum('none','coupon','manual','pos','mixed')` আর
   `discount_note` যোগ করলে রিপোর্ট ও audit সহজ হবে।
3. **Report:** gross → discount breakdown → net → COGS → profit; কোনো ধাপে একই discount
   দুইবার বিয়োগ হবে না। `orders.amount`-এর সাথে মিলে যাওয়াটাই calculator-এর built-in check।
4. **Coupon refund:** কুপন-ডিসকাউন্ট করা অর্ডারে রিফান্ড হলে cash refund ≤ actually paid;
   coupon value cash হিসেবে ফেরত নয় — `refund_note`-এ স্পষ্ট করুন।

---

## 5. বিশেষায়িত রিপোর্ট — ডেটা ম্যাপিং ও ডেটা গ্যাপ

### 5.1 পেমেন্ট মেথড অ্যানালিটিক্স

```sql
SELECT payment_method,
       COUNT(*)                              AS txns,
       SUM(payment_status = 'completed')     AS success,
       SUM(payment_status = 'failed')        AS failed,
       SUM(payment_status = 'pending')       AS pending,
       SUM(amount)                           AS volume,
       AVG(amount)                           AS avg_ticket
FROM payments
WHERE created_at BETWEEN ? AND ?
GROUP BY payment_method
ORDER BY volume DESC;
```

> ⚠️ `payment_status` আজ শুধু `'pending'` — কোনো `completed`/`failed` নেই।
> সফটওয়্যারে পেমেন্ট স্ট্যাটাস আপডেট হচ্ছে কি না যাচাই দরকার, নইলে সাকসেস রেট সবসময় ০% দেখাবে।

### 5.2 কুরিয়ার পারফরম্যান্স

উৎস ১ — `courier_shipments` (provider, current_status, shipping_cost, created_at):
```sql
SELECT provider, current_status, COUNT(*) AS parcels, SUM(shipping_cost) AS cost
FROM courier_shipments
WHERE created_at BETWEEN ? AND ?
GROUP BY provider, current_status;
```

উৎস ২ — `courier_events` (সত্যিকারের ডেলিভারি ইতিহাস, **এখন ০ রো**):
```sql
SELECT provider,
       COUNT(DISTINCT order_id)                                     AS parcels,
       COUNT(DISTINCT CASE WHEN order_status_slug = 'delivered' THEN order_id END) AS delivered,
       AVG(TIMESTAMPDIFF(HOUR, MIN(created_at), MAX(created_at)))  AS avg_hours
FROM courier_events
WHERE created_at BETWEEN ? AND ?
GROUP BY provider;
```

**সাকসেস রেটের জন্য প্রস্তাবিত ফলব্যাক:**
```
success_rate = (count(current_status = 'delivered')) / (total parcels) × 100
```
`courier_events` খালি থাকলে এই fallback ব্যবহার করবে, এবং UI-তে লিখে দেবে
"Based on shipment status; event-level tracking not available yet"।

### 5.3 রিটার্ন ও ক্যান্সেলেশন

> রিটার্ন এখন **header-only**: `order_returns.order_id` UNIQUE, লাইন/পরিমাণ নেই। তাই
> product-level return rate এখনই সম্ভব নয়। P5-এ `order_return_items` যোগ করার পর
> নিচের কোয়েরি item-level-এ বদলাবে।

উৎস ১ — `order_returns` (reason, return_mode, restock_condition, refunded, refund_amount):
```sql
SELECT return_mode,
       COALESCE(reason, 'not stated') AS reason,
       COUNT(*)                        AS requests,
       SUM(refunded = 1)               AS refunded,
       SUM(refund_amount)              AS refund_value
FROM order_returns
WHERE created_at BETWEEN ? AND ?
GROUP BY return_mode, reason
ORDER BY requests DESC;
```

উৎস ২ — ক্যান্সেলেশন `orders.order_status = 5` (cancelled) থেকে।
**লক্ষ্য:** `order_status` কলামে slug না **ID** লেখা আছে (`order_statuses` টেবিল ম্যাপ করে)।
`AnalyticsDashboardController::resolveOrderStatusBySlug()`-এর মতো একটা হেল্পার বানাতে হবে,
নইলে `'cancelled'` স্ট্রিং দিয়ে কুয়েরি করলে **শূন্য** ফিরবে — এটাই একটি লুকানো বাগ।

### 5.4 কুপন ইমপ্যাক্ট

```sql
SELECT c.code, c.type, c.value,
       COUNT(cu.id)                                AS uses,
       SUM(cu.discount_amount)                     AS discount_given,
       SUM(od.sale_price * od.qty)                 AS revenue
FROM coupons c
LEFT JOIN coupon_usages cu ON cu.coupon_id = c.id AND cu.created_at BETWEEN ? AND ?
LEFT JOIN orders o  ON o.id = cu.order_id
LEFT JOIN order_details od ON od.order_id = o.id
GROUP BY c.id
ORDER BY discount_given DESC;
```

**"কোন কুপন ভালো কাজ করছে" বলতে হলে আরও দরকার:**
- `orders.discount` থেকে কুপন ছাড়ের সত্যিকারের অংশ (কিন্তু **আবার বিয়োগ নয়** — `orders.amount`-এ
  ডিসকাউন্ট আগেই বাদ),
- ওই অর্ডারগুলোর **gross margin** — কুপনে লাভ হয়েছে কি না তা দেখতে।
  মানে: `margin_before_coupon` বনাম `margin_after_coupon`।
  এটা ছাড়া "কুপন কত ডিসকাউন্ট দিল" আর "কুপন কত লাভ করিয়েছে" — দুটো আলাদা প্রশ্ন।
  `coupon_usages.coupon_code` snapshot আছে, তাই কুপন delete হলেও attribution থাকবে।

### 5.5 SEED_FIXTURE_COVERAGE — ডেটার দায়িত্ব এখন seed-এর

v3-তে ডেটা-গ্যাপ আর স্থায়ী সীমা নয়; `DevReportFixtureSeeder`-এর চুক্তি হলো প্রতিটি রিপোর্ট
নিজের আসল query path-এ চলার মতো row পাবে। `DEV-RESET-ALLOWED`:

| রিপোর্ট | seed যা দেবে | যাচাই |
|---|---|---|
| ট্রেন্ড চার্ট | ৩০টি অর্ডার, ১২ সপ্তাহে ছড়ানো; delivered-এ `delivered_at` | ordered/delivered দুই সিরিজেই ≥ ৬ পয়েন্ট |
| পেমেন্ট মেথড | COD + bKash + Nagad; paid/failed/pending মিশ্রণ | মেথড-ভিত্তিক সাকসেস রেট ০ < x < ১০০ |
| কুরিয়ার | pathao + steadfast `courier_shipments`; delivered/returned `courier_events` | সাকসেস রেট ও গড় ডেলিভারি সময় calculable |
| রিটার্ন/ক্যান্সেল | ২টি `order_returns` + `order_return_items` + restock movement | রিটার্ন রেট ও রিফান্ড লাইন-লেভেলে দেখা যায় |
| কুপন ইমপ্যাক্ট | ৩টি `coupon_usages` (checkout + POS), `coupon_code` snapshot | uses/discount/revenue মার্জিনসহ |
| স্টক/লেজার | প্রতিটি variant-এ `variant_stocks` row + `OPENING_BALANCE`; ২টি variant threshold-এর নিচে | stock report-এ low-stock ব্যাজ |

**নিয়ম:** fixed `fake()->seed(20261116)`, `updateOrCreate` + natural key (`slug`, `sku`),
`random_int()`/`time()` নয়। Seed double-run-এ duplicate নয়। প্রতিটি seed শেষে row-count
assertion; কোনো assertion fail করলে seeder exception ছুড়বে — চুপচাপ খালি রিপোর্ট নয়।
"ডেটা নেই" empty state UI-তে থাকবে defensive হিসেবে, কিন্তু canonical seed-এর ফল কখনো খালি হবে না।

---

## 6. RBAC_MATRIX — পারমিশন ম্যাট্রিক্স

### 6.1 নতুন পারমিশন (৮টি)

| পারমিশন নাম | কী দেখায় | ডিফল্ট রোল |
|---|---|---|
| `report-sales-view` | অর্ডার রিপোর্ট, বিক্রি সারাংশ | Admin, Manager |
| `report-profit-view` | **নেট প্রফিট, মার্জিন %** | **Admin** |
| `report-stock-view` | স্টক রিপোর্ট, স্টক মূল্য | Admin, Manager |
| `report-inventory-ledger-view` | মুভমেন্ট লেজার | Admin, Manager |
| `report-payment-view` | পেমেন্ট অ্যানালিটিক্স | Admin |
| `report-courier-view` | কুরিয়ার পারফরম্যান্স | Admin, Manager |
| `report-returns-view` | রিটার্ন/ক্যান্সেলেশন | Admin, Manager |
| `report-coupon-view` | কুপন ইমপ্যাক্ট | Admin, Manager |

### 6.2 নিয়ম

1. **`Support` রোল পেতে একটিও আর্থিক রিপোর্ট পারমিশন নয়** — এটাই মূল উদ্দেশ্য।
2. `report-profit-view` শুধু Admin-এ — কারণ এটা সবচেয়ে সংবেদনশীল।
3. রিপোর্ট রুটে মিডলওয়্যার: `->middleware('permission:report-sales-view')` — একই প্যাটার্নে
   যেমন `routes/web.php:1291`।
4. **সাইডবারের লিংকও লুকাতে হবে** — শুধু রুট গার্ড করলে ইউজার ক্লিক করে ৪০৩ দেখবে।
   `resources/views/backEnd/layouts/partials/sidebar.blade.php`-এ `@can('report-profit-view')` ব্যবহার করুন।
5. সাইডবারে "Finance/Reports" আলাদা সেকশন হিসেবে থাকলে দারুণ হয় — দর্শক ইউজার খুঁজে পায়।

### 6.3 বাস্তবায়ন

- পারমিশন সিড: `database/seeders/ReportPermissionSeeder.php` (idempotent, `firstOrCreate`)।
- রোল-ম্যাপিং: শুধু নতুন পারমিশনে `givePermissionTo()`; **`syncPermissions()` কখনো নয়** —
  বর্তমান ৭২টি লিংক (Admin ৭২, Manager ০, Support ০) মুছে যাবে।
- **নিরাপত্তা:** seed-এর আগে/পরে `role_has_permissions` count মিলিয়ে দেখুন — শুধু প্রত্যাশিত
  নতুন লিংক বাড়বে; Admin-এর ৭২টি পুরোনো লিংক অটুট থাকতে হবে।

---

## 7. NOTIFICATION_ARCHITECTURE — সার্ভার + ক্লায়েন্ট

### 7.1 সার্ভার পার্ট (আছে, ঠিক আছে)

```
GenericDbNotification (via: database)
        ↓
notifications টেবিল (২০১ রো)
        ↓
navbar.blade.php:57-105 → বেল + ৫টি লেটেস্ট + আনরিড ব্যাজ
```

এটা **কাজ করছে** এবং ঠিক আছে। এখানে কিছু বদলানোর দরকার নেই।

### 7.2 ক্লায়েন্ট পার্ট — বাস্তবসম্মত ৩টি অপশন

**প্রস্তাবিত: লাইটওয়েট পোলিং (সুপারিশ করা)**

```
new GenericDbNotification(...)  →  notifications টেবিল
        ↓
admin.notifications.poll রুট → { unread: N, latest: [...] }  (JSON)
        ↓
admin-notifications-poller.js → প্রতি ২০ সেকেন্ডে fetch → বেল ব্যাজ আপডেট + Toastr
```

| সুবিধা | কারণ |
|---|---|
| নতুন ইনফ্রা দরকার নেই | Redis নেই, Redis-এর কোনো queue/broadcast নেই |
| Broadcast ড্রাইভার লাগে না | `BROADCAST_DRIVER=log` — Echo বা Pusher লাগে না |
| ডেটা ফাঁকা হলে ঝুঁকি নেই | রুট শুধু `notifications` টেবিল পড়ে |
| ২০ সেকেন্ড বিলম্ব গ্রহণযোগ্য | স্টক অ্যালার্ট/অর্ডার নোটিফিকেশনে যথেষ্ট |

**বিকল্প ২ — SSE (Server-Sent Events):** লাইভ কিন্তু `php artisan serve`-এর single-threaded
সার্ভারে **প্রতিটি কানেকশন সার্ভারটি আটকে রাখবে** — ডেভ পরিবেশে ঝুঁকি। প্রডাকশনে (php-fpm/nginx)
ঠিক আছে, কিন্তু এই মেশিনে ঝুঁকি বেশি। **সুপারিশ করা হয় না এখন।**

**বিকল্প ৩ — Laravel Reverb / WebSockets:** সবচেয়ে সুন্দর, কিন্তু আলাদা সার্ভার প্রসেস,
আলাদা পোর্ট, আর সাধারণত Redis দরকার। বর্তমানে খরচ বেশি, মান আপেল নয়।

> **সিদ্ধান্ত:** পোলিং (বিকল্প ১)। যদি ভবিষ্যতে Reverb চালু হয়, পোলিং বন্ধ করে Echo-তে
> মুখান্তর করা যাবে — কারণ সার্ভার পার্ট (DB চ্যানেল) একই থাকবে।

### 7.3 যা বদলাবেন না

- `GenericDbNotification::via()` → `['database']` থাকবে। `mail` যোগ করলে প্রতিটি
  নোটিফিকেশনে ইমেইল যাবে (মেইলহগ বন্ধ, ২০১ রো থাকলে সমস্যা)।
- বেল ড্রপডাউনের মার্কআপ — কাজ করছে, সেটা ভাঙবেন না।

---

## 8. EXPORT_FORMAT_FIX — আইকন ও ফরম্যাট

### 8.1 সমস্যা (প্রমাণিত)

```
বাটন আইকন : ri-file-excel-line   ← এক্সেল
ডাউনলোড   : .csv (text/csv)      ← সিএসবি
```

### 8.2 সমাধান — দুই ধাপ

**ধাপ ১ (এখনই, ~৩০ মিনিট):** আইকন বদলান →
`ri-file-spreadsheet-line` (CSV হলে) অথবা লেবেল স্পষ্ট করে `"Export CSV"`।
ফাইল: `reports/stock.blade.php:97`, `reports/order.blade.php:96`,
`public/admin/assets/js/admin-report-table.js`.

**ধাপ ২ (পরে, ~১ দিন):** আসল XLSX এক্সপোর্ট, সার্ভার-সাইড:
- `composer require phpoffice/phpspreadsheet`
- নতুন রুট: `GET admin/reports/{report}/export?format=xlsx`
- শিট ১: Summary (KPI), শিট ২: Detail (ফিল্টারসহ), শিট ৩: Chart data
- বাংলা টেক্সটের জন্য `DejaVu Sans` ফন্ট সেট করা লাগবে — ডিফল্ট Calibri বাংলা দেখায় না

**সতর্কতা:** ক্লায়েন্ট-সাইড CSV এখন যেভাবে কাজ করছে সেটা ভাঙবেন না — এটা ভালো সমাধান,
পেজ লোড হয় না, সার্ভারে লোড নেই। XLSX শুধু **অ্যাডভান্সড** অপশন হিসেবে যোগ করুন।

---

## 9. SCHEDULED_REPORTING — শিডিউলড ও ইমেইল রিপোর্ট

### 9.1 ডিজাইন

```
app/Console/Commands/SendScheduledReport.php
    ├─ signature: reports:send {--report=} {--period=daily|weekly|monthly}
    ├─ রিপোর্ট ডেটা তৈরি (NetProfitCalculator + ট্রেন্ড কোয়েরি)
    ├─ Mailable (resources/views/emails/reports/scheduled-report.blade.php)
    └─ প্রাপক: admin role-এর ইউজারদের email (general_settings থেকে বা নতুন টেবিল)

app/Console/Kernel.php
    $schedule->command('reports:send --report=sales --period=daily')->dailyAt('08:00');
    $schedule->command('reports:send --report=sales --period=weekly')->weeklyOn(1, '08:00');
    $schedule->command('reports:send --report=profit --period=monthly')->monthlyOn(1, '08:00');
```

### 9.2 যা বাধা করবে (এখনই ঠিক করতে হবে)

| বাধা | প্রমাণ | সমাধান |
|---|---|---|
| মেইলহগ বন্ধ | `ss -ltn`-এ ১০২৫ নেই | আসল SMTP ক্রেডেনশিয়াল দিন, অথবা `docker run -d -p 1025:1025 mailhog/mailhog` |
| `QUEUE_CONNECTION=sync` | `.env` | `.env` → `QUEUE_CONNECTION=database` + `php artisan queue:table` + `queue:work` সেটআপ |
| রিপোর্ট সেটিং সংরক্ষণ নেই | কোনো টেবিল নেই | নতুন টেবিল `scheduled_reports` (report_key, period, recipients json, is_active, last_sent_at) |
| ক্রন চালু নেই | `Kernel::schedule()` ফাঁকা | `* * * * * cd /path && php artisan schedule:run` — ক্রন লাগবে |

### 9.3 ইমেইলের ভেতরে কী থাকবে

- সারাংশ: অর্ডার সংখ্যা, gross revenue, net profit, margin %
- ট্রেন্ড ছোট একটা SVG স্পার্কলাইন (এমেইলে JS চলে না, তাই **সার্ভার-সাইড SVG**)
- টপ ৫ প্রোডাক্ট, টপ ৫ কাস্টমার
- লিংক: "অ্যাডমিনে বিস্তারিত দেখুন" (permission চেকসহ URL)

> ইমেইলে ফাইল (CSV/XLSX) অ্যাটাচ করা যাবে — কিন্তু বড় রিপোর্টে মেইল সার্ভার লিমিট লাগে।
> **প্রথমে শুধু ইনবউক্সে সারাংশ + লিংক**, পরে অ্যাটাচমেন্ট যোগ করবেন।

---

## 10. IMPLEMENTATION_PHASES — বাস্তবায়ন পর্ব

> Dev site, তাই প্রতিটি পর্বের canonical reset: `php artisan migrate:fresh --seed`।
> প্রতিটি পর্ব নিজে থেকে ship হওয়ার মতো; পরের পর্ব আগেরটির উপর দাঁড়ায়।
> কোনো production-style backfill, legacy data preservation বা dual-write নেই (`DEV-RESET-ALLOWED`)।

### P0 — Dev reset + canonical seed + data integrity ⬅️ **এখনই করুন**

> চার্ট/প্রফিটের আগে ডেটা সত্য করতে হবে। v3-তে এটা backfill নয় — **ডেটা তৈরি**।

| কাজ | ফাইল |
|---|---|
| `DevCatalogSeeder`: product + ২–৪টি gallery image + variants (unique `sku`, attributes, price, image) + `variant_stocks` + `OPENING_BALANCE` ledger | `database/seeders/DevCatalogSeeder.php`, `database/seeders/data/dev-catalog.php` (নতুন) |
| `DevOrderSeeder`: ৩০টি অর্ডার `OrderPlacementService::place()` দিয়ে; delivered/cancelled/shipped status domain path দিয়ে | `database/seeders/DevOrderSeeder.php` (নতুন) |
| `DevReportFixtureSeeder`: ২টি return + items, ৩টি coupon usage, pathao+steadfast shipment/event | `database/seeders/DevReportFixtureSeeder.php` (নতুন) |
| `DatabaseSeeder` cleanup: চারটি conflicting product seeder ও raw `DemoOrderDetailsSeeder` বাদ | `database/seeders/DatabaseSeeder.php` |
| `orders.delivered_at`, `cancelled_at` nullable + index | নতুন মাইগ্রেশন |
| checkout-এ `RESERVE`, approve-এ `SALE`, cancel-এ `RELEASE`, return restock-এ `RETURN` movement | `OrderPlacementService`, order status path, `ReturnsRefundsController` |
| seed guard: variant ছাড়া product নেই, stock row ছাড়া variant নেই, image না থাকলে fail-fast | seeders |
| `products.stock` read/write path-এর পূর্ণ inventory (grep gate) | controllers/services/views |

**যাচাই:** `migrate:fresh --seed` exit 0; ৩০ প্রোডাক্ট, প্রতিটিতে ≥ ১ variant, সমান সংখ্যক
`variant_stocks` row, প্রতিটি variant-এ image; ৩০ অর্ডার / ২ return / ৩ coupon usage / ≥ ৬ courier event;
`inventory_movements`-এ `OPENING_BALANCE` + `SALE`; placement-এ `available` কমে;
`grep -rn "products.stock" app database` শুধু মাইগ্রেশন/retire-list দেখায়।

### P1 — RBAC ও finance gate

| কাজ | ফাইল |
|---|---|
| ৮টি report permission + `order-finance-edit` + `audit-log-view` সিড | `database/seeders/ReportPermissionSeeder.php` (নতুন) |
| রিপোর্ট + finance-সংবেদনশীল রুটে `permission:` middleware | `routes/web.php` |
| সাইডবারে `@can(...)` গেট | `resources/views/backEnd/layouts/partials/sidebar.blade.php` |
| 403 পেজ template-স্টাইল | `resources/views/errors/403.blade.php` |
| report view/export audit log | `AuditLogger` |

**যাচাই:** Support → ৪০৩; Manager → allowed ২০০, profit/payment ৪০৩; Admin → সব ২০০;
`role_has_permissions`-এ আগের ৭২টি লিংক অটুট; `migrate:fresh --seed`-এর পরেও permission সিড চলে।

### P2 — দুই লাভ + ফ্রি শিপিং + নোট

| কাজ | ফাইল |
|---|---|
| `courier_shipments.shipping_cost` | মাইগ্রেশন, `PathaoOrderSender.php`, seed fixture |
| `NetProfitCalculator` (দুই ভিউ) | `app/Services/Report/NetProfitCalculator.php` (নতুন) |
| `orders.shipping_is_free`, `shipping_list_price`; read-only toggle + free action | মাইগ্রেশন, order create/edit view, `OrderController` |
| customer order page-এ ফ্রি হলে ড্রপডাউন লুকানো | checkout/order view |
| `orders.admin_note` create/edit + `order.note.updated` audit | `OrderController`, `AuditLogger` |
| অর্ডার/প্রফিট রিপোর্টে View A + View B + free-shipping value | `reports/order.blade.php` |

**যাচাই:** seed-এর deterministic delivered order-এ View A = View B + shipping margin − courier cost;
free toggle-এ `shipping_charge=0`, `shipping_list_price` snapshot, audit row; নোট save-এ before/after।

### P3 — টাইম-সিরিজ চার্ট

| কাজ | ফাইল |
|---|---|
| `SalesTrendService` — ordered (`created_at`) vs delivered (`delivered_at`) | `app/Services/Report/SalesTrendService.php` |
| `created_at`/`delivered_at` ফিল্টার | `OrderController::stock_report()` / `order_report()` |
| চার্ট কার্ড + ApexCharts | `reports/stock.blade.php`, `reports/order.blade.php`, `admin-page-reports.js` |
| ডেটা পাসিং | `data-report-charts` JSON attr |

**যাচাই:** `/admin/order-report?period=quarter`-এ ১২ সপ্তাহের ordered সিরিজ ও delivered সিরিজ,
কনসোলে error নেই; period query string-এ থাকে; seed-এর বাইরে ডেটা না থাকলেও দুটো সিরিজই ভিন্ন।

### P4 — স্টক (ledger-only) ও `products.stock` ড্রপ

| কাজ | ফাইল |
|---|---|
| `variant_stocks.low_stock_threshold` | মাইগ্রেশন |
| স্টক রিপোর্ট variant থেকে পড়া + low-stock ব্যাজ + ledger সেকশন | `reports/stock.blade.php` |
| edit-এ stock read-only + "Adjust stock" movement action | product/variant forms, `InventoryController` |
| `ProductStockSync`, `InitVariantStocksCommand`, `BackfillDefaultVariantsCommand` retire | `app/Services`, `app/Console/Commands` |
| edit/update/price_update/quick_update/order approve-এ stock write বন্ধ | `ProductController`, `OrderController` |
| duplicate default variant guard (seed/creation path) | `ProductController`, seeders |
| reconcile যাচাইয়ের পর `products.stock` ড্রপ | মাইগ্রেশন |
| অ্যালার্ট trigger + idempotent notification | `InventoryController`, order status path, returns |

**যাচাই:** edit ফর্মে স্টক বাড়ানো যায় না; adjustment movement note ছাড়া save হয় না;
threshold-এর নিচে নামলে একবার notification; `variant_stocks.available` = লেজার projection;
`SHOW COLUMNS FROM products LIKE 'stock'` → empty; seed double-run-এ duplicate variant/stock নেই।

**অবস্থা (2026-11-16):** adjustment (+/−/damage/ below-zero refusal), whole-dataset KPI,
storefront ATP, reservation-aware product availability, Stock Report-এর variant read-path
এবং legacy `products.stock` reconcile সম্পন্ন ও regression-audited (`ShopWorkflowAuditTest`
৯ passed + `StockReportReadPathTest` ২ passed + `LegacyStockReconcileTest` ১ passed +
`StockReportFilterTest` ২ passed; live mismatch ৩০/৩০ → ০)। Same-day date range ও
pagination query-string preserve করা হয়েছে। এখনো বাকি: `ProductStockSync` retire,
`products.stock` drop, low-stock alert trigger।

### P5 — ৪টি বিশেষায়িত রিপোর্ট + রিটার্ন/কুপন

| কাজ | ফাইল |
|---|---|
| `ReportsController`-এ payment/courier/returns/coupon actions | `app/Http/Controllers/Admin/ReportsController.php` |
| `order_return_items` + `order_returns.order_id` unique drop | মাইগ্রেশন |
| return flow: requested→approved→received→restocked→refunded + audit | `ReturnsRefundsController` |
| coupon/discount breakdown (double-count guard) | `CouponUsage` + report queries |
| "ডেটা নেই" empty state | শেয়ার্ড পার্শিয়াল |

**যাচাই:** ৪টি রুট ২০০/৪০৩; seed fixture-এ চারটি রিপোর্টই অ-শূন্য; partial return-এ
item-level restock/refund; coupon report-এ `orders.discount` ও `coupon_usages` একবারই গণনা।

### P6 — এক্সপোর্ট, নোটিফিকেশন ও শিডিউলড রিপোর্ট

| কাজ | ফাইল |
|---|---|
| CSV আইকন/লেবেল সংশোধন; XLSX (পরে) | `admin-report-table.js`, report views |
| admin notification polling + mark read | নতুন রুট + `admin-notifications.js` |
| `scheduled_reports` + command + Mailable + queue + cron | মাইগ্রেশন, `SendScheduledReport`, `ScheduledReportMail`, `Kernel` |

**যাচাই:** CSV বাটনে "Export CSV"; bell ২০s-এ update; `php artisan reports:send --report=profit --period=daily`
→ mail log; `schedule:list`-এ ইভেন্ট।

---

## 11. ঝুঁকি ও সিদ্ধান্ত

| # | ঝুঁকি | প্রভাব | সমাধান |
|---|---|---|---|
| R1 | Seed fixture অসম্পূর্ণ/random | রিপোর্ট খালি বা ভিন্ন সংখ্যা | fixed seed + natural key + row-count assertion; `migrate:fresh --seed` canonical |
| R2 | `raw_payload` ওভাররাইট হয় | শিপিং খরচ হারিয়ে যায় | `shipping_cost` কলাম (P2) |
| R3 | কেউ seed bypass করে product/variant তৈরি করে | stock row/image ছাড়া প্রোডাক্ট | `ProductController`-এর variant+stock path একমাত্র write path; seed guard |
| R4 | স্টোরফ্রন্টে স্টক মুভমেন্ট লেখা হয় না | লেজার অসম্পূর্ণ | P0-তে checkout/approve/cancel/return movement |
| R5 | `payment_status` সবসময় `pending` | সাকসেস রেট ০% | seed-এ paid/failed/pending মিশ্রণ + payment status path যাচাই |
| R6 | `order_status` স্ট্রিং না ID | ক্যান্সেল কুয়েরি ভাঁকা | স্লাগ↔ID হেল্পার |
| R7 | মেইলহগ বন্ধ | শিডিউলড রিপোর্ট কাজ করবে না | SMTP ক্রেডেনশিয়াল (P6) |
| R8 | `QUEUE_CONNECTION=sync`, `jobs` নেই | শিডিউলার ব্লক হবে | `database` কিউ + `queue:work` |
| R9 | ফোন/মোবাইলে চার্ট | রিপোর্ট পড়া যাবে না | ApexCharts `responsive`, ছোট স্ক্রিনে টেবিল fallback |
| R10 | `order_returns.order_id` UNIQUE | একাধিক আংশিক রিটার্ন অসম্ভব | unique index drop + `order_return_items` |
| R11 | ফ্রি শিপিং toggle-এর ON/OFF উল্টো বোঝা | ভুল করে চার্জ/ফ্রি হতে পারে | read-only toggle + audit + helper text; §4B.2-এর সিদ্ধান্ত |
| R12 | `products.stock` ড্রপের পর পুরোনো কোড | fatal SQL / স্টক শূন্য | সব read/write path মাইগ্রেট + `grep -rn "products.stock"` gate + fresh-seed যাচাই |
| R13 | Seed idempotency ভাঙা | দ্বিতীয় run-এ duplicate product/order | `updateOrCreate` + `sku`/`slug`; fixed `fake()->seed(20261116)` |

### সিদ্ধান্ত — আগের ওপেন প্রশ্নের উত্তর

1. **শিপিং:** two views — View A delivery-inclusive, View B product-only; shipping revenue
   আর actual courier cost আলাদা লাইন। ফ্রি শিপিং ভ্যালু double-count নয়। ✅
2. **ডেটা:** dev site, তাই canonical baseline-ই seed; production-style backfill/legacy preservation
   নেই। `[DEMO]` লেবেল লাগবে না — seed origin `DevCatalogSeeder`-এ documented, আর
   seed-ই একমাত্র ডেটা উৎস। `DEV-RESET-ALLOWED` ✅
3. **শিডিউলড রিপোর্ট প্রাপক:** permission-ভিত্তিক (`report-sales-view` / `report-profit-view`),
   `User::all()` নয়। ✅
4. **থ্রেশহোল্ড:** variant-level (`variant_stocks.low_stock_threshold`); variant-হীন
   প্রোডাক্ট থাকবেই না, তাই fallback-এর প্রশ্ন নেই। ✅
5. **স্টক:** `products.stock` থাকবে না; edit-এ স্টক বাড়ানো যাবে না, শুধু adjustment movement। ✅
6. **অর্ডার নোট:** `orders.admin_note` create+edit-এ; `audit_logs.note` আলাদা; customer-facing নয়। ✅
7. **ফ্রি শিপিং:** read-only status toggle; ON = ফ্রি → customer dropdown লুকানো;
   `shipping_list_price` snapshot + `shipping_charge = 0`। ✅

---

## 12. প্রমাণ সংগ্রহের কমান্ড (যা দিয়ে এই ডকুমেন্টের দাবি যাচাই করা হয়েছে)

```bash
cd "/home/sahon/Desktop/new evo"

# রিপোর্ট রুট
grep -n "stock-report\|order-report" routes/web.php

# রিপোর্ট কন্ট্রোলার
sed -n '1549,1628p' app/Http/Controllers/Admin/OrderController.php

# ট্রেন্ড লজিক
sed -n '800,856p' app/Http/Controllers/Admin/AnalyticsDashboardController.php

# অর্ডারের টাকার সূত্র
sed -n '46,62p' app/Services/OrderPlacementService.php

# এক্সপোর্ট আইকন বনাম ফরম্যাট
grep -n "ri-file-excel-line" resources/views/backEnd/reports/*.blade.php
grep -n "csv\|download" public/admin/assets/js/admin-report-table.js

# লেজার ও স্টক
export MYSQL_PWD="$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT * FROM inventory_movements;"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT * FROM variant_stocks;"

# RBAC বাস্তবতা
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT COUNT(*) FROM permissions;"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT r.name, COUNT(rp.permission_id) FROM roles r LEFT JOIN role_has_permissions rp ON rp.role_id=r.id GROUP BY r.id;"
grep -rn "permission:" routes/web.php | wc -l
grep -rn "@can" resources/views/backEnd | wc -l

# স্টক কভারেজ ও অমিল
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT COUNT(*) products, SUM(v.id IS NULL) no_variant FROM products p LEFT JOIN product_variants v ON v.product_id=p.id;"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT COUNT(*) variants FROM product_variants; SELECT COUNT(*) stock_rows FROM variant_stocks;"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT p.id,p.stock,SUM(vs.available) variant_sum FROM products p JOIN product_variants v ON v.product_id=p.id LEFT JOIN variant_stocks vs ON vs.variant_id=v.id GROUP BY p.id;"

# নোট ও রিটার্ন স্কিমা
mysql -h 127.0.0.1 -u root -D z_evo -e "SHOW COLUMNS FROM audit_logs; SHOW COLUMNS FROM orders LIKE '%note%';"
mysql -h 127.0.0.1 -u root -D z_evo -e "SHOW COLUMNS FROM order_returns; SHOW INDEX FROM order_returns;"

# v3 seed baseline (canonical)
php artisan migrate:fresh --seed
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT COUNT(*) products FROM products; SELECT COUNT(*) variants FROM product_variants; SELECT COUNT(*) stock_rows FROM variant_stocks;"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT COUNT(*) opening FROM inventory_movements WHERE type='OPENING_BALANCE'; SELECT COUNT(*) sales FROM inventory_movements WHERE type='SALE';"
mysql -h 127.0.0.1 -u root -D z_evo -e "SELECT COUNT(*) FROM coupon_usages; SELECT COUNT(*) FROM order_returns; SELECT COUNT(*) FROM courier_events; SELECT COUNT(*) FROM orders;"

# legacy stock writer বাকি আছে কি না
grep -rn "products.stock" app database --include=*.php | grep -v migrations | head -20
grep -rn "ProductStockSync" app database --include=*.php | head -20

# স্কেডিউলার খালি
grep -n "schedule" app/Console/Kernel.php

# সার্ভিস পোর্ট
ss -ltn | grep -E ':(1025|3306|6379|8000)'
```

---

## 13. সারাংশ

v3-তে আপনার মূল দাবি — "এই প্রোডাক্ট/অর্ডার বাস্তব নয়, ডেভেলপমেন্ট সাইট" — পুরো প্ল্যানের
ভিত্তি হিসেবে গৃহীত। এখন কাজের ক্রম:

1. **Seed-first baseline (`SEED-FIRST-V3`, `DEV-CATALOG-SEED`)** — একটাই canonical
   `DevCatalogSeeder` + `DevOrderSeeder` + `DevReportFixtureSeeder`; চারটি conflicting
   product seeder ও raw order seeder বাদ। `php artisan migrate:fresh --seed`-ই canonical reset।
2. **Variant-stock-image completeness (`SEED-VARIANTS-STOCK-IMAGES`)** — প্রতিটি প্রোডাক্টে
   variant, প্রতিটি variant-এ unique SKU/attributes/price/image আর `variant_stocks` row;
   initial stock `OPENING_BALANCE` movement।
3. **Legacy removal (`DROP-LEGACY-STOCK`)** — `products.stock`, `ProductStockSync`,
   `InitVariantStocksCommand`, `BackfillDefaultVariantsCommand` retire; কোনো backfill নয়।
4. **RBAC (`RBAC-CORRECTED`)** — ৭২ permission; Admin=৭২, Manager=০, Support=০;
   `permission:` middleware মাত্র ১১টি; `@can`=০ — তাই P1-এ রিপোর্ট + finance gate একসাথে।
5. **স্টক (`STOCK-SSOT-LEDGER`, `NO-PRODUCTS-STOCK`)** — SSOT = `variant_stocks` +
   `inventory_movements`; edit-এ স্টক নয়, শুধু adjustment movement।
6. **ট্রেন্ড (`TREND-ORDERED-DELIVERED`)** — Ordered (`created_at`) ও Delivered (`delivered_at`)
   দুই সিরিজ; `updated_at` ফিল্টার বাদ।
7. **লাভ (`DUAL-PROFIT-VIEWS`)** — View A delivery-inclusive, View B product-only;
   `orders.amount` authoritative, discount একবারই।
8. **নোট (`ORDER-NOTES`)** — `audit_logs.note` + admin `admin_note` + `order.note.updated` audit।
9. **রিটার্ন/কুপন/ডিসকাউন্ট (`RETURNS-COUPON-DISCOUNT`)** — `order_returns` header +
   `order_return_items`; coupon শুধু attribution; discount দুইবার নয়।
10. **ক্রম** — seed reset → integrity → RBAC → profit → chart → stock → reports → automation।

আর একটা কথা — **এক্সপোর্ট আইকনটা শুধু মিনিটের কাজ, কিন্তু কার্যকরিতার দিক থেকে এটা সবচেয়ে
সস্তা মুনাফি** — কারণ আজকাল কেউ CSV নাম দেখে এক্সেল ফাইল আশা করে, সেটা পায় না,
আর ফিলিং করে সাধারণ ব্যবহারকারীর হাতে সমস্যা হয়।

---

## 14. বাস্তবায়ন অবস্থা — ২০২৬-১১-১৬ (v3 seed layer)

`php artisan migrate:fresh --seed`-এ যা তৈরি হবে, তা এখন scratch DB-তে verified।

| অংশ | ফাইল | প্রমাণ |
|---|---|---|
| Catalog | `DevCatalogSeeder` | `DEV_CATALOG_SEED_OK` (30/30/30) |
| Shipping rates | `DevShippingChargeSeeder` | ৩টি row: 120 / 70 / 130, read-only guards |
| Orders | `DevOrderSeeder` | 30 invoice, 35 line, status 12/10/4/2/2 |
| Ledger | `InventoryLedger` | OPENING 30 · RESERVE 35 · SALE 21 · RELEASE 2 · RETURN 2 · DAMAGED 1 |
| Lifecycle | `OrderLifecycle` | `delivered_at` 14, `cancelled_at` 2, audit 56 status row |
| Returns/coupons/courier | `DevReportFixtureSeeder` | 2 returns + 3 items, 3 usages, 6 shipments + 14 events |
| Verifier | `tools/verify_dev_seed_bundle.sh` | `DEV_SEED_BUNDLE_OK` (দুইবার, idempotent) |

বিস্তারিত: `docs/DEV-ORDER-SEED-2026-11-16.md`।

বাকি: আসল `z_evo`-তে একবার `php artisan migrate:fresh --seed` চালানো (user চালাবে),
তারপর `DROP-LEGACY-STOCK` ধাপে `products.stock` retire করা।

---

**সংশোধনের ইতিহাস**

| তারিখ | পরিবর্তন | লেখক |
|---|---|---|
| ২০২৬-১১-১৬ | প্রথম সংস্করণ — লাইভ কোড ও `z_evo` ডেটাবেজ দেখে যাচাইকৃত | zombiecoder |
| ২০২৬-১১-১৬ | **v2** — RBAC/stock/trend দাবি সংশোধিত; নোট, ফ্রি শিপিং, দুই লাভ, লেজার-অনলি স্টক, রিটার্ন/কুপন/ডিসকাউন্ট যোগ; ফেজ ক্রম বদলানো | manu |
| ২০২৬-১১-১৬ | **v3 (`SEED-FIRST-V3`)** — ডেটা-গ্যাপ ফ্রেমিং বাদ; canonical seed layer (catalog/variant/stock/image/order/report fixture), legacy `products.stock` outright retire, P0 = dev reset + seed | zombiecoder |
