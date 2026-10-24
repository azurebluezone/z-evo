# Tracking, consent and AI data layer — plan (2026-10-24)

Owner of this plan: zombiecoder (Chief Arbiter). Audit by the same session.
Implementation is **not** started; this document is the contract a builder works
from. Every number below was measured on `127.0.0.1:8000` today.

**One-line decision:** keep the first-party collector as the single source of
truth, mint **one `event_id` per logical event on the server**, and fan it out to
Meta CAPI and a **Google-hosted server-side GTM** container on our own subdomain.
No Stape, no third-party tag host. AI reads a pseudonymised, read-only copy.

---

## 1. Current state (measured, not assumed)

| Layer | Where | State |
|---|---|---|
| Visitor identity | `app/Http/Middleware/VisitorIdentify.php` | live: UUID cookie (1 year), geo, UA, channel, heartbeat |
| Event ingestion | `POST /events/collect` → `Frontend\EventCollectController` | live, CSRF-exempt, whitelist + 50/batch cap |
| Event store | `event_logs` | **404 rows** (343 browser, 61 legacy null, 0 server) |
| Visitor store | `visitors_master` | **483 rows**; geo filled for **10** |
| Recorder | `app/Services/Analytics/EventRecorder.php` | `event_id` + `insertOrIgnore` dedupe, `dedup_status` column |
| Browser layer | `resources/views/frontend/partials/event-layer-head.blade.php` | included by **both** themes (`layouts/app.blade.php:257`, `fm/layouts.blade.php:286`) |
| Meta browser pixel | same partial `:434-472` | code present, **`facebook_pixel_id` NULL → never renders** |
| Meta CAPI | `app/Services/FacebookTrackingService.php` | code present, `facebook_server_side_tracking=0`, token NULL, API version **`v17.0`** |
| TikTok pixel/CAPI | `tiktok-tracking.js`, `TikTokTrackingService` | code present, `tiktok_pixel_id` NULL |
| GTM | `google_tag_managers` table | **0 rows**; no container injected |
| GA4 | hardcoded in the **classic** layout only | `G-1ME4K0RD7K` at `layouts/app.blade.php:2091`, GTM `GTM-K29C9BKJ` at `layouts/master.blade.php:2519` — demo IDs, not in settings |
| Pixels table | `ecom_pixels` | 0 rows |

Event mix today: `PageView 262, Scroll_25 40, Scroll_50 27, BeginCheckout 20,
AddToCart 17, Purchase 11, Scroll_75 10, FakeOrderAttempt 9`.

Two facts that matter more than the counts:

1. **Zero server-side events have ever been recorded** (`tracking_method=server`
   count 0). Every `dedup_status` is `browser_only` (343) or null (61), i.e. the
   dedupe path has **never been exercised** — it is code, not proof.
2. **`page_path` is NULL on 212 of 404 rows**, so page-level grouping is already
   lossy before any new work starts.

---

## 2. The six questions, answered

### 2.1 Duplicate events between browser and server — **broken today**

The intent is right and the column exists, but the id never survives the trip:

| Path | Where the id comes from | Passed on? |
|---|---|---|
| `trackPurchase()` (JS) | `generateEventId()` inside the method (`facebook-tracking.js:351`) | yes → pixel `eventID`, and posted to `/api/facebook/track` |
| `POST /api/facebook/track` | request body `event_id` | **no** — `routes/api.php:66` calls `sendEvent($eventName, $eventData, $userData)` and **drops `event_id`**, so the service mints a new one |
| `CustomerController@order_save` → `sendPurchaseEvent()` (`:570`) | none | service mints a **third** id (`FacebookTrackingService.php:48`) |

So one purchase can produce: pixel hit (id A) + browser-triggered CAPI (id B) +
controller CAPI (id C). Three ids, three conversions, no dedupe. The comment in
`order_success.blade.php:51` claims "the identical id"; the code does not.

**Rule to adopt:** the id is minted **once, server-side**, before any fan-out.
Browser receives it in the page payload; CAPI and sGTM receive the same value.

### 2.2 Meta official-document compliance — **not compliant**

