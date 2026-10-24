# Jannat Mini Mart — Delivery Plan (2026-10-24)

Owner of this plan: jarin (debug/RCA). Execution is split across agents; each
row names the accountable agent and the evidence that closes it.

## 1. Goal

Ship the Jannat Mini Mart storefront as a client-ready site: correct branding
(logo/slider/banner/product fallbacks), working wishlist, correct variant
display and selection, client data in settings, and a copy-ready AI prompt pack
for generating the real image assets.

Acceptance = every row in §5 has its evidence, `php artisan test` is green, and
the live pages return the stated HTTP codes.

## 2. Client data (source of truth for settings)

| Party | Name | Phone | Email | Address |
|---|---|---|---|---|
| Store | Jannat Mini Mart | 08035979290 | jannatminimart2610@gmail.com | 272-0107 Chiba-ken, Ichikawa-shi, Oshikiri 21-8, 101 Gyotoku Heights 1F |
| Owner | Gous Uddin | 08035979290 (same) | jahangir314@gmail.com | 272-0141 Chiba-ken, Ichikawa-shi, Kandori 1-10-24, 103 Front Islands Heights |

One number serves both parties; the two emails and the two addresses are distinct
— do not collapse them.

Brand tokens: `#0F7A3D` primary, `#22A65B` secondary, `#F6F2E7` cream,
`#12261B` ink, `#E8A33D` accent.

## 3. Image specification (measured from the theme CSS)

| Asset | Size | Ratio | Source of the ratio |
|---|---|---|---|
| Logo master | 512×512 | 1:1 | header slot |
| Logo compact | 128×128 | 1:1 | mobile header |
| Favicon | 48×48 / 32×32 | 1:1 | `layouts.blade.php:24` |
| Hero banner | 1920×560 | 24:7 | `fm-home-v2.css:321` comment ("1920x560 banner") |
| Slider figure | 1440×1040 | 720:520 | `fm-sections-home.css:364` |
| Product image | 1000×1000 | 1:1 | `fm-sections.css:364` (`aspect-ratio:1/1`) |
| Category banner | 1200×900 | 4:3 | `fm-home-v2.css:604` |
| OG image | 1200×630 | 1.91:1 | social share |

Deliverable for the client: `work/zevo/AI-PROMPT-jannat-branding.html`
(single file, copy buttons, no external dependency).

## 4. Recovery status (closed)

`docs/RECOVERY-audit-2026-10-24.md` — the mounted `Desktop/zevo` snapshot was
audited file-by-file; its unique content (Admin Wishlist module + 4 fixes + 4
tests) is now in this repo, byte-identical. Commit `c066b6f`, pushed.

## 5. Work queue

| # | Task | Owner | Status | Closing evidence |
|---|---|---|---|---|
| 1 | Admin Wishlist module (controller/model/migration/view/sidebar/routes) | jarin | DONE | `c066b6f`, `AdminWishlistPageTest` green |
| 2 | Wishlist durable rows for logged-in customers | jarin | DONE | `routes/web.php` closures + test |
| 3 | `/customer/checkout` 500 (orphan `$shurjopay_gateway`) | jarin | DONE | test `checkout dispatches tiktok…` green |
| 4 | Courier admin JS dead after `@endsection` | jarin | DONE | `AdminCourierPagesTest` green |
| 5 | Order risk-field casts (DataTables 500) | jarin | DONE | `AdminOrderDatatableTest` green |
| 6 | Old-password error in the error bag (admin + customer) | jarin | DONE | `PasswordChangeTest` green |
| 7 | Storefront product sidebar design vs `work/Products.html` | nila | IN PROGRESS | curl 200 + diff stat |
| 8 | Variant label shows raw slug (`a-56`) | general | IN PROGRESS | corrected label + `variant-detail` JSON |
| 9 | Admin variant selection lost on re-render | general | IN PROGRESS | posted payload keeps all rows |
| 10 | Branding fallback tool in `work/zevo/` (product/slider/banner/favorite) | unassigned | PENDING | generated files + sha256 |
| 11 | Client data into `general_settings` + `contacts` | unassigned | PENDING | DB row dump |
| 12 | `/wishlist` dead-end for guests (redirects home) | unassigned | PENDING | route behaviour decision |
| 13 | QA pass on 1–12 | majnu | PENDING | test run + curl matrix |
| 14 | Security review of wishlist endpoints (authz, mass assignment) | brishti | PENDING | findings list |

## 6. Monitoring protocol for delegated work

- Each subagent gets: the exact file, the repro command, the acceptance check,
  and a hard "do not commit" rule (jarin owns commits to avoid races).
- jarin verifies every report independently: re-runs the curl/test, reads the
  diff, and only then marks the row DONE.
- Two agents never touch the same file in the same round: nila owns
  `shop-sidebar.blade.php`; general owns the product/variant files.
- Three failed attempts on one row → stop, record the evidence, hand off.

## 7. Commit / rollback discipline

- Every closed step: `git add -A && git commit && git push origin main`, then
  compare `git rev-parse HEAD` with `git ls-remote origin main`.
- DB backup before any migration: `/tmp/opencode/db-backup6.sql` (2026-10-24).
- No force-push, no history rewrite. Revert = `git revert <sha>`.
