# Scope → Record: Category & Brand pages (modern theme)

Status: **done**. Built, harness green at **24/24**, screenshots captured.
Every claim below is verified by fetching `http://127.0.0.1:8123` with the
modern preview session, not by reading code.

---

## 1. What was actually true before this task (measured)

`ThemeViewFinder` maps `frontend.pages.products` -> `frontend.fm.pages.products`.
`FrontendController::category()`, `products()` (child), `subcategory()` and
`brand()` all `return view('frontend.pages.products', ...)`, so **all four
already rendered the full modern Shop page**: sidebar filters, toolbar, product
grid, cart forms, pagination.

| URL | HTTP | modern | h1 / breadcrumb current | cards |
|---|---|---|---|---|
| `/products` | 200 | yes | `All products & groceries` | 24 |
| `/category/premature` | 200 | yes | `Premature` | 9 |
| `/category/newborn` | 200 | yes | `Newborn` | – |
| `/products/brand/xiaomi-redmi` | 200 | yes | **`All products & groceries` (wrong)** | 0 |

So the `STATUS.md` §7 row "ক্যাটাগরি/ব্র্যান্ড — পেন্ডিং" was **outdated**: these
pages were never unconverted. Four defects were hiding on them.

## 2. Defects found (all reproduced, all fixed)

### BUG A — the brand page named itself "All products & groceries"
`pages/products.blade.php` derived `$fmHeading` only from query state:

```php
$fmHeading = ($fmActiveCategory->name ?? null)     // request('category')
    ?? ($fmActiveBrands->isNotEmpty() ? ... : null) // request('brand')
    ?? $fmTypeLabel                                 // request('type')
    ?? 'All products & groceries';
```

The brand route is `/products/brand/{slug}` — the slug lives in the **path**,
never in the query string, so all three candidates were null. Category pages
looked correct only because they happened to resolve through the first branch.
The heading feeds the `<h1>` **and** the breadcrumb `aria-current="page"` item
(`sections/shop-header.blade.php:86` and `:98`), so both were wrong.

### BUG B — the filter form always submitted to `/products`
`sections/shop-sidebar.blade.php:35` rendered
`<form method="get" action="{{ route('products.page') }}" id="fmFilterForm">`,
which resolves to `/products` **on every page the section appears on**. The
toolbar sort select (`name="sort_by" form="fmFilterForm"`) belongs to that form.

| on | checked `category` radio | result of pressing sort/price |
|---|---|---|
| `/category/premature` | `premature` | `/products?category=premature` — works, but migrates off the route |
| `/products/brand/xiaomi-redmi` | `""` (empty) | `/products?category=` — **brand context lost, list becomes everything** |

### BUG C — a query the controller ignores could rename the page
`?category=` was honoured by the heading but **ignored by the controller** on
path-scoped routes (`FrontendController::category()` reads only its own `$slug`):

```
/category/premature?category=newborn  ->  heading "Newborn" over Premature products
```

Reachable straight from the sidebar, which renders 13 `name="category"` radios
on that page.

### BUG D — every price sort on the controller paths returned 500
Discovered only once BUG B was fixed and the sort query actually landed on the
category route.

```
SQLSTATE[42S22]: Column not found: Unknown column 'price' in 'order clause'
```

Two duplicate sort implementations had drifted apart:

| implementation | sorts by | result |
|---|---|---|
| `/products` closure (`routes/web.php:322`) | `new_price` | 200 ✓ |
| `FrontendController::applyProductListingSort()` (`:45`) | `price` | **500** |

The products table has `purchase_price, old_price, new_price` — there is no
`price` column. **Pre-existing and design-agnostic**: classic
`/category/premature?sort_by=price_lth` returned 500 with no cookie involved.
Reachable from 6 call sites (`category`, child category, `subcategory`, `brand`,
and the two search actions at `:1253`, `:1289`).

## 3. Changes

**`resources/views/frontend/fm/pages/products.blade.php`**
1. `$pageTitle` appended to the `$fmHeading` chain, before the literal, guarded
   with `isset()`. The four `FrontendController` listing actions all pass
   `$pageTitle`, so the brand page names itself. The `/products` closure passes
   **no** `pageTitle` (`compact('products','categoryCounts','brandCounts','priceCeiling','hasReviewData')`)
   so `isset()` fails there and the generic literal is still used — a page that
   was already correct is untouched.
2. The **route wins over the query** on path-scoped routes: `category`,
   `products.category` and `products.brand` re-derive `$fmCategorySlug` from the
   route parameter instead of `request('category')`. This is what closes BUG C.

