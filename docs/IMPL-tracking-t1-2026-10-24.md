# Tracking T1 — implementation record (2026-10-24)

Scope: the four P1 defects from `docs/PLAN-tracking-2026-10-24.md` §12 — the
outbox that lost events, the missing `session_id`, the unsigned collector, and
the NULL `page_path` rows. **No new table, no new column**: `session_id` rides in
the existing `event_logs.event_details` JSON, which is exactly the "no schema
churn" constraint the AI sandbox plan needs.

T0 (the dedupe id) shipped first: `docs/IMPL-tracking-t0-2026-10-24.md`.

---

## 1. What changed

| # | File | Change |
|---|---|---|
| 1 | `app/Support/TrackToken.php` *(new)* | `issue()` / `verify()` — HMAC-SHA256 over `visitor_id|session_id|exp` keyed with `app.key`. The token payload carries **only** the expiry; the ids stay in the HttpOnly cookies. 30 min TTL. |
| 2 | `app/Http/Middleware/VisitorIdentify.php` | Issues `session_id` (UUID, HttpOnly, 30 min cookie) and re-queues it on every request so the window rolls forward with no server-side store. Sets both ids as request attributes for the render. |
| 3 | `resources/views/frontend/partials/event-layer-head.blade.php` | Prints `window.__TRACK__ = {token, exp, page_path, server_url}` (no ids). `queueEvent()` now always sets `page_path`; `PageView` carries `title` + `referrer`. |
| 4 | same partial — `flush()` | **Outbox with ack**: `fetch(keepalive)` first, `sendBeacon` only as a fallback, and the queue is trimmed **after** the server acknowledges. `inFlight` guard stops two flushes deleting each other's rows. Bounded batch (5 events). |
| 5 | `app/Http/Controllers/Frontend/EventCollectController.php` | Verifies `track_token`; a valid token makes the cookies authoritative and the body `visitor_id` is ignored; a body id that disagrees with the cookie and has no token is rejected (422). Attaches `session_id` to every event and guarantees a `page_path` (explicit → URL → `/`). |
| 6 | `app/Services/Analytics/EventRecorder.php` | `pickPagePath()` prefers an explicit `page_path` over parsing the URL; `page_path` removed from the `event_details` blob so it is not stored twice. |
| 7 | `app/Services/FacebookTrackingService.php` | Server-side `recordLocally()` writes `page_path` + `session_id` too, so browser and server rows agree. |
| 8 | `app/Http/Controllers/Frontend/CustomerController.php` | The server `Purchase` row carries `session_id`. |

---

## 2. Why the token carries no ids

Base64 is not encryption. The first version embedded `{v, s, e}` and the rendered
page immediately exposed the HttpOnly `visitor_id` to any script that could read
the DOM — the exact exfiltration path HttpOnly exists to close. The final shape is
`base64({"e": exp}) . hmac(visitor|session|exp)`: the collector already holds both
cookies, so it can recompute the MAC, and the page learns nothing but the expiry.

Consequence, by design:

- valid token → cookies win, body `visitor_id` is ignored;
- no token (cached page, cookie-blocked browser) → accepted, body id allowed only
  when there is no cookie to contradict it;
- invalid/expired token → `422`.

---

## 3. Evidence

### 3.1 Rendered page carries a token and no identifiers

```
GET / → 200
window.__TRACK__ token payload (base64-decoded) = {"e":1792825122}
token_len = 66
'visitor' appears inside the __TRACK__ block = False
```

`Set-Cookie` headers:

```
visitor_id   Max-Age=31536000; path=/; httponly; samesite=lax
session_id   Max-Age=1800;     path=/; httponly; samesite=lax
```

`document.cookie` in Chromium = `XSRF-TOKEN=…` only — neither id is readable from JS.

Both themes render the token: `/` (fm), `/hot-deals` (fm), `/offer` (classic) →
`window.__TRACK__ 4`, `token: "` 1 each.

### 3.2 Collector: accept, replay, reject

| Request | Result |
|---|---|
| valid token + cookie, new `event_id` | `200 {"saved":1,"duplicates":0}` |
| same payload again (replay) | `200 {"saved":0,"duplicates":1}` — **one row only** |
| forged body `visitor_id`, no token | `422 {"message":"visitor_id mismatch"}` |
| `track_token=aaa.bbb` | `422 {"message":"Invalid track_token"}` |
| valid token + forged body `visitor_id` | `200` and the row belongs to the **cookie** visitor |
| legacy: no token, no cookie, body id | `200`, accepted |

Database after the run (test rows deleted afterwards):

```
rows=1
visitor=3fd4328e-5ab4-4a5a-96db-de131a713a4c
page_path=/
details={"session_id":"3e3971c4-5bf9-43db-92d9-f72d30bcaa64"}
status=browser_only matched=browser
```

`session_id` is stored in `event_details` — no migration.

### 3.3 `page_path` is never NULL, even for legacy payloads

`Scroll_25` sent with only `url=http://127.0.0.1:8000/offer?x=1` and no
`page_path` → stored `page_path=/offer` (query stripped). Every client-side row
now sends the column explicitly, so the NULL source is closed at both ends.

### 3.4 Outbox: success drains, failure keeps

Playwright on `/`, reading `localStorage.__event_queue__`:

```
ack-ok   {"before":1,"after":0,"collectRequests":1,"track":{"hasToken":true,"path":"/"}}
ack-fail {"before":1,"after":1,"collectRequests":1,"track":{"hasToken":true,"path":"/"}}
```

With `/events/collect` aborted the queue is **kept** (`after:1`); the old code
cleared it before the request and lost the batch. Replaying a kept batch is safe
because `event_id` is unique — proven in §3.2 (`duplicates:1`, still one row).

### 3.5 Static checks

```
php -l  TrackToken.php VisitorIdentify.php EventCollectController.php
        CustomerController.php FacebookTrackingService.php EventRecorder.php → all clean
blade compileString event-layer-head.blade.php                            → OK
inline <script> extracted from the rendered page → node --check            → ok
```

Only browser console error on `/` is a pre-existing missing asset,
`404 /uploads/category/default.png` — unrelated to tracking.

---

## 4. Still open (from the plan)

| Item | Phase |
|---|---|
| Consent page, banner, blocked-mode matrix | T2 |
| `/events/collect` origin allowlist + rate limit | T2 |
| sGTM container on `t.<domain>`, GA4 + Meta tags | T3 |
| Admin Live / Visitors / Visitor 360 / Journey | T4 |
| `ai_sandbox` read-only views | T5 |
| `session_id` promoted to a real column | only if a query needs it; JSON is enough today |
| Meta `test_event_code` | needs a settings column — still deferred |

---

## 5. Rollback

- revert `app/Support/TrackToken.php` and the six edited files;
- the `session_id` cookie disappears on its own within 30 minutes;
- no data migration to undo. Test rows were deleted
  (`deleted_events=3 deleted_visitors=1 remaining_t1=0`).

**Verified by:** `curl` with a cookie jar on `/`, `/hot-deals`, `/offer`;
`POST /events/collect` in six variants; `php artisan tinker` row inspection;
Playwright `outbox.mjs` (ack + failure), `cookie.mjs` (HttpOnly proof),
`r404.mjs`; `php -l`, Blade `compileString`, `node --check`.
