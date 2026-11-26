/*
 * Live browser verification: admin order -> courier -> courier risk.
 * Contract: gc-mwh0ju9r
 *
 * The user's six questions, answered against the REAL screen and the REAL
 * courier API - no fixture, no mock:
 *
 *   1. Does /admin/order/all render BOTH courier buttons (SteadFast + Pathao)?
 *   2. Is the courier button actually wired (does clicking it react)?
 *   3. Can an order be created from the admin POS form?
 *   4. Does clicking "Send to courier" bring up the send form, with the
 *      recipient, the COD amount and the endpoint?
 *   5. Does one click really put the order into the courier API, and what does
 *      the courier answer?
 *   6. Does the courier API risk check run for that phone number, and what does
 *      it show?
 *
 * Everything is captured: screenshots in work/courier-flow/ and a machine
 * readable evidence.json that tools/verify/order-courier-flow.php asserts on.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const OUT = process.env.OUT_DIR || path.resolve(__dirname, '..', '..', 'work', 'courier-flow');
const EMAIL = process.env.ADMIN_EMAIL || 'admin@gmail.com';
const PASSWORD = process.env.ADMIN_PASSWORD || '105585';

const CUSTOMER = process.env.CUSTOMER_NAME || 'Jarin Courier Flow Check';
/*
 * A FRESH number on every run, on purpose.
 *
 * CourierFraudService::checkPhone() reuses a saved check for 12 hours, so a
 * number that was looked up once would answer from the cache and the courier
 * API would never be called again. A new number each run makes the risk check
 * a live call every time.
 */
const PHONE = process.env.CUSTOMER_PHONE || ('019' + String(Date.now()).slice(-8));
const ADDRESS = process.env.CUSTOMER_ADDRESS || 'House 42, Road 11, Banani, Dhaka 1213';

fs.mkdirSync(OUT, { recursive: true });

const evidence = {
    started_at: new Date().toISOString(),
    base_url: BASE,
    customer: { name: CUSTOMER, phone: PHONE, address: ADDRESS },
    steps: [],
    console_errors: [],
    bad_responses: [],
};

function step(name, ok, detail) {
    evidence.steps.push({ name, ok: Boolean(ok), detail: detail || '' });
    console.log(`${ok ? 'PASS' : 'FAIL'} ${name}${detail ? ' | ' + detail : ''}`);
}

