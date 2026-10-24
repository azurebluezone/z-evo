# Courier — menus, API settings and pages audit (2026-10-24)

Audited by: zombiecoder (Chief Arbiter). **Read-only.** No provider API was
called, no token rotated, no provider toggled, no order sent, no sync run.

**Verdict in one line:** every courier page renders and its forms are wired, but
the same credentials row is written by **three different screens with three
different rule sets**, the "Test Connection" button on one screen actually
**saves** the credentials, and no provider is configured at all — so nothing can
currently ship.

---

## 1. Scope

| Area | Files |
|---|---|
| Menu | `resources/views/backEnd/layouts/partials/sidebar.blade.php:250-287` |
| Controllers | `Admin\CourierController`, `Admin\CourierSettingsController`, `Admin\ApiIntegrationController` (courier half), `Admin\CourierFraudCheckerController`, `Admin\OrderCourierController`, `Api\CourierWebhookController` |
| Services | `app/Services/CourierConnectionTester.php`, `CourierOrderSender.php`, `CourierFraudService.php` |
| Models | `app/Models/Courierapi.php` (table `courier_apis`), `CourierShipment`, `CourierFraudCheck` |
| Views | `backEnd/courier/{index,edit,settings,shipments,shipment,fraud_checker}.blade.php`, `backEnd/apiintegration/courier_manage.blade.php` |
| Shared JS | `resources/views/backEnd/layouts/partials/scripts.blade.php:364-470` |
| Tables | `courier_apis` (2 rows), `courier_shipments` (0), `courier_fraud_checks` (1), stale `courierapis` (0 rows, unused) |

---

## 2. Menus → routes → controllers

Sidebar group **Courier** (`ri-truck-line`), 5 live items + 1 disabled:

| # | Menu label | Route | Controller | Purpose | State |
|---|---|---|---|---|---|
| 1 | Couriers | `admin.courier.index` → `/admin/courier` | `CourierController@index` | per-provider card: status, shipment counts, **Test**, **Rotate token**, webhook URL + bearer token | 200 |
| 2 | Shipments | `admin.courier.shipments` | `CourierController@shipments` | filter by provider/status/keyword, paginated list, detail page | 200 |
| 3 | Settings | `courierapi.manage` → `/admin/courierapi/manage` | `ApiIntegrationController@courier_manage` | legacy credential form + Test + Sync | 200 |
| 4 | Fraud Checker | `admin.courier.fraud-checker` | `CourierFraudCheckerController@index` | search by phone, **Check Now**, **Override** | 200 |
| 5 | API Settings | `admin.courier.settings` | `CourierSettingsController@index` | second credential form (SteadFast + Pathao) with Test/Sync | 200 |
| — | Status Fast Courier | — | — | `comingSoon` disabled entry | disabled |

Reachable but **not in the menu** (each is linked from another screen):

| URL | Route | Entry point |
|---|---|---|
| `/admin/courier/{type}/edit` | `admin.courier.edit` | "Edit credentials" on the Couriers card |
| `/admin/courier/shipments/{shipment}` | `admin.courier.shipment` | row link in Shipments |
| `/admin/order/{order}/courier/{provider}` | `admin.order.courier.show/store` | order screen |
| `POST /admin/bulk-courier/{slug}` | `admin.bulk_courier` | order list bulk action |
| `POST /admin/courier/check-phone` | `admin.courier.check.phone` | **orphan — no UI calls it** |
| `POST /api/pathao-webhook`, `/api/steadfast-webhook` | `api.*.webhook` | provider → us |

Route/provider guards: `CourierController::guardType()` (`:228-236`) 404s any
provider outside `['steadfast','pathao']`; `OrderCourierController` normalises the
route provider the same way before calling the sender.

---

## 3. Per-page checks (Chromium, admin session, `127.0.0.1:8000`)

| Page | HTTP | Console errors | Zero-size icons | `scrollWidth/innerWidth` @1440 | @390 |
|---|---|---|---|---|---|
| `/admin/courier` | 200 | none | 0 / 50 | 1440/1440 | 390/390 |
| `/admin/courier/shipments` | 200 | none | 0 / 46 | 1440/1440 | 390/390 |
| `/admin/courierapi/manage` | 200 | none | 0 / 47 | 1440/1440 | 390/390 |
| `/admin/courier/fraud-checker` | 200 | none | 0 | 1440/1440 | 390/390 |
| `/admin/courier/settings` | 200 | none | 0 | 1440/1440 | 390/390 |
| `/admin/courier/steadfast/edit` | 200 | none | 0 | 1440/1440 | 390/390 |
| `/admin/courier/pathao/edit` | 200 | none | 0 | 1440/1440 | 390/390 |

