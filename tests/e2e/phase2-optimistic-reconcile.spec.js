const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const SCREENSHOT_DIR = path.resolve(__dirname, '../../work/Codex/screenshots/phase-2');

function parseCount(value) {
  const n = parseInt(String(value ?? '').replace(/[^\d-]/g, ''), 10);
  return Number.isFinite(n) ? n : 0;
}

async function readCartUiState(page) {
  return page.evaluate(() => {
    const readTexts = (selector) => Array.from(document.querySelectorAll(selector)).map((el) => (el.textContent || '').trim());
    return {
      cartCountTexts: readTexts('.cart-count'),
      cartItemCountTexts: readTexts('.cartItemCount'),
      localStorageCount: localStorage.getItem('cart_count'),
    };
  });
}

async function firstInStockCard(page) {
  const card = page.locator('.product-card[data-in-stock="1"]').first();
  await expect(card).toBeVisible();
  return card;
}

test.describe('Phase-2 Optimistic UI + Reconcile/Rollback', () => {
  test.beforeEach(async ({ page }) => {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
    await page.goto('/products', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.product-card .addToCart', { timeout: 20000 });
  });

  test('instant click-response and reconcile cart badge', async ({ page }) => {
    const card = await firstInStockCard(page);
    const addBtn = card.locator('.addToCart').first();

    const initialState = await readCartUiState(page);
    const initialCount = parseCount(initialState.cartItemCountTexts[0] || initialState.cartCountTexts[0] || '0');

    const start = Date.now();
    await addBtn.click();

    const qtyValue = card.locator('.product-card-qty-value');
    await expect(qtyValue).toHaveText('1', { timeout: 1200 });
    const optimisticLatencyMs = Date.now() - start;
    expect(optimisticLatencyMs).toBeLessThan(1200);

    await expect
      .poll(async () => {
        const state = await readCartUiState(page);
        const fromItem = parseCount(state.cartItemCountTexts[0] || '0');
        const fromBadge = parseCount(state.cartCountTexts[0] || '0');
        return Math.max(fromItem, fromBadge);
      }, {
        timeout: 15000,
      })
      .toBeGreaterThanOrEqual(initialCount + 1);

    const finalState = await readCartUiState(page);
    const settledCount = Math.max(
      parseCount(finalState.cartItemCountTexts[0] || '0'),
      parseCount(finalState.cartCountTexts[0] || '0'),
    );
    expect(parseCount(finalState.localStorageCount || '0')).toBe(settledCount);

    await page.screenshot({
      path: path.join(SCREENSHOT_DIR, 'phase2-optimistic-instant.png'),
      fullPage: true,
    });
  });

  test('rollback simulation on forced server failure', async ({ page }) => {
    await page.route('**/add-to-cart/**', async (route) => {
      await route.abort('failed');
    });

    const card = await firstInStockCard(page);
    await card.locator('.addToCart').first().click();

    await expect
      .poll(async () => {
        const qty = await card.getAttribute('data-cart-qty');
        return parseCount(qty || '0');
      }, {
        timeout: 12000,
      })
      .toBe(0);

    await expect(card.locator('.addToCart').first()).toBeVisible();

    await page.screenshot({
      path: path.join(SCREENSHOT_DIR, 'phase2-rollback-on-fail.png'),
      fullPage: true,
    });
  });

  test('cart page/header settled state snapshot', async ({ page }) => {
    const card = await firstInStockCard(page);
    await card.locator('.addToCart').first().click();

    await expect
      .poll(async () => {
        const state = await readCartUiState(page);
        return Math.max(
          parseCount(state.cartItemCountTexts[0] || '0'),
          parseCount(state.cartCountTexts[0] || '0'),
        );
      }, {
        timeout: 15000,
      })
      .toBeGreaterThan(0);

    await page.screenshot({
      path: path.join(SCREENSHOT_DIR, 'phase2-cart-page-state.png'),
      fullPage: true,
    });
  });

  test('rapid 3 clicks: qty-sum badge, notification-per-click, no reload', async ({ page }) => {
    const card = await firstInStockCard(page);
    const addBtn = card.locator('.addToCart').first();

    const initialState = await readCartUiState(page);
    const initialCount = parseCount(initialState.cartItemCountTexts[0] || initialState.cartCountTexts[0] || '0');

    await page.evaluate(() => {
      window.__phase2NoReloadMarker = `marker-${Date.now()}`;
      window.__phase2NotifyMessages = [];
      const previousNotify = window.notifyCart;
      window.notifyCart = function (message) {
        window.__phase2NotifyMessages.push(String(message || ''));
        if (typeof previousNotify === 'function') {
          return previousNotify(message);
        }
      };
    });

    await addBtn.click();
    const plus = card.locator('.product-card-qty-plus').first();
    await expect(plus).toBeVisible();
    await Promise.all([plus.click(), plus.click()]);

    await expect(card.locator('.product-card-qty-value')).toHaveText('3', { timeout: 8000 });

    await expect
      .poll(async () => {
        const state = await readCartUiState(page);
        const fromItem = parseCount(state.cartItemCountTexts[0] || '0');
        const fromBadge = parseCount(state.cartCountTexts[0] || '0');
        return Math.max(fromItem, fromBadge);
      }, { timeout: 15000 })
      .toBeGreaterThanOrEqual(initialCount + 3);

    const marker = await page.evaluate(() => window.__phase2NoReloadMarker || null);
    expect(typeof marker).toBe('string');
    expect(marker.startsWith('marker-')).toBeTruthy();
    await expect(page).toHaveURL(/\/products/);

    const notifications = await page.evaluate(() => Array.isArray(window.__phase2NotifyMessages) ? window.__phase2NotifyMessages.length : 0);
    expect(notifications).toBeGreaterThanOrEqual(3);

    await page.screenshot({
      path: path.join(SCREENSHOT_DIR, 'phase2-rapid-3-clicks-qty-sum.png'),
      fullPage: true,
    });
  });
});
