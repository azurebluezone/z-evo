# Admin product watermark — বাস্তব নকশা

**তারিখ:** 2026-11-01
**রেফারেন্স কনসেপ্ট:** `/home/sahon/Downloads/aa/easy-watermark/` (WordPress plugin, v1.0.11 — GD দিয়ে ফাইলে ওয়াটারমার্ক পোড়ায়)
**আমাদের প্রকল্প:** Laravel 10, `intervention/image ^2.7`, product ছবি যায় `public/uploads/product/`-এ
**স্ট্যাটাস:** ডিজাইন ডকুমেন্ট (এখনো কোনো কোড বদলানো হয়নি)

---

## 1. এক নজরে — আমরা আসলে কী বানাতে চাই

অ্যাডমিন প্রোডাক্ট আপলোড করবে, আর চাইলে (প্রোডাক্ট-ভিত্তিক বা গ্লোবাল সেটিং) ওই প্রোডাক্টের ছবিতে নিজের লোগো/টেক্সট ওয়াটারমার্ক বসাতে পারবে। পজিশন, সাইজ, অপাসিটি, স্কেল — সব অ্যাডমিন থেকে নিয়ন্ত্রণ করবে।

easy-watermark যা করে (এবং আমরা যা শিখলাম):

| easy-watermark | কী করে | আমাদের জন্য প্রাসঙ্গিকতা |
| --- | --- | --- |
| `watermark` post type | কনফিগ JSON → `post_content` | আমাদের কনফিগ DB/config-এ, কিন্তু একই ধারণা |
| `wp_generate_attachment_metadata` hook | WordPress যেসব resize বানায়, প্রতিটায় আলাদা করে বসায় | আমাদের প্রোডাক্টে resize নেই → এই সুবিধা নেই |
| `compute_offset()` | `start/center/end` + px/% → pixel coordinate | **হুবহু পোর্ট করার যোগ্য, এটাই মূল ম্যাথ** |
| `LocalBackupper` | overwrite করার আগে ফাইল কপি | আমরা overwrite করব না — original আলাদা রাখব |
| `_ew_applied_watermarks` | দুইবার বসে যাওয়া আটকায় | আমরা "সবসময় original থেকে render" দিয়ে এটা built-in পাব |

---

## 2. আমাদের বর্তমান বাস্তবতা (কোড থেকে)

| বিষয় | প্রমাণ |
| --- | --- |
| প্রোডাক্ট ছবি সেভ হয় সরাসরি move দিয়ে | `app/Http/Controllers/Admin/ProductController.php:325-332` (`$image->move($uploadPath, $name)`) |
| আপডেটেও একই পথ | `ProductController.php:688-698` |
| DB-তে শুধু relative path | `productimages.image` = `uploads/product/{time}-{original}` (`app/Models/Productimage.php`) |
| resize/thumbnail generation **নেই** | `ProductController.php`-এ `Image::make` ব্যবহার হয়নি; brand/category/customer-এ আছে, product-এ নেই |
| ছবি দেখানোর সময় path জোড়া হয় | `resources/views/frontend/pages/product-details.blade.php:18` |
| একই ফাইল সব জায়গায় দেখায় | card, list, detail — সব একই `uploads/product/...` |
| `public/uploads/product/` এখন কার্যত খালি | `du -sh` → 4.0K (তাই বাস্তব ছবি দিয়ে যাচাইয়ের জন্য আসল প্রোডাক্ট ইমেজ লাগবে) |

**এর মানে:** WordPress-এ WordPress নিজেই ৪-৫টা size বানিয়ে দেয়, তাই easy-watermark প্রতিটা size-এ আলাদা করে বসাতে পারে। আমাদের এখানে **একটাই ফাইল** — সেটাই সব জায়গায় CSS দিয়ে ছোট-বড় হয়। এই একটা পার্থক্যই পুরো ডিজাইন বদলে দেয় (নিচে ৩ ও ৫ নম্বর অংশ)।

---

