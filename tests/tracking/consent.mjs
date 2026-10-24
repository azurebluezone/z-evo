import pw from '/home/xubuntu/Desktop/z-evo/tests/e2e/node_modules/@playwright/test/index.js';
const { chromium } = pw;
const b = await chromium.launch();
const BASE = 'http://127.0.0.1:8000';

async function fresh(opts = {}) {
  const ctx = await b.newContext(opts);
  const p = await ctx.newPage();
  const fb = [];
  const errs = [];
  const infos = [];
  p.on('request', r => { if (r.url().includes('connect.facebook.net')) fb.push(r.url().slice(0, 60)); });
  p.on('console', m => {
    if (m.type() === 'error') errs.push(m.text().slice(0, 80));
    if (m.type() === 'info') infos.push(m.text().slice(0, 80));
  });
  p.on('pageerror', e => errs.push('PAGEERROR ' + e.message.slice(0, 80)));
  return { ctx, p, fb, errs, infos };
}

// A: first visit, no decision
{
  const { ctx, p, fb, errs } = await fresh();
  await p.goto(BASE + '/', { waitUntil: 'networkidle' });
  const r = await p.evaluate(() => ({
    marketing: window.__CONSENT__.marketing,
    decided: window.__CONSENT__.decided,
    banner: !!document.querySelector('[aria-label="Cookie choices"]'),
    bannerText: (document.querySelector('[aria-label="Cookie choices"] p')?.textContent || '').slice(0, 40)
  }));
  console.log('A first-visit', JSON.stringify({ ...r, fbRequests: fb.length, errors: [...new Set(errs)] }));
  await ctx.close();
}

// B: accept all -> reload -> pixel loads
{
  const { ctx, p, fb, errs } = await fresh();
  await p.goto(BASE + '/', { waitUntil: 'networkidle' });
  const before = fb.length;
  await Promise.all([
    p.waitForNavigation({ waitUntil: 'networkidle' }),
    p.click('button:has-text("Accept all")')
  ]);
  await p.waitForTimeout(800);
  const r = await p.evaluate(() => ({
    marketing: window.__CONSENT__.marketing,
    analytics: window.__CONSENT__.analytics,
    decided: window.__CONSENT__.decided,
    banner: !!document.querySelector('[aria-label="Cookie choices"]'),
    cookie: document.cookie.split('; ').find(c => c.startsWith('consent='))
  }));
  console.log('B accept-all', JSON.stringify({ ...r, fbBefore: before, fbAfter: fb.length, errors: [...new Set(errs)] }));
  await ctx.close();
}

// C: reject -> no pixel, cookie remembered
{
  const { ctx, p, fb, errs } = await fresh();
  await p.goto(BASE + '/', { waitUntil: 'networkidle' });
  await p.click('button:has-text("Reject non-essential")');
  await p.waitForTimeout(600);
  const r = await p.evaluate(() => ({
    marketing: window.__CONSENT__.marketing,
    decided: window.__CONSENT__.decided,
    banner: !!document.querySelector('[aria-label="Cookie choices"]')
  }));
  await p.reload({ waitUntil: 'networkidle' });
  const after = await p.evaluate(() => ({ banner: !!document.querySelector('[aria-label="Cookie choices"]'), marketing: window.__CONSENT__.marketing }));
  console.log('C reject', JSON.stringify({ ...r, afterReload: after, fbRequests: fb.length, errors: [...new Set(errs)] }));
  await ctx.close();
}

// D: localStorage blocked
{
  const { ctx, p, fb, errs } = await fresh();
  await p.addInitScript(() => {
    Object.defineProperty(window, 'localStorage', {
      get() { throw new Error('blocked'); }
    });
  });
  await p.goto(BASE + '/', { waitUntil: 'networkidle' });
  await p.evaluate(() => window.__EVENT_LOGGER__.flush());
  await p.waitForTimeout(900);
  const r = await p.evaluate(() => ({ hasQueueApi: !!window.__EVENT_LOGGER__, track: !!window.__TRACK__.token }));
  console.log('D storage-blocked', JSON.stringify({ ...r, errors: [...new Set(errs)] }));
  await ctx.close();
}

// E: cookies blocked at flush time -> 422 -> info, no error
{
  const { ctx, p, errs, infos } = await fresh();
  await p.goto(BASE + '/', { waitUntil: 'networkidle' });
  await ctx.clearCookies();
  await p.evaluate(() => window.__EVENT_LOGGER__.flush());
  await p.waitForTimeout(1200);
  console.log('E cookie-blocked', JSON.stringify({ errors: [...new Set(errs)], infos: [...new Set(infos)] }));
  await ctx.close();
}

// F: consent page controls + banner suppressed there
{
  const { ctx, p, errs } = await fresh();
  await p.goto(BASE + '/privacy/cookies', { waitUntil: 'networkidle' });
  const r1 = await p.evaluate(() => ({
    banner: !!document.querySelector('[aria-label="Cookie choices"]'),
    buttons: [...document.querySelectorAll('[data-consent]')].map(b => b.getAttribute('data-consent')),
    title: document.querySelector('h1')?.textContent.trim()
  }));
  await p.click('[data-consent="prefs"]');
  await p.waitForTimeout(300);
  const panel = await p.evaluate(() => ({
    panel: !!document.querySelector('[aria-label="Cookie preferences"]'),
    analyticsChecked: document.getElementById('consent-analytics')?.checked,
    marketingChecked: document.getElementById('consent-marketing')?.checked
  }));
  console.log('F consent-page', JSON.stringify({ ...r1, ...panel, errors: [...new Set(errs)] }));
  await ctx.close();
}

await b.close();
