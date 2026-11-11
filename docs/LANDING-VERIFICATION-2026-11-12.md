# Landing pages — rebuild, polish and release verification

- Date: 2026-11-12
- Project: `today` 1.0.0 (Laravel 10), live MySQL database `z_evo`
- Session: `ses_e33b1873cffegXtyQYUttSYJw5`
- Goal contract: `gc-mwcq0v50` — check `php tools/verify_landing_polish.php`
- Scope: the nine dynamic landing designs (`ht2`, `t1`, `th3`–`th9`), the admin design manager, the campaign content form, the layered card-image fallback, the shared order form, the MCP allowlist and the image/branding inventory.

This document supersedes the six-page notes in `docs/LANDING-PAGES-2026-11-11.md` for the current nine-design set. Campaign pricing remains documented in `docs/LANDING-CAMPAIGNS-2026-11-10.md` and `work/landing-campaign-pricing.md`.

## 1. Delivered state

| Area | Result | Evidence |
| --- | --- | --- |
| Nine designs | `resources/views/templates/` contains only `ht2`, `t1`, `th3`–`th9`; no legacy `.blade.php` or `.html` files remain | `AdminLandingDesignTest::test_only_the_nine_design_views_remain` |
| Admin manager | `/admin/landing-pages/manage` is a nine-card grid with friendly names, Active badge and side-by-side View/Edit | `AdminLandingDesignTest::test_manage_page_lists_the_nine_designs_with_view_and_edit` |
| Variable page | English labels, no notices/stats; every toggle persists to `templates.config.enabled_variables` | `AdminLandingDesignTest::test_every_variable_toggle_persists_to_the_template_config` |
| Campaign content | Registry-only fields; legacy Features/Benefits/Testimonials/FAQs/Why-us/Packages form and hints removed | `AdminLandingDesignTest::test_campaign_content_panel_has_no_legacy_hints_or_feature_fields` |
| Admin layout | Fluid shell (`container-fluid`) and no nested page container | `AdminLandingDesignTest::test_admin_layout_is_fluid_and_page_views_do_not_nest_containers` |
| Order form | Every design exposes exactly one `POST /landing-order` form, in admin preview and on the public page | `LandingOrderFormTest` (2 tests / 62 assertions) |
| Card fallback | Campaign image → generated hero → Appearance image → tracked branding fallback | `app/Support/LandingCardImage.php`, `php tools/verify_landing_polish.php` |
| Branding filebox | Logo, brand video and final fallback tracked under `public/branding/` with a SHA-256 manifest | `public/branding/manifest.json` |
| Image inventory | 157 images across admin, landing, uploads and branding trees | `docs/IMAGE-INVENTORY-2026-11-12.md`, `docs/image-inventory.json` |
| MCP allowlist | `image-brand_*` and `public-api_*` are allowed in investigate/work/verify; intake stays closed | Adapter test + installed-plugin assertion |

## 2. Admin container fix

The template reference at `/home/sahon/Desktop/admin html/html/vertical-menu-template` uses the fluid variant (`layouts-fluid.html`). Our layout was using the fixed `container-xxl` shell while 11 page views nested a second `container-xxl flex-grow-1 container-p-y`, which produced the visible side gaps and double horizontal padding.

Changed:

- `resources/views/backEnd/layouts/app.blade.php` — `container-xxl flex-grow-1 container-p-y` → `container-fluid flex-grow-1 container-p-y`
- `resources/views/backEnd/layouts/0app.blade.php` — same fluid shell
- `resources/views/backEnd/layouts/partials/navbar.blade.php` — `layout-navbar container-xxl` → `layout-navbar container-fluid`
- `resources/views/backEnd/layouts/partials/footer.blade.php` — `container-xxl` → `container-fluid`
- 11 page views — the inner duplicate container became `w-100`:
  `campaign/create`, `campaign/edit`, `landing-pages/create`, `landing-pages/edit`, `notifications/templates/create`, `notifications/templates/edit`, `prompt/generator`, `roles/create`, `tiktok-test/index`, `user-submissions/service-requests/edit`, `user-submissions/service-requests/show`

