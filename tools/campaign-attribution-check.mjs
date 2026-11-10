/**
 * Real-browser campaign attribution check.
 *
 * Proves the client side of the funnel on a real campaign landing page:
 *   - window.__CAMPAIGN__ carries campaign/product/price context;
 *   - view_item is pushed to dataLayer and ViewContent is queued;
 *   - the first interaction with the order form queues Engagement_FormStart;
 *   - submitting it queues Lead and flushes a collector batch that contains
 *     campaign_id and the attribution details.
 *
 * The collector request is stubbed so this check stays independent of the
 * database-writing feature suite; persistence is covered by
 * tests/Feature/CampaignAttributionTest.php.
 *
 * Usage: node tools/campaign-attribution-check.mjs
 */

import { chromium } from 'playwright';
import { spawn, spawnSync } from 'node:child_process';
import net from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const slug = 'qa-responsive-fixture';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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

function php(args) {
  const result = spawnSync('php', args, { cwd: root, encoding: 'utf8' });
  if (result.status !== 0) {
    throw new Error(`php ${args.join(' ')} failed: ${result.stderr || result.stdout}`);
  }
  return result.stdout.trim();
}

async function waitForServer(base, attempts = 80) {
  for (let i = 0; i < attempts; i += 1) {
    try {
      const response = await fetch(base + '/', { redirect: 'manual' });
      if (response.status > 0) return;
    } catch {
      // server not listening yet
    }
    await sleep(250);
  }
  throw new Error('PHP server did not become ready');
}

