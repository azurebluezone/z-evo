# সমাধানের প্রমাণ

প্রতিটি দাবির সাথে কমান্ড ও পর্যবেক্ষণ। যেটা দাবি আছে, সেটা নিচে মাপা।

---

## S1 — লক স্ক্রিন

| প্রশ্ন | ফলাফল |
|---|---|
| লক স্ক্রিন কোন verb-এ? | `POST /admin/locked`, `POST /admin/unlocked` |
| মিডলওয়্যার কী? | `Authenticate`, `CheckReffer` — **`LockAccount` বাদ**, `auth` আছে |
| `LockAccount` এখন কী করে? | সরাসরি লক ভিউ ফেরত দেয় (আগে `redirect()->route('locked')` করত, যা এখন নিজের উপর লুপ করাত) |
| navbar/master লিংক | `<a href>` → `POST` ফর্ম, `@csrf` সহ |
| ডেড কোড | সরানো (`return` এর পরের লাইন) |

```php
Route::post('locked', [DashboardController::class, 'locked'])
    ->name('locked')
    ->withoutMiddleware([\App\Http\Middleware\LockAccount::class]);
```

---

## S2 — GET-এ স্টেট পরিবর্তন

```
$ python3 tools/admin_state_changing_gets.py
admin GET routes that write to the database
  none found
```

আগে একই টুল ৭টি রুট দেখাত:

```
admin/order-bulk-destroy        OrderController@bulk_destroy
admin/order-status              OrderController@order_status
admin/order-assign              OrderController@order_assign
admin/products/update-status    ProductController@update_status
admin/products/update-feature   ProductController@update_feature
admin/products/update-deals     ProductController@update_deals
admin/bulk-courier/{slug}       OrderController@bulk_courier
```

যারা POST-এ গেছে তাদের কাস্টমারও বদলেছে:

```
$ python3 tools/admin_post_callers.py
admin POST routes in the table : 215
blade forms pointing at them  : 201
every form that posts to an admin route carries @csrf
```

লেআউটে একবার `$.ajaxSetup` বসানো হয়েছে, তাই নতুন AJAX যোগ করলে টোকেন নিজে থেকেই লাগে:

```js
jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': meta.content, ... } });
```

---

## K1 — অর্ডার বোর্ড

