# ADMIN UX LOG — 2026-10-25

Project: `/home/sahon/Desktop/new evo` · admin at `http://127.0.0.1:8123/admin`
Evidence produced by: `zombiecoder` (Chief Arbiter) on 2026-10-25.

This log is the human-readable companion to the machine evidence in `work/audit/`. Every claim below
has a command or artifact next to it.

---

## 1. Acceptance result

| Gate | Command | Result |
|---|---|---|
| Card content on every sidebar page | `php artisan admin:audit-pages --json=work/audit/pages.json` | 59 rows · **54 PASS · 0 FAIL · 5 excluded** (own KPI band, by config) |
| Real browser, desktop + mobile | `node tools/admin-ui-audit.mjs --manifest=work/audit/pages.json` | **118 runs** (59 pages × 1440/390) · 0 HTTP failures · 0 h-overflow · 0 broken images · 0 console errors · 0 failed responses |
| Admin feature tests | `php artisan test --filter=Admin` | **61 passed · 2 skipped · 444 assertions** |
| Whole suite | `php artisan test` | **72 passed · 2 skipped · 485 assertions** |
| Stat header tests | `php artisan test --filter=AdminStatGridTest` | **17 passed · 177 assertions** |
| Footer social CRUD | `php artisan test --filter=AdminFooterSocialTest` | **5 passed · 20 assertions** |
| Review moderation | `php artisan test --filter=AdminReviewTest` | **7 passed · 45 assertions** |
| POS cart tests | `php artisan test --filter=AdminPosCart` | 8 passed · 29 assertions |

Artifacts:

- `work/audit/admin-ui-audit.json` — per page/viewport status, card labels + values, card widths,
  scrollWidth, broken images, console errors.
- `work/audit/*.png` — screenshot per page/viewport.
- `work/audit/pages.json` — the sidebar route list used as the walk order.
- `work/audit/run-full.log` — one line per page/viewport for the last full run.

---

## 2. What the four cards mean now

The template is fixed; the data is the page's own:

1. `Total <module>` — count of the module's table, scoped when the page shows a subset.
2. `Active <module>` — `status in (1, 'active')` (or a configured status column).
3. `Inactive <module>` — total − active.
4. `Showing This Page` — `min(20, total)`; DataTables rewrites it after every draw.

Overrides when active/inactive would be a lie:

| Page | Cards 2 and 3 |
|---|---|
| Orders, order report | Pending Orders · Delivered Orders |
| Invoices | Awaiting Payment · Paid Payments |
| Stock report | Out of Stock · In Stock |
| Reviews | Pending Reviews · Active Reviews |
| Refunds | Refunded · Awaiting Refund |
| Tables without a status column | Added Today · Added This Month |