async function main() {
  const port = await freePort();
  const base = `http://127.0.0.1:${port}`;
  const failures = [];
  let browser;
  let server;

  try {
    php(['tools/landing_fixture.php', 'create', 'sample-template']);

    server = spawn('php', ['-S', `127.0.0.1:${port}`, '../server.php'], {
      cwd: path.join(root, 'public'),
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
    });

    await waitForServer(base);

    browser = await chromium.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));

    const response = await page.goto(`${base}/campaign/${slug}`, {
      waitUntil: 'domcontentloaded',
      timeout: 20000,
    });

    if (!response || response.status() !== 200) {
      throw new Error(`campaign page returned HTTP ${response ? response.status() : 'no response'}`);
    }

    await sleep(600);

    const initial = await page.evaluate(() => {
      const queue = JSON.parse(localStorage.getItem('__event_queue__') || '[]');
      const layer = window.dataLayer || [];

      return {
        campaign: window.__CAMPAIGN__ || null,
        viewItem: layer.find((row) => row && row.event === 'view_item') || null,
        queueNames: queue.map((row) => row.name),
        viewContent: queue.find((row) => row.name === 'ViewContent') || null,
        formCount: document.querySelectorAll('form[action*="/landing-order"]').length,
      };
    });

    if (!initial.campaign) failures.push('window.__CAMPAIGN__ is missing');
    if (!initial.viewItem) failures.push('dataLayer has no view_item event');
    if (!initial.viewContent) failures.push('event queue has no ViewContent');

    if (initial.campaign) {
      if (!(initial.campaign.id > 0)) failures.push('campaign context has no id');
      if (!(initial.campaign.product_id > 0)) failures.push('campaign context has no product_id');
      if (!(initial.campaign.price > 0)) failures.push('campaign context has no price');
      if (initial.campaign.currency !== 'BDT') failures.push('campaign context currency is not BDT');

      if (initial.viewItem) {
        if (Number(initial.viewItem.campaign_id) !== Number(initial.campaign.id)) {
          failures.push('view_item campaign_id does not match the page context');
        }
        if (Number(initial.viewItem.product_id) !== Number(initial.campaign.product_id)) {
          failures.push('view_item product_id does not match the page context');
        }
      }

      if (initial.viewContent) {
        const data = initial.viewContent.event_data || {};
        if (Number(data.campaign_id) !== Number(initial.campaign.id)) {
          failures.push('ViewContent event_data.campaign_id does not match the page context');
        }
        if (Number(data.product_id) !== Number(initial.campaign.product_id)) {
          failures.push('ViewContent event_data.product_id does not match the page context');
        }
        if (Number(initial.viewContent.campaign_id) !== Number(initial.campaign.id)) {
          failures.push('ViewContent row campaign_id does not match the page context');
        }
      }
    }

    if (initial.formCount < 1) failures.push('campaign page has no landing-order form');

    // Capture what the real logger would POST instead of writing QA rows from a
    // stub server. The fetch override must be installed before the flush.
    await page.evaluate(() => {
      window.__ATTRIBUTION_BATCHES__ = [];
      window.fetch = function (url, options) {
        try {
          if (String(url).indexOf('/events/collect') !== -1 && options && options.body) {
            window.__ATTRIBUTION_BATCHES__.push(JSON.parse(options.body));
          }
        } catch (e) {}
        return Promise.resolve(
          new Response('{"stored":0}', { status: 200, headers: { 'Content-Type': 'application/json' } })
        );
      };
    });

    await page.evaluate(() => {
      const form = document.querySelector('form[action*="/landing-order"]');
      const button = form.querySelector('button[type="submit"], button');
      button.dispatchEvent(new PointerEvent('pointerdown', {
        bubbles: true,
        cancelable: true,
        view: window,
      }));
    });

    await sleep(150);

    const afterStart = await page.evaluate(() => {
      const queue = JSON.parse(localStorage.getItem('__event_queue__') || '[]');
      const layer = window.dataLayer || [];
      return {
        names: queue.map((row) => row.name),
        start: queue.find((row) => row.name === 'Engagement_FormStart') || null,
        dataLayerStart: layer.find((row) => row && row.event === 'form_start') || null,
      };
    });

    if (!afterStart.start) failures.push('order-form interaction did not queue Engagement_FormStart');
    if (!afterStart.dataLayerStart) failures.push('order-form interaction did not push form_start');
    if (afterStart.start && initial.campaign
      && Number((afterStart.start.event_data || {}).campaign_id) !== Number(initial.campaign.id)) {
      failures.push('Engagement_FormStart lost the campaign context');
    }

    await page.evaluate(() => {
      const form = document.querySelector('form[action*="/landing-order"]');
      form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });

    await sleep(300);

    const afterSubmit = await page.evaluate(() => {
      const batches = window.__ATTRIBUTION_BATCHES__ || [];
      const events = batches.flatMap((batch) => batch.events || []);
      const layer = window.dataLayer || [];
      return {
        events,
        names: events.map((row) => row.name),
        lead: events.find((row) => row.name === 'Lead') || null,
        dataLayerLead: layer.find((row) => row && row.event === 'generate_lead') || null,
      };
    });

    const required = ['ViewContent', 'Engagement_FormStart', 'Lead'];
    for (const name of required) {
      if (!afterSubmit.names.includes(name)) failures.push(`collector batch is missing ${name}`);
    }
    if (!afterSubmit.dataLayerLead) failures.push('submit did not push generate_lead');

    if (afterSubmit.lead && initial.campaign) {
      const data = afterSubmit.lead.event_data || {};
      if (Number(afterSubmit.lead.campaign_id) !== Number(initial.campaign.id)) {
        failures.push('Lead row campaign_id does not match the page context');
      }
      if (Number(data.campaign_id) !== Number(initial.campaign.id)) {
        failures.push('Lead event_data.campaign_id does not match the page context');
      }
      if (Number(data.product_id) !== Number(initial.campaign.product_id)) {
        failures.push('Lead event_data.product_id does not match the page context');
      }
      if (Number(data.value) !== Number(initial.campaign.price)) {
        failures.push('Lead event_data.value does not match the campaign price');
      }
    }

    if (pageErrors.length > 0) {
      failures.push(`page errors: ${pageErrors.join(' | ')}`);
    }

    if (failures.length === 0) {
      console.log(`PASS campaign context campaign_id=${initial.campaign.id} product_id=${initial.campaign.product_id} price=${initial.campaign.price}`);
      console.log('PASS dataLayer view_item');
      console.log('PASS queue ViewContent with campaign context');
      console.log('PASS form_start on first order-form interaction');
      console.log('PASS Lead on submit with campaign context and campaign price');
    }
  } catch (error) {
    failures.push(`harness: ${error.message}`);
  } finally {
    try {
      php(['tools/landing_fixture.php', 'drop']);
    } catch (error) {
      failures.push(`cleanup: ${error.message}`);
    }
    if (browser) await browser.close().catch(() => {});
    if (server) {
      server.kill('SIGTERM');
      await sleep(400);
      if (!server.killed) server.kill('SIGKILL');
    }
  }

  if (failures.length > 0) {
    console.error('CAMPAIGN_ATTRIBUTION_BROWSER_FAIL');
    for (const failure of failures) console.error(' -', failure);
    process.exit(1);
  }

  console.log('CAMPAIGN_ATTRIBUTION_BROWSER_OK');
}

main().catch((error) => {
  console.error('CAMPAIGN_ATTRIBUTION_BROWSER_FAIL');
  console.error(error);
  process.exit(1);
});
