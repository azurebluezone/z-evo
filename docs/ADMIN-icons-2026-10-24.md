# Admin — password visibility toggle, icon rendering, order board overflow

**Date:** 2026-10-24
**Pages:** `GET /admin/change-password`, the admin icon set as a whole, `GET /admin/order/board`
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

Audit of the whole admin view tree: **324** `<i>` tags across **94** files use a `ri-*` class without the `ri` base class. The change-password eye was one instance of a project-wide migration gap.

Three classes used in views do not exist in this icon set at all (so they stay invisible even with the base class):

| Used | Replacement | Where |
|---|---|---|
| `ri-shield-alert-line` | `ri-error-warning-line` | `admin/dashboard.blade.php` ("Safety & Support Alerts") |
| `ri-list-check-line` | `ri-list-check` | `order/board.blade.php` (list view toggle) |
| `ri-filter-search-line` | `ri-filter-3-line` | `visitors/visitors.blade.php` ("Filter & Search") |

And the legacy size helpers `.ri-3x` / `.ri-lg` were font-size utilities in the old build; in the mask build they were no-ops, so empty-state icons rendered at 1em.

---

## 2. Fix, step 1 — the toggle and a safety net (commit `f891096`)

**`public/admin/assets/css/admin-icons.css` (new, loaded last by `layouts/partials/styles.blade.php`)**

1. Base-class shim — restored box + mask for legacy markup, scoped to `<i>` and `:not(.ri)` so the correctly written tags were untouched:
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

The shim was a safety net, not the cleanup. It is gone in step 2.

---

## 3. Fix, step 2 — canonical sweep, shim removed

All **324** non-canonical tags in **94** files now read `class="ri ri-<name> ..."`. The sweep was mechanical (tag-scoped, Blade expressions preserved), then verified twice:

- static audit: `non-canonical remaining: 0`, `canonical <i> tags now: 1031`
- one tag needed a second pass because a Blade expression sat inside the class attribute:
  `<i class="ri-lock-unlock-line {{ $failed_login_attempts > 0 ? 'text-dark' : 'text-white' }}">`

With zero legacy call sites left, the `i[class*="ri-"]:not(.ri)` shim was deleted from `admin-icons.css`. The file now only documents the contract and styles `.toggle-password` + the legacy size helpers.

### Why this is safe — before/after box comparison

Same 8 admin pages, same viewport, before the sweep (shim active) and after (shim removed):

| Page | Icons | 0×0 | No mask | Icon sizes changed | `scrollWidth` |
|---|---:|---:|---:|---:|---|
| `/admin/dashboard` | 55 → 55 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |
| `/admin/change-password` | 39 → 39 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |
| `/admin/products/manage` | 81 → 81 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |
| `/admin/categories/manage` | 75 → 75 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |
| `/admin/customer` | 42 → 42 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |
| `/admin/invoices` | 40 → 40 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |
| `/admin/order/board` | 35 → 35 | 0 → 0 | 0 → 0 | 0 | **2008 → 1440** |
| `/admin/visitors/manage` | 69 → 69 | 0 → 0 | 0 → 0 | 0 | 1440 → 1440 |

Only the class string differs (`ri-*` → `ri ri-*`); every icon box is identical. Raw data: `/tmp/opencode/icon-before.json`, `/tmp/opencode/icon-final.json`.

---

## 4. Fix, step 3 — order board horizontal overflow

`/admin/order/board` scrolled the whole page sideways: `document.documentElement.scrollWidth = 2008` at `innerWidth = 1440`.

**Root cause:** the scoped rule was written for an element that does not exist.

```css
#kanbanBoard.kanban-container { display:flex; ... overflow-x:auto; }   /* never matched */
```

`#kanbanBoard` is the host and carries `.kanban-scroll`; jKanban builds its own **child** `.kanban-container` and sets an inline `width: 1680px` (6 boards × 250px + gutters) on it. So the flex layout and the scroll container never applied: the six columns stacked vertically, and the 1680px child escaped the 1044px card body.

**Fix (`order/board.blade.php`, scoped to the board):**

```css
#kanbanBoard { max-width: 100%; }
#kanbanBoard > .kanban-container {
  display: flex; gap: .75rem; align-items: flex-start;
  max-width: 100%; overflow-x: auto; padding-bottom: .5rem;
}
#kanbanBoard > .kanban-container > .kanban-board { margin-left: 0 !important; margin-right: 0 !important; }
#kanbanBoard .kanban-board { flex: 0 0 auto; /* library sets width:250px inline */ }
```

