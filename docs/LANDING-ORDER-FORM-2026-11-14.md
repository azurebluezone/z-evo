# Landing page order form - the normal order fields, placed on the landing page

- Date: 2026-11-14
- Project: `today` 1.0.0 (Laravel 10), live MySQL database `z_evo`
- Goal contract: `gc-mwgbtuex` - check `test -f tests/Feature/LandingOrderPlacementTest.php && php artisan test --filter=LandingOrderPlacementTest && echo LANDING_ORDER_FORM_OK`
- Scope: the nine landing designs (`ht2`, `t1`, `th3`-`th9`), the shared landing shell, the landing order endpoint and the order writing code shared with the storefront checkout.

Supersedes the "landing order adds to the cart and bounces to the checkout" description in `docs/LANDING-CAMPAIGNS-2026-11-10.md` for the landing flow. The storefront checkout itself is unchanged.

## 1. What changed

| Area | Before | After |
| --- | --- | --- |
| Landing order form | one `quantity` select, no customer fields | the fields of a normal product order: `name`, `phone`, `address`, `area`, `payment_method` - plus `quantity`, `product_id`, `campaign_id` |
| Landing order POST | added the product to the session cart, redirected to `/customer/checkout` | writes the order (order, shipping, payment, order_details) and redirects to `/customer/order-success/{id}` |
| Order writing | inline in `CustomerController@order_save` | `App\Services\OrderPlacementService`, called by the checkout AND the landing form |
| Form design | the shared `.zlt-*` card | the design of the order section of the nine HTML designs in `~/Videos/lp` (numbered steps, selectable option cards, icon inputs, order summary) |

## 2. The form

`resources/views/frontend/landing/partials/order-section.blade.php` is the single order form of every design (the nine Blade views extend `layouts.templates`, which includes this partial). Its structure follows the HTML designs:

1. header - cash-on-delivery badge, heading, one short line;
2. one card with the application logo and the product line (image, name, offer price);
3. step 1 - quantity as selectable cards (the "pack option" pattern of the designs);
4. step 2 - delivery details: name, mobile (11 digits), full address, delivery area (the active `shipping_charges` rows with their charge);
5. step 3 - payment method: cash on delivery, plus bKash when its gateway row is enabled;
6. order summary - line price, old price, delivery charge, total;
7. submit button and the three trust lines.

The CSS lives in `public/frontend/landing/templates-layout.css` (`.zlt-order-head`, `.zlt-step*`, `.zlt-option*`, `.zlt-input-wrap`), so the form renders on all nine designs without Tailwind or Bootstrap.

Field names are the checkout names, so the saved order holds the values a normal product order holds: `name`, `address`, `phone`, `area` (the `shipping_charges` id) and `payment_method` (`Cash On Delivery` / `bkash`).

## 3. The backend

`App\Services\OrderPlacementService`:

- `place(array $lines, array $customer, array $options)` writes the `orders`, `shippings`, `payments` and `order_details` rows and the inventory reservation, computes the money from the line prices (never from the request) and runs the courier fraud check;
- `trackPurchase(Order $order, array $lines, array $options)` fires the server-side purchase events (Meta, TikTok, GA4 and the shop's own event log) with the same deterministic purchase id the confirmation page uses.

`CustomerController@order_save` now calls the service instead of writing those rows inline; the storefront checkout behaviour (coupon revalidation, campaign revalidation, guest account, notifications, SMS, redirect) is unchanged.

`ShoppingController@landingOrder` validates the customer fields with the checkout rules, resolves the campaign price through `CampaignPricing`, resolves the guest account through `GuestCheckoutAccountService`, places the order and redirects to the order-success page (or to the bKash gateway when bKash is the chosen method).

## 3b. One form, nine looks, nine positions

The form stays ONE shared partial - the fields, the validation and the order writing are identical everywhere - but every design owns its order section:

| Design | Position (like its HTML twin) | Look |
| --- | --- | --- |
| `ht2` Premium Collection | before the FAQ | slate-50 band, white card, red #dc2626, radius 1rem |
| `t1` Funnel Offer | after the product showcase | dark #161b24 card with an amber #f59e0b border, green totals |
| `th3` Premium Wall Art | before the FAQ | white card, emerald step badge, red submit |
| `th4` Artisan Honey | before the FAQ | white card radius 20px, emerald steps, rose CTA |
| `th5` Natural Honey | before the FAQ | warm stone band, honey amber options, red pill submit |
| `th6` Rose Editorial Abaya | last | rose gradient band, rose card radius 1.5rem |
| `th7` Fashion Editorial Abaya | before the FAQ | white card radius 20px, orange #c2410c steps, orange pill submit |
| `th8` Dark Premium Panjabi | last | dark #161f36 card, gold #e6af2e accents and submit |
| `th9` Soft Commerce Hoodie | last | rose card radius 1.5rem, rose gradient submit |

How it works:

- `LandingTemplateVars::markOrderRendered()` / `orderRendered()` - the form marks itself as rendered; the shared layout (`resources/views/layouts/templates.blade.php`) skips its own include then, so a page can never show two order forms.
- the six designs that place the form themselves include `frontend.landing.partials.order-section` inside their own content (`ht2`, `th3`, `th4`, `th5`, `th7` before the FAQ, `t1` after the product showcase); `th6`, `th8` and `th9` keep it in the layout, after the whole content.
- each design's own stylesheet (`public/frontend/landing/<design>.css`) carries a scoped block `body[data-landing-template="<design>"]` that re-tints the shared `.zlt-*` form. The shared variables (`--zlt-red`, `--zlt-ink`, `--zlt-line`, ...) are overridden per design, and the dark designs (t1, th8) also get their own surface, input and text colours.

## 4. Verification

| Command | Observed |
| --- | --- |
| `php artisan test --filter=LandingOrderPlacementTest` | 3 passed / 156 assertions |
| `php artisan test --filter=LandingOrderDesignTest` | 3 passed / 156 assertions (one form per design, the position of the form, the scoped block in every design stylesheet) |
| `node work/landing_order_design_shots.mjs` | `ORDER_SHOTS_OK shots=9` - one screenshot of the order section per design under `work/shots/` |
| `php artisan test --filter=LandingCampaignOrderTest\|LandingOrderFormTest\|LandingBrandLogoTest\|LandingOrderPlacementTest` | 14 passed / 410 assertions |
| `php artisan test --filter='CampaignCheckoutRevalidationTest\|CampaignCouponEligibilityTest\|CampaignEndToEndTest'` | 11 passed / 130 assertions |
| `php artisan test` | 176 passed, 7 failed (the same 7 that fail at `df99293` - courier x2, sample-template x2, campaign attribution, home rails, analytics chart) |

The three test files that needed a campaign line INSIDE the cart now seed it with `tests/Feature/SeedsCampaignCartLines.php` (the same options the landing order used to write), so the checkout revalidation and the coupon eligibility rules stay covered.

## 5. Known limits

- The storefront has no other entry point that puts a campaign-priced line into the cart; the landing form is the campaign funnel and it now places the order directly. The checkout-side campaign rules stay in the code and stay tested, but they only fire for a cart line that was seeded by hand.
- A visitor who lands on the campaign page and orders there never sees the storefront cart. A campaign order is a single product line by design.
