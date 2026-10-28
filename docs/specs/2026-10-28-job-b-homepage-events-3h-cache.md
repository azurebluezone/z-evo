# JOB B — Homepage Event Instrumentation (logo_click / search / favorite) + 3-hour Cache

Owner: implementation agent (job B).
Written by: zombiecoder (arbiter), 2026-10-28.
Project: `/home/sahon/Desktop/new evo` — Laravel 10.50.3, MySQL `z_evo`.
All file:line evidence verified live before this doc. If a claim is wrong, STOP and report.

## 0. Ground rules (non-negotiable)

- Read this whole file before touching anything.
- Touch ONLY files in section 6 (ownership). Everything else is read-only for you.
- Do NOT run `php artisan migrate` (live `z_evo` DB). Do NOT run `view:clear`/`config:cache`/
  `route:clear`/`optimize`.
- Comments in code: English only. No mock/demo. Evidence before every success claim:
  command + exit code + key line. Labels SOURCE_VERIFIED / NOT_TESTED.
- Three-strike rule: same failure 3x → stop, report blocker.
- A dev server may be on port 8123 (arbiter's). Do not kill it. Use `grep` + `php -l` +
  `route:list` for proof, not browser.

## 1. Goal (what "done" means)

The existing event-tracking layer already captures `page_view`, `scroll_depth`,
`outbound_click`, `add_to_cart`, `begin_checkout`. Add three MISSING event types the
requirement asks for, WITHOUT touching the server event-name validation (it accepts any
`event_name` ≤50 chars — no whitelist to extend):

1. `logo_click` — when the user clicks the site logo (header brand link to home).
2. `search` — when the user submits a product search (captures the query).
3. `favorite` — a forward-compatible hook when a wishlist/favorite control is clicked
   (may have no trigger element today; wire the listener anyway).

Plus: add a 3-hour TTL cache on the analytics summary read path (requirement "cache 3 hours").

## 2. Verified current state (evidence)

| Fact | Evidence |
| --- | --- |
| Event layer is a global JS push + sendBeacon batcher | `resources/views/frontend/partials/event-layer-head.blade.php` — `window.__EVENT_LAYER__.push(...)`; sendBeacon at `:432-437`; queue flush; scroll milestones `:275-297`; `bindScrollDepth()` at `:565` |
| Events already wired | `page_view` `:578`; `scroll_depth` `:294`; `outbound_click` `:335`; `begin_checkout` `:486`; `add_to_cart` `:525-534` |
| Event layer included on frontend layouts | `grep -rln "event-layer-head" resources/views/frontend` → `layouts/master.blade.php`, `layouts/app.blade.php`, `fm/layouts.blade.php`, `fm/layouts/account.blade.php`, `partials/consent-banner.blade.php`, `partials/analytics-ga4.blade.php` |
| Collector endpoint + controller | route `Route::post('/events/collect', [EventCollectController::class,'collect'])->name('events.collect')` at `routes/web.php:835`; controller `app/Http/Controllers/Api/EventLogController.php` |
| Server event-name validation = format only (no whitelist) | `EventLogController.php:67-79` — `event_name` required + `preg_match` format + `max:50`; no `in:` allow-list. Model `app/Models/EventLog.php` stores `event_name` |
| Consent gating present and currently auto-granted | browser eval on homepage → `__CONSENT__` = `{analytics:'granted', marketing:'granted', decided:true}`; consent banner `resources/views/frontend/partials/consent-banner.blade.php` (180-day cookie). Your events must respect the same gate the existing layer uses (see 3.4) |
| Header logo is a brand link to home | `resources/views/frontend/layouts/components/header.blade.php:39` is NOT this project's header. THIS project's header: find the brand/logo anchor. Verified pattern in `resources/views/frontend/fm/*` and `frontend/layouts/*` — locate the logo `<a href="{{ route('home') }}">` (grep `route('home')` + `logo` under `resources/views/frontend`). Report the exact file:line + selector you instrumented |
| Search form (products search) | `resources/views/frontend/home/home.blade.php` has a search form; the live search action is `/search` with input `name="q"` (`fmSearchInput` / `fmDrawerSearchInput`, verified `form[action="/search"] input[name="q"]` on homepage DOM). Two forms exist (header + drawer) |
| Homepage has NO explicit favorite/wishlist control in the fm header | grep `favorite\|wishlist` in `frontend/home/home.blade.php` → 0. A wishlist route exists (`route('wishlist')` in footer). The `favorite` listener is a forward-compatible hook → mark NOT_TESTED |
| Analytics summary / 3h cache target | No `addHours(3)` anywhere (repo grep → 0). The only long cache is `OrderController.php:125` `addHours(6)` courier reference. Analytics summary lives in `app/Http/Controllers/Admin/AnalyticsDashboardController.php` (read it; find its heaviest query) and/or the Real-time Analytics admin page. Pick the analytics summary count query and cache it 3h |

## 3. Build spec

### 3.1 `logo_click` event

In the event layer script (`event-layer-head.blade.php`), add a delegated click listener:

- Target: the header brand/logo link — an `<a>` whose `href` is the home route AND that
  contains an `<img>` with the site logo, inside the header/banner. Implement defensively:
  delegate on `document`, match `a[href]` where the link's `closest('header, [role=banner], .navbar, .fm-header, .header')` is truthy AND the link contains an `img`. If no match on click, return silently.
- On match: `window.__EVENT_LAYER__.push('logo_click', { target_href: a.getAttribute('href') })`.
- Bind once (guard with a flag like the existing `bind*` helpers). Follow the file's existing
  listener style (see `bindScrollDepth` `:565` and the outbound_click bind `:335`).

### 3.2 `search` event

- Delegate `submit` on `document` for `form[action*="/search"]` (covers both header and
  drawer forms; verified both post to `/search`).
- Read `form.querySelector('input[name="q"]').value.trim()`; only fire when non-empty.
- `window.__EVENT_LAYER__.push('search', { query: q })`.
- Do NOT `preventDefault()` — let the form navigate normally (matching the existing
  add_to_cart/begin_checkout non-blocking style).

### 3.3 `favorite` event (forward-compatible hook)

- Delegate `click` on `document` for `.wishlist-btn, .add-to-wishlist, [data-favorite], [data-wishlist], a[href*="wishlist"]` (whichever selectors exist anywhere in the frontend; grep first and list them).
- `window.__EVENT_LAYER__.push('favorite', { target: <class or href> })`.
- No trigger element exists on the homepage today → mark `NOT_TESTED — no trigger on homepage`.
  The listener must be harmless (no error) when nothing matches.

### 3.4 Consent gate

- The existing layer likely checks the consent state before flushing. Read how `page_view`
  and `add_to_cart` decide to queue/flush (search the file for the consent/analytics check).
  Your three new events go through the SAME `window.__EVENT_LAYER__.push(...)` path, so they
  inherit the gate automatically. Confirm this by reading, and state it in the report. Do NOT
  add a second consent mechanism.

### 3.5 3-hour analytics cache

- In `app/Http/Controllers/Admin/AnalyticsDashboardController.php` (or the real-time
  analytics controller it uses — verify the class that powers the admin Real-time Analytics
  page), wrap the main summary/count computation in:
  `Cache::remember('admin.analytics.summary_3h', now()->addHours(3), function () { ... });`
- English comment: `// Requirement: analytics summary cached for 3 hours (10800s)`. Use
  `now()->addHours(3)` explicitly so the TTL is greppable.
- Do NOT invent a product-card cache. Only the analytics summary.

## 4. Acceptance checks (run all, paste output + exit code)

1. `php -l` on any touched `.php` file (the event layer is blade — skip; the controller must pass).
2. `grep -n "logo_click\|'search'\|'favorite'" resources/views/frontend/partials/event-layer-head.blade.php`
   → all three event names present.
3. `grep -c "addEventListener\|\.on(" resources/views/frontend/partials/event-layer-head.blade.php`
   → count increased vs baseline (capture baseline BEFORE your edit).
4. `grep -n "addHours(3)" app/Http/Controllers/Admin/AnalyticsDashboardController.php` (or the
   analytics controller you chose) → ≥1 hit.
5. `git status --short` → only your owned files (section 6). Ignore other agents' files.
6. Runtime event POST: NOT_TESTED (needs a browser session + consent). Say so plainly. The
   arbiter already proved `PageView` events land in `event_logs` (73 rows) — your events use
   the identical `push`+sendBeacon path.

## 5. What you must NOT do

- No server-side event-name whitelist to extend (there is none) — do not add one.
- No changes to `EventLogController` validation, no new routes, no new tables.
- No consent-banner rewrite. No changes to `createpage/*`, courier views, sidebar
  (job A territory), any controller under `app/Http/Controllers/Admin` EXCEPT the analytics
  cache edit in 3.5.

## 6. Ownership (files you may create/edit)

- `resources/views/frontend/partials/event-layer-head.blade.php` (edit: add 3 listeners)
- `app/Http/Controllers/Admin/AnalyticsDashboardController.php` (edit: wrap summary in 3h cache)
  — OR, if the Real-time Analytics page is powered by a different controller, that one file
  (state which in your report; touch only ONE controller for the cache).

Forbidden: `routes/*.php`, `bootstrap/app.php`, `app/Http/Controllers/Api/EventLogController.php`,
`app/Models/EventLog.php`, `resources/views/frontend/layouts/*` (header/footer), consent-banner,
`resources/views/backEnd/*` (job A territory), any migration/model.

## 7. Report format

Files edited (paths); every acceptance check with command + exit code + key line; the exact
selector used for `logo_click` and `search`; confirmation that new events inherit the consent
gate (with file:line of the gate); `NOT_TESTED` items listed; deviations with reason; one open
question max. Then append a lesson via `append_syllabus` only if you found a project-wide rule.
