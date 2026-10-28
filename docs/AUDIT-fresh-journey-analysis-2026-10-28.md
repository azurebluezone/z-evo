# Fresh-Mind Analysis Report — Full Customer Journey + Admin Claims

**Date:** 2026-10-28
**Analyst:** manu (Chief Systems Architect)
**Scope:** Re-verify every "solved" claim against real usage: customer click → product page → cart → checkout → phone handling → order → courier events → admin panel → company handoff → product/category/brand/variant management.
**Method:** Live walkthrough on `http://127.0.0.1:8123` + source reading. All test data created during the audit was removed afterwards (orders 6–9, coupon AUDIT50, usage rows, fraud rows, notifications; `A12454.used_count` resynced to 0).
**Rule followed:** NO fixes applied. Findings only.

---

## A. What passed (verified live, no issue)

| # | Journey step | Evidence |
|---|---|---|
| A1 | Homepage → product page → add-to-cart → cart → checkout | All HTTP 200; product id 14 added, cart showed the product, checkout form rendered with name/phone/address/area/payment |
| A2 | Order placement (guest checkout) | POST `/customer/order-save` → 302 → `/customer/order-success/{id}`; order + shipping + payment + order_details rows created |
| A3 | Phone fraud check on checkout | `courier_fraud_checks` row created automatically with `provider=internal`, order's `risk_label`/`risk_source=checkout` set |
| A4 | Order tracking page | `/customer/order-track/result?invoice_id=X&phone=Y` → 200, shows invoice, placed time, Pending status, item, total (890+120−10 discount = ৳1,000 correct) |
| A5 | Admin sees new orders | `/admin/order/all` 200, test order visible; fraud-checker page shows phone rows |
| A6 | Coupon apply → usage recording → details page | `apply-coupon` (with `subtotal`) → order placed → `coupon_usages` row (`source=checkout`, `order_id` linked) → `/admin/coupon/1/usage` showed Used today/total = 1, recent row "Storefront #8 10.00" |
| A7 | Product/Category/Brand/Variant/Attribute admin CRUD | 21 GET pages all 200 |
| A8 | Variant pipeline end to end | Admin form → `product_variants` + `variant_stocks` → frontend pills → `/products/1/variant-detail?attribute_values=[15]` → 200 with real price/stock |
| A9 | Category→products, Brand→products | `/products/category/canvas` lists exactly the 2 DB products; `/products/brand/demo-brand` lists exactly the 10 DB products |
| A10 | Earlier session claims re-checked fresh | stat-grid OFF on settings pages (grid:0 ×3), footer contact fields present (3), homepage renders saved email, SEO partial = 12 x-admin fields on all 3 pages, page/create has 0 broken submit + Quill `full`, banner edit 200, coupon usage page correct |
| A11 | Courier guardrails with no keys | All previews refuse with clear message; webhooks return 403; admin pages honestly show "No API Connect" |

---

## B. INCONSISTENCIES FOUND (not fixed — written down as requested)

### B1. [HIGH] Coupon discount is computed from a CLIENT-CLAIMED subtotal → negative order amounts are possible

- `Frontend/CouponController.php:22` — `$subtotal = (float) $request->input('subtotal', 0);` (client JSON field).
- `Frontend/CustomerController.php:357,397` — `order_save` reads `Session::get('discount')` and writes `amount = (subtotal + shipping) - discount` with **no clamp, no recompute, no `isUsageAvailable()` recheck** (`CouponController.php:37` checks the limit only at apply time).
- **Live proof:** claimed `subtotal=50000` for a 50% coupon while the real cart was ৳890 → server replied `amount:25000` → placed order #9 → `orders.amount = -23100`, `payments.amount = -23100`, `coupon_usages.discount_amount = 25000`.
- Also: `calculateDiscount()` trusts the same fake subtotal, so `min_amount` gating is bypassable (cart ৳50 passing a ৳100 minimum by claiming ৳5000 — reproduced in tinker).
- Impact: any visitor can create negative-amount orders and drain discount value; reports/revenue figures get corrupted.

### B2. [HIGH] Admin status change to id 5 books a courier consignment — but 5 = Cancelled

