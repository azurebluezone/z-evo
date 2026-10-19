# অ্যাডমিন ইনপুট ফরম: কোথায় কী ভিন্ন

**তারিখ:** ২০২৬-১০-১২
**স্কোপ:** `resources/views/backEnd/` — ১৯২টি ভিউ, ১,০১৭টি কন্ট্রোল
(লেবেল বাদ দিয়ে হিসাব করা হয়নি; text ইনপুট ৩১৬টি ছড়িয়ে ১১১টি ফাইলে)
**টুল:** `python3 tools/admin_form_input_audit.py`

বাটন কম্পোনেন্টের আগে যেটা করেছিলাম, ইনপুটের জন্য: প্রথমে পুরো ভাণ্ডার, তারপর অমিল।
নিচের সব সংখ্যা মাপা, অনুমান নয়।

---

## সংক্ষিপ্ত

একটা জিনিস — `<input>` — পুরো অ্যাডমিনে **১১টি ভিন্ন class স্ট্রিং** ব্যবহার হয়।
সবচেয়ে প্রচুর (২৪৩টি) `form-control`। বাকি ৭৩টি ফিল্ড অন্য কিছু ব্যবহার করে।
`<select>`-এর ক্ষেত্রে **১৮টি**।

কিন্তু সংখ্যা সবচেয়ে বড় সমস্যা নয়। আসল সমস্যা: **একই পেজের ভেতরে দুই রীতি।**

---

## ১. পেজ ভেতরে মিশ্র লেবেল রীতি — সবচেয়ে স্পষ্ট সমস্যা

অ্যাডমিনে দুই ধরনের ফিল্ড আছে:

* যেগুলোর `<label class="form-label">` আছে
* যেগুলোর কোনো লেবেল নেই, শুধু `placeholder` — ফিল্ড খালি হলে দেখার কিছু নেই

**৪৬টি** ফাইলে কোনো লেবেলই নেই, শুধু placeholder।
**৩১টি** ফাইলে **একই পেজে দুটোই** আছে:

| ফাইল | লেবেল আছে | শুধু placeholder |
|---|---|---|
| `homepage/settings.blade.php` | ১২ | ১২ |
| `courier/settings.blade.php` | ৯ | ৮ |
| `customer/edit.blade.php` | ১ | ৫ |
| `courier/fraud_checker.blade.php` | ৫ | ৪ |
| `kook/event.blade.php` | ১ | ৫ |
| `campaign/create.blade.php` | ১৯ | ৩ |
| `order/create.blade.php` | ৩ | ৫ |

`homepage/settings.blade.php`-এ বারোটি লেবেল আছে আর বারোটি ফিল্ডের লেবেল নেই — এক
স্ক্রিনের দুই ভাগে দুই রীতি।

### কেন এটা শুধু তোকালো নয়

`placeholder` লেবেলের বিকল্প নয়:

* ফিল্ডে লেখা থাকলে placeholder আর দেখা যায় না
* স্ক্রিন রিডার লেবেল পড়ে, placeholder পড়ে না
* placeholder মুছে গেলে ফিল্ডটি কী চাই তা বোঝা যায় না

---

## ২. একই ধরনের জিনিস, ভিন্ন class

### চেকবক্স: `switch-input` বনাম `form-check-input`

```
form-check-input            89
(no class)                  42
form-check-input rounded-0  28
switch-input                24
```

একই কাজ — টিক বসানো — চার রকম লেখায়। `switch-input` দিয়ে টগল সুইচ বানানো হয়,
আর `form-check-input` দিয়ে সাধারণ চেকবক্স। ফাইল: `homepage/settings.blade.php`
(১২টি `switch-input`)

### সিলেক্ট: `form-control` বনাম `form-select`

```
form-select      102
form-control      21      ← সিলেক্ট বক্সে টেক্সট ইনপুটের class
```

একটা সিলেক্ট ইনপুট `form-control` পেলে সে লেখার বাক্সের মতো দেখায় — dropdown-arrow
ও padding ভিন্ন। মাপা হয়ে **১৭টি** কন্ট্রোলে এই ভুল।

### সাইজ: `form-control-sm` বনাম ছাড়া

```
form-control            243
form-control rounded-0   57
form-control-sm            2
form-select-sm            5
form-select-lg            2
```

একই ফর্মে ছোট ও বড় ইনপুট পাশাপাশি — টেবিলের ভেতরে ছোট, বাইরে বড়, এটাই ইচ্ছাকৃত
হতে পারে; কিন্তু `kook/order-edit.blade.php`-এ একই সেকশনে তিনটি ভিন্ন সাইজ।

