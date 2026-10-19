# ভ্যারিয়েন্ট: এক ক্লিক = পুরো জোড়া

**তারিখ:** ২০২৬-১০-১১
**পূর্বের ডকুমেন্ট:** [`PRODUCT-PHOTOS-commons-2026-10-11.md`](./PRODUCT-PHOTOS-commons-2026-10-11.md)

---

## ১. আপনার কথাটাই সমস্যা ছিল

> *"একটি ভ্যারিয়েন্ট = একটি ইমেজ, একটি দাম, একটি কালার — এটা সবকিছু মিলে একটা জোড়া।
> যখন যে জোড়ায় ক্লিক করবে, তখন ওই জোড়ার সব ডাটা আসবে।"*

আগের পেজে ঠিক উল্টোটা ছিল: **তিনটি আলাদা জায়গায় তিনটি আলাদা উত্তর।**

```
ক্লিক → variant-images   → শুধু ছবি
      → check-stock      → শুধু দাম + স্টক
```
দুটো কল আলাদা আলাদা সময়ে আসত, তাই ক্রেতা এক মুহূর্তের জন্য এমন অবস্থা দেখত:
**ছবি বদলে গেছে, কিন্তু দাম এখনও আগেরটা** — অথবা উল্টো।

---

## ২. সমাধান: একটি endpoint, একটি বান্ডিল

### ২.১ নতুন endpoint

`GET /products/{productId}/variant-detail?attribute_values=[4]`

```json
{
  "found": true,
  "variant": {
    "id": 4,
    "sku": "ZZTEST-LARGE",
    "title": "ZZTEST Large",
    "image": "http://.../uploads/product/fresh-carrots.jpg",
    "price": 780,
    "old_price": null,
    "formatted_price": "Tk 780",
    "stock_quantity": 12,
    "in_stock": true,
    "attributes": [{ "slug": "color", "label": "Forest Green", "value_id": 4 }]
  }
}
```

**একটি কলে ছবি + দাম + স্টক + SKU + অ্যাট্রিবিউট লেবেল — সব একসাথে।**
`attributes` ফিল্ডে নাম ফেরত আসে ("Forest Green"), যাতে শুধু রঙিন চিপ না দেখিয়ে
**লেখায়ও** পছন্দটা বোঝা যায়।

### ২.২ ফ্রন্টএন্ড: `fmVariant` মডিউল (আপনার চাওয়া প্রিফিক্স)

`product-details.blade.php`-এ এখন একটি নেমস্পেসড মডিউল:

```js
var fmVariant = (function () {
    return {
        resolve: resolve,      // ক্লিকের পরে ডাবল
        apply: apply,          // পুরো বান্ডিল এক ধাপে বসাও
        selectedIds: selectedIds,
        request: request
    };
})();
```

| সুরক্ষা | কেন |
|---|---|
| **প্রিফিক্স** `fmVariant.*` | অন্য স্ক্রিপ্টের নামের সাথে সংঘর্ষ হবে না |
| **স্টেল রিকোয়েস্ট ড্রপ** | দ্রুত ক্লিকে পুরনো উত্তর নতুন উত্তরের সাথে মিশে যাবে না |
| **১৮০ms ডিবাউন্স** | সারির উপর দ্রুত ক্লিক করলে শুধু শেষ চয়নার জন্য অপেক্ষা |
| **`apply()` এক ফাংশন** | দাম বদলাবে না ছবি বদলানো ভুলে যাওয়া যাবে না |
| **দুই পুরনো endpoint অপ্রয়োজনীয়** | `checkStock`, `variantImages` — আর কোথাও কল হয় না |

---

## ৩. প্রমাণ: ৯/৯ পাস

```
PASS  Variant pills render  — 2 pill(s), ids [3,4]
PASS  ONE request answers the whole click  — /products/1/variant-detail?attribute_values=%5B%224%22%5D
PASS  Photo changes with the variant  — fm_825a1beb86d5308d.jpg -> fresh-carrots.jpg
PASS  Price changes with the variant  — Tk 499 -> Tk 780
PASS  SKU changes with the variant  — FC-0001 -> ZZTEST-LARGE
PASS  Stock is variant specific  — "In stock (12 left)"
PASS  Choice is named on screen  — note="Large"
PASS  Variant id reaches the form  — hidden variant[] = [4,4]
PASS  No uncaught JS errors

VARIANT BUNDLE: 9/9 passed
```