**`resources/views/frontend/fm/sections/shop-sidebar.blade.php`**
3. `action="{{ request()->url() }}"` — current path, no query. A GET form
   replaces the query string wholesale, so only the path can carry the route
   context. Closes BUG B.
4. `brand[]` carried forward as a hidden input **only while the Brand block did
   not render** (`@unless($fmFilterBrands->isNotEmpty())`), so a real brand
   checkbox is never duplicated. Without it, a brand filter on a page whose
   Brand block is hidden would be silently dropped by the first submit.
5. `type` needed **no** work: the section already shipped
   `@if($fmType !== '') <input type="hidden" name="type">`. A second copy added
   by this task was removed — it produced `type=featured&type=featured`.
6. The Categories block splits by route shape: **links** on path-scoped routes,
   **radios** on `/products`. On a path-scoped route the controller ignores
   `?category=`, so a radio there offered a choice that could never work.
   Classic already answers this way — 392 category links and **zero** radios on
   `/category/premature`.

**`public/frontend/css/fm-products.css`**
7. `a.fm-check-row[aria-current="page"]` reproduces the selected look of
   `:has(input:checked)` for those links (same colours, same count chip).

**`app/Http/Controllers/Frontend/FrontendController.php`**
8. `applyProductListingSort()`: `orderBy('price', ...)` -> `orderBy('new_price', ...)`
   in both branches. Closes BUG D. This corrects a reference to a column that
   does not exist, matching the working sibling implementation in
   `routes/web.php:322-329` — no new behaviour, no invented backend.

## 4. Explicitly NOT in scope

- **Sidebar Brand filter on category/brand pages.** `$brands` *is* shared
  globally (`AppServiceProvider:121 view()->share('brands', ...)`), so the block
  renders on `/products` (one checkbox: brand `n/a` carries 1238 products). It
  does not render on `/category/{slug}` or `/products/brand/{slug}` because those
  actions never pass `$brandCounts`. Classic has **no** `name="brand"` filter on
  the products page either (only `main-categories.blade.php`), so rendering one
  there would add a feature classic never had, on routes that ignore it. Left
  alone and reported.
- Brand **display names are junk demo data** (`xiaomi-redmi` -> `George`,
  `n/a` -> `-`). The heading is correct — it prints what the DB says. Not a bug;
  documented so nobody "fixes" it.
- Breadcrumb, grid, toolbar, pager, cart forms: already working, untouched.
- `subcategory`/`childcategory` routes: same view, same fixes, no extra code.
- Classic theme: only touched by the BUG D column correction, which turns a 500
  into a 200.

## 5. Acceptance — `verify/category-check.mjs`, 24/24

| # | proves |
|---|---|
| 1-3 | category page modern, h1 **and** breadcrumb = category name, crumb links to `/products` |
| 4 | form action ends with `/category/premature` (BUG B) |
| 5-6 | departments are links not radios; current one carries `aria-current="page"` |
| 7 | sort control present |
| 8-9 | `?category=newborn` no longer retitles a Premature listing (BUG C) |
| 10-12 | a **real** sort submit stays on `/category/premature`, reaches the server, **and the grid reads back ascending** `[150,150,150,200,350,350]` (BUG D) |
| 13-15 | brand page names itself, form submits to the brand path, departments are links |
| 16 | sorting from the brand page keeps the brand route (BUG B) |
| 17-18 | `/products` still uses radios and keeps its generic heading |
| 19 | `/products?category=newborn` still renames the page (query-scoped route unchanged) |
| 20 | `?type=featured` survives with exactly one carrying hidden input |
| 21 | no horizontal scroll at 375 px |
| 22 | without the cookie, classic still serves (18 `/products/show/` links) |
| 23-24 | zero console errors, zero failed requests |

Harness notes worth keeping:
- `Network.setCacheDisabled(true)` — the classic check deletes the preview
  cookie and re-requests the URL it is already on; a cached modern copy passed
  markup the server never sent.
- Failed requests are recorded **with their URL** (`loadingFailed` carries only
  the error text), so a failure can be attributed to a first-party asset or to a
  third-party resource Chrome refused.

## 6. After the work

- `tools/bangla_to_english.py --check`
- `STATUS.md`: §7 category/brand -> done, §6.x session entry, §3 file map
  unchanged (only `fm-products.css` already listed, plus the controller fix).
- Screenshots: `shots/category-desktop.png`, `shots/category-mobile.png`.
