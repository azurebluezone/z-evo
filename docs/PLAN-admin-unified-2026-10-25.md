# PLAN — Admin panel: one stat header, one form language, page-by-page proof

**Date:** 2026-10-25
**Project:** `/home/sahon/Desktop/new evo` (Laravel 10, admin = Sneat `vertical-menu-template`)
**Owner:** zombiecoder (Chief Arbiter) · implementer for this plan
**Reference design source:** `/home/sahon/Desktop/admin html/html/vertical-menu-template/` (145
template pages, same Sneat version as `public/admin/assets`). It is a **read-only reference outside
the repo**; every artefact this plan ships is copied/created inside this repo, so no evidence
depends on that path.
**Rule of this document:** every number below was measured with a command, not estimated. Commands
are repeated in §8 so any later session can re-measure the same things.

---

## 1. What the user asked (verbatim intent, grouped)

| # | Request | Type |
|---|---|---|
| A1 | Put the 4-card customer stat grid (Total / Active / Inactive / This Page) on **every** admin page | BUILD |
| A2 | Menus without the grid (coupon, courier, …) must show real values, not empty space; a dedicated controller/service may supply them | BUILD |
| A3 | Stop the admin looking hand-made: one page uses one form, the next a different one, one button style each; unify | REDESIGN |
| A4 | Use the reference HTML/CSS in `Desktop/admin html` for buttons/tables/forms where needed | CONSTRAINT |
| A5 | Walk the sidebar link by link, page by page, and fix what is inconsistent | PROCESS |
| A6 | Build the tools needed for that walk | TOOLING |
| A7 | Download/load a UI/UX skill and use it | PROCESS |
| A8 | Keep SSOT + syllabus current; use other agents when useful | PROCESS |
| A9 | No Bengali inside code; no claim without a test | CONSTRAINT |
| A10 | Test as a real user, find inconsistencies, fix them, improve UX | ACCEPTANCE |
| A11 | Write this document **before** starting | PROCESS |

## 2. Measured baseline (2026-10-25, this checkout)

| Fact | Value | How |
|---|---|---|
| Admin routes | **414** | `php artisan route:list --path=admin --json \| jq length` |
| Backend Blade views | **202** | `find resources/views/backEnd -name '*.blade.php' \| wc -l` |
| Views extending the admin layout | **169** | `grep -rl "@extends('backEnd.layouts.app')" resources/views` |
| Sidebar links | **90** hrefs in 610 lines | `grep -c 'href=' backEnd/layouts/partials/sidebar.blade.php` |
| Views already using the stat-card idiom | **24** | `grep -rl avatar-initial resources/views/backEnd` |
| Views with the exact 4-card customer grid | **1** (`customer/index`) + 1 legacy copy (`kook/customer`) | `grep -rl "Total Customers"` |
| Distinct text-input class strings | **12** (333 inputs, 118 files) | `python3 tools/admin_form_input_audit.py` |
| Distinct select class strings | **18** (122 selects, 62 files); 10 selects still use `form-control` | same |
| Distinct button class strings | **45** (148 buttons, 48 files) | same |
| State-changing GET routes | **0** | `python3 tools/admin_state_changing_gets.py` |
| Self-closed/duplicated `<script>` blocks | **0** | `python3 tools/admin_script_tag_audit.py` |
| `customers` rows (seed state) | **0** | `select count(*) from customers` |
| `coupons` / `courier_apis` rows | **0** / **0** | same |
| Admin login smoke | `/admin/login` → **200** | `curl` on `127.0.0.1:8123` |

Consequences for design:

1. The grid cannot be a per-page controller return — 169 views would each need a change and the
   next new page would forget it. It must be rendered by the layout from one shared service.
2. "Total / Active / Inactive" must be **database-wide**, not `paginator->getCollection()` (today's
   `customer/index` counts only the 10 rows of the current page — a real defect).
3. Pages whose module has no records legitimately show `0`; pages with no countable module must show
   `—` (em dash) with a hint, never a fake `0`.