| Requirement (Meta CAPI for `action_source=website`) | Current code | Verdict |
|---|---|---|
| `event_name`, `event_time` (unix, < 7 days), `event_id`, `action_source` | present (`FacebookTrackingService.php:53-58`) | ok |
| `user_data.em/ph/fn/ln/ct/st/zp/country` **SHA-256, lowercase, trimmed** | present (`:329-357`) | ok |
| `user_data.client_ip_address`, `client_user_agent` | **absent** | fail — match quality |
| `user_data.fbc` / `fbp` (not hashed) | **absent** | fail — click attribution lost |
| `test_event_code` for verification | absent | fail — cannot verify safely |
| Graph API version current | default **`v17.0`** (settings value) | fail — deprecated |
| One event per request, no batching/retry/timeout | `Http::post(...)` no timeout | fail — blocking, silent loss |
| Endpoint not abusable | `/api/facebook/track` is public, unauthenticated, unthrottled, accepts arbitrary `user_data`, and returns `$e->getMessage()` | **security fail** |

### 2.3 Do both frontend templates fire correctly? — **partially**

| Theme | Event layer | Purchase | Ad platforms |
|---|---|---|---|
| modern (`fm`) | yes, `fm/layouts.blade.php:286` | yes, `fm/pages/customer/order_success.blade.php:358-410` | none (pixel id NULL) |
| classic (`layouts/app`, `layouts/master`) | yes, `app.blade.php:257` | yes, `layouts/customer/order_success.blade.php:223-272` | **hardcoded** GA4 `G-1ME4K0RD7K` + GTM `GTM-K29C9BKJ` |

Both themes call `trackPurchase(purchaseData)`, but the payload key is `total`
while `trackPurchase` reads `orderData.amount` (`facebook-tracking.js:354`) →
**`value` is always 0** on the browser Purchase. Classic additionally ships
someone else's measurement IDs, which is both a data leak and a duplicate-source
risk. Measured: `/` and `/hot-deals` contain no `fbq`/`gtag`/`googletagmanager`;
`/offer` (classic) contains `gtag` 3× and `googletagmanager` 1×.

### 2.4 localStorage / btoa blocking — **guarded, but lossy**

`readQueue`/`writeQueue` are wrapped in `try/catch`
(`event-layer-head.blade.php:39-54`), so private mode or a storage-blocking
extension cannot throw into the page. Two real problems remain:

- `flush()` **clears the queue before the request succeeds** (`:268`) and
  `sendBeacon` cannot report a failure → a blocked/rejected POST silently
  destroys the batch.
- Nothing checks `navigator.sendBeacon` availability *before* clearing, and the
  fetch fallback also clears first.
- No `btoa` is used (good — the payload is a JSON `Blob`), so the classic
  "InvalidCharacterError on Unicode" bug does not apply here.

**Rule:** move to a `sent_at`/`ack` outbox. Clear only on HTTP 2xx, keep a cap
(200 events) and a TTL (7 days), drop oldest first.

### 2.5 Origin — **fragile**

- `visitor_id` cookie is set with Laravel's `cookie()` helper
  (`VisitorIdentify.php:50`) → **HttpOnly by default**, so
  `getCookie('visitor_id')` in the browser returns `null`. The collector then
  falls back to `$request->cookie('visitor_id')` (`EventCollectController.php:60`),
  which works only because the request is same-origin.
- Proven: a raw (unencrypted) cookie value is rejected with `422 Invalid
  visitor_id`; a body `visitor_id` is accepted (live probe below).
- The collector accepts a **client-supplied `visitor_id`** with no signature, so
  anyone can write events into another visitor's journey. Integrity gap.
- `origin` is not stored or checked anywhere; `referrer` is captured only on the
  visitor's **first** touch.

**Rule:** keep `visitor_id` HttpOnly, add a short-lived **signed** `track_token`
(HMAC of `visitor_id|session_id|exp`) that the page can read, and verify it on
`/events/collect`. Reject cross-origin POSTs by `Origin`/`Referer` allowlist.

### 2.6 Server-side GTM — **absent; adopt Google's own, not Stape**

No container is configured (`google_tag_managers` 0 rows). Decision: run **sGTM
on Google Cloud (or any VM) behind `t.<domain>`**, provisioned from the Google
container snippet, with our own TLS certificate. Stape is a hosting reseller; it
adds a third party to the trust path for no functional gain, and the user
explicitly excluded it.

