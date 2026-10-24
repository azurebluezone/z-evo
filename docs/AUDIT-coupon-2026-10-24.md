# Coupon — audit (2026-10-24)

Audited by: zombiecoder (Chief Arbiter). Read-only except one clearly-marked test
coupon that was created and deleted through the admin UI.

**Verdict in one line:** the admin CRUD works and the discount math is correct,
but the storefront **trusts a number the browser sends**, `used_count` is never
incremented, and the order stores the discount without storing which coupon
produced it. A customer can self-issue a discount of any size.

---

## 1. Scope

| Layer | Files |
|---|---|
| Admin routes | `admin/coupon/manage`, `/manage/datatable`, `/create`, `/save`, `/{id}/edit`, `/update`, `/active`, `/inactive`, `/destroy` |
| Admin controller | `app/Http/Controllers/Admin/CouponController.php` |
| Frontend routes | `POST /apply-coupon` (`coupon.apply`), `POST /remove-coupon` (`coupon.remove`) |
| Frontend controller | `app/Http/Controllers/Frontend/CouponController.php` |
| Order consumer | `app/Http/Controllers/Frontend/CustomerController.php:355,395,396` |
| Model | `app/Models/Coupon.php` (`scopeRunning`, `isUsageAvailable`, `calculateDiscount`, `products()`) |
| Views | `resources/views/backEnd/coupon/{index,_row,create,edit}.blade.php` |
| Table | `coupons(id, code, type, value, min_amount, max_discount, start_at, end_at, usage_limit, used_count, status, created_at, updated_at)` — **0 rows** at audit time |
| Pivot | `coupon_product` via `Coupon::products()` |

---

## 2. Route → controller → view matrix

| URL | Method | Controller method | View / response | Works? |
|---|---|---|---|---|
| `/admin/coupon/manage` | GET | `index` | `backEnd.coupon.index` (shared `partials.datatable`, endpoint `coupon.datatable`) | yes, 200 |
| `/admin/coupon/manage/datatable` | GET | `datatable` | JSON rows from `coupon._row` | yes (auth-gated) |
| `/admin/coupon/create` | GET | `create` | `backEnd.coupon.create` | yes, 200 |
| `/admin/coupon/save` | POST | `store` | redirect to list | yes |
| `/admin/coupon/{id}/edit` | GET | `edit` | `backEnd.coupon.edit` | yes |
| `/admin/coupon/update` | POST | `update` | redirect to list | yes |
| `/admin/coupon/active` \| `/inactive` | POST | `active`/`inactive` | status flip | yes |
| `/admin/coupon/destroy` | POST | `destroy` | delete + pivot | yes |
| `/apply-coupon` | POST | `Frontend\CouponController@apply` | JSON `{status,message,amount,coupon_code}` | yes, **but see P1-1** |
| `/remove-coupon` | POST | `remove` | JSON `{status:'success'}` | yes |

Admin form fields (`#couponsForm`) are exactly the validated inputs — no orphan
field, no validated-but-missing input:

`code, type(fixed|percent), value, min_amount, max_discount, usage_limit, start_at, end_at, product_ids[], status`

`_token` is present in the form (`create.blade.php:33-38`), so CSRF is on.

---

## 3. Rules: enforced vs decorative

| Rule | Where | Enforced? |
|---|---|---|
| Code unique + upper-cased | `Admin\CouponController.php:36,60` | **yes** |
| Type ∈ {fixed, percent} | `:37` | yes |
| `value`, `min_amount`, `max_discount` numeric ≥ 0 | `:38-40` | yes |
| `start_at ≤ end_at` | `:42` | yes |
| `usage_limit` integer ≥ 1 | `:43` | stored, **never enforced** (P1-2) |
| Active + date window | `Coupon::scopeRunning()` | yes, on apply only |
| Min-amount gate | `Coupon::calculateDiscount():74` | yes, against the **client-sent** subtotal (P1-1) |
| Percent cap (`max_discount`) | `:84-86` | yes, math correct |
| Discount ≤ subtotal, ≥ 0 | `:90-91` | yes, math correct |
| Product restriction | `Frontend\CouponController.php:46-57` | yes, recomputed server-side from the cart |
| `used_count` increments | — | **no writer anywhere in `app/`** (grep `used_count` returns only the model + admin `= 0` on create) |
| Per-user limit | — | **no column, no check** |
| Coupon recorded on the order | — | **no column** on `orders` (P1-3) |
| Re-validated at order placement | — | **no** (P1-4) |

