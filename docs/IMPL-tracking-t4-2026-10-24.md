# T4 — Admin experience, part 1 (Live now) + client data request

Date: 2026-10-24 · Owner: zombiecoder (Chief Arbiter) · Commit: _(see git log)_

This record covers two deliverables:

1. **T4.1 — `/admin/analytics/live`** becomes a real page (it used to be JSON
   only), with four stat cards, a 25-row live feed, and a Leaflet/OSM map by
   division.
2. **`work/client-data-request.html`** — the client-facing data request page that
   goes to Jannat Mini Mart, so the owner-blocked credentials listed in
   `IMPL-tracking-t3-2026-10-24.md` §5 can actually be collected.

T4.2 (Visitors list), T4.3 (Visitor 360) and T4.4 (Journey/funnel) are **not**
done. They are the next plan, in §6 below.

---

## 1. The route contract (why one route, two answers)

`GET /admin/analytics/live` already existed and the dashboard polls it with
`fetch(..., { headers: { Accept: 'application/json' } })`
(`backEnd/analytics/partials/scripts.blade.php:192`).

So the route now branches on `$request->expectsJson()`:

| Caller | Gets |
|---|---|
| dashboard poller (`Accept: application/json`) | the same JSON as before, plus `stats` and `map` |
| a browser | `backEnd.analytics.live` (the new Blade page) |

Why not a second endpoint: two endpoints would mean two filter resolvers and two
chances for the cards and the table to disagree. The controller's stated contract
("every figure is computed after applying the same filter set") is preserved by
sharing one payload.

The JSON keeps `rows`, `live_visitors`, `server_time` untouched, so the existing
dashboard is not a regression — it simply gains two keys.

---

## 2. What changed

| # | File | Change |
|---|---|---|
| 1 | `app/Http/Controllers/Admin/AnalyticsDashboardController.php` | `live()` returns the page for browsers; new `liveStats()`, `liveMap()`, `divisionCounts()`, `liveEventQuery()`; `presentLiveRow()` now exposes `division`, `city`, `time_hms`; `liveData()` limit 60 → **25** (plan §9.1). |
| 2 | `app/Services/Analytics/BangladeshGeo.php` | new `DIVISION_CENTERS` (8 division HQ coordinates) — one source for the map. |
| 3 | `resources/views/backEnd/analytics/live.blade.php` *(new)* | The page: filter bar, 4 stat cards, live table, map card, division chips, empty/error states, polling JS. |
| 4 | `resources/views/backEnd/layouts/partials/sidebar.blade.php` | "Real-time Analytics" is now a group: **Overview** + **Live now**. |
| 5 | `public/admin/assets/vendor/libs/leaflet/` *(new, vendored)* | Leaflet 1.9.4 `leaflet.js` + `leaflet.css` + `LICENSE` (BSD-2-Clause). No CDN at runtime. |
| 6 | `tests/Feature/AdminLiveAnalyticsPageTest.php` *(new)* | 5 tests / 91 assertions: page renders, script lands inside the document, JSON contract, map point shape, `division`/`city` on rows. |
| 7 | `work/client-data-request.html` *(new)* | The client data request page (10 groups, 40 items, 70 fields). |

No migration, no schema change, no new config key, no new dependency in
`package.json` / `composer.json`.

---

## 3. Decisions and why

**The five-minute window, and what "delta" means.**
The plan says "each with a delta vs the previous hour". A 5-minute count compared
with a full hour is meaningless (every delta would be −90%), so the comparison
window is **the same 5 minutes, one hour earlier** (`now-65m … now-60m`). The
card prints the previous value next to the delta, so the reader can check the
arithmetic instead of trusting a percentage.

**The map falls back to today, and says so.** With no events in 5 minutes the map
would be blank, which reads as "broken map". `map.scope` is therefore either
`live` or `today`, and the card subtitle changes with it. The fallback is
explicit in the payload, not a silent substitution.

**Division HQ points, not IP-derived cities.** `DIVISION_CENTERS` is a fixed
point per division. The geo database resolves to a city at best; plotting a city
would pretend to a precision we do not have. The map answers "which division is
busy", which is the question the report asks.

**Pixel-radius `circleMarker`, not metre-radius `circle`.** A metre radius of
10–44 km looks wrong at zoom 7 and disappears at zoom 5. A pixel radius keeps
the dot readable at every zoom level.

