# Tracking T0 — implementation record (2026-10-24)

Scope: the four defects that made browser/server deduplication impossible, plus
the secrets and demo ids that were leaking into the page. **No new tables, no new
columns** — every change reuses `event_logs.event_id` / `dedup_status`, which is
exactly what the AI sandbox plan needs to read later.

Plan this implements: `docs/PLAN-tracking-2026-10-24.md` §12 phase **T0**.

---

## 1. What changed

| # | File | Change |
|---|---|---|
| 1 | `app/Support/EventId.php` *(new)* | `EventId::purchase($order)` — 32-hex id derived from `id + invoice_id`. Deterministic, so controller, page and any retry compute the same value without storing it. |
| 2 | `app/Http/Controllers/Frontend/CustomerController.php` | Purchase id minted once at commit; `sendPurchaseEvent(..., $purchaseEventId)`; first-party `Purchase` row written through `EventRecorder` (`tracking_method=server`) **before** any ad-platform call; the old `EventLog::create()` (which wrote a row with no `event_id` and no method — the source of the 61 legacy null rows) is gone. `order_success()` passes the id to the view only for the visit that follows checkout (`Session::pull('recent_order_<id>')`). |
| 3 | `app/Services/FacebookTrackingService.php` | `sendPurchaseEvent()` accepts `?string $eventId`; `event_source_url` uses the referer instead of the API endpoint; `Http::connectTimeout(4)->timeout(8)`; `fbp` / `fbc` forwarded raw (unhashed, per Meta) from the raw cookie jar; PII hashing unchanged. |
| 4 | `public/assets/js/facebook-tracking.js` | `trackPurchase(orderData, eventId)`; `value` reads `total ?? amount` (the browser Purchase was always **0** because the views send `total`); the same id goes to the pixel, the relay and the local log; `logLocalEvent()` forwards the id into `__EVENT_LOGGER__.queue`. |
| 5 | `resources/views/frontend/partials/event-layer-head.blade.php` | `queueEvent(name, meta, eventId)` uses a caller-supplied id when present; `accessToken` removed from `window.facebookTrackingConfig` (a live ad-account secret in every page source). |
| 6 | `app/Http/Controllers/Api/FacebookTrackController.php` *(new)* | Replaces the inline closure. Whitelists event names, validates `event_id` shape, caps payload size, forwards the id, never returns provider/exception text. |
| 7 | `routes/api.php` | `/api/facebook/track` → controller + `throttle:30,1`. |
| 8 | `app/Support/TrackingEvents.php` *(new)* | One vocabulary for the collector and the relay, so the two whitelists cannot drift. `EventCollectController` now delegates to it. |
| 9 | `resources/views/frontend/layouts/app.blade.php` | Hardcoded GA4 `G-1ME4K0RD7K` block removed (it belonged to the theme author, not this shop). |
| 10 | `resources/views/frontend/layouts/master.blade.php` | Hardcoded `GTM-K29C9BKJ` noscript iframe removed; GTM now comes only from `google_tag_managers`. |
| 11 | `resources/views/frontend/{layouts/customer,fm/pages/customer}/order_success.blade.php` | Pixel block renders only when `$purchaseEventId` is present; `tracker.trackPurchase(data, id)` and the `fbq` fallback pass `{eventID: id}`. |
| 12 | `app/Http/Controllers/Admin/FacebookTrackingController.php` + `backEnd/facebook-tracking/index.blade.php` | Token stays **settable from admin**, but the field is `type=password`, never pre-filled, and blank means "keep the stored token". API version is a free-text `v\d+\.\d+` instead of a hardcoded v15–v17 list. |

---

## 2. Why this shape (and not a separate container)

Server-side tracking does not require a cloud vendor or a separate container. The
"server side" is simply **our runtime** calling `graph.facebook.com` with the
token that never reaches the browser. That is what `FacebookTrackingService`
already did; T0 only made the id shared and the call bounded.

A Google-hosted sGTM container stays optional (plan §8) and would sit **in front
of** this service, not replace it: the container is a runtime too — ours or
Google's, same mechanism, different host. Nothing in T0 depends on that decision,
which is why T0 could ship today.

---

## 3. Evidence

