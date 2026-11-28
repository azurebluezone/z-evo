/*
 * Classic footer layout check (contract gc-mwxel9da).
 *
 * Renders the real classic home page in headless Chromium at five widths and
 * measures the footer instead of guessing. A "squashed" footer shows up as one
 * of these objective failures:
 *
 *   - the document scrolls sideways (a column is wider than the screen)
 *   - the about text is clipped by its max-height fence
 *   - two footer boxes overlap
 *   - a middle column is narrower than readable text
 *   - the footer itself collapses below its content height
 *
 * Screenshots and the raw geometry land in tools/verify/.artifacts/footer/.
 * Prints ZC-FOOTER-LAYOUT-OK only when every width passes.
 */
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const { chromium } = require('playwright');

const ROOT = path.resolve(__dirname, '..', '..');
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const OUT = path.resolve(__dirname, '.artifacts', 'footer');
const WIDTHS = [1440, 1280, 1024, 768, 390];

fs.mkdirSync(OUT, { recursive: true });

let failures = 0;
const report = { base_url: BASE, widths: [], checks: [] };

function pass(name, detail) {
    report.checks.push({ name, ok: true, detail: detail || '' });
    console.log('PASS: ' + name + (detail ? ' | ' + detail : ''));
}

function fail(name, detail) {
    report.checks.push({ name, ok: false, detail: detail || '' });
    failures += 1;
    console.log('FAIL: ' + name + (detail ? ' | ' + detail : ''));
}

async function reachable() {
    try {
        const response = await fetch(BASE + '/');
        return response.status < 500;
    } catch (error) {
        return false;
    }
}

async function ensureServer() {
    if (await reachable()) {
        return null;
    }

    const child = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'], {
        cwd: ROOT,
        stdio: 'ignore',
    });

    for (let attempt = 0; attempt < 80; attempt += 1) {
        await new Promise((resolve) => setTimeout(resolve, 250));
        if (await reachable()) {
            return child;
        }
    }

    child.kill();
    throw new Error('the dev server did not answer on ' + BASE);
}

function intersection(a, b) {
    const width = Math.min(a.right, b.right) - Math.max(a.left, b.left);
    const height = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top);
    return width > 0.5 && height > 0.5 ? Math.round(width * height) : 0;
}

