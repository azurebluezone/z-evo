# Tracking T3 — server-side GTM / GA4 (implementation record, 2026-10-24)

Scope: `docs/PLAN-tracking-2026-10-24.md` §8 (server container) and §12 (P3).
**No new table, no new column.** Every credential is an environment value; the
browser tag, the server container URL and the Measurement Protocol sender are all
off until the corresponding value exists.

Depends on T0/T1/T2: `docs/IMPL-tracking-t0-2026-10-24.md`,
`docs/IMPL-tracking-t1-2026-10-24.md`, `docs/IMPL-tracking-t2-2026-10-24.md`.

---

## 1. The "trial code" question — answered with Google's own validation server

The user asked whether Google's documentation contains a trial/test code that can
be used to check the implementation. It does, and it is better than a trial
account:

> "The validation server does **not** validate `api_secret` or
> `firebase_app_id`. Carefully review those values to make sure they are
> correct."
> — <https://developers.google.com/analytics/devguides/collection/protocol/ga4/validating-events>

The only difference between the production and validation endpoints is the path:

| Server | URL |
|---|---|
| Measurement Protocol | `/mp/collect` |
| Validation server | `/debug/mp/collect` |

So the exact body this code sends can be checked against Google's server with
placeholder credentials, and the answer is authoritative about payload shape.
Verified by hand first:

```
POST /debug/mp/collect?measurement_id=G-XXXXXXXXXX&api_secret=dummy_secret_for_shape_test
{"client_id":"1234567890.1700000000","validation_behavior":"ENFORCE_RECOMMENDATIONS",
 "events":[{"name":"purchase","params":{"transaction_id":"A-1","currency":"BDT","value":1250.5}}]}
-> {"validationMessages": []}

{"events":[{"name":"_badEventName","params":{}}]}
-> NAME_INVALID

{"events":[{"name":"page_view","params":{}}]}
-> VALUE_REQUIRED (client_id)
```

That became `tests/tracking/ga4-validate.php`: the payload our service builds is
posted to the validation server, and two negative controls prove the endpoint is
actually checking (an empty array from a server that accepts anything would be a
false pass).

**Limit, stated plainly:** the validation server checks the payload, not the
property. It cannot tell us whether `G-XXXX` exists, whether the API secret is
correct, or whether a hit reaches the reports. That still needs the owner's GA4
account (see §5).

---

## 2. What changed

| # | File | Change |
|---|---|---|
| 1 | `config/tracking.php` | `gtm.container_id`, `gtm.server_container_id`, `server_container.url`, `ga4.measurement_id`, `ga4.api_secret`, `ga4.server_side`, `ga4.debug`, `ga4.region`. The server container URL is explicit → `TRACKING_HOST` → derived `t.<host of APP_URL>`, and stays `null` for localhost. |
| 2 | `app/Support/Ga4.php` *(new)* | Config accessors, `endpoint()` (global/EU, production/debug), `clientId()` (`_ga` cookie → derived from `visitor_id` → random), `sessionId()` (31-bit positive int), `purchasePayload()`, `item()`. No HTTP, no Eloquent: the payload is testable in isolation. |
| 3 | `app/Services/Ga4TrackingService.php` *(new)* | Measurement Protocol sender. Purchase only. Analytics-consent gate **before** any request. 4 s timeout, never throws, never logs the URL (it carries the secret). In debug mode it posts to `/debug/mp/collect` and treats validation messages as a failure. |
| 4 | `resources/views/frontend/partials/analytics-ga4.blade.php` *(new)* | Prints gtag.js when there is no GTM web container, with `transport_url` = server container URL, `send_page_view: false`, consent-mode signals (analytics granted, ads denied). Defines `window.__GA4__` — the single bridge between the event layer and GA4. |
| 5 | `resources/views/frontend/partials/event-layer-head.blade.php` | GTM id falls back to `TRACKING_GTM_ID`; `__GA4__.send()` called from `queueEvent()` (one call site); includes the GA4 partial; **raw admin GTM snippets are now consent-gated**. |
| 6 | `resources/views/frontend/partials/event-layer-body.blade.php` | GTM `<noscript>` iframe is consent-gated (it is a Google request too). |
| 7 | `resources/views/frontend/layouts/master.blade.php` | Classic customer-area layout printed a GTM container for **every** row with no consent check, using the raw admin field as the container id. Now: id extracted, env fallback, analytics gate, one container. |
| 8 | `resources/views/frontend/{fm/pages,layouts}/customer/order_success.blade.php` | Both themes call `__GA4__.purchase()` with `transaction_id` = invoice and the same line items the pixel uses. |
| 9 | `app/Http/Controllers/Frontend/CustomerController.php` | Calls `Ga4TrackingService::sendPurchase()` next to the Meta/TikTok calls, inside `try/catch`. |
| 10 | `tests/tracking/ga4-validate.php` *(new)* | Payload assertions + Google validation server + two negative controls + endpoint selection. |
| 11 | `tests/tracking/ga4-browser.mjs` *(new)* | 23 browser assertions: denied loads nothing, granted loads gtag with `transport_url`, the initial PageView arrives, bridge maps/drops events, purchase carries the invoice. |

