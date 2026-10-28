import { chromium } from 'playwright';
const browser = await chromium.launch({ executablePath: '/usr/bin/chromium-browser', headless: true, args: ['--no-sandbox'] });
console.log('version', browser.version());
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
await page.goto('http://127.0.0.1:8123/admin/login', { waitUntil: 'domcontentloaded' });
console.log('title', await page.title(), 'w', await page.evaluate(() => innerWidth));
await browser.close();