## 3. "কন্ট্রোলার + মডেল" ভাবনাটা কোথায় ভাঙে

তুমি যেটা ভেবেছ → বাস্তবে যা লাগবে:

| ভাবনা | বাস্তবতা |
| --- | --- |
| মডেলে `size`, `position` কলাম বসিয়ে দিলাম | মডেল হলো ফ্যাক্ট শিট; **পিক্সেলের কাজ** মডেলে নয় — ছবি খোলা, মেমরি, ফন্ট, ব্যাকআপ, ব্যর্থতা — এসব সার্ভিস/জবের কাজ |
| কন্ট্রোলার থেকে `$image->watermark()` ডাকলাম | আপলোড request-এর ভিতরে ৫MB ছবি + GD decode = timeout/memory; আপলোড `201` ফেরত দেওয়ার আগে ওয়াটারমার্ক শেষ হওয়া **উচিত নয়** |
| পজিশন `px`-এ রাখলাম | একই ফাইল ৩০০px কার্ডে আর ১২০০px ডিটেইলে দেখায়; **px পজিশন দুই জায়গায় দুই রকম দেখাবে** → `%` ছাড়া উপায় নেই |
| একবার বসিয়ে দিলাম, হয়ে গেল | অ্যাডমিন পরে পজিশন বদলাবে → **দুইবার পোড়ালে দুই ওয়াটারমার্ক** জমে। সমাধান: original রেখে প্রতিবার original থেকে render |
| ছবি বদলে দিলাম | original হারালে ফেরানোর উপায় নেই; আর `image` কলামে যেটা আছে সেটাই "আসল" — overwrite করলে অডিট/rollback শূন্য |
| সাইজ মডেলে রাখলাম | "সাইজ" আসলে **দুটো আলাদা জিনিস**: (ক) ওয়াটারমার্ক কত বড় হবে (image-এর তুলনায় %), (খ) আউটপুট ফাইল কত বড় হবে — এদের গুলিয়ে ফেললেই বাগ |

---

## 4. প্রস্তাবিত আর্কিটেকচার

```
[Admin form]
    │  image[] + watermark toggle + alignment + offset% + opacity + scale%
    ▼
StoreProductRequest (validation)          ← size/type/alignment whitelist
    │
    ▼
ProductController::store/update
    │  file move → public/uploads/product/ (derivative)
    │  original copy → storage/app/watermark-originals/ (private, no web access)
    │  DB: productimages { image, original_path, watermark_profile_id }
    ▼
ApplyWatermarkJob (queue)                  ← request-এর বাইরে, timeout/memory আলাদা
    │
    ▼
WatermarkService::apply(Productimage $img)
    ├── WatermarkProfile (config/DB): type, logo_id/text, alignment, offset%, scale%, opacity
    ├── WatermarkPosition::offset()  ← pure function, unit test করা যায়
    └── Intervention Image: open(original) → insert/ text → save(derivative, 85)
```

**নিয়ম একটাই, আর সেটাই সবচেয়ে গুরুত্বপূর্ণ:**
> Original কখনো ছোঁয়া যাবে না। যা-ই পোড়াতে হবে, সেটা original-এর **কপিতে** হবে।

এতে ফ্রি-তে যা পাওয়া যায়:
- দ্বিতীয়বার ওয়াটারমার্ক বসালে জমে না (stack হয় না)
- অ্যাডমিন পজিশন বদলালে শুধু re-render
- rollback = derivative মুছে original থেকে আবার render (ব্যাকআপ সিস্টেম লাগে না)
- easy-watermark-এর `_ew_applied_watermarks` + `LocalBackupper` — দুটোই অপ্রয়োজনীয় হয়ে যায়

---

## 5. সাইজ ম্যাথ — ওয়াটারমার্ক কত বড় হবে

easy-watermark-এর `AttachmentProcessorGD.php:395-430` থেকে নেওয়া ধারণা, আমাদের ফাইলে একটি মাত্র আউটপুট:

| mode | সূত্র | কখন |
| --- | --- | --- |
| `none` | original size | ছোট লোগো, হাত দিয়ে বসানো |
| `fit_to_width` | `scale = img_w / wm_w`; `new_w = img_w × pct`; `new_h = wm_h × scale × pct` | ওয়াটারমার্ক পুরো প্রস্থ জুড়বে না, অনুপাতে বড় হবে |
| `fit_to_height` | `scale = img_h / wm_h`; `new_h = img_h × pct`; `new_w = wm_w × scale × pct` | লম্বা ছবি |
| `contain` | যেটা constrain করবে সেটা বেছে `fit_to_*`, তারপর 100% | লোগো ছবির ভিতরে পুরোটা থাকবে |
| `cover` | বিপরীত axis ধরে `fit_to_*` | লোগো পুরো ছবি ঢাকবে (কropa হবে) |

**সিদ্ধান্ত:** আমাদের প্রোডাক্টে একটাই ফাইল, তাই `fit_to_width` + `scale%` (৫–৩০%) সবচেয়ে নিরাপদ ডিফল্ট। `cover` দিলে ছোট ছবিতে লোগো ছবিকে ঢেকে দেবে — প্রোডাক্ট ছবির জন্য বিপদ।

`scale_down_only` ধারণাটাও রাখা দরকার: ছবি লোগোর চেয়ে ছোট হলে লোগো আর বড় করব না (উপসর্গ blur)।

---

## 6. পজিশন ম্যাথ — হুবহু যে সূত্রটা বসবে

easy-watermark-এর `AttachmentProcessor.php:136-184` এর পোর্ট:

```
axis(alignment, 'x') → alignment-এ 'left' থাকলে start, 'right' থাকলে end, নইলে center
axis(alignment, 'y') → 'top' → start, 'bottom' → end, নইলে center

offset(position, value, unit, image_dim, wm_dim):
    value = unit == '%' ? round(value/100 × image_dim) : value
    start  → value
    center → (image_dim − wm_dim)/2 + value
    end    → image_dim − wm_dim − value
```

তিনটা অপরিহার্য বৈশিষ্ট্য (এগুলো না বুঝলে UI ভুল দেখাবে):

1. `%`-এর base হলো **ছবির** dimension, ওয়াটারমার্কের নয়।
2. `start`-এ `+offset`, `end`-এ `−offset` — offset সবসময় **ভিতরের দিকে**, বাইরে নয়।
3. `end`-এ `image_dim − wm_dim` বিয়োগ হয়, তাই ওয়াটারমার্কের কিনারা ছবির কিনারায় ঠেকে; ২০px offset মানে কিনারার ভিতরে ২০px ফাঁক।

### Worked example

ছবি `1200 × 1200`, লোগো `240 × 80`, alignment `bottom-right`, offset `x=20px, y=20px`:

```
x: end  → 1200 − 240 − 20 = 940
y: end  → 1200 −  80 − 20 = 1100
লোগো পড়বে x 940…1180, y 1100…1180  → ডান-নিচে ২০px ফাঁক ✔
```

একই জিনিস `%`-এ (x=5%, y=5%):

```
x: 5% of 1200 = 60 → 1200 − 240 − 60 = 900
y: 5% of 1200 = 60 → 1200 −  80 − 60 = 1060
```

এখন ছবিটা যদি ১২০০px আর কার্ডে ৩০০px-এ দেখানো হয়, `%` ব্যবহারের কারণে ওয়াটারমার্ক **আপেক্ষিকভাবে একই জায়গায়** থাকবে। `px` হলে ৩০০px-এ ২০px অনেক বড় ফাঁক দেখাবে — এটাই আসল কারণ `%` বাধ্যতামূলক।

### কোডে কোথায় থাকবে

`app/Support/Watermark/WatermarkPosition.php` — শুধু ম্যাথ, কোনো DB/HTTP নয়:

```php
final class WatermarkPosition
{
    public static function axis(string $alignment, string $axis): string
    {
        [$start, $end] = $axis === 'x' ? ['left', 'right'] : ['top', 'bottom'];

        if (str_contains($alignment, $start)) return 'start';
        if (str_contains($alignment, $end))   return 'end';
        return 'center';
    }

    public static function offset(string $pos, float $value, string $unit, int $imageDim, int $wmDim): int
    {
        $v = $unit === '%' ? (int) round($value / 100 * $imageDim) : (int) round($value);

        return match ($pos) {
            'start'  => $v,
            'center' => (int) (($imageDim - $wmDim) / 2 + $v),
            'end'    => $imageDim - $wmDim - $v,
        };
    }
}
```

এটা pure function — ছবি ছাড়াই টেস্ট করা যায়, আর এটাই একমাত্র অংশ যেখানে "ভুল হলে ওয়াটারমার্ক অর্ধেক কাটা পড়ে" ধরনের বাগ হয়।

---

## 7. টেক্সট ওয়াটারমার্কের ফাঁদ (বাংলায় বিশেষভাবে)

easy-watermark `imagettftext` + `imagettfbbox` ব্যবহার করে, আর বেসলাইন ঠিক করতে `delta_x/delta_y` বিয়োগ করে (`AttachmentProcessorGD.php:527-536`, `620-644`)। GD-র origin baseline, top-left নয় — এই কারণেই delta দরকার।

আমাদের ক্ষেত্রে দুটো বাড়তি বিপদ:

1. **বাংলা লেখা GD দিয়ে ভাঙবে।** GD complex text shaping (conjunct, matra reordering) জানে না। "স্বপ্ন" লিখলে অক্ষর আলাদা হয়ে যাবে। সমাধান: বাংলা টেক্সট ওয়াটারমার্ক = আগে থেকে রেন্ডার করা PNG লোগো, অথবা Imagick + Pango (`imagick` + `pango` delegate)।
2. **ফন্ট path** `storage/app/fonts/`-এ রাখতে হবে এবং `resource_path`/`storage_path` দিয়ে resolve করতে হবে — public path নয়।
3. **টেক্সট সাইজ** px নয়, point; আউটপুট ছবির প্রস্থের সাপেক্ষে হিসাব করা ভালো: `fontSize = imageWidth × ratio`।

**সিদ্ধান্ত:** v1-এ শুধু **image watermark (PNG লোগো, alpha)** সাপোর্ট করব। টেক্সট watermark পরের ধাপে, তাও Imagick-এ। এতে ৭০% জটিলতা কমে, আর প্রোডাক্ট ছবির জন্য লোগোই বেশি দরকার।

---

## 8. admin UI — যেটুকু লাগবে, তার বেশি নয়

| কন্ট্রোল | টাইপ | নোট |
| --- | --- | --- |
| Enable watermark | switch | per product override; default গ্লোবাল সেটিং থেকে |
| Alignment | 3×3 radio grid | `top-left … bottom-right` — easy-watermark-এর মতোই ৯টা ভ্যালু |
| Offset X / Y | number + unit dropdown (`%`/`px`) | default `%`, 3% |
| Scale | number % (5–50) | `fit_to_width` এর সাথে |
| Opacity | range 0–100 | alpha PNG-তে কার্যত উপেক্ষিত (নিচে ১০ নম্বর) |
| Logo | media picker (attachment id) | একবার আপলোড, সব প্রোডাক্টে reuse |
| Preview | বাটন → AJAX route | **সবসময় টেম্প কপিতে render**, original-এ নয় |

Preview route (`POST /admin/watermark/preview`): একটি ছোট (max 800px) টেম্প ইমেজে একই `WatermarkService` চালিয়ে `image/jpeg` response। easy-watermark এভাবে করে (`Watermark/Preview.php` + `Ajax` autosave `_ew_tmp_params`) — ধারণাটা ভালো, শুধু আমাদের টেম্প ফাইল `/tmp`-এ রেখে request শেষে মুছে দিতে হবে।

---

## 9. আপলোড flow — ধাপে ধাপে

