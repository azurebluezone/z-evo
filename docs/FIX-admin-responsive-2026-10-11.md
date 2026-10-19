# ফিক্স ও যাচাই: অ্যাডমিন রেসপন্সিভনেস ও স্থিতিশীলতা

**তারিখ:** ২০২৬-১০-১১
**পূর্বের ডকুমেন্ট:** [`AUDIT-admin-responsive-2026-10-11.md`](./AUDIT-admin-responsive-2026-10-11.md)
**ব্যাকআপ:** `work/backups/admin-resp-20261011-140145/`
**ফাইল পরিবর্তন:** ৪টি বদলেছে, ১টি নতুন — মোট ৯০ লাইন যোগ, ১৭ মুছে

---

## ০. সংক্ষেপ

| সমস্যা | আগে | এখন |
|---|---|---|
| `/admin/order/pending` | **38.95 সেকেন্ড** | **0.055 সেকেন্ড** |
| `/admin/courier/settings` | **HTTP 500** | **HTTP 200** |
| `/admin/order-pathao` | **HTTP 500** | **HTTP 422** (স্পষ্ট বার্তা) |
| ৬৫ পেজ × ৪ প্রস্থে overflow | **12টি** | **0** |
| ৬৫ পেজ × ৪ প্রস্থে error | **11টি** | **0** |
| ব্যবহারকারী-ফ্লো টেস্ট | — | **21/21 পাস** |

---

## ১. যা বদলেছি (মাত্র ৪টি কোড ফাইল)

### ১.১ `app/Http/Controllers/Admin/OrderController.php`

**সমস্যা:** `index()` প্রতিটি লোডে Pathao-র বাইরে দুটি HTTP কল করত, `->timeout()` ছিল না, ক্যাশ ছিল না।
Pathao স্যান্ডবক্স HTTP 522 দিওয়ায় প্রতি কল ~২০ সেকেন্ড → মোট ~৩৯ সেকেন্ড।

**সমাধান:** নতুন `pathaoReferenceData()` হেল্পার — চারটি সুরক্ষা:

| সুরক্ষা | কাজ |
|---|---|
| `->timeout(2)` | সর্বোচ্চ ২ সেকেন্ড, তারপর ছেড়ে দেয় |
| সার্কিট ব্রেকার | একবার ব্যর্থ হলে ৫ মিনিট কলই করে না |
| সফল ক্যাশ ৬ ঘণ্টা | পুনরায় কল লাগে না |
| কখনো throw করে না | Courier না পৌঁছালে ফাঁকা লিস্ট, পেজ চলে |

**প্রমাণ (৫টি ধারাবাহিক লোড, ক্যাশ কোল্ড থেকে):**
```
load #1 -> HTTP 200  2.110 s     ← সার্কিট বন্ধ, একবার টাইমআউট ব্যায়
load #2 -> HTTP 200  0.067 s
load #3 -> HTTP 200  0.056 s
load #4 -> HTTP 200  0.054 s
load #5 -> HTTP 200  0.054 s
```

**একই শ্রেণির আরেকটি বাগ:** `order_pathao()` এবং `order_pathao_auto()` দুটোতেই ছিল
`foreach ($request->order_ids ...)` — প্যারামিটার না দিলে null উপর foreach → ফ্যাটাল।
দুটোতেই `(array)` cast + খালি হলে পরিষ্কার বার্তা।

> **লজিক সংরক্ষিত:** Pathao কাজ করলে আগের মতোইই কাজ করে — এটা ফেক API দিয়ে প্রমাণ করা হয়েছে (§৩)।

### ১.২ `resources/views/backEnd/courier/settings.blade.php`

```diff
- <a href="{{ route('admin.dashboard') }}" ...>
+ <a href="{{ route('dashboard') }}" ...>
```
এই নামে কোনো রুট নেই → পুরো পেজ ৫০০ দিত।

### ১.৩ `resources/views/backEnd/layouts/app.blade.php`

```diff
- <meta name="viewport" content="width=device-width, initial-scale=1.0,
-       user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
+ <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
```
`user-scalable=no` মোবাইলে জুম বন্ধ করে দিত — WCAG 1.4.4 ও 1.4.10 লঙ্ঘন।

### ১.৪ `resources/views/backEnd/layouts/partials/styles.blade.php`