Result: one fluid container from the layout; pages no longer add a second centered max-width or extra `container-p-y` padding. The check scans every `backEnd` view outside `layouts/` and requires zero matches for the nested container.

## 3. Campaign content form

`resources/views/backEnd/campaign/partials/content-fields.blade.php` is now generated from `config/landing_templates.php` only:

- scalar content variables and repeaters come from `LandingTemplateVars::contentFieldsFor()`;
- the legacy hard-coded arrays (`features`, `benefits`, `testimonials`, `faqs`, `why_us`, `packages`) and every hint/subtitle were deleted;
- the panel keeps a single `Landing Content` heading, field labels, repeater column labels and Add/Remove controls;
- the create/edit “Section Visibility” block with Show Features/Benefits/Testimonials/FAQ/CTA was removed because the nine designs use the enabled-variable registry instead.

`tests/Feature/CampaignTemplateFieldsTest.php` now exercises the real registry template `ht2` (`trust_items`, `variants`, `comparison`, `reviews`, `faqs`, announcement fields) instead of the deleted `sample-template`.

## 4. Card image fallback chain

`app/Support/LandingCardImage.php` resolves the image for each admin design card in this order:

1. `campaigns.extra_fields.hero_image` (editor override);
2. `campaigns.image_one`;
3. `public/frontend/landing/pictures/generated/<view>-hero.jpg`;
4. `MediaPath::fallbackPath()` — `general_settings.frontend_empty_image` from `/admin/website-setup/appearance`, with the bundled placeholder as its own fallback;
5. `public/branding/card-fallback.jpg` — final tracked fallback, copied byte-for-byte from `work/zevo/card-fallback.jpg` (2172×724 PNG payload in a `.jpg` container; browsers render it as-is).

Remote URLs are returned untouched. `LandingCardImage::url()` converts local paths with `asset()` and leaves absolute URLs alone. `LandingPageController@index` and `resources/views/backEnd/landing-pages/index.blade.php` use this resolver, so no card can render a missing file.

The prior generated default remains at `public/frontend/landing/pictures/generated/card-fallback.jpg`; the branding copy is the last line of defence and is tracked by git.

## 5. Order form proof

The form is shared, not duplicated: `resources/views/layouts/templates.blade.php:62` includes `frontend.landing.partials.order-section`, whose form posts to `route('landing.order')` with `product_id`, `campaign_id`, `quantity` and CSRF token.

Verified by `tests/Feature/LandingOrderFormTest.php`:

- admin preview `/admin/campaign/806/preview` contains exactly one `action="…/landing-order"` form, one `#landing-order` section, `product_id=14`, `campaign_id=806`, `quantity` and `_token`;
- every active campaign for the nine designs returns HTTP 200 on `/campaign/{slug}` with exactly one form and the matching product/campaign IDs.

## 6. MCP tool diagnosis and fix

Diagnosis: the `image-brand` MCP server itself is healthy — `node ~/.opencode/mcp/image-brand/selfcheck.mjs` exits 0 (`tools=6 pipeline=ok fit=320x200 decorate=3 cards bytes=93035`). The block was the ZombieCoder plugin phase allowlist: OpenCode exposes MCP tools as `<server>_<tool>` (the error named `image-brand_image_brand`), while the phase lists only contained exact built-in names.

Fix implemented in both the adapter source and the installed plugin:

- `src/agents/defaults.ts` — added `image-brand_*` and `public-api_*` to `investigate`, `work` and `verify`;
- `src/agents/base.ts` — `toolMatches()` supports a trailing `*` namespace pattern; `allows()` now uses it for both the allowlist and the denied list;
- `test/agents.test.ts` — asserts the namespaces are allowed in investigate/work/verify and denied in intake.

Files patched (identical copies):

- `/home/sahon/adapter/src/agents/defaults.ts`
- `/home/sahon/adapter/src/agents/base.ts`
- `/home/sahon/.opencode/plugins/zombiecoder/agents/defaults.ts`
- `/home/sahon/.opencode/plugins/zombiecoder/agents/base.ts`

Adapter tests: 16 passed / 0 failed. Installed-plugin assertion:

```
work=true
investigate=true
verify=true
intake=false
```

A running OpenCode process loads the plugin once, so restart OpenCode (or the plugin host) before calling `image-brand_*` in a live session. The code path is verified; the live tool call is `NOT_TESTED` until that restart.

## 7. Branding filebox and image inventory

`public/branding/` is the tracked filebox (source `work/zevo/`, which is gitignored):

| File | Role | Size | Dimensions / duration | SHA-256 |
| --- | --- | ---: | --- | --- |
| `logo.png` | Brand logo | 122,602 B | 866×288 | `aa0a9681a2a3…` |
| `card-fallback.jpg` | Final card image fallback | 921,471 B | 2172×724 | `5ab83dfd978d…` |
| `brand-intro.mp4` | Brand introduction video | 3,693,807 B | 8 s container | `ab2363037f9c…` |
| `manifest.json` | Machine-readable filebox manifest | — | 3 files | — |

`ffprobe` reports an 8-second MP4 container but no decodable video stream for the supplied file; the bytes are kept exactly as delivered and the manifest records that note.

Inventory command:

```
php tools/image_inventory.php
→ IMAGE_INVENTORY_OK dirs=4 images=157 bytes=7240684
```

| Directory | Images | Size |
| --- | ---: | ---: |
| `public/admin/assets/img` (admin template) | 77 | 664.7 KB |
| `public/frontend/landing/pictures` (landing templates) | 56 | 4.50 MB |
| `public/uploads` (database-referenced uploads) | 22 | 779.2 KB |
| `public/branding` (image files in the filebox) | 2 | 1,019.6 KB |

Full per-file dimensions and hashes: `docs/IMAGE-INVENTORY-2026-11-12.md` and `docs/image-inventory.json`.

## 8. Verification evidence

| Command | Observed | Exit |
| --- | --- | ---: |
| `php artisan test --filter=AdminLandingDesignTest` | 7 passed / 128 assertions | 0 |
| `php artisan test --filter=CampaignTemplateFieldsTest` | 4 passed / 58 assertions | 0 |
| `php artisan test --filter=LandingOrderFormTest` | 2 passed / 62 assertions | 0 |
| `php artisan test --filter='LandingPageAdminTest\|CampaignPhase4AdminTest\|LandingCampaignOrderTest'` | 20 passed / 117 assertions | 0 |
| `php tools/verify_landing_dynamic_all.php` | `LANDING_DYNAMIC_ALL_OK (731 checks, 9 designs)` | 0 |
| `php tools/image_inventory.php` | `IMAGE_INVENTORY_OK dirs=4 images=157 bytes=7240684` | 0 |
| `node --test test/agents.test.ts` (adapter) | 16 passed / 0 failed | 0 |
| `node … zombiecoder.allows(...)` (installed plugin) | work/investigate/verify true, intake false | 0 |
| `php tools/verify_landing_polish.php` | `layout=fluid inner_pages=0 copy=clean legacy=removed fallback=layered branding=4 order=present docs=2 images=157` | 0 |

## 9. Data and environment notes

- Campaign `768` (`Raw Honey Herbal Offer`, legacy `herbal-health`) was published but its Blade view no longer exists. It was set to `status=0` so the home page does not advertise a 404 and all nine new campaigns fit the nine-event home limit. Active campaigns are now exactly `803`–`811`.
- Campaign `806` remains the preview reference: `product_id=14`, campaign price `801`, regular price `890`.
- The nine active designs and their campaigns: `ht2`→803, `t1`→804, `th3`→805, `th4`→806, `th5`→807, `th6`→808, `th7`→809, `th8`→810, `th9`→811.
- Re-run the release check after any template or layout edit: `php tools/verify_landing_polish.php`.

## 10. Known limits

- The admin layout was verified structurally (fluid shell, zero nested containers) and through the feature suite; a pixel-level visual pass at `http://127.0.0.1:8000/admin` was not automated.
- The MCP namespace patch is verified in code; the live tool call needs an OpenCode restart as noted in section 6.
- `brand-intro.mp4` has no decodable stream according to `ffprobe`; it is stored as supplied, not re-encoded.