---

## 3. Decisions and why

**Server-side GA4 is purchase-only.** GA4 has no general browser/server dedupe;
forwarding every event through the Measurement Protocol would double every page
view. Purchases are the documented exception: GA4 deduplicates them by
`transaction_id` ("Minimize duplicate key events with transaction IDs"). The
browser tag and the server sender therefore both use the invoice number, and the
MP copy is a fallback for a visitor whose browser tag was blocked — not a second
pipeline. `TRACKING_GA4_SERVER_SIDE` defaults to `false`.

**Nothing is loaded before consent, so consent mode is set once.** T2 already
prints vendor scripts server-side only after a grant. That makes the
"denied default, update later" pattern unnecessary — and printing gtag.js before
the decision just to set defaults would undo the gate. The tag declares analytics
granted and ad signals denied, which matches what this shop actually does (no
Google Ads).

**`client_id` must be the browser's, or the server event is a new user.**
`_ga` is `GA1.1.<random>.<timestamp>`; the part after the second dot is the client
id gtag already uses. When `_ga` is missing (blocked, or the tag never loaded) the
id is derived from our own `visitor_id`, which lives for a year, so the same
visitor does not fragment per order. With neither value available (CLI, admin
preview) a random GA4-shaped id is used — a constant like `0.0` would pile every
such order onto one fake user.

**`session_id` is derived, and that is a known limitation.** GA4 wants a positive
integer; ours is a UUID, hashed into the 31-bit range. The MP purchase therefore
does not necessarily join the browser's GA4 session. It is included because
without it the event is not attached to any session at all, which is worse for a
purchase that arrived only server-side. Dedupe by `transaction_id` means the
normal case (browser tag fired) never produces a second purchase.

**The host is derived from `APP_URL`, never purchased.** `TRACKING_HOST=t.<host>`
or `TRACKING_SERVER_CONTAINER_URL=https://<host>/t` (option B). The derived
default only exists for a real domain: `t.localhost` cannot resolve, so dev
stays `null` instead of pointing at a dead host.

**Two consent holes were found and closed while wiring GTM.**
`event-layer-head` printed admin-pasted raw GTM snippets unconditionally, and
`layouts/master` (classic customer area) printed a container for every row with
no gate at all. Both are Google requests before the visitor decides. Proven with
a temporary snippet row in `google_tag_managers`: denied → 0 requests on both
layouts, granted → the snippet and container appear.

---

## 4. Evidence

### 4.1 Payload vs Google's validation server

`php tests/tracking/ga4-validate.php` → **exit 0**

```
PASS  payload has a client_id in GA4 shape :: 1234567890.1700000000
PASS  transaction_id is the invoice number :: INV-4242
PASS  session_id is a positive integer :: 1700000000
PASS  two line items / shipping / discount
PASS  validation server answered 2xx :: status=200
PASS  no validation messages for our payload :: {"validationMessages": [ ]}
PASS  negative control: invalid event name is rejected :: NAME_INVALID
PASS  negative control: missing client_id is rejected :: VALUE_REQUIRED
PASS  endpoint() / endpoint(true) / region=eu
RESULT: PASS (0 failures)
```

### 4.2 Server sender: consent gate and exact request

`Http::fake()` + an in-memory order:

```
isEnabled=true
no_consent=false sent=0            (Http::assertNothingSent passed)
consent=true
granted=true
url=https://www.google-analytics.com/mp/collect?measurement_id=G-TEST123&api_secret=***
body={"client_id":"…","events":[{"name":"purchase","params":{"transaction_id":"INV-4242",
      "value":2450,"currency":"BDT","items":[{"item_id":"101","item_name":"Frock",
      "price":1190,"quantity":2}],"engagement_time_msec":100,"shipping":80,"discount":100}}]}
debug=true                         (asserted: /debug/mp/collect)
client_id_from_ga_cookie=555.666
```

### 4.3 Browser: 23/23

`node tests/tracking/ga4-browser.mjs` → **exit 0** (gtag.js stubbed at the network
layer so the test measures our code):

