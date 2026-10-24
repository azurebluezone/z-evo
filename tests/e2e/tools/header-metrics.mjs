/**
 * Header geometry probe (read-only).
 *
 * Visits the storefront at eight widths and prints one JSON record per width:
 * row / brand / search / actions boxes, logo centre delta, top-bar gutters,
 * icon sizes, nav metrics, and the scroll width.
 *
 * Run from tests/e2e:
 *     node tools/header-metrics.mjs
 *     BASE_URL=http://127.0.0.1:8000 node tools/header-metrics.mjs
 *
 * The assertions that gate a release live in ../header-layout.spec.js; this
 * script is the measuring tape you reach for when one of them fails.
 */
import pw from '../node_modules/@playwright/test/index.js';

const { chromium } = pw;
const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const widths = [1440, 1280, 1200, 1199, 1024, 768, 390, 320];

const browser = await chromium.launch();
const page = await browser.newPage();
const results = [];

for (const width of widths) {
  await page.setViewportSize({ width, height: 900 });
  await page.goto(base + '/', { waitUntil: 'networkidle' });
  // The first paint after a cold cache can still be mid-layout; one frame is
  // enough and keeps the probe honest about steady state.
  await page.waitForTimeout(150);

  const data = await page.evaluate(() => {
    const q = (s) => document.querySelector(s);
    const box = (el) => {
      if (!el) return null;
      const b = el.getBoundingClientRect();
      return { x: +b.x.toFixed(1), y: +b.y.toFixed(1), w: +b.width.toFixed(1), h: +b.height.toFixed(1) };
    };
    const cs = (el) => (el ? getComputedStyle(el) : null);

    const row = q('.fm-header-row');
    const logo = q('.fm-header-logo');
    const hamburger = q('.fm-header-brand > button[data-bs-toggle="offcanvas"]');
    const actions = q('.fm-header-actions');
    const navLinks = [...document.querySelectorAll('.app-nav-menu a')].map((a) => {
      const s = cs(a);
      return { t: a.textContent.trim(), box: box(a), font: s.fontSize, weight: s.fontWeight, color: s.color };
    });
    const icons = [...document.querySelectorAll('header[data-purpose="primary-header"] .bi')]
      .filter((i) => i.offsetParent !== null)
      .map((i) => ({ cls: i.className, size: cs(i).fontSize }));
    const submit = q('header[data-purpose="primary-header"] form[role="search"] button[type="submit"] .bi');
    const help = q('.fm-topbar-contact');
    const lang = q('.fm-topbar-lang');

    const l = logo ? logo.getBoundingClientRect() : null;
    const r = row ? row.getBoundingClientRect() : null;

    return {
      vw: innerWidth,
      scrollW: document.documentElement.scrollWidth,
      row: box(row),
      brand: box(q('.fm-header-brand')),
      hamburger: box(hamburger),
      logo: box(logo),
      logoImg: box(q('.fm-header-logo img')),
      search: box(q('.fm-header-search')),
      actions: box(actions),
      nav: box(q('[data-purpose="primary-navigation"]')),
      navLinks,
      icons,
      submitIcon: submit ? cs(submit).fontSize : null,
      help: box(help),
      lang: box(lang),
      helpText: help ? help.innerText.replace(/\s+/g, ' ').trim() : null,
      langText: lang ? lang.innerText.trim() : null,
      logoCenteredDelta: l && r
        ? +(((l.left + l.right) / 2) - ((r.left + r.right) / 2)).toFixed(1)
        : null,
      gapToActions: l && actions
        ? +(actions.getBoundingClientRect().left - l.right).toFixed(1)
        : null,
    };
  });

  data.width = width;
  results.push(data);
}

console.log(JSON.stringify(results, null, 1));
await browser.close();
