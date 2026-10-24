import pw from '/home/xubuntu/Desktop/z-evo/tests/e2e/node_modules/@playwright/test/index.js';
const b = await pw.chromium.launch(); const p = await b.newPage();
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
console.log('document.cookie =', JSON.stringify(await p.evaluate(() => document.cookie)));
await b.close();