The inline `margin-left/right:15px` the library writes on every board is dropped because the `gap` already owns the spacing (inline styles need `!important` to lose against a stylesheet).

The scroller is also made usable without a mouse: after init the script sets `tabindex="0"`, `role="region"` and `aria-label="Order board, 6 status columns, scrolls horizontally"` on the container, plus a `:focus-visible` ring and a visible 8px scrollbar (otherwise the columns past the fourth are undiscoverable).

### Evidence

| Width | `scrollWidth` / `innerWidth` | Container client / scroll | Columns |
|---|---|---|---|
| 1440 | **1440 / 1440** | 1044 / 1560 | 6 in one row |
| 1024 | **1024 / 1024** | 888 / 1560 | 6 in one row |
| 768 | **768 / 768** | 664 / 1560 | 6 in one row |
| 390 | **390 / 390** | 286 / 1560 | 6 in one row |

- Keyboard: focus the scroller, `ArrowRight` ×2 → `scrollLeft 0 → 80`.
- Drag still persists: dragging the first Pending card to Processing moved it in the DOM **and** in the database; it was dragged back and `orders.id=2` verified as `order_status=1` afterwards (no test data left behind).
- Console errors on the board: none.
- Screenshots: `/tmp/opencode/board-1440-fixed.png`, `/tmp/opencode/board-390-fixed.png`.

---

## 5. Verification

```bash
cd tests/e2e
ADMIN_EMAIL=… ADMIN_PASSWORD=… node tools/admin-icons-check.mjs   # → exit 0, problems: []
./node_modules/.bin/playwright test header-layout.spec.js        # → 14 passed
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
| Non-canonical `ri-*` tags in `resources/views` | 324 (94 files) | **0** (1031 canonical) |
| Order board `scrollWidth` at 1440 | 2008 | **1440** |
| Order board `scrollWidth` at 390 | not measured before | **390** |

---

## 6. Blast radius — read this before the next admin change

- **324 `<i>` tags changed.** The rendered result is byte-for-byte the same box as the shim produced (table in §3), but this touched 94 blade files. A file-by-file visual pass of the admin is still worth doing before a release; the four probed pages plus the board, visitors, customers and invoices are covered by measurement, not by human eyes.
- The sweep is mechanical: only a class token was inserted. Any future `ri-*` icon must be written as `class="ri ri-<name>"` — without the base class it is a 0×0 element again, and the shim that used to hide that mistake is gone.
- The board now scrolls **inside the card**, not the page. That is deliberate (the page-level sideways scroll was the bug), and the scroller is focusable and labelled.

**Not changed (pre-existing, out of scope):**
- `/admin/settings/general` is 404 — no such route.
- Storefront suite: 27 of 44 tests still fail on classic-theme selectors (see `docs/HEADER-STATUS-2026-10-24.md`).

---

## 7. Files

| File | Change |
|---|---|
| `public/admin/assets/css/admin-icons.css` | **new** — icon contract notes, `.toggle-password` geometry, legacy size helpers (shim removed in §3) |
| `resources/views/backEnd/layouts/partials/styles.blade.php` | links the new stylesheet after `admin-responsive.css` |
| `resources/views/backEnd/admin/changepassword.blade.php` | 3 buttons (base class, aria) + rewritten toggle script |
| `resources/views/backEnd/admin/dashboard.blade.php` | `ri-shield-alert-line` → `ri-error-warning-line`; base class |
| `resources/views/backEnd/order/board.blade.php` | `ri-list-check-line` → `ri ri-list-check`; board scroller fix + a11y |
| `resources/views/backEnd/visitors/visitors.blade.php` | `ri-filter-search-line` → `ri-filter-3-line` |
| **94 blade files** | base class added to 324 `<i>` tags |
| `tests/e2e/tools/admin-icons-check.mjs` | **new** — regression probe (no credentials in repo) |

## 8. Next

1. Human visual pass over the admin screens the probe does not cover (courier settings, recommendations, order edit, product edit — the files with the most swept tags).
2. Port the four legacy storefront e2e specs (see `docs/HEADER-STATUS-2026-10-24.md` §8.2).
3. `/offers` renders the classic header because of the `/offer` route; topbar phone/WhatsApp settings are still NULL.
