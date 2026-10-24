/**
 * Admin order-board probe (read-only).
 *
 * The board is wider than its card, so the columns must scroll INSIDE the card
 * and never push the page sideways. Needs an admin session:
 *
 *     ADMIN_EMAIL=… ADMIN_PASSWORD='…' node tools/admin-board-check.mjs
 *
 * Without credentials it exits 2 and touches nothing.
 *
 * Checks, at four widths:
 *   1. documentElement.scrollWidth === innerWidth (no page-level sideways scroll);
 *   2. the six columns sit on one row and the scroller really is scrollable;
 *   3. the scroller is keyboard reachable (tabindex) and labelled;
 *   4. ArrowRight moves it.
 *
 * It never drags a card: that would persist a status change.
 */
import pw from '../node_modules/@playwright/test/index.js';

const { chromium } = pw;
const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const email = process.env.ADMIN_EMAIL;
const password = process.env.ADMIN_PASSWORD;

if (!email || !password) {
  console.error('Set ADMIN_EMAIL and ADMIN_PASSWORD. Nothing was changed.');
  process.exit(2);
}

const widths = [1440, 1024, 768, 390];
const browser = await chromium.launch();
const page = await browser.newPage();
const out = { widths: [], problems: [] };

await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(base + '/admin/login', { waitUntil: 'networkidle' });
await page.fill('input[name="email"]', email);
await page.fill('input[name="password"]', password);
await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

for (const width of widths) {
  await page.setViewportSize({ width, height: 900 });
  await page.goto(base + '/admin/order/board', { waitUntil: 'networkidle' });
  await page.waitForTimeout(200);

  const m = await page.evaluate(() => {
    const scroller = document.querySelector('#kanbanBoard > .kanban-container');
    if (!scroller) return { missing: true };
    const boards = [...scroller.querySelectorAll('.kanban-board')];
    const rows = new Set(boards.map((b) => Math.round(b.getBoundingClientRect().y))).size;
    return {
      scrollWidth: document.documentElement.scrollWidth,
      innerWidth,
      boardCount: boards.length,
      rows,
      clientWidth: scroller.clientWidth,
      scrollWidthInner: scroller.scrollWidth,
      overflowX: getComputedStyle(scroller).overflowX,
      tabindex: scroller.getAttribute('tabindex'),
      role: scroller.getAttribute('role'),
      label: scroller.getAttribute('aria-label'),
    };
  });

  if (m.missing) {
    out.problems.push(`${width}: no #kanbanBoard > .kanban-container`);
    continue;
  }

  await page.evaluate(() => document.querySelector('#kanbanBoard > .kanban-container').focus());
  const before = await page.evaluate(() => document.querySelector('#kanbanBoard > .kanban-container').scrollLeft);
  await page.keyboard.press('ArrowRight');
  await page.waitForTimeout(250);
  const after = await page.evaluate(() => document.querySelector('#kanbanBoard > .kanban-container').scrollLeft);

  out.widths.push({ width, ...m, keyboardScroll: [before, after] });

  if (m.scrollWidth > m.innerWidth) out.problems.push(`${width}: page scrolls sideways (${m.scrollWidth})`);
  if (m.overflowX !== 'auto') out.problems.push(`${width}: scroller overflow-x is ${m.overflowX}`);
  if (m.rows !== 1) out.problems.push(`${width}: columns span ${m.rows} rows`);
  if (m.scrollWidthInner <= m.clientWidth) out.problems.push(`${width}: scroller is not scrollable`);
  if (m.tabindex !== '0' || m.role !== 'region' || !m.label) out.problems.push(`${width}: scroller is not keyboard reachable/labelled`);
  if (after <= before) out.problems.push(`${width}: ArrowRight did not scroll`);
}

console.log(JSON.stringify(out, null, 1));
await browser.close();
process.exit(out.problems.length ? 1 : 0);