Form/route match:

| Page | Forms | Action | CSRF | Matches controller validation |
|---|---|---|---|---|
| `courier/settings` | `#steadfastForm`, `#pathaoForm` | `admin.courier.steadfast.update`, `admin.courier.pathao.update` | yes | mostly — see P1-1 (url `max:99`, keys `max:155`) |
| `courierapi/manage` | 2 credential forms + sync block | `courierapi.update` | yes | **no** — url `string` not `url`, all keys `nullable` (P1-1) |
| `courier/{type}/edit` | 1 form | `admin.courier.update` | yes | yes (`url required|url|max:255`, keys `required`) |
| `courier/fraud-checker` | 3 (`GET` search, `POST` check, `POST` override) | matching routes | yes on POST | yes |
| `courier/shipments` | 1 `GET` filter | same route | n/a | yes |
| `courier/index` | toggle + rotate-token forms | matching routes | yes | yes |

---

## 4. API configuration and test buttons

### Where the credentials live

One table, `courier_apis`, one row per provider (`steadfast`, `pathao`), columns
`url, api_key, secret_key, client_id, client_secret, username, password, token,
webhook_token, status`. `Courierapi::$hidden = ['webhook_token']`, and
`isConfigured()` / `isReady()` (`app/Models/Courierapi.php:76-104`) gate every
send on both status and credentials.

**Current DB state (measured):**

```
steadfast  status=0  url=NULL  api_key=empty  secret_key=empty  webhook_token=set
pathao     status=0  url=NULL  client_id=empty  client_secret=empty  username=empty  password=empty  webhook_token=set
courier_shipments=0   courier_fraud_checks=1
```

So both providers are **off and unconfigured**: no order can be sent, and every
"Test" button will report "not configured" until credentials are entered.

### Three writers, one row

| Surface | Route | Rules | Writes `webhook_token`? |
|---|---|---|---|
| `/admin/courier/{type}/edit` | `admin.courier.update` → `CourierController@update:62` | `url required\|url\|max:255`; steadfast `api_key+secret_key required\|max:255`; pathao 4 fields required; generates token if blank | **yes** |
| `/admin/courier/settings` | `admin.courier.{steadfast,pathao}.update` → `CourierSettingsController@updateSteadfast:31` / `updatePathao:65` | `url required\|url\|max:99`; keys `required\|max:155`; `username` must be `email`; **then runs a live test** | no |
| `/admin/courierapi/manage` | `courierapi.update` → `ApiIntegrationController@courier_update:139` | `url required\|string\|max:255` (**not** a URL); **all credentials `nullable`**; no `title`; `status` from request | no |

Same column, three contracts. Consequence in P1-1.

### Test buttons — do they work?

| Button | Page | Endpoint | Implementation | Verdict |
|---|---|---|---|---|
| Test (per provider) | `/admin/courier` | `POST /admin/courier/{type}/test` → `CourierController@test:140` | `CourierConnectionTester` — bounded 15s, multi-step (ping → balance / token), JSON `{ok, steps[], message}` rendered inline | **works** (not exercised: would call the provider) |
| Test Connection (SteadFast) | `/admin/courierapi/manage` | `admin.courier.test.steadfast` → `ApiIntegrationController@testSteadfastConnection:208` | single `GET` to `str_replace('/create_order','/get_balance',$url)` with the **form's** keys (tests unsaved input) | **works**, different engine from the above |
| Test Connection (Pathao) | `/admin/courierapi/manage` | `admin.courier.test.pathao` → `:249` | issues a token against `/aladdin/api/v1/issue-token`, writes the token back into the form field | **works** |
| Test Connection (SteadFast/Pathao) | `/admin/courier/settings` | **`admin.courier.steadfast.update` / `pathao.update`** | saves the credentials and status, *then* tests | **not a test — a save** (P1-2) |
| Sync Orders | `/admin/courierapi/manage` | `admin.courierapi.sync.orders` → `ApiIntegrationController@syncOrdersWithCourier:300` | fraud-score backfill over orders with `risk_label` NULL | works, misleading name (P2-4) |
| Sync Orders | `/admin/courier/settings` | `admin.courier.sync.orders` → `CourierSettingsController@syncOrdersWithCourier:176` | the same fraud-score backfill, different code | duplicate (P2-4) |
| Check Now / Override | fraud checker | `admin.courier.fraud-checker.check/override` | `CourierFraudService::checkPhone` (external, 20s timeout) / local override | wired; external path not exercised |