- `Admin/OrderController.php:903` — `if ($request->status == 5 && $courier != 5)` fires `CourierOrderSender->send($order, 'steadfast')`.
- `database/migrations/2026_10_18_000006_seed_order_statuses.php:67` — `['slug' => 'cancelled', 'id' => 5]`.
- Effect: moving an order to **Cancelled** books (and may bill) a parcel; moving to **Shipped (3)** never sends. The intended hook was presumably "shipped".
- Related: `OrderController.php:1331-1335` bulk-skip guard labels id 5 as "Order already delivered" — same wrong-id root cause.

### B3. [HIGH] Per-order "Send to courier" screen offers Pathao, but the sender only speaks SteadFast protocol

- `Admin/OrderCourierController.php:132-141` accepts `steadfast|pathao`; Pathao button exists in `backEnd/order/edit.blade.php:294`.
- `app/Services/CourierOrderSender.php:161-170,376-412` — only SteadFast (`POST {url}/create_order`, `Api-Key`/`Secret-Key`, SteadFast-only refusal texts). Pathao's real flow lives elsewhere (`OrderController.php:2240+`, bulk only).
- Effect: a Pathao send from the per-order screen can only fail once credentials exist. (Admitted in code comment at `OrderController.php:1399-1402`.)

### B4. [MEDIUM] Order flow does not validate phone format server-side; no OTP at checkout

- `Frontend/CustomerController.php:344-349` — `order_save` validates `phone => required` only (no `regex`/`digits:11`). Frontend has `pattern="0[0-9]+"` (client-side only).
- OTP exists **only** for account registration (`verify`, `resendotp` — `CustomerController.php:139-171`); guest checkout phone is never verified.
- Impact: garbage phone numbers reach courier payloads (`CourierOrderSender` has a BD-phone regex → later hard failure) and fraud checks key off phone.

### B5. [MEDIUM] Courier webhook events are overwritten, not logged; no per-order courier timeline in admin

- `Api/CourierWebhookController.php:186-193` replaces `current_status` + single `raw_payload` JSON; no events table exists (migration `2026_03_16_170000_create_courier_shipments_table.php:11-26`).
- `Admin/OrderController.php:720-733` + `backEnd/order/process.blade.php:219-249` show "Courier history" = *other orders of the same customer*, not this order's status.
- Effect: admin cannot see THIS order's courier event history; delivered→returned transitions unrecoverable.

### B6. [MEDIUM] Webhook status mapping can never produce local `shipped` (3) or `returned` (6)

- `Api/CourierWebhookController.php:272-298` maps courier words to pending/processing/delivered/cancelled only; e.g. `return_dispatched` → `cancelled`.
- Two seeded statuses are unreachable from courier data; returned orders get mislabelled cancelled.

### B7. [MEDIUM] Content-management forms do not use the shared form language (x-admin)

- Registered components: `AppServiceProvider.php:79-82`. Usage found only in `website-setup/footer.blade.php`, `courier/settings.blade.php`, and the SEO partial.
- `product/create.blade.php` (20 raw inputs), `product/edit`, `category/create|edit`, `brand/create|edit`, `variants/create|edit`, `attributes/*`, `subcategory/childcategory/*` — **0 x-admin field components** each, 1–16 raw `form-control` fields per file.
- Product/Category = mixed (componentized SEO block + hand-written rest); Brand uses a **different** SEO partial (`layouts/partials/seo-fields.blade.php` at `brand/create.blade.php:60`) — two SEO form variants in one slice.

### B8. [MEDIUM] 19 admin views still use raw `<input type="submit">` (node-waves breakage — same bug as page/create)

`order/create:236`, `campaign/edit:434`, `roles/create:63`, `roles/edit:72`, `socialmedia/create:107`, `socialmedia/edit:108`, `pixels/create:71`, `pixels/edit:72`, `apiintegration/sms_manage:122`, `tagmanager/create:71`, `tagmanager/edit:72`, `settings/create:120`, `settings/edit:145`, `contact/create:130`, `contact/edit:132`, `homepage/collections/create:109`, `homepage/collections/edit:114`, `permissions/create:46`, `permissions/edit:57`.

### B9. [MEDIUM] Product image `title[]` can never be set at create time

- `Admin/ProductController.php:323,340` reads `$request->title` for `productimages.title`; `product/create.blade.php` has no `name="title"` input (edit form has 3) → every created image gets `title=''`.

### B10. [MEDIUM] `product_variants.discount_price` is a dead field

Written in `variants/create`, `variants/edit.blade.php:154`, `product/create.blade.php:740`, validated/stored at `ProductController.php:530`, `VariantController.php:96,164` — **zero read paths** in frontend, cart, or reports.

