# Tracking T2 — implementation record (2026-10-24)

Scope: consent (`docs/PLAN-tracking-2026-10-24.md` §7) plus the collector origin
allowlist from the same phase. **No new table, no new column** — the choice lives
in one first-party cookie, and every stored event carries it inside the existing
`event_logs.event_details` JSON.

Depends on T0/T1: `docs/IMPL-tracking-t0-2026-10-24.md`,
`docs/IMPL-tracking-t1-2026-10-24.md`.

---

## 1. What changed

| # | File | Change |
|---|---|---|
| 1 | `app/Support/Consent.php` *(new)* | Reads the `consent` cookie, versioned (`v=1`), defaults **denied**. `analytics()` / `marketing()` / `forEvent()`. An old version is treated as "no decision", so a policy change re-asks instead of silently reusing an old answer. |
| 2 | `app/Http/Middleware/EncryptCookies.php` | `consent` excluded from encryption: the banner writes it in plain JS, and it holds two booleans, not a secret. |
| 3 | `resources/views/frontend/partials/consent-banner.blade.php` *(new)* | Banner + preference panel, injected as DOM so both themes and every layout that already includes `event-layer-head` get it with zero layout edits. Equal-weight Accept/Reject, nothing pre-ticked, focus moved into the panel, `role=dialog`. |
| 4 | `resources/views/frontend/partials/event-layer-head.blade.php` | Renders `window.__CONSENT__`; prints the Meta pixel, TikTok pixel and GTM container **only** when the matching category is granted; memory-queue fallback when `localStorage` throws; one `console.info` when the collector rejects (422) or cookies are blocked; `navigator.cookieEnabled === false` short-circuits the flush. |
| 5 | `resources/views/frontend/{fm/pages,pages}/privacy-cookies.blade.php` *(new)* | `/privacy/cookies` in both themes: current state, three categories, a table of the nine cookies actually set, and controls wired to the same `__CONSENT_UI__` the banner uses. Works as a document without JS. |
| 6 | `app/Http/Controllers/Frontend/ConsentController.php` *(new)* + `routes/web.php` | `GET /privacy/cookies` → `consent.page`, inside the `visitor_identify` group so the page has the same identity as the rest of the shop. |
| 7 | `app/Http/Controllers/Frontend/EventCollectController.php` | Origin allowlist (`Sec-Fetch-Site`, then `Origin`, then `Referer`); every event gets `consent` from the **cookie**, never from the request body. |
| 8 | `app/Services/FacebookTrackingService.php`, `TikTokTrackingService.php` | Fan-out refuses to run without marketing consent, before any HTTP call. First-party storage is untouched. |
| 9 | `public/assets/js/facebook-tracking.js` | `marketingAllowed()` guard on both `sendClientEvent` and `sendServerEvent` — the second lock, in case a tab was rendered before the choice changed. |

---

## 2. Decisions and why

**Denied by default, and the server is the judge.** The cookie is readable by JS
(it has to be), so a script could claim consent. It cannot: the collector reads
the cookie itself and overwrites whatever the body said, and the services read the
same cookie. The body has no vote.

**A grant reloads once; a rejection never does.** Vendor scripts are printed by
Blade, and a pixel cannot be un-printed. Reloading on grant means one code path
(server-rendered tags) instead of two, and no double-init. Rejection changes
nothing that was already loaded, so it closes immediately.

**First-party events are not gated.** §7 requires the funnel to stay honest:
events are stored with `consent_state=denied`, and only the fan-out to Meta /
TikTok / GA4 is blocked. Gating storage would have produced an empty dashboard and
a false "no traffic" reading.

**Origin allowlist order.** `Sec-Fetch-Site: cross-site` is a hard no (the browser
states it); then `Origin`; then `Referer`; if neither header exists the caller is
not a browser, and there is no identity to steal (a random UUID buys nothing), so
it is allowed rather than breaking CLI probes. A cross-site attacker always sends
their own origin, which is not in the list.

---

## 3. Evidence

