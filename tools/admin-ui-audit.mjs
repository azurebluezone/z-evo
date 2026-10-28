#!/usr/bin/env node
/**
 * admin-ui-audit.mjs - see the admin the way an admin sees it.
 *
 *   node tools/admin-ui-audit.mjs                 # desktop + mobile, writes work/audit/
 *   node tools/admin-ui-audit.mjs --base=... --only=coupon,courier
 *
 * For every page it records what a screenshot cannot argue with:
 *   - HTTP status and console errors (level=error)
 *   - broken images (complete but naturalWidth 0)
 *   - the four header cards: label and rendered value
 *   - horizontal overflow at each viewport (scrollWidth > innerWidth)
 *
 * The screenshots are the deliverable for the human eye; the JSON is the
 * evidence a later session can diff. Exit code 1 means at least one page
 * failed one of the hard checks.
 */
import { chromium } from 'playwright';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const args = Object.fromEntries(
  process.argv.slice(2).map((a) => {
    const [k, v] = a.replace(/^--/, '').split('=');
    return [k, v ?? true];
  })
);

const base = args.base || 'http://127.0.0.1:8123';
const outDir = args.out || 'work/audit';
const email = args.email || 'admin@gmail.com';
const password = args.password || '123456';

/*
 * Two ways to choose pages:
 *   default      a short hand-picked list, fast enough for a smoke check;
 *   --manifest   every row admin:audit-pages wrote, i.e. the sidebar's own
 *                page list. That is the walk that can claim "every page".
 */
const defaultPages = [
  ['dashboard', '/admin/dashboard'],
  ['customers', '/admin/customer'],
  ['coupons', '/admin/coupon/manage'],
  ['couriers', '/admin/courier'],
  ['products', '/admin/products/manage'],
  ['orders', '/admin/order/all'],
  ['website-setup', '/admin/website-setup'],
  ['analytics-live', '/admin/analytics/live'],
];

const pages = (args.manifest
  ? JSON.parse(readFileSync(args.manifest, 'utf8')).rows
      .filter((row) => !String(row.uri).includes('{'))
      .map((row) => [row.route, '/' + String(row.uri).replace(/^\/+/, ''), row.result])
  : defaultPages.map(([name, path]) => [name, path, 'PASS'])
).filter(([name, , result]) => args.keepSkipped || result !== 'SKIP')
  .filter(([name]) => !args.only || String(args.only).split(',').includes(name));

const viewports = args.viewport
  ? [{ name: 'custom', width: Number(String(args.viewport).split('x')[0]), height: Number(String(args.viewport).split('x')[1] || 900) }]
  : [
      { name: 'desktop', width: 1440, height: 900 },
      { name: 'mobile', width: 390, height: 844 },
    ];

mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const results = [];

for (const vp of viewports) {
  const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, deviceScaleFactor: 1 });
  const page = await context.newPage();

  const consoleErrors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  const failedResponses = [];
  page.on('response', (res) => {
    if (res.status() >= 400) failedResponses.push(`${res.status()} ${res.url()}`);
  });

  await page.goto(`${base}/admin/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  await Promise.all([
    page.waitForURL(/\/admin\/dashboard/, { timeout: 20000 }),
    page.click('button[type="submit"]'),
  ]);

  for (const [name, path, manifestResult] of pages) {
    consoleErrors.length = 0;
    failedResponses.length = 0;

    const response = await page.goto(base + path, { waitUntil: 'networkidle', timeout: 30000 }).catch((e) => null);
    const status = response ? response.status() : 0;

    // Let the datatable finish its first draw before reading "Showing This Page".
    await page.waitForTimeout(600);

    const metrics = await page.evaluate(() => {
      const cards = [...document.querySelectorAll('[data-admin-stat-grid] [data-stat]')].map((el) => ({
        key: el.dataset.stat,
        value: el.textContent.trim(),
        label: el.closest('.card-body')?.querySelector('h6')?.textContent.trim() || '',
      }));
      return {
        title: document.title,
        gridCount: document.querySelectorAll('[data-admin-stat-grid]').length,
        cards,
        viewportWidth: window.innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        hOverflow: document.documentElement.scrollWidth > window.innerWidth,
        brokenImages: [...document.images].filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.src),
        cardWidths: [...document.querySelectorAll('[data-admin-stat-grid] > div')].map((el) => Math.round(el.getBoundingClientRect().width)),
      };
    });

    const file = join(outDir, `${name}-${vp.name}-${vp.width}.png`);
    await page.screenshot({ path: file, fullPage: false });

    results.push({
      viewport: vp.name,
      width: vp.width,
      page: name,
      path,
      status,
      ...metrics,
      consoleErrors: [...consoleErrors],
      failedResponses: [...failedResponses],
      screenshot: file,
    });
  }

  await context.close();
}

await browser.close();

const jsonPath = join(outDir, 'admin-ui-audit.json');
writeFileSync(jsonPath, JSON.stringify({ base, generatedAt: new Date().toISOString(), results }, null, 2));

const hardFailures = results.filter(
  (r) =>
    r.status !== 200 ||
    r.hOverflow ||
    (r.gridCount !== 1 && r.path !== '/admin/dashboard') ||
    r.brokenImages.length > 0 ||
    r.consoleErrors.length > 0
);

console.log(`pages audited: ${results.length}`);
for (const r of results) {
  const flags = [
    r.status !== 200 ? `HTTP ${r.status}` : '',
    r.gridCount !== 1 && r.path !== '/admin/dashboard' ? `grid=${r.gridCount}` : '',
    r.hOverflow ? `h-overflow ${r.scrollWidth}>${r.viewportWidth}` : '',
    r.brokenImages.length ? `${r.brokenImages.length} broken img` : '',
    r.consoleErrors.length ? `${r.consoleErrors.length} console error` : '',
  ].filter(Boolean);
  console.log(
    `${r.viewport.padEnd(8)} ${r.page.padEnd(15)} ${flags.length ? flags.join(', ') : 'OK'}  ${r.cards
      .map((c) => `${c.label}=${c.value}`)
      .join(' | ')}`
  );
}
console.log(`json: ${jsonPath}`);
process.exit(hardFailures.length === 0 ? 0 : 1);
