# Admin — Footer rebuild and storefront link contract

**Date:** 2026-10-31
**Page:** `GET /admin/website-setup/footer` (`admin.website-setup.footer` → `Admin\WebsiteSetupController@footer`)
**Save:** `POST /admin/website-setup/footer` (`admin.website-setup.footer.update`)

---

## 1. What was asked

- Rebuild the footer screen without helper-text walls; keep section titles out of the form.
- Manage the footer **about** paragraph with the Quill editor and a **400 visible-character** cap.
- Replace the old text-only social preset select with a **real icon picker** that cannot break alignment.
- Add a repeater for **footer external links** (the storefront's four link columns).
- Keep both storefront templates (`classic` and `fm`) reading the same saved data.
- Update the project documentation as the work lands.

## 2. Screen contract — three cards, one form

The page is a single `<form id="footerContentAdminForm">`; card 2 and the social card's controls attach to it
through the HTML5 `form` attribute, so both Save buttons post the same request.

| Card | Fields | Notes |
|---|---|---|
| **Footer content** | about text (Quill), copyright, contact address / phone / email | Quill toolbar is the shared admin preset; live `visible / 400` counter blocks submit over the cap |
| **Footer links** | group, label, URL, new-tab switch per row | add/remove rows client-side; server clamps at 30 and drops blank rows |
| **Social icons** | title, platform picker, link, brand colour, status | add form and edit modal use the same picker partial; the table lists saved rows |

Interaction states covered: add/remove link rows, empty-state row (hidden as soon as one row exists),
live link count badge (`0 links` / `1 link` / `2 links`), picker open/close, selected option
(`aria-pressed`, preview icon, hidden `icon` value, label, brand colour), edit modal prefill,
disabled add button at 30 rows.

### Layout fixes that are easy to regress

- The form uses Bootstrap's 12-column grid: `col-md-4` for the three-field rows
  (Name/Platform/Link and address/phone/email) and `col-md-6` for payment/copyright.
  No `col-lg` breakpoints — the fields divide at 768px instead of waiting for 992px.
- The icon picker menu is a Bootstrap grid too: `row row-cols-2 row-cols-lg-3 g-1`,
  one `.col` per option. It is 2 columns below 992px (the control is ~235px there and
  three columns would clip the labels) and 3 columns from 992px (menu ~356px). The old
  custom `.icon-picker__grid` CSS is gone.
- The picker **control** is a `.form-select`, not a `.btn`: the template's pickers are
  selects, and the button was 10px short (38px vs 48px) with a 22px left inset instead of
  15px, so its text never lined up with the Name/Link inputs. As `.form-select` it inherits
  the theme box — 48px tall, 16px text offset (1px border + 15px padding), 8px radius,
  1px border in the same colour, the theme caret — at 1440, 800 and 375. One rule stays
  local (`text-align: start`) because it is a `<button>`, not a real select.
- `#footer-about-editor .ql-editor { min-block-size: 7.5rem; }` — the shared Quill CSS forces
  15rem, which is wasted space for a 400-character blurb.
- `admin-responsive.css` stacks every `.card-header.d-flex` below 576px and stretches its children.
  These three headers only hold a title and a count badge, so the view overrides that locally back
  to `flex-direction: row; align-items: center` at `<576px`.
- The links table wrapper carries `position-relative`: the visually-hidden header label is
  absolutely positioned, and without a positioned ancestor it added page-wide horizontal scroll
  at 375px even though the table itself scrolled inside `.table-responsive`.
- Table CSS overrides `btn-text-danger`'s colour, so destructive buttons also carry `text-danger`.

## 3. Data model

### `general_settings.footer_links` (JSON, nullable)

Added by `database/migrations/2026_10_31_000001_add_footer_links_to_general_settings_table.php`.
One JSON column, because the footer is a display list, not a relational entity (same shape
`header_links` already uses).

```json
[
  {"group": "terms", "label": "Privacy Policy", "url": "/privacy-policy", "new_tab": 0},
  {"group": "site",  "label": "Home",           "url": "https://shop.example/", "new_tab": 1}
]
```

Server rules in `WebsiteSetupController::normalizeFooterLinks()`:

- a row is stored only when **both** `label` and `url` are non-empty after trim;
- `group` is one of `terms|site|useful|other`; an unknown value becomes `other`;
- `label` is cut at 100 chars, `url` at 255;
- `new_tab` is stored as `int` (`1` or `0`);
- the list stops at 30 rows.

The hidden marker `footer_links_present=1` distinguishes “the repeater was submitted” from
“this request did not touch the links”. Without the marker, stored links are left alone;
with the marker and no rows, they are cleared to `[]`.

### `general_settings.footer_about_text`

- Quill HTML is cleaned by `App\Support\HtmlSanitizer::inline()` before storage:
  allow-list `b, strong, i, em, u, s, br, p, ul, ol, li, a`; event handlers and
  `javascript:` / `data:` / `vbscript:` hrefs are stripped.
- The 400-character rule counts **visible** text (`HtmlSanitizer::visibleLength()`), not markup.
- The request-level rule is `max:10000` on the raw HTML, then the visible-length guard rejects
  over 400 with a validation error.

### `contacts`

The footer form writes the active contact row (`Contact::where('status', 1)->orderBy('id')->first()`),
falling back to a new `Contact`, then `fill()` + `save()`. `phone/email/address` are `NOT NULL` in the old migration,
so blank inputs are saved as `''`; the storefront handles blank values with its designed fallback.

## 4. Social icon catalog

`app/Support/SocialIcons.php` is the single catalog. It holds 16 entries:

Facebook, Instagram, X, YouTube, TikTok, WhatsApp, LinkedIn, Pinterest, Telegram,
Snapchat, Discord, Reddit, GitHub, Google, Dribbble, Behance.

Each entry carries:

| Field | Used by |
|---|---|
| `fa` | value stored in `social_media.icon`; rendered by the classic storefront |
| `ri` | admin table + picker (Remix Icon set loaded by the admin theme) |
| `bi` | `fm` storefront (Bootstrap Icons 1.11.3 CDN) |
| `color` | admin preview / brand colour preset |

Helpers: `adminIcon()` (unknown class → `ri-global-line`), `storefrontIcon()` (unknown → `bi-globe2`),
`color()` (unknown → `#6b7280`), `entryForFa()` (also accepts the short `fa-...` form typed by hand).
The DB keeps the historical Font Awesome class; only the **rendering** differs per theme.

Verification of the font classes:

- every `ri` class exists as a rule in `public/admin/assets/vendor/fonts/iconify-icons.css`;
- every `bi` class exists in Bootstrap Icons 1.11.3;
- every `fa` class exists in the storefront's local Font Awesome build (`public/assets/global/css/all.min.css`).

## 5. Storefront contracts

### Classic theme

`resources/views/frontend/layouts/partials/footer.blade.php` reads `general_settings.footer_links`
and groups rows by `terms|site|useful|other`. **A group with at least one managed row replaces
that group's designed defaults; a group with none keeps the designed links**, so a fresh install
still renders a complete footer. Rows render `new_tab` as `target="_blank"` (with the usual
`rel` handling in the markup).

### `fm` theme

`resources/views/frontend/fm/partials/footer/footer-links.blade.php` maps managed groups to its
own columns: `site` → company links, `terms` → policy links. `useful` and `other` are not rendered
by `fm` because that footer has its own bottom menu; the data is still stored once and available
to the classic theme. Contact address/email come from the same active contact row.

### About HTML

Both footers print the sanitized HTML rather than escaping it. The classic footer used to
wrap that HTML in a `<p class="footer-about-text">`, so Quill's own `<p>` landed inside a
paragraph and the browser split the widget. It is now a `<div>` with the same class; the
6-line budget is a `max-height: 8.925rem` (6 × 0.875rem × 1.7 line-height) instead of
`-webkit-line-clamp`, which does not clamp block children. The fm footer got the same
`<p>` → `<div>` change.

`footer_about_text` in `z_evo.general_settings` had been saved once as escaped HTML
(`<p>&lt;p&gt;…&lt;/p&gt;</p>`), which is data, not a render bug; it now holds
`<p>Jannat Mini Mart brings you quality products at the best price, delivered across Bangladesh.</p>`
and the storefront prints it as markup.

## 6. Verification

**Feature tests**

```bash
php artisan test --filter='AdminFooter'
# Tests: 8 passed (57 assertions)
```

- `tests/Feature/AdminFooterContentTest.php`
  - saves about HTML (sanitized), contact fields and managed links;
  - skips blank rows, keeps `other` group, casts `new_tab`;
  - marker-only request clears stored links;
  - 401 visible characters is rejected and the stored value does not change.
- `tests/Feature/AdminFooterSocialTest.php`
  - screen renders both pickers and the shared-form markers;
  - create/update persist title, icon, link, colour, status;
  - unchecked status saves as `0`;
  - storefront footer renders the saved link (the test calls `refreshApplication()` because
    `AppServiceProvider` shares `$socialicons` at boot and PHPUnit keeps one booted app).

**Acceptance script**

```bash
bash /tmp/opencode/footer-ui-check.sh
# FOOTER_UI_CHECK: PASS  (exit 0)
```

The script runs the feature tests, checks all 16 `ri` classes against the loaded font, renders
the real Blade with an authenticated user, and asserts the screen markers (two pickers, 16
options each, form association, link marker, counter, removed helper-text walls) plus the
storefront/fallback contracts.

**Browser QA** (real Chromium against a render of the real Blade with the real admin assets)

| Width | Result |
|---|---|
| 1440 | content 1180px; Name/Platform/Link on one row; pickers 368px; links table 1090px; no overflow |
| 800 | three fields 247px each on one row; picker control 235×48; menu 235px, 2 columns, 0 clipped labels; no overflow |
| 375 | page `scrollWidth = innerWidth = 375`; cards 343px; table scrolls inside its wrapper; social header badge 52×24; picker control 303×48, menu 303px/2 columns, 16 icons |

Picker geometry is asserted, not eyeballed: `bash /tmp/opencode/picker-size-check.sh` reads
the computed box of `#social_new_picker .icon-picker__toggle` and the sibling
`input[name="title"]` at 1440/800/375 and requires equal height, font, radius, border and
text offset (16px), a menu as wide as the control with the same left edge, no overlap with
the control, 16 options, 0 clipped labels and no page overflow.

Interactions exercised: picker open/select/close (hidden value, label, colour, `aria-pressed`),
edit modal prefill, add/remove link rows, live badge update, Quill counter (114 visible chars).

**Storage → storefront proof (31 Oct 2026)**

The admin POST route could not be hit (no login), so the **controller actions themselves**
were called with a real `Illuminate\Http\Request` and session (same code the form posts to),
then the DB row and `GET /` were read back.

| Step | Observed |
|---|---|
| `updateFooter` write | `footer_about_text = <p>Footer write proof 2026-10-31: this HTML came through the real updateFooter action.</p>`, `copyright = © 2026 Jannat Mini Mart (proof)`, `footer_links = [{"url":"/","group":"site","label":"Proof Home","new_tab":0},{"url":"/page/terms","group":"terms","label":"Proof Terms","new_tab":1}]`, `contacts` row unchanged (`House 12, Road 3, Barishal` / `+8801700000000` / `info@jannatminimart.com`) |
| `SocialMediaController@store` | row `id=129`, `title=Facebook`, `icon=fab fa-facebook-f`, `link=https://facebook.com/jannatminimart`, `color=#1877F2`, `status=1` |
| `GET /` after the writes | `200`, 718,621 bytes: `<div class="mb-0 footer-about-text"><p>Footer write proof …</p></div>`, `Proof Home`, `Proof Terms`, `<i class="fab fa-facebook-f"></i>`, contact values — `&lt;p&gt;` count `0` |
| restore through the same actions | about back to the real sentence, `footer_links=[]`, probe row `129` deleted (`social_rows=0`) |
| `GET /` after restore | `200`, 721,059 bytes: about prints `<p>Jannat Mini Mart brings you quality products …</p>`, no proof links, no escaped markup |

The earlier escaped value was **stored data** (`<p>&lt;p&gt;…&lt;/p&gt;</p>`), not a controller
encoding bug; it was rewritten to the clean HTML above.

**Live HTTP proof (31 Oct 2026, after credentials were supplied)**

The admin account (`users.id=1`) was used to log in at `/admin/login` over real HTTP, so the
whole chain is now `LIVE_PROBE_PASSED`, not a controller shortcut:

| Step | Observed |
|---|---|
| `POST /admin/login` | redirect to `/admin/dashboard`; session cookie kept |
| `GET /admin/website-setup/footer` | `200`; both pickers render; saved rows in the table |
| form submit (real click) | Name `Facebook`, picker option `fab fa-facebook-f`, link `https://facebook.com/jannatminimart`; redirect back to the same page |
| DB after submit | `social_media` row `id=162`, `title=Facebook`, `icon=fab fa-facebook-f`, `color=#1877f2`, `status=1` |
| admin table | `1 Facebook Facebook https://facebook.com/jannatminimart Active` |
| `GET /` after submit | `200`, 720,370 bytes; footer renders `<a href="https://facebook.com/jannatminimart"><i class="fab fa-facebook-f"></i></a>` |
| responsive box | 1440/800/375: input and picker both `h=48`, same width (`356` / `234.7` / `303`), no horizontal overflow |

Screenshots: `/tmp/opencode/shots/proof-1-form-before.png` … `proof-7-form-375.png` (the earlier
`admin-*.png` set is the same screen). Gate:
`bash /tmp/opencode/live-form-proof.sh` → `LIVE_FORM_PROOF: PASS`, exit 0, full output in
`/tmp/opencode/live-form-proof.log`; the run created probe row `id=163` through the real form,
saw it on `GET /` (`fa-instagram`), then deleted it through the real delete form and confirmed
`GET /` no longer shows it while `social_media` is back to the single row `162`.

**Evidence boundaries**

- No credentials are stored in this document; the live run used the account the owner supplied.
- Temporary QA pages under `public/__qa-*.html` were removed after verification.
- Changes are uncommitted in the working tree.

## 7. Files

| File | Change |
|---|---|
| `resources/views/backEnd/website-setup/footer.blade.php` | rebuilt 3-card screen, Quill guard, links repeater, social table/modal, JS |
| `resources/views/backEnd/website-setup/partials/social-icon-picker.blade.php` | `.form-select` icon picker (16 options + custom class) |
| `app/Http/Controllers/Admin/WebsiteSetupController.php` | footer data, validation, sanitizer, link normalization, contact write |
| `app/Support/SocialIcons.php` | shared 16-entry fa/ri/bi/color catalog + helpers |
| `app/Support/HtmlSanitizer.php` | allow-list + visible-length counter |
| `app/Models/GeneralSetting.php` | `footer_links` fillable + `array` cast |
| `database/migrations/2026_10_31_000001_add_footer_links_to_general_settings_table.php` | JSON column |
| `resources/views/frontend/layouts/partials/footer.blade.php` | classic footer reads managed links; about HTML in a div |
| `resources/views/frontend/fm/partials/footer/footer-links.blade.php` | fm footer reads managed links; about HTML in a div |
| `tests/Feature/AdminFooterContentTest.php` | new content/links/400-char tests |
| `tests/Feature/AdminFooterSocialTest.php` | updated markup assertions + storefront refresh |
| `app/Support/MediaPath.php` | fallback image SSOT (earlier step) |
| `app/Support/ImageUsage.php`, `app/Console/Commands/ImagesAuditCommand.php` | `images:audit` live scanner (earlier step) |
