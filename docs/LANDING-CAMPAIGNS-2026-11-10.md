# Landing Campaigns — Implementation and Evidence

- Date: 2026-11-10
- Project: `today` 1.0.0 (Laravel), live MySQL database `z_evo`
- Scope: one active landing campaign per active template, each tied to a real catalog product, every supported template component enabled
- Server used for verification: `APP_URL=http://127.0.0.1:8010 php artisan serve --host=127.0.0.1 --port=8010`

## 1. Campaign map

| # | Template | Campaign (id) | Product (id) | Campaign price | Live URL |
| ---: | --- | --- | --- | ---: | --- |
| 1 | `default` | `red-apple-fresh-offer` (767) | Red Apple (20) | 279 | `http://127.0.0.1:8010/campaign/red-apple-fresh-offer` |
| 2 | `herbal-health` | `raw-honey-herbal-offer` (768) | Artisan Raw Honey (14) | 749 | `http://127.0.0.1:8010/campaign/raw-honey-herbal-offer` |
| 3 | `template-1` | `mustard-oil-countdown-offer` (769) | Mustard Oil (1L) (24) | 289 | `http://127.0.0.1:8010/campaign/mustard-oil-countdown-offer` |
| 4 | `template-2` | `red-rice-modern-offer` (770) | Red Rice (23) | 229 | `http://127.0.0.1:8010/campaign/red-rice-modern-offer` |
| 5 | `template-3` | `sourdough-brand-offer` (771) | Artisan Sourdough Bread (22) | 129 | `http://127.0.0.1:8010/campaign/sourdough-brand-offer` |
| 6 | `sample-template` | `desi-ghee-sample-offer` (772) | Desi Ghee (25) | 649 | `http://127.0.0.1:8010/campaign/desi-ghee-sample-offer` |

All six rows: `status=1`, inside the active window `2026-11-10 .. 2026-12-10`, `sort_order` 1–6, `campaign_price_enabled=1`, and all five `show_*` flags (`features`, `benefits`, `testimonials`, `faq`, `cta`) set to `1`.

## 2. Component coverage

The public templates render these native sections; the campaign content supplies the rows.

| Template | Sections enabled and populated |
| --- | --- |
| `default` | Hero title/subtitle/CTA, product description, order form with hidden `campaign_id` |
| `herbal-health` | Hero, benefits (3 rows), testimonials (3 rows), order form |
| `template-1` | Countdown, features (3), why-us (3), contact phone, order form |
| `template-2` | Countdown, features (3), why-us (3), contact phone, order form |
| `template-3` | Hero video, product slider/gallery (3 product images), features (3), benefits (3), packages (2), contact/social links, order form |
| `sample-template` | Features (3), benefits (3), testimonials (3), FAQs (4), order form |

Content is stored in the campaign columns and in the normalized `extra_fields` JSON (`CampaignContent` whitelist); list rows use the supported `features`, `benefits`, `testimonials`, `faqs`, `why_us`, `packages` structures.

## 3. Seed and verification entry points

- `tools/seed_landing_campaigns.php` — idempotent by campaign slug. It updates the product's first `productimages` row and gallery rows, then creates/updates each campaign with its content, pricing, flags, window and sort order. Safe to re-run.
- `tools/verify_landing_campaigns.php` — contract check. It validates the six DB rows, distinct templates/products, component flags, required content rows, the public HTTP 200 response, section markers and the hidden `campaign_id`. It prints `LANDING_TEMPLATES_LIVE_OK` and exits 0 only on a full pass.

Terminal evidence saved in:

- `work/audit/landing-campaigns/verify-terminal.log`
- `work/audit/landing-campaigns/phpunit-end-to-end.log`
- `work/audit/landing-campaigns/phpunit-phase4-admin.log`

Latest terminal run (2026-11-10 08:45 +06:00):

```text
CHECK red-apple-fresh-offer            id=767 template=default        product=Red Apple                http=200 html=43144
CHECK raw-honey-herbal-offer           id=768 template=herbal-health  product=Artisan Raw Honey        http=200 html=51312
CHECK mustard-oil-countdown-offer      id=769 template=template-1     product=Mustard Oil (1L)         http=200 html=50941
CHECK red-rice-modern-offer            id=770 template=template-2     product=Red Rice                 http=200 html=50189
CHECK sourdough-brand-offer            id=771 template=template-3     product=Artisan Sourdough Bread  http=200 html=54845
CHECK desi-ghee-sample-offer           id=772 template=sample-template product=Desi Ghee                http=200 html=53866
LANDING_TEMPLATES_LIVE_OK
EXIT=0
```

Regression tests (live MySQL, tests clean their own rows):

- `php artisan test --filter=CampaignEndToEndTest` → 1 passed, 46 assertions, exit 0
- `php artisan test --filter=CampaignPhase4AdminTest` → 9 passed, 59 assertions, exit 0