`calculateDiscount` itself is sound: percent → `subtotal*value/100`, capped by
`max_discount`, clamped to `[0, subtotal]`. The defect is the input it is given.

---

## 4. Frontend flow (as built)

1. Cart page posts the code **plus a `subtotal` the browser computed**.
2. `apply()` reads `$subtotal = (float) $request->input('subtotal', 0)` (`:17`).
3. `Coupon::where('code',$code)->running()->first()` (`:26`) — inactive/expired/out-of-window coupons are rejected, and stale session keys are cleared.
4. `isUsageAvailable()` (`:36`) — always true in practice because `used_count` never moves.
5. Product-restricted coupons recompute the eligible subtotal from `Cart::instance('shopping')` (`:46-57`) — correct, cart-derived, not client-derived.
6. `calculateDiscount($eligibleSubtotal)` → session `discount`, `coupon_code`, `coupon_id` (`:64-66`).
7. Checkout `order_save` reads `Session::get('discount')` (`CustomerController.php:355`) and writes `amount = subtotal + shipping - discount` (`:395`) and `discount` (`:396`).
8. `remove()` clears all three session keys (`:76-78`).

Session `discount` is **not** recomputed when the cart changes and is **not**
re-validated at step 7, so it can outlive the condition that created it.

---

## 5. Defects

### P1-1 — `apply-coupon` trusts a client-supplied `subtotal`

`app/Http/Controllers/Frontend/CouponController.php:17` reads the subtotal from
the request; `:56` computes the discount from it. The cart is available
server-side (`Cart::instance('shopping')->subtotal()`), so the number is
unnecessary.

**Measured live** (percent 10, min 100 coupon `ZZTEST10`):

```
subtotal 1000    → HTTP 200, amount 100      (correct)
subtotal 999999  → HTTP 200, amount 99999.9  (self-issued discount)
```

`calculateDiscount` caps at the *claimed* subtotal, so the cap cannot save this.
**Fix:** ignore the request field; compute `Cart::instance('shopping')->subtotal()`.

### P1-2 — `used_count` has no writer, so `usage_limit` is decorative

`Coupon::isUsageAvailable()` (`app/Models/Coupon.php:63-70`) is the only reader.
`grep -rn used_count app resources/views` outside the model/admin controller
returns nothing. A coupon limited to 1 use is unlimited.

### P1-3 — the order does not record which coupon was used

`orders` columns: `id,invoice_id,amount,discount,shipping_charge,customer_id,user_id,order_status,admin_note,risk_label,risk_score_percent,risk_last_checked_at,risk_source,created_at,updated_at`.
No `coupon_id`, no `coupon_code`. After checkout nobody can answer "which coupon
produced this discount" — needed for both accounting and any future per-user limit.

### P1-4 — the discount is not re-validated at order placement

`CustomerController.php:355` consumes `Session::get('discount')` verbatim. If the
coupon expires, is deactivated, or its window closes between apply and checkout,
the order still gets the discount. Because the value is a fixed amount captured
earlier, changing the cart afterwards also lets `amount` go to or below zero
(`:395` has no clamp).

### P2-1 — POS has a second, separate coupon path

`Admin\OrderController.php:1731-1790` implements `pos_coupon_code` with its own
validation and its own session key. Two coupon engines means two places to fix
P1-2/P1-4 and two behaviours to test. Consolidate on the model.

### P2-2 — `index()` loads every coupon the view never uses

`Admin\CouponController.php:17` runs `Coupon::orderByDesc('id')->get()` but
`index.blade.php` renders through `coupon.datatable`. Harmless today, misleading
tomorrow (someone will "fix" the view and not the query).

### P3-1 — `status` is not in the store/update validation list