**Filters are applied to the cards and the map too.** `liveStats()` and
`liveMap()` take the same `$filters` array as the table (`liveEventQuery()`), so
filtering to a channel or division cannot leave the headline numbers describing a
different audience than the rows underneath.

**The table fits 1440 without an inner scrollbar.** Measured at 1440: wrapper
`scrollWidth = clientWidth = 724`. Getting there needed three things: `H:i:s`
instead of "2 minutes ago" (the relative string is in the `title`), truncated
page/ISP/device cells with the full value in `title`, and `.82rem` cells. At 390
the wrapper scrolls horizontally (926 vs 328) — the correct pattern for a 7-column
table; the **page** never scrolls sideways (measured `scrollWidth 375` at
`innerWidth 390`).

**`x-admin.card` does not exist.** Plan §9 names it, but there is no
`resources/views/components/admin/card`; only `x-admin.button` exists. The page
therefore uses the real shared pattern — `card` + `card-body` +
`avatar-initial rounded-3 bg-label-*` + `ri-*` icons — the same one the Flash
Sale page uses (`backEnd/flash-sale/index.blade.php:16-40`). Recorded here so the
next agent does not go looking for a component that was never there.

**Rows are built with `createElement` + `textContent`.** `page_path` comes from
the URL the browser reports. A page path containing
`<img src=x onerror=alert(1)>` is a real possibility on a public storefront, and
this table is inside the admin. Verified: the payload rendered as text, `img`
count stayed 0.

**Leaflet is vendored, not CDN-loaded.** A CDN `<script>` in the admin panel is
one more third party that can be blocked or compromised, and the page must work
on an intranet/offline box. 148 KB JS + 15 KB CSS, licence included. The only
external request the page makes is the OSM tile layer, which is the map itself.

---

## 4. Evidence

Environment: `http://127.0.0.1:8000`, admin session, `frontend_theme=modern`,
MySQL `1`, 422 `event_logs` / 574 `visitors_master`, **no division values** (geo
never resolved in dev) — so the empty map is the honest dev state.

### 4.1 Automated

```
php artisan test tests/Feature/AdminLiveAnalyticsPageTest.php
  PASS  Tests\Feature\AdminLiveAnalyticsPageTest
  ✓ live page renders for a browser
  ✓ live page script is inside the document
  ✓ live json keeps the poller contract and adds stats and map
  ✓ map points carry coordinates and a count
  ✓ rows expose division and city for the map filter
  Tests: 5 passed (91 assertions)   exit 0
```

```
php artisan test
  Tests: 40 passed (245 assertions)   exit 0
```

### 4.2 Browser measurements (Chromium via the desktop browser tool)

Real page, real session, no stubs:

| Check | Result |
|---|---|
| title / h1 | `Live now — Real-time Analytics` / `Live now` |
| rows | 25 (`preset=today`) |
| stat cards | `0`, `0.0`, `—`, `—` (5-min window empty — matches the data) |
| Leaflet loaded | `true`; map hidden + empty state shown (no divisions) |
| page horizontal scroll @892 | `false` (877 ≤ 892) |
| page horizontal scroll @1440 | `false` (1425 ≤ 1440) |
| table wrapper @1440 | `724 / 724` — **fits, no inner scrollbar** |
| table wrapper @390 | `926 / 328` — inner scroll only; page `375 ≤ 390` |
| sidebar | `Real-time Analytics › Overview › Live now` active |
| console | `0` messages |
| visible zero-size icons | `0` (card icons 24 px, toolbar icons 20 px) |

Polling was observed working: the header text changed from the Blade-rendered
`08:17:06` to the JS-rendered `8:17:06 AM` after the first 10 s interval, with no
page reload.

### 4.3 Populated state (stubbed payload, no DB writes)

`window.fetch` was replaced in the page, then **Refresh** clicked. This proves the
render path without inserting fake analytics rows into the development database:

| Check | Result |
|---|---|
| stat cards | `3`, `3.6`, `/product/rice-5kg`, `Facebook` |
| delta badges | `+50%`, `+50%`, `44.4%`, `66.7%`, class `bg-label-success` |
| rows rebuilt | 3, cells `1 minute ago`→`H:i:s`… (time column shows `H:i:s`) |
| XSS attempt in `page_path` | rendered as text; `#live-rows img` = **0** |
| chips | `Dhaka 2`, `Chattogram 1` |
| map | shown, 7 Leaflet panes, **2 circles**, total `3`, scope `Last 5 minutes` |
| division filter (chip) | 3 rows → **1** row, badge "Dhaka", `aria-pressed=true`; click again → **3** rows |
| pause on hover | `mouseenter` on tbody → `paused` badge visible; `mouseleave` → hidden |
| error state | rejected fetch → alert visible, **last good 3 rows retained**, error time = last success |