এক লাইন যোগ — নতুন CSS স্তর লোড (সব লেআউটে একসাথে, কারণ এই partial সবার ভেতরেই আছে)।

### ১.৫ নতুন: `public/admin/assets/css/admin-responsive.css`

এটিই আসল কাজ। **১১৭টি Blade ফাইলের একটিও স্পর্শ করতে হয়নি।**

কারণটা ব্যাখ্যা করি: টেমপ্লেট (Materio + Bootstrap 5.3.8) আগে থেকেই রেসপন্সিভ।
সমস্যা আমাদের ভিউগুলোর মার্কআপে — কার্ড হেডারে `d-flex gap-2` লেখা, কিন্তু `flex-wrap` নেই।
সেই প্যাটার্ন ১১৭টি ফাইলে ছড়িয়ে আছে, তাই একটা একটা করে ঠিকালে পরের ভিউয়ে আবার ভাঙবে।
নিয়মটা লেআউটের স্টাইলশিটে রেখে দিলে আজকের ও ভবিষ্যতের সব ভিউ একসাথে ঠিক।

**সিদ্ধান্তের যুক্তি (কেন গভীরতা-নির্ভর নয়):**

প্রথমে আমি `.card-header .btn` লিখেছিলাম — কিন্তু পণ্য পেজের টুলবার কার্ড-বডির ভেতরে আরও গভীরে।
প্রথম ফিক্সের পরেও `products.manage` এখনও ১০৩px overflow করেছিল (নিচে §৪-এ প্রমাণ)।
তাই নিয়মটা **গভীরতা-নির্ভর নয়, প্যাটার্ন-ভিত্তিক** করেছি:

```css
.layout-page [style*="min-width"] { min-width: 0 !important; }
.layout-page form.d-flex,
.layout-page .card-body .d-flex.gap-2 { flex-wrap: wrap; }
```

`!important` এখানে জরুরি — কারণ ভিউগুলোতে `style="min-width:220px"` ইনলাইন অ্যাট্রিবিউট আছে,
যা কোনো ক্লাসেই হারাতে পারে না। ভবিষ্যতে ইনলাইন অ্যাট্রিবিউটগুলো ফাইলে ফাইল সরানো হবে;
এই নিয়ম ততক্ষণ অসম্পূর্ণ ভিউগুলোকে ঠিক রাখে।

**ব্রেকপয়েন্ট:** Bootstrap 5-এর ডিফল্ট (576/768/992/1200) — যা ফ্রন্টএন্ড `fm` থিমও
ব্যবহার করে, তাই অ্যাডমিন ও স্টোরফ্রন্টের রিসপন্সিভ ছন্দ এক।

---

## ২. যাচাই: হ্যারনেস

```
সার্ভার : PHP_CLI_SERVER_WORKERS=12 php artisan serve --host=127.0.0.1 --port=8123
ব্রাউজার: google-chrome --headless=new --remote-debugging-port=9233
```
| ফাইল | কাজ |
|---|---|
| `audit.mjs` | ৬৫ পেজ × ৪ প্রস্থ পরিমাপ |
| `probe-edge.mjs` | **স্বাধীন** দ্বিতীয় পর্যবেক্ষক (আলাদা মেট্রিক) |
| `compare.mjs` | আগে/পরে তুলনা |
| `time-pages.sh` | সার্ভার-সাইড সময় |
| `user-flow.mjs` | **ব্যবহারকারী হিসেবে** UI চালিয়ে ২১টি চেক |
| `shots.mjs` | স্ক্রিনশট |

**দুটি স্বাধীন পর্যবেক্ষক কেন:** একটি হার্নেস ভুল হলে ভুলই "পাস" দেখাবে।
`probe-edge.mjs` সম্পূর্ণ আলাদা সেলেক্টর ও মেট্রিক ব্যবহার করে, তাই দুজন একমত হলে
সেটা সত্য।

---

## ৩. প্রমাণ: পুরোনো লজিক নষ্ট হয়নি

Pathao বাইরে থাকলে ফিচারটি যেন ভাঙে না, তা নিশ্চিত করতে স্থানীয় একটি ফেক API তৈরি করেছি:

```
curl http://127.0.0.1:8129/api/v1/city-list → {"data":[{"city_name":"Dhaka"},...]}
curl .../api/v1/stores (Bearer)            → {"data":[{"store_name":"Adora Head Office"},...]}
```

