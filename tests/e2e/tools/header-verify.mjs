/**
 * Header interaction probe (read-only).
 *
 * Checks the things a static box measurement cannot see:
 *   - the account dropdown and the language menu open inside the viewport;
 *   - the phone nav drawer carries the six destinations and no lightning icon;
 *   - the phone search drawer opens with the same 20px controls;
 *   - the same header renders on a second page;
 *   - console errors and failed requests (minus the known analytics abort).
 *
 * Run from tests/e2e:
 *     node tools/header-verify.mjs
 *     BASE_URL=http://127.0.0.1:8000 node tools/header-verify.mjs
 */
import pw from '../node_modules/@playwright/test/index.js';

const { chromium } = pw;
const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');

const browser = await chromium.launch();
const page = await browser.newPage();
const consoleErrors = [];
const failed = [];

page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text()); });
page.on('requestfailed', (r) => failed.push(r.url() + ' :: ' + (r.failure()?.errorText || '')));

const out = {};

// ---------- desktop ----------
await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(base + '/', { waitUntil: 'networkidle' });

out.desktop = await page.evaluate(() => {
  const q = (s) => document.querySelector(s);
  const links = [...document.querySelectorAll('.app-nav-menu a')];
  const nonActive = links.filter((a) => !a.classList.contains('active'));
  const navStyles = [...new Set(nonActive.map((a) => {
    const s = getComputedStyle(a);
    return `${s.fontSize}|${s.fontWeight}|${s.color}|${s.height}|${s.paddingLeft}`;
  }))];
  const headerIcons = [...document.querySelectorAll('header[data-purpose="primary-header"] .bi')]
    .filter((i) => i.offsetParent !== null).map((i) => getComputedStyle(i).fontSize);
  const logo = q('.fm-header-logo').getBoundingClientRect();
  const row = q('.fm-header-row').getBoundingClientRect();
  return {
    categoriesPillNextToLogo: !!q('.fm-header-brand ~ div.hidden.xl\\:block'),
    navLinkCount: links.length,
    navStyles,
    navLabels: links.map((a) => a.textContent.trim()),
    headerIconSizes: [...new Set(headerIcons)],
    searchSubmitIcon: getComputedStyle(q('header form[role="search"] button[type="submit"] .bi')).fontSize,
    logoCenterDelta: +(((logo.left + logo.right) / 2) - ((row.left + row.right) / 2)).toFixed(1),
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth,
    help: q('.fm-topbar-contact').innerText.replace(/\s+/g, ' ').trim(),
    lang: q('.fm-topbar-lang').innerText.trim(),
    helpLeft: +q('.fm-topbar-contact').getBoundingClientRect().left.toFixed(1),
    langRight: +q('.fm-topbar-lang').getBoundingClientRect().right.toFixed(1),
  };
});

// account dropdown
await page.click('.fm-header-user > button');
await page.waitForTimeout(300);
out.accountMenu = await page.evaluate(() => {
  const m = document.querySelector('.fm-header-user .dropdown-menu');
  const b = m.getBoundingClientRect();
  return {
    shown: m.classList.contains('show'),
    visible: b.width > 0 && b.height > 0,
    left: +b.left.toFixed(1), right: +b.right.toFixed(1),
    inViewport: b.left >= 0 && b.right <= innerWidth,
  };
});
await page.keyboard.press('Escape');

// language dropdown
await page.click('.fm-topbar-lang');
await page.waitForTimeout(300);
out.languageMenu = await page.evaluate(() => {
  const m = document.querySelector('[data-purpose="announcement-banner"] .dropdown-menu');
  const b = m.getBoundingClientRect();
  return {
    shown: m.classList.contains('show'),
    left: +b.left.toFixed(1), right: +b.right.toFixed(1),
    inViewport: b.left >= 0 && b.right <= innerWidth,
  };
});
await page.keyboard.press('Escape');

// ---------- tablet ----------
await page.setViewportSize({ width: 768, height: 900 });
await page.goto(base + '/', { waitUntil: 'networkidle' });
out.tablet = await page.evaluate(() => {
  const logo = document.querySelector('.fm-header-logo').getBoundingClientRect();
  const row = document.querySelector('.fm-header-row').getBoundingClientRect();
  const act = document.querySelector('.fm-header-actions').getBoundingClientRect();
  return {
    logoCenterDelta: +(((logo.left + logo.right) / 2) - ((row.left + row.right) / 2)).toFixed(1),
    gapToActions: +(act.left - logo.right).toFixed(1),
    navHidden: getComputedStyle(document.querySelector('[data-purpose="primary-navigation"]')).display === 'none',
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth,
  };
});