### B11. [MEDIUM] `/brands` is a redirect stub

`routes/web.php:725-727` → `redirect()->route('home')` (live 302). Brand directory page does not exist; only `/products/brand/{slug}` works. Any link using `route('brands.page')` drops users on the homepage.

### B12. [LOW] Checkout fraud check swallows every failure silently — `CustomerController.php:425-429` (`catch (\Throwable) { // no-op }`).

### B13. [LOW] `order_process` courier fallback is a silent no-op — no enabled SteadFast row → returns bare `"ok"`, status saved, no courier booked, no message (`OrderController.php:941-943`). Also always tries `steadfast` even if Pathao is the configured courier.

### B14. [LOW] Toast arguments inverted on courier result — `OrderController.php:937,939` `Toastr::error('Courier', $message)` renders the long reason as the title.

### B15. [LOW] Pathao bulk send hardcodes parcel data — `OrderController.php:2330-2351`: `item_description => 'Order from Adora'`, qty 1, weight 0.5, and on location failure silently falls back to the store's city/zone.

### B16. [LOW] Dead/misplaced pieces in product create — `proSize`/`proColor` attach branches unreachable (`ProductController.php:313-319`); `products` table has no `sku` column but `Product::$fillable` contains `'sku'` (`app/Models/Product.php:71`) while `store()` passes `$request->except(...)` straight into `Product::create` (`ProductController.php:269-286`) — a future `name="sku"` field becomes an SQL error; `CategoryController.php:81` forces `home_page=0` on create though the form has no such field.

### B17. [LOW] Duplicate attribute routes — `/admin/attributes` (`admin.attributes.soon`) and `/admin/attributes/manage` (`admin.attributes.index`) hit the same `AttributeController::index` (`routes/web.php:947-948`).

### B18. [LOW] Credential/documentation drift — `README.md:209` and `CreateAdminUserSeeder.php:29` say password `123456`; live check: it **fails** for all 3 seeded users, actual working password is `105585` (per `docs/AUDIT-admin-responsive-2026-10-11.md:46`). Verified live: `123456` → redirect to `/admin/login`; `105585` → `/admin/dashboard` 200. Probe reproducibility is broken for anyone reading the README.

### B19. [LOW] `APP_DEBUG=true` + debugbar renders the full SQL log into every page's HTML (frontend homepage contains 24 debugbar markers; the order-success page embeds full query/backtrace JSON with table/column names and file paths). Harmless locally, but an information-disclosure landmine if this `.env` ships.

### B20. [LOW] Fraud-check rows are keyed by phone only — `courier_fraud_checks` has no `order_id` (live query error confirmed the column does not exist), so a check cannot be tied back to the order that triggered it.

---

## C. Priority suggestion (when you decide to fix)

1. **B1 first** — money-affecting: recompute discount server-side in `order_save` from the real cart, clamp `discount ≤ subtotal + shipping`, recheck `isUsageAvailable()`.
2. **B2/B3** — courier id mapping (`shipped` = 3, not 5) and either implement a real Pathao sender on the per-order screen or remove the button.
3. **B4** — server-side BD phone regex at checkout; OTP is a product decision.
4. **B5/B6** — courier event log table + per-order timeline.
5. Rest as cleanup batches (B7/B8 form language, B9–B11 dead fields/stubs, LOWs).

**Labels:** LIVE_PROBE_PASSED (A1–A11), SOURCE_VERIFIED + LIVE_PROBE_PASSED (B1 live negative-amount proof), SOURCE_VERIFIED (B2–B20 except B4/B18 which are also LIVE_PROBE_PASSED).

---

## D. FIX ADDENDUM (applied after the audit, same day)

The findings above were written first, then the user approved fixing them. Each
fix below is verified live; the original finding text is left intact above as the
audit trail. Test data created to prove the fixes was removed afterwards.

### FIXED — B1 (coupon discount bypass) — SOURCE_VERIFIED + LIVE_PROBE_PASSED
- `Frontend/CouponController.php::apply` now computes the subtotal from the
  SERVER cart (`Cart::instance('shopping')->subtotal()`), ignoring the
  client-sent `subtotal` field entirely.
- `Frontend/CustomerController.php::order_save` recomputes the discount from the
  real cart via `$coupon->calculateDiscount()`, re-checks `isUsageAvailable()`,
  and clamps `discount ≤ subtotal + shipping` (and `≥ 0`).