4. Unifying 333 inputs and 148 buttons by hand is not one commit. It is a page-by-page migration
   behind a shared component, proven by the existing snapshot/verify scripts.

## 3. Design — the stat header

### 3.1 One service, one partial, one layout slot

| Piece | Path | Responsibility |
|---|---|---|
| Service | `app/Services/Admin/PageStats.php` | all four numbers, scoped per page, memoised per request |
| Map | `config/admin_stats.php` | URL segment → `[label, icon, table, where]`; the only place a new menu is registered |
| Partial | `resources/views/backEnd/partials/stat-grid.blade.php` | renders the 4 cards; no queries, no logic beyond formatting |
| Slot | `resources/views/backEnd/layouts/app.blade.php` | includes the partial once, above `@yield('content')` |
| Composer | `app/Providers/AppServiceProvider.php` | binds `$adminStats` to `backEnd.layouts.app` |

Why a service and not a controller: a controller would mean either an extra HTTP request per page
(flash of empty cards, one more failure mode) or 169 controller edits. A view composer keeps the
number of changed files at **3**, and every future admin page inherits the header automatically.

### 3.2 Card contract (fixed template, page-specific data)

The header is one template. The numbers are about **the page the admin is on** — the customer trio
must never be copied onto the coupon page (the user's correction, 2026-10-25).

| # | Label | Value | Source |
|---|---|---|---|
| 1 | `Total <module>` | count of the page's table, optionally scoped by `where`/`where_all` | `config/admin_stats.php` |
| 2 | `Active <module>` | `status in ('1','active')` (or `status_column`) | same table |
| 3 | `Inactive <module>` | total − active | same table |
| 2/3 alt | `Pending Orders` / `Delivered Orders`, `Awaiting Payment` / `Paid Payments`, `Out of Stock` / `In Stock`, `Added Today` / `Added This Month` | `metrics[]` when active/inactive is not the honest word | config |
| 4 | `Showing This Page` | `min(20, total)`, refreshed by DataTables after each draw | `admin-datatable.js` |

- A page can describe a **subset**: `where` (one condition) or `where_all` (several) scopes card 1–4 —
  recommended products (`is_recommended=1`), pending reviews, refunds that need money back, blocked
  visitors. The header never counts rows the page is not showing.
- A page with no countable module (website setup, homepage settings, theme) falls back to the shop
  overview (Total Products / Orders / Customers) and an em dash for card 4. Never a fabricated `0`.
- Card 4 is an em dash on forms (`create`/`edit`/`show`/`print`) and on configured `no_list_routes`:
  "Showing This Page" is only honest where a table is actually on screen.
- Route overrides are looked up **literally**. Route names contain dots
  (`admin.courier.shipments`), so `config('admin_stats.routes.'.$name)` silently walks into nested
  arrays and returns null. `PageStats::definitionFor()` reads the `routes` array as an array; a
  feature test pins this.
- Dashboard: **excluded** by `config('admin_stats.exclude')` because its own KPI band already renders
  the same totals. One config line flips this.

### 3.3 Query budget

- Up to 4 counts per page (total, active, inactive is arithmetic, page count is arithmetic), memoised
  per request. Scoped pages add the same `where` to each count, not a second table.
- No cache TTL: a coupon created and immediately listed is reflected.
- Added latency target: **< 5 ms** on the seeded DB.


## 4. Design — one form language (A3)

Not a rewrite. Three shared primitives, migrated page by page, each page proven by the snapshot
scripts:

| Primitive | Today | Target |
|---|---|---|
| Text/search input | 12 class strings | `x-admin.input` → `form-control` (+ `is-invalid`, `aria-describedby`) |
| Select | 18 strings, 10 use `form-control` | `x-admin.select` → `form-select` (+ `select2` opt-in flag) |
| Button | 45 strings / 148 buttons | `x-admin.button` (already exists; remaining raw buttons converted) |

Migration order is the **sidebar order**, because that is the order the user walks:

1. Dashboard → 2. Customer → 3. Orders/Returns/Refunds → 4. Catalogue (product, inventory,
   categories, subcategories, child categories, brands, attributes) → 5. Flash sale, recommendations,
   landing pages, campaign, coupon → 6. Banners, promo banner → 7. Payments → 8. Courier group →
   9. Shipping → 10. Visitors/Analytics → 11. Social events → 12. User submissions → 13. Settings /
   website setup / roles / permissions / users.

A page is "done" only when: `admin_form_input_audit.py` shows 0 non-canonical text classes and 0
`form-control` selects **in that page**, the page renders 200, and the page has no raw `<button
class=` that has a component equivalent.

## 5. Phases and acceptance

| Phase | Work | Closes when (evidence) |
|---|---|---|
| **P0** (done) | `PageStats` + `config/admin_stats.php` + partial + layout slot + composer; content-aware cards per page; route overrides; scoped subsets; remove the duplicated 4-card block from `customer/index` | `admin:audit-pages` renders all 59 sidebar pages, reads the rendered labels/values back and compares them with independent DB counts: 59 PASS / 0 FAIL. `php artisan test --filter='AdminStatGrid|AdminPermissionCatalog'` green (18 tests). |
| **P1** | `x-admin.input` / `x-admin.select` components; convert the 12 sidebar pages that have forms | audit counts per converted page: 0 deviant classes; visual check at 1440 and 390; keyboard focus visible |
| **P2** | Buttons: convert remaining raw buttons; delete dead variants from `x-admin.button` | `verify_admin_buttons.php` exit 0; grep for `btn-` in converted pages = 0 |
| **P3** (in progress) | Page-by-page UX pass against the reference template: empty states, error states, table density, mobile h-scroll, duplicate page titles | each page: screenshot + audit row + a note in `docs/ADMIN-UX-LOG-2026-10-25.md` |

### 5.1 Status 2026-10-25 (P0 complete, P3 started)

| Evidence | Result |
|---|---|
| `php artisan admin:audit-pages` | 60 rows, **59 PASS, 0 FAIL, 1 excluded** (dashboard); each row compares 4 rendered cards with DB counts |
| `node tools/admin-ui-audit.mjs --manifest=work/audit/pages.json` | 59 pages × 1440/390 = **118 runs, 0 hard failures**: HTTP 200, one grid, no h-overflow, no broken image, no console error |
| `php artisan test --filter=Admin` | **53 passed, 2 skipped, 380 assertions** |
| `php artisan test --filter=AdminStatGrid` | **18 passed, 152 assertions** |
| `php artisan test --filter=AdminPosCart` | **8 passed, 29 assertions** |

Defects found by the walk and fixed in this pass:

1. Route overrides with dotted names never applied (silent `null`); e.g. `reviews.pending` and
   `admin.courier.shipments` fell back to the wrong module.
2. `admin.invoices.index` counted `orders.status` (column does not exist) and 500'd.
3. `orders.order_status` held the slug `pending` from the demo seeder; the Pending tab and KPI showed
   zero. Seeder now writes the id, `Order` normalises slug writes, and a migration cleaned the row.
4. POS cart endpoints 500'd on a stale row id (`InvalidRowIDException`, null `options`, missing
   shipping charge). They now validate input and return 404 JSON.
