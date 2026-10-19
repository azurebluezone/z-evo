# Demo catalogue rebuild — what changed and how it was proved

**Date:** 2026-10-08
**Scope:** wipe the old catalogue, seed a 20 item grocery demo, rebuild the
product card to the reference design, verify the eleven home sections, and
rebrand to Zevo E-commerce.

---

## 1. The wipe (done first, backed up)

Two backups were taken and verified before a single row was deleted.

| Backup | Size | Contents |
|---|---|---|
| `arafat_bak/db-before-wipe-080932.sql` | 51 MB | 69 tables, 99 INSERT statements |
| `arafat_bak/uploads-product-093501.tar.gz` | 1.2 GB | 4,270 product image files |

Removed: `products` 1240, `productimages` 6992, `product_variants` 2861,
`productsizes` 1986, `productcolors` 33, `reviews` 9, `orders` 253,
`order_details` 940, `order_returns` 4, `flash_sale_products` 1,
`campaign_reviews` 28. Then `public/uploads/product/` was emptied (4,269 files,
1.3 GB).

Kept on purpose: categories, brands, settings, customers, colours and sizes.
`settings/` (logo), `banner/`, `category/`, `brand/` were NOT touched because
they carry the site's own branding and setup.

The order deletion was flagged before it happened and the confirmed instruction
was "delete all data". The dump is the only rollback.

---

## 2. Image generator

`tools/make_demo_product_images.py` — Pillow, no network access.

Style copied from the reference set at
`~/Desktop/zevlms/blog/images/blog-covers/` (measured, not assumed: 54 files,
all exactly 1200x630 JPEG, average 137 KB):

* dark teal-to-green gradient, faint 26px dot grid, corner vignette
* rounded green pill badge, amber accent rule, brand lockup on a white plate
* **1200x630 for banners** (matches the reference exactly)
* **1000x1000 for catalogue images** (`--square`), because the product card is
  `aspect-square`

Five bugs were found by reading the output, not by trusting the code:

| Bug | Cause | Fix |
|---|---|---|
| Titles truncated to "Avocado..." | `wrap_text` guessed overflow from the line count | lay out every word, clip only when it truly does not fit |
| Pack-size chip invisible | `(255,255,255,34)` drawn straight onto RGBA overwrites alpha instead of blending | separate layer + `alpha_composite` |
| White blob under the title | translucent plate drawn twice, second with `fill=None` | `rounded_rectangle` takes `fill=None` as outline-only |
| Carrots pointed up out of frame | screen angles 250-294 deg; 90 deg is down | angles 64/90/116 |
| Carrot looked brown, then pink | art composited before the scrim, then a brightness floor lifted green+blue | draw art after the scrim, clamp only to 0-255 |

Product names are **not** drawn on the square frame: the card already prints the
name, so baking it in duplicated it and would leave stale wording after a rename.

---

## 3. Seed

`php artisan demo:products` (`app/Console/Commands/SeedDemoProductsCommand.php`)
with `--dry-run` and `--wipe`.

* 20 products, 7 grocery departments, brand `Zevo Fresh`
* 20 productimages + 20 default variants, per-product SKU and product code
* `is_recommended=1` on all, `feature_product` on every 3rd, `topsale` on
  every 4th so the home feeds all have something to show
* every row stamped `demo-catalogue-zevo` in `meta_description`, so `--wipe`
  removes exactly what the command created

Three bugs caught while building it:

1. `--dry-run` reported `IMAGE MISSING` for all 20 files that existed — the check
   read the `productimages` table instead of the filesystem.
2. `slug` was missing from the insert payload (`Field 'slug' doesn't have a
   default value`).
3. `ucwords()` turned `dairy-eggs` into "Dairy Eggs" while the catalogue says
   "Dairy & Eggs"; the display-name-to-slug map is now explicit.

`fresh-milk-packet` vs `fresh-creamer-pack` was a typo in my own Python list. A
cross-check now asserts the Python catalogue, the image files and the PHP
catalogue are the same 20 slugs — all three identical.

---

## 4. Product card

`resources/views/frontend/fm/components/product-card.blade.php` rebuilt to
`work/4db2d5a5-e6dd-48bc-affb-b79b5dfffe5f.png`.

The brief's "keep our extra button" is resolved as the reference's second
action. It is a **real** route, not a placeholder:
`route('cart.store.buy')` → `cart/store/buy` → `ShoppingController::
cart_store_by_now`. Measured: POST returns 302 to `/customer/checkout`, while
`cart.store` returns 302 back to the listing.