## 4. Browser QA and screenshots

Full report: `work/audit/landing-campaigns/QA-REPORT.md`
Raw evidence: `work/audit/landing-campaigns/qa-report.json`
Retest evidence: `work/audit/landing-campaigns/template-1-retest.json`

Browser: Playwright Chromium, desktop `1440x900`, mobile `390x844`, cookie banner rejected, AOS scrolled into view. Dev-only Debugbar was removed before the final measurements; it was the sole cause of an earlier wide mobile measurement.

| Template | Desktop screenshot | Mobile screenshot |
| --- | --- | --- |
| `default` | `work/audit/landing-campaigns/default-desktop.png` (1440x900) | `default-mobile.png` (390x844) |
| `herbal-health` | `herbal-health-desktop.png` (1440x3717) | `herbal-health-mobile.png` (390x4600) |
| `template-1` | `template-1-desktop.png` (1440x3126) | `template-1-mobile.png` (390x3334) |
| `template-2` | `template-2-desktop.png` (1440x2769) | `template-2-mobile.png` (390x4130) |
| `template-3` | `template-3-desktop.png` (1440x5315) | `template-3-mobile.png` (390x5467) |
| `sample-template` | `sample-template-desktop.png` (1440x3375) | `sample-template-mobile.png` (390x3955) |

Measured result: all six pages HTTP 200, titles match the product, zero horizontal overflow on both viewports, zero own console errors, product images load, and hidden `campaign_id` 767–772 is present. `template-3` has one external YouTube warning (`No available adapters.`), not a page error.

Mustard-oil defect D1 (wrong olive-oil photo) is **FIXED**: hero and slider images now load `uploads/product/mustard-oil-demo.svg` (`naturalWidth=267`), desktop hero box `846x476`, mobile `352x198`, both screenshots refreshed and the SVG label is visible on desktop. Report row and retest JSON updated.

## 5. Demo content disclosure

This is seeded demo content, not verified production content:

- Contact phone `01XXXXXXXXX`, email `support@zevo.example`, social URLs `https://example.com/zevo/*`, and YouTube embed `https://www.youtube.com/embed/7Un6mV2YQ54` are placeholders/demo links. No real support channel or brand video exists in the project.
- Testimonials are sample copy, not real customer reviews.
- Prices are seeded campaign demo prices. Stock, shipping cost and payment settlement were not validated.
- Product images are representative demo assets, not photographs of the seller's physical stock:
  - Red rice: derived from Wikimedia Commons `File:Jiangxi red glutinous rice.jpg` (CC0, Fumikas Sagisavas), cropped to 16:9.
  - Mustard oil: generated SVG demo packshot for this task, labelled `DEMO PACKSHOT`.
  - The other demo JPGs were supplied as demo assets; their source and licence are not recorded in the repo. Treat them as demo-only and replace them with licensed product photography before commercial use.

## 6. Known limitations and not-tested paths

- `default` and `sample-template` do not render a visible price, product image, or name/phone/address fields in their own document flow; their CTA posts to `/landing-order` where checkout collects the details. This is the existing template behaviour, not a regression.
- Live order submission, payment, delivery/Pathao, Meta CAPI and TikTok API calls were not tested with real credentials or a real courier/payment account. The automated `CampaignEndToEndTest` covers the landing → cart → checkout → order snapshot path against the live DB.
- The site was verified on the Laravel development server at `127.0.0.1:8010`, not a production web server/CDN.
- `php artisan serve` on port 8000 was already occupied; port 8010 was used with matching `APP_URL`.
- Before launch, replace every placeholder above and re-check the database copy. Re-run `php tools/verify_landing_campaigns.php` after any content change.

## 7. Reproduction

```bash
cd "/home/sahon/Desktop/new evo"
APP_URL=http://127.0.0.1:8010 php artisan serve --host=127.0.0.1 --port=8010
php tools/seed_landing_campaigns.php
php tools/verify_landing_campaigns.php
php artisan test --filter=CampaignEndToEndTest
php artisan test --filter=CampaignPhase4AdminTest
```

## 8. Files changed

- `tools/seed_landing_campaigns.php` — campaign/product/image seeding; mustard image path now `.svg`
- `public/uploads/product/mustard-oil-demo.svg` — generated demo packshot
- `public/uploads/product/red-apple-demo.jpg`, `raw-honey-demo.jpg`, `desi-ghee-demo.jpg`, `red-rice-demo.jpg`, `sourdough-demo.jpg`, `sourdough-slice-demo.jpg`, `sourdough-loaf-demo.jpg` — active demo images
- `work/audit/landing-campaigns/` — QA report, raw JSON, 12 screenshots, console logs, retest gate and evidence
- `work/landing-campaigns-implementation.md` — this document
