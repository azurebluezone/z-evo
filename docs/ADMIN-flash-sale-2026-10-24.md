# Admin — Flash Sale manage screen decoration

**Date:** 2026-10-24
**Page:** `GET /admin/flash-sale/manage` (`flash-sales.index` → `Admin\FlashSaleController@index`)

---

## 1. What was asked

Decorate the page with the four-card summary pattern used on the recommendations
screen: three stat cards (icon tile, big number, label, hint) and one action
card.

## 2. What the numbers mean

The old page was a bare table. The new cards must not invent numbers, so every
value comes from a query that already exists:

| Card | Value | Source |
|---|---|---|
| Total Flash Sales | `1` | `FlashSale::count()` |
| Active | `1` | `FlashSale::active()->count()` — the `status` switch |
| Running Now | `1` | `FlashSale::running()->count()` — status **and** `now()` inside the start/end window |
| Products linked | `4` | `distinct(product_id)` on `flash_sale_products` |

`active` and `running` are the **same scopes the storefront uses**
(`FrontendController@index`, lines 898 and 903), so the dashboard cannot claim a
campaign is live while `/` disagrees. `FlashSale::isRunning()` drives the per-row
`Live` badge with the same rule.

No extra query for the table: the controller already loads the sales with
`withCount('products')`.

## 3. Changes

**`app/Http/Controllers/Admin/FlashSaleController.php`**

- `index()` also builds `$stats` (total / active / running / products) and passes
  it to the view. Nothing else in the action changed.

**`resources/views/backEnd/flash-sale/index.blade.php`**

- four-card row (`row g-6 mb-6`, `col-sm-6 col-lg-3`, `card h-100`) using the
  admin theme's own `avatar` / `avatar-initial` / `bg-label-*` / `icon-24px`
  classes — all present in `core.css` (verified: `.g-6{gutter 1.5rem}`,
  `.mb-6{1.5rem}`, `.avatar-initial`, `.bg-label-primary|success|info`,
  `.icon-24px`)
- card 4 is the action card (`bg-label-primary border-primary`) and states a
  real constraint from the controller: **up to 10 products per sale**
  (`array_slice($productIds, 0, 10)` in `store()`)
- `Live` badge next to a title when `$sale->isRunning()`
- `N products linked` badge in the card header
- empty state: icon + "No flash sale yet" + explanation + create button
- `aria-label` on both switch states ("Activate/Deactivate <title>") — the
  switch had a `title` but no accessible name
- `text-nowrap` on the start/end cells so a date never breaks mid-value; the
  table now scrolls inside `.table-responsive` instead of squeezing to 4-line
  titles on a phone
- decorative icons carry `aria-hidden="true"`

## 4. Evidence

Probe: `/tmp/opencode/flash.mjs` (Playwright, admin session, read-only).

| Width | Page `scrollWidth` / `innerWidth` | Cards | Layout |
|---|---|---|---|
| 1440 | **1440 / 1440** | 4 × 265×156 | one row |
| 1024 | **1024 / 1024** | 4 × 250×176 | one row |
| 768 | **768 / 768** | 4 × 380 | 2 × 2 |
| 390 | **390 / 390** | 4 × 382 | stacked |

- HTTP 200, 52 `ri-*` icons, **0** zero-size, **0** without a mask, **0** console errors.
- Rendered values match the database: 1 sale, status on, start `2026-10-24 02:56`
  ≤ now ≤ end `2026-10-26 03:56`, 4 products → cards read 1 / 1 / 1 / 4.
- Table at 390: 788px wide inside a 358px scroller (`overflow-x: auto`), so the
  page itself never scrolls sideways.
- Screenshots: `/tmp/opencode/flash-1440.png`, `/tmp/opencode/flash-390.png`.

**NOT_TESTED:** the empty state (`@forelse` `@empty` branch) has no dataset on
this install — the branch compiles with the page, but it was not rendered. See
the backlog item.

## 5. Files

| File | Change |
|---|---|
| `app/Http/Controllers/Admin/FlashSaleController.php` | `$stats` in `index()` |
| `resources/views/backEnd/flash-sale/index.blade.php` | four-card row, Live badge, empty state, aria labels, nowrap dates |
