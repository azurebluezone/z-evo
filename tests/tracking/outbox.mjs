import pw from '/home/xubuntu/Desktop/z-evo/tests/e2e/node_modules/@playwright/test/index.js';
const { chromium } = pw;
const b = await chromium.launch();

async function run(label, block) {
  const p = await b.newPage();
  const errs = [];
  p.on('console', m => { if (m.type() === 'error') errs.push(m.text().slice(0, 120)); });
  p.on('pageerror', e => errs.push('PAGEERROR ' + e.message.slice(0, 120)));
  const seen = [];
  p.on('request', r => { if (r.url().includes('/events/collect')) seen.push(r.method()); });
  if (block) {
    await p.route('**/events/collect', route => route.abort('failed'));
  }
  await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
  const before = await p.evaluate(() => JSON.parse(localStorage.getItem('__event_queue__') || '[]').length);
  const track = await p.evaluate(() => ({ hasToken: !!window.__TRACK__?.token, path: window.__TRACK__?.page_path }));
  // Force a flush and give the request time to settle.
  await p.evaluate(() => window.__EVENT_LOGGER__.flush());
  await p.waitForTimeout(1500);
  const after = await p.evaluate(() => JSON.parse(localStorage.getItem('__event_queue__') || '[]').length);
  console.log(label, JSON.stringify({ before, after, collectRequests: seen.length, track, errors: [...new Set(errs)].slice(0, 3) }));
  await p.close();
}

await run('ack-ok  ', false);
await run('ack-fail', true);
await b.close();