ব্রাউজারে (লগইন করা অ্যাডমিন, অস্থায়ী অর্ডার #7 বানিয়ে):

```
title              : Order Board
jKanban            : function
boards             : 6      (Pending, Unpaid, On The Way, On Hold, Completed, Cancelled)
cards              : 1      (ZZ-PROOF / ZZ-BOARD-1)
data-eid           : 7
data-column-from   : 1
console errors     : []
```

ড্র্যাগ → `POST /admin/order/board/move`:

```
order #7  Pending(1) → On The Way(3)
  order_status      : 1 → 3
  product 1 stock   : 108 → 106      (qty 2)
  inventory_movements: 1 row (ON_THE_WAY_DEDUCT)
```

পুনরাবৃত্তি ও খারাপ ইনপুট:

```
dropOntoSameColumn : 200 {"status":"success","message":"No change"}
  → stock এখনও 106, ledger এখনও 1 row      (স্টক দ্বিতীয়বার কমেনি)
invalidStatus      : 422 "The selected status id is invalid."
invalidOrder       : 422 "The selected order id is invalid."
```

বোর্ড ড্র্যাগের সেভ **একই** ফাংশনে যায় যা তালিকার dropdown ব্যবহার করে — `OrderController::applyOrderStatusStock()`। ২৫০ লাইনের স্টক-হিসাব একবারই আছে, দুই জায়গায় নয়।

যাচাই: ব্রাউজারে ডিবাগ → ব্রাউজারে `POST /admin/order/board/move` → MySQL।

---

## P1 — প্রোডাক্ট গ্রিডে ইনলাইন দাম/স্টক

ব্রাউজারে, প্রোডাক্ট #11-এর দাম ক্লিক করে বদলে:

```
inlineScriptLoaded : true
priceCells         : 20   stockCells: 20
click → input open : true
Enter → save       : "৳320" → "৳777.25"  →  DB: new_price = 777
```

**প্রথমবার একটি সত্যিকারের বাগ ধরা পড়েছিল:** `new_price` কলামটি `INT`, কিন্তু ইনপুটে `step="0.01"` ছিল। টাইপ করা `777.25` স্ক্রিনে `777.25` দেখাল, MySQL রাউন্ড করে `777` সেভ করল — পরের রিফ্রেশে `777` দেখাত, মানে স্ক্রিন বান্ডিল থাকত। সমাধান: ইনপুট `step="1"`, আর কন্ট্রোলার সেভের পরে `$product->refresh()` করে **ডাটাবেস থেকে পড়া** মান ফেরত দেয়।

পরে যাচাই:

```
Enter "999"          → "৳999"      DB: new_price = 999
POST price = -5      → 422         (ঋণাত্মক দাম প্রত্যাখ্যাত)
POST price = 99999999→ 422         (অতিরিক্ত বড় প্রত্যাখ্যাত)
```

টেস্টের পরিবর্তন ফেরানো হয়েছে (প্রোডাক্ট #11 → 320)।

---

## F1 — অর্ডার ফর্মে BS Stepper

ব্রাউজারে `/admin/order/create`:

```
start : stepper-1 / Products
next1 : stepper-2 / Customer
next2 : stepper-3 / Payment
prev1 : stepper-2 / Customer
```

প্রথম চেষ্টায় কাজ করেনি। **তিনটি আলাদা কারণ**, প্রতিটিই লাইব্রেরির বান্ডিল পড়ে বের করা:

| সমস্যা | কী ঘটেছিল | সমাধান |
|---|---|---|
| Pane লুকানো হচ্ছিল না | এই টেমপ্লেটের bs-stepper CSS লুকায় `.bs-stepper .content` — আমি `.step-pane` দিয়েছিলাম, তাই তিনটি ধাপ একসাথে দেখা যাচ্ছিল | pane ক্লাস → `content` |
| Next বোতাম কাজ করছিল না | এই বিল্ড শুধু হেডার-লিংকে ক্লিক বাঁধে; `data-bs-stepper-next` বোতামগুলো কেউ বাঁধে না | `fm-order-stepper.js`-এ ডেলিগেট করে `stepper.next()` |
| Instance তৈরি হচ্ছিল না | বান্ডিল নিজে থেকে initialize হয় না, শুধু ক্লাস export করে | `new Stepper(el, {linear:true})` |
| বোতাম কাজ করত কিন্তু pane বদলাত না | বোতামগুলো `.bs-stepper` ডিভের **বাইরে** পড়ে গিয়েছিল | `tools/order_form_stepper_nav.py` সেগুলো ভেতরে সরায় |

প্রমাণ যে ফর্মের আচরণ বদলায়নি:

```
$ python3 tools/verify_order_form_fields.py .../create.blade.php.bak .../create.blade.php
fields before: 4   after: 4
identical: the form still posts the same named fields

$ python3 tools/verify_order_form_fields.py .../edit.blade.php.bak .../edit.blade.php
fields before: 5   after: 5
identical: the form still posts the same named fields
```

---

## যা একসাথে চালিয়ে যাচাই

```
373 blade files compiled, 0 failures
admin GET routes that write to the database: none found
every form that posts to an admin route carries @csrf
every grid endpoint sits behind the same permission as its screen
every endpoint with data returns well-formed rows
distinct class strings: 176 / identical: 157 / MISMATCHED: 0
```

টেস্টের জন্য বানানো অস্থায়ী অ্যাডমিন (৬০টি permission সহ) কাজ শেষে মুছে দেওয়া হয়েছে:
`users = 3` (আগের মতো), `orders = 0`, `coupons = 0`, প্রোডাক্ট #11-এর স্টক ও দাম আগের মতো।

---

## রিগ্রেশন গার্ড

| ফাইল | কী ঠেকায় |
|---|---|
| `tools/admin_state_changing_gets.py` | GET রুটে কোনো লেখা |
| `tools/admin_post_callers.py` | `@csrf` ছাড়া POST ফর্ম |
| `tools/admin_grid_permissions.py` | permission allow-list-এর বাইরে থাকা endpoint |
| `tools/verify_grid_endpoints.php` | ভাঙা JSON envelope |
| `tools/verify_grid_endpoints_empty.php` | খালি টেবিলের endpoint প্রমাণ |
| `tools/verify_grid_pages.php` | গ্রিড না লাগানো লিস্ট স্ক্রিন |
| `tools/verify_order_form_fields.py` | ফর্মে হারিয়ে যাওয়া ফিল্ড |
| `tools/verify_admin_buttons.php` | বাটনের ক্লাস বদলে যাওয়া |
| `tools/compile_all_blades.php` | blade সিনট্যাক্স |
| `tools/admin_button_snapshot.py` | বাটনের "আগের" অবস্থা হারানো |


---

# পরবর্তী সেশনে ধরা পড়া বাগগুলো

উপরের যাচাই লেখার পরেই যে অংশটি আমার প্রশ্নের বিপরীতে লেখা হয়েছিল, সেটা পড়ে ধরা পড়ল যে আমি রুট বদলেছি কিন্তু **কলার বদলানোর যাচাই করিনি**। পরীক্ষা করে দেখা গেল সমস্যা আমার আনা একটির চেয়ে বড়।

## B1 — `order/index.blade.php`-এ কোনো `<script>` ট্যাগই ছিল না

ফাইলটিতে `@push('script')` ছিল, কিন্তু খোলার ট্যাগ নয় — শুধু একটা `</script>`। ব্রাউজার unmatched closing ট্যাগ বাদ দিয়ে পুরো জেসকে **পেজের টেক্সট হিসেবে** ছাড়ত, তাই:

* `order_assign` সাবমিট হ্যান্ডলার — কাজ করত না
* `order_status_form` সাবমিড — কাজ করত না
* বাল্ক ডিলিট — কাজ করত না
* কুরিয়ার বাটন — কাজ করত না
* `checkall` সিলেক্ট-অল — কাজ করত না

এটি HEAD-এ ছিল, আমার লেখা নয়।

পাশে আরেকটি সিনট্যাক্স সমস্যা ছিল: `$(document).ready(function(){` শেষে `})` দিয়ে বন্ধ হচ্ছিল (যা কখনোই `ready()` বন্ধ করে না) এবং একটি `})();` ছিল যার কোনো শুরুই নেই।

### প্রমাণ (ব্রাউজারে, লগইন করে)

```
হ্যান্ডলার বাঁধা আছে কি না (jQuery এর event store থেকে):
  submit : ["form#order_assign", "form#order_status_form"]
  click  : [".order_delete", ".multi_order_print", ".individual-courier-btn, .multi_order_courier"]
  change : [".checkall"]
```

এবার প্রতিটি আসলে কাজ করে, XHR ধরে দেখা হয়েছে:

```
select-all         : checkall ক্লিক → সারি নির্বাচিত 1
order assign       : POST /admin/order-assign
                     → DB: order 9 user_id = 7   ✅
order status       : POST (Pending → On Hold)
                     → DB: order 9 status = 4     ✅
bulk courier       : POST /admin/bulk-courier/steadfast   ✅
bulk delete        : POST /admin/order-bulk-destroy
                     → DB: orders 0, order_details 0    ✅
```

## B2 — `order_assign`-এর ajax এখনও GET পাঠাচ্ছিল

রুট POST করার সময় ফর্মের `method="POST"` বদলেছি, কিন্তু সেই ফর্ম জেস দিয়ে সাবমিট হয়, তাই আসল verb ঠিক করতে হয় `type:'GET'` → `type:'POST'` — যা করিনি। ফলে বাটন 405 দিত।

## B3 — `.checkall` হেডারে ছিল না

সারির প্রতিটিতে `input.checkbox` ছিল, কিন্তু হেডারে select-all ছিল না — JS শুধু `.checkall` শুনছিল। এখন শেয়ার্ড গ্রিড পার্শিয়ালে `'selectable' => true` দিলে প্রথম হেডার ঘরে বসে।

নতুন ঘর যোগ করা হয়নি — সারির প্রথম `<td>`-তেই চেকবক্স আছে, তাই নতুন `<th>` যোগ করলে হেডার ৯ হতো আর বডি ৮, আর DataTables এক কলাম সরে যেত।

```
headerCells: 8   rowCells: 8   ← মিলছে
```

## নতুন দুটি রিগ্রেশন গার্ড

| ফাইল | কী ঠেকায় |
|---|---|
| `tools/admin_callers_match_verb.py` | রুটের verb আর কলারের verb না মেলা (ব্লেড-সচেতন; হ্যান্ডলার-ভিতরিক `url: url` ও ধরে) |
| `tools/admin_script_tag_audit.py` | `<script>` ট্যাগ ছাড়া ছাড়া JS |

দুটোই প্রমাণিত: বাগ ইচ্ছাকৃতভাবে ঢুকিয়ে দেখে ধরাপড়া, তারপর সরিয়ে দেখা হয়েছে শূন্য।

### দুটো টুল নিজেই যে ভুল করেছিল

দুটোই প্রথমে ভুল জানিয়েছিল, তাই রেকর্ডে রাখা দরকার:

* `admin_callers_match_verb.py` প্রথমে **১৩টি** অমিল দেখিয়েছিল — ১২টি মিথ্যা। `route('admin.attributes.destroy', $a->id)`-এর ভেতরের `>`-এ থেমে গিয়ে `method="POST"` পড়া হয়নি।
* `admin_script_tag_audit.py` প্রথমে **২৪৫টি** সমস্যা দেখিয়েছিল — সবগুলোই টুলের নিজের। slice-এ `>` বাকি থাকায় প্রতিটি ফাইলে "Unexpected token '>'", তারপর blade-strip করায় আরও ভুল।

স্থিরভাবে Blade-টেমপ্লেটড JS বৈধ কি না যাচাই করা যায় না — তাই node-ভিত্তিক যাচাই সরিয়ে দেওয়া হয়েছে, এবং আসল প্রমাণ এখন ব্রাউজার।
