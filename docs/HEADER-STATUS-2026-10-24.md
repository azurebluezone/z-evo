# HEADER STATUS — modern (fm) storefront header

**Date:** 2026-10-24
**Theme:** `modern` (`resources/views/frontend/fm/*`), `general_settings.frontend_theme = modern`
**Scope:** header + top bar only. Classic theme (`resources/views/frontend/*`) untouched.
**Contract test:** `tests/e2e/header-layout.spec.js` — 14 tests, all green.

---

## 1. What was asked

1. Desktop-এ লোগোর পাশের duplicate **Categories pill** সরাও।
2. হেডারে মোট জায়গা কত, কোন zone কত পাবে — skill + web research দিয়ে ঠিক করো।
3. আইকনের সাইজ এক করো (বড়/ছোট মিক্স নেই), "Special offers"-এর আলাদা লাল রঙ ও lightning icon বাদ।
4. Nav menu-র measurement সমান করো — এক ফন্ট, এক কালার, এক হাইট।
5. Top bar-এ **Helpline বাঁয়ে, Language ডানে** — responsive-এও দুই পাশে।
6. Responsive-এ লোগো সেন্টার।
7. Industry best practice অনুযায়ী menu position।
8. বর্তমান অবস্থা + চলমান + পরবর্তী কাজ ডকুমেন্ট করে remote-এ push।

---

## 2. Decisions

| # | Decision | Why |
|---|---|---|
| D1 | Desktop-এ logo-র পাশের Categories pill **সরানো হয়েছে** | ওটা nav strip-এর "Browse Categories" + "Categories" লিংকেরই তৃতীয় কপি ছিল, আর শুধু `xl` (≥1200) এ দেখাত — যেখানে strip-ও দেখা যায়। সরানোর ফলে search field 66px চওড়া হয়েছে। |
| D2 | ≥1024px-এ header একটি **3-column grid**: `minmax(180px,220px) \| minmax(280px,1fr) \| auto` | Flex-এ brand + pill + search + actions একসাথে প্রতিযোগিতা করত, তাই search আগে চেপে যেত। Grid-এ brand-এর ceiling আছে, search একমাত্র flexible cell, actions intrinsic। |
| D3 | 1024–1279px band-এ brand column `170px`, gutter `16px` | এই band-এ জায়গা সবচেয়ে কম (976px interior); logo 144px, তাই 170px-এ কিছুই কাটে না, search 66px পায়। |
| D4 | <1024px-এ লোগো **absolute center** (`left:50%` + translate) | Flex-এ দুই পাশের cluster-এর intrinsic width বদলালে (badge এলে) লোগো সরে যেত। Absolute centering-এ badge কখনো লোগো নড়ায় না — 768/390/320 তিনটিতেই delta 0। |
| D5 | সব header icon **20px** (`1.25rem`), hit box **44×44** | আগে search 18, wishlist 20, account 24, cart 16–18 — চার সাইজ, তিন line-box। এখন hamburger থেকে basket পর্যন্ত একই 20px। |
| D6 | Nav link: `14px / 600 / #374151 / 36px height / 12px padding / 4px gap`; active = `700` + `#ecfdf5` pill + `#1b5e20` | "Special offers" এর লাল রঙ + lightning বাদ; emphasis শুধু active state-এ, সবার জন্য একই ট্রিটমেন্ট। |
| D7 | Top bar দুই প্রান্তে pinned: helpline বাঁয়ে, language (+ WhatsApp থাকলে) ডানে | আগে helpline `md`-এর নিচে hidden ছিল, তাই ফোনে ডান দিক ফাঁকা থাকত। এখন দুই control-ই সব width-এ থাকে; centering margin দিয়ে নয়, `justify-between` দিয়ে। |
| D8 | Grid-এর gap CSS-এর মালিকানায়; row-এ কোনো `gap-*` utility নেই | **Bootstrap 5.3** `.gap-3{gap:1rem!important}` পাঠায়, যা Tailwind-এর `.gap-3` (0.75rem) এবং আমাদের `column-gap` দুটোকেই হারায়। এতে search column 16px কম পাচ্ছিল। |

---

## 3. Measured space budget (Chromium, DPR 1, `127.0.0.1:8000`)