### 3.1 Dedupe, proven against the real recorder

Test order `#2` (invoice `90050`), temporary visitor, deleted afterwards:

```
order=2 invoice=90050 id=5e8ee2cb297cd69df00212b5edd225a1 deterministic=yes len=32
server_batch={"stored":1,"duplicates":0,"rejected":[]}
browser_batch={"stored":0,"duplicates":1,"rejected":[]}
rows=1 status=primary method=server matched=browser page_path=/checkout
cleanup rows=0 visitor=0
```

One row, marked `primary`, matched against the browser copy. Before T0 the two
paths produced three unrelated ids and `dedup_status` could only ever be
`browser_only`.

### 3.2 The public relay

| Request | Result |
|---|---|
| `event_name=HACKED_EVENT` | `422 {"error":"Unsupported event name"}` |
| `event_name=Purchase`, tracking disabled | `400 {"error":"Facebook tracking is disabled"}` |
| `event_name=Purchase`, `event_id="bad id!"` | `422 {"error":"Invalid event_id"}` |
| 34 rapid POSTs from one IP | `27 × 400` then `7 × 429` (limiter counts the earlier probes: 30/min) |

### 3.3 Demo ids are gone

`curl /`, `/offer`, `/hot-deals` → `G-1ME4K0RD7K 0`, `GTM-K29C9BKJ 0`,
`googletagmanager 0`, `fbq( 0`. `/offer` used to carry `gtag` 3× and
`googletagmanager` 1×.

### 3.4 The pixel block is conditional

Rendered both branches of `frontend.fm.pages.customer.order_success` with a real
order:

```
with-id: id_present=yes trackPurchase=yes eventID=yes
no-id:   id_present=no  trackPurchase=no  eventID=no
```

Live: `GET /customer/order-success/2` → `200`, `eventID 0`, `trackPurchase 0`
(no session marker, i.e. a shared or refreshed URL cannot re-fire a Purchase).

### 3.5 Admin

`GET /admin/facebook-tracking` → `200`, token input `type=password`, `value=''`,
placeholder `Paste the Conversions API token`, version input `v17.0`,
`scrollWidth 1440 / innerWidth 1440`, 0 console errors.

### 3.6 Static checks

```
php -l  app/Support/EventId.php  app/Support/TrackingEvents.php
        app/Http/Controllers/Api/FacebookTrackController.php
        app/Http/Controllers/Frontend/CustomerController.php
        app/Http/Controllers/Frontend/EventCollectController.php
        app/Http/Controllers/Admin/FacebookTrackingController.php
        app/Services/FacebookTrackingService.php  routes/api.php     → all clean
node --check public/assets/js/facebook-tracking.js                    → ok
blade compileString on 6 changed views                                → all OK
```

---

## 4. Not done (stays in the plan)

| Item | Phase |
|---|---|
| Outbox with ack (queue is still cleared before the POST succeeds) | T1 |
| `session_id`, signed `track_token`, origin allowlist | T1/T2 |
| Consent page + blocked-mode matrix | T2 |
| Google-hosted sGTM on `t.<domain>` | T3 |
| Admin Live / Visitors / Visitor 360 / Journey pages | T4 |
| `ai_sandbox` read-only views | T5 |
| Meta `test_event_code` | needs a settings column; deliberately deferred to avoid a schema change |

Also unchanged on purpose: `TikTokTrackingService::sendPurchaseEvent()` still
mints its own id. TikTok dedupe was out of T0 scope; when it is picked up it
should take the same `$purchaseEventId`.

---

## 5. Rollback

Every change is additive or a deletion of a dead snippet:

- revert `app/Support/EventId.php`, `TrackingEvents.php`, `FacebookTrackController.php`;
- `git checkout` the six edited files;
- the only data touch was the temporary visitor/event rows created by the test,
  already deleted (`rows=0 visitor=0`).

**Verified by:** `php /tmp/opencode/dedupe.php`, `/tmp/opencode/render2.php`,
`/tmp/opencode/bladecheck.php`, `node --check`, `php -l`, `curl` on `/`,
`/offer`, `/hot-deals`, `/customer/order-success/2`, `POST /api/facebook/track`,
and Playwright on `/admin/facebook-tracking`.