Why this shape:

- one origin we control (`https://t.example.com`) for both GA4 and Meta tags;
- the browser talks to our domain, so ad blockers see first-party traffic;
- the container can call Meta CAPI and GA4 from the server, using the same
  `event_id` we already store — one fan-out, no client secrets;
- the access token lives in the container, never in the page (today
  `facebook_conversion_api_token` is printed into `window.facebookTrackingConfig`
  at `event-layer-head.blade.php:458` — a live secret in HTML).

---

## 3. Target architecture

```
browser                        our origin                         external
-------                        ----------                         --------
page view  ── signed token ──► POST /events/collect ──► event_logs (SSOT)
                                     │                      │
                                     │                      ├─► visitor_sessions
                                     │                      └─► outbox (status)
                                     │                              │
                                     │                       worker │ HMAC-signed
                                     │                              ▼
                                     └──────────────► sGTM  t.domain  ──► GA4
                                                          │        └──► Meta CAPI
                                                          └──► TikTok CAPI
admin realtime ◄── read models (visitor_sessions, event_logs, orders)
AI sandbox     ◄── read-only views + pseudonymised ids (no PII)
```

Principles:

1. **One writer.** Only `EventRecorder` writes `event_logs`; the browser never
   writes an ad platform directly.
2. **One id.** `event_id` is minted server-side, stored, and reused by every
   downstream call. The browser gets it, it does not invent it.
3. **Outbox.** Delivery to sGTM/Meta is a queued job with retries and a visible
   status; a failed ad call must never lose the first-party event.
4. **Consent gates the fan-out, not the collection.** We may store a first-party
   event under legitimate interest; we may only forward marketing events after
   consent.

---

## 4. Identity and cookie contract

| Name | Set by | TTL | Flags | Readable by JS | Purpose |
|---|---|---|---|---|---|
| `visitor_id` | `VisitorIdentify` | 365 d | HttpOnly, Secure, SameSite=Lax | no | stable anonymous id (UUIDv4) |
| `session_id` | `VisitorIdentify` | 30 min rolling | HttpOnly, Secure, SameSite=Lax | no | visit grouping (new after 30 min idle) |
| `track_token` | page render | 30 min | **not** HttpOnly, Secure, SameSite=Lax | yes | HMAC(`visitor_id|session_id|exp`) — proves the collector call is ours |
| `consent` | consent page | 180 d | not HttpOnly, SameSite=Lax | yes | `{analytics:bool, marketing:bool, ts, version}` |
| `fbp`/`fbc` | Meta pixel | 90 d | first-party | yes | click attribution, forwarded unhashed |
| `_ga` | GA4 via sGTM | 2 y | first-party | yes | GA client id |

`visitor_id` stays HttpOnly on purpose: the browser never needs to know it, and
keeping it out of JS removes one exfiltration path. `track_token` is the only
thing the page carries, and it expires.

Server-side identity joins:

- `visitors_master.visitor_id` → `event_logs.visitor_id` (already the join key);
- on checkout, `orders.customer_id` + `visitors_master.user_id` link a visitor to
  a customer **only after** the order exists (no pre-login profiling);
- `event_logs.session_id` (new column) makes "this visit" a first-class object.

---

## 5. Event contract

Canonical names stay Meta-compatible (already whitelisted in
`EventCollectController::ALLOWED_EVENT_NAMES`). One table, one meaning:

| Event | Fires when | Owner | Required payload |
|---|---|---|---|
| `PageView` | every full page render | server (on render) | `url, page_path, referrer, title` |
| `ViewContent` | product detail rendered | server | `content_ids, content_type, value, currency` |
| `Search` | search submitted | browser | `search_string` |
| `AddToCart` | cart add confirmed by the server response | server | `content_ids, value, currency, num_items` |
| `InitiateCheckout` | checkout page rendered | server | `content_ids, value, num_items` |
| `AddShippingInfo` | area selected | browser | `shipping_tier` |
| `Purchase` | order row committed | **server only** | `order_id, value, currency, content_ids, num_items` |
| `Scroll_25/50/75/100` | milestone crossed | browser | `scroll_depth` |
| `Outbound_Click` | external link clicked | browser | `outbound_host, click_target` |
| `FakeOrderAttempt` | fraud gate rejects | server | `reason` |