| Viewport | Interior row | Brand column | Search | Actions | Row h | Nav strip |
|---:|---:|---:|---:|---:|---:|---:|
| 1440 | 1232 | 220 (logo 144.3×48) | **790.8** | 173.2 | 74 | 49 |
| 1280 | 1232 | 220 (logo 144.3×48) | **790.8** | 173.2 | 74 | 49 |
| 1200 | 976 | 170 (logo 144.3×48) | **600.8** | 173.2 | 74 | 49 |
| 1024 | 976 | 170 (logo 144.3×48) | **600.8** | 173.2 | 74 | 49 |
| 768 | 720 (flex) | logo 132.3×44, centered Δ0 | drawer | 148 | 64 | hidden |
| 390 | 366 (flex) | logo 108.3×36, centered Δ0 | drawer | 96 | 64 | hidden |
| 320 | 296 (flex) | logo 96×36, centered Δ0 | drawer | 96 | 64 | hidden |

- Grid gaps: 24px (≥1280), 16px (1024–1279). Flex gap: 12px (≥576), 8px (<576).
- Logo→actions clearance: 768 = 145.9px, 390 = 32.9px, 320 = **4px** (tight but no overlap, no sideways scroll).
- Top bar gutters (help.left / width − lang.right): 1440 = 104/104, 768 = 24/24, 390 = 12/12, 320 = 12/12 — symmetric at every width.
- One icon size everywhere: `20px` (search submit, account, wishlist, basket, AI mic/camera, hamburger). The offcanvas title's magnifier is a heading ornament, not a control, and is excluded from the contract.

---

## 4. Responsive contract

| Band | Header behaviour |
|---|---|
| ≥1280 | Grid `220 \| 1fr \| auto`, gap 24. Search inline, full width of its column. |
| 1024–1279 | Grid `170 \| 1fr \| auto`, gap 16. Search inline. |
| 768–1023 | Flex: hamburger left, **logo absolutely centred**, actions right. Search moves to `#fmSearchDrawer` (phone trigger), nav strip hidden, drawer carries all 6 destinations. |
| 576–767 | Same as above; wishlist/account remain visible; cart shows basket only. |
| <576 | Same; account + wishlist move into the drawer, cart collapses to a 44×44 pill with a 16px badge. |
| Every width | Top bar: helpline left, language right. No horizontal scroll (`scrollWidth === innerWidth`). |

---

## 5. Research basis

`websearch` returned no results for this environment, so two articles were fetched directly and used as the reference set:

- **Uxcel — header design examples**: keep the header to 3–4 functional zones (brand, search, utilities), one visual weight for navigation, and never duplicate a destination in two header controls.
- **CustomFit.ai — e-commerce header design**: search is the widest flexible element on desktop; utility icons share one size and one hit area; logo width is capped rather than proportional to viewport; language/currency belongs in the thin utility strip, not the main bar.
- **Tubik Studio — website header best practices** (fetched, used for the "one row of destinations" and "active state carries emphasis" points).

Adopted: 3-zone grid, capped brand column, search as the only flexible cell, single icon token, utility strip with ends pinned. Not adopted: mega-menu, sticky shrinking header, promo countdown (no requirement, and each adds JS weight).

---

## 6. Evidence

```bash
# contract test (14/14)
cd tests/e2e && ./node_modules/.bin/playwright test header-layout.spec.js --reporter=line
#   → 14 passed (42.1s)

# geometry at 8 widths
node /tmp/opencode/header-metrics.mjs   # → /tmp/opencode/metrics.json

# dropdowns, drawers, pages
node /tmp/opencode/header-verify.mjs
```

Verified in the run:

- `categoriesPillNextToLogo: false`
- `navStyles: ["14px|600|rgb(55, 65, 81)|36px|12px"]` (one set for all non-active links)
- `headerIconSizes: ["20px"]`, `searchSubmitIcon: "20px"`
- account menu `left 1046.8 → right 1206.8`, language menu `1176 → 1336` — both inside a 1440 viewport
- drawer: 6 links, `bi-lightning-charge-fill` count `0`
- search drawer interactive icons: `["20px"]`
- pages 200 with the shared header: `/`, `/products`, `/categories`, `/shop/cart`, `/about-us`, `/track-order`, `/hot-deals`, `/site/contact-us`
- `/offers` → 200 but **classic header** (`/offer` route), pre-existing (see §8.4)
- only 404 across 7 pages × 4 widths: `/uploads/category/default.png` (one unique URL, repeated per page load; no category image seeded)
- `analytics` beacon `POST /events/collect` aborts (`net::ERR_ABORTED`) — pre-existing, not a header request
- `scrollWidth === innerWidth` at 1440/1280/1200/1199/1024/768/390/320