5. Mobile: 4 stacked cards pushed content below the fold → 2×2 from `col-6`.
6. Mobile: visitors list header buttons and social-events tabs overflowed the viewport.
7. `website-setup` hub was a dead screen of "HeaderSoon / FooterSoon / AppearanceSoon" tabs; it now
   redirects to the real Header page.
8. Navbar avatar 404 (`public/uploads` missing): added `public/uploads/users/default-avatar.png`.


Out of scope for P0: changing business logic, seeding coupons/couriers, touching the storefront.

## 6. Tooling (A6)

| Tool | Purpose |
|---|---|
| `php artisan admin:audit-pages` (new) | boots the app, logs in the admin in-process, walks every parameterless `GET /admin/*`, asserts `200` + exactly one `data-admin-stat-grid` + no duplicate `<h1>`, prints a table, exits non-zero on failure |
| `python3 tools/admin_form_input_audit.py` (existing) | measures the input vocabulary per page |
| `php tools/verify_admin_buttons.php` (existing) | proves a button conversion kept the class set |
| `php artisan test --filter=Admin` (existing suite) | regression gate |

The audit is the "walk the sidebar" step in executable form. It cannot feel like a user; §7 covers
that separately.

## 7. User-perspective pass (A10)

The tool proves pages render. It does not prove they are usable. Manual pass per page, at 1440 and
390, recorded in `docs/ADMIN-UX-LOG-2026-10-25.md`:

1. Can I tell what this page is for within 3 seconds (title + card header)?
2. Is the primary action in the same place as on sibling pages?
3. Does the empty state tell me what to do next?
4. Tab order: sidebar → header → first field → primary action; focus ring always visible.
5. No horizontal scroll; tables collapse to a readable card/scroll region.
6. Every number on screen has a unit and a time window.

Any deviation is logged with page, viewport, what was expected, what happened, and the fix commit.

## 8. Re-measure commands

```bash
cd "/home/sahon/Desktop/new evo"
php artisan route:list --path=admin --json | jq length
find resources/views/backEnd -name '*.blade.php' | wc -l
grep -rl "@extends('backEnd.layouts.app')" resources/views | wc -l
python3 tools/admin_form_input_audit.py
python3 tools/admin_state_changing_gets.py
python3 tools/admin_script_tag_audit.py
php artisan admin:audit-pages
php artisan test --filter=Admin
```

## 9. Risks and rollback

| Risk | Mitigation |
|---|---|
| 2 extra count queries per admin page on a large `customers` table | indexed `status` is not guaranteed → measure; if > 5 ms, add a 30 s cache with an explicit "as of" tooltip |
| A page's `@section('content')` already starts with a heading; the header adds height | the grid sits above the heading; padding is `mb-4` only, no extra wrapper card |
| `admin:audit-pages` hits routes that mutate on GET | tool restricts to `GET` and the existing audit already proves 0 state-changing GETs; it skips `logout`, `locked`, `login` |
| The reference template is outside the repo | only visual idioms are borrowed; shipped markup lives in this repo and is diffable |
| Duplicate grid on the dashboard | explicit `exclude` entry with a comment, one-line revert |

Rollback: `git revert <commit>` — the layout slot and partial are additive; removing the
`customer/index` block restores the old page.

## 10. Definition of done for this plan

- Every reachable admin GET page renders exactly one stat header with correct numbers.
- Coupon, courier and every other sidebar module report their own count.
- Inputs/selects/buttons on migrated pages come from three shared components, proven by the existing
  verify scripts.
- `admin:audit-pages` exits 0; `php artisan test --filter=Admin` green; no Bengali in code.
- SSOT + syllabus updated with the final evidence, not with intentions.

---

## 11. Status — 2026-10-25, second pass (verified)

| Gate | Result |
|---|---|
| `php artisan admin:audit-pages` | 59 pages · 54 PASS · 0 FAIL · 5 excluded (own KPI band) |
| `node tools/admin-ui-audit.mjs --manifest=work/audit/pages.json` | 118 runs · 0 HTTP/overflow/image/console failures |
| `php artisan test --filter=Admin` | 61 passed · 2 skipped · 444 assertions |

Delivered in this pass (details and per-page browser evidence in `ADMIN-UX-LOG-2026-10-25.md` §7):

- Courier credentials exist on **one** screen (`courier/settings`); legacy URLs redirect; fraud-risk
  sync lives with its results on Fraud Checker.
- Footer social icons persist `link` + `color` (columns added by migration) and render on the storefront.
- `review/pending` uses the same table/card/button language as `review/index`.
- `order/all` and `order-report` render **one** stat band; report money totals moved under the table.
- Shared form components `x-admin.input/select/textarea/switch` registered and used on every touched page.

Remaining for A3 (not blockers): 335 raw `btn` class strings and 536 raw `form-control` occurrences in
`resources/views/backEnd`, to be converted page by page with the audit re-run after each batch.