---

## 5. Order → courier flow and webhooks

1. Admin opens `admin/order/{order}/courier/{provider}` → `OrderCourierController@show`, provider normalised, non-whitelisted → 404.
2. Confirm POSTs to the same route → `CourierOrderSender@send`.
3. Sender first checks for an existing shipment with a non-empty `consignment_id` (`CourierOrderSender.php:137-146`) and refuses a second send with a readable message — **double-send guard present**.
4. Provider call is wrapped in `try/catch` (`:173-197`); failure returns `{ok:false, message}` and the controller redirects back with `courier_error`, success with `courier_success`.
5. Provider webhooks: `POST /api/{pathao,steadfast}-webhook` → `CourierWebhookController`. Auth = bearer token compared with `hash_equals` against `courier_apis.webhook_token`, plus a per-IP rate limit; mismatches are 401. Status updates map into `courier_shipments` (`consignment_id`, `tracking_code`, `current_status`, `raw_payload`, `last_synced_at`).
6. `CourierShipment` currently has **0 rows**, i.e. no webhook has ever landed in this install.

---

## 6. Defects

### P1-1 — three writers to `courier_apis`, and one of them can wipe credentials

`ApiIntegrationController@courier_update` (`:139-172`) validates every credential
as `nullable` and then writes `$request->only([...])` straight through
`$update_data->update($input)`. The form it serves posts **empty strings** when a
field is left blank (`apiintegration/courier_manage.blade.php:20,30,127,137`), so
a save from `/admin/courierapi/manage` silently replaces working keys with `''`.
The other two surfaces require the keys, and `/admin/courier/settings` caps
`url` at 99 chars while the others allow 255 — a URL that saves on one screen can
be rejected on another.

**Fix:** one `UpdateCourierCredentials` action, one rule set, and
`filled($value) ? $value : keep` semantics for every secret field.

### P1-2 — "Test Connection" on `/admin/courier/settings` saves the credentials first

`courier/settings.blade.php:244-278` posts to `admin.courier.steadfast.update`;
`CourierSettingsController@updateSteadfast:31-60` writes the row, **then** runs the
test. Pressing Test therefore changes production config and the provider status
(`status` hidden input `#steadfastStatus`). Same for Pathao (`:281-315`).
**Fix:** a read-only `POST /admin/courier/{type}/test` that accepts the form's
unsaved values (the `CourierConnectionTester` already exists for this).

### P1-3 — no provider is configured, so the whole module is inert

Measured: both rows `status=0`, `url=NULL`, all keys empty. `isReady()` is false,
`toggle()` refuses to enable an unconfigured courier
(`CourierController.php:124`), and no order can be sent. This is a **data**
blocker, not a code bug — it needs the client's real SteadFast/Pathao credentials.

### P2-1 — the same `url` column means two different things

`courier/settings.blade.php:46` defaults to a **full endpoint**
(`https://portal.steadfast.com.bd/api/v1/create_order`) and
`ApiIntegrationController@testSteadfastConnection:217` requires exactly that
shape (`str_replace('/create_order','/get_balance',…)`), while
`courier/edit.blade.php:48` defaults to a **base**
(`https://portal.packzy.com/api/v1`) and `CourierConnectionTester::steadfastBase:168-173`
strips `/create_order|/get_balance|/ping`. A URL saved on one screen breaks the
tester on the other. **Fix:** store the base URL only; derive endpoints in one
place.

### P2-2 — secrets are echoed into HTML `value=""`

`courier/edit.blade.php:66,72` and `apiintegration/courier_manage.blade.php:20,30,127,137`
print the stored key/secret/password into the input value. Today the values are
empty (nothing leaks — measured `secretEcho = 0`), but the moment real keys are
entered they appear in page source and in any HTML dump. **Fix:** blank the field
and treat empty as "keep existing".

### P2-3 — the webhook bearer token is printed on the Couriers page

`courier/index.blade.php:163` renders `value="{{ $courier->webhook_token }}"` so
the admin can copy it. Deliberate, and the model hides it from JSON, but it is a
live secret in the DOM. Keep, and note it in the security review; do not add it
to any export.