- **Live proof of the fix:** claimed `subtotal=50000` on a real ৳890 cart with a
  50% coupon now yields `amount:445` (real 50%), placed order amount `565`
  (890+120−445), payment `565`. The old behaviour produced `amount=-23100`.
  Negative-amount orders are impossible (calculateDiscount caps at subtotal, the
  clamp caps at subtotal+shipping).

### FIXED — B2 (courier auto-send on wrong status) — SOURCE_VERIFIED + LIVE_PROBE_PASSED
- `Admin/OrderController.php::order_process` auto-send now fires on status **3
  (shipped)**, not 5 (cancelled). `$courier !== 3` prevents re-send.
- Bulk skip guard now skips terminal states 4/5/6 (delivered/cancelled/returned)
  with the correct label, instead of only id 5 mislabelled "delivered".
- **Live proof:** changing an order to shipped (3) redirects (302) to
  `/admin/order/shipped`; delivered (4) and processing (2) redirect cleanly.

### FIXED — B13 + B14 + null-return 500 (order_process robustness) — LIVE_PROBE_PASSED
- B13: the silent `return "ok"` no-op (no enabled SteadFast) now shows a clear
  Toastr warning and redirects; status is still saved.
- B14: `Toastr::error/success($message, 'Courier')` args corrected (message first,
  title second).
- Bonus: `order_process` used to return null for every non-shipped status and
  crash with HTTP 500 for orders that have no shipping row. Both fixed - a
  fallback redirect was added and the shipping update is now null-guarded.
  **Live proof:** status changes to processing/delivered now return 302, not 500.

### FIXED — B3 (per-order Pathao honesty) — SOURCE_VERIFIED + LIVE_PROBE_PASSED
- `CourierOrderSender::preview` refuses `provider=pathao` with a clear message
  (it speaks only the SteadFast protocol); the per-order Pathao button was
  removed from `backEnd/order/edit.blade.php`. Pathao goes through the bulk
  `order_pathao_auto` flow, which handles OAuth + stores.

### FIXED — B4 (server-side phone format) — SOURCE_VERIFIED
- `order_save` validates `phone` with `regex:/^01[3-9]\d{8}$/` (BD mobile, same
  pattern the courier sender enforces). Format check only - NO OTP, per the
  user's explicit decision.

### FIXED — B5 (courier event log + admin timeline) — LIVE_PROBE_PASSED
- New `courier_events` table (append-only) + `CourierEvent` model.
  `CourierWebhookController::applyStatus` logs every callback before the
  shipment's single `raw_payload` is overwritten, so a delivered -> returned
  transition keeps both steps.
