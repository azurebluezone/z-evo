/**
 * Contract gc-mwx5vjl7 - real browser end-to-end over CDP.
 *
 * Uses OpenCode's Chromium (the Playwright-bundled build the CDP server
 * discovers), never Firefox. Drives the actual admin and storefront screens:
 *
 *   1. admin login
 *   2. product create with two gallery uploads
 *   3. two variants, each with its own photo, price and opening stock
 *   4. storefront: pick each variant, read photo, price, stock and ceiling
 *   5. buy now with a quantity of two
 *   6. checkout placed as a guest (Cash On Delivery)
 *   7. admin order list: change the status to Delivered (the real dropdown)
 *
 * Writes a JSON manifest of everything the browser measured and prints
 * CDP_E2E_MANIFEST=<path> as its last line. The DB verifier cross-checks it.
 */

import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const BASE = process.env.APP_URL || 'http://127.0.0.1:8000';
const STAMP = Date.now();
const MARKER = 'E2E' + STAMP;
const PRODUCT_NAME = `CDP E2E ${MARKER}`;
const ARTIFACTS = path.join(ROOT, 'tools/verify/.artifacts', MARKER);

fs.mkdirSync(ARTIFACTS, { recursive: true });

/* ------------------------------------------------------------------ *
 * Tiny PNG writer, so the fixtures are real decodable images and each
 * one is a different colour (the admin validation only accepts images).
 * ---------------------------------------------------------------- */
let crcTable = null;

function crc32(buffer) {
    if (!crcTable) {
        crcTable = new Int32Array(256);
        for (let n = 0; n < 256; n++) {
            let c = n;
            for (let k = 0; k < 8; k++) {
                c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
            }
            crcTable[n] = c;
        }
    }

    let crc = 0xffffffff;
    for (let i = 0; i < buffer.length; i++) {
        crc = crcTable[(crc ^ buffer[i]) & 0xff] ^ (crc >>> 8);
    }

    return (crc ^ 0xffffffff) >>> 0;
}

function pngChunk(type, data) {
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length);
    const body = Buffer.concat([Buffer.from(type, 'ascii'), data]);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(body));
    return Buffer.concat([length, body, crc]);
}

function makePng(width, height, [r, g, b]) {
    const stride = width * 4 + 1;
    const raw = Buffer.alloc(stride * height);

    for (let y = 0; y < height; y++) {
        const row = y * stride;
        raw[row] = 0;
        for (let x = 0; x < width; x++) {
            const px = row + 1 + x * 4;
            raw[px] = r;
            raw[px + 1] = g;
            raw[px + 2] = b;
            raw[px + 3] = 255;
        }
    }

    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(width, 0);
    ihdr.writeUInt32BE(height, 4);
    ihdr[8] = 8;
    ihdr[9] = 6;

    return Buffer.concat([
        Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
        pngChunk('IHDR', ihdr),
        pngChunk('IDAT', zlib.deflateSync(raw)),
        pngChunk('IEND', Buffer.alloc(0)),
    ]);
}

function writeFixture(name, colour) {
    const file = path.join(ARTIFACTS, name);
    fs.writeFileSync(file, makePng(24, 24, colour));
    return file;
}

const galleryOne = writeFixture('gallery-1.png', [30, 120, 200]);
const galleryTwo = writeFixture('gallery-2.png', [210, 90, 40]);
const variantPhotoA = writeFixture('variant-sky.png', [74, 144, 217]);
const variantPhotoB = writeFixture('variant-gold.png', [215, 179, 42]);

const PHONE = '019' + String(STAMP).slice(-8);
const ORDER_QTY = 2;

const manifest = {
    marker: MARKER,
    baseUrl: BASE,
    product: { id: null, name: PRODUCT_NAME, galleryUploads: ['gallery-1.png', 'gallery-2.png'], baseImage: null },
    variants: {
        a: {
            sku: MARKER + '-SKY',
            title: 'Sky Blue',
            colour: 'Sky Blue',
            attributeValueId: null,
            priceOverride: 1120,
            openingStock: 3,
            dom: {},
        },
        b: {
            sku: MARKER + '-GOLD',
            title: 'Original',
            colour: 'Original',
            attributeValueId: null,
            priceOverride: 1010,
            openingStock: 6,
            dom: {},
        },
    },
    cart: { qty: ORDER_QTY, visibleQty: null, mirrorQty: null },
    order: { id: null, qty: ORDER_QTY, phone: PHONE, name: 'CDP Tester' },
    admin: { statusLabel: null, finalUrl: null },
};