1. `StoreProductRequest` validate: `image.*` → `image|mimes:jpeg,png,jpg,webp|max:4096`, watermark ফিল্ড whitelist (`alignment` ∈ ৯টা, `scale` 1–100, `opacity` 0–100, `offset.unit` ∈ `px,%`)।
2. ফাইল move → `public/uploads/product/{time}-{name}` (বর্তমান আচরণ অপরিবর্তিত)।
3. একই ফাইল **কপি** → `storage/app/watermark-originals/{hash}.{ext}` (private, `public/`-এর বাইরে)।
4. `productimages` row: `image` (public path), `original_path`, `watermark_profile_id` (nullable), `watermarked_at` (nullable)।
5. ওয়াটারমার্ক চালু থাকলে `ApplyWatermarkJob::dispatch($productimage->id)->afterCommit()` — আপলোড response দ্রুত ফেরে, ছবি কিছুক্ষণ পরে watermarked হয় (UI-তে "processing" ব্যাজ)।
6. Job: original → decode → `->orientate()` (EXIF!) → resize watermark (৫ নম্বর) → `insert($logo, $x, $y, 'top-left', $opacity)` → `save($publicPath, 85)` → `watermarked_at = now()`।
7. ব্যর্থ হলে: `failed()`-এ `watermarked_at = null`, log, অ্যাডমিন নোটিফিকেশন; **original অক্ষত**, তাই আবার চালানো নিরাপদ (idempotent)।

`Image::make` (Intervention v2) — `->insert($watermarkPath, $x, $y, 'top-left', $opacity)` সিগনেচার ব্যবহার করা যাবে; `$x/$y` আসবে `WatermarkPosition` থেকে।

---

## 10. অপারেশন ও পারফরম্যান্স — যেগুলো ভুলে গেলে প্রোডাকশনে ফাঁসবে

| ঝুঁকি | কেন | করণীয় |
| --- | --- | --- |
| **Memory** | GD truecolor ≈ 4 bytes/px; ৪০০০×৪০০০ ছবি = ৬৪MB, কপি নিলে ১২৮MB+ | Job-এ `memory_limit=512M`, অথবা ৩০০০px-এর বড় ছবিতে Imagick |
| **Timeout** | ছবি প্রতি ০.৫–২s, bulk upload-এ ২০টা ছবি | queue-তে, `timeout=120`; request-এ কখনো নয় |
| **EXIF orientation** | ফোনের ছবি ঘোরানো থাকলে ওয়াটারমার্ক ভুল দিকে বসে | `->orientate()` সর্বদা |
| **Alpha PNG ও opacity** | alpha থাকলে opacity param কাজ করে না (easy-watermark-ও এটা মেনে `imagecopy` ব্যবহার করে) | UI-তে opacity disable + টুলটিপ, অথবা alpha-কে pre-multiply করে ফেলো |
| **Animated GIF/WebP** | Intervention v2 প্রথম frame-এ flatten করে | v1-এ GIF আপলোডে ওয়াটারমার্ক skip + লগ |
| **Cache/CDN** | একই filename, নতুন বাইটস = পুরনো ক্যাশ | filename-এ `-wm{version}` অথবা query `?v={watermarked_at}` |
| **Deletion** | `ProductController.php:792-799` ফাইল মুছে, original রয়ে যাবে = orphan | delete-এ original-ও মুছতে হবে |
| **Bulk / existing** | ১০০০+ পুরনো প্রোডাক্ট | `php artisan watermark:backfill --chunk=50`, dry-run ফ্ল্যাগ, progress bar |
| **S3/remote disk** | `public/uploads` লোকাল; ভবিষ্যতে S3 হলে `move()` ভাঙবে | Service-এ `Storage` facade ব্যবহার, path hardcode নয় |
| **Permission** | keu update করতে পারলে watermark বদলাতে পারবে | spatie permission-এ `apply_watermark` gate + Policy |

---

## 11. সিদ্ধান্ত ম্যাট্রিক্স (কেন এটাই বেছে নিলাম)

