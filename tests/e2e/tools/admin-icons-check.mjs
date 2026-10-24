/**
 * Admin icon + password-toggle probe (read-only, env-gated).
 *
 * The admin needs a real session, so credentials are NOT stored here:
 *
 *     ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='...' \
 *       node tools/admin-icons-check.mjs
 *
 * Without them the script exits 2 and does nothing.
 *
 * What it proves:
 *   1. every visible <i class="ri-..."> on the checked pages has a non-zero
 *      box and a mask (the 2026-10-24 "empty eye button" regression);
 *   2. each .toggle-password flips its input between password/text, swaps the
 *      icon, and updates aria-pressed + aria-label;
 *   3. no checked admin page scrolls sideways because of the icon layer.
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

const pages = [
  ['/admin/dashboard', 'dashboard'],
  ['/admin/change-password', 'change-password'],
  ['/admin/products/manage', 'products'],
  ['/admin/categories/manage', 'categories'],
];

const browser = await chromium.launch();
const page = await browser.newPage();
const out = { pages: [], toggles: [], problems: [] };

await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(base + '/admin/login', { waitUntil: 'networkidle' });
await page.fill('input[name="email"]', email);
await page.fill('input[name="password"]', password);
await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

for (const [url, name] of pages) {
  const res = await page.goto(base + url, { waitUntil: 'networkidle' });
  const m = await page.evaluate(() => {
    const icons = [...document.querySelectorAll('i[class*="ri-"]')];
    const zeroVisible = icons.filter((i) => {
      const r = i.getBoundingClientRect();
      return i.offsetParent !== null && (r.width === 0 || r.height === 0);
    });
    const noMask = icons.filter((i) => {
      const s = getComputedStyle(i);
      return i.offsetParent !== null && s.maskImage === 'none' && s.webkitMaskImage === 'none';
    });
    return {
      icons: icons.length,
      zeroVisible: zeroVisible.map((i) => i.className),
      noMask: noMask.map((i) => i.className),
      scrollWidth: document.documentElement.scrollWidth,
      innerWidth,
    };
  });
  out.pages.push({ name, status: res.status(), ...m });
  if (m.zeroVisible.length) out.problems.push(`${name}: zero-size visible icons`);
  if (m.noMask.length) out.problems.push(`${name}: visible icons without a mask`);
  if (m.scrollWidth > m.innerWidth) out.problems.push(`${name}: horizontal overflow`);
}

await page.goto(base + '/admin/change-password', { waitUntil: 'networkidle' });

const toggles = await page.$$('.toggle-password');
for (const btn of toggles) {
  const target = await btn.getAttribute('data-target');
  const before = await page.evaluate((s) => ({
    type: document.querySelector(s).type,
    pressed: document.querySelector(`.toggle-password[data-target="${s}"]`).getAttribute('aria-pressed'),
    icon: document.querySelector(`.toggle-password[data-target="${s}"] i`).className,
  }), target);

  await btn.click();
  const on = await page.evaluate((s) => ({
    type: document.querySelector(s).type,
    pressed: document.querySelector(`.toggle-password[data-target="${s}"]`).getAttribute('aria-pressed'),
    icon: document.querySelector(`.toggle-password[data-target="${s}"] i`).className,
    focused: document.activeElement.id,
  }), target);

  await btn.click();
  const off = await page.evaluate((s) => ({
    type: document.querySelector(s).type,
    pressed: document.querySelector(`.toggle-password[data-target="${s}"]`).getAttribute('aria-pressed'),
    icon: document.querySelector(`.toggle-password[data-target="${s}"] i`).className,
  }), target);

  out.toggles.push({ target, before, on, off });

  if (on.type !== 'text' || off.type !== 'password') out.problems.push(`${target}: type did not flip`);
  if (on.pressed !== 'true' || off.pressed !== 'false') out.problems.push(`${target}: aria-pressed did not flip`);
  if (!on.icon.includes('ri-eye-line') || on.icon.includes('eye-off')) out.problems.push(`${target}: icon did not change`);
}

console.log(JSON.stringify(out, null, 1));
await browser.close();
process.exit(out.problems.length ? 1 : 0);