- `Admin/OrderController::process` passes this order's events; the admin order
  process page now shows a "Courier events (this order)" timeline, and the old
  misleading "Courier history" (which was the customer's OTHER orders) is
  renamed "Customer's other courier shipments".
- Migration `2026_10_28_000001_create_courier_events_table` applied.

### FIXED — B6 (webhook returned mapping) — SOURCE_VERIFIED
- `CourierWebhookController` mapping: `returned` / `return_dispatched` /
  `return_approval_pending` now map to the local `returned` status (id 6), not
  `cancelled`. The `wouldRegress` guard's terminal set now includes `returned`
  so delivered -> returned is allowed and returned is protected.

### FIXED — B18 (admin password drift) — LIVE_PROBE_PASSED
- `README.md` and `CreateAdminUserSeeder.php` now document/set the working local
  admin password `105585` (the old `123456` fails `Hash::check` for all seeded
  users). Verified live: `105585` -> `/admin/dashboard` 200.

### NEW — starter coupon (per user request)
- `JANNAT10`: 10% off, min cart ৳300, max discount ৳150, valid 60 days, unlimited
  uses, status active. Verified end-to-end live (apply ৳89 on a ৳890 cart ->
  order amount ৳921, usage logged `source=checkout`, then reset to 0 uses).

### Answered standing question — coupon time expiry with no scheduled job
- `Coupon::scopeRunning` (`app/Models/Coupon.php:42-50`) compares `start_at` /
  `end_at` against `now()` on EVERY query (`Coupon::where(...)->running()`), so
  a coupon becomes invalid the moment its window passes. No scheduler job is
  needed (grep of `app/Console/Kernel.php` for coupon = empty).

### Regression after all fixes
- `php artisan test --filter=Admin` = **63 passed (452 assertions)**.
- `php artisan admin:audit-pages` = 61 pages, **0 fail**.
- `/tmp/opencode/admin_walk.py` = **0 FAILING PAGES**.
- All audit/fix test data removed (orders, test coupons, usage rows, fraud rows,
  notifications); `JANNAT10` left fresh at 0 uses.

---

## Addendum E — B7–B17, B20 cleared (second fix batch)

Labels follow the evidence contract: `SOURCE_VERIFIED` = code read at path:line,
`LIVE_PROBE_PASSED` = real HTTP/DB proof on the running server,
`NOT_TESTED` / `UNVERIFIED` where proof does not exist.

### FIXED — B8 (19 raw submit buttons) — SOURCE_VERIFIED + LIVE_PROBE_PASSED
- Converted 19 `<input type="submit">` to `<x-admin.button variant="success"
  type="submit">` across campaign/contact/collections/order/permissions/pixels/
  roles/settings/socialmedia/tagmanager/sms_manage create+edit views via a
  comment-aware script (so the two intentional `createpage` comments survived).
- Grep now shows only those 2 comment matches; all converted create/edit pages
  render 200 live. (campaign/{id}/edit and pixels/{id}/edit return 500 because no
  Campaign/Pixel records exist — `->id` on null in the controller — a
  pre-existing data issue unrelated to this blade change.)

### FIXED — B12 (silent fraud catch) — SOURCE_VERIFIED
- `CustomerController` checkout fraud `try/catch` was a bare `// no-op`. It now
  `Log::warning`s the order id, phone and error, so a broken fraud service is
  visible instead of reporting nothing.

### FIXED — B17 (duplicate attribute route) — SOURCE_VERIFIED + LIVE_PROBE_PASSED
- Removed the `/admin/attributes` stub route (`admin.attributes.soon`); the
  sidebar now links `admin.attributes.index` (`/admin/attributes/manage`) with an
  `admin.attributes.*` active-check. Zero `attributes.soon` refs remain;
  `/admin/attributes/manage` renders 200.

### FIXED — B9 (product image title always empty) — SOURCE_VERIFIED + LIVE_PROBE_PASSED
- The create form never posts `title[]` for the multi-image gallery, so
  `$titles[$key]` was null and EVERY created image got `title=''`. `store()` now
  falls back to the original filename when no title is supplied, and the create
  form gained an optional `title[]` next to Main Image (matching the edit form).
  Product create/manage/edit render 200.

### FIXED — B15 (hardcoded Pathao parcel data) — SOURCE_VERIFIED
- New `pathaoParcelData(Order)` helper derives real `item_quantity` (sum of
  `order_details.qty`), real `item_description` (first 3 product names, fallback
  `Order #invoice`), and `sender_name` from `GeneralSetting->name`
  ("GS Corporation"). Both Pathao send paths use it; the block that used
  `sender_name='Test'` / `'Order from Adora'` / qty 1 now reads real values. The
  silent store-location fallback now logs a warning. Verified by reflecting the
  helper on order 4. Per-item weight is not stored in `order_details`, so 0.5kg
  remains the documented fallback — `NOT_TESTED` against a real Pathao API.

### FIXED — B16 (dead proSize/proColor) — SOURCE_VERIFIED
- Removed the unreachable `sizes()`/`colors()` attach branches in `store()` and
  the always-null `sync()` in `update()`: no form, script or route posts those
  keys (verified project-wide). The audit's `sku` claim is a **FALSE POSITIVE** —
  `sku` is in `$appends` as `getSkuAttribute()` (= `product_code`), NOT in
  `$fillable`, and `Product::create` uses the allow-list, so there is no SQL bug.
  Product pages render 200.

### FIXED — B11 (/brands redirect stub) — LIVE_PROBE_PASSED
- `/brands` was `redirect()->route('home')`, but header nav AND footer both link
  here, so "Brands" silently dropped visitors on the homepage. Replaced with a
  real closure fetching active brands + `GeneralSetting`, rendering new
  `frontend/fm/pages/brands.blade.php` + `sections/brands-index.blade.php` in the
  FM design language (same shell as /categories). Live: `/brands` 200, 5 brand
  cards, each links `products.brand/{slug}`; a brand page renders 200.

### FIXED — B7, phase 1 (small content forms -> x-admin) — LIVE_PROBE_PASSED
- Per user decision (smallest correct diff), converted only the LOW-risk forms:
  `category/create|edit`, `brand/create|edit`, `subcategory/create|edit`,
  `childcategory/create|edit` — raw `form-floating` text inputs -> `x-admin.input`,
  status checkboxes -> `x-admin.switch`, and subcategory's flat category dropdown
  -> `x-admin.select`.
- **Honest exception:** childcategory's subcategory dropdown stays hand-written
  because it is a GROUPED select (disabled `-- Category --` headers) and
  `x-admin.select` only emits a flat key=>value list.
- Live: all 8 forms render 200 with component fields wired (`for`/`id` match);
  category create (id=14) and subcategory create (id=17, select=cat 1,
  switch=status 1) were really submitted and then deleted.
- **NOT DONE (deferred by user):** `product/create|edit`, `variant/*`,
  `attribute/*` remain on the raw layout — highest risk (dropify/select2/variant
  JS) and converting them changes the floating->flat label design project-wide.
  Treated as a known inconsistency, not a bug: every form already renders 200 and
  saves correctly.

### FALSE POSITIVE — B20 (courier_fraud_checks has no order_id) — SOURCE_VERIFIED
- The fraud result IS attached to the order, just on the ORDER's own columns:
  `CourierFraudService::attachToOrder()` writes `risk_label`,
  `risk_score_percent`, `risk_last_checked_at`, `risk_source` onto `orders`
  (`app/Services/CourierFraudService.php:175-182`), and `order/_row.blade.php`
  renders exactly those (2 orders already carry a `risk_label`). The
  `courier_fraud_checks` table is a per-PHONE cache keyed by phone, so one check
  serves many orders. Adding `order_id` there would BREAK that dedup. No change
  made — same conclusion as the B16 `sku` false positive.

### Regression after Addendum E fixes
- `php artisan test --filter=Admin` = **63 passed (452 assertions)**.
- `php artisan admin:audit-pages` = 61 pages, **0 fail** (13 intentionally skipped).
- `/tmp/opencode/admin_walk.py` = **0 FAILING PAGES**.
- All B7 test rows (category id=14, subcategory id=17) created-then-deleted; no
  leftover fixture data.

### FIXED — B10 (product_variants.discount_price wired into frontend pricing) — LIVE_PROBE_PASSED
- The `discount_price` column existed and was written (variant create/edit forms,
  product-create JS, VariantController + ProductController), but NO frontend path
  ever read it — it was a genuinely dead field. All 6 seeded variants had it NULL,
  so there was zero regression risk.
- Per user decision it is now wired into pricing (product `old_price -> new_price`
  semantics): a variant `discount_price` set LOWER than the variant's base price
  (`price_override` else product `new_price`) becomes the selling price, and the
  base becomes the struck old price + drives the save badge.
- **Backend** (`FrontendController`): both variant resolve endpoints now SELECT
  `discount_price` (was missing from the query) and apply the discount rule —
  `checkStock` (classic page, `products/check-stock`) and `variantDetail` (FM page,
  `products/{id}/variant-detail`). `variantDetail` now returns the variant base as
  `old_price` when a variant discount applies, so the save badge is per-pick.
- **Frontend** (`fm-product-detail.js`): `applyPrice(price, oldPrice)` now consumes
  the bundle's `old_price` (falls back to product-level `CFG.baseOldPrice`), so a
  variant discount shows the correct struck price instead of the product's
  `old_price`. `apply()` passes `bundle.old_price`. `node --check` OK.
- **Live proof**: variant 6 (product 1, base 1150, product new 1150 / old 1250) set
  to `discount_price=999` -> `variantDetail` returned `price=999, old_price=1150`;
  `checkStock` returned `price=999, formatted "৳ 999 <del>৳ 1150</del>"`. Test
  discount then reset to NULL; all variants back to NULL (no leftover).
- **Regression**: `php artisan test --filter=Admin` = 63 passed (452 assertions),
  `admin:audit-pages` = 61 pages 0 fail, `admin_walk.py` = 0 FAILING PAGES; product
  detail page renders 200.
- Note: the pill `data-price` attribute (summary.blade:139) is a dead/empty
  attribute — the pill builder (`values[]`) emits no `price` key and no JS reads
  it; price always comes from the resolve endpoint, so it was left untouched.
