# Dev catalog seed — canonical Adora 30 — `DEV-CATALOG-SEED`

**তৈরি:** ২০২৬-১১-১৬
**Seeder:** `database/seeders/DevCatalogSeeder.php`
**Manifest:** `public/seed-assets/adora-2026/catalog.json`
**Verifier:** `tools/verify_dev_catalog.php`

## কী করে

`DevCatalogSeeder` committed bundle থেকে canonical dev catalog বানায়। প্রতিটি
catalog entry-র জন্য:

| ধাপ | কী লেখা হয় | natural key |
|---|---|---|
| Category | ১২টি age category (`Premature` → `Baby Elements`) | `slug` |
| Product | নাম, slug, দাম, description, flags | `slug` |
| Gallery | catalog-এর সব ছবি, exact order | `product_id` + delete/insert |
| Variant | একটি default variant | `sku = AD-<source_id>-DEFAULT` |
| Stock | একটি `variant_stocks` row | `variant_id` |
| Ledger | একটি `OPENING_BALANCE` movement | `variant_id` + `type` |

`products.stock`-এ ২৫ লেখা হয় শুধু `DROP-LEGACY-STOCK`-এর আগে পর্যন্ত — কারণ
কলামটি এখনো `NOT NULL`, আর `ProductStockSync` ওই মান থেকেই variant stock row
তৈরি করে। stock-এর আসল SSOT `variant_stocks` + `inventory_movements`।

## Determinism

- Product code: `AD-<source_id>` — source dump-এর `P0001`-জাতীয় code পুরোনো demo
  seeders-এর সাথে সংঘর্ষ করে, তাই source id ব্যবহার করা হয়েছে।
- Variant: প্রতি প্রোডাক্টে ঠিক একটি, SKU `AD-<source_id>-DEFAULT`, title
  `<name> - Default`, `attributes_json = {"Size":"Default"}`।
- ছবি: `SeedAssets::relativeUploadPath()` → `uploads/product/seed/<slug>/<file>`;
  DB-তে bundle path কখনো যায় না।
- কোনো `random_int()`, `time()`, `Str::random()` নেই। একই bundle → একই rows।

## Idempotency

- Product: slug দিয়ে match; থাকলে update, না থাকলে insert।
- Gallery: exact set — পুরোনো rows delete করে catalog order-এ insert।
- Variant: SKU দিয়ে match; stock row শুধু missing হলে `ProductStockSync` তৈরি করে।
- `OPENING_BALANCE`: শুধু না থাকলে লেখা হয় — দ্বিতীয় run-এ duplicate movement নেই।

## DatabaseSeeder wiring

ক্রম:

1. `SeedAssetSeeder` — bundle → `public/uploads/product/seed/`
2. `DevCatalogSeeder` — canonical catalog
3. `StorefrontDemoSeeder` decoration-only — banners, services, homepage switches,
   theme; কোনো product/category/brand লেখে না

`DatabaseSeeder` থেকে সরানো হয়েছে:

- `DemoStorefrontSeeder` (১০টি পুরোনো fashion/canvas product)
- `ShinjukuProductsSeeder` (২০টি grocery product)
- `DemoOrderDetailsSeeder` (random SKU + order; `DevOrderSeeder` পরে আসবে)
- `StorefrontDemoSeeder`-এর product/category অংশ (decoration-only instance দিয়ে প্রতিস্থাপিত)

ফাইলগুলো মুছে ফেলা হয়নি — শুধু `DatabaseSeeder` আর canonical path-এ নেই।

## চালানো ও যাচাই

```bash
php artisan db:seed --class=SeedAssetSeeder
php artisan db:seed --class=DevCatalogSeeder
php artisan db:seed --class=DevCatalogSeeder   # idempotency
php tools/verify_dev_catalog.php
```

## প্রমাণ (২০২৬-১১-১৬)

```text
run 1: Adora catalog: 30 products (30 created, 0 updated).
run 2: Adora catalog: 30 products (0 created, 30 updated).

PRODUCTS 30
IMAGES 30
VARIANTS 30
DEV_CATALOG_SEED_OK
```

Verifier যা যাচাই করে:

- প্রতি slug-এ ঠিক ১টি product
- catalog-এর সাথে মিলে যাওয়া exact gallery, প্রতিটি ফাইল disk-এ আছে
- প্রতি product-এ ঠিক ১টি variant, SKU `AD-<source_id>-DEFAULT`
- প্রতি variant-এ ঠিক ১টি `variant_stocks` row
- প্রতি variant-এ ঠিক ১টি `OPENING_BALANCE` movement
- ১২টি category-র প্রতিটি ঠিক ১টি slug row

## সীমা ও পরবর্তী ধাপ

- বর্তমান dev DB-তে পুরোনো ৩০টি fixture product এখনো আছে; canonical state পেতে
  `php artisan migrate:fresh --seed` চালাতে হবে (`DEV-RESET-ALLOWED`)।
- `DevOrderSeeder` এখনো আসেনি — তাই fresh seed-এ orders থাকবে না। পরের ধাপে
  variant-aware order/payment/shipping fixture আসবে।
- `products.stock` bridge `DROP-LEGACY-STOCK` ধাপে সরবে; তখন `DevCatalogSeeder`-এর
  এক লাইন ও `ProductStockSync`-এর legacy dependency বাদ যাবে।

সম্পর্কিত:

- `database/seeders/DevCatalogSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/StorefrontDemoSeeder.php` (`decorationOnly()`)
- `app/Support/SeedAssets.php`
- `docs/SEED-ASSETS-ADORA-2026.md` (`ADORA-2026`)
- `docs/PLAN-reports-analytics-2026-11-16.md` (`SEED-FIRST-V3`, `DEV-CATALOG-SEED`)
