# tests/e2e/tools — measurement probes

Scripts here are **read-only**. They print JSON and never mutate the app.
The release gates live in `../*.spec.js`; these are the measuring tape you reach
for when a gate fails or before you change a layout.

All of them resolve Playwright from `../node_modules`, so run them from
`tests/e2e`:

```bash
cd tests/e2e
node tools/header-metrics.mjs
node tools/header-verify.mjs
```

## header-metrics.mjs

Eight widths (1440 / 1280 / 1200 / 1199 / 1024 / 768 / 390 / 320) → row, brand,
search and action boxes, logo centre delta, logo→actions gap, top-bar gutters,
every visible icon size, nav link metrics, `scrollWidth`.

```bash
BASE_URL=http://127.0.0.1:8000 node tools/header-metrics.mjs
```

## header-verify.mjs

Interactions a box measurement cannot see: account + language dropdowns inside
the viewport, the phone nav drawer (six destinations, no lightning icon), the
phone search drawer (same 20px controls), the same header on `/products`, plus
console errors and failed requests.

```bash
BASE_URL=http://127.0.0.1:8000 node tools/header-verify.mjs
```

## admin-icons-check.mjs

Needs an admin session, so **credentials are never stored here**:

```bash
ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='…' node tools/admin-icons-check.mjs
```

Without them it exits 2 and touches nothing. It checks four admin pages for
visible `ri-*` icons with a zero box or no mask, horizontal overflow, and the
`.toggle-password` behaviour (type flip, icon swap, `aria-pressed`). Exit 1 if
any problem is found.

## Notes

- `analytics` beacon `POST /events/collect` shows up as `net::ERR_ABORTED` in
  `header-verify.mjs`; it is a known pre-existing abort, not a header request.
- The storefront probe expects the modern (`fm`) theme
  (`general_settings.frontend_theme = modern`).