Other changes: badge reads "Save 20%" instead of a bare percentage, price moved
to its own row so a wide `Tk 1,090` cannot push the buttons out of the card,
title allowed two lines (the reference clamps to one, but "Cold Pressed
Mustard Oil" became "Cold Pressed Mus..."), and `min-height: 30rem` gives the
slightly taller card the brief asked for.

**One real CSS bug:** the "Add" button rendered as an empty pill. The old rule
set `flex: 0 0 auto` for a flex-row layout; once the actions moved into a CSS
grid that property is ignored and `min-width: 0` let the cell shrink until the
label and icon were clipped. Both properties are now set explicitly. Only a
screenshot revealed this — the markup was correct.

---

## 5. Sidebar bug found and fixed

The filter sidebar's department and brand lists rendered **completely blank**.
`$sidecategories`, `$brands`, `$brandCounts` and `$maxPrice` were never passed
by any route, and `shop-sidebar` guards every block with `?? collect()`, so an
absent value silently produced zero rows instead of failing.

Fixed in both places that build sidebar data: `FrontendController::
withListingSidebar()` and the `/products` closure in `routes/web.php`. Only
categories and brands that own a published product are listed, which also keeps
the twelve empty baby departments out of a grocery shop:

```
All categories 20 | Bakery Pantry 1 | Dairy Eggs 4 | Fresh Fruits 2
Fresh Vegetables 8 | Meat Seafood 1 | Pantry Staples 2 | Rice Flour 2
```

---

## 6. Home sections — all eleven render

The brief listed eleven `frontend.sections.*` names. Those are the **classic**
theme's sections; the modern theme has its own eight. Both were verified.

| Section | Result | Why it was missing |
|---|---|---|
| hero-banner | renders, 8 slides | fine |
| promo-banners | renders, 3 banners | `promo_banners` table was empty |
| categories | renders | fine |
| flash-sale | renders, 6 products | window had expired in April and the pivot table was empty |
| recommended / featured / best-selling / discount / collections / top-brands / service | render | fine |

The seeder now creates a running flash-sale window (yesterday → +3 days, so it
expires on its own) with six products, and three promo banners.

**Harness bugs found while testing this** — both produced phantom results:

1. Markers were matched against `document.documentElement.outerHTML` pulled over
   CDP. For an ~800 KB string that returns `undefined`, so seven sections were
   reported missing when they were all present. Markers are now counted inside
   the page.
2. The CDP browser keeps cookies between runs and already held the modern-theme
   preview cookie, so the "classic" measurement silently served the modern
   theme. Now `Network.clearBrowserCookies` runs first, and the theme actually
   served is asserted.

---

## 7. Branding

`tools/make_brand_logo.py` renders the wordmark. The old logo had "ADORA"
**baked into the image**, so the name could not be changed from the database at
all.

Convention gotcha, documented in the script: `white_logo` means the logo drawn
in **white** (for dark backgrounds) and `dark_logo` the one drawn in dark ink.
The first version had them swapped and produced an invisible white-on-white
logo in the header.

Footer fix: the footer tile is a near-white plate, so it must use `dark_logo`;
it was picking `white_logo` first.

Social icons were invisible: `social_media.icon` stores Font Awesome names
(`fab fa-facebook-f`) but the theme loads Bootstrap Icons, so they rendered as
empty circles. Known networks are now mapped to Bootstrap equivalents with a
neutral fallback.

The four identity lines sit in one 11px muted line under the copyright, verified
as a single line at 375 / 768 / 1024 / 1440.

**Not changed on purpose:** `info@adorazstyle.com`, the Facebook page
`adora.cloths` and the phone number are the owner's real contact channels.
Inventing replacements would leave customers unable to reach the shop.

---

## 8. Harnesses

| Harness | Result |
|---|---|
| `card-check.mjs` (new) | **50/50** at 375 / 768 / 1024 / 1280 / 1440 |
| `home-sections-check.mjs` (new) | **23/23**, both themes |
| `footer-branding-check.mjs` (new) | **7/7** at each of 4 widths |
| `variant-check.mjs` (new) | **9/9** — the 5-defect variant proof |
| `detail-check.mjs` (new) | **30/30** — modern product detail page |
| `cart-check.mjs` (new) | **28/28** — modern cart against the grocery seed |
| `tools/bangla_to_english.py --check` | OK |
| `php -l` on 3 touched PHP files | clean |
| `py_compile` on 2 scripts | clean |

`card-check` asserts what the screenshot exposed: every one of the 20 cards
keeps both button labels, both buttons clear 34x60 px, cards in a row share one
height, no horizontal overflow, no console errors.

Final database: 20 products / 20 images / 80 variants / 80 stock rows /
0 orders / 0 reviews, `frontend_theme` still `classic`, 20 image files on disk.

---

## 9. Variant & attribute support — proved with real evidence

The old catalogue had no working variants at all: the demo seeder only wrote a
flat `{"size":"Standard"}` `attributes_json` that never matched anything, and
the fallback SKU was `ZEVO-ORGANIC-HASS-AVOCADO-DEFAULT` (far from the
`{product_code}-DEFAULT` contract the cart resolves). Result: `checkStock`
returned no stock, add-to-cart stored `variant_id: null`.

Both are fixed in `SeedDemoProductsCommand.php` + `ShoppingController::
resolveSelectedVariant()` and now proven end-to-end in a real browser:

### The seeder contract (authoritative)

| Variant | SKU | `attributes_json` |
|---|---|---|
| DEFAULT | `{product_code}-DEFAULT` | `{"ids":[],"attrs":[]}` |
| attribute V1 | `{product_code}-V1` | `{"attrs":{"pack-size":label},"ids":{"pack-size":valueId}}` |
| attribute V2 | `{product_code}-V2` | same shape, Pack of 2 |
| attribute V3 | `{product_code}-V3` | same shape, Pack of 3 |

One shared grocery attribute `pack-size` ("Pack Size", kind custom, status 1)
with three `attribute_values`: value ids 27 Single / 28 Pack of 2 / 29 Pack of
3, multipliers 1/2/3, `price_override` = exact 2x/3x of base (no fake
discount), pack stock = `floor(stock/multiplier)`, `weight_gram` scaled from
the unit.

### Wipe → seed cycle is perfectly clean

| Check | value |
|---|---|
| `product_variants` after wipe | 0 |
| `variant_stocks` after wipe (orphan cleanup) | 0 |
| `inventory_reservations` after wipe | 0 |
| `inventory_movements` after wipe | 0 |
| attribute values after wipe (demo `pack-size` + 3 values) | 0 |
| `sizes` / `colors` leftover rows | 0 |
| after re-seed: variants | 80 |
| after re-seed: stock rows | 80 |
| after re-seed: orphan variant stocks | 0 |
| 20 × `{code}-DEFAULT` with empty attrs | 20/20 |
| flat `{"size":"Standard"}` leftovers | 0 |

### The five-defect proof (`variant-check.mjs`, 9/9 PASS)

1. **DB writes the real shape** — 80 variants / 80 stock rows / 1 attribute /
   3 values, zero orphans.
2. **DEFAULT SKU contract** — all 20 defaults are `{product_code}-DEFAULT`
   with `{"ids":[],"attrs":[]}`.
3. **`checkStock` matches on real attribute ids** — `check-stock?product_id=26&
   variant[]=27` → `ZEVO-0001-V1 @ 499`; `variant[]=29` → `ZEVO-0001-V3 @ 1497`.
4. **Every product page renders a picker** — all 20 pages (ids 26–45) render
   3 real `attributeBtn` pills labelled Single / Pack of 2 / Pack of 3 with
   `data-attribute` ids 27/28/29.
5. **Add-to-cart stores the real variant** — no selection resolves to DEFAULT
   (variant_id 106, title "Default"); Pack of 3 stores the V3 id + label +
   price 1497.

### Modern product detail page (`detail-check.mjs`, 30/30 PASS)

Screenshots captured while green: `verify/shots/variant-picker-base.png`
(base picker), `variant-picker-packed2.png` (classic, after picking Pack of 2
the price repaints to 998), `detail-picked-packed2.png` (fm theme, same pick),
`detail-desktop.png`, `detail-reviews.png` (honest 0-review state), plus the
cart shots `cart-desktop.png` / `cart-coupon.png`.

### Cart against the grocery seed (`cart-check.mjs`, 28/28 PASS)

Seed = Free Range Eggs (33) x2 + Red Rice (41) x1 @ 350 → subtotal 1050,
savings callout the REAL 290 (2×110 from eggs + 70 from rice, both read from
`old_price`). Coupon `FMTEST10` (10 % percent, running) seeds itself in the
harness; apply → discount 105, total 945, survives reload; basket change drops
it for good.

The one real 404 exposed by the suite — `uploads/category/default.png` was
referenced by four frontend views but never existed — was fixed by placing the
app's own default image at that path. It was not filtered out of the harness;
the file exists now and the check is green.