function clean(text) {
    return String(text || '').replace(/\s+/g, ' ').trim();
}

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 950 } });
    const page = await context.newPage();

    page.on('console', message => {
        if (message.type() === 'error') {
            evidence.console_errors.push(clean(message.text()).slice(0, 300));
        }
    });
    page.on('response', response => {
        const url = response.url();
        if (!url.startsWith(BASE)) return;
        if (response.status() >= 400) {
            evidence.bad_responses.push(`${response.status()} ${response.request().method()} ${url.replace(BASE, '')}`);
        }
    });

    // ---------------------------------------------------------------- 1. LOGIN
    try {
        await page.goto(`${BASE}/admin/login`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.fill('input[name="email"]', EMAIL);
        await page.fill('input[name="password"]', PASSWORD);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {}),
            page.click('button[type="submit"]'),
        ]);
        await page.waitForTimeout(1200);
        const loggedIn = !page.url().includes('/login') && page.url().includes('/admin');
        step('admin login', loggedIn, page.url());
        evidence.login = { ok: loggedIn, url: page.url() };
    } catch (error) {
        step('admin login', false, error.message.slice(0, 200));
        evidence.login = { ok: false, error: error.message.slice(0, 200) };
    }

    // ------------------------------------------------- 2. BOTH COURIER BUTTONS
    try {
        await page.goto(`${BASE}/admin/order/all`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForSelector('.multi_order_courier', { timeout: 20000 });
        await page.waitForTimeout(1500);

        const buttons = await page.evaluate(() => {
            const read = selector => {
                const el = document.querySelector(selector);
                if (!el) return null;
                const box = el.getBoundingClientRect();
                return {
                    text: (el.textContent || '').replace(/\s+/g, ' ').trim(),
                    visible: !!(box.width && box.height),
                    title: el.getAttribute('title') || '',
                    href: el.getAttribute('href') || '',
                };
            };
            return {
                steadfast: read('.multi_order_courier'),
                pathao: read('.individual-courier-btn'),
                perRowSendLinks: document.querySelectorAll('a[href*="/courier/steadfast/"]').length,
            };
        });

        evidence.order_page_buttons = buttons;
        step('order page renders the SteadFast button', Boolean(buttons.steadfast && buttons.steadfast.visible),
            buttons.steadfast ? buttons.steadfast.text : 'missing');
        step('order page renders the Pathao button', Boolean(buttons.pathao && buttons.pathao.visible),
            buttons.pathao ? buttons.pathao.text : 'missing');
        await page.screenshot({ path: path.join(OUT, '01-order-page.png'), fullPage: false });
    } catch (error) {
        step('order page renders both courier buttons', false, error.message.slice(0, 200));
        evidence.order_page_buttons = { error: error.message.slice(0, 200) };
    }

    // ------------------------------------- 3. THE BUTTON IS WIRED (no order yet)
    try {
        await page.click('.multi_order_courier');
        await page.waitForTimeout(1200);
        const toast = await page.evaluate(() => {
            const node = document.querySelector('.toast, .toast-message, .toastr, [id*="toast"]');
            return node ? (node.textContent || '').replace(/\s+/g, ' ').trim() : '';
        });
        evidence.button_click_no_selection = toast;
        step('clicking SteadFast with no order selected reacts', toast.length > 0, toast || 'no toast found');
        await page.screenshot({ path: path.join(OUT, '02-button-no-selection.png'), fullPage: false });
    } catch (error) {
        step('clicking SteadFast with no order selected reacts', false, error.message.slice(0, 200));
        evidence.button_click_no_selection = { error: error.message.slice(0, 200) };
    }

    // ------------------------------------------------------- 4. CREATE AN ORDER
    let orderId = null;
    let invoiceId = null;
    try {
        await page.goto(`${BASE}/admin/order/create`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForSelector('#cart_add', { timeout: 20000 });

        // A product has to be in the POS cart before the order can be saved.
        const options = await page.$$eval('#cart_add option', nodes =>
            nodes.filter(node => node.value).map(node => ({ value: node.value, label: node.textContent.trim() })));
        if (!options.length) throw new Error('no product in the POS list');

        await page.selectOption('#cart_add', options[0].value);
        await page.waitForTimeout(2000);
        await page.waitForSelector('#cartTable tr', { timeout: 20000 });

        // Step 2 - the customer.
        await page.click('.btn-next');
        await page.waitForTimeout(600);
        await page.fill('#name', CUSTOMER);
        await page.fill('#phone', PHONE);
        await page.fill('#address', ADDRESS);
        const areas = await page.$$eval('#area option', nodes =>
            nodes.filter(node => node.value).map(node => node.value));
        if (!areas.length) throw new Error('no shipping area configured');
        await page.selectOption('#area', areas[0]);
        await page.waitForTimeout(400);

        // Step 3 - payment, then submit.
        await page.click('.btn-next');
        await page.waitForTimeout(600);
        await page.screenshot({ path: path.join(OUT, '03-pos-form.png'), fullPage: false });
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => {}),
            page.click('form.pos_form button[type="submit"]'),
        ]);
        await page.waitForTimeout(1500);
        evidence.order_create_url = page.url();

        // Find the new order back on the list.
        await page.goto(`${BASE}/admin/order/all`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForSelector('#orderTable tbody tr', { timeout: 25000 });
        await page.waitForTimeout(1500);

        const found = await page.evaluate(name => {
            const rows = Array.from(document.querySelectorAll('#orderTable tbody tr'));
            const row = rows.find(tr => (tr.textContent || '').includes(name));
            if (!row) return null;
            const invoiceLink = row.querySelector('a[href*="/order/invoice/"]');
            const courierLink = row.querySelector('a[href*="/courier/steadfast/"]');
            const checkbox = row.querySelector('input.checkbox');
            return {
                invoice: invoiceLink ? (invoiceLink.textContent || '').replace('#', '').trim() : '',
                courierHref: courierLink ? courierLink.getAttribute('href') : '',
                orderId: courierLink
                    ? (courierLink.getAttribute('href').match(/order\/(\d+)\/courier/) || [])[1] || null
                    : (checkbox ? checkbox.value : null),
                checkboxValue: checkbox ? checkbox.value : '',
                riskChip: (() => {
                    const badge = row.querySelector('td:nth-child(5) .badge');
                    return badge ? (badge.textContent || '').replace(/\s+/g, ' ').trim() : '';
                })(),
                riskProvider: (() => {
                    const badges = row.querySelectorAll('td:nth-child(5) .badge');
                    return badges.length > 1 ? (badges[1].textContent || '').replace(/\s+/g, ' ').trim() : '';
                })(),
                rowText: (row.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 200),
            };
        }, CUSTOMER);

        evidence.created_order = found;
        orderId = found && found.orderId ? found.orderId : null;
        invoiceId = found ? found.invoice : null;

        // The checkbox is what the courier buttons read.
        if (orderId) {
            const box = await page.$(`#orderTable tbody input.checkbox[value="${orderId}"]`);
            if (box) {
                await box.check();
                await page.waitForTimeout(300);
                evidence.created_order_checkbox_checked = await box.isChecked();
            }
        }

        step('order created from the admin POS form', Boolean(orderId),
            found ? `order #${orderId} invoice ${found.invoice} | ${found.rowText}` : 'new order row not found');
        await page.screenshot({ path: path.join(OUT, '04-order-created.png'), fullPage: false });
    } catch (error) {
        step('order created from the admin POS form', false, error.message.slice(0, 250));
        evidence.created_order = { error: error.message.slice(0, 250) };
    }

    // ------------------------------- 5. ONE CLICK SENDS TO THE COURIER (bulk)
    if (orderId) {
        try {
            const [response] = await Promise.all([
                page.waitForResponse(r => r.url().includes('/bulk-courier/'), { timeout: 60000 }).catch(() => null),
                page.click('.multi_order_courier'),
            ]);
            await page.waitForTimeout(1500);
            let body = null;
            let status = null;
            if (response) {
                status = response.status();
                // The body can only be read once.
                const raw = await response.text().catch(() => '');
                try { body = raw ? JSON.parse(raw) : { raw: '' }; } catch (e) { body = { raw: raw.slice(0, 400) }; }
            }
            const toast = await page.evaluate(() => {
                const node = document.querySelector('.toast, .toast-message, .toastr, [id*="toast"]');
                return node ? (node.textContent || '').replace(/\s+/g, ' ').trim() : '';
            });
            evidence.bulk_send = { http_status: status, response: body, toast };
            step('one click on SteadFast posts to the courier endpoint', Boolean(response),
                status ? `HTTP ${status} ${JSON.stringify(body).slice(0, 240)}` : 'no request captured');
            await page.screenshot({ path: path.join(OUT, '05-bulk-send.png'), fullPage: false });
        } catch (error) {
            step('one click on SteadFast posts to the courier endpoint', false, error.message.slice(0, 200));
            evidence.bulk_send = { error: error.message.slice(0, 200) };
        }
    }

    // ---------------------------- 5b. THE PATHAO BUTTON (the second courier)
    if (orderId) {
        try {
            await page.goto(`${BASE}/admin/order/all`, { waitUntil: 'domcontentloaded', timeout: 45000 });
            await page.waitForSelector('#orderTable tbody tr', { timeout: 25000 });
            await page.waitForTimeout(1000);

            const box = await page.$(`#orderTable tbody input.checkbox[value="${orderId}"]`);
            if (box) {
                await box.check();
                await page.waitForTimeout(300);
            }

            // The Pathao token request can take 30s to time out on this host.
            const [response] = await Promise.all([
                page.waitForResponse(r => r.url().includes('/order-pathao-auto'), { timeout: 180000 }).catch(() => null),
                page.click('.individual-courier-btn'),
            ]);
            await page.waitForTimeout(2000);

            let body = null;
            let status = null;
            if (response) {
                status = response.status();
                // The body can only be read once.
                const raw = await response.text().catch(() => '');
                try { body = raw ? JSON.parse(raw) : { raw: '' }; } catch (e) { body = { raw: raw.slice(0, 400) }; }
            }
            const toast = await page.evaluate(() => {
                const node = document.querySelector('.toast, .toast-message, .toastr, [id*="toast"]');
                return node ? (node.textContent || '').replace(/\s+/g, ' ').trim() : '';
            });

            evidence.pathao_send = { http_status: status, response: body, toast };
            step('the Pathao button posts to the Pathao endpoint', Boolean(response),
                status ? `HTTP ${status} ${JSON.stringify(body).slice(0, 260)}` : 'no request captured');
            await page.screenshot({ path: path.join(OUT, '09-pathao-send.png'), fullPage: false });
        } catch (error) {
            step('the Pathao button posts to the Pathao endpoint', false, error.message.slice(0, 200));
            evidence.pathao_send = { error: error.message.slice(0, 200) };
        }
    }

    // ---------------------------- 6. THE PER-ORDER COURIER SCREEN (the form)
    if (orderId) {
        try {
            await page.goto(`${BASE}/admin/order/all`, { waitUntil: 'domcontentloaded', timeout: 45000 });
            await page.waitForSelector('#orderTable tbody tr', { timeout: 25000 });
            await page.waitForTimeout(1200);

            const link = await page.$(`#orderTable tbody a[href*="/order/${orderId}/courier/"]`);
            if (!link) throw new Error('the per-order courier link is gone (a consignment may already exist)');

            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {}),
                link.click(),
            ]);
            await page.waitForTimeout(1200);

            const form = await page.evaluate(() => {
                const el = document.querySelector('#fmCourierForm');
                if (!el) return null;
                const tidy = value => (value || '').replace(/\s+/g, ' ').trim();
                const rows = Array.from(document.querySelectorAll('.card-body table tr')).map(tr => {
                    const th = tr.querySelector('th');
                    const td = tr.querySelector('td');
                    return th && td ? `${tidy(th.textContent)}: ${tidy(td.textContent)}` : '';
                }).filter(Boolean);
                const submit = el.querySelector('[data-confirm-send]');
                const box = document.querySelector('#courierConfirm');
                return {
                    hasForm: true,
                    previewRows: rows,
                    endpoint: (rows.find(r => /Endpoint/i.test(r)) || ''),
                    recipient: (rows.find(r => /Recipient/i.test(r)) || ''),
                    phone: (rows.find(r => /^Phone/i.test(r)) || ''),
                    cod: (rows.find(r => /Cash to collect/i.test(r)) || ''),
                    address: (rows.find(r => /Address/i.test(r)) || ''),
                    sendButtonText: submit ? tidy(submit.textContent) : '',
                    sendButtonDisabled: submit ? !!submit.disabled : null,
                    confirmCheckboxPresent: !!box,
                    noteField: !!document.querySelector('#courierNote'),
                };
            });
            evidence.courier_form = form;
            step('the courier button opens the send form', Boolean(form && form.hasForm),
                form ? `send button: "${form.sendButtonText}" (disabled until the box is ticked: ${form.sendButtonDisabled})` : 'no #fmCourierForm');
            step('the form shows recipient, phone, address, COD and endpoint', Boolean(form && form.endpoint && form.phone && form.cod),
                form ? [form.recipient, form.phone, form.cod, form.endpoint].join(' | ').slice(0, 260) : '');
            await page.screenshot({ path: path.join(OUT, '06-courier-form.png'), fullPage: true });
        } catch (error) {
            step('the courier button opens the send form', false, error.message.slice(0, 200));
            evidence.courier_form = { error: error.message.slice(0, 200) };
        }
    }

    // --------------------------------------- 7. SUBMIT THE FORM TO THE COURIER
    if (orderId) {
        try {
            await page.goto(`${BASE}/admin/order/${orderId}/courier/steadfast`, { waitUntil: 'domcontentloaded', timeout: 45000 });
            const hasForm = await page.$('#fmCourierForm');
            if (!hasForm) throw new Error('the send form is not on the screen');

            await page.check('#courierConfirm');
            await page.waitForTimeout(300);
            const enabled = await page.$eval('[data-confirm-send]', el => !el.disabled);

            const [response] = await Promise.all([
                page.waitForResponse(r => r.url().includes(`/order/${orderId}/courier/steadfast`) && r.request().method() === 'POST',
                    { timeout: 60000 }).catch(() => null),
                page.click('[data-confirm-send]'),
            ]);
            await page.waitForTimeout(2500);

            let status = null;
            let bodyText = '';
            if (response) {
                status = response.status();
                bodyText = (await response.text().catch(() => '')).replace(/\s+/g, ' ').slice(0, 300);
            }
            const alert = await page.evaluate(() => {
                const node = document.querySelector('.alert-success, .alert-danger');
                return node ? (node.textContent || '').replace(/\s+/g, ' ').trim() : '';
            });
            const consignment = await page.evaluate(() => {
                const text = document.body.innerText;
                const match = text.match(/Consignment\s*([A-Za-z0-9\-]+)/i);
                return match ? match[1] : '';
            });

            evidence.form_send = {
                submit_enabled_after_tick: enabled,
                http_status: status,
                response_preview: bodyText,
                page_alert: alert,
                consignment_id: consignment,
            };
            step('the send form posts to the courier API', Boolean(response),
                status ? `HTTP ${status}` : 'no POST captured');
            step('the courier answered (success or a real refusal)', Boolean(alert || consignment),
                (alert || consignment || 'no message on screen').slice(0, 240));
            await page.screenshot({ path: path.join(OUT, '07-send-result.png'), fullPage: true });
        } catch (error) {
            step('the send form posts to the courier API', false, error.message.slice(0, 200));
            evidence.form_send = { error: error.message.slice(0, 200) };
        }
    }

    // ------------------------------------------- 8. COURIER API RISK CHECK
    try {
        await page.goto(`${BASE}/admin/courier/fraud-checker`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForSelector('#fraudPhone', { timeout: 20000 });
        await page.fill('#fraudPhone', PHONE);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }).catch(() => {}),
            page.click('button[formaction*="fraud-checker/check"]'),
        ]);
        await page.waitForTimeout(2000);

        const result = await page.evaluate(() => {
            const box = document.querySelector('.alert-light.border');
            const badge = document.querySelector('.alert-light .badge');
            return {
                summary: box ? (box.textContent || '').replace(/\s+/g, ' ').trim() : '',
                riskBadge: badge ? (badge.textContent || '').trim() : '',
                url: location.href,
            };
        });
        evidence.risk_check = result;
        step('the courier risk check runs and shows a result', Boolean(result.riskBadge || result.summary),
            result.summary.slice(0, 260));
        await page.screenshot({ path: path.join(OUT, '08-risk-check.png'), fullPage: false });
    } catch (error) {
        step('the courier risk check runs and shows a result', false, error.message.slice(0, 200));
        evidence.risk_check = { error: error.message.slice(0, 200) };
    }

    evidence.finished_at = new Date().toISOString();
    evidence.order_id = orderId;
    evidence.invoice_id = invoiceId;
    fs.writeFileSync(path.join(OUT, 'evidence.json'), JSON.stringify(evidence, null, 2));

    const failed = evidence.steps.filter(s => !s.ok);
    console.log(`\nSTEPS ${evidence.steps.length} | FAILED ${failed.length}`);
    console.log(`EVIDENCE ${path.join(OUT, 'evidence.json')}`);

    await browser.close();
    process.exit(failed.length ? 1 : 0);
})().catch(error => {
    console.error('RUNNER CRASHED: ' + error.stack);
    process.exit(2);
});
