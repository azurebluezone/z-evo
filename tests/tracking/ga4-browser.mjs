/**
 * GA4 browser tag + bridge check (T3).
 *
 * Runs against the local dev server with TRACKING_GA4_ID and
 * TRACKING_SERVER_CONTAINER_URL set. gtag.js is stubbed at the network layer so
 * the test measures our code, not Google's:
 *
 *   - consent denied  -> no gtag request, no __GA4__ bridge
 *   - consent granted -> gtag config carries transport_url (the sGTM host)
 *   - __GA4__.send    -> dataLayer receives the mapped GA4 event
 *   - __GA4__.purchase-> dataLayer receives purchase with the invoice as
 *                        transaction_id (the GA4 dedupe key)
 *
 * Run: node tests/tracking/ga4-browser.mjs
 */
import pw from '../../tests/e2e/node_modules/@playwright/test/index.js';

const BASE = 'http://127.0.0.1:8000';
const GA4_ID = process.env.EXPECT_GA4_ID || 'G-TEST123';
const SERVER_URL = process.env.EXPECT_SERVER_URL || 'https://t.localhost:8000';

const browser = await pw.chromium.launch();
let failures = 0;

function check(label, ok, detail = '') {
  if (!ok) failures++;
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}${detail ? ' :: ' + detail : ''}`);
}

async function newPage({ consent }) {
  const context = await browser.newContext();

  if (consent) {
    await context.addCookies([{
      name: 'consent',
      value: encodeURIComponent(JSON.stringify(consent)),
      domain: '127.0.0.1',
      path: '/'
    }]);
  }

  const page = await context.newPage();
  const gtagRequests = [];
  const external = [];

  // Stub gtag.js: present, but inert. Any assertion below is about our own
  // dataLayer commands, not about Google's script doing something.
  // A RegExp, not a glob: Playwright's glob matching does not reliably include
  // the ?id= query string, and a silently unmatched route made this test report
  // "no request" while the script was loading fine.
  await page.route(/googletagmanager\.com\/gtag\/js/, (route) => {
    gtagRequests.push(route.request().url());
    return route.fulfill({
      status: 200,
      contentType: 'application/javascript',
      body: 'window.__GTAG_STUB__ = true;'
    });
  });

  page.on('request', (req) => {
    const url = req.url();
    if (url.includes('google-analytics.com')) external.push(url);
  });

  await page.goto(BASE + '/', { waitUntil: 'networkidle' });

  return { context, page, gtagRequests, external };
}

const layer = () => Array.from(window.dataLayer || []).map((entry) => Array.from(entry));

// ---------------------------------------------------------------------------
// 1. Denied: nothing Google-related may be printed or requested.
// ---------------------------------------------------------------------------
{
  const { context, page, gtagRequests, external } = await newPage({ consent: null });

  const state = await page.evaluate(() => ({
    bridge: typeof window.__GA4__,
    gtag: typeof window.gtag,
    layerLength: (window.dataLayer || []).length
  }));

  check('denied: __GA4__ is not defined', state.bridge === 'undefined', state.bridge);
  check('denied: no gtag.js request', gtagRequests.length === 0, 'requests=' + gtagRequests.length);
  check('denied: no google-analytics request', external.length === 0, 'requests=' + external.length);

  await context.close();
}

// ---------------------------------------------------------------------------
// 2. Granted: config + bridge.
// ---------------------------------------------------------------------------
{
  const { context, page, gtagRequests, external } = await newPage({
    consent: { analytics: 'granted', marketing: 'denied', v: 1, ts: 1 }
  });

  const entries = await page.evaluate(layer);

  check('granted: gtag.js requested once', gtagRequests.length === 1, 'requests=' + gtagRequests.length);
  check('granted: gtag.js URL carries the measurement id', (gtagRequests[0] || '').includes(GA4_ID), gtagRequests[0] || '');
  check('granted: no direct google-analytics hit (transport_url handles it)', external.length === 0, 'requests=' + external.length);

  const config = entries.find((e) => e[0] === 'config' && e[1] === GA4_ID);
  check('granted: gtag config uses the measurement id', !!config);
  check('granted: send_page_view is false (the event layer sends it)', config?.[2]?.send_page_view === false, JSON.stringify(config?.[2] || {}));
  check('granted: transport_url points at the server container', config?.[2]?.transport_url === SERVER_URL, String(config?.[2]?.transport_url));

  // The event logger queues PageView while it initialises, so the bridge has to
  // exist before that script runs. This assertion is what catches a regression
  // where the GA4 partial is included too late and the first page view vanishes.
  const pageView = entries.find((e) => e[0] === 'event' && e[1] === 'page_view');
  check('granted: initial PageView reached GA4', !!pageView, JSON.stringify(pageView?.[2] || {}));
  check('granted: page_view carries page_location', typeof pageView?.[2]?.page_location === 'string' && pageView[2].page_location.length > 0, String(pageView?.[2]?.page_location));

  const consent = entries.find((e) => e[0] === 'consent' && e[1] === 'default');
  check('granted: analytics_storage granted', consent?.[2]?.analytics_storage === 'granted', JSON.stringify(consent?.[2] || {}));
  check('granted: ad signals stay denied', consent?.[2]?.ad_storage === 'denied' && consent?.[2]?.ad_user_data === 'denied');

  // ---- the bridge: a mapped event reaches dataLayer with items ------------
  await page.evaluate(() => {
    window.__GA4__.send('AddToCart', {
      event_data: { product_id: '77', product_name: 'Baby Frock', product_price: '1250' }
    }, { url: location.href, page_title: 'Test page' });
  });

  const afterSend = await page.evaluate(layer);
  const addToCart = afterSend.find((e) => e[0] === 'event' && e[1] === 'add_to_cart');

  check('bridge: AddToCart maps to add_to_cart', !!addToCart);
  check('bridge: item id/name/price survive', addToCart?.[2]?.items?.[0]?.item_id === '77' && addToCart?.[2]?.items?.[0]?.price === 1250, JSON.stringify(addToCart?.[2]?.items || []));
  check('bridge: page_location is forwarded', typeof addToCart?.[2]?.page_location === 'string' && addToCart[2].page_location.includes('/'), String(addToCart?.[2]?.page_location));

  // ---- unmapped event is dropped, not sent with an invented name ----------
  const before = afterSend.length;
  await page.evaluate(() => window.__GA4__.send('Scroll_50', {}, {}));
  const afterUnmapped = await page.evaluate(layer);
  check('bridge: unmapped event is dropped', afterUnmapped.length === before, `before=${before} after=${afterUnmapped.length}`);

  // ---- purchase uses the invoice as transaction_id ------------------------
  await page.evaluate(() => {
    window.__GA4__.purchase({
      transaction_id: 'INV-9001',
      value: 2450,
      currency: 'BDT',
      items: [{ item_id: '77', item_name: 'Baby Frock', price: 1250, quantity: 2 }]
    });
  });

  const afterPurchase = await page.evaluate(layer);
  const purchase = afterPurchase.find((e) => e[0] === 'event' && e[1] === 'purchase');

  check('purchase: event name is purchase', !!purchase);
  check('purchase: transaction_id is the invoice', purchase?.[2]?.transaction_id === 'INV-9001', String(purchase?.[2]?.transaction_id));
  check('purchase: value and currency', purchase?.[2]?.value === 2450 && purchase?.[2]?.currency === 'BDT', JSON.stringify(purchase?.[2] || {}));
  check('purchase: items forwarded', (purchase?.[2]?.items || []).length === 1);

  await context.close();
}

// ---------------------------------------------------------------------------
// 3. Marketing granted but analytics denied: still no GA4.
// ---------------------------------------------------------------------------
{
  const { context, page, gtagRequests } = await newPage({
    consent: { analytics: 'denied', marketing: 'granted', v: 1, ts: 1 }
  });

  const bridge = await page.evaluate(() => typeof window.__GA4__);
  check('analytics denied + marketing granted: no GA4 bridge', bridge === 'undefined', bridge);
  check('analytics denied: no gtag.js request', gtagRequests.length === 0, 'requests=' + gtagRequests.length);

  await context.close();
}

await browser.close();

console.log(`\nRESULT: ${failures === 0 ? 'PASS (0 failures)' : 'FAIL (' + failures + ' failures)'}`);
process.exit(failures === 0 ? 0 : 1);