```
denied:  __GA4__ undefined, 0 gtag requests, 0 google-analytics requests
granted: 1 gtag request (?id=G-TEST123), config {send_page_view:false,
         transport_url:"https://t.localhost:8000", debug_mode:false},
         consent {analytics_storage:granted, ad_storage:denied, ad_user_data:denied}
initial: PageView -> page_view {page_location:"http://127.0.0.1:8000", page_title:"Home"}
bridge:  AddToCart -> add_to_cart, items [{item_id:"77",price:1250}], page_location set
bridge:  Scroll_50 dropped (unmapped, no invented name)
purchase: purchase with transaction_id "INV-9001", value 2450 BDT, 1 item
marketing granted + analytics denied: still no GA4
RESULT: PASS (0 failures)
```

**Bug found by this test, and fixed:** the GA4 partial was first included after
the event logger. The logger queues `PageView` while it is still initialising, so
`window.__GA4__` did not exist yet and the first page view of every visit was
dropped. The include now sits *before* the logger, and the test asserts that the
initial `page_view` reaches the dataLayer — the assertion exists precisely so
this cannot regress silently.

### 4.4 Both themes

`/privacy/cookies` (the page proven to switch in T2):

```
modern  status=200 ga4=1 gtag=1 fm-consent=39 cookie-page=0
classic status=200 ga4=1 gtag=1 fm-consent=0  cookie-page=2
```

### 4.5 Consent holes (temporary `google_tag_managers` row, deleted afterwards)

| Page | Layout | Denied | Granted |
|---|---|---|---|
| `/about-us` | `frontend.layouts.app` | gtm 0, raw snippet 0, noscript 0 | gtm 2, raw 1, noscript 1 |
| `/customer/forgot-password` | `frontend.layouts.master` | gtm 0, raw 0 | gtm 2, raw 1, id 3 |

`rows_left=0` after the check.

### 4.6 Host derivation

```
no env                                  -> host=null, server=null
APP_URL=https://shop.test + GTM-SRV123  -> https://t.shop.test
TRACKING_SERVER_CONTAINER_URL=https://shop.test/t -> https://shop.test/t
APP_URL=http://localhost:8000 + GTM-SRV123 -> null
```

### 4.7 Regression (T1/T2 suites, unchanged code paths)

```
outbox.mjs   ack-ok {before:1, after:0, collectRequests:1}
             ack-fail {before:1, after:1, collectRequests:1}
dedupe.php   server_batch stored=1; browser_batch duplicates=1; rows=1; cleanup rows=0
consent.mjs  A denied + banner, B granted + cookie, C reject remembered,
             D storage-blocked queue alive, E one console.info, F page controls
```

Scenario B reports `fbAfter=0` now because the Meta pixel id is `NULL` again
(T2 temporarily set a fake id to prove the gate). The gate itself is unchanged.

### 4.8 Static checks

```
php -l  config/tracking.php Ga4.php Ga4TrackingService.php
        CustomerController.php ga4-validate.php              -> clean
Blade compileString: event-layer-head, analytics-ga4,
        fm order_success, classic order_success, master       -> all OK
node --check tests/tracking/ga4-browser.mjs                  -> ok
```

---

## 5. What still needs the owner (cannot be verified here)

| Need | Why | Where it goes |
|---|---|---|
| GTM **web** container id (`GTM-XXXX`) | a real container is required to see tags fire | admin "GTM code" field, or `TRACKING_GTM_ID` |
| GTM **server** container + `CONTAINER_CONFIG` | Google hosts the tagging server; the image is `gcr.io/cloud-tagging-10302018/gtm-cloud-image:stable` and `/healthy` must answer 200 | Google Cloud/Tag Manager, not this repo |
| DNS + TLS for `t.<host>` (option A) or `/t/` routing (option B) | the browser must reach the container on our own host | DNS / web server |
| GA4 measurement id + Measurement Protocol API secret | the server sender and the tag need a real property | `TRACKING_GA4_ID`, `TRACKING_GA4_API_SECRET` |
| Meta `test_event_code` | still needs a settings column; deferred | backlog |

Until those exist, the correct state is what is committed: no tag is printed, no
request is made, and the first-party event log keeps working.

---

## 6. Rollback

- revert `config/tracking.php`, `Ga4.php`, `Ga4TrackingService.php`,
  `analytics-ga4.blade.php`, the five edited views and the controller call;
- remove `TRACKING_*` from `.env` (nothing is printed without them anyway);
- delete `tests/tracking/ga4-validate.php`, `ga4-browser.mjs`;
- no migration, no data change. The temporary `google_tag_managers` row used for
  the consent-hole proof was deleted (`rows_left=0`).

**Verified by:** `php tests/tracking/ga4-validate.php` (17 assertions, 2 negative
controls), `node tests/tracking/ga4-browser.mjs` (23 assertions), `Http::fake`
request inspection, curl on both themes and both layouts with and without the
consent cookie, `outbox.mjs`, `dedupe.php`, `consent.mjs`, `php -l`,
Blade `compileString`, `node --check`.