স্ক্রিনশট: `work/proof-variant-bundle.png` — Tk 780 · SKU ZZTEST-LARGE · In stock (12 left)

---

## ৪. যা বদলেছে

```
M app/Http/Controllers/Frontend/FrontendController.php   (+ ভ্যারিয়েন্টDetail মেথড)
M routes/web.php                                         (+ ১ রুট)
M resources/views/frontend/fm/pages/product-details.blade.php
M tools/variant_test_data.php                            (টেস্ট ডেটার লেবেল সংশোধন)
```

**পুরোনো endpoint মুছিনি** — `variantImages` ও `checkStock` আগের ক্লাসিক থিমে
প্রয়োজন হতে পারে। fm পেজ কেউ আর কল করে না।

---

## ৫. 🔴 একটি বাগ যা সবাই মিস করবে — সার্ভার

নতুন রুট HTTP-তে 404 দিচ্ছিল, যদিও কোড সম্পূর্ণ ঠিক ছিল। কারণ:

```
ss -ltnp | grep 8123   →   pid=4837   (সকাল 7:38 থেকে চলা পুরনো সার্ভার)
```

আমার `pkill -f "artisan serve"` **শেলকেই হত্যা করেছিল, সার্ভার বাঁচেনি**। তাই নতুন
রুট সেই প্রসেসের কাছে ছিল না। ভেতরে `php artisan tinker`-এ একই রিকোয়েস্ট **সঠিকভাবে
২০০** দিয়েছিল — প্রমাণ যে কোড ঠিক ছিল, সার্ভারই পুরনো ছিল।

**পরের বার:** `pkill` করার আগে `ss -ltnp | grep <port>` দিয়ে প্রকৃত PID বের করে
সেটাতেই `kill` করব।

---

## ৬. আমার ভুল, স্বীকার করে

| ভুল | শিক্ষা |
|---|---|
| ৪০ মিনিট "কোড বাগ" খুঁজেছি, আসলে সার্ভার পুরনো ছিল | প্রথমে অবস্থা যাচাই (`ss`), তারপর কোড |
| প্রথমে `--apply` করে ছবি কপি করেছি, DB আপডেট ছিল না | ফাইল কপি হলেই ভিউ পরিবর্তন হয় না |
| টেস্ট ডেটায় `attrs` = অ্যাট্রিবিউটের নাম ("Size") দিয়েছিলাম | লেবেল হতে হয় **ভ্যালুর** নাম ("Small") |
| টেস্টে আইডি `2` হার্ডকোড করেছিলাম | ডেটা বদলালে চেক ভুল দেখায় — পড়ে ঠিক করেছি |

---

## ৭. পরবর্তী ধাপ

| কাজ | অবস্থা |
|---|---|
| **কালার-ভ্যারিয়েন্ট UI** (রঙিন চিপ) | পিল কাজ করে, কিন্তু রঙিন সোয়াচ এখনো নেই — `detail-summary.blade.php`-এ CSS যোগ করতে হবে |
| **ক্যাশ TTL (৩ ঘণ্টা)** | শুরু হয়নি |
| **কুরিয়ার API + ব্যাচ রেসপন্স** | শুরু হয়নি |
| **fm-এ নেই এমন ১০টি পেজ** | শুরু হয়নি |

---

## ৮. চালানোর নিয়ম

```bash
# ভ্যারিয়েন্ট টেস্ট ডেটা
php tools/variant_test_data.php --seed      # বসাও
php tools/variant_test_data.php --remove    # সরাও (শুধু ZZTEST মার্কার সারি মুছে)

# প্রমাণ
node work/variant-proof.mjs                 # ৯টি চেক
```

> **নতুন প্রোডাক্টে ভ্যারিয়েন্ট যোগ করার পর কিছু করতে হবে না** — পেজের স্ক্রিপ্ট
> নিজে থেকেই `variant-detail` কল করে পুরো বান্ডিল দেখাবে। শুধু DB-তে
> `product_variants`-এ `image`, `price_override`, `sku` ও `attributes_json`
> পূরণ থাকতে হবে।