### P2-4 — two "Sync Orders" endpoints, neither syncs orders

`admin.courierapi.sync.orders` and `admin.courier.sync.orders` both backfill
**fraud risk** onto orders (`ApiIntegrationController.php:300`,
`CourierSettingsController.php:176`). No code pulls shipment status *from* the
provider; status only arrives by webhook. The label misleads the operator into
thinking stale shipments will be refreshed. **Fix:** rename to "Backfill fraud
scores" and delete one copy.

### P2-5 — stale `courierapis` table

`courier_apis` is the live table (`Courierapi::$table`); `courierapis` still
exists with 0 rows. Harmless, but it is exactly the trap that already caused the
`hasColumn('courierapis', …)` bugs documented in the model header. **Fix:** drop
it in a migration after confirming no raw query references it.

### P3-1 — two connection testers

`CourierConnectionTester` (multi-step, 15s timeout, structured steps) and
`ApiIntegrationController::testSteadfastConnection/testPathaoConnection` (single
call, no timeout). Two answers to "is my courier working?" **Fix:** keep the
tester, make both screens call it.

### P3-2 — `admin.courier.check-phone` is an orphan route

`CourierSettingsController@checkPhoneViaCourier:233` exists and is routed, but no
view or JS calls it. Either wire it into the fraud checker or remove it.

### P3-3 — fraud "Check Now" has no in-flight state

The form is a plain POST; `CourierFraudService` allows a 20s upstream call
(`:344`), so the button looks dead and can be submitted twice. **Fix:** disable
on submit, or make it a JSON call with a spinner.

---

## 7. NOT_TESTED

| Item | Reason |
|---|---|
| Any real provider call (ping, balance, issue-token, create_order) | no credentials configured; calling them would be an outbound side effect |
| Toggle provider on/off | mutates `courier_apis.status`; also impossible while unconfigured |
| Rotate webhook token | invalidates the token the provider would be using |
| Send an order to a courier | creates a real consignment |
| Webhook delivery + status mapping | requires a signed provider callback; `courier_shipments` is empty |
| `bulk-courier/{slug}` | sends N orders to a provider |
| Fraud "Check Now" against a live provider | external call with 20s timeout |
| Shipment detail page with a real shipment | table is empty; the empty state was rendered instead |

---

## 8. Ordered next steps

1. **One credential writer.** Extract a single `UpdateCourierCredentials` action used by all three screens; `nullable` secrets keep the stored value; one rule set (`url` base, `max:255`). Accept: saving a blank key on `/admin/courierapi/manage` leaves the old key intact; a URL accepted on one screen is accepted on all three.
2. **Make Test read-only.** Add `POST /admin/courier/{type}/test` that takes the unsaved form values and calls `CourierConnectionTester`; point the settings-page buttons at it. Accept: pressing Test changes no row (`updated_at` unchanged).
3. **Unify the URL contract.** Store the base only; build `/ping`, `/get_balance`, `/create_order` in the tester/sender. Accept: the same saved URL passes the test on both screens.
4. **Enter real credentials and enable one provider**, then run exactly one live test and record the JSON in this doc. Accept: `isReady()` true for that provider; the test returns `ok: true`.
5. **Blank the secret inputs** and add "leave blank to keep" helper text (P2-2). Accept: page source contains no key material.
6. **Merge/rename the two sync endpoints** to "Backfill fraud scores" (P2-4); wire or delete `check-phone` (P3-2).
7. **Drop the stale `courierapis` table** (P2-5) once a grep of raw queries is clean.
8. **Send one real order and receive one webhook** in a staging session; verify `courier_shipments` gains a row with `consignment_id` + `current_status`. Accept: double-send is refused with the existing message.

**Verified by:** `php artisan route:list | grep -iE "courier|webhook"`; `read` of `CourierController`, `CourierSettingsController`, `ApiIntegrationController:139-320`, `CourierWebhookController`, `Courierapi.php`, `CourierOrderSender.php`, `CourierConnectionTester.php`; `describe courier_apis|courier_shipments|courier_fraud_checks|courierapis` + row counts via `php -r`; `node /tmp/opencode/courier.mjs` and `courier2.mjs` (7 pages at 1440 and 390, console + overflow + icon size); `GET /admin/courier{,/shipments,/settings,/fraud-checker}`, `/admin/courierapi/manage`, `/admin/courier/{steadfast,pathao}/edit`.
