/**
 * Real-browser check for the admin "Landing Content" panel.
 *
 * Renders the real Blade partial through tools/render_campaign_content_panel.php,
 * loads it in Chromium, switches through every active template and asserts:
 *   - only the fields declared in templates.config.extra_fields are visible;
 *   - template-3's social_links entry expands to facebook/instagram/youtube;
 *   - Add row clones a uniquely named row and Remove deletes it;
 *   - the page raises no JavaScript errors.
 *
 * Usage: node tools/campaign-content-panel-check.mjs
 */

import { chromium } from 'playwright';
import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import net from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const pagePath = path.join(root, 'public/qa-campaign-content-panel.html');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const expected = {
    'default': [],
    'herbal-health': ['benefits', 'testimonials'],
    'template-1': ['countdown_date', 'features', 'why_us', 'contact_phone'],
    'template-2': ['countdown_date', 'features', 'why_us', 'contact_phone'],
    'template-3': ['video_url', 'features', 'benefits', 'packages', 'contact_phone', 'email', 'facebook', 'instagram', 'youtube'],
    'sample-template': ['features', 'benefits', 'testimonials', 'faqs'],
};

function freePort() {
    return new Promise((resolve, reject) => {
        const server = net.createServer();
        server.once('error', reject);
        server.listen(0, '127.0.0.1', () => {
            const { port } = server.address();
            server.close(() => resolve(port));
        });
    });
}

async function waitForServer(base, attempts = 80) {
    for (let i = 0; i < attempts; i += 1) {
        try {
            const response = await fetch(base + '/qa-campaign-content-panel.html');
            if (response.ok) {
                return;
            }
        } catch {
            // not listening yet
        }
        await sleep(250);
    }
    throw new Error('PHP server did not become ready');
}

async function visibleFields(page) {
    return page.evaluate(() => {
        const nodes = Array.from(document.querySelectorAll('#landingContentPanel [data-field]'));
        const values = nodes
            .filter((node) => node.classList.contains('landing-content-field'))
            .filter((node) => !node.classList.contains('d-none'))
            .map((node) => node.getAttribute('data-field'));

        return Array.from(new Set(values)).sort();
    });
}

async function main() {
    const failures = [];
    let browser;
    let server;

    try {
        const render = spawnSync('php', ['tools/render_campaign_content_panel.php', pagePath], {
            cwd: root,
            encoding: 'utf8',
        });
        if (render.status !== 0) {
            throw new Error('Panel render failed: ' + (render.stderr || render.stdout));
        }

        const port = await freePort();
        const base = `http://127.0.0.1:${port}`;

        server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', '.'], {
            cwd: path.join(root, 'public'),
            stdio: ['ignore', 'pipe', 'pipe'],
            env: { ...process.env, PHP_CLI_SERVER_WORKERS: '2' },
        });

        await waitForServer(base);

        browser = await chromium.launch();
        const page = await browser.newPage();
        const pageErrors = [];
        page.on('pageerror', (error) => pageErrors.push(String(error)));

        await page.goto(`${base}/qa-campaign-content-panel.html`, { waitUntil: 'networkidle' });

        const templateIds = await page.evaluate(() => window.__QA_TEMPLATES__ || {});

        for (const [viewName, fields] of Object.entries(expected)) {
            if (!templateIds[viewName]) {
                failures.push(`template id missing for ${viewName}`);
                continue;
            }

            await page.selectOption('#qa-template', templateIds[viewName]);
            const actual = await visibleFields(page);
            const wanted = [...fields].sort();

            if (JSON.stringify(actual) !== JSON.stringify(wanted)) {
                failures.push(`${viewName}: expected [${wanted.join(', ')}] got [${actual.join(', ')}]`);
            }
        }

        await page.selectOption('#qa-template', templateIds['sample-template']);

        const featureRows = page.locator('.js-rows[data-field="features"] .js-row');
        const before = await featureRows.count();

        await page.click('button.js-add-row[data-field="features"]');
        await sleep(50);

        const after = await featureRows.count();
        if (after !== before + 1) {
            failures.push(`add row did not append: ${before} -> ${after}`);
        }

        const lastName = await page
            .locator('.js-rows[data-field="features"] .js-row')
            .last()
            .locator('input[name*="[title]"]')
            .getAttribute('name');

        if (!/^extra_fields\[features\]\[1000\]\[title\]$/.test(lastName || '')) {
            failures.push(`new row has unexpected name: ${lastName}`);
        }

        await page.locator('.js-rows[data-field="features"] .js-row').last().locator('.js-remove-row').click();
        await sleep(50);

        const afterRemove = await featureRows.count();
        if (afterRemove !== before) {
            failures.push(`remove row did not delete: ${after} -> ${afterRemove}`);
        }

        if (pageErrors.length > 0) {
            failures.push('page errors: ' + pageErrors.join(' | '));
        }

        if (failures.length > 0) {
            console.error('FAIL ' + failures.join('\nFAIL '));
            process.exitCode = 1;
            return;
        }

        console.log('PASS template field filtering');
        console.log('PASS social_links expands to facebook/instagram/youtube');
        console.log('PASS add/remove repeatable row');
        console.log('CAMPAIGN_CONTENT_PANEL_OK');
    } finally {
        if (browser) {
            await browser.close();
        }

        if (server) {
            server.kill();
        }

        if (fs.existsSync(pagePath)) {
            fs.unlinkSync(pagePath);
        }
    }
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
