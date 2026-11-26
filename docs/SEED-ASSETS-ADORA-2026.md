# Adora 2026 seed assets — portable demo catalog — `ADORA-2026`

**তৈরি:** ২০২৬-১১-১৬
**উৎস (provenance only):** `/media/sahon/301E3D251E3CE60E/Adora 2026/`
**Bundle:** `public/seed-assets/adora-2026/`

## কেন এই bundle

v3 seed-first plan (`SEED-FIRST-V3`, `DEV-CATALOG-SEED`) অনুযায়ী dev baseline-এ আসল
প্রোডাক্ট নাম ও ডেমো ছবি থাকবে। কিন্তু প্রজেক্ট যেখানে বিক্রি বা ডিপ্লয় হবে, সেখানে
`Adora 2026` external drive থাকবে না। তাই:

1. নাম ও ছবি **একবার** import করে repo-র `public/seed-assets/adora-2026/`-এ রাখা হয়েছে।
2. `SeedAssetSeeder` প্রতিবার seed-এ bundle থেকে
   `public/uploads/product/seed/<slug>/`-এ কপি করে।
3. DB-তে শুধু `uploads/product/seed/<slug>/<file>` relative path যায় — bundle path নয়।

ফলে `php artisan migrate:fresh --seed` যেকোনো মেশিনে, external drive ছাড়াই, ছবি সহ
সাইট বানায়।

## গঠন

```text
public/seed-assets/adora-2026/
├── README.md            # bundle-এর সংক্ষিপ্ত নোট
├── catalog.json         # ৩০ প্রোডাক্টের manifest
└── images/
    └── <product-slug>/  # ০১-<name>.jpg (max 1400px, JPEG q85)
```

`catalog.json`-এ প্রতি প্রোডাক্টে: `source_id`, `name`, `slug`, `category`,
`product_code`, `purchase_price`, `old_price`, `new_price`, `description`,
`images[]` (`file`, `source`, `bytes`, `width`, `height`)।

## নির্বাচন নিয়ম (deterministic)

- উৎস: `adorazstyl_sahon.sql` — ১,১৯৭ প্রোডাক্ট, ১২টি age category।
- ১২টি category-তে round-robin, lowest product id first; মোট ৩০টি প্রোডাক্ট।
- প্রতিটি প্রোডাক্টে সর্বোচ্চ ৩টি gallery image, শুধু disk-এ থাকা ফাইল।
- বর্তমান বণ্টন: Premature/Newborn/0-3/3-6/6-9/9-12 = ৩টি করে, বাকি ৬টি category =
  ২টি করে।
- মোট ৩০টি ছবি, ~৫.৩ MB — repo-তে রাখার মতো আকার।

## কমান্ড

Rebuild bundle (external drive লাগবে):

```bash
python3 tools/import_adora_2026_assets.py
python3 tools/import_adora_2026_assets.py --limit 30 --max-images 3
```

Seed চালানোর পর ছবি publish:

```bash
php artisan db:seed --class=SeedAssetSeeder
```

`DatabaseSeeder`-এ seeder wired আছে, তাই সাধারণত আলাদা কমান্ড লাগে না:

```bash
php artisan migrate:fresh --seed
```

## আচরণ

- **Idempotent:** destination ফাইলের SHA-256 bundle ফাইলের সমান হলে skip হয়।
  দ্বিতীয় run-এ `30 products, 0 copied, 30 unchanged`।
- **Fail-fast:** bundle file বা `catalog.json` missing হলে `RuntimeException` — চুপচাপ
  skip করে ভাঙা ছবির সাইট বানায় না।
- **User upload রক্ষা:** শুধু mismatch হলেই copy; তাই seed কখনো অকারণে ফাইল rewrite করে না।
- **Portable:** bundle repo-র অংশ; deploy/sale-এ `public/seed-assets/` বাদ দেওয়া যাবে না।
  `public/uploads/product/seed/` regenerate হয়, তাই এটা git-এ না থাকলেও চলে।

## রক্ষণাবেক্ষণ

| কাজ | নিয়ম |
|---|---|
| নতুন ছবি/প্রোডাক্ট যোগ | `tools/import_adora_2026_assets.py` চালিয়ে bundle rebuild, তারপর seeder |
| শুধু uploads সাফ করা | `rm -rf public/uploads/product/seed` তারপর seeder — bundle অক্ষত থাকে |
| Bundle delete | **করবেন না** — এটাই demo ছবির committed source of truth |
| Source drive বদলানো | tool-এর `--sql` / `--source-images` flag ব্যবহার করুন |
| `catalog.json` হাতে এডিট | এড়িয়ে চলুন; deterministic rebuild-ই প্রমাণযোগ্য পথ |

## যাচাই প্রমাণ (২০২৬-১১-১৬)

```text
$ php artisan db:seed --class=SeedAssetSeeder        # run 1
Seed assets: 30 products, 30 copied, 0 unchanged (5248777 bytes).

$ php artisan db:seed --class=SeedAssetSeeder        # run 2
Seed assets: 30 products, 0 copied, 30 unchanged (0 bytes).

$ find public/uploads/product/seed -type f | wc -l
30
```

সম্পর্কিত ফাইল:

- `app/Support/SeedAssets.php`
- `database/seeders/SeedAssetSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tools/import_adora_2026_assets.py`
- `docs/PLAN-reports-analytics-2026-11-16.md` → `SEED-ASSET-BUNDLE`