### 3.1 Blocked-mode matrix (Playwright, `tests/tracking/consent.mjs`)

| Scenario | Result |
|---|---|
| **A** first visit, no decision | `marketing=denied decided=false`, banner in DOM, **0** requests to `connect.facebook.net` |
| **B** click "Accept all" | reload → `analytics=granted marketing=granted decided=true`, banner gone, `connect.facebook.net` requests **2** (`fbevents.js` + `/tr`) |
| **C** click "Reject non-essential" | `marketing=denied`, banner gone, **0** pixel requests; still `denied` after a manual reload (cookie remembered) |
| **D** `localStorage` getter throws | no page error, `__EVENT_LOGGER__` alive, token present — queue moved to memory |
| **E** cookies cleared before flush | collector `422`, exactly one `console.info` ("Tracking paused…"), page continues |
| **F** `/privacy/cookies` | banner suppressed on this page, 3 controls (`reject`, `prefs`, `accept`), panel opens with **both toggles unchecked** |

The only console error in every scenario is the pre-existing
`404 /uploads/category/default.png`, unrelated to tracking.

### 3.2 Origin allowlist (`POST /events/collect`)

| Request | Result |
|---|---|
| no browser headers | `200` |
| `Origin: http://127.0.0.1:8000` + `Sec-Fetch-Site: same-origin` | `200` |
| `Origin: https://evil.example` + `Sec-Fetch-Site: cross-site` | `403 Origin not allowed` |
| `Origin: https://evil.example` (no Sec-Fetch-Site) | `403` |
| `Referer: https://evil.example/x` | `403` |

No row was written for any rejected request (`evil_rows=0`).

### 3.3 Consent is stored per event, from the cookie

```
status=200 {"message":"OK","saved":1,"duplicates":0}
row=yes page_path=/ details={"session_id":"b1a40766-…","consent":{"analytics":"granted","marketing":"denied","v":1}}
```

### 3.4 Server-side fan-out gate

With pixel id + token + `facebook_server_side_tracking=1` temporarily set and
`Http::preventStrayRequests()` armed:

```
isEnabled=true
marketing_consent=false
sendEvent_return=false   (false and no exception = no HTTP call was attempted)
restored pixel=NULL token=NULL enabled=0
```

### 3.5 Both themes

`/privacy/cookies` with `frontend_theme=modern` → `fm-consent` markup;
switched to `classic` → `cookie-page` markup, `fm-consent` count 0; theme restored
to `modern` immediately after.

### 3.6 Static checks

```
php -l  Consent.php ConsentController.php EncryptCookies.php
        EventCollectController.php FacebookTrackingService.php
        TikTokTrackingService.php routes/web.php        → all clean
blade compileString on the 4 changed/new views         → all OK
inline <script> from the rendered cookie page          → node --check ok
php artisan route:list | grep privacy/cookies           → GET privacy/cookies consent.page
```

---

## 4. Still open

| Item | Phase |
|---|---|
| sGTM container on `t.<APP_URL host>`, GA4 tag, `_ga` cookie | T3 |
| Admin Live / Visitors / Visitor 360 / Journey + map | T4 |
| `ai_sandbox` read-only views | T5 |
| `consent` surfaced as a filter in the admin reports | with T4 |
| Cookie-page copy review by the owner (legal wording) | owner |

Note: `_ga` and `_fbp` appear in the table because the plan forwards to GA4/Meta;
neither is set until T3 ships and consent is granted. The table describes the
target state, not today's traffic.

---

## 5. Rollback

- revert the two new views, `consent-banner.blade.php`, `Consent.php`,
  `ConsentController.php`, the route and the six edited files;
- delete the `consent` cookie if a clean slate is wanted
  (`document.cookie = 'consent=;path=/;max-age=0'`);
- no migration, no data change. Test rows were deleted after every check.

**Verified by:** `tests/tracking/consent.mjs` (six scenarios), `curl` origin
matrix, `php artisan tinker` row inspection, `Http::preventStrayRequests()` gate
test, theme switch, `php -l`, Blade `compileString`, `node --check`.