Rules:

- **Purchase is server-owned.** The confirmation page may *display* the receipt;
  it must not be the thing that reports the sale.
- Every event carries `event_id`, `session_id`, `visitor_id`, `occurred_at`
  (ISO-8601), `page_path`, `channel`, `consent_state`.
- `value` is numeric and comes from the DB, never from the DOM.

---

## 6. Dedupe specification

1. `event_id` = 32 hex chars, minted **once** by `EventRecorder::mintEventId()`
   at the moment the logical event happens (order commit, cart add, render).
2. Persist first: `event_logs` unique index on `event_id` (already enforced by
   `insertOrIgnore`, keep it).
3. Enqueue one outbox row per destination (`meta`, `ga4`, `tiktok`) with the
   **same** `event_id`.
4. Worker sends; on 2xx mark `sent`, on 4xx mark `failed` with the provider
   message, on 5xx retry with backoff (max 5, 1 m → 30 m).
5. `dedup_status` becomes meaningful: `browser_only | server_only | matched |
   duplicate | failed`. Today only the first two can ever occur.
6. Acceptance: one purchase → exactly **one** row in `event_logs`, **one** Meta
   event with `event_id=X`, and a replayed worker job produces `duplicate`, not a
   second conversion.

---

## 7. Consent and the custom cookie page

Required because the plan forwards data to Meta/Google.

**Page:** `/privacy/cookies` — modern theme, same card/table language as the rest
of the site. Sections: what we collect, why, the three categories, a table of
every cookie (name, purpose, TTL, first/third party), and a "Change preferences"
button that reopens the panel.

**Categories**

| Category | Default | Gates |
|---|---|---|
| Strictly necessary | on, not toggleable | session, cart, CSRF, `visitor_id` |
| Analytics | off until chosen | first-party event forwarding to GA4 |
| Marketing | off until chosen | Meta/TikTok CAPI fan-out, `fbp/fbc` |

**Blocked-mode matrix** (must be tested, not assumed):

| Storage state | Behaviour |
|---|---|
| localStorage blocked | queue lives in memory for the page life; flush on `visibilitychange`; no console error |
| cookies blocked | collector returns `422`; page continues; one `console.info`, never `error` |
| consent = analytics off | events stored first-party with `consent_state=denied`; outbox rows marked `skipped` |
| consent = marketing off | Meta/TikTok rows never created; GA4 still allowed if analytics on |

Banner: one line + two buttons + "Preferences"; no dark patterns; remember the
choice for 180 days; re-ask when the version changes.

---

## 8. Server-side GTM deployment (Google-hosted, no Stape)

| Step | Detail | Acceptance |
|---|---|---|
| 1 | Create container `Jannat — Server` in the same Google account as GA4 | container id visible in Tag Manager |
| 2 | Provision `https://t.<domain>` with a Google-managed or Let's Encrypt cert | `curl -I https://t.<domain>/healthy` → 200 |
| 3 | Point GA4 config + Meta CAPI tags at the container; store the Meta token **in the container**, never in the page | page source contains no `access_token` |
| 4 | Allowlist the container origin to our storefront domains only | a request from another origin is rejected |
| 5 | Send `event_id` from the outbox into the container as a tag parameter | Meta Events Manager shows one event per purchase, `event_id` matching `event_logs` |
| 6 | Keep a fallback: if sGTM is down, the outbox retries; the first-party event is already safe | worker backlog visible in admin |

Cost note: one `e2-micro`/`e2-small` VM is enough at this traffic; the container
is stateless. No vendor markup.

---

## 9. Admin experience (the pages must look as good as they work)

Reuse the existing admin design system — `x-admin.card`, the shared
`backEnd/partials/datatable`, `ri-*` icons, the same stat-card pattern used on the
Flash Sale page. No new CSS framework.

### 9.1 Live now (`/admin/analytics/live`)

- **4 stat cards**: visitors now (5-min window), events/min, top page, top
  channel — each with a delta vs the previous hour.
- **Live table**: time, visitor (short id), page, channel, device, ISP, last
  event — newest first, 25 rows, pause-on-hover.
- **Map card**: Leaflet/OSM (no API key) with one circle per `division`, radius by
  visitor count; clicking a circle filters the table.
