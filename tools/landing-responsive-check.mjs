/**
 * Real-browser responsive check for the six campaign landing templates.
 *
 * Starts the Laravel app with PHP's built-in server, creates a QA campaign,
 * switches it through every template, and asserts at 390/768/1440 that
 * documentElement.scrollWidth never exceeds the viewport. Also proves the
 * extracted template CSS + shared responsive.css actually load with HTTP 200.
 *
 * Usage: node tools/landing-responsive-check.mjs
 */

import { chromium } from 'playwright';
import { spawn, spawnSync } from 'node:child_process';
import net from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const templates = ['default', 'herbal-health', 'sample-template', 'template-1', 'template-2', 'template-3'];
const viewports = [
  { width: 390, height: 844 },
  { width: 768, height: 1024 },
  { width: 1440, height: 900 },
];
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
    php(['tools/landing_fixture.php', 'create', 'template-1']);

    // server.php expects getcwd() to be public/: it serves existing files and
    // falls back to public/index.php for routes.
    server = spawn('php', ['-S', `127.0.0.1:${port}`, '../server.php'], {
      cwd: path.join(root, 'public'),
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
    });

    let serverError = '';
    server.stderr.on('data', (chunk) => {
      serverError += chunk.toString();
    });

    await waitForServer(base);

    // Playwright's bundled Chromium is the supported binary here; the distro
    // /usr/bin/chromium-browser is a snap stub on this machine.
    browser = await chromium.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    const page = await browser.newPage({ viewport: viewports[0] });
    let responses = [];
    let pageErrors = [];

    page.on('response', (response) => {
      const url = response.url();
      if (url.includes('/frontend/landing/')) {
        responses.push({ url, status: response.status() });
      }
    });
    page.on('pageerror', (error) => pageErrors.push(error.message));

    for (const template of templates) {
      php(['tools/landing_fixture.php', 'template', template]);

      for (const viewport of viewports) {
        responses = [];
        pageErrors = [];
        await page.setViewportSize(viewport);

        try {
          const response = await page.goto(`${base}/campaign/${slug}`, {
            waitUntil: 'domcontentloaded',
            timeout: 20000,
          });

          if (!response || response.status() !== 200) {
            failures.push(`${template}@${viewport.width}: HTTP ${response ? response.status() : 'no response'}`);
            continue;
          }

          await sleep(700);

          const metrics = await page.evaluate(() => {
            const vw = window.innerWidth;
            const offenders = Array.from(document.querySelectorAll('body *'))
              .map((el) => {
                const rect = el.getBoundingClientRect();
                return {
                  tag: el.tagName.toLowerCase(),
                  cls: (el.className || '').toString().slice(0, 60),
                  right: Math.round(rect.right * 10) / 10,
                  width: Math.round(rect.width * 10) / 10,
                };
              })
              .filter((item) => item.right > vw + 1)
              .slice(0, 6);

            const countdown = document.querySelector('#cd-days');

            return {
              innerWidth: vw,
              scrollWidth: document.documentElement.scrollWidth,
              bodyScrollWidth: document.body.scrollWidth,
              countdownDays: countdown ? countdown.textContent.trim() : null,
              offenders,
            };
          });

          if (metrics.scrollWidth > metrics.innerWidth + 1) {
            failures.push(
              `${template}@${viewport.width}: scrollWidth=${metrics.scrollWidth} > innerWidth=${metrics.innerWidth}; ` +
                `offenders=${JSON.stringify(metrics.offenders)}`
            );
          }

          const cssHit = responses.find(
            (item) => item.url.includes(`/frontend/landing/${template}.css`) && item.status === 200
          );
          const responsiveHit = responses.find(
            (item) => item.url.includes('/frontend/landing/responsive.css') && item.status === 200
          );

          if (!cssHit) failures.push(`${template}@${viewport.width}: ${template}.css was not served with 200`);
          if (!responsiveHit) failures.push(`${template}@${viewport.width}: responsive.css was not served with 200`);

          // Proves the extracted JS actually executes, not just downloads.
          if (metrics.countdownDays === '00') {
            failures.push(`${template}@${viewport.width}: extracted countdown JS did not run`);
          }
          if (pageErrors.length > 0) {
            failures.push(`${template}@${viewport.width}: JS page errors: ${pageErrors.join(' | ')}`);
          }

          // Every commerce template now renders a real submit button; the
          // union keeps the check honest for any future template that only
          // offers a styled anchor as its primary action.
          const actionVisible = await page
            .locator('button[type="submit"], .cta-button, .btn-main, .btn-submit, .submit-btn')
            .first()
            .isVisible()
            .catch(() => false);
          if (!actionVisible) failures.push(`${template}@${viewport.width}: primary order control is not visible`);

          if (failures.length === 0 || !failures.some((line) => line.startsWith(`${template}@${viewport.width}:`))) {
            console.log(
              `PASS ${template}@${viewport.width} scrollWidth=${metrics.scrollWidth} ` +
                `css=${cssHit ? 'ok' : 'missing'} responsive=${responsiveHit ? 'ok' : 'missing'} action=visible`
            );
          }
        } catch (error) {
          failures.push(`${template}@${viewport.width}: ${error.message}`);
        }
      }
    }

    if (serverError) {
      // PHP's built-in server logs every request to stderr; only surface it on failure.
      if (failures.length > 0) console.error(serverError.slice(-2000));
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
    console.error('LANDING_RESPONSIVE_FAIL');
    for (const failure of failures) console.error(' -', failure);
    process.exit(1);
  }

  console.log(`LANDING_RESPONSIVE_OK (${templates.length} templates x ${viewports.length} viewports)`);
}

main().catch((error) => {
  console.error('LANDING_RESPONSIVE_FAIL');
  console.error(error);
  process.exit(1);
});
