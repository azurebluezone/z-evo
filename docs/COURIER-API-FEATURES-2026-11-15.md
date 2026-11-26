# Courier API features — what we can get, what we cannot, and why the admin kept going to the courier's site

Written 2026-11-15, from live probes against the configured courier accounts and
from the code that uses them. Every status in this document was measured, not
assumed.

---

## 1. The two accounts

| Provider | Type | Base URL | Credentials | Live answer |
|---|---|---|---|---|
| SteadFast (via Packzy) | `steadfast` | `https://portal.packzy.com/api/v1` | Api-Key + Secret-Key present | `GET /ping` → 200 pong, `GET /get_balance` → 200 |
| Pathao | `pathao` | `https://courier-api-sandbox.pathao.com` | client id / secret / user / password present | `POST /api/v1/issue-token` → token issued, `POST /api/v1/orders` → consignment created |

The official `portal.steadfast.com.bd` host does not resolve from this server,
and the configured account is a Packzy-hosted API that is **SteadFast-shaped
but not the full SteadFast API** — that difference is the whole story below.

---

## 2. The feature map — courier dashboard → API route → live answer

Probed with a **GET**, so looking never creates anything. A route that exists
but only accepts POST answers `405`; a route this account does not have
answers `404`.

### SteadFast / Packzy (`portal.packzy.com/api/v1`)

| Courier dashboard feature | API route | Live answer | Verdict |
|---|---|---|---|
| API connection | `GET /ping` | 200 `pong`, `service: steadfast-api v1` | **Available** |
| Balance / recharge | `GET /get_balance` | 200 `{"current_balance":0}` | **Available** (balance is 0) |
| Send order | `POST /create_order` | 401 `Account is not active!` | **Blocked** — the account is inactive |
| Customer check | `GET /fraud_check/score/{phone}` | 403 `Your account is inactive.` | **Blocked** |
| Parcel list | `GET /get_all_orders` | 404 `The route api/v1/get_all_orders could not be found.` | **Not offered** |
| Cancel parcel | `POST /cancel_order` | 404 | **Not offered** |
| Pickup point | `GET /get_all_pickup_stores` | 404 | **Not offered** |
| Courier list | `GET /get_all_couriers` | 404 | **Not offered** |
| Delivery charge / product types | `GET /get_product_categories` | 404 | **Not offered** |

Also probed and confirmed absent: `/get_pickup_stores`, `/get_couriers`,
`/product_categories`, `/get_order/{id}`, `/get_all_merchants`,
`/get_due_amount`, `/get_payment_options`, `/get_areas`, `/get_delivery_charge`,
`/orders`, `/parcels`, `/pickup`, `/pickup_request`, `/delivery_status`,
`/cancel`, `/track`, `/tracking`, `/get_status` — every one `404`.

### Pathao (sandbox)

| Courier dashboard feature | API route | Live answer | Verdict |
|---|---|---|---|
| API connection | `POST /api/v1/issue-token` | token issued | **Available** |
| Balance / recharge | — | not on the courier API | **Not offered** (it is on `merchant.pathao.com`, a different host and login) |
| Pickup point | `GET /api/v1/stores` | answers, but slowly | **Available** |
| Send order | `POST /api/v1/orders` | `201`, consignment `DS151126N2GWK6` | **Available** |
| Parcel list | `GET /api/v1/orders` | 401 `Invalid Access Token!` (different scope) | **Not usable** |
| Cancel parcel | `PUT /api/v1/orders/{id}` | not wired | **Not offered** |

---

## 3. Why the admin had to keep opening the courier's website

Four reasons, all measured:

1. **The balance was nowhere in our panel.** `get_balance` worked, but only
   inside the "Test connection" result. There was no balance card, so the
   answer lived on the courier's site.
2. **The parcel status never came back.** `courier_events` held **0 rows** and
   every parcel in `courier_shipments` was frozen at `Pending`. The reason:
   the webhook URL is built from `APP_URL`, and `APP_URL=http://localhost` —
   an address the courier cannot call. The admin had to copy the URL and the
   token into the courier portal by hand, and nothing said so anywhere.