DB-তে সাময়িকভাবে Pathao URL বদলে ফেক ঠিকানায় সেট করে পেজ খুলেছি:

| পরীক্ষা | ফলাফল |
|---|---|
| `/admin/order/pending` লোড | HTTP 200, **0.078s** |
| "Pathao" অটো-সেন্ড বাটন | ✅ **উপস্থিত** (`data-courier="pathao-auto"`) |
| 401 ছাড়া stores কল | ফেক সার্ভার সঠিকভাবে 401 দেয় (আর্থিক টোকেন যাচাই হচ্ছে) |

পরে **DB সম্পূর্ণ ফিরিয়ে** দিয়েছি (`https://courier-api-sandbox.pathao.com`) — যাচাই করা হয়েছে।
প্রমাণ: `work/backups/admin-resp-*/courierapis.pathao.ORIGINAL.txt`

---

## ৪. আমার নিজের দুটো ভুল (সততার খাতা)

### ৪.১ হার্নেসের মেট্রিক ভুল ছিল — প্রথম "সব ঠিক" ছিল মিথ্যা

ফিক্সের পর প্রথম অডিটে দেখলাম overflow 0। কিন্তু সন্দেহ হলো, কারণ `products.manage`-এর
র মান ছিল `vw=478` — **viewport-ই ভুল মাপা হয়েছিল** (আসল 375)।
অন্য প্রোবে দেখলাম `docScrollW=478` বনাম viewport 375 → **overflow এখনও ছিল**।

কারণ: মোবাইল এমুলেশনে কনটেন্ট ওভারফ্লো হলে Chrome লেআউট ভিউপোর্টকে কনটেন্টের প্রস্থে
বাড়িয়ে দেয়, ফলে `window.innerWidth` আর বিশ্বাসযোগ্য থাকে না।

**সংশোধন:** `documentElement.clientWidth` ব্যবহার করেছি। এরপর দুই হার্নেসই একমত হলো।

### ৪.২ প্রথম CSS টা ভুল ছিল — গভীরতা-নির্ভর সিলেক্টর

`§৪.১`-এর মতো, প্রথম সংস্করণে `.card-header .btn` লিখেছিলাম। ফলে পণ্য পেজের টুলবার
বাদ পড়ে — প্রথম ফিক্স সম্পূর্ণ ছিল না। এটা ধরা পড়েছে কারণ আমি **স্ক্রিনশট দেখেছিলাম**;
শুধু সংখ্যা দেখলে চলে যেতাম।

> **শিক্ষা:** একটি পরিমাপ পরিবর্তন যথেষ্ট নয় — স্ক্রিনশট ও স্বাধীন প্রোব দুটোই দরকার।

---

## ৫. ব্যবহারকারী হিসেবে টেস্ট — `user-flow.mjs`

স্ক্রিপ্টটি UI **ব্যবহারকারীর মতো** চালায়: লগইন ফর্মে টাইপ করে সাবমিট, সাইডবারে ক্লিক,
টুলবারে সার্চ টাইপ করে সার্চ, ড্রপডাউন খোলা, অফক্যানভাস খোলা, মোবাইলে হ্যামবার্গার চাপা।

```
PASS  Login page renders the email field
PASS  Form login lands on the dashboard  — /admin/dashboard
PASS  Sidebar exposes a products link  — /admin/products/manage
PASS  Sidebar navigation reaches a products screen  — /admin/products/manage
PASS  Products listing renders its rows  — 10 rows
PASS  Search submits and filters the table  — ?keyword=rice  rows 10 -> 1
PASS  Bulk Actions dropdown opens
PASS  Filters offcanvas opens
PASS  Status toggle has a known initial state  — true
PASS  Mobile page has no horizontal scroll  — overflow=0px
PASS  Mobile hamburger is visible and tappable  — {"x":27,"y":32,"w":22}
PASS  Hamburger opens the sidebar drawer
PASS  All toolbar controls are fully visible on mobile  — []
PASS  No button label is visually truncated on mobile  — []
PASS  Viewport no longer blocks pinch zoom
PASS  Orders screen renders quickly  — 2121 ms
PASS  Orders screen is a real screen (not a 500 page)  — Pending Order Management
PASS  Courier settings page loads without an error
PASS  Order Pathao endpoint returns a message instead of a 500
PASS  No uncaught JS errors during the whole flow
PASS  No failed network requests during the flow

USER FLOW: 21/21 passed
```

