# Admin — password visibility toggle + icon rendering

**Date:** 2026-10-24
**Page:** `GET /admin/change-password` (`Admin\DashboardController@change_password`)
**Reported:** the three `btn btn-outline-secondary toggle-password rounded-0 waves-effect` buttons render as empty outline boxes — no eye glyph.

---

## 1. Root cause

The admin icon set is **mask based**, not font based. `public/admin/assets/vendor/fonts/iconify-icons.css` is 1.3 MB of rules shaped like:

```css
.ri {                                  /* the base class */
  display: inline-block; width: 1em; height: 1em;
  background-color: currentColor;
  -webkit-mask-image: var(--svg); mask-image: var(--svg);
  -webkit-mask-size: 100% 100%; mask-size: 100% 100%;
}
.ri-eye-off-line { --svg: url("data:image/svg+xml,..."); }   /* artwork only */
```

The base class owns the box and the mask plumbing; the name class only supplies the artwork. The views were written for the **old font build**, where `<i class="ri-eye-off-line">` worked because the font family did the drawing. After the switch to the mask build:

- `<i class="ri-eye-off-line">` has no `display`, no width, no mask → **0×0**, invisible.
- Measured before the fix: button `46×48`, icon `0×0`, `font-family: Inter` (no icon font).

Two more defects on the same page:

| # | Defect | Effect |
|---|---|---|
| R1 | the three `<i>` tags lack the `ri` base class | empty button |
| R2 | the toggle script set `className = 'ri-eye-line'` (no base class) | even a working icon would vanish on first click |
| R3 | no `aria-label` / `aria-pressed` | a screen reader hears "button" with no purpose and no state |

Audit of the whole admin view tree: **329** `<i>` tags use a `ri-*` class without the `ri` base class. The change-password eye was one instance of a project-wide migration gap.

Three classes used in views do not exist in this icon set at all (so they stay invisible even with the base class):

| Used | Replacement | Where |
|---|---|---|
| `ri-shield-alert-line` | `ri-error-warning-line` | `admin/dashboard.blade.php` ("Safety & Support Alerts") |
| `ri-list-check-line` | `ri-list-check` | `order/board.blade.php` (list view toggle) |
| `ri-filter-search-line` | `ri-filter-3-line` | `visitors/visitors.blade.php` ("Filter & Search") |

And the legacy size helpers `.ri-3x` / `.ri-lg` were font-size utilities in the old build; in the mask build they were no-ops, so empty-state icons rendered at 1em.

---

## 2. Fix

**`public/admin/assets/css/admin-icons.css` (new, loaded last by `layouts/partials/styles.blade.php`)**

1. Base-class shim — restores box + mask for legacy markup, scoped to `<i>` and `:not(.ri)` so the 702 correctly written tags are untouched:
   ```css
   i[class*="ri-"]:not(.ri) { display:inline-block; width:1em; height:1em;
     background-color:currentColor; -webkit-mask-image:var(--svg); mask-image:var(--svg); ... }
   ```
2. `.toggle-password` geometry: `48×48`, `padding:0`, glyph `1em` at `font-size:1.125rem` (18px), hover, `:focus-visible`, and a visible `[aria-pressed="true"]` state (tinted background + primary border) so the control tells the truth about the input type.
3. `.ri-3x` → `3em`, `.ri-lg` → `1.33em` (the two legacy size helpers that still exist in markup).

**`resources/views/backEnd/admin/changepassword.blade.php`**

- canonical icons: `<i aria-hidden="true" class="ri ri-eye-off-line"></i>`
- `data-label`, `aria-controls`, `aria-label="Show old password"`, `aria-pressed="false"`
- script: guards a missing target, toggles `target.type`, sets `ri ri-eye-line` / `ri ri-eye-off-line`, updates `aria-pressed` + `aria-label` ("Show/Hide …"), and returns focus to the input so the caret is not lost.

---

## 3. Evidence

Probe: `tests/e2e/tools/admin-icons-check.mjs` (env-gated credentials, exit code 1 on any problem).

```bash
cd tests/e2e
ADMIN_EMAIL=… ADMIN_PASSWORD=… node tools/admin-icons-check.mjs
# → exit 0, problems: []
```

| Check | Before | After |
|---|---|---|
| Eye button box | 46×48, padding `7.7px 22px` | **48×48**, padding `0` |
| Eye glyph box | **0×0**, no mask | **18×18**, mask applied |
| `aria-label` | `null` | "Show old password" |
| `aria-pressed` | `null` | `false` → `true` → `false` |
| Click #old_password | icon removed on toggle (R2) | `type` `password→text→password`, icon `ri-eye-off-line ↔ ri-eye-line`, focus back on `old_password` |
| Same for new / confirm | — | both flip, both restore |
| Visible `ri-*` with a 0×0 box (4 admin pages) | 3 on change-password | **0** |
| Visible `ri-*` without a mask | `ri-shield-alert-line` on dashboard | **0** |
| Horizontal overflow, 1440 (dashboard, change-password, products, categories) | none | none |
| Change-password at 390px | — | button 48×48, icon 18×18, `scrollWidth 390` |

Pages checked: `/admin/dashboard`, `/admin/change-password`, `/admin/products/manage`, `/admin/categories/manage` (200 each), plus `/admin/order/board` and `/admin/visitors/manage` for the renamed icons.

Screenshots: `/tmp/opencode/admin-cp-fixed-1440.png`, `/tmp/opencode/admin-cp-fixed-390.png`.

---

## 4. Blast radius — read this before the next admin change

The shim makes **all 329** legacy `ri-*` icons render. That is the intended design (they were written to be visible), and the four checked pages show no zero-size icon and no horizontal overflow. It is still a visual change on every admin screen that used the old font markup — a full admin visual pass is listed as next work, not claimed as done.

**Not changed:**
- the 329 call sites are still non-canonical; the shim is the safety net, not the cleanup. Sweep command (per file, review before commit):
  `rg -l '<i[^>]*class="[^"]*ri-[^"]*"' resources/views | …`
- `.kanban-container` on `/admin/order/board` is 1680px wide with `overflow-x: visible`, so the page scrolls sideways (`scrollWidth 2008` at 1440). **Pre-existing** — identical with and without `admin-icons.css`.

---

## 5. Files

| File | Change |
|---|---|
| `public/admin/assets/css/admin-icons.css` | **new** — icon base shim, toggle geometry, legacy size helpers |
| `resources/views/backEnd/layouts/partials/styles.blade.php` | links the new stylesheet after `admin-responsive.css` |
| `resources/views/backEnd/admin/changepassword.blade.php` | 3 buttons (base class, aria) + rewritten toggle script |
| `resources/views/backEnd/admin/dashboard.blade.php` | `ri-shield-alert-line` → `ri-error-warning-line` |
| `resources/views/backEnd/order/board.blade.php` | `ri-list-check-line` → `ri ri-list-check` |
| `resources/views/backEnd/visitors/visitors.blade.php` | `ri-filter-search-line` → `ri-filter-3-line` |
| `tests/e2e/tools/admin-icons-check.mjs` | **new** — regression probe (no credentials in repo) |

## 6. Next

1. Sweep the 329 legacy `ri-*` tags to the canonical `ri ri-*` form, then delete the shim — one PR, with an admin screenshot pass.
2. Fix `.kanban-container` overflow on the order board (`max-width:100%; overflow-x:auto` on the scroll owner, not the page).
3. Port the four legacy storefront e2e specs (see `docs/HEADER-STATUS-2026-10-24.md` §8.2).