function fail(message) {
    throw new Error(message);
}

function log(step, detail) {
    console.log(`[step] ${step}${detail ? ': ' + detail : ''}`);
}

async function findProductId(name) {
    const raw = execFileSync('php', [
        path.join(ROOT, 'tools/verify/cdp_variant_e2e_check.php'),
        'find-product',
        name,
    ], { cwd: ROOT, encoding: 'utf8' });

    const parsed = JSON.parse(raw.trim());
    if (!parsed.id) {
        fail('find-product returned no id');
    }

    return parsed.id;
}

async function selectFirstRealOption(page, selector) {
    const value = await page.$eval(selector, (el) => {
        const option = Array.from(el.options).find((o) => o.value && o.value !== '0');
        return option ? option.value : '';
    });

    if (!value) {
        fail(`no selectable option in ${selector}`);
    }

    await page.selectOption(selector, value);
    return value;
}

function pageErrorText(page) {
    return page.evaluate(() => {
        const notes = Array.from(document.querySelectorAll('.invalid-feedback, .alert, .toast-message'))
            .map((el) => el.textContent.trim())
            .filter(Boolean);

        return notes.slice(0, 8).join(' | ');
    }).catch(() => '');
}

async function submitAndWait(page, clickSelector, urlPattern, label) {
    try {
        await Promise.all([
            page.waitForURL(urlPattern, { timeout: 30000 }),
            page.click(clickSelector),
        ]);
    } catch (error) {
        const detail = await pageErrorText(page);
        const afterState = await page.evaluate(() => Array.from(document.querySelectorAll('input[type="file"]'))
            .map((el) => [el.name, el.files.length])).catch(() => []);
        console.log('[debug] file inputs after failure', JSON.stringify(afterState));
        await page.screenshot({ path: path.join(ARTIFACTS, `${label}-failed.png`), fullPage: true }).catch(() => {});
        fail(`${label} did not navigate (${page.url()}) ${detail}`.trim());
    }
}

