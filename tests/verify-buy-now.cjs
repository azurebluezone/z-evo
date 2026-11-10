/*
 * Buy Now verification (contract gc-mw5c1wba).
 *
 * Reads the real rendered storefront: every .fm-product-card must show both
 * "Add to Cart" and "Buy Now", the old "Order Now" label must be gone, and a
 * real Buy Now click must add the item and land on a non-empty checkout.
 *
 * Run through tests/verify-buy-now.sh so NODE_PATH and the artifact path are
 * set. No fixture replaces the application.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const ROOT = path.resolve(__dirname, '..');
const OUT = process.env.OUT_DIR || path.join(ROOT, 'work/audit-shop-admin');
fs.mkdirSync(OUT, { recursive: true });

const results = [];
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail });
    console.log(`${ok ? 'PASS' : 'FAIL'} ${name}${detail ? ' | ' + detail : ''}`);
}

async function readCards(page) {
    return page.evaluate(() => {
        const cards = Array.from(document.querySelectorAll('.fm-product-card'));
        const texts = card => Array.from(card.querySelectorAll('button'))
            .map(button => (button.textContent || '').replace(/\s+/g, ' ').trim());
        return {
            cards: cards.length,
            add: cards.filter(card => texts(card).some(text => /Add to Cart/i.test(text))).length,
            buy: cards.filter(card => texts(card).some(text => /Buy Now/i.test(text))).length,
            oldLabel: cards.filter(card => texts(card).some(text => /Order Now/i.test(text))).length,
            buyForms: cards.filter(card => card.querySelector('form[action*="cart/store/buy"]')).length,
        };
    });
}

async function sweep(ctx, label, totals) {
    const page = await ctx.newPage();
    for (const url of ['/', '/products']) {
        let response = null;
        try {
            response = await page.goto(BASE + url, { waitUntil: 'load', timeout: 45000 });
            await page.waitForSelector('.fm-product-card', { timeout: 20000 });
            await page.waitForTimeout(400);
        } catch (error) {
            check(`${label} ${url} renders cards`, false, error.message.slice(0, 120));
            continue;
        }
        const counts = await readCards(page);
        totals.cards += counts.cards;
        totals.add += counts.add;
        totals.buy += counts.buy;
        const ok = counts.cards > 0
            && counts.add === counts.cards
            && counts.buy === counts.cards
            && counts.buyForms === counts.cards
            && counts.oldLabel === 0;
        check(
            `${label} ${url} card actions`,
            ok && response.status() === 200,
            `status=${response.status()} cards=${counts.cards} add=${counts.add} buy=${counts.buy} buyForms=${counts.buyForms} orderNow=${counts.oldLabel}`
        );
    }
    await page.close();
}

(async () => {
    console.log('== Buy Now verification ==');
    console.log('base:', BASE);

    const browser = await chromium.launch({ headless: true });
    const desktop = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const mobile = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });

    const totals = { cards: 0, add: 0, buy: 0 };
    await sweep(desktop, 'desktop', totals);
    await sweep(mobile, 'mobile', totals);

    // Real click: buy the first available product from the listing.
    const page = await desktop.newPage();
    await page.goto(BASE + '/products', { waitUntil: 'load', timeout: 45000 });
    await page.waitForSelector('.fm-product-card', { timeout: 20000 });
    const handle = await page.evaluateHandle(() => {
        const card = Array.from(document.querySelectorAll('.fm-product-card'))
            .find(item => Array.from(item.querySelectorAll('button'))
                .some(button => /Buy Now/i.test(button.textContent || '') && !button.disabled));
        if (!card) return null;
        return Array.from(card.querySelectorAll('button'))
            .find(item => /Buy Now/i.test(item.textContent || '') && !item.disabled) || null;
    });
    const target = handle ? handle.asElement() : null;
    let clicked = false;
    let productName = '';
    if (target) {
        productName = await page.evaluate(el => (el.closest('.fm-product-card')?.querySelector('.fm-product-title')?.textContent || '').trim(), target);
        await Promise.all([
            page.waitForURL(url => String(url).includes('/customer/checkout'), { timeout: 30000 }),
            target.click(),
        ]);
        await page.waitForLoadState('load');
        await page.waitForTimeout(700);
        clicked = true;
    }
    const checkout = await page.evaluate(() => ({
        url: location.pathname,
        lines: document.querySelectorAll('.fm-co__line').length,
        empty: /Your cart is empty/i.test(document.body ? document.body.innerText : ''),
        lineText: (document.querySelector('.fm-co__line-name')?.textContent || '').trim(),
    })).catch(() => ({ url: '', lines: 0, empty: true, lineText: '' }));
    const productStem = productName.slice(0, 12);
    check(
        'Buy Now click lands on a non-empty checkout',
        clicked && checkout.url.includes('/customer/checkout') && checkout.lines > 0 && !checkout.empty,
        `product=${productName.slice(0, 40)} url=${checkout.url} lines=${checkout.lines} empty=${checkout.empty} first=${checkout.lineText.slice(0, 30)}`
    );
    check(
        'Buy Now checkout line is the clicked product',
        productStem !== '' && checkout.lineText.includes(productStem),
        `expected~${productStem} got=${checkout.lineText.slice(0, 40)}`
    );
    await page.close();

    // Detail page wording.
    const detail = await desktop.newPage();
    await detail.goto(BASE + '/product/artisan-raw-honey', { waitUntil: 'load', timeout: 45000 });
    const detailState = await detail.evaluate(() => {
        const buttons = Array.from(document.querySelectorAll('button'));
        const buy = buttons.find(button => /Buy Now/i.test(button.textContent || ''));
        return {
            buy: Boolean(buy),
            action: buy ? (buy.closest('form')?.getAttribute('action') || '') : '',
            text: buy ? (buy.textContent || '').replace(/\s+/g, ' ').trim() : '',
        };
    });
    check(
        'product detail shows Buy Now and posts to the buy route',
        detailState.buy && detailState.action.includes('cart/store/buy'),
        `text=${detailState.text} action=${detailState.action}`
    );
    await detail.close();

    await desktop.close();
    await mobile.close();
    await browser.close();

    const failed = results.filter(result => !result.ok);
    fs.writeFileSync(path.join(OUT, 'buy-now.json'), JSON.stringify({ failed: failed.length, results }, null, 2));
    console.log(`RESULT checks=${results.length} failed=${failed.length}`);
    if (failed.length === 0) {
        console.log(`BUY-NOW-PASS cards=${totals.cards} add=${totals.add} buy=${totals.buy} checkout=yes detail=yes`);
        process.exit(0);
    }
    for (const failure of failed) {
        console.log('  FAILED:', failure.name, failure.detail);
    }
    console.log('BUY-NOW-FAIL');
    process.exit(1);
})().catch(error => {
    console.error('BUY-NOW-FAIL', error);
    process.exit(1);
});
