# Order → courier → courier risk — live verification (2026-11-15)

Contract `gc-mwh7396d` (the earlier `gc-mwh0ju9r` had its ledger block wiped by an
unrelated process; its check still passes as
`php tools/verify/order-courier-flow.php`).

Verified by Jarin against the running app (`http://127.0.0.1:8000`, admin
`admin@gmail.com`) with a real browser (Playwright/Chromium) and real courier
API calls.

---

## 1. What the admin asked

1. Does the order page carry both courier buttons (SteadFast + Pathao)?
2. Does clicking a courier button bring up a form?
3. Can the order data be sent in one click?
4. Create one order.
5. Does the courier API check show a risk?

---

## 2. Answers (all live, all evidenced)

| # | Question | Answer | Evidence |
|---|---|---|---|
| 1 | Both courier buttons on the order page? | **Yes** | `/admin/order/all` renders `.multi_order_courier` (SteadFast → POST `/admin/bulk-courier/steadfast`) and `.individual-courier-btn` (Pathao → POST `/admin/order-pathao-auto`). `work/courier-flow/01-order-page.png` |
| 2 | Does the button bring up a form? | **The per-order one does; the top SteadFast button is a bulk action with no form** | The truck icon in a row opens `/admin/order/{id}/courier/steadfast` → `#fmCourierForm` with recipient, phone, address, COD and the endpoint. `06-courier-form.png` |
| 3 | One click to send? | **Yes — and both couriers really received the order** | SteadFast one click → `POST https://portal.packzy.com/api/v1/create_order` → **401 `Account is not active!`**. Pathao one click → **a real consignment `DS151126N2GWK6`** in the Pathao sandbox, status Pending. `05-bulk-send.png`, `09-pathao-send.png` |
| 4 | An order created? | **Yes — after three bugs were fixed** | Order #512, invoice 50397, amount ৳1270, shipping `Jarin Courier Flow Check / 01909094802 / House 42, Road 11, Banani`, one line item. Before the fix no order could be created at all. |
| 5 | Risk from the courier API? | **The pipeline runs; the SteadFast account has no answer for it** | `GET /fraud_check/score/{phone}` → **403 `Your account is inactive.`** → the service falls back to our own shipments → the screen shows **Risk: Risky / Unknown**, provider `internal` / “Local history”. `08-risk-check.png` |

### The courier answers, verbatim

```
GET  https://portal.packzy.com/api/v1/get_balance             -> 200 {"status":200,"current_balance":0}
GET  https://portal.packzy.com/api/v1/fraud_check/score/{ph}  -> 403 {"message":"Your account is inactive. Please contact support."}
POST https://portal.packzy.com/api/v1/create_order            -> 401 "Account is not active!"
POST https://courier-api-sandbox.pathao.com/oauth/token       -> 401 (it works from here; the consignment exists)
```

`get_balance` answering 200 while `create_order` answers 401 is the courier’s
own inconsistency — the credentials are read, the account is not active.

---

## 3. Root causes found and fixed

### 3.1 The POS cart could not take a product (JS string literal)

`public/admin/assets/js/admin-page-order-create.js` and
`admin-page-order-edit.js` called the cart endpoints as

```js
url: "CFG.cart_add",     // a STRING, not the value
```

so the browser asked for `/admin/order/CFG.cart_add`. The admin order route
(`order/{slug}`) swallows that path and answers **HTTP 200 with the orders page
HTML**, jQuery could not read JSON from it, the toast said *“Failed to add
product to cart”* — and the POS cart stayed empty. **No order could be created
from the admin POS screen.** Fixed to `url: CFG.cart_add` (22 call sites).

### 3.2 The order insert wrote a column that never existed

`OrderController::order_store()` and `order_update()` set `$order->note`, but
the `orders` table has no `note` column — the admin note lives in `admin_note`
(migration `2026_03_03_124223`). Every insert died with

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'note' in 'field list'
```

The admin saw *“Order creation failed”* and no order. Both assignments removed.

### 3.3 The order line items wrote a column that never existed either

`order_details` has no `product_discount` column, although the code writes it
(`order_store`, `order_update`) and reads it back to rebuild the cart
(`order_edit`). Every line-item insert failed, so an order was saved with **no
products at all**. Added
`database/migrations/2026_11_15_000001_add_product_discount_to_order_details_table.php`
(DECIMAL(12,2) default 0, matching the money columns).

### 3.4 The courier’s refusal was reported as a network problem

`CourierOrderSender::send()` caught every throwable alike. Guzzle throws on a
4xx, so the courier’s **401 `Account is not active!`** was reported to the admin
as *“Could not reach SteadFast Courier. Nothing was created — please try
again.”* — an outage message for a refusal, and the real reason only reached
the log. `RequestException` is now handled separately and the courier’s own
status and message reach the admin.

### 3.5 The bulk button always said “sent”

`admin-page-order-index.js` read the bulk response `{"status":"completed"}` as
success, so a courier that refused **every** order still produced a green
*“Orders Sent Successfully!”*. The toast now reads the per-order `results[]`.

### 3.6 The courier chooser offered a courier that cannot be sent there

The per-order screen listed Pathao next to SteadFast as if both worked; clicking
Pathao landed on a screen saying *“Per-order Pathao sending is not supported by
this screen”* — the answer arrived only after the click. The chooser now says
it on the card:

* a courier this screen can book → **“Send from here”** and a link,
* a courier it cannot book (Pathao) → **“Bulk action only”**, the reason spelled
  out, and **no link at all**.

`CourierOrderSender::perOrderSupport()` is the single source of truth, and
`preview()` uses it, so the card and the refusal can never drift apart.

### 3.7 One courier’s parcel hid the other courier’s button

`backEnd/order/_row.blade.php` hid the truck icon when **any** provider had a
consignment. Booking an order with Pathao from the list therefore made the
SteadFast route disappear from the list — although the send screen itself
refuses only a double booking with the *same* courier. The guard now looks at
the SteadFast shipment alone.

---

## 4. Two things that are still not ours to fix

* **The SteadFast/Packzy merchant account is not active** (`401 Account is not
  active!` on create, `403` on the risk score). Until the courier activates it,
  no order can be booked with them and no courier-side risk score can be read.
  The balance is also `0`.
* **Pathao’s sandbox works**, but its merchant portal login answers `400` and
  its token endpoint answers `401` from this host — the booking path works, the
  merchant-portal history lookup does not.

---

## 5. Things worth knowing

* `CourierFraudService::checkPhone()` reuses a saved check for **12 hours**, so
  a second “Check Now” in the Fraud Checker does **not** call the courier API
  again. Use a fresh number when testing the live lookup.
* One **pending** parcel with no completed delivery is labelled **“Risky”**
  (`determineRiskLabel`: a null success rate with any history is “risky”). A
  brand-new number reads **“New”**.
* The Fraud Checker stores the result but does not write it onto the order row;
  the order list shows it from the saved check (`attachRiskFallbackToOrders`).
* The per-row truck icon disappears once a **SteadFast** consignment exists —
  that is deliberate (no second billable parcel with the same courier).

---

## 6. How to re-run it

```bash
node tools/verify/order-courier-flow.browser.cjs   # 12 live steps, screenshots + evidence.json
php  tools/verify/order-courier-flow.php            # prints ORDER_COURIER_FLOW_OK
php  tools/verify/order-courier-chooser.php         # prints COURIER_CHOOSER_OK
```

**Pre-existing unrelated failure:** `SampleTemplateOrderTest` (2 tests) fails
on this branch with and without these changes (verified by stashing them).