async function pickVariant(page, colour, expectedLeft) {
    const pill = page.locator('.fm-attr-pill', { hasText: colour }).first();
    await pill.waitFor({ state: 'visible', timeout: 20000 });
    const valueId = await pill.getAttribute('data-attr-id');

    const [response] = await Promise.all([
        page.waitForResponse(
            (res) => res.url().includes('/variant-detail') && res.request().method() === 'GET',
            { timeout: 20000 }
        ),
        pill.click(),
    ]);

    await page.waitForFunction(
        (left) => {
            const el = document.getElementById('fmDetailStockText');
            return el && el.textContent.includes('(' + left + ' left)');
        },
        expectedLeft,
        { timeout: 15000 }
    );

    const body = await response.json().catch(() => null);
    if (!body || !body.found) {
        fail(`variant-detail did not resolve the ${colour} pick`);
    }

    return {
        valueId,
        mainImage: await page.getAttribute('#fmDetailMainPhoto', 'src'),
        priceText: (await page.textContent('#fmDetailPrice')).trim(),
        stockText: (await page.textContent('#fmDetailStockText')).trim(),
        qtyMax: await page.getAttribute('#fmDetailQty', 'max'),
        qtyDataMax: await page.getAttribute('#fmDetailQty', 'data-max-stock'),
    };
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
const page = await context.newPage();
page.setDefaultTimeout(20000);

page.on('pageerror', (error) => console.error('[browser-error]', error.message));

try {
    /* ---------------------------------------------------------------- *
     * 1. Admin login
     * -------------------------------------------------------------- */
    log('admin login');
    await page.goto(BASE + '/admin/login', { waitUntil: 'domcontentloaded' });
    await page.fill('#email', 'admin@gmail.com');
    await page.fill('#password', '105585');
    await Promise.all([
        page.waitForURL((url) => !url.toString().includes('/admin/login'), { timeout: 25000 }),
        page.click('button[type="submit"]'),
    ]);
    if (!page.url().includes('/admin')) {
        fail('admin login did not reach the admin panel');
    }
    await page.screenshot({ path: path.join(ARTIFACTS, '01-admin-dashboard.png'), fullPage: false });

    /* ---------------------------------------------------------------- *
     * 2. Product create with two gallery uploads
     * -------------------------------------------------------------- */
    log('product create');
    await page.goto(BASE + '/admin/products/create', { waitUntil: 'domcontentloaded' });
    await page.fill('#name', PRODUCT_NAME);
    await selectFirstRealOption(page, '#category_id');
    await page.fill('#new_price', '1000');
    await page.fill('#purchase_price', '700');
    if (await page.locator('#stock').count()) {
        await page.fill('#stock', '0');
    }
    await page.evaluate(() => {
        const box = document.querySelector('#description');
        if (box) {
            box.value = 'Fixture product created by the CDP end-to-end run.';
        }
    });
    // The form has TWO image[] inputs: a required Main Image and a Gallery
    // Images input with `multiple`. A browser cannot submit the same field
    // twice, so the main file goes to the first input and the second photo to
    // the gallery input.
    await page.setInputFiles('input[name="image[]"]:not([multiple])', galleryOne);
    await page.setInputFiles('input[name="image[]"][multiple]', [galleryTwo]);
    // Chromium updates the internal form data a tick after the file is set;
    // submitting in the same tick has been seen to post the form without it.
    await page.waitForTimeout(150);

    await submitAndWait(page, 'button[form="productForm"]', /\/admin\/products\/manage/, 'product-create');

    manifest.product.id = await findProductId(PRODUCT_NAME);
    if (!manifest.product.id) {
        fail('created product id was not found in the database');
    }
    log('product created', `id=${manifest.product.id}`);

    /* ---------------------------------------------------------------- *
     * 3. Two variants with their own photo, price and opening stock
     * -------------------------------------------------------------- */
    async function createVariant(spec) {
        log('variant create', spec.sku);
        await page.goto(BASE + '/admin/variants/create', { waitUntil: 'domcontentloaded' });
        await page.selectOption('select[name="product_id"]', String(manifest.product.id));
        await page.fill('#sku', spec.sku);
        await page.fill('#title', spec.title);
        await page.fill('#price_override', String(spec.priceOverride));
        await page.fill('#initial_stock', String(spec.openingStock));
        await page.setInputFiles('#image', spec.photo);

        const attributeSelector = 'select[name="attribute_ids[color]"]';
        if (!(await page.locator(attributeSelector).count())) {
            fail('the colour attribute select is missing on the variant create screen');
        }

        const colourValue = await page.$eval(attributeSelector, (el, wanted) => {
            const option = Array.from(el.options).find((o) => o.textContent.includes(wanted));
            return option ? option.value : '';
        }, spec.colour);

        if (!colourValue) {
            fail(`the variant screen has no "${spec.colour}" option`);
        }

        await page.selectOption(attributeSelector, colourValue);
        await submitAndWait(page, 'button[form="adminForm"]', /\/admin\/variants(\?|$)/, 'variant-create');
        return colourValue;
    }

    manifest.variants.a.attributeValueId = await createVariant({
        sku: manifest.variants.a.sku,
        title: manifest.variants.a.title,
        colour: manifest.variants.a.colour,
        priceOverride: manifest.variants.a.priceOverride,
        openingStock: manifest.variants.a.openingStock,
        photo: variantPhotoA,
    });

    manifest.variants.b.attributeValueId = await createVariant({
        sku: manifest.variants.b.sku,
        title: manifest.variants.b.title,
        colour: manifest.variants.b.colour,
        priceOverride: manifest.variants.b.priceOverride,
        openingStock: manifest.variants.b.openingStock,
        photo: variantPhotoB,
    });

    /* ---------------------------------------------------------------- *
     * 4. Storefront: every pick shows its own photo, price and stock
     * -------------------------------------------------------------- */
    log('storefront product page');
    await page.goto(BASE + '/product/' + manifest.product.id, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#fmDetailMainPhoto', { timeout: 20000 });
    await page.waitForFunction(() => window.FM_DETAIL_CONFIG && window.FmQtyCeiling, null, { timeout: 20000 });

    manifest.product.baseImage = await page.getAttribute('#fmDetailMainPhoto', 'src');

    const skyDom = await pickVariant(page, manifest.variants.a.colour, manifest.variants.a.openingStock);
    await page.screenshot({ path: path.join(ARTIFACTS, '02-storefront-sky.png'), fullPage: false });

    const goldDom = await pickVariant(page, manifest.variants.b.colour, manifest.variants.b.openingStock);

    // Pick Sky Blue again for the purchase (clicking the active pill would toggle it off).
    const skyAgain = await pickVariant(page, manifest.variants.a.colour, manifest.variants.a.openingStock);

    manifest.variants.a.attributeValueId = skyAgain.valueId;
    manifest.variants.a.dom = skyDom;
    manifest.variants.b.dom = goldDom;

    /* ---------------------------------------------------------------- *
     * 5. Quantity and buy now
     * -------------------------------------------------------------- */
    log('stepper and buy now');
    await page.click('[data-fm-qty="1"]');
    await page.waitForFunction((qty) => {
        const input = document.getElementById('fmDetailQty');
        return input && input.value === String(qty);
    }, ORDER_QTY, { timeout: 5000 });

    manifest.cart.visibleQty = await page.inputValue('#fmDetailQty');
    manifest.cart.mirrorQty = await page.$$eval('[data-fm-qty-source]', (inputs) =>
        inputs.map((input) => input.value).join(',')
    );

    if (manifest.cart.mirrorQty.split(',').some((value) => value !== String(ORDER_QTY))) {
        fail('the hidden form mirrors did not follow the stepper quantity');
    }

    await page.screenshot({ path: path.join(ARTIFACTS, '03-storefront-selected.png'), fullPage: false });

    await submitAndWait(
        page,
        'form[action$="/cart/store/buy"] button[type="submit"]',
        /\/checkout/,
        'buy-now'
    );

    /* ---------------------------------------------------------------- *
     * 6. Guest checkout
     * -------------------------------------------------------------- */
    log('checkout');
    await page.waitForSelector('#coName', { timeout: 20000 });
    await page.fill('#coName', manifest.order.name);
    await page.fill('#coPhone', manifest.order.phone);
    await page.fill('#coAddress', '12 CDP Road, Dhaka');
    await selectFirstRealOption(page, '#coArea');
    await page.screenshot({ path: path.join(ARTIFACTS, '04-checkout.png'), fullPage: false });

    await submitAndWait(page, 'button[data-fm-submit]', /order-success/, 'place-order');

    const orderMatch = page.url().match(/order-success\/(\d+)/);
    if (!orderMatch) {
        fail('order success url carried no order id');
    }
    manifest.order.id = Number(orderMatch[1]);
    await page.screenshot({ path: path.join(ARTIFACTS, '05-order-success.png'), fullPage: false });
    log('order placed', `id=${manifest.order.id}`);

    /* ---------------------------------------------------------------- *
     * 7. Admin order list: the real status dropdown -> Delivered
     * -------------------------------------------------------------- */
    log('admin status change');
    await page.goto(BASE + '/admin/order/all', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector(`input[name="order_ids[]"][value="${manifest.order.id}"]`, { state: 'attached', timeout: 30000 });

    const rowForm = page
        .locator('form.row-status-form')
        .filter({ has: page.locator(`input[name="order_ids[]"][value="${manifest.order.id}"]`) });
    const statusSelect = rowForm.locator('select.row-status-select');

    const delivered = await statusSelect.evaluate((el) => {
        const option = Array.from(el.options).find((o) => o.textContent.trim().toLowerCase() === 'delivered');
        return option ? { value: option.value, label: option.textContent.trim() } : null;
    });

    if (!delivered) {
        fail('the order list has no Delivered status option');
    }

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
        statusSelect.selectOption(delivered.value),
    ]);

    await page.waitForSelector(`input[name="order_ids[]"][value="${manifest.order.id}"]`, { state: 'attached', timeout: 30000 });
    const selectedLabel = await page
        .locator('form.row-status-form')
        .filter({ has: page.locator(`input[name="order_ids[]"][value="${manifest.order.id}"]`) })
        .locator('select.row-status-select')
        .evaluate((el) => (el.selectedOptions[0] ? el.selectedOptions[0].textContent.trim() : ''));

    manifest.admin.statusLabel = selectedLabel;
    manifest.admin.finalUrl = page.url();
    await page.screenshot({ path: path.join(ARTIFACTS, '06-admin-delivered.png'), fullPage: false });

    if (selectedLabel.toLowerCase() !== 'delivered') {
        fail('the admin list did not keep the Delivered status');
    }

    const manifestPath = path.join(ARTIFACTS, 'manifest.json');
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));

    console.log('CDP_E2E_MANIFEST=' + manifestPath);
    await browser.close();
} catch (error) {
    console.error('CDP_E2E_FAILED:', error.message);
    await page.screenshot({ path: path.join(ARTIFACTS, '99-failure.png'), fullPage: true }).catch(() => {});
    await browser.close().catch(() => {});
    process.exit(1);
}