- Empty state: "No visitors in the last 5 minutes." — not a blank table.

### 9.2 Visitors (`/admin/visitors/list`)

- Filters: channel, division, device, consent state, has-order.
- Columns: visitor, first seen, last seen, pages, events, channel, ISP, orders.
- Row → **Visitor 360**.

### 9.3 Visitor 360 (`/admin/analytics/visitor/{visitorId}`)

- Header card: id, first/last seen, geo, device, channel, consent.
- **Journey timeline**: every event with its `event_id`, page, and time; the
  Purchase node links to the order.
- **Funnel strip**: PageView → ViewContent → AddToCart → Checkout → Purchase with
  drop counts and the biggest gap highlighted.
- **"Why no purchase" card**: rule-based, explainable — e.g. "left at shipping
  step", "price viewed twice, no cart", "returned 3× in 24 h". No AI here yet.

### 9.4 Journey / funnel (`/admin/analytics`)

- Sankey or stacked bar by channel → landing page → exit page.
- Table: landing page, visitors, bounce (single PageView), cart rate, purchase
  rate, revenue.
- Date range + channel filter, CSV export.

**Visual acceptance for every page:** no horizontal scroll at 1440 and 390, zero
zero-size icons, loading skeleton, empty state, error state with retry, and
keyboard-reachable filters.

---

## 10. Real-time analytics: what "estimate" means

"কেন কিনলো না" is not a fact we can store; it is an inference we must label as
such. The page shows three layers, clearly separated:

1. **Observed** (facts): counts, pages, timings, drop step — from `event_logs`.
2. **Derived** (arithmetic): conversion rate, cart-abandon rate, median time to
   purchase.
3. **Estimated** (heuristic): "likely reason" from a fixed rule table
   (abandoned at shipping, returned without cart, single-page bounce, price
   compared). Each estimate carries the rule name and the evidence rows.

SQL shape (read model, refreshed every 60 s):

```sql
-- visitors per division, last 24h
SELECT division, COUNT(DISTINCT visitor_id) AS visitors
FROM visitors_master
WHERE last_seen_at >= NOW() - INTERVAL 24 HOUR
GROUP BY division ORDER BY visitors DESC;

-- funnel per session
SELECT session_id,
       MAX(event_name = 'PageView')        AS viewed,
       MAX(event_name = 'ViewContent')     AS product,
       MAX(event_name = 'AddToCart')       AS carted,
       MAX(event_name = 'InitiateCheckout')AS checkout,
       MAX(event_name = 'Purchase')        AS bought
FROM event_logs
WHERE occurred_at >= NOW() - INTERVAL 24 HOUR
GROUP BY session_id;
```

---

## 11. AI sandbox (read-only, pseudonymised)

The AI layer is **not** implemented now; the data shape must not block it.

| Layer | Rule |
|---|---|
| Source | `event_logs`, `visitor_sessions`, `orders` (aggregates), product catalogue |
| Copy | nightly materialised views in a separate schema (`ai_sandbox`) |
| PII | `visitor_id` → `pseudo_id` (HMAC with a rotating key); no email, phone, address, payment data |
| Access | DB user `ai_reader` with `SELECT` only on `ai_sandbox.*`, no `event_logs` |
| Consumers | future MCP tools (Meta Ads, GA4) read the same schema, never production |
| Guardrail | AI output is a *recommendation*; every write path stays human-triggered (matches `work/AI Feature.md` §"AI as an Enabler") |

Minimum viable sandbox tables: `ai_sandbox.sessions` (pseudo_id, channel,
division, device, pages, duration, converted), `ai_sandbox.funnel_daily`
(day, step, count), `ai_sandbox.product_interest` (product_id, views, carts,
purchases, revenue). No free-text, no identifiers.

---

## 12. Phases and acceptance