Scoped pages (card 1–4 all respect the page's subset):

| Page | Scope |
|---|---|
| Recommendations | `products.is_recommended = 1` |
| Pending reviews | `reviews.status = 'pending'` |
| Refunds | `order_returns.restocked = 1 AND refund_required = 1` |
| Blocked visitors | `visitors_master.status = 0` |
| Stock report | `products.status = 1` |

No-list pages (card 4 = `—`): create/edit forms, website setup, homepage settings, frontend theme,
shipping verification, courier settings/fraud checker, reports-style tools.

Fallback (no countable module): website setup / homepage settings / theme / dev tools show the shop
overview (Total Products / Orders / Customers) and `—` for page count. The dashboard is excluded
entirely because it already has its own KPI band.

---

## 3. Defects found while walking, and the fix

| # | Symptom (real user) | Root cause | Fix | Proof |
|---|---|---|---|---|
| 1 | A page showed the customer trio (or another module's numbers) | Module resolved only from URL segment; route overrides read with dot notation returned null | Read `config('admin_stats.routes')` as an array and look the name up literally; `definitionFor()` public | `AdminStatGridTest::test_route_overrides_with_dotted_names_are_honoured` |
| 2 | Invoices page 500 | `invoices` mapped to `orders`, then counted `orders.status` (no such column) | Invoice cards use payment metrics; route-specific definition | `admin:audit-pages` invoices row PASS |
| 3 | Orders → Pending tab empty; KPI pending = 0 | Demo seeder wrote `order_status = 'pending'` (slug) while every admin query filters by id | Seeder writes the id; `Order::setOrderStatusAttribute` normalises slug writes; migration `2026_10_25_000001` cleaned the existing row | `AdminOrderDatatableTest` (previously failing) now green |
| 4 | POS cart buttons could 500 | Stale `rowId` threw `InvalidRowIDException`; missing cart row / shipping id dereferenced null | Validate input; 404 JSON with a message; decrement at 1 removes the row | `AdminPosCartTest` (8 tests) |
| 5 | Mobile: page content pushed below the fold | Four full-width cards stacked under 576px | `col-6 col-lg-3` → 2×2 on phones | screenshots `*-mobile-390.png`, card widths 171–187px |
| 6 | Mobile: visitors list and social-events page scrolled sideways | Button group and `nav-fill` tabs had no wrap/scroll | `flex-wrap` on the header, `flex-nowrap overflow-auto` on tabs | `admin-ui-audit` h-overflow count 0 |
| 7 | Website setup showed "HeaderSoon / FooterSoon / AppearanceSoon" dead tabs | Stub view never connected to the real pages | `WebsiteSetupController@index` redirects to `admin.website-setup.header`; test pins the redirect | `AdminStatGridTest::test_website_setup_hub_opens_the_real_header_page` |
| 8 | Navbar avatar broken on every page (2 × 404) | `public/uploads/` did not exist | Added `public/uploads/users/default-avatar.png`; upload folders are created on demand by controllers | `admin-ui-audit` brokenImages 0 |

---

## 4. Browser evidence worth keeping

| Page | Viewport | Cards (rendered) |
|---|---|---|
| Coupons | 1440 / 390 | Total 0 · Active 0 · Inactive 0 · Showing 0 |
| Couriers | 1440 / 390 | Total 2 · Active 2 · Inactive 0 · Showing 2 |
| Products | 1440 / 390 | Total 30 · Active 30 · Inactive 0 · Showing 20 |
| Orders (`/all`) | 1440 / 390 | Total 1 · Pending 1 · Delivered 0 · Showing 1 |
| Reviews (pending) | 1440 / 390 | Total Pending Reviews 0 · Added Today 0 · Added This Month 0 · Showing 0 |
| Refunds | 1440 / 390 | Total Refunds 0 · Refunded 0 · Awaiting Refund 0 · Showing 0 |
| Website setup (header) | 1440 / 390 | Total Products 30 · Total Orders 1 · Total Customers 0 · Showing — |
| Analytics live | 1440 / 390 | Total Visitor Events 68 · Added Today 68 · Added This Month 68 · Showing 20 |

`work/audit/admin-ui-audit.json` has the full set (59 pages).

---

## 5. Known, not fixed in this pass

| Item | Why it is open | Next step |
|---|---|---|
| P1 form language (`x-admin.input` / `x-admin.select`) | Large page-by-page refactor; this pass was the user's card correction | Convert in sidebar order, one page per commit, re-run `tools/admin_form_input_audit.py` |
| P2 button variants | Same | Run `tools/verify_admin_buttons.php`, convert page by page |
| `/admin/customer/profile` without `?id` returns 404 | Correct by design: it needs a customer id | Audit skips it; no change |
| phpMyAdmin UI unreachable | Apache/Nginx not running on this machine | `sudo systemctl start apache2` when needed |
| `ShinjukuProductsSeeder` skips 25 rows | `work/t/data/shinjuku_products.json` and `public/uploads/product/shinjuku/` are absent from the clone | Restore the fixture or make the seeder fail loudly |
| Demo admin password had drifted from `123456` | Local-only; reset with tinker and verified `Hash::check` | Reset again if a manual test changes it |

---

## 6. Re-run the evidence

```bash
cd "/home/sahon/Desktop/new evo"

php artisan config:clear && php artisan view:clear
php artisan admin:audit-pages --json=work/audit/pages.json
node tools/admin-ui-audit.mjs --manifest=work/audit/pages.json
php artisan test --filter='AdminStatGrid|AdminPermissionCatalog|AdminPosCart'
php artisan test --filter=Admin
```

`admin:audit-pages` exits non-zero on any FAIL; `admin-ui-audit.mjs` exits non-zero on HTTP != 200,
missing/duplicate grid (dashboard excepted), horizontal overflow, broken image or console error.

---

## 7. Second pass — one courier screen, one form language, no duplicate bands

Reported by the user: the courier API credentials existed on three screens with three different forms;
the footer Social Icons form silently discarded the link; `order-report`, `review/pending` and
`order/all` each opened with two stat bands.

### 7.1 What changed

| Area | Before | After | Proof |
|---|---|---|---|
| Courier credentials | Editable on `courier/settings`, `courierapi/manage`, `courier/{type}/edit` | One screen: `courier/settings` (save + live test + webhook endpoint/token/rotate) | `AdminCourierPagesTest` — 7 passed; index has no `name="api_key"` |
| Legacy courier URLs | Two more credential forms | `courierapi/manage` 302 → `admin.courier.settings`; `courier/{type}/edit` 302 → `#courier-{type}` | `test_legacy_courier_api_page_redirects_to_api_settings`, `test_courier_edit_redirects_to_the_one_settings_screen` |
| Courier fraud-risk sync | Duplicate endpoint + card on settings | One endpoint, card beside the results on Fraud Checker | `CourierSettingsController::syncOrdersWithCourier`; grouped `whereNull` query so the phone filter cannot be escaped by OR |
| Footer social icons | Form posted `link`/`color`; table had neither column → every icon pointed at `#` | Migration adds both columns; fillable + validation; edit moved into a modal | `AdminFooterSocialTest` — 5 passed; browser CRUD 11/11 below |
| `review/pending` | Third visual language: striped table, no product, no empty state | Same card/table/badges/buttons as `review/index` | `AdminReviewTest::test_pending_page_lists_the_product_and_an_approve_action` + empty state test |
| `order/all` | Shared header + page KPI band = 8 stat cards | One band (shared, order language) | `AdminStatGridTest::test_pages_render_exactly_one_stat_band` |
| `order-report` | Shared header + money KPI band | One band; money totals in a strip under the table (filter totals) | same test |
| Products / Recommendations | Own KPI band + shared header (or no shared header) | Shared header only; Hot Deals as a badge; Add Product in the header | `AdminStatGridTest` product + scoped tests |
| Form language | Hand-written `label`/`input`/error triples | `x-admin.input`, `x-admin.select`, `x-admin.textarea`, `x-admin.switch`, `x-admin.button` | registered in `AppServiceProvider`; used on every page touched in this pass |

### 7.2 Browser evidence (this pass)

`node /tmp/opencode/pw-verify.mjs` — logged in as the real admin, 1440 × 1000 and 390 × 844:

| Page | Status | Stat bands | Credential inputs | Overflow | Broken images | Console errors |
|---|---|---:|---:|---:|---:|---:|
| `/admin/courier/settings` | 200 | 1 | 4 (the one form) | 0 | 0 | 0 |
| `/admin/courier` | 200 | 1 | 0 | 0 | 0 | 0 |
| `/admin/website-setup/footer` | 200 | 1 | 0 | 0 | 0 | 0 |
| `/admin/review/pending` | 200 | 1 | 0 | 0 | 0 | 0 |
| `/admin/order/all` | 200 | 1 | 0 | 0 | 0 | 0 |
| `/admin/order-report` | 200 | 1 | 0 | 0 | 0 | 0 |
| `/admin/products/manage` | 200 | 1 | 0 | 0 | 0 | 0 |

Screenshots: `work/ux/verify-<page>-1440.png` and `work/ux/verify-<page>-390.png`.

Footer social icon end-to-end (`node /tmp/opencode/pw-social.mjs`), **11/11 checks**:

1. add → redirect back to the footer page · 2. row appears · 3. link stored · 4. storefront footer links to it ·
5. edit modal prefilled (title/link/colour/status) · 6. edit saves new link · 7. Hide asks for confirmation ·
8. Hide sets the row Inactive · 9. Delete asks for confirmation · 10. row deleted · 11. storefront link gone.
Page errors: 0. The row was removed afterwards; `social_media` is empty again.

The four pages excluded from the shared header were re-checked: `flash-sale/manage`, `analytics`,
`analytics/live`, `stock-report` each render **exactly one** own KPI band (0 shared grids).

### 7.3 Still open

| Item | Count | Why |
|---|---:|---|
| Raw `class="btn btn…"` in `backEnd` views | 335 | Page-by-page refactor; this pass converted only touched pages |
| Raw `class="form-control…"` in `backEnd` views | 536 | Same; `x-admin.input` has 14 call sites so far |
| `tools/admin_form_input_audit.py` re-run | — | Run after the next conversion batch, not per page |