// ---------- phone ----------
await page.setViewportSize({ width: 390, height: 844 });
await page.goto(base + '/', { waitUntil: 'networkidle' });
out.phone = await page.evaluate(() => {
  const logo = document.querySelector('.fm-header-logo').getBoundingClientRect();
  const row = document.querySelector('.fm-header-row').getBoundingClientRect();
  const act = document.querySelector('.fm-header-actions').getBoundingClientRect();
  const help = document.querySelector('.fm-topbar-contact').getBoundingClientRect();
  const lang = document.querySelector('.fm-topbar-lang').getBoundingClientRect();
  const badge = document.querySelector('[data-fm-cart-count]').getBoundingClientRect();
  const basket = document.querySelector('.fm-header-cart .bi-basket2-fill').getBoundingClientRect();
  return {
    logoCenterDelta: +(((logo.left + logo.right) / 2) - ((row.left + row.right) / 2)).toFixed(1),
    gapToActions: +(act.left - logo.right).toFixed(1),
    helpLeft: +help.left.toFixed(1),
    langRight: +lang.right.toFixed(1),
    helpVisible: help.width > 0,
    langVisible: lang.width > 0,
    cartBadge: { w: +badge.width.toFixed(1), h: +badge.height.toFixed(1) },
    basketVisibleW: +basket.width.toFixed(1),
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth,
    navHidden: getComputedStyle(document.querySelector('[data-purpose="primary-navigation"]')).display === 'none',
  };
});

// nav drawer
await page.click('.fm-header-brand > button[data-bs-toggle="offcanvas"]');
await page.waitForTimeout(500);
out.drawer = await page.evaluate(() => {
  const d = document.querySelector('#mobileNavDrawer');
  return {
    shown: d.classList.contains('show'),
    links: [...d.querySelectorAll('.fm-drawer-links a')].map((a) => a.textContent.replace(/\s+/g, ' ').trim()),
    lightningIcons: d.querySelectorAll('.bi-lightning-charge-fill').length,
    categoriesLink: !!d.querySelector('a[href$="/categories"]'),
  };
});
await page.keyboard.press('Escape');
await page.waitForTimeout(400);

// search drawer
await page.click('.fm-search-open');
await page.waitForTimeout(500);
out.searchDrawer = await page.evaluate(() => {
  const d = document.querySelector('#fmSearchDrawer');
  const b = d.getBoundingClientRect();
  return {
    shown: d.classList.contains('show'),
    width: +b.width.toFixed(1),
    height: +b.height.toFixed(1),
    submitIcon: getComputedStyle(d.querySelector('button[type="submit"] .bi')).fontSize,
  };
});
await page.keyboard.press('Escape');

// ---------- 320 ----------
await page.setViewportSize({ width: 320, height: 720 });
await page.goto(base + '/', { waitUntil: 'networkidle' });
out.small = await page.evaluate(() => {
  const logo = document.querySelector('.fm-header-logo').getBoundingClientRect();
  const act = document.querySelector('.fm-header-actions').getBoundingClientRect();
  return {
    gapToActions: +(act.left - logo.right).toFixed(1),
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth,
  };
});

// ---------- another page (shared header) ----------
await page.setViewportSize({ width: 390, height: 844 });
await page.goto(base + '/products', { waitUntil: 'networkidle' });
out.productsPage = await page.evaluate(() => {
  const logo = document.querySelector('.fm-header-logo');
  const row = document.querySelector('.fm-header-row');
  const l = logo.getBoundingClientRect();
  const r = row.getBoundingClientRect();
  return {
    logoPresent: !!logo,
    logoCenterDelta: +(((l.left + l.right) / 2) - ((r.left + r.right) / 2)).toFixed(1),
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth,
  };
});

out.consoleErrors = consoleErrors;
out.failedRequests = failed;

console.log(JSON.stringify(out, null, 1));
await browser.close();