| Phase | Work | Closes when |
|---|---|---|
| **P0** (1 day) | Fix the live defects: `event_id` passed through `/api/facebook/track`, `sendPurchaseEvent` takes an id, `value` reads `total`, remove hardcoded `G-1ME4K0RD7K`/`GTM-K29C9BKJ`, throttle + shared-secret the public track endpoint | one purchase → one `event_logs` row, one Meta event, no hardcoded ids in any view |
| **P1** (2–3 days) | Outbox + `session_id` + signed `track_token` + `page_path` always set; consent state on every event | replay test produces `duplicate`; 0 rows with NULL `page_path` in a 24 h window |
| **P2** (2–3 days) | Consent page + banner + blocked-mode matrix; `/events/collect` origin allowlist | matrix table from §7 passes in Chromium with storage blocked |
| **P3** (3–4 days) | sGTM container on `t.<domain>`; GA4 + Meta tags; token out of the page | Meta Events Manager shows deduplicated pairs; page source has no token |
| **P4** (3–4 days) | Admin: Live, Visitors, Visitor 360, Journey pages + map | visual + a11y acceptance from §9; 1440/390 clean |
| **P5** (later) | `ai_sandbox` views + `ai_reader` user | `SELECT` works, `INSERT` denied, no PII column present |

P0 is independent of everything else and can ship this week.

**Status:** P0 (= T0 in the backlog) is **done and committed**. Implementation
record, evidence and rollback: `docs/IMPL-tracking-t0-2026-10-24.md`. Measured
result — one purchase produced exactly one `event_logs` row
(`status=primary method=server matched=browser`), the relay rejects unknown
events and non-shape ids, and `/`, `/offer`, `/hot-deals` no longer contain
`G-1ME4K0RD7K`, `GTM-K29C9BKJ` or `googletagmanager`. P1 is next.

---

## 13. Risks and open decisions

| Risk | Impact | Mitigation |
|---|---|---|
| No pixel/token configured | nothing to verify against | use Meta **Test Events** code, not production |
| `visitor_id` is client-supplied to the collector | event poisoning | signed token (P1) + rate limit per IP |
| Public `/api/facebook/track` | quota burn, fake conversions | remove the route; sGTM replaces it (P0/P3) |
| Geo empty for 473/483 visitors | map and division report are blind | check the geo DB path before building the map |
| `page_path` NULL on half the rows | page report wrong | P1 |
| Hardcoded demo GA/GTM ids in classic | data goes to a stranger's property | P0 |
| Two themes | double maintenance | one partial (already true) + one test per theme |

**Open for the owner:** final domain for `t.` (needs DNS), whether GA4 is required
at all (the shop may only want Meta), and the consent copy in Bengali.

---

## 14. Evidence appendix

- `php artisan route:list | grep -iE "visitor|event|pixel|analytics|track"` — collector, analytics, pixel and visitor routes exist.
- `describe visitors_master|event_logs|ecom_pixels` + counts: 483 / 404 / 0.
- `select tracking_method, count(*)` → `browser=343, null=61`; `dedup_status` → `browser_only=343, null=61`; `server` = 0.
- `select page_path is null` → 212 of 404.
- `general_settings`: `facebook_pixel_id=NULL`, `facebook_conversion_api_token=NULL`, `facebook_conversion_api_version=v17.0`, `facebook_server_side_tracking=0`, `tiktok_pixel_id=NULL`.
- `google_tag_managers=0`, `social_media=0`, `ecom_pixels=0`.
- `curl /` `/hot-deals` → no `fbq`/`gtag`; `curl /offer` → `gtag` 3×, `googletagmanager` 1× (classic hardcoded ids).
- Live collector probe (test visitor, deleted after): `POST /events/collect` with body `visitor_id` → `200 {"saved":1,"duplicates":0}`, row `PageView/browser/browser_only`, `page_path='/probe'`; cleanup `events_left=0 visitor_left=0`. A raw cookie without the body id → `422 Invalid visitor_id` (cookie is encrypted/HttpOnly).
- Code anchors: `event-layer-head.blade.php:39-54,112-128,252-286,434-472`;
  `facebook-tracking.js:176-196,350-364`; `FacebookTrackingService.php:48-66,329-357`;
  `routes/api.php:54-81`; `CustomerController.php:569-576`;
  `layouts/app.blade.php:2091,2099`; `layouts/master.blade.php:2519`;
  `fm/layouts.blade.php:286`; `fm/pages/customer/order_success.blade.php:358-410`.

**Verified by:** the commands above, run on 2026-10-24 against
`http://127.0.0.1:8000` and the local MySQL schema; no ad-platform call was made
(nothing is configured), so every Meta/GA4 statement is a code-level finding, not
a live probe.