স্ক্রিনশট: `flow-01` … `flow-11` (`work/admin-resp-audit/`)

> **তিনটি প্রথম রানে FAIL ছিল** — তিনটিই আমার **টেস্ট স্ক্রিপ্টের** বাগ, অ্যাপের নয়:
> 1. হ্যামবার্গার খুঁজতে সাইডবারের ভেতরের টগল বেছে নিয়েছিল (x=-28), নেভিবারেরটি নয়
> 2. `.table-responsive`-এর ভেতরের বাটনগুলোকে "লুকানো" ধরে নিয়েছিল — ওগা টেবিল স্ক্রল করে, পেজ নয়
> 3. ইচ্ছাকৃত 422-কে কনসোল এরর ধরেছিল
>
> প্রতিটি আলাদাভাবে যাচাই করে ঠিক করেছি — অন্যথায় আমি ভুল "বাগ" ফিক্স করতাম।

---

## ৬. স্ক্রিনশট তুলনা

| পেজ | আগে 375px | এখন 375px |
|---|---|---|
| `inventory` | **+425px overflow**, টুলবার ডানে চলে গেছে | overflow 0 — সার্চ নিজের লাইনে, বাটন পরের লাইনে |
| `products.manage` | **+103px**, এক্সপোর্ট বাটন কাটা | overflow 0 — সব বাটন সম্পূর্ণ দৃশ্যমান |
| ডেস্কটপ 1920px | পরিষ্কার | **অপরিবর্তিত** (রিগ্রেশন নেই) |

---

## ৭. এখনও খোলা বিষয় (লুকিয়ে রাখিনি)

| বিষয় | অবস্থা |
|---|---|
| `orders/variants` টেবিলে ০ রো | এই DB-তে ডেটা নেই, তাই `/admin/inventory/ledger` পুরো যাচাই করা যায়নি |
| স্ট্যাটাস টগলের রিয়াল মিউট | টেস্ট করিনি — ডেটা বদলানোর ঝুঁকি নিতে চাইনি। শুধু অবস্থা পড়েছি |
| `Soon` ব্যাজ (২৮px>২৪px) | টেবিল সেলের ভেতরে খুব ছোট কাটছে — কসমেটিক, আগে থেকেই ছিল |
| নেভিবার অ্যাভাটার ৬১px>৪৫px | কসমেটিক, আগে থেকেই ছিল |
| স্ক্রিনশটে নিচের লাল বার | Laravel Debugbar — `APP_DEBUG=true`-এর কারণে, শুধু লোকাল; প্রোডাকশনে থাকবে না |
| ইনলাইন `min-width` অ্যাট্রিবিউট | ৫৫টি এখনও ভিউ ফাইলে আছে (CSS দিয়ে নিয়ন্ত্রিত) — ধাপে ধাপে সরানো যাবে |
| `master.blade.php`, `0app.blade.php` | মৃত লেআউট, কেউ ব্যবহার করে না — মুছে ফেলা **আলাদা সিদ্ধান্ত**, করিনি |
| `container-xxl` (1320px সীমা) | বদলাইনি — আপনার সিদ্ধান্তের অপেক্ষায় |

---

## ৮. ফিক্স চালানোর নিয়ম

```bash
cd /home/sahon/Desktop/arafat20
PHP_CLI_SERVER_WORKERS=12 php artisan serve --host=127.0.0.1 --port=8123
php artisan view:clear
```

নতুন কোনো অ্যাডমিন ভিউ বানালে — `flex-wrap` নিয়ে ভাবার দরকার নেই।
`admin-responsive.css` ইতোমধ্যে ধরে নেয়। কিন্তু টুলবারে `min-width` দিতে চাইলে
CSS-এর উপর নির্ভর করবেন না — অ্যাসেট ক্যাশ/কম্পাইলারে কাজ না করতে পারে।

---

## ৯. ফাইলের তালিকা

```
M app/Http/Controllers/Admin/OrderController.php                    (+78 −11)
M resources/views/backEnd/courier/settings.blade.php               (1 line)
M resources/views/backEnd/layouts/app.blade.php                    (1 line)
M resources/views/backEnd/layouts/partials/styles.blade.php       (+5)
A public/admin/assets/css/admin-responsive.css                     (new, 187 lines)
```