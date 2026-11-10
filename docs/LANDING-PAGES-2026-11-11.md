# Landing Pages manager + landing campaigns — current state

- Date: 2026-11-11
- Project: `today` 1.0.0 (Laravel 10), live MySQL database `z_evo`
- Scope: every row of the admin Landing Pages manager (`/admin/landing-pages/manage`) is a real page for one active template, tied to its campaign and catalog product, with the order form wired.
- Verified server: `APP_URL=http://127.0.0.1:8010 php artisan serve --host=127.0.0.1 --port=8010`

Two separate features exist and both are live:

| Feature | Table | Public URL | Admin |
| --- | --- | --- | --- |
| Landing campaigns | `campaigns` | `/campaign/{slug}` | Landing Page → Campaign |
| Landing pages | `landing_pages` | `/landing-pages/{slug}` | Landing Page → Landing Pages |

## 1. Landing pages map (this session)

Six rows, one per active template. The two legacy demo rows were repurposed in place; four rows were added.

| id | Template (view) | Slug | Product (id) | Campaign (id) | Price | Public URL |
| ---: | --- | --- | --- | ---: | ---: | --- |
| 1 | Default (`default`) | `red-apple-fresh-offer` | Red Apple (20) | 767 | 279 | `/landing-pages/red-apple-fresh-offer` |
| 2 | Herbal Health (`herbal-health`) | `raw-honey-herbal-offer` | Artisan Raw Honey (14) | 768 | 749 | `/landing-pages/raw-honey-herbal-offer` |
| 70 | Product Countdown (`template-1`) | `mustard-oil-countdown-offer` | Mustard Oil 1L (24) | 769 | 289 | `/landing-pages/mustard-oil-countdown-offer` |
| 71 | Modern Product (`template-2`) | `red-rice-modern-offer` | Red Rice (23) | 770 | 229 | `/landing-pages/red-rice-modern-offer` |
| 72 | Brand Product (`template-3`) | `sourdough-brand-offer` | Artisan Sourdough Bread (22) | 771 | 129 | `/landing-pages/sourdough-brand-offer` |
| 73 | Sample Template (`sample-template`) | `desi-ghee-sample-offer` | Desi Ghee (25) | 772 | 649 | `/landing-pages/desi-ghee-sample-offer` |

Legacy demo rows/slugs `demo-default-offer` and `demo-second-offer` no longer exist.

### Row content source

Content is copied from the matching campaign so campaign and landing page cannot drift:

- `title` = product name; `subtitle` = campaign `hero_subtitle` (fallback `short_description`)
- `description` = campaign `description`; `cta_text` = campaign `hero_button_text` (fallback `অর্ডার করুন`)
- `image_url` = product image (accessor / first product image)
- `extra_fields` = campaign `extra_fields` + `product_id`, `campaign_id`, `old_price` (product `old_price`), `new_price` (campaign `campaign_price`), `show_features/benefits/testimonials/faq/cta` = true
- `template-3` row also gets `gallery_images` from the product gallery
- `cta_link` = `/landing-order` (relative, so it works on any host/port)
- `status` = 1, `sort_order` = 1..6

## 2. Files added

| Path | Purpose |
| --- | --- |
| `tools/seed_landing_pages.php` | Idempotent seeder: six rows, repurposes legacy rows, deletes demo slugs |
| `tools/verify_landing_pages.php` | Contract check (DB rows + public pages + admin list); prints `LANDING_PAGES_ALL_UPDATED_OK` |
| `tools/seed_landing_campaigns.php` | Campaign/product seeder from the campaign phase |
| `tools/verify_landing_campaigns.php` | Campaign check; prints `LANDING_TEMPLATES_LIVE_OK` |
| `tests/Feature/LandingPageAdminTest.php` | Admin landing-page feature tests (5 tests) |

Browser QA harness and artifacts live in `work/audit/landing-pages/` (`qa.mjs`, `qa-report.json`, 12 screenshots). `/work/` is gitignored, so those stay local by design.

## 3. Verification evidence (run on 2026-11-11)