### 4.4 Client data page

`work/client-data-request.html` served over HTTP and opened in Chromium:

| Check | Result |
|---|---|
| fields / items / groups | 70 / 40 / 10 |
| counters | `০ / ৭০` → after one input `১ / ৭০`, ring `১%`, section `১ / ৮` |
| localStorage | `{"ga4_measurement_id":"G-TEST12345"}` written on input |
| copy | toast `কপি হয়েছে` (clipboard write resolved) |
| console | `0` messages |
| horizontal scroll @892 | `false` |
| desktop layout @1440 | grid `232px 828px`, sticky TOC, ring visible, paired fields `384px 384px` |

No external dependency except the optional Google Fonts `<link>` (falls back to
system Bengali fonts offline). All copy is inline; the page is a single file.

---

## 5. Known limits (stated, not hidden)

1. **Dev has no division data**, so the map's populated state is proven with a
   stubbed payload, not with real rows. The empty state is the real state here.
2. **`stats` and `map` ignore `preset`** by design (they are always the 5-minute
   window), but they **do** respect `channel` / `division`. The table respects
   the full filter set.
3. **OSM tiles are an external request.** If the admin network blocks
   `tile.openstreetmap.org`, the map renders empty while the chips still work.
4. **The page polls every 10 s per open tab.** No backoff on repeated failures
   beyond the error banner; a stale tab still polls. Acceptable at admin scale,
   noted for the next pass.
5. **`map` counts distinct visitors per division**, not events — a visitor with 40
   events is one circle unit, which is what the card claims.

---

## 6. Next plan (T4.2 → T4.4)

### T4.2 — Visitors list (`/admin/visitors/list`)

- Filters: channel, division, device, consent state, has-order.
  `consent` lives in `event_logs.event_details` JSON; the list must read it
  without a schema change (`JSON_EXTRACT`, or a derived column in the query).
- Columns: visitor, first seen, last seen, pages, events, channel, ISP, orders.
- Row → Visitor 360. Keep the existing JSON/datatable contract if the page uses it.
- Acceptance: filters keyboard-reachable, no h-scroll at 1440/390, empty state.

### T4.3 — Visitor 360 (`/admin/analytics/visitor/{visitorId}`)

- Same route, same branch as `live()`: `expectsJson()` keeps the JSON, browsers
  get the Blade page.
- Header card (id, first/last seen, geo, device, channel, consent).
- Journey timeline: every event with `event_id`, page, time; Purchase links to the
  order (`orders.invoice_id` = `EventId::purchase` input).
- Funnel strip: PageView → ViewContent → AddToCart → Checkout → Purchase with drop
  counts; highlight the biggest gap.
- "Why no purchase": rule-based and explainable ("left at shipping step",
  "viewed price twice, no cart", "returned 3× in 24 h"). No AI.
- Acceptance: 500-event cap surfaced in the UI, not silently truncated.

### T4.4 — Journey / funnel (`/admin/analytics`)

- Channel → landing page → exit page (stacked bar is enough; a Sankey drawn in CSS
  is already the pattern in `index.blade.php`).
- Table: landing page, visitors, bounce (single PageView), cart rate, purchase
  rate, revenue; date range + channel filter; CSV export.
- Acceptance: CSV row count = table row count; totals reconcile with `kpi`.

### Still blocked on the owner (unchanged from T3)

GTM web container id · GTM server container + `CONTAINER_CONFIG` · DNS/TLS for
`t.<host>` · GA4 measurement id + MP API secret · Meta `test_event_code` column.
**`work/client-data-request.html` is the artefact that unblocks these** — send it,
and the answers drop straight into the admin settings / `.env`.

---

## 7. Rollback

```bash
git revert <this commit>
```

The controller branch is additive (`stats`/`map` keys), the sidebar change is a
markup swap, the view and test are new files, and the vendored Leaflet directory
is inert unless the live page is opened. Reverting restores the JSON-only
`/admin/analytics/live`; the dashboard keeps working because its contract
(`rows`/`live_visitors`/`server_time`) never changed.