### `rounded-0`

একটি class ১১১টি ফাইলে। এটি theme-এর নিজস্ব "square corners" রীতি — কিন্তু
`form-control rounded-0` (৫৭টি) বনাম শুধু `form-control` (২৪৩টি) মানে একই ফর্মে কিছু
ফিল্ড বর্গাকার, কিছু গোল। দুটো সচেচচ কেন আলাদা হওয়া উচিত বলার মতো নয়।

---

## ৩. লেবেল-বিহীন কন্ট্রোল: ২১৭টি

টুল প্রতিটি কন্ট্রোলের জন্য `<label for=…>` বা `aria-label` খোঁজে। পায় না:

```
217 control  —  কোনো লেবেল নেই, কোনো aria-label নেই
  25টির নমুনা:
    campaign/create.blade.php:88   <select> product_id
    campaign/edit.blade.php:265    <input>  start_date
    coupon/edit.blade.php:39       <input>  code
    coupon/edit.blade.php:45       <select> type
    coupon/edit.blade.php:54       <input>  value
```

---

## ৪. লেআউট: `form-floating` বনাম `form-group`

```
form-floating ব্যবহারকারী ফাইল : 43
form-group mb-3 ব্যবহারকারী   : 29
```

দুটো ভিন্ন vertical rhythm দেয়। `form-floating`-এ লেভেল উপরে উঠে যায় (Bootstrap 5.2+),
`form-group mb-3`-এ পাশে থাকে। একই অ্যাডমিনে দুটোই।

---

## ৫. বহুল ব্যবহৃত অভ্যাস (এগুলো ঠিক আছে, রেখে দেওয়া উচিত)

| অভ্যাস | সংখ্যা | মন্তব্য |
|---|---|---|
| `form-control` | ২৪৩ | সঠিক — এটাই মূল |
| `@error('x') is-invalid @enderror` | প্রায় সব | সঠিক — এটাই ব্লেডের উপায় |
| `form-select` | ১০২ | সঠিক |
| `form-check-input` | ৮৯ | সঠিক |

---

## কী করা উচিত (সিদ্ধান্তের জন্য তৈরি, কাজ শুরু করা হয়নি)

**প্রস্তাব:** বাটনের মতো একটি শেয়ার্ড ফর্ম কম্পোনেন্ট —

```
<x-admin.input name="title" label="Title" />
<x-admin.select name="type" :options="$types" label="Type" />
<x-admin.switch name="status" label="Active" />
```

যা নিশ্চিত করবে: সব ফিল্ডে লেবেল, এক class vocabulary, এক size, `id`/`name` মিলবে।

**কিন্তু আগে যা ঠিক করতে হবে** (কম্পোনেন্টের আগে, নইলে কম্পোনেন্টটিও ভিন্ন হয়ে যাবে):

1. **`form-control` নাকি `form-control rounded-0`** — কোনটি মান? ২৪৩ বনাম ৫৭।
   theme-এর বাকি অংশ কোনটি ব্যবহার করে সেটাই ঠিক করতে হবে।
2. **ছোট সাইজ কোথায় ব্যবহার হবে** — শুধু টেবিলের ভেতরে?
3. **`switch-input` কি `form-check-input`-এর বদলে দেওয়া যাবে** — নাকি এটাই
   আলাদা একটা ধরন (টগল সুইচ)?
4. **লেবেল-বিহীন ২১৭টি কন্ট্রোলের লেবেল কী হবে** — লেখা বাংলা, ইংরেজি, নাকি
   ডেটাবেসের নাম থেকে?

৪ নম্বর প্রশ্নটি ছাড়া বাকিগুলো উত্তর দেওয়া যায়; সেটি ছাড়া কম্পোনেন্ট বানানো শুরু
করা মানে ২১৭টি লেবেল নিয়ে আমার নিজের ইচ্ছা ঢেলে দেওয়া।

---

## যাচাই

```
$ python3 tools/admin_form_input_audit.py
admin input inventory
control      count  distinct   files   most common
text           316        11     111   form-control  x243
checkbox       235        22     108   form-check-input  x89
select         110        18      59   form-select  x50
...
217 controls with no label and no aria-label
```

টুলটি `@error(...) is-invalid @enderror` আগে থেকেই সরিয়ে নেয় — না হলে text ইনপুটের
"৭৮টি ভিন্ন class" দেখাত, যেটার বেশিরভাগ আসলে সঠিক ব্যবহার।