3. **The courier's own parcel list is not on this API** (`get_all_orders` →
   404), so "how many parcels do I have" could only be answered by the
   courier's counter.
4. **Delivery charge, pickup point, rider list and parcel cancel** are all
   absent from the API (404), so they stayed on the courier's site by
   necessity.

---

## 4. What is now in our admin panel

### `/admin/courier/dashboard` (new)

* **Live balance** per provider, read from the API at page load. SteadFast
  shows `৳ 0.00`; Pathao says its balance is on the merchant portal instead
  of showing a zero that would be a lie.
* **Parcels we booked** — total, today, 7 days, 30 days, and the delivered /
  cancelled / pending split, plus the last sync time. The panel says in words
  that this is *our* count, not the courier's counter.
* **Webhook readiness** — the exact URL the courier must call, and a red
  badge with the reason when that address points at `localhost` and therefore
  cannot be reached.
* **The capability matrix** — every courier-dashboard feature, the route it
  needs, the live HTTP answer, and whether we can use it here. This is the
  list of things the admin used to have to look up on the courier's site.

### `/admin/audit-log` (new)

One trail for everything that happens, so "what happened to this order?" is
one query instead of a code reading:

| Action | Written when |
|---|---|
| `order.created` | an order is created (POS or storefront) |
| `order.note.updated` | an order note is rewritten — the **old text is kept** |
| `order.status.changed` | an order moves (list dropdown or kanban board) |
| `inventory.movement.recorded` | every row written to the inventory ledger |
| `courier.order.sent` / `.refused` / `.unreachable` | a parcel is sent to a courier |
| `courier.credentials.saved` / `.toggled` / `.webhook_token.rotated` | the courier account changes |
| `courier.fraud_check.run` | a risk lookup is run for a number |
| `return.approved` / `return.refunded` | a return or refund is decided |

The table (`audit_logs`) carries: `user_id`, `actor_name` (a snapshot, so the
log still reads correctly after a user is deleted), `action`, `subject_type`,
`subject_id`, `description`, `before` / `after` (JSON), `source`, `ip` and the
timestamps.

Two things it fixed that the old code could not do:

* **The note history.** `orders.admin_note` is one mutable column — overwriting
  it destroyed the previous text, and nothing recorded who wrote it. The note
  is now appended to the trail with the value it replaced.
* **The inventory ledger's missing "who".** The ledger was written with
  `DB::table('inventory_movements')->insert()` at **eleven** call sites, so no
  event fired anywhere and `created_by` was usually empty. Every one of those
  sites now goes through `InventoryMovement::record()`, which writes the
  ledger row **and** the audit row in one place.

---

## 5. What still needs the courier's own website

| Feature | Why |
|---|---|
| The courier's own parcel count and list | `get_all_orders` is 404 on this account |
| Cancel a parcel | `cancel_order` is 404 on this account |
| Delivery charge and product categories | `get_product_categories` is 404 on this account |
| Pickup points and rider list | `get_all_pickup_stores` / `get_all_couriers` are 404 (Pathao has stores) |
| SteadFast parcel status | the account is inactive (`401` / `403`); Pathao's parcel list needs another scope |

**The one thing that is ours to fix:** `APP_URL` must be the public address of
this site, and the webhook URL plus the token must be pasted into the courier
portal once. Until then the parcel status cannot come back, and the dashboard
says so in red instead of leaving the admin to guess.

---

## 6. How to re-check it

```bash
php  tools/verify/audit-log-courier-dashboard.php   # prints AUDIT_LOG_COURIER_DASHBOARD_OK
node tools/verify/order-courier-flow.browser.cjs     # the live order -> courier -> risk walk
php  tools/verify/order-courier-flow.php              # ORDER_COURIER_FLOW_OK
php  tools/verify/order-courier-chooser.php           # COURIER_CHOOSER_OK
```
