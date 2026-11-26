/*
 * Live browser check of the two new admin screens (contract gc-mwh8hoc2).
 *
 * Reads the rendered pages, not the source: the audit list must show the
 * recorded actions, and the courier dashboard must show the live balance, the
 * parcel counts, the webhook guide and the capability matrix.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const OUT = process.env.OUT_DIR || path.resolve(__dirname, '..', '..', 'work', 'courier-dashboard');
fs.mkdirSync(OUT, { recursive: true });

const results = [];
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail });
    console.log(`${ok ? 'PASS' : 'FAIL'} ${name}${detail ? ' | ' + detail : ''}`);
}

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const consoleErrors = [];
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 200)); });
    page.on('pageerror', e => consoleErrors.push(String(e).slice(0, 200)));

    await page.goto(`${BASE}/admin/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', 'admin@gmail.com');
    await page.fill('input[name="password"]', '105585');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
        page.click('button[type="submit"]'),
    ]);
    check('admin login', !page.url().includes('/login'), page.url());

    // ---- the audit log screen
    await page.goto(`${BASE}/admin/audit-log`, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('table', { timeout: 20000 });
    const audit = await page.evaluate(() => {
        const text = document.body.innerText.replace(/\s+/g, ' ');
        return {
            rows: document.querySelectorAll('tbody tr').length,
            hasNoteAction: text.toLowerCase().includes('order.note.updated'),
            hasMovementAction: text.toLowerCase().includes('inventory.movement.recorded'),
            hasFilter: !!document.querySelector('#filterAction'),
            sample: text.slice(text.indexOf('order.note.updated') - 40, text.indexOf('order.note.updated') + 160),
        };
    });
    await page.screenshot({ path: path.join(OUT, '01-audit-log.png'), fullPage: false });
    check('the audit screen lists the recorded actions', audit.hasNoteAction && audit.hasMovementAction, `${audit.rows} rows | ${audit.sample}`);
    check('the audit screen has its filters', audit.hasFilter);

    // ---- the courier dashboard screen
    await page.goto(`${BASE}/admin/courier/dashboard`, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('table', { timeout: 30000 });
    await page.waitForTimeout(1500);
    const dash = await page.evaluate(() => {
        // The theme uppercases labels with CSS, and innerText reflects that,
        // so every comparison here is case-insensitive.
        const text = document.body.innerText.replace(/\s+/g, ' ').toLowerCase();
        const featureRows = Array.from(document.querySelectorAll('table tbody tr'))
            .map(tr => (tr.textContent || '').replace(/\s+/g, ' ').trim())
            .filter(Boolean);
        const verdicts = Array.from(document.querySelectorAll('table .badge'))
            .map(b => (b.textContent || '').trim())
            .filter(Boolean);
        const lower = text.toLowerCase();
        return {
            hasBalance: lower.includes('balance'),
            hasParcels: lower.includes('parcels we booked'),
            hasWebhook: lower.includes('webhook'),
            hasRouteColumn: lower.includes('api route'),
            featureRows: featureRows.length,
            verdictCount: verdicts.length,
            verdicts: Array.from(new Set(verdicts)).slice(0, 8),
            balance: (text.match(/balance\s*৳\s*[0-9,.]+/) || [''])[0],
            webhookWarning: lower.includes('the courier cannot reach this'),
            sample: featureRows[0] ? featureRows[0].slice(0, 150) : '',
        };
    });
    await page.screenshot({ path: path.join(OUT, '02-courier-dashboard.png'), fullPage: false });
    check('the courier dashboard shows the balance', dash.hasBalance, dash.balance || 'no balance rendered');
    check('the courier dashboard shows the parcel counts', dash.hasParcels);
    check('the courier dashboard shows the webhook guide', dash.hasWebhook, dash.webhookWarning ? 'it warns that the courier cannot reach us' : '');
    check('the courier dashboard shows the capability matrix', dash.hasRouteColumn && dash.featureRows > 0, `${dash.featureRows} feature rows | verdicts: ${dash.verdicts.join(', ')}`);
    check('no console errors on either screen', consoleErrors.length === 0, consoleErrors.join(' | ').slice(0, 200));

    const failed = results.filter(r => !r.ok);
    console.log(`\nSTEPS ${results.length} | FAILED ${failed.length}`);
    console.log(`SCREENSHOTS ${OUT}`);
    await browser.close();
    process.exit(failed.length ? 1 : 0);
})().catch(e => { console.error('RUNNER CRASHED: ' + e.stack); process.exit(2); });