(async () => {
    const server = await ensureServer();
    const browser = await chromium.launch({ headless: true });

    try {
        for (const width of WIDTHS) {
            const context = await browser.newContext({
                viewport: { width, height: width < 600 ? 844 : 900 },
                deviceScaleFactor: 1,
            });
            const page = await context.newPage();

            const response = await page.goto(BASE + '/', { waitUntil: 'load', timeout: 60000 });
            if (!response || response.status() >= 400) {
                fail('home page at ' + width, 'HTTP ' + (response ? response.status() : 'no response'));
                await context.close();
                continue;
            }

            await page.waitForSelector('.footer-area', { timeout: 15000 });
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
            await page.waitForTimeout(250);

            const metrics = await page.evaluate(() => {
                const rect = (element) => {
                    const box = element.getBoundingClientRect();
                    return {
                        left: box.left,
                        top: box.top,
                        right: box.right,
                        bottom: box.bottom,
                        width: box.width,
                        height: box.height,
                    };
                };

                const footer = document.querySelector('.footer-area');
                const top = document.querySelector('.footer-area .footer-top');
                const middle = document.querySelector('.footer-area .footer-middle');
                const about = document.querySelector('.footer-area .footer-about-text');

                const topChildren = top
                    ? Array.from(top.children).map((child) => ({
                          name: child.className || child.tagName.toLowerCase(),
                          box: rect(child),
                      }))
                    : [];

                const middleChildren = middle
                    ? Array.from(middle.children).map((child) => ({
                          name: child.className || child.tagName.toLowerCase(),
                          box: rect(child),
                      }))
                    : [];

                const linkOverflow = Array.from(
                    document.querySelectorAll('.footer-area .footer-widget ul li a, .footer-area .widget-contact ul li')
                )
                    .filter((element) => element.scrollWidth > element.clientWidth + 2)
                    .map((element) => (element.textContent || '').trim().slice(0, 40));

                const contact = document.querySelector('.footer-area .widget-contact');
                const consentBanner = document.querySelector('[role="dialog"][aria-label="Cookie choices"]');
                const copyright = document.querySelector('.footer-area .footer-copyright');

                return {
                    viewport: { width: window.innerWidth, height: window.innerHeight },
                    documentScrollWidth: document.documentElement.scrollWidth,
                    contact: contact ? rect(contact) : null,
                    emptyWidgets: Array.from(document.querySelectorAll('.footer-area .footer-middle > .footer-widget'))
                        .filter((widget) => widget.querySelectorAll('ul li a').length === 0)
                        .map((widget) => {
                            const title = widget.querySelector('.title');
                            return title ? title.textContent.trim() : 'unnamed widget';
                        }),
                    consent: {
                        banner: consentBanner ? rect(consentBanner) : null,
                        copyright: copyright ? rect(copyright) : null,
                        viewportHeight: window.innerHeight,
                    },
                    footer: footer ? rect(footer) : null,
                    top: top
                        ? {
                              box: rect(top),
                              gap: getComputedStyle(top).gap,
                              paddingTop: getComputedStyle(top).paddingTop,
                              display: getComputedStyle(top).display,
                          }
                        : null,
                    middle: middle
                        ? {
                              box: rect(middle),
                              columns: getComputedStyle(middle).gridTemplateColumns,
                              padding: getComputedStyle(middle).padding,
                              display: getComputedStyle(middle).display,
                          }
                        : null,
                    about: about
                        ? {
                              clientHeight: about.clientHeight,
                              scrollHeight: about.scrollHeight,
                              maxHeight: getComputedStyle(about).maxHeight,
                              fontSize: getComputedStyle(about).fontSize,
                              lineHeight: getComputedStyle(about).lineHeight,
                          }
                        : null,
                    topChildren,
                    middleChildren,
                    linkOverflow,
                };
            });

            report.widths.push({ width, metrics });

            const label = width + 'px';

            if (!metrics.footer) {
                fail('footer exists at ' + label);
                await context.close();
                continue;
            }

            if (metrics.documentScrollWidth <= metrics.viewport.width + 1) {
                pass('no horizontal overflow at ' + label, 'scrollWidth=' + metrics.documentScrollWidth);
            } else {
                fail(
                    'no horizontal overflow at ' + label,
                    'scrollWidth=' + metrics.documentScrollWidth + ' viewport=' + metrics.viewport.width
                );
            }

            if (metrics.about && metrics.about.scrollHeight <= metrics.about.clientHeight + 1) {
                pass('about text not clipped at ' + label, 'clientHeight=' + metrics.about.clientHeight);
            } else if (metrics.about) {
                fail(
                    'about text not clipped at ' + label,
                    'scrollHeight=' + metrics.about.scrollHeight + ' clientHeight=' + metrics.about.clientHeight
                );
            }

            const groups = [
                ['footer-top', metrics.topChildren],
                ['footer-middle', metrics.middleChildren],
            ];

            for (const [group, children] of groups) {
                let overlapping = null;
                for (let i = 0; i < children.length; i += 1) {
                    for (let j = i + 1; j < children.length; j += 1) {
                        const area = intersection(children[i].box, children[j].box);
                        if (area > 0) {
                            overlapping = children[i].name + ' x ' + children[j].name + ' (' + area + 'px2)';
                        }
                    }
                }

                if (!overlapping) {
                    pass(group + ' boxes do not overlap at ' + label, children.length + ' boxes');
                } else {
                    fail(group + ' boxes do not overlap at ' + label, overlapping);
                }
            }

            const minWidth = width >= 1024 ? 140 : 120;
            const narrow = metrics.middleChildren.filter((child) => child.box.width < minWidth);
            if (narrow.length === 0) {
                pass(
                    'middle columns stay readable at ' + label,
                    'min=' + Math.round(Math.min(...metrics.middleChildren.map((c) => c.box.width)))
                );
            } else {
                fail(
                    'middle columns stay readable at ' + label,
                    narrow.map((c) => c.name + '=' + Math.round(c.box.width)).join(', ')
                );
            }

            const minHeight = width >= 1024 ? 300 : 200;
            if (metrics.footer.height >= minHeight) {
                pass('footer keeps its height at ' + label, Math.round(metrics.footer.height) + 'px');
            } else {
                fail('footer keeps its height at ' + label, Math.round(metrics.footer.height) + 'px');
            }

            if (metrics.linkOverflow.length === 0) {
                pass('footer rows are not clipped at ' + label);
            } else {
                fail('footer rows are not clipped at ' + label, metrics.linkOverflow.join(' | '));
            }

            const contactMin = width >= 768 ? 200 : 0;
            if (!metrics.contact) {
                fail('contact column exists at ' + label);
            } else if (metrics.contact.width >= contactMin) {
                pass('contact column keeps a readable width at ' + label, Math.round(metrics.contact.width) + 'px');
            } else {
                fail('contact column keeps a readable width at ' + label, Math.round(metrics.contact.width) + 'px');
            }

            if (metrics.emptyWidgets.length === 0) {
                pass('no empty footer column at ' + label);
            } else {
                fail('no empty footer column at ' + label, metrics.emptyWidgets.join(', '));
            }

            if (!metrics.consent.banner) {
                pass('consent banner does not cover the copyright at ' + label, 'banner not shown');
            } else if (
                metrics.consent.copyright &&
                metrics.consent.copyright.bottom <=
                    metrics.consent.viewportHeight - metrics.consent.banner.height + 1
            ) {
                pass(
                    'consent banner does not cover the copyright at ' + label,
                    'banner=' + Math.round(metrics.consent.banner.height) + 'px'
                );
            } else {
                fail(
                    'consent banner does not cover the copyright at ' + label,
                    'copyright bottom=' +
                        Math.round(metrics.consent.copyright ? metrics.consent.copyright.bottom : -1) +
                        ' banner top=' +
                        Math.round(metrics.consent.viewportHeight - metrics.consent.banner.height)
                );
            }

            await page.locator('.footer-area').screenshot({ path: path.join(OUT, 'footer-' + width + '.png') });
            await context.close();
        }
    } finally {
        await browser.close();
        if (server) {
            server.kill();
        }
    }

    fs.writeFileSync(path.join(OUT, 'geometry.json'), JSON.stringify(report, null, 2));

    console.log('');
    console.log('Screenshots and geometry: ' + OUT);

    if (failures > 0) {
        console.log('FOOTER LAYOUT FAILED (' + failures + ' checks)');
        process.exit(1);
    }

    console.log('ZC-FOOTER-LAYOUT-OK');
})().catch((error) => {
    console.error('FOOTER LAYOUT CHECK ERROR: ' + error.message);
    process.exit(1);
});