| প্রশ্ন | অপশন | রায় |
| --- | --- | --- |
| original overwrite করব? | A: হ্যাঁ (easy-watermark-এর মতো) / B: না, derivative | **B** — overwrite মানে rollback নেই, আর দ্বিতীয়বার বসালে জমে |
| আউটপুট কয়টা ফাইল? | A: এক / B: thumb+card+detail তিনটা | **A (v1)** — এখন ফ্রন্টএন্ড একটাই ফাইল চায়; B পরের ধাপে (তখন প্রতিটা size-এ `%` জ্যামিতি এমনিতেই কাজ করবে) |
| পজিশন একক? | px / % | **% ডিফল্ট**, px অপশনাল (নির্দিষ্ট ডিজাইনের জন্য) |
| টেক্সট watermark? | GD / Imagick / বাদ | **v1-এ বাদ**, শুধু PNG লোগো |
| কখন বসবে? | request-এ / queue-তে | **queue** |
| কনফিগ কোথায়? | DB টেবিল / `config/watermark.php` | **config ডিফল্ট + per-product DB override** |

---

## 12. টেস্ট প্ল্যান (প্রমাণ ছাড়া "হয়ে গেছে" নয়)

1. **Unit — ম্যাথ:** `WatermarkPosition` এর জন্য ৯ alignment × `{px, %}` × `{start, center, end}` কেস; expected মান হাতে হিসাব করা (উপরের worked example সহ)। এটাই সবচেয়ে সস্তা এবং সবচেয়ে দামি টেস্ট।
2. **Unit — boundary:** offset এত বড় যে ওয়াটারমার্ক ছবির বাইরে চলে যায় → clamp করা উচিত (`max(0, min(...))`) নাকি error? সিদ্ধান্ত নিতে হবে; v1-এ clamp।
3. **Feature — upload:** `Storage::fake` + `UploadedFile::fake()->image('p.jpg', 1200, 1200)` → assert job dispatch, `original_path` ফাইল আছে, DB row সঠিক।
4. **Feature — apply:** আসল ছবি + আসল লোগো দিয়ে job চালিয়ে output-এর dimension + নির্দিষ্ট pixel-এ রঙ যাচাই (`bottom-right`-এ offset-এর ভিতরে লোগোর রঙ আছে, কিনারার ২০px ফাঁকা)।
5. **Visual — বাস্তব:** একই ফাইল ৩০০px, ৬০০px, ১২০০px-এ রেন্ডার করে চোখে দেখা — `%` পজিশন ঠিক থাকলে তিনটাতেই একই অনুপাতে বসবে।
6. **Regression:** watermark বন্ধ প্রোডাক্টের ফাইল byte-identical থাকবে (hash তুলনা)।
7. **Rollback:** পজিশন বদলে re-run → একটাই ওয়াটারমার্ক, দুটো নয়।

---

## 13. এখনো যা নেই / পরের ধাপ

- **আসল ইনপুট নেই:** `public/uploads/product/` খালি, তাই লাইভ যাচাইয়ের জন্য একটি আসল প্রোডাক্ট ছবি + লোগো PNG দরকার। ওটা ছাড়া "কাজ করছে" বলা যাবে না — সর্বোচ্চ `NOT_TESTED`।
- **কোড লেখা হয়নি:** এই ডকুমেন্ট শুধু নকশা। অনুমোদন পেলে ক্রম: migration → `WatermarkPosition` + টেস্ট → `WatermarkService` → Job → ProductController hook → admin UI → preview route → backfill command।
- **সিদ্ধান্ত দরকার (তোমার):** (ক) v1-এ শুধু লোগো, নাকি বাংলা টেক্সটও? (খ) watermark চালু হবে গ্লোবাল সেটিং থেকে, নাকি প্রতি প্রোডাক্টে আলাদা? (গ) পুরনো ছবিগুলো একবারে backfill করব, নাকি শুধু নতুন আপলোডে?

---

WATERMARK_DOC_V1