```bash
php tools/seed_landing_pages.php      # exit 0
php tools/verify_landing_pages.php    # exit 0
php artisan test --filter=LandingPageAdminTest
```

`verify_landing_pages.php` output:

```text
CHECK red-apple-fresh-offer            id=1  template=default          product=Red Apple                http=200 html=42243
CHECK raw-honey-herbal-offer           id=2  template=herbal-health    product=Artisan Raw Honey        http=200 html=50199
CHECK mustard-oil-countdown-offer      id=70 template=template-1       product=Mustard Oil (1L)         http=200 html=49859
CHECK red-rice-modern-offer            id=71 template=template-2       product=Red Rice                 http=200 html=49198
CHECK sourdough-brand-offer            id=72 template=template-3       product=Artisan Sourdough Bread  http=200 html=53856
CHECK desi-ghee-sample-offer           id=73 template=sample-template  product=Desi Ghee                http=200 html=52879
ADMIN /admin/landing-pages/manage      http=200 rows=7 html=89435
LANDING_PAGES_ALL_UPDATED_OK
```

The check fails unless: exactly six rows, no Demo title/slug, distinct templates/products/campaigns, `product_id`/`campaign_id`/image/prices present, template content rows present, each public page returns HTTP 200 with template markers and hidden `product_id` + `campaign_id`, and the admin list renders all six rows.

`LandingPageAdminTest`: 5 passed (23 assertions) — index, show, update route, template config, public index.

Browser QA (Playwright, headless Chromium):

- 6 pages × 2 viewports (desktop 1440×900, mobile 390×844) = 12 runs, all HTTP 200
- horizontal overflow: none (`scrollWidth` = 1440/1440 and 390/390 on every page)
- console errors: 0 per page (fresh browser context per page)
- exactly 1 `form[action="/landing-order"]` per page, with hidden `product_id` and `campaign_id`
- screenshots: `work/audit/landing-pages/{template}-{desktop,mobile}.png`

## 4. Campaign phase (previous session, same live DB)

Six active campaigns 767–772, one per template, window 2026-11-10 .. 2026-12-10, all `show_*` flags on. `php tools/verify_landing_campaigns.php` → `LANDING_TEMPLATES_LIVE_OK` (exit 0). `CampaignEndToEndTest` 1 passed / 46 assertions; `CampaignPhase4AdminTest` 9 passed / 59 assertions. Full campaign record: `docs/LANDING-CAMPAIGNS-2026-11-10.md`.

## 5. Known limitations and disclosures

- `default` and `sample-template` do not render the price string or the product `<img>` (the sample hero uses a CSS background). The data is stored; the template views simply have no price/image slot. No template code was changed for this task.
- Screenshots were captured after injecting `[data-aos]{opacity:1!important;transform:none!important}`; AOS otherwise keeps sections at opacity 0 in headless full-page capture. This is a capture workaround, not a page fix.
- An earlier shared-context QA run logged 422 console entries on some pages; with a fresh browser context per page the count is 0. Treated as cross-page contamination, not a page-owned error.
- `landing_pages.cta_link` is now the relative `/landing-order`. The admin edit form validates `cta_link` as `url`, so editing this value through the UI may require an absolute URL. Seeding bypasses the form validation.
- Demo product images live in `public/uploads/product/`, which is gitignored (`/public/uploads/*`). A fresh checkout will not have them; run the seeders in an environment that has the demo assets.
- Image provenance: red rice = Wikimedia Commons CC0 (`Jiangxi red glutinous rice.jpg`, Fumikas Sagisavas, 16:9 crop); mustard = locally generated `mustard-oil-demo.svg`; provenance of the remaining demo JPGs is not recorded.

## 6. Placeholders (not real business data)

Phone `01XXXXXXXXX`, email `support@zevo.example`, social links `https://example.com/zevo/*`, YouTube embed `7Un6mV2YQ54`, testimonials are sample copy.

## 7. NOT_TESTED

- live order submit → cart/order persistence for these six pages
- payment gateway, Pathao courier, Meta CAPI, TikTok API
- real support phone/email/social channels
- admin create/edit/destroy through the UI (rows were written by the seeder; the update route is covered by `LandingPageAdminTest`)
