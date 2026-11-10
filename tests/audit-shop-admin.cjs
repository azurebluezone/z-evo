/*
 * Shop + admin audit (contract gc-mw49m1ri).
 *
 * Everything here is observed from a real HTTP response or a real rendered
 * DOM: no fixture replaces the application. Run through
 * tests/audit-shop-admin.sh so NODE_PATH and the artifact directory are set.
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { chromium } = require('playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const EMAIL = process.env.ADMIN_EMAIL || 'admin@gmail.com';
const PASSWORD = process.env.ADMIN_PASSWORD || '105585';
const ROOT = path.resolve(__dirname, '..');
const OUT = process.env.OUT_DIR || path.join(ROOT, 'work/audit-shop-admin');
const SHOTS = path.join(OUT, 'shots');
fs.mkdirSync(SHOTS, { recursive: true });

const DEMO = /Jannat|Demo Brand|Developer Zone|SPORTS EQUIPMENT|\+?8801700000000|\b01700000000\b|jannatminimart|Add your number|freshmart|Evergreen Terrace|555-FOOD|Lorem ipsum|Test zone/i;
const results = [];

function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail });
    console.log(`${ok ? 'PASS' : 'FAIL'} ${name}${detail ? ' | ' + detail : ''}`);
}

function textOf(body) {
    return (body || '').replace(/\s+/g, ' ').trim();
}

async function jsonCheck() {
    const code = "echo json_encode(['demo_brand'=>\\App\\Models\\Brand::where('name','Demo Brand')->count(),'social_links'=>\\App\\Models\\SocialMedia::where('link','like','%jannat%')->count(),'contacts_bad'=>\\Illuminate\\Support\\Facades\\DB::table('contacts')->where('phone','!=','')->orWhere('email','!=','')->orWhere('address','!=','')->count(),'banners'=>\\Illuminate\\Support\\Facades\\DB::table('banners')->count(),'services_bad'=>\\Illuminate\\Support\\Facades\\DB::table('services')->where('subtitle','like','%cards and mobile%')->orWhere('subtitle','like','%24 hours%')->orWhere('subtitle','like','%7 days%')->count(),'shipping_test'=>\\Illuminate\\Support\\Facades\\DB::table('shipping_charges')->where('name','Test zone')->count(),'orders'=>\\Illuminate\\Support\\Facades\\DB::table('orders')->count(),'customers'=>\\Illuminate\\Support\\Facades\\DB::table('customers')->count(),'products'=>\\Illuminate\\Support\\Facades\\DB::table('products')->count()]);";
    const raw = execFileSync('php', ['artisan', 'tinker', '--execute', code], {
        cwd: ROOT,
        encoding: 'utf8',
        timeout: 30000,
    });
    const line = raw.trim().split('\n').filter(Boolean).pop();
    return JSON.parse(line);
}

async function newPage(ctx, onConsole) {
    const page = await ctx.newPage();
    if (onConsole) {
        page.on('pageerror', error => onConsole.errors.push('js: ' + error.message));
        page.on('requestfailed', request => onConsole.failed.push('failed: ' + request.url()));
        page.on('response', response => {
            const type = response.request().resourceType();
            if (['image', 'script', 'stylesheet', 'font'].includes(type) && response.status() >= 400) {
                onConsole.failed.push(`${response.status()} ${response.url()}`);
            }
        });
    }
    return page;
}

async function visit(ctx, url, opts = {}) {
    const log = { errors: [], failed: [] };
    const page = await newPage(ctx, log);
    let response = null;
    try {
        response = await page.goto(BASE + url, { waitUntil: 'load', timeout: 45000 });
        await page.waitForTimeout(opts.wait ?? 700);
    } catch (error) {
        log.errors.push('nav: ' + error.message.slice(0, 120));
    }
    const dom = await page.evaluate(() => ({
        title: document.title,
        width: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        text: document.body ? document.body.innerText : '',
        images: Array.from(document.images).filter(image => image.complete && image.naturalWidth === 0).length,
    })).catch(() => ({ title: '', width: 0, scrollWidth: 0, text: '', images: 0 }));
    if (opts.shot) {
        await page.screenshot({ path: path.join(SHOTS, opts.shot), fullPage: false }).catch(() => {});
    }
    if (opts.keepOpen) {
        return { page, status: response ? response.status() : 0, ...dom, log };
    }
    await page.close();
    return { status: response ? response.status() : 0, ...dom, log };
}

async function sweep(ctx, label, pages) {
    for (const item of pages) {
        const expected = item.status || 200;
        const r = await visit(ctx, item.url, { wait: item.wait, shot: item.shot });
        const demo = r.text.match(DEMO);
        const clean = !demo && r.log.errors.length === 0 && r.log.failed.length === 0;
        const fits = r.scrollWidth <= r.width + 1;
        const statusOk = r.status === expected;
        const bodyOk = item.contains ? r.text.includes(item.contains) : true;
        check(
            `${label} ${item.url}`,
            statusOk && fits && clean && bodyOk,
            [
                `status=${r.status}`,
                `width=${r.width}/${r.scrollWidth}`,
                demo ? `demo=${demo.slice(0, 3).join(',')}` : '',
                bodyOk ? '' : `missing=${item.contains}`,
                r.log.errors[0] || '',
                r.log.failed[0] || '',
            ].filter(Boolean).join(' ')
        );
    }
}

async function loginAdmin(ctx) {
    const page = await ctx.newPage();
    await page.goto(BASE + '/admin/login', { waitUntil: 'load', timeout: 45000 });
    await page.fill('input[name=email]', EMAIL);
    await page.fill('input[name=password]', PASSWORD);
    await Promise.all([
        page.waitForURL(url => !String(url).includes('/admin/login'), { timeout: 30000 }),
        page.click('button[type=submit]'),
    ]);
    return page;
}

async function adminSweep(ctx, label, pages, mobile) {
    const page = await loginAdmin(ctx);
    for (const item of pages) {
        const log = { errors: [], failed: [] };
        page.on('pageerror', error => log.errors.push('js: ' + error.message));
        page.on('response', response => {
            const type = response.request().resourceType();
            if (['image', 'script', 'stylesheet', 'font'].includes(type) && response.status() >= 400) {
                log.failed.push(`${response.status()} ${response.url()}`);
            }
        });
        let response = null;
        try {
            response = await page.goto(BASE + item.url, { waitUntil: 'load', timeout: 45000 });
            await page.waitForTimeout(item.wait ?? 1800);
        } catch (error) {
            log.errors.push('nav: ' + error.message.slice(0, 120));
        }
        const dom = await page.evaluate(() => {
            const stat = document.querySelector('[data-stat="showing-this-page"]');
            const table = document.querySelector('.content-wrapper table');
            // DataTables renders a "No data available" row for an empty
            // result; it is chrome, not a record.
            const rows = table && table.tBodies[0]
                ? Array.from(table.tBodies[0].rows).filter(row => {
                    const emptyCopy = /No data available|No matching records|No .+ yet/i.test(row.innerText);
                    const emptyCell = row.cells.length === 1 && row.cells[0].colSpan > 1;
                    return !emptyCopy && !emptyCell;
                }).length
                : 0;
            return {
                width: innerWidth,
                scrollWidth: document.documentElement.scrollWidth,
                text: document.body ? document.body.innerText : '',
                stat: stat ? stat.textContent.trim() : null,
                rows,
            };
        }).catch(() => ({ width: 0, scrollWidth: 0, text: '', stat: null, rows: 0 }));
        if (item.shot) {
            await page.screenshot({ path: path.join(SHOTS, item.shot), fullPage: false }).catch(() => {});
        }
        const fits = dom.scrollWidth <= dom.width + 1;
        const clean = log.errors.length === 0 && log.failed.length === 0;
        let statOk = true;
        if (item.stat) {
            statOk = dom.rows > 0
                ? dom.stat === dom.rows.toLocaleString()
                : ['—', '0', ''].includes(dom.stat ?? '');
        }
        check(
            `${label} ${item.url}`,
            (response ? response.status() : 0) === 200 && fits && clean && statOk,
            [
                `status=${response ? response.status() : 0}`,
                `width=${dom.width}/${dom.scrollWidth}`,
                `rows=${dom.rows}`,
                `card=${dom.stat}`,
                statOk ? '' : 'card mismatch',
                log.errors[0] || '',
                log.failed[0] || '',
            ].filter(Boolean).join(' ')
        );
    }
    await page.close();
}

async function measure(page) {
    const data = await page.evaluate(() => {
        const nav = performance.getEntriesByType('navigation')[0] || {};
        const resources = performance.getEntriesByType('resource');
        const total = resources.reduce((sum, entry) => sum + (entry.transferSize || 0), 0);
        const largest = resources
            .map(entry => ({ url: entry.name, bytes: entry.transferSize || 0 }))
            .sort((a, b) => b.bytes - a.bytes)
            .slice(0, 5);
        return {
            html: nav.transferSize || 0,
            total,
            count: resources.length,
            largest,
        };
    });
    return data;
}

(async () => {
    console.log('== Shop + admin audit ==');
    console.log('base:', BASE);

    // 1. Database claims the presentation must not contradict.
    try {
        const db = await jsonCheck();
        check('db demo brand removed', db.demo_brand === 0, `demo_brand=${db.demo_brand}`);
        check('db demo social link removed', db.social_links === 0, `social_links=${db.social_links}`);
        check('db dummy contact rows cleared', db.contacts_bad === 0, `contacts_bad=${db.contacts_bad}`);
        check('db one owned banner only', db.banners === 1, `banners=${db.banners}`);
        check('db service promises replaced', db.services_bad === 0, `services_bad=${db.services_bad}`);
        check('db no test shipping zone', db.shipping_test === 0, `shipping_test=${db.shipping_test}`);
        check('db test orders removed', db.orders === 0, `orders=${db.orders}`);
        check('db test customers removed', db.customers === 0, `customers=${db.customers}`);
        check('db catalog kept for the shop', db.products > 0, `products=${db.products}`);
        fs.writeFileSync(path.join(OUT, 'db.json'), JSON.stringify(db, null, 2));
    } catch (error) {
        check('db inspection', false, error.message.slice(0, 160));
    }

    const browser = await chromium.launch({ headless: true });
    const desktop = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const mobile = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });

    // 2. Storefront pages and their fallbacks.
    const storefront = [
        { url: '/', contains: 'Popular categories', shot: 'home-desktop.png' },
        { url: '/products', contains: 'Displaying', shot: 'products-desktop.png' },
        { url: '/product/artisan-raw-honey', contains: 'Artisan Raw Honey', shot: 'product-desktop.png' },
        { url: '/category/fresh-farm-vegetables', contains: 'Fresh Farm Vegetables' },
        { url: '/search?keyword=zzzznotfound', contains: 'Search' },
        { url: '/search?keyword=', contains: 'Search' },
        { url: '/shop/cart', contains: 'Shopping Basket' },
        { url: '/customer/checkout', contains: 'Your cart is empty' },
        { url: '/customer/login', contains: 'Login Account' },
        { url: '/customer/register', contains: 'Create' },
        { url: '/site/contact-us', contains: 'Contact us', shot: 'contact-desktop.png' },
        { url: '/about-us', contains: 'ZEvo' },
        { url: '/faq', contains: 'FAQ' },
        { url: '/track-order', contains: 'Track' },
        { url: '/product/does-not-exist', status: 404, contains: 'This page is not here', shot: '404-desktop.png' },
        { url: '/no-such-page', status: 404, contains: 'This page is not here' },
    ];
    await sweep(desktop, 'desktop', storefront);

    // 3. Mobile storefront.
    await sweep(mobile, 'mobile', [
        { url: '/', contains: 'Popular categories', shot: 'home-mobile.png' },
        { url: '/products', contains: 'Displaying', shot: 'products-mobile.png' },
        { url: '/product/artisan-raw-honey', contains: 'Artisan Raw Honey', shot: 'product-mobile.png' },
        { url: '/customer/checkout', contains: 'Your cart is empty', shot: 'checkout-empty-mobile.png' },
        { url: '/customer/login', contains: 'Login Account' },
        { url: '/site/contact-us', contains: 'Contact us' },
        { url: '/product/does-not-exist', status: 404, contains: 'This page is not here', shot: '404-mobile.png' },
    ]);

    // 4. Footer links resolve (contact route included).
    const linkPage = await newPage(desktop);
    await linkPage.goto(BASE + '/', { waitUntil: 'load' });
    const footerLinks = await linkPage.evaluate(() =>
        Array.from(new Set(Array.from(document.querySelectorAll('footer a[href]')).map(a => a.href)))
            .filter(href => href.startsWith(location.origin))
    );
    await linkPage.close();
    let footerBad = 0;
    for (const href of footerLinks) {
        const response = await desktop.request.get(href, { maxRedirects: 5 });
        if (response.status() >= 400) {
            footerBad++;
            console.log('  footer link', response.status(), href.replace(BASE, ''));
        }
    }
    check('all footer links resolve', footerLinks.length > 0 && footerBad === 0, `links=${footerLinks.length} bad=${footerBad}`);

    // 5. Contact route: /contact redirects, canonical page is 200.
    const redirect = await desktop.request.get(BASE + '/contact', { maxRedirects: 0 });
    check('/contact redirects to the real page', redirect.status() === 301 && (redirect.headers()['location'] || '').includes('/site/contact-us'), `status=${redirect.status()} location=${redirect.headers()['location'] || ''}`);
    const canonical = await desktop.request.get(BASE + '/site/contact-us', { maxRedirects: 5 });
    check('/site/contact-us returns 200', canonical.status() === 200, `status=${canonical.status()}`);

    // 6. Error paths a visitor can actually hit.
    {
        const page = await newPage(desktop);
        await page.goto(BASE + '/customer/login', { waitUntil: 'load' });
        await page.fill('input[name=phone]', '01799999999');
        await page.fill('input[name=password]', 'wrongpass123');
        await Promise.all([
            page.waitForLoadState('load'),
            page.click('form.fm-au__form button[type=submit]'),
        ]);
        await page.waitForTimeout(400);
        const alert = await page.locator('.alert').first().innerText().catch(() => '');
        check('invalid login explains the failure', /No account found/i.test(alert), textOf(alert).slice(0, 80));
        await page.close();
    }
    {
        const page = await newPage(desktop);
        await page.goto(BASE + '/site/contact-us', { waitUntil: 'load' });
        await Promise.all([
            page.waitForResponse(response =>
                response.url().includes('contact-submit') && response.request().method() === 'POST'
            ),
            page.evaluate(() => document.querySelector('form.fm-contact__form').requestSubmit()),
        ]);
        await page.waitForLoadState('load');
        await page.waitForTimeout(500);
        const alert = await page.locator('.alert').first().innerText().catch(() => '');
        check('empty contact form shows validation errors', /required/i.test(alert), textOf(alert).slice(0, 100));
        await page.close();
    }

    // 7. Admin pages, desktop and mobile, including card-4 honesty.
    await adminSweep(desktop, 'admin', [
        { url: '/admin/dashboard', shot: 'admin-dashboard-desktop.png' },
        { url: '/admin/order/all', stat: true, shot: 'admin-orders-empty-desktop.png' },
        { url: '/admin/products/manage', stat: true, shot: 'admin-products-desktop.png' },
        { url: '/admin/visitors/list', stat: true, shot: 'admin-visitors-desktop.png' },
        { url: '/admin/customer/manage', stat: true },
        { url: '/admin/analytics' },
        { url: '/admin/analytics?from=notadate&to=13/45/2026' },
        { url: '/admin/analytics?from=2026-11-05&to=2026-10-01' },
        { url: '/admin/contact/manage' },
    ], false);
    await adminSweep(mobile, 'mobile-admin', [
        { url: '/admin/dashboard', shot: 'admin-dashboard-mobile.png' },
        { url: '/admin/order/all', stat: true },
        { url: '/admin/products/manage', stat: true, shot: 'admin-products-mobile.png' },
        { url: '/admin/visitors/list', stat: true, shot: 'admin-visitors-mobile.png' },
    ], true);

    // 8. Resource budgets (local, uncompressed; debugbar included).
    {
        const page = await newPage(desktop);
        await page.goto(BASE + '/', { waitUntil: 'load' });
        await page.waitForTimeout(800);
        const home = await measure(page);
        check('home page stays under 1.5 MB', home.total < 1.5 * 1024 * 1024, `html=${Math.round(home.html / 1024)}KB total=${Math.round(home.total / 1024)}KB requests=${home.count}`);
        fs.writeFileSync(path.join(OUT, 'resources-home.json'), JSON.stringify(home, null, 2));
        await page.close();
    }
    {
        const page = await loginAdmin(desktop);
        await page.goto(BASE + '/admin/products/manage', { waitUntil: 'load' });
        await page.waitForTimeout(1800);
        const admin = await measure(page);
        check('admin list page stays under 9 MB', admin.total < 9 * 1024 * 1024, `html=${Math.round(admin.html / 1024)}KB total=${Math.round(admin.total / 1024)}KB requests=${admin.count}`);
        fs.writeFileSync(path.join(OUT, 'resources-admin.json'), JSON.stringify(admin, null, 2));
        await page.close();
    }

    await desktop.close();
    await mobile.close();
    await browser.close();

    const failed = results.filter(result => !result.ok);
    fs.writeFileSync(path.join(OUT, 'result.json'), JSON.stringify({ failed: failed.length, results }, null, 2));
    console.log(`RESULT checks=${results.length} failed=${failed.length}`);
    if (failed.length === 0) {
        console.log('AUDIT-PASS desktop=yes mobile=yes errors=handled mock=0');
        process.exit(0);
    }
    for (const failure of failed) {
        console.log('  FAILED:', failure.name, failure.detail);
    }
    process.exit(1);
})().catch(error => {
    console.error('AUDIT-FAIL', error);
    process.exit(1);
});