Screenshots: `/tmp/opencode/header-{1440,1280,1024,768,390,320}.png`, `drawer-nav-390.png`, `drawer-search-390.png`.
Backups (pre-change): `/tmp/opencode/header-bak/`.

---

## 7. Currently ongoing

- **None in the header itself.** The contract is green; the remaining items below are separate tasks.

## 8. Next (ordered)

1. **Populate the top bar data.** `general_settings.support_phone` and `whatsapp_number` are `NULL` and `contacts` is empty, so the bar renders "Helpline: Add your number". Fill via admin → settings (no code change needed).
2. **Fix the 4 legacy e2e specs** (`homepage-journey`, `user-journey`, `quantity-button-performance`, `phase2-optimistic-reconcile`, `variant-switch-no-reload`). They query classic-theme selectors that do not exist in the modern theme: `.product-card` = 0, `.addToCart` = 0, `.product-card-qty-plus` = 0 on `/` today (`.fm-product-card` = 36). **Pre-existing drift, not caused by this change** — the header edit touches no product-card markup. Either port them to `fm-*` selectors or scope them to the classic theme.
3. **Bootstrap vs Tailwind `gap-*` collision audit.** Bootstrap 5.3's `.gap-*` uses `!important` with a different scale (`.gap-3` = 1rem vs Tailwind 0.75rem), so every element relying on Tailwind `gap-*` is silently off. Header is fixed; other sections should be audited with the same CDP `CSS.getMatchedStylesForNode` method.
4. **`/offers` renders the classic header.** The route resolves to `/offer` and returns the classic layout (`fmHeader: false`, `.header-area` present). Pre-existing; needs a modern view or a redirect decision.
5. **Move the verification scripts** (`/tmp/opencode/header-metrics.mjs`, `header-verify.mjs`) into `tests/e2e/tools/` so the measurements are reproducible from the repo.
6. **AI triggers are static** (`fm-ai.js` drawer exists; product-photo path not wired). Separate feature.

---

## 9. Files changed

| File | Change |
|---|---|
| `resources/views/frontend/fm/partials/header/primary-header.blade.php` | Categories pill removed; `fm-header-row` / `fm-header-logo` / `fm-header-search` hooks; gap utilities removed; space-budget comment |
| `.../header/quick-actions.blade.php` | `fm-header-actions`; all glyph utilities dropped (CSS owns 20px) |
| `.../header/account-dropdown.blade.php` | glyph size to the shared token |
| `.../header/announcement-bar.blade.php` | helpline left / language + WhatsApp right, both ends on every width |
| `.../header/nav-links.blade.php` | lightning icon + `fm-nav-offers` removed; one label treatment |
| `.../header/primary-navigation.blade.php` | `fm-navbar-start`, `fm-nav-chevron` (inline `font-size` style gone) |
| `.../header/mobile-nav-drawer.blade.php` | stale "Categories pill" wording corrected |
| `.../header/search-bar.blade.php` | submit glyph `text-xs` removed |
| `public/frontend/css/fm-sections.css` | topbar, grid, centered logo, nav metrics, icon token, cart badge |
| `public/frontend/css/fm-ai.css` | AI trigger glyph 20px |
| `public/frontend/js/fm-app.js` | comment: desktop pill no longer a pulse trigger |
| `tests/e2e/header-layout.spec.js` | **new** 14-test geometry contract |

## 10. Rollback

```bash
cp -a /tmp/opencode/header-bak/header/. resources/views/frontend/fm/partials/header/
cp /tmp/opencode/header-bak/fm-sections.css public/frontend/css/fm-sections.css
cp /tmp/opencode/header-bak/layouts.blade.php resources/views/frontend/fm/layouts.blade.php
php artisan view:clear
```