`store()` derives `status` from `$request->has('status')` (`:61`), which is
correct for a checkbox, but it means an API caller cannot set `status=0`
explicitly and an unchecked box silently means "inactive". Document it or use
`$request->boolean('status')` with a default of active.

### P3-2 — no per-user limit column

`coupons` has `usage_limit` (global) only. "One per customer" is impossible
without a `coupon_redemptions` table. Not a bug today, a design gap to decide on
before the first real campaign.

---

## 6. Live evidence

All of the following was executed on `http://127.0.0.1:8000` with an admin
session (`admin@gmail.com`), Chromium via Playwright.

| Check | Result |
|---|---|
| `GET /admin/coupon/manage` @1440 | 200, `scrollWidth 1440 / innerWidth 1440`, empty state "No coupons yet." rendered |
| `GET /admin/coupon/manage` @390 | 200, `390 / 390` |
| `GET /admin/coupon/create` @1440 | 200, 11 form fields as listed in §2, CSRF token present, 0 console errors |
| Create `ZZTEST10` (percent 10, min 100, active) via the UI | redirect to list, row present (`#couponTable tbody tr` = 1) |
| `POST /apply-coupon` honest subtotal | 200 `{amount: 100}` |
| `POST /apply-coupon` inflated subtotal | 200 `{amount: 99999.9}` |
| Delete via `POST /admin/coupon/destroy` | 200, row gone, `DB::table('coupons')->count()` = **0** (cleanup verified) |
| Console errors across all pages | none |

Probe scripts: `/tmp/opencode/coupon.mjs`, `/tmp/opencode/coupon2.mjs`.

---

## 7. NOT_TESTED

| Item | Why |
|---|---|
| Applying a coupon from the storefront cart UI end to end | the cart needs a product added; the same code path was exercised directly against `/apply-coupon`, which is where the defect lives |
| Checkout with an applied coupon (order row values) | would create a real order + reserve inventory; read from `CustomerController.php:355,395,396` instead |
| `usage_limit` exhaustion | impossible to observe while nothing increments `used_count` (P1-2) |
| Product-restricted coupon eligibility | needs `coupon_product` rows and matching cart items; code path read, not exercised |
| POS coupon path | admin order screen requires a full POS session; code path read only |

---

## 8. Ordered next steps

1. **Stop trusting the client subtotal** — `Frontend\CouponController.php:17` → `Cart::instance('shopping')->subtotal()`; delete the `subtotal` field from the cart JS. Accept: inflated-subtotal probe returns `amount` for the real cart only.
2. **Persist the redemption** — add `coupon_id`/`coupon_code` (nullable) to `orders`, write them in `order_save`, and increment `used_count` inside the same `DB::transaction` that creates the order. Accept: order row shows the code; `used_count` goes 0→1.
3. **Re-validate at placement** — in `order_save`, reload the coupon by `Session::get('coupon_id')`, re-run `running()` + `isUsageAvailable()` + `calculateDiscount($realSubtotal)`; on failure drop the discount and tell the user. Accept: deactivating a coupon between apply and checkout yields an order with `discount = 0`.
4. **Clamp the amount** — `amount = max(0, subtotal + shipping - discount)`. Accept: unit test on the arithmetic.
5. **Merge the POS path** onto the same model helpers (P2-1). Accept: one place references `calculateDiscount`.
6. **Decide per-user limits** (P3-2) before the first campaign; if yes, add `coupon_redemptions` and check it in `apply()`.
7. **Clean up** `index()`'s unused query (P2-2) while touching the file.

**Verified by:** `php artisan route:list | grep -i coupon`; `read app/Http/Controllers/{Admin,Frontend}/CouponController.php`; `read app/Models/Coupon.php`; `read app/Http/Controllers/Frontend/CustomerController.php:330-400`; `describe coupons`, `describe orders`, `count(coupons)` via `php -r` + Eloquent bootstrap; `node /tmp/opencode/coupon.mjs`, `node /tmp/opencode/coupon2.mjs`; `GET /admin/coupon/manage`, `GET /admin/coupon/create`, `POST /apply-coupon`, `POST /admin/coupon/destroy` on `127.0.0.1:8000`